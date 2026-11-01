<?php

use App\Models\Batch;
use App\Models\Category;
use App\Models\Certification;
use App\Models\Product;
use App\Models\RiskAssessment;
use App\Models\User;
use App\Services\NutriMind\ImpactAnalysisService;
use App\Services\NutriMind\ScenarioSimulator;

/**
 * NutriMind (Phase 3) — graphe d'impact et simulation « what-if ».
 *
 * Vérifie l'exploration bornée des relations réelles, la protection contre les
 * cycles, la classification confirmé / potentiel / incertain, la comparaison
 * scénario de référence / scénario simulé et l'absence totale d'écriture.
 */

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/** Un fournisseur avec un produit vérifié et un lot actif. */
function nmImpactWorld(): array
{
    $supplier = User::factory()->create(['organization_name' => 'Ferme Impact']);
    $product = Product::factory()->create([
        'producer_id' => $supplier->id,
        'verification_status' => 'verified',
        'name' => 'Produit Impact',
    ]);
    $batch = Batch::factory()->for($product)->create([
        'status' => 'active',
        'expiration_date' => today()->addMonth(),
        'lot_number' => 'LOT-IMPACT-001',
    ]);

    return [$supplier, $product, $batch];
}

// ---------------------------------------------------------------------------
// 1. Exploration : dépendances directes et indirectes
// ---------------------------------------------------------------------------

it('explore les dépendances directes et indirectes depuis un lot', function () {
    [$supplier, $product, $batch] = nmImpactWorld();

    $graph = app(ImpactAnalysisService::class)->analyze($batch, 3);

    $keys = collect($graph['nodes'])->pluck('key');
    expect($keys)->toContain(Batch::class.':'.$batch->id);
    expect($keys)->toContain(Product::class.':'.$product->id);
    expect($keys)->toContain(User::class.':'.$supplier->id);

    // Le produit est en dépendance directe ; le fournisseur, atteint via le produit, est indirect.
    $toProduct = collect($graph['edges'])->firstWhere('to', Product::class.':'.$product->id);
    expect($toProduct['dependency'])->toBe('direct');

    $toSupplier = collect($graph['edges'])->firstWhere('to', User::class.':'.$supplier->id);
    expect($toSupplier['dependency'])->toBe('indirect');
    expect($toSupplier['relation'])->toBe('producer');
});

it('porte le chemin de relation jusqu’à chaque entité affectée', function () {
    [$supplier, $product, $batch] = nmImpactWorld();

    $graph = app(ImpactAnalysisService::class)->analyze($batch, 3);

    $node = collect($graph['nodes'])->firstWhere('key', User::class.':'.$supplier->id);
    expect($node)->not->toBeNull();
    expect($node['path'])->toHaveCount(2);
    expect($node['path'][0]['relation'])->toBe('product');
    expect($node['path'][1]['relation'])->toBe('producer');
});

// ---------------------------------------------------------------------------
// 2. Protection contre les cycles
// ---------------------------------------------------------------------------

it('protège contre les cycles sans revisiter un nœud', function () {
    [$supplier, $product, $batch] = nmImpactWorld();

    $graph = app(ImpactAnalysisService::class)->analyze($batch, 3);

    // Chaque nœud n'apparaît qu'une seule fois.
    $keys = collect($graph['nodes'])->pluck('key');
    expect($keys->count())->toBe($keys->unique()->count());

    // Le retour produit → lot est enregistré comme un cycle et n'est pas réexploré.
    $cycle = collect($graph['edges'])->first(
        fn (array $edge) => $edge['to'] === Batch::class.':'.$batch->id && $edge['cycle'] === true
    );
    expect($cycle)->not->toBeNull();
    expect($cycle['from'])->toBe(Product::class.':'.$product->id);
});

// ---------------------------------------------------------------------------
// 3. Bornes d'exploration
// ---------------------------------------------------------------------------

it('respecte la profondeur maximale demandée', function () {
    [$supplier, $product, $batch] = nmImpactWorld();

    $graph = app(ImpactAnalysisService::class)->analyze($batch, 1);

    expect(collect($graph['nodes'])->max('depth'))->toBeLessThanOrEqual(1);
    // Le fournisseur (profondeur 2) reste hors périmètre.
    expect(collect($graph['nodes'])->pluck('key'))->not->toContain(User::class.':'.$supplier->id);
});

it('borne le nombre de nœuds et signale la troncature', function () {
    [$supplier, $product, $batch] = nmImpactWorld();

    Batch::factory()->count(12)->for($product)->create([
        'status' => 'active',
        'expiration_date' => today()->addMonth(),
    ]);

    $graph = app(ImpactAnalysisService::class)->analyze($product, 3, 5);

    expect(count($graph['nodes']))->toBeLessThanOrEqual(5);
    expect($graph['bounds']['truncated'])->toBeTrue();
    expect($graph['bounds']['max_nodes'])->toBe(5);
});

// ---------------------------------------------------------------------------
// 4. Classification : confirmé / potentiel / incertain
// ---------------------------------------------------------------------------

it('classe l’impact en confirmé, potentiel et incertain', function () {
    $supplier = User::factory()->create(['organization_name' => 'Ferme Class']);
    $barren = Product::factory()->create([
        'producer_id' => $supplier->id,
        'verification_status' => 'pending',
    ]);

    // Fournisseur d'origine : un produit sans aucune donnée → impact incertain.
    $graph = app(ImpactAnalysisService::class)->analyze($supplier);

    $barrenNode = collect($graph['nodes'])->firstWhere('key', Product::class.':'.$barren->id);
    expect($barrenNode['impact'])->toBe('unknown');
    expect($graph['summary']['unknown'])->toBeGreaterThanOrEqual(1);
});

it('marque « confirmé » un incident de sécurité avec sa preuve', function () {
    [$supplier, $product] = nmImpactWorld();

    $batch = Batch::factory()->for($product)->create([
        'status' => 'recalled',
        'expiration_date' => today()->addMonth(),
    ]);
    $certification = Certification::factory()->for($product)->create([
        'status' => Certification::STATUS_VALID,
        'expires_at' => today()->subDay(),
    ]);

    $graph = app(ImpactAnalysisService::class)->analyze($batch, 3);
    $byKey = collect($graph['nodes'])->keyBy('key');

    // Le lot rappelé porte un signal confirmé.
    $batchNode = $byKey[Batch::class.':'.$batch->id];
    expect($batchNode['impact'])->toBe('confirmed');
    expect($batchNode['signal']['label'])->toBe('Lot rappelé');
    expect($batchNode['signal']['severity'])->toBe('critical');

    // La certification expirée du produit est confirmée également.
    $certNode = $byKey[Certification::class.':'.$certification->id];
    expect($certNode['impact'])->toBe('confirmed');
    expect($certNode['signal']['label'])->toBe('Certification expirée');

    // Le produit lui-même, sans incident propre, reste « potentiel » (données présentes).
    expect($byKey[Product::class.':'.$product->id]['impact'])->toBe('potential');

    expect($graph['summary']['confirmed'])->toBeGreaterThanOrEqual(2);
});

// ---------------------------------------------------------------------------
// 5. Robustesse
// ---------------------------------------------------------------------------

it('refuse les entités dont le graphe n’est pas explorable', function () {
    $category = Category::factory()->create();

    expect(fn () => app(ImpactAnalysisService::class)->analyze($category))
        ->toThrow(InvalidArgumentException::class);
});

// ---------------------------------------------------------------------------
// 6. Simulation « what-if »
// ---------------------------------------------------------------------------

/** Un produit vérifié sans aucun risque confirmé (données présentes et favorables). */
function nmSimCleanProduct(): Product
{
    $product = Product::factory()->create(['verification_status' => 'verified']);

    Certification::factory()->for($product)->create([
        'status' => Certification::STATUS_VALID,
        'expires_at' => today()->addYear(),
    ]);

    \App\Models\QualityCheck::create([
        'product_id' => $product->id,
        'batch_id' => null,
        'status' => 'completed',
        'quality_score' => 88,
        'decision' => 'sell',
    ]);

    \App\Models\OcrCheck::create([
        'product_id' => $product->id,
        'image_path' => 'labels/sim.jpg',
        'status' => 'completed',
        'inconsistency_score' => 5,
        'mismatches' => [],
    ]);

    return $product;
}

it('compare l’état réel et l’état simulé d’un scénario', function () {
    $product = nmSimCleanProduct();

    $simulation = app(ScenarioSimulator::class)->simulate($product, ['certification_expired']);

    expect($simulation['is_simulation'])->toBeTrue();
    expect($simulation['scenarios'])->toHaveCount(1);
    expect($simulation['scenarios'][0]['key'])->toBe('certification_expired');

    // État réel : aucun risque.
    expect($simulation['baseline']['score'])->toBe(0.0);
    expect($simulation['baseline']['severity'])->toBe('info');

    // État simulé : certification expirée → 40 points, passage en avertissement.
    expect($simulation['simulated']['score'])->toBe(40.0);
    expect($simulation['simulated']['severity'])->toBe('warning');
    expect($simulation['delta']['score'])->toBe(40.0);
    expect($simulation['delta']['severity_changed'])->toBeTrue();
    expect($simulation['delta']['to'])->toBe('warning');

    // La constatation nouvelle est explicitement hypothétique.
    $new = collect($simulation['findings']['new'])->firstWhere('rule', 'certification_expired');
    expect($new)->not->toBeNull();
    expect($new['hypothetical'])->toBeTrue();
});

it('n’ajoute pas un risque déjà présent dans l’évaluation réelle', function () {
    $product = Product::factory()->create(['verification_status' => 'verified']);
    Certification::factory()->for($product)->create([
        'status' => Certification::STATUS_VALID,
        'expires_at' => today()->subDays(5),
    ]);

    $simulation = app(ScenarioSimulator::class)->simulate($product, ['certification_expired']);

    // Le scénario recouvre la règle déjà déclenchée : aucun double comptage.
    expect($simulation['baseline']['score'])->toBe(40.0);
    expect($simulation['simulated']['score'])->toBe(40.0);
    expect($simulation['delta']['score'])->toBe(0.0);
    expect($simulation['findings']['new'])->toBeEmpty();
    expect(collect($simulation['findings']['remaining'])->pluck('rule'))->toContain('certification_expired');
});

it('remplace l’information manquante par la donnée simulée', function () {
    // Produit vérifié mais sans certification enregistrée.
    $product = Product::factory()->create(['verification_status' => 'verified']);

    $baseline = app(\App\Services\NutriMind\NutriMindService::class)->evaluate($product);
    expect(collect($baseline['missing_findings'])->pluck('rule'))->toContain('missing_certification');

    $simulation = app(ScenarioSimulator::class)->simulate($product, ['certification_expired']);

    // L'information manquante est levée (couverte par l'hypothèse), hors du score.
    expect(collect($simulation['findings']['resolved'])->pluck('rule'))->toContain('missing_certification');
    // Le score ne compte que le risque simulé.
    expect($simulation['simulated']['score'])->toBe(40.0);
});

it('applique la dérogation critique lors d’une simulation de rappel', function () {
    $product = nmSimCleanProduct();
    $batch = Batch::factory()->for($product)->create([
        'status' => 'active',
        'expiration_date' => today()->addMonth(),
    ]);

    $simulation = app(ScenarioSimulator::class)->simulate($batch, ['recall']);

    expect($simulation['simulated']['critical_override'])->toBeTrue();
    expect($simulation['simulated']['severity'])->toBe('critical');
    expect($simulation['simulated']['score'])->toBe(70.0);
});

it('ne modifie jamais les données réelles pendant une simulation', function () {
    $product = nmSimCleanProduct();

    $before = [
        'products' => Product::count(),
        'batches' => Batch::count(),
        'certifications' => Certification::count(),
        'assessments' => RiskAssessment::count(),
    ];

    app(ScenarioSimulator::class)->simulate($product, ['certification_expired', 'quality_failed', 'recall']);

    expect(Product::count())->toBe($before['products']);
    expect(Batch::count())->toBe($before['batches']);
    expect(Certification::count())->toBe($before['certifications']);
    // Aucune évaluation simulée n'est persistée dans l'historique.
    expect(RiskAssessment::count())->toBe($before['assessments']);
});

it('refuse un scénario inconnu', function () {
    $product = nmSimCleanProduct();

    expect(fn () => app(ScenarioSimulator::class)->simulate($product, ['scenario_inexistant']))
        ->toThrow(InvalidArgumentException::class);
});
