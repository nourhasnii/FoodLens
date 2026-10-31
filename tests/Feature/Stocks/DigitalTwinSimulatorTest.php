<?php

/*
| Module Stocks — jumeau numérique « Et si ? » (simulation sans IA / avec IA).
*/

use App\Models\Site;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockSimulation;
use App\Services\Simulation\DigitalTwinSimulator;
use App\Services\Stock\StockService;

require_once __DIR__.'/StocksTestHelpers.php';

beforeEach(function () {
    $this->withoutVite();
    $this->stocks = app(StockService::class);
    $this->simulator = app(DigitalTwinSimulator::class);

    $this->product = stockProduct('Lait frais 1 L');
    $this->warehouse = Site::factory()->warehouse()->create(['name' => 'Entrepôt Sfax', 'latitude' => 34.7406, 'longitude' => 10.7603, 'capacity' => 100000]);
    $this->store = Site::factory()->retailStore()->create(['name' => 'Magasin Sousse', 'latitude' => 35.8256, 'longitude' => 10.6360, 'capacity' => 100000]);
    $this->other = Site::factory()->retailStore()->create(['name' => 'Magasin Monastir', 'latitude' => 35.7643, 'longitude' => 10.8113, 'capacity' => 100000]);
    $this->association = Site::factory()->association()->create(['name' => 'Association Sfax', 'latitude' => 34.7521, 'longitude' => 10.7700]);

    // 8 semaines de ventes : 20 u./jour à Sousse, 10 u./jour à Monastir
    foreach ([[$this->store, 20], [$this->other, 10]] as [$site, $qty]) {
        for ($d = 1; $d <= 56; $d++) {
            StockMovement::factory()->sale()->from($site)->on(now()->subDays($d)->setTime(12, 0))
                ->create(['product_id' => $this->product->id, 'quantity' => $qty]);
        }
    }

    // Stock de rayon (sans lot) et un lot à l'entrepôt qui périme dans 3 jours
    $this->stocks->openLine(['site_id' => $this->store->id, 'product_id' => $this->product->id, 'quantity' => 60, 'min_threshold' => 50]);
    $this->stocks->openLine(['site_id' => $this->other->id, 'product_id' => $this->product->id, 'quantity' => 100, 'min_threshold' => 30]);
    $this->batch = stockBatch($this->product, days: 3);
    $this->stocks->record(['type' => 'in', 'batch_id' => $this->batch->id, 'destination_site_id' => $this->warehouse->id, 'quantity' => 300]);
});

/** Stock de départ = vendu + jeté + donné + restant, pour une stratégie. */
function conserved(array $result, string $strategy): float
{
    $t = $result[$strategy]['totals'];

    return round($result['initial']['units'] - ($t['sold'] + $t['wasted'] + $t['donated'] + $t['remaining']), 2);
}

it('ne perd ni ne crée aucune unité (bilan équilibré dans les deux stratégies)', function () {
    $r = $this->simulator->run(['horizon' => 14]);

    expect($r['initial']['units'])->toEqual(460.0)
        ->and(conserved($r, 'baseline'))->toEqual(0.0)
        ->and(conserved($r, 'ai'))->toEqual(0.0)
        ->and($r['days'])->toHaveCount(14)
        ->and($r['ai']['frames'])->toHaveCount(14);
});

it('gaspille moins avec l’IA qu’avec la pratique habituelle', function () {
    $r = $this->simulator->run(['horizon' => 14]);
    $b = $r['baseline']['totals'];
    $a = $r['ai']['totals'];

    expect($b['wasted'])->toBeGreaterThan(0)
        ->and($a['wasted'])->toBeLessThan($b['wasted'])
        ->and($a['donated'] + $a['transferred'])->toBeGreaterThan(0)
        ->and($r['comparison']['wasted']['reduction_pct'])->toBeGreaterThan(0)
        ->and(collect($r['ai']['actions'])->pluck('kind'))->toContain('transfer');
});

it('donne aux associations la veille de la DLC ce qui ne sera pas vendu', function () {
    $r = $this->simulator->run(['horizon' => 7]);

    $donations = collect($r['ai']['actions'])->where('kind', 'donation');
    expect($donations)->not->toBeEmpty()
        ->and($r['ai']['totals']['meals'])->toBeGreaterThan(0)
        ->and($donations->first()['text'])->toContain('Association Sfax');
});

it('compte les ventes perdues quand un magasin ferme, pour les deux stratégies', function () {
    $normal = $this->simulator->run(['horizon' => 7]);
    $closed = $this->simulator->run(['horizon' => 7, 'closed' => ['site_id' => $this->store->id, 'from' => 1, 'days' => 3]]);

    // 3 jours sans vente à Sousse (≈ 20 u./jour)
    expect($closed['baseline']['totals']['unmet'])->toBeGreaterThan($normal['baseline']['totals']['unmet'] + 50)
        ->and($closed['ai']['totals']['unmet'])->toBeGreaterThan($normal['ai']['totals']['unmet'] + 50)
        ->and(collect($closed['ai']['frames'][1]['sites'])->firstWhere('id', $this->store->id)['status'])->toBe('closed');
});

it('applique un pic de demande à la demande prévue', function () {
    $normal = $this->simulator->run(['horizon' => 7]);
    $peak = $this->simulator->run(['horizon' => 7, 'demand' => ['change' => 50]]);

    expect($peak['baseline']['totals']['demand'])->toEqual(round($normal['baseline']['totals']['demand'] * 1.5, 2));
});

it('raccourcit la DLC en cas de rupture de la chaîne du froid', function () {
    $normal = $this->simulator->run(['horizon' => 7]);
    $cold = $this->simulator->run(['horizon' => 7, 'cold_chain' => ['site_id' => $this->warehouse->id, 'day' => 0, 'lost_days' => 2]]);

    expect($cold['baseline']['totals']['wasted'])->toBeGreaterThanOrEqual($normal['baseline']['totals']['wasted'])
        ->and(collect($cold['ai']['actions'])->pluck('kind'))->toContain('event')
        ->and(conserved($cold, 'ai'))->toEqual(0.0);
});

it('ne modifie jamais les stocks réels', function () {
    $before = [Stock::sum('quantity'), StockMovement::count()];

    $this->simulator->run(['horizon' => 14, 'transport' => ['delay' => 2]]);

    expect([Stock::sum('quantity'), StockMovement::count()])->toEqual($before);
});

it('borne les réglages du scénario', function () {
    $s = $this->simulator->normalize(['horizon' => 99, 'demand' => ['change' => 999, 'to' => 500], 'transport' => ['delay' => 30]]);

    expect($s['horizon'])->toBe(DigitalTwinSimulator::MAX_HORIZON)
        ->and($s['demand']['change'])->toEqual(200.0)
        ->and($s['demand']['to'])->toBe(DigitalTwinSimulator::MAX_HORIZON - 1)
        ->and($s['transport']['delay'])->toBe(7);
});

it('lance une simulation depuis la page et l’enregistre', function () {
    $user = stockUser('distributeur');

    $this->actingAs($user)->get(route('simulations.index'))->assertOk()->assertSee('Jumeau numérique');

    $this->actingAs($user)->post(route('simulations.store'), [
        'preset' => 'store_closed', 'horizon' => 7,
        'closed_site_id' => $this->store->id, 'closed_from' => 2, 'closed_days' => 3,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $simulation = StockSimulation::firstOrFail();
    expect($simulation->user_id)->toBe($user->id)
        ->and($simulation->horizon_days)->toBe(7)
        ->and($simulation->scenario['closed']['from'])->toBe(1) // « jour 2 » saisi = indice 1
        ->and($simulation->results['ai']['totals'])->toHaveKey('wasted');

    $this->actingAs($user)->get(route('simulations.show', $simulation))
        ->assertOk()->assertSee('Sans IA ou avec IA ?')->assertSee('Journal des décisions');
});

it('valide le formulaire de simulation', function () {
    $this->actingAs(stockUser('distributeur'))
        ->post(route('simulations.store'), ['preset' => 'inconnu', 'horizon' => 3, 'demand_from' => 5, 'demand_to' => 2])
        ->assertSessionHasErrors(['preset', 'horizon', 'demand_to']);

    expect(StockSimulation::count())->toBe(0);
});

it('supprime une simulation et protège l’écran du consommateur', function () {
    $simulation = StockSimulation::factory()->create();

    $this->actingAs(stockUser('consommateur'))->get(route('simulations.show', $simulation))->assertForbidden();

    $this->actingAs(stockUser('admin'))->delete(route('simulations.destroy', $simulation))
        ->assertRedirect(route('simulations.index'));
    expect(StockSimulation::count())->toBe(0);
});
