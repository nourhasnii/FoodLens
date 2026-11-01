<?php

use App\Models\Batch;
use App\Models\Category;
use App\Models\Certification;
use App\Models\OcrCheck;
use App\Models\Product;
use App\Models\QualityCheck;
use App\Models\RiskAssessment;
use App\Models\TransportCondition;
use App\Models\User;
use App\Services\NutriMind\NutriMindService;
use Illuminate\Support\Facades\DB;

/**
 * NutriMind (Phase 2) — moteur de risque explicable.
 * Vérifie les bornes de score, l'absence de double comptage, la distinction
 * donnée manquante / risque nul, la priorité des incidents critiques,
 * l'historique persité et l'autorisation d'accès au moteur.
 */

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/** Produit vérifié avec des données « propres » (aucun risque confirmé). */
function nutrimindCleanProduct(): Product
{
    $product = Product::factory()->create(['verification_status' => 'verified']);

    Certification::factory()->for($product)->create([
        'status' => Certification::STATUS_VALID,
        'expires_at' => today()->addYear(),
    ]);

    QualityCheck::create([
        'product_id' => $product->id,
        'batch_id' => null,
        'status' => 'completed',
        'quality_score' => 88,
        'decision' => 'sell',
    ]);

    OcrCheck::create([
        'product_id' => $product->id,
        'image_path' => 'labels/clean.jpg',
        'status' => 'completed',
        'inconsistency_score' => 5,
        'mismatches' => [],
    ]);

    return $product;
}

/** Récupère une constatation par règle dans un résultat d'évaluation. */
function nutrimindFinding(array $result, string $rule): ?array
{
    return collect($result['findings'])->firstWhere('rule', $rule);
}

// ---------------------------------------------------------------------------
// 1. Bornes de sévérité
// ---------------------------------------------------------------------------

it('calcule la sévérité selon les seuils, avec dérogation critique', function () {
    expect(NutriMindService::severityFor(0))->toBe('info');
    expect(NutriMindService::severityFor(39.99))->toBe('info');
    expect(NutriMindService::severityFor(40))->toBe('warning');
    expect(NutriMindService::severityFor(69.99))->toBe('warning');
    expect(NutriMindService::severityFor(70))->toBe('critical');
    expect(NutriMindService::severityFor(100))->toBe('critical');

    // Un incident critique confirmé force la sévérité, même à score nul.
    expect(NutriMindService::severityFor(0, true))->toBe('critical');
});

// ---------------------------------------------------------------------------
// 2. Évaluation sans risque et complétude des données
// ---------------------------------------------------------------------------

it('renvoie un score nul et une complétude maximale pour un produit sans risque', function () {
    $result = app(NutriMindService::class)->evaluate(nutrimindCleanProduct());

    expect($result['score'])->toBe(0.0);
    expect($result['severity'])->toBe('info');
    expect($result['risk_findings'])->toBeEmpty();
    expect($result['missing_findings'])->toBeEmpty();
    expect((float) $result['data_completeness'])->toBe(100.0);
});

it('distingue l’absence de donnée d’un risque nul', function () {
    // Produit sans certification, sans contrôle qualité, sans OCR et non vérifié.
    $product = Product::factory()->create(['verification_status' => 'pending']);

    $result = app(NutriMindService::class)->evaluate($product);

    // Aucun risque confirmé…
    expect($result['score'])->toBe(0.0);
    expect($result['risk_findings'])->toBeEmpty();

    // …mais des informations manquantes, signalées hors score.
    $rules = collect($result['missing_findings'])->pluck('rule');
    expect($rules)->toContain('missing_certification');
    expect($rules)->toContain('missing_quality_check');
    expect($rules)->toContain('missing_ocr_check');
    expect($rules)->toContain('product_unverified');

    expect((float) $result['data_completeness'])->toBe(0.0);

    // Les constatations « missing » ne contribuent pas au score.
    expect(collect($result['missing_findings'])->sum('contribution'))->toBe(0.0);
});

// ---------------------------------------------------------------------------
// 3. Certification expirée : source réelle, contribution et pas de double comptage
// ---------------------------------------------------------------------------

it('détecte une certification expirée avec sa source et sa contribution', function () {
    $product = Product::factory()->create(['verification_status' => 'verified']);

    $certification = Certification::factory()->for($product)->create([
        'status' => Certification::STATUS_VALID,
        'expires_at' => today()->subDays(4),
    ]);

    $result = app(NutriMindService::class)->evaluate($product);
    $finding = nutrimindFinding($result, 'certification_expired');

    expect($finding)->not->toBeNull();
    expect($finding['severity'])->toBe('critical');
    expect($finding['contribution'])->toBe(40.0);
    expect($finding['source_type'])->toBe(Certification::class);
    expect($finding['source_id'])->toBe($certification->id);

    expect($result['score'])->toBe(40.0);
    expect($result['severity'])->toBe('warning'); // 40 atteint le seuil d'avertissement
});

it('ne compte pas deux fois plusieurs certifications expirées du même produit', function () {
    $product = Product::factory()->create(['verification_status' => 'verified']);

    Certification::factory()->for($product)->create([
        'status' => Certification::STATUS_VALID, 'expires_at' => today()->subDays(3),
    ]);
    Certification::factory()->for($product)->create([
        'status' => Certification::STATUS_VALID, 'expires_at' => today()->subDays(20),
    ]);

    $result = app(NutriMindService::class)->evaluate($product);

    $certFindings = collect($result['risk_findings'])->where('rule', 'certification_expired');
    expect($certFindings)->toHaveCount(1);
    expect($result['score'])->toBe(40.0); // une seule contribution de 40, pas 80
});

it('ne cumule pas « certification expirée » et « certification manquante »', function () {
    $product = Product::factory()->create(['verification_status' => 'verified']);

    Certification::factory()->for($product)->create([
        'status' => Certification::STATUS_VALID, 'expires_at' => today()->subDays(2),
    ]);

    $result = app(NutriMindService::class)->evaluate($product);

    expect(nutrimindFinding($result, 'certification_expired'))->not->toBeNull();
    expect(nutrimindFinding($result, 'missing_certification'))->toBeNull();
});

it('module la contribution d’une certification proche de l’expiration', function () {
    $product = Product::factory()->create(['verification_status' => 'verified']);

    Certification::factory()->for($product)->create([
        'status' => Certification::STATUS_VALID,
        'expires_at' => today()->addDays(15),
    ]);

    $finding = nutrimindFinding(app(NutriMindService::class)->evaluate($product), 'certification_expiring');

    expect($finding)->not->toBeNull();
    expect($finding['severity'])->toBe('warning');
    // 20 × (30 − 15) / 30 = 10
    expect($finding['contribution'])->toBe(10.0);
});

// ---------------------------------------------------------------------------
// 4. Incidents critiques : priorité indépendante du score numérique
// ---------------------------------------------------------------------------

it('priorise un lot rappelé au-delà du score numérique', function () {
    $product = nutrimindCleanProduct();

    $batch = Batch::factory()->for($product)->create([
        'status' => 'recalled',
        'expiration_date' => today()->addMonth(),
    ]);

    $result = app(NutriMindService::class)->evaluate($batch);
    $finding = nutrimindFinding($result, 'recall');

    expect($finding)->not->toBeNull();
    expect($finding['critical_override'])->toBeTrue();
    expect($result['critical_override'])->toBeTrue();
    expect($result['severity'])->toBe('critical');
    // Plancher de score imposé par l'incident confirmé (60 brut → 70 plancher).
    expect($result['score'])->toBe(70.0);
});

// ---------------------------------------------------------------------------
// 5. OCR et qualité : contribution proportionnelle à la mesure réelle
// ---------------------------------------------------------------------------

it('module le risque OCR selon le score d’incohérence', function () {
    $product = nutrimindCleanProduct();

    OcrCheck::create([
        'product_id' => $product->id,
        'batch_id' => null,
        'image_path' => 'labels/bad.jpg',
        'status' => 'completed',
        'inconsistency_score' => 82,
        'mismatches' => [['field' => 'lot_number', 'severity' => 'critical']],
    ]);

    $result = app(NutriMindService::class)->evaluate($product);
    $finding = nutrimindFinding($result, 'ocr_inconsistency');

    expect($finding)->not->toBeNull();
    expect($finding['severity'])->toBe('critical');
    // 35 × 0,82 = 28,7
    expect($finding['contribution'])->toBe(28.7);
    expect($result['score'])->toBe(28.7);
});

it('module le risque qualité selon le score mesuré', function () {
    $product = nutrimindCleanProduct();

    QualityCheck::create([
        'product_id' => $product->id,
        'batch_id' => null,
        'status' => 'completed',
        'quality_score' => 25,
        'decision' => 'withdraw',
    ]);

    $result = app(NutriMindService::class)->evaluate($product);
    $finding = nutrimindFinding($result, 'quality_failed');

    expect($finding)->not->toBeNull();
    expect($finding['severity'])->toBe('critical');
    // 45 × (50 − 25) / 50 = 22,5
    expect($finding['contribution'])->toBe(22.5);
    expect($result['score'])->toBe(22.5);
});

// ---------------------------------------------------------------------------
// 6. Chaîne du froid
// ---------------------------------------------------------------------------

it('détecte une rupture de chaîne du froid proportionnelle aux relevés', function () {
    $product = nutrimindCleanProduct();
    $batch = Batch::factory()->for($product)->create([
        'status' => 'active',
        'expiration_date' => today()->addMonth(),
    ]);

    TransportCondition::factory()->for($batch)->create(['temperature' => 3.0]);
    TransportCondition::factory()->for($batch)->create(['temperature' => 9.5]);

    $result = app(NutriMindService::class)->evaluate($batch);
    $finding = nutrimindFinding($result, 'cold_chain');

    expect($finding)->not->toBeNull();
    // 1 relevé hors plage sur 2 → 30 × 0,5 = 15
    expect($finding['contribution'])->toBe(15.0);
});

// ---------------------------------------------------------------------------
// 7. Fournisseur : agrégation bornée (pas de N+1)
// ---------------------------------------------------------------------------

it('agrège les risques des produits d’un fournisseur sans N+1', function () {
    $supplier = User::factory()->create(['organization_name' => 'Ferme du Sud']);

    $clean = Product::factory()->create(['producer_id' => $supplier->id, 'verification_status' => 'verified']);
    Certification::factory()->for($clean)->valid()->create();

    $risky = Product::factory()->create(['producer_id' => $supplier->id, 'verification_status' => 'verified']);
    Certification::factory()->for($risky)->create([
        'status' => Certification::STATUS_VALID, 'expires_at' => today()->subDays(6),
    ]);

    // Le coût en requêtes est borné (aucune requête par produit pendant l'analyse).
    DB::flushQueryLog();
    DB::enableQueryLog();

    $result = app(NutriMindService::class)->evaluate($supplier);

    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($result['entity_label'])->toBe('Ferme du Sud');
    expect(nutrimindFinding($result, 'certification_expired'))->not->toBeNull();
    expect($queryCount)->toBeLessThanOrEqual(10);
});

// ---------------------------------------------------------------------------
// 8. Historique persisté et traçabilité
// ---------------------------------------------------------------------------

it('conserve l’historique des évaluations sans écraser les précédentes', function () {
    $actor = User::factory()->create();
    $product = nutrimindCleanProduct();

    $service = app(NutriMindService::class);

    $first = $service->assess($product, $actor);
    expect($first)->toBeInstanceOf(RiskAssessment::class);
    expect($first->is_simulation)->toBeFalse();
    expect($first->rule_set_version)->toBe(config('nutrimind.rule_set_version'));
    expect($first->created_by)->toBe($actor->id);
    expect($first->assessable_type)->toBe(Product::class);
    expect($first->assessable_id)->toBe($product->id);
    expect((float) $first->score)->toBe(0.0);

    // Nouvelle évaluation après l'apparition d'un risque : l'ancienne est conservée.
    Certification::factory()->for($product)->create([
        'status' => Certification::STATUS_VALID, 'expires_at' => today()->subDays(3),
    ]);

    $second = $service->assess($product, $actor);

    expect((float) $second->score)->toBe(40.0);
    expect(RiskAssessment::where('assessable_type', Product::class)
        ->where('assessable_id', $product->id)->count())->toBe(2);
    expect((float) RiskAssessment::find($first->id)->score)->toBe(0.0);
});

it('expose les constatations par nature et les références de preuve', function () {
    $product = nutrimindCleanProduct();

    Certification::factory()->for($product)->create([
        'status' => Certification::STATUS_VALID, 'expires_at' => today()->subDays(1),
    ]);

    $assessment = app(NutriMindService::class)->assess($product);

    expect($assessment->riskFindings())->toHaveCount(1);
    expect($assessment->riskFindings()[0]['rule'])->toBe('certification_expired');
    expect($assessment->missingFindings())->toBeArray();
    expect($assessment->evidence[0])->toHaveKeys(['rule', 'source_type', 'source_id', 'contribution']);
});

// ---------------------------------------------------------------------------
// 9. Robustesse
// ---------------------------------------------------------------------------

it('refuse les entités non prises en charge', function () {
    $category = Category::factory()->create();

    expect(fn () => app(NutriMindService::class)->evaluate($category))
        ->toThrow(InvalidArgumentException::class);
});
