<?php

/*
| Module Stocks — calcul automatique du stock et alertes de rupture.
*/

use App\Events\StockLevelLow;
use App\Models\Site;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Notifications\LowStockNotification;
use App\Services\Stock\StockService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

require_once __DIR__.'/StocksTestHelpers.php';

beforeEach(function () {
    $this->withoutVite();
    $this->service = app(StockService::class);
    $this->product = stockProduct();
    $this->warehouse = Site::factory()->warehouse()->create(['capacity' => 100000]);
    $this->store = Site::factory()->retailStore()->create(['capacity' => 100000]);
});

it('calcule le stock : entrée 100, sortie 30 → 70', function () {
    $this->service->record(['type' => 'in', 'product_id' => $this->product->id, 'destination_site_id' => $this->warehouse->id, 'quantity' => 100]);
    $this->service->record(['type' => 'out', 'product_id' => $this->product->id, 'source_site_id' => $this->warehouse->id, 'quantity' => 30]);

    expect($this->service->availableAt($this->warehouse->id, $this->product->id))->toEqual(70.0)
        ->and(StockMovement::count())->toBe(2);
});

it('déplace la quantité lors d’un transfert entre deux sites', function () {
    $this->service->record(['type' => 'in', 'product_id' => $this->product->id, 'destination_site_id' => $this->warehouse->id, 'quantity' => 100]);
    $this->service->record(['type' => 'transfer', 'product_id' => $this->product->id, 'source_site_id' => $this->warehouse->id, 'destination_site_id' => $this->store->id, 'quantity' => 40]);

    expect($this->service->availableAt($this->warehouse->id, $this->product->id))->toEqual(60.0)
        ->and($this->service->availableAt($this->store->id, $this->product->id))->toEqual(40.0);
});

it('ajuste le stock après un inventaire physique', function () {
    $this->service->record(['type' => 'in', 'product_id' => $this->product->id, 'destination_site_id' => $this->warehouse->id, 'quantity' => 100]);
    $movement = $this->service->record(['type' => 'adjustment', 'product_id' => $this->product->id, 'site_id' => $this->warehouse->id, 'counted_quantity' => 92]);

    expect($movement->quantity)->toEqual(8.0)
        ->and($movement->source_site_id)->toBe($this->warehouse->id)
        ->and($this->service->availableAt($this->warehouse->id, $this->product->id))->toEqual(92.0);
});

it('refuse une sortie supérieure au stock disponible', function () {
    $this->service->record(['type' => 'in', 'product_id' => $this->product->id, 'destination_site_id' => $this->warehouse->id, 'quantity' => 10]);

    $this->service->record(['type' => 'out', 'product_id' => $this->product->id, 'source_site_id' => $this->warehouse->id, 'quantity' => 50]);
})->throws(ValidationException::class);

it('refuse un transfert vers le même site', function () {
    $this->service->record(['type' => 'in', 'product_id' => $this->product->id, 'destination_site_id' => $this->warehouse->id, 'quantity' => 10]);

    $this->service->record(['type' => 'transfer', 'product_id' => $this->product->id, 'source_site_id' => $this->warehouse->id, 'destination_site_id' => $this->warehouse->id, 'quantity' => 5]);
})->throws(ValidationException::class);

it('refuse l’entrée en stock d’un lot rappelé', function () {
    $batch = stockBatch($this->product, status: 'recalled');

    $this->service->record(['type' => 'in', 'batch_id' => $batch->id, 'destination_site_id' => $this->warehouse->id, 'quantity' => 10]);
})->throws(ValidationException::class);

it('déclenche l’alerte une seule fois quand le stock passe sous le seuil', function () {
    Event::fake([StockLevelLow::class]);

    $this->service->openLine(['site_id' => $this->warehouse->id, 'product_id' => $this->product->id, 'quantity' => 100, 'min_threshold' => 20]);

    $this->service->record(['type' => 'out', 'product_id' => $this->product->id, 'source_site_id' => $this->warehouse->id, 'quantity' => 70]); // 30 > 20 : pas d'alerte
    Event::assertNotDispatched(StockLevelLow::class);

    $this->service->record(['type' => 'out', 'product_id' => $this->product->id, 'source_site_id' => $this->warehouse->id, 'quantity' => 15]); // 15 ≤ 20 : alerte
    $this->service->record(['type' => 'out', 'product_id' => $this->product->id, 'source_site_id' => $this->warehouse->id, 'quantity' => 5]);  // déjà sous le seuil : pas de 2e alerte

    Event::assertDispatchedTimes(StockLevelLow::class, 1);
    expect($this->service->lowStock())->toHaveCount(1);
});

it('envoie l’alerte de rupture seulement aux gestionnaires de stock', function () {
    Notification::fake();

    $manager = stockUser('distributeur');
    $admin = stockUser('admin');
    $consumer = stockUser('consommateur');
    $suspended = stockUser('producteur');
    $suspended->update(['account_status' => 'suspended']);

    $this->service->openLine(['site_id' => $this->warehouse->id, 'product_id' => $this->product->id, 'quantity' => 50, 'min_threshold' => 20]);
    $this->service->record(['type' => 'out', 'product_id' => $this->product->id, 'source_site_id' => $this->warehouse->id, 'quantity' => 40]);

    Notification::assertSentTo([$manager, $admin], LowStockNotification::class);
    Notification::assertNotSentTo([$consumer, $suspended], LowStockNotification::class);
});

it('enregistre un mouvement via le formulaire (Form Request + contrôleur)', function () {
    $this->actingAs(stockUser('distributeur'))
        ->post(route('stock-movements.store'), [
            'type' => 'in', 'product_id' => $this->product->id,
            'destination_site_id' => $this->warehouse->id, 'quantity' => 25,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Stock::where('site_id', $this->warehouse->id)->value('quantity'))->toEqual(25.0);
});

it('valide le formulaire de mouvement (quantité obligatoire et positive)', function () {
    $this->actingAs(stockUser('distributeur'))
        ->post(route('stock-movements.store'), [
            'type' => 'out', 'product_id' => $this->product->id,
            'source_site_id' => $this->warehouse->id, 'quantity' => -5,
        ])
        ->assertSessionHasErrors('quantity');

    expect(StockMovement::count())->toBe(0);
});
