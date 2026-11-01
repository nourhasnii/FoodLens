<?php

use App\Models\Batch;
use App\Models\Certification;
use App\Models\OcrCheck;
use App\Models\Product;
use App\Models\QualityCheck;
use App\Models\RiskAssessment;
use App\Models\User;
use App\Services\NutriMind\NutriMindService;
use App\Services\NutriMind\RecommendationService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * NutriMind (Phase 4) — recommandations correctives sûres, comparaison
 * historique et console de décision.
 *
 * Vérifie :
 *  - la réutilisation exclusive des workflows existants (aucun bouton inventé) ;
 *  - le marquage « action sensible » des décisions réglementées ;
 *  - l'enregistrement des évaluations et des actions dans le journal d'audit ;
 *  - une conclusion d'amélioration fondée sur une preuve (constatation levée) ;
 *  - la protection serveur par la permission `manage_products`.
 */

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/** Utilisateur disposant des permissions de gestion (rôle dédié). */
function nmRecAdmin(): User
{
    $manageProducts = Permission::firstOrCreate(['name' => 'manage_products', 'guard_name' => 'web']);
    $manageAllProducts = Permission::firstOrCreate(['name' => 'manage_all_products', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'nutrimind-manager', 'guard_name' => 'web']);
    $role->givePermissionTo([$manageProducts, $manageAllProducts]);

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

/** Produit vérifié avec des données « propres » (aucun risque confirmé). */
function nmRecCleanProduct(): Product
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
        'quality_score' => 90,
        'decision' => 'sell',
    ]);

    OcrCheck::create([
        'product_id' => $product->id,
        'image_path' => 'labels/nm-rec-clean.jpg',
        'status' => 'completed',
        'inconsistency_score' => 5,
        'mismatches' => [],
    ]);

    return $product;
}

/** Récupère une recommandation par règle. */
function nmRecByRule(array $recommendations, string $rule): ?array
{
    return collect($recommendations)->firstWhere('rule', $rule);
}

// ---------------------------------------------------------------------------
// 1. Recommandations : réutilisation des workflows réels
// ---------------------------------------------------------------------------

it('traduit une certification expirée en recommandation liée à un workflow réel', function () {
    $product = nmRecCleanProduct();
    $expired = Certification::factory()->for($product)->create([
        'status' => Certification::STATUS_EXPIRED,
        'expires_at' => today()->subDays(3),
    ]);

    $recommendations = app(RecommendationService::class)->recommend($product);
    $recommendation = nmRecByRule($recommendations, 'certification_expired');

    expect($recommendation)->not->toBeNull();
    expect($recommendation['title'])->not->toBeEmpty();
    expect($recommendation['guidance'])->not->toBeEmpty();
    expect($recommendation['action'])->not->toBeNull();
    expect($recommendation['action']['url'])->toBe(route('admin.certifications.show', $expired));
    expect($recommendation['consequential'])->toBeFalse();
});

it('oriente une certification manquante vers la liste filtrée du produit', function () {
    $product = Product::factory()->create(['verification_status' => 'verified']);

    $recommendation = nmRecByRule(
        app(RecommendationService::class)->recommend($product),
        'missing_certification'
    );

    expect($recommendation)->not->toBeNull();
    expect($recommendation['action'])->not->toBeNull();
    expect($recommendation['action']['url'])
        ->toBe(route('admin.certifications.index', ['product_id' => $product->id]));
});

it('n’invente aucune action pour une règle sans workflow existant', function () {
    $service = app(RecommendationService::class);

    // Règle inconnue : aucune action.
    expect($service->actionFor(['rule' => 'unknown_rule']))->toBeNull();

    // Règle connue mais source non résoluble : aucune action inventée.
    expect($service->actionFor([
        'rule' => 'certification_expired',
        'source_type' => null,
        'source_id' => null,
    ]))->toBeNull();
});

// ---------------------------------------------------------------------------
// 2. Marquage des actions réglementées (confirmation requise)
// ---------------------------------------------------------------------------

it('ne marque comme sensibles que les actions à conséquence réglementée', function () {
    $product = nmRecCleanProduct();
    $batch = Batch::factory()->for($product)->create([
        'status' => 'recalled',
        'expiration_date' => today()->addMonth(),
        'lot_number' => 'LOT-NMREC-001',
    ]);

    $recommendations = app(RecommendationService::class)->recommend($product);

    $recall = nmRecByRule($recommendations, 'recall');
    expect($recall)->not->toBeNull();
    expect($recall['consequential'])->toBeTrue();
    expect($recall['action'])->not->toBeNull();
    expect($recall['action']['url'])->toBe(route('admin.batches.edit', $batch));

    // Une recommandation non réglementée reste non bloquante.
    $coldChain = nmRecByRule($recommendations, 'cold_chain');
    expect($coldChain)->toBeNull();

    expect(config('nutrimind.recommendations.quality_failed.consequential'))->toBeTrue();
    expect(config('nutrimind.recommendations.batch_expired.consequential'))->toBeTrue();
    expect(config('nutrimind.recommendations.certification_expired.consequential'))->toBeFalse();
    expect(config('nutrimind.recommendations.ocr_inconsistency.consequential'))->toBeFalse();
});

// ---------------------------------------------------------------------------
// 3. Historique et comparaison fondée sur une preuve
// ---------------------------------------------------------------------------

it('confirme une amélioration seulement lorsqu’une constatation disparaît', function () {
    $engine = app(NutriMindService::class);

    $product = nmRecCleanProduct();
    $expired = Certification::factory()->for($product)->create([
        'status' => Certification::STATUS_EXPIRED,
        'expires_at' => today()->subDays(10),
    ]);

    $engine->assess($product);

    // Action corrective réelle : la certification est renouvelée.
    $expired->update([
        'status' => Certification::STATUS_VALID,
        'expires_at' => today()->addYear(),
    ]);

    $engine->assess($product->fresh());

    $history = $engine->history($product);
    expect($history)->toHaveCount(2);

    $comparison = $engine->compare($history[1], $history[0]);

    expect($comparison['delta']['direction'])->toBe('improved');
    expect($comparison['resolved'])->not->toBeEmpty();
    expect($comparison['evidence'])->not->toBeEmpty();
    expect($comparison['conclusion'])->toBe('improvement_confirmed');
});

it('ne confirme pas une amélioration si aucune constatation n’est levée', function () {
    $engine = app(NutriMindService::class);

    $product = nmRecCleanProduct();
    $check = QualityCheck::create([
        'product_id' => $product->id,
        'batch_id' => null,
        'status' => 'completed',
        'quality_score' => 40,
        'decision' => 'inspect',
    ]);

    $engine->assess($product);

    // Le score baisse (moins défavorable) mais la même constatation subsiste.
    $check->update(['quality_score' => 45]);
    $engine->assess($product->fresh());

    $history = $engine->history($product);
    $comparison = $engine->compare($history[1], $history[0]);

    expect($comparison['delta']['direction'])->toBe('improved');
    expect($comparison['resolved'])->toBeEmpty();
    expect($comparison['conclusion'])->toBe('improvement_unconfirmed');
});

it('signale une régression lorsque le risque augmente', function () {
    $engine = app(NutriMindService::class);

    $product = nmRecCleanProduct();
    $cert = Certification::factory()->for($product)->create([
        'status' => Certification::STATUS_VALID,
        'expires_at' => today()->addYear(),
    ]);

    $engine->assess($product);

    $cert->update([
        'status' => Certification::STATUS_EXPIRED,
        'expires_at' => today()->subDay(),
    ]);
    $engine->assess($product->fresh());

    $history = $engine->history($product);
    $comparison = $engine->compare($history[1], $history[0]);

    expect($comparison['delta']['direction'])->toBe('worsened');
    expect($comparison['new'])->not->toBeEmpty();
    expect($comparison['conclusion'])->toBe('regression');
});

// ---------------------------------------------------------------------------
// 4. Contrôle d'accès serveur (permission `manage_products`)
// ---------------------------------------------------------------------------

it('protège la console NutriMind par la permission manage_products', function () {
    $authorized = nmRecAdmin();
    $unauthorized = User::factory()->create();
    $product = Product::factory()->create();
    $params = ['type' => 'product', 'id' => $product->id];

    $this->actingAs($unauthorized)->get(route('admin.nutrimind.index'))->assertForbidden();
    $this->actingAs($unauthorized)->get(route('admin.nutrimind.show', $params))->assertForbidden();
    $this->actingAs($unauthorized)->post(route('admin.nutrimind.assess', $params))->assertForbidden();
    $this->actingAs($unauthorized)->post(route('admin.nutrimind.actions.store', $params), [
        'rule' => 'certification_expired',
        'label' => 'Test',
    ])->assertForbidden();

    $this->assertDatabaseCount('risk_assessments', 0);

    $this->actingAs($authorized)
        ->get(route('admin.nutrimind.index'))
        ->assertOk()
        ->assertSee('NutriMind');
});

// ---------------------------------------------------------------------------
// 5. Console : affichage de la décision justifiée et de la simulation
// ---------------------------------------------------------------------------

it('affiche la décision justifiée, le graphe d’impact et la simulation what-if', function () {
    $admin = nmRecAdmin();
    $product = nmRecCleanProduct();
    Certification::factory()->for($product)->create([
        'status' => Certification::STATUS_EXPIRED,
        'expires_at' => today()->subDays(2),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.nutrimind.show', ['type' => 'product', 'id' => $product->id]))
        ->assertOk()
        ->assertSee('Facteurs contributifs')
        ->assertSee('Recommandations correctives')
        ->assertSee('Graphe d’impact')
        ->assertSee('Historique');

    $this->actingAs($admin)
        ->get(route('admin.nutrimind.show', [
            'type' => 'product',
            'id' => $product->id,
            'scenarios' => ['certification_expired'],
        ]))
        ->assertOk()
        ->assertSee('Simulation');
});

// ---------------------------------------------------------------------------
// 6. Journal d'audit : évaluation et action corrective
// ---------------------------------------------------------------------------

it('enregistre une évaluation et la journalise dans l’audit', function () {
    $admin = nmRecAdmin();
    $product = nmRecCleanProduct();

    $this->actingAs($admin)
        ->post(route('admin.nutrimind.assess', ['type' => 'product', 'id' => $product->id]))
        ->assertRedirect();

    $this->assertDatabaseHas('risk_assessments', [
        'assessable_type' => Product::class,
        'assessable_id' => $product->id,
        'is_simulation' => false,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'nutrimind.assessed',
        'entity_type' => Product::class,
        'entity_id' => $product->id,
    ]);

    $assessment = RiskAssessment::query()->latest('id')->first();
    expect($assessment)->not->toBeNull();
    expect($assessment->created_by)->toBe($admin->id);
});

it('journalise une action corrective engagée', function () {
    $admin = nmRecAdmin();
    $product = Product::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.nutrimind.actions.store', ['type' => 'product', 'id' => $product->id]), [
            'rule' => 'certification_expired',
            'label' => 'Renouveler la certification expirée',
            'note' => 'Organisme émetteur contacté.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'nutrimind.action_recorded',
        'entity_type' => Product::class,
        'entity_id' => $product->id,
    ]);
});
