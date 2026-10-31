<?php

/*
| Module Stocks — inventaire par photo (IA de vision via Ollama, simulée avec Http::fake).
*/

use App\Models\PhotoInventory;
use App\Models\Site;
use App\Models\StockMovement;
use App\Services\Inventory\PhotoInventoryService;
use App\Services\Stock\StockService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/StocksTestHelpers.php';

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('local');
    config(['services.ollama.endpoint' => 'http://ollama.test/api/generate']);

    $this->stocks = app(StockService::class);
    $this->user = stockUser('distributeur');
    $this->site = Site::factory()->retailStore()->create(['name' => 'Magasin Sousse']);
    $this->yaourt = stockProduct('Yaourt nature 125 g');
    $this->lait = stockProduct('Lait frais 1 L');

    $this->stocks->openLine(['site_id' => $this->site->id, 'product_id' => $this->yaourt->id, 'quantity' => 120]);
    $this->stocks->openLine(['site_id' => $this->site->id, 'product_id' => $this->lait->id, 'quantity' => 40]);
});

/** Réponse Ollama simulée : le JSON du modèle est dans le champ « response ». */
function fakeVision(array $json): void
{
    Http::fake(['ollama.test/*' => Http::response([
        'model' => 'qwen2.5vl', 'done' => true, 'response' => json_encode($json),
    ])]);
}

function sendPhoto($test, int $siteId)
{
    return $test->actingAs($test->user)->post(route('photo-inventories.store'), [
        'site_id' => $siteId,
        'photo' => UploadedFile::fake()->image('rayon.jpg', 800, 600),
    ]);
}

it('compte les produits de la photo avec l’IA et les rapproche du stock', function () {
    fakeVision(['items' => [
        ['product' => 'yaourt nature', 'count' => 112, 'confidence' => 0.9],
        ['product' => 'Lait frais 1 L', 'count' => 40, 'confidence' => 0.45],
        ['product' => 'Boîte de thon', 'count' => 6, 'confidence' => 0.7],
    ], 'notes' => 'Deux pots renversés au fond.']);

    sendPhoto($this, $this->site->id)->assertRedirect()->assertSessionHasNoErrors();

    $inv = PhotoInventory::firstOrFail();
    $items = collect($inv->items)->keyBy('product_id');

    expect($inv->status)->toBe(PhotoInventory::ANALYZED)
        ->and($inv->ai_model)->toBe('qwen2.5vl')
        ->and($items[$this->yaourt->id]['system_qty'])->toEqual(120.0)
        ->and($items[$this->yaourt->id]['ai_count'])->toEqual(112.0)
        ->and($items[$this->lait->id]['confidence'])->toEqual(0.45)
        ->and($inv->unmatched)->toHaveCount(1)
        ->and($inv->unmatched[0]['product'])->toBe('Boîte de thon')
        ->and($inv->ai_notes)->toContain('renversés')
        ->and($inv->discrepancies())->toBe(1);

    // La photo et la liste des produits ont bien été envoyées au modèle
    Http::assertSent(fn ($request) => count($request['images']) === 1
        && str_contains($request['prompt'], 'Yaourt nature 125 g')
        && $request['format'] === 'json');

    $this->actingAs($this->user)->get(route('photo-inventories.show', $inv))
        ->assertOk()->assertSee('Deux pots renversés')->assertSee('à vérifier')->assertSee('Boîte de thon');
});

it('passe en saisie manuelle si Ollama n’est pas lancé', function () {
    Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('Connection refused'));

    sendPhoto($this, $this->site->id)->assertRedirect();

    $inv = PhotoInventory::firstOrFail();
    expect($inv->status)->toBe(PhotoInventory::FAILED)
        ->and($inv->error)->toContain('ollama serve')
        ->and(collect($inv->items)->pluck('ai_count')->filter())->toBeEmpty()
        ->and($inv->items)->toHaveCount(2); // les produits du site restent proposés à la saisie

    $this->actingAs($this->user)->get(route('photo-inventories.show', $inv))->assertOk()->assertSee('Relancer l’analyse IA');
});

it('passe en saisie manuelle si l’IA répond dans un format illisible', function () {
    Http::fake(['ollama.test/*' => Http::response(['model' => 'qwen2.5vl', 'response' => 'Je vois des yaourts.'])]);

    sendPhoto($this, $this->site->id);

    expect(PhotoInventory::firstOrFail()->status)->toBe(PhotoInventory::FAILED);
});

it('transforme le comptage validé en mouvements d’ajustement', function () {
    fakeVision(['items' => [['product' => 'Yaourt nature 125 g', 'count' => 112, 'confidence' => 0.9]]]);
    sendPhoto($this, $this->site->id);
    $inv = PhotoInventory::firstOrFail();

    // L'utilisateur corrige : 110 yaourts (et non 112), 45 laits ; rien n'a changé avant la validation
    expect($this->stocks->availableAt($this->site->id, $this->yaourt->id))->toEqual(120.0);

    $this->actingAs($this->user)->post(route('photo-inventories.apply', $inv), [
        'counts' => [$this->yaourt->id => 110, $this->lait->id => 45],
    ])->assertRedirect(route('photo-inventories.show', $inv))->assertSessionHasNoErrors();

    $inv->refresh();
    expect($inv->status)->toBe(PhotoInventory::APPLIED)
        ->and($inv->adjustments_count)->toBe(2)
        ->and($inv->applied_by)->toBe($this->user->id)
        ->and($this->stocks->availableAt($this->site->id, $this->yaourt->id))->toEqual(110.0)
        ->and($this->stocks->availableAt($this->site->id, $this->lait->id))->toEqual(45.0)
        ->and(StockMovement::where('reason', 'Inventaire photo #'.$inv->id)->count())->toBe(2);
});

it('ne touche pas aux produits laissés vides et refuse une seconde validation', function () {
    fakeVision(['items' => []]);
    sendPhoto($this, $this->site->id);
    $inv = PhotoInventory::firstOrFail();

    $this->actingAs($this->user)->post(route('photo-inventories.apply', $inv), [
        'counts' => [$this->yaourt->id => 120, $this->lait->id => ''],
    ])->assertSessionHasNoErrors();

    expect($inv->fresh()->adjustments_count)->toBe(0)
        ->and($this->stocks->availableAt($this->site->id, $this->lait->id))->toEqual(40.0);

    $this->actingAs($this->user)->post(route('photo-inventories.apply', $inv), ['counts' => [$this->yaourt->id => 1]])
        ->assertSessionHasErrors('counts');
    expect($this->stocks->availableAt($this->site->id, $this->yaourt->id))->toEqual(120.0);
});

it('retire un manque du stock sans lot d’abord, puis du lot le plus proche de sa DLC', function () {
    $soon = stockBatch($this->yaourt, days: 3);
    $later = stockBatch($this->yaourt, days: 20);
    $this->stocks->record(['type' => 'in', 'batch_id' => $soon->id, 'destination_site_id' => $this->site->id, 'quantity' => 30]);
    $this->stocks->record(['type' => 'in', 'batch_id' => $later->id, 'destination_site_id' => $this->site->id, 'quantity' => 30]);
    $inv = PhotoInventory::factory()->at($this->site)->create(['items' => app(PhotoInventoryService::class)->productsAt($this->site->id)
        ->map(fn ($p) => ['product_id' => $p->id, 'name' => $p->name, 'unit' => $p->unit, 'system_qty' => 0, 'ai_count' => null, 'confidence' => null, 'ai_label' => null, 'final_qty' => null])->all()]);

    // 180 enregistrés (120 sans lot + 30 + 30), 50 comptés : il manque 130
    app(PhotoInventoryService::class)->apply($inv, [$this->yaourt->id => 50], $this->user);

    $line = fn ($batchId) => (float) \App\Models\Stock::where('site_id', $this->site->id)->where('product_id', $this->yaourt->id)->where('batch_id', $batchId)->value('quantity');
    expect($line(null))->toEqual(0.0)
        ->and($line($soon->id))->toEqual(20.0)
        ->and($line($later->id))->toEqual(30.0);
});

it('rapproche les noms lus par l’IA des produits (sans accents ni majuscules)', function () {
    $service = app(PhotoInventoryService::class);

    expect($service->similarity('YAOURT nature', 'Yaourt nature 125 g'))->toEqual(100.0)
        ->and($service->similarity('lait frais', 'Lait frais 1 L'))->toEqual(100.0)
        ->and($service->similarity('pot de yaourt', 'Yaourt nature 125 g'))->toBeGreaterThanOrEqual(55)
        ->and($service->similarity('boîte de thon', 'Lait frais 1 L'))->toBeLessThan(55);
});

it('valide l’envoi de la photo et protège l’écran', function () {
    $this->actingAs($this->user)->post(route('photo-inventories.store'), [
        'site_id' => 999999,
        'photo' => UploadedFile::fake()->create('notes.pdf', 50, 'application/pdf'),
    ])->assertSessionHasErrors(['site_id', 'photo']);

    $this->actingAs(stockUser('consommateur'))->get(route('photo-inventories.index'))->assertForbidden();
    $this->actingAs($this->user)->get(route('photo-inventories.index'))->assertOk()->assertSee('Inventaire par photo');
});

it('sert la photo seulement aux utilisateurs autorisés', function () {
    fakeVision(['items' => []]);
    sendPhoto($this, $this->site->id);
    $inv = PhotoInventory::firstOrFail();

    $this->actingAs($this->user)->get(route('photo-inventories.photo', $inv))->assertOk();
    $this->actingAs(stockUser('consommateur'))->get(route('photo-inventories.photo', $inv))->assertForbidden();
});
