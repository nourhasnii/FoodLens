<?php

/*
| Module Stocks — DLC dynamique (microbiologie prévisionnelle, modèle de Ratkowsky).
*/

use App\Models\Batch;
use App\Models\ShelfLifeAssessment;
use App\Models\Site;
use App\Models\Stock;
use App\Models\TransportCondition;
use App\Services\Optimization\OptimizationEngine;
use App\Services\ShelfLife\ShelfLifePredictor;
use App\Services\Stock\StockService;
use App\Support\BatchAttributes;
use Carbon\CarbonImmutable;
use Database\Seeders\ShelfLifeDemoSeeder;

require_once __DIR__.'/StocksTestHelpers.php';

beforeEach(function () {
    $this->withoutVite();
    $this->predictor = app(ShelfLifePredictor::class);
    $this->stocks = app(StockService::class);
    $this->yaourt = stockProduct('Yaourt nature 125 g');
    $this->batch = stockBatch($this->yaourt, days: 12);
    $this->printed = CarbonImmutable::today()->addDays(12);
});

/** Ajoute un relevé de température au lot (table du module Chaîne du froid). */
function reading(Batch $batch, float $temp, int $minutes, string $location = 'Camion', int $hoursAgo = 30): TransportCondition
{
    return TransportCondition::query()->create([
        'batch_id' => $batch->id, 'recorded_at' => now()->subHours($hoursAgo), 'temperature' => $temp,
        'duration_minutes' => $minutes, 'location' => $location,
    ]);
}

it('applique le modèle de Ratkowsky aux produits laitiers', function () {
    // Tréf 4 °C, Tmin −2 °C : à 10 °C, ((10 + 2) / (4 + 2))² = 4 fois plus vite
    expect($this->predictor->relativeRate(10, 4, -2))->toEqual(4.0)
        ->and($this->predictor->relativeRate(4, 4, -2))->toEqual(1.0)
        ->and($this->predictor->relativeRate(1, 4, -2))->toEqual(0.25)
        ->and($this->predictor->relativeRate(-5, 4, -2))->toEqual(0.0)   // sous Tmin : pas de croissance
        ->and($this->predictor->relativeRate(80, 4, -2))->toEqual(40.0); // plafonné
});

it('arrondit la DLC avec prudence : 6 h perdues ou plus retirent un jour', function () {
    $d = CarbonImmutable::parse('2026-11-13');

    expect($this->predictor->shift($d, 5.9)->toDateString())->toBe('2026-11-13')
        ->and($this->predictor->shift($d, 6)->toDateString())->toBe('2026-11-12')
        ->and($this->predictor->shift($d, 29.9)->toDateString())->toBe('2026-11-12')
        ->and($this->predictor->shift($d, 30.1)->toDateString())->toBe('2026-11-11')
        ->and($this->predictor->shift($d, 61.1)->toDateString())->toBe('2026-11-10');
});

it('garde la DLC imprimée quand il n’y a aucun relevé', function () {
    $p = $this->predictor->predict($this->batch);

    expect($p['risk'])->toBe(ShelfLifePredictor::RISK_UNMONITORED)
        ->and($p['predicted'])->toBe($this->printed->toDateString())
        ->and($p['lost_hours'])->toEqual(0.0)
        ->and(BatchAttributes::effectiveExpiryDate($this->batch)->toDateString())->toBe($this->printed->toDateString());
});

it('ne change rien quand la chaîne du froid est respectée (pas de fausse alerte)', function () {
    reading($this->batch, 3.5, 600);
    reading($this->batch, 3.9, 300, hoursAgo: 20);

    $p = $this->predictor->predict($this->batch);

    expect($p['risk'])->toBe(ShelfLifePredictor::RISK_OK)
        ->and($p['predicted'])->toBe($p['printed'])
        ->and($p['explanation'])->toContain('chaîne du froid a été respectée');
});

it('avance la DLC après une panne de froid et donne une fourchette', function () {
    reading($this->batch, 3.6, 240, 'Usine Béja', 40);
    reading($this->batch, 9.8, 600, 'Panne du groupe froid près d’Enfidha', 36);
    reading($this->batch, 11.2, 300, 'Attente du dépanneur', 26);
    reading($this->batch, 14.5, 120, 'Quai de déchargement', 21);

    $p = $this->predictor->predict($this->batch);

    // 10 h × (3,87 − 1) + 5 h × (4,84 − 1) + 2 h × (7,56 − 1) ≈ 61 h perdues → 3 jours
    expect($p['lost_hours'])->toBeGreaterThan(60)->toBeLessThan(62)
        ->and($p['predicted'])->toBe($this->printed->subDays(3)->toDateString())
        ->and($p['earliest'] <= $p['predicted'])->toBeTrue()
        ->and($p['latest'] >= $p['predicted'])->toBeTrue()
        ->and($p['earliest'] < $p['latest'])->toBeTrue()
        ->and($p['risk'])->toBe(ShelfLifePredictor::RISK_CRITICAL)  // 17 h au-dessus de 8 °C
        ->and($p['critical_minutes'])->toBe(1020)
        ->and($p['profile'])->toBe('laitier')
        ->and($p['explanation'])->toContain('Enfidha')
        ->and($p['action'])->toContain('Contrôle qualité');
});

it('n’abîme pas un produit stable (huile) pour quelques degrés', function () {
    $huile = stockProduct('Huile d’olive extra vierge');
    $huile->update(['category' => 'Épicerie']);
    $batch = stockBatch($huile, days: 300);
    reading($batch, 12, 600);

    $p = $this->predictor->predict($batch);

    expect($p['profile'])->toBe('stable')
        ->and($p['lost_hours'])->toEqual(0.0)
        ->and($p['predicted'])->toBe($p['printed']);
});

it('utilise la DLC réelle dans l’état des stocks et dans l’optimisation', function () {
    $site = Site::factory()->warehouse()->create(['latitude' => 34.74, 'longitude' => 10.76, 'capacity' => 100000]);
    Site::factory()->retailStore()->create(['latitude' => 35.82, 'longitude' => 10.63, 'capacity' => 100000]);
    $this->stocks->record(['type' => 'in', 'batch_id' => $this->batch->id, 'destination_site_id' => $site->id, 'quantity' => 100]);
    $line = Stock::where('batch_id', $this->batch->id)->first();
    expect($line->state())->toBe('ok'); // DLC imprimée dans 12 jours

    // 12 h à 20 °C (×13,4) : environ 149 h perdues → 6 jours de moins, la DLC réelle arrive dans moins de 7 jours
    reading($this->batch, 20, 720);

    expect(BatchAttributes::daysToEffectiveExpiry($this->batch))->toBeLessThan(7)
        ->and(BatchAttributes::daysToExpiry($this->batch))->toBe(12)
        ->and($line->fresh()->state())->not->toBe('ok');

    $rec = app(OptimizationEngine::class)->recommend($this->batch->fresh(), $site, 50);
    expect($rec->days_to_expiry)->toBe(BatchAttributes::daysToEffectiveExpiry($this->batch))
        ->and(json_encode($rec->explanation, JSON_UNESCAPED_UNICODE))->toContain('DLC réelle prédite');
});

it('revient à la DLC imprimée quand un responsable rejette la prédiction', function () {
    reading($this->batch, 12, 900);
    expect(BatchAttributes::effectiveExpiryDate($this->batch)->lt($this->printed))->toBeTrue();

    ShelfLifeAssessment::factory()->rejected()->create(['batch_id' => $this->batch->id]);

    expect(BatchAttributes::effectiveExpiryDate($this->batch)->toDateString())->toBe($this->printed->toDateString());
});

it('affiche les lots et la fiche d’un lot avec sa courbe de température', function () {
    $site = Site::factory()->warehouse()->create();
    $this->stocks->record(['type' => 'in', 'batch_id' => $this->batch->id, 'destination_site_id' => $site->id, 'quantity' => 80]);
    reading($this->batch, 10.5, 480, 'Panne du camion');
    $user = stockUser('distributeur');

    $this->actingAs($user)->get(route('shelf-life.index'))
        ->assertOk()->assertSee($this->batch->lot_number)->assertSee('DLC réelle prédite')->assertSee('Ratkowsky');

    $this->actingAs($user)->get(route('shelf-life.show', $this->batch))
        ->assertOk()->assertSee('Températures pendant le transport')->assertSee('Panne du camion')
        ->assertSee('Valider la DLC réelle');
});

it('enregistre la décision du responsable et exige une justification pour garder la DLC imprimée', function () {
    reading($this->batch, 10.5, 480);
    $user = stockUser('admin');

    $this->actingAs($user)->post(route('shelf-life.decide', $this->batch), ['decision' => 'rejected'])
        ->assertSessionHasErrors('note');
    expect(ShelfLifeAssessment::count())->toBe(0);

    $this->actingAs($user)->post(route('shelf-life.decide', $this->batch), ['decision' => 'accepted'])
        ->assertRedirect(route('shelf-life.show', $this->batch));

    $a = ShelfLifeAssessment::firstOrFail();
    expect($a->decision)->toBe('accepted')
        ->and($a->user_id)->toBe($user->id)
        ->and($a->printed_expiry->toDateString())->toBe($this->printed->toDateString())
        ->and($a->predicted_expiry->lt($a->printed_expiry))->toBeTrue()
        ->and($a->model['profile'])->toBe('laitier');
});

it('réserve la DLC dynamique aux gestionnaires de stock', function () {
    $this->actingAs(stockUser('consommateur'))->get(route('shelf-life.index'))->assertForbidden();
    $this->actingAs(stockUser('consommateur'))->post(route('shelf-life.decide', $this->batch), ['decision' => 'accepted'])->assertForbidden();
});

it('ajoute les relevés de démonstration une seule fois', function () {
    $this->batch->update(['lot_number' => 'LOT-2026-001']);
    stockBatch(stockProduct('Lait frais 1 L'), days: 2)->update(['lot_number' => 'LOT-2026-002']);

    $this->seed(ShelfLifeDemoSeeder::class);
    $this->seed(ShelfLifeDemoSeeder::class);

    expect(TransportCondition::count())->toBe(10)
        ->and($this->predictor->predict($this->batch->fresh())['risk'])->toBe(ShelfLifePredictor::RISK_CRITICAL);
});
