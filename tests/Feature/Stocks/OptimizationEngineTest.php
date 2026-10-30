<?php

/*
| Module Optimisation — moteur IA de choix de destination d'un lot.
*/

use App\Enums\RecommendationStatus;
use App\Events\BatchDestinationValidated;
use App\Models\OptimizationRecommendation;
use App\Models\Site;
use App\Services\Optimization\OptimizationEngine;
use App\Services\Stock\StockService;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

require_once __DIR__.'/StocksTestHelpers.php';

beforeEach(function () {
    $this->withoutVite();
    $this->engine = app(OptimizationEngine::class);
    $this->stocks = app(StockService::class);
    $this->product = stockProduct();
    $this->batch = stockBatch($this->product, days: 10);

    // Sfax (origine), Sousse (proche, grande capacité), Tunis (loin), Gabès (plein)
    $this->source = Site::factory()->production()->create(['name' => 'Usine Sfax', 'latitude' => 34.7406, 'longitude' => 10.7603, 'capacity' => 100000]);
    $this->sousse = Site::factory()->warehouse()->create(['name' => 'Entrepôt Sousse', 'latitude' => 35.8256, 'longitude' => 10.6360, 'capacity' => 50000]);
    $this->tunis = Site::factory()->warehouse()->create(['name' => 'Entrepôt Tunis', 'latitude' => 36.8065, 'longitude' => 10.1815, 'capacity' => 50000]);
    $this->full = Site::factory()->warehouse()->create(['name' => 'Dépôt Gabès plein', 'latitude' => 33.8815, 'longitude' => 10.0982, 'capacity' => 10]);
    $this->asso = Site::factory()->association()->create(['latitude' => 34.75, 'longitude' => 10.75]);

    $this->stocks->record(['type' => 'in', 'batch_id' => $this->batch->id, 'destination_site_id' => $this->source->id, 'quantity' => 500]);
});

it('recommande une destination faisable avec un score et une explication', function () {
    $rec = $this->engine->recommend($this->batch, $this->source, 200);

    expect($rec)->toBeInstanceOf(OptimizationRecommendation::class)
        ->and($rec->status)->toBe(RecommendationStatus::Pending)
        ->and($rec->recommended_site_id)->toBeIn([$this->sousse->id, $this->tunis->id])
        ->and((float) $rec->score)->toBeGreaterThan(0)->toBeLessThanOrEqual(100)
        ->and($rec->explanation)->not->toBeEmpty()
        ->and(OptimizationRecommendation::count())->toBe(1); // résultat stocké en base
});

it('exclut le site d’origine, les associations et les sites sans capacité', function () {
    $rec = $this->engine->recommend($this->batch, $this->source, 200);
    $candidates = collect($rec->candidates)->keyBy('site_id');

    expect($candidates->has($this->source->id))->toBeFalse()
        ->and($candidates->has($this->asso->id))->toBeFalse()
        ->and($candidates[$this->full->id]['feasible'])->toBeFalse()
        ->and($candidates[$this->full->id]['total'])->toEqual(0.0);
});

it('préfère le site le plus proche quand le reste est équivalent', function () {
    $rec = $this->engine->recommend($this->batch, $this->source, 200);

    expect($rec->recommended_site_id)->toBe($this->sousse->id);
});

it('refuse d’optimiser un lot rappelé', function () {
    $this->batch->update(['status' => 'recalled']);

    $this->engine->recommend($this->batch->fresh(), $this->source, 100);
})->throws(ValidationException::class);

it('refuse d’optimiser un lot dont la DLC est dépassée', function () {
    $this->batch->update(['expiration_date' => now()->subDay()->toDateString()]);

    $this->engine->recommend($this->batch->fresh(), $this->source, 100);
})->throws(ValidationException::class);

it('valide la recommandation et crée le transfert de stock automatiquement', function () {
    Event::fake([BatchDestinationValidated::class]);
    $user = stockUser('distributeur');
    $rec = $this->engine->recommend($this->batch, $this->source, 200, $user);

    $this->engine->applyDecision($rec, 'accept', null, 'OK', createMovement: true, user: $user);

    expect($rec->fresh()->status)->toBe(RecommendationStatus::Accepted)
        ->and($rec->fresh()->stock_movement_id)->not->toBeNull()
        ->and($this->stocks->availableAt($this->source->id, $this->product->id))->toEqual(300.0)
        ->and($this->stocks->availableAt($rec->recommended_site_id, $this->product->id))->toEqual(200.0);

    Event::assertDispatched(BatchDestinationValidated::class);
});

it('permet à l’utilisateur de choisir un autre site (modification)', function () {
    $rec = $this->engine->recommend($this->batch, $this->source, 100);
    $other = $rec->recommended_site_id === $this->sousse->id ? $this->tunis : $this->sousse;

    $this->engine->applyDecision($rec, 'modify', $other->id, 'Client prioritaire', createMovement: false);

    expect($rec->fresh()->status)->toBe(RecommendationStatus::Modified)
        ->and($rec->fresh()->chosen_site_id)->toBe($other->id);
});

it('ne traite pas deux fois la même recommandation', function () {
    $rec = $this->engine->recommend($this->batch, $this->source, 100);
    $this->engine->applyDecision($rec, 'reject', null, 'Non', createMovement: false);

    $this->engine->applyDecision($rec->fresh(), 'accept', null, null, createMovement: false);
})->throws(ValidationException::class);

it('lance une optimisation depuis la page (contrôleur + Form Request)', function () {
    $this->actingAs(stockUser('distributeur'))
        ->post(route('optimization.store'), [
            'batch_id' => $this->batch->id,
            'source_site_id' => $this->source->id,
            'quantity' => 150,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $rec = OptimizationRecommendation::firstOrFail();
    $this->actingAs(stockUser('distributeur'))->get(route('optimization.show', $rec))->assertOk();
});
