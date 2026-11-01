<?php

use App\Models\Certification;
use App\Models\CertificationAIAnalysis;
use App\Models\User;
use App\Jobs\AnalyzeCertificationIntelligenceJob;
use App\Services\CertificationAIService;
use App\Services\CertificationIntelligenceService;
use App\Services\OllamaService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function seededProducerUser(): User
{
    $manageProducts = Permission::firstOrCreate(['name' => 'manage_products', 'guard_name' => 'web']);
    $producer = Role::firstOrCreate(['name' => 'producteur', 'guard_name' => 'web']);
    $producer->givePermissionTo($manageProducts);

    $user = User::factory()->create();
    $user->assignRole($producer);

    return $user;
}

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->seed(\Database\Seeders\ProductSeeder::class);
    $this->seed(\Database\Seeders\CertificationSeeder::class);
});

function deterministicSample(): array
{
    return [
        'compliance' => ['score' => 68.0, 'level' => 'ATTENTION'],
        'risk' => [
            'score' => 72.0,
            'level' => 'HIGH',
            'factors' => [
                '2 certifications are expired',
                '4 certifications expire within 30 days',
                '7 products have no valid certification',
                '15 products are potentially affected',
            ],
        ],
        'counts' => [
            'valid_certifications' => 24,
            'expiring_soon' => 4,
            'expired_certifications' => 2,
            'pending_certifications' => 1,
            'total_certifications' => 31,
            'certified_products' => 10,
            'uncertified_products' => 7,
            'affected_products' => 15,
            'products_affected_by_expiring' => 10,
            'products_affected_by_expired' => 5,
            'total_products' => 17,
        ],
        'snapshot' => [
            'generated_at' => now()->toIso8601String(),
            'window_days' => 30,
        ],
        'expiration_analysis' => [
            'groups' => [
                'already_expired' => 2,
                '0_to_7_days' => 1,
                '8_to_30_days' => 3,
                '31_to_90_days' => 8,
                'over_90_days' => 17,
            ],
            'expiring_in_next_7_days' => 1,
            'nearest' => [],
        ],
        'impact_analysis' => [
            'coverage_percent' => 58.8,
            'expired_by_organization' => [],
            'expiring_by_organization' => [],
            'top_affected_products' => [],
        ],
        'priority_actions' => [
            ['priority' => 'HIGH', 'action' => 'Renew expired certifications', 'reason' => '2 are expired'],
        ],
    ];
}

it('computes deterministic certification intelligence using real products and certifications', function () {
    $service = app(CertificationIntelligenceService::class);
    $report = $service->generate();

    expect($report)->toBeArray()
        ->and($report)->toHaveKeys(['snapshot', 'counts', 'compliance', 'risk', 'expiration_analysis', 'impact_analysis', 'priority_actions']);

    $counts = $report['counts'];
    $totalInDb = Certification::count();
    expect($counts['total_certifications'])->toBeInt()->toBe($totalInDb)
        ->and($counts['valid_certifications'] + $counts['expiring_soon'] + $counts['expired_certifications'] + $counts['pending_certifications'])
        ->toBeGreaterThanOrEqual($totalInDb);

    $compliance = $report['compliance'];
    expect($compliance['score'])->toBeFloat()->toBeBetween(0, 100)
        ->and($compliance['level'])->toBeIn(['EXCELLENT', 'GOOD', 'ATTENTION', 'CRITICAL']);

    $risk = $report['risk'];
    expect($risk['score'])->toBeFloat()->toBeBetween(0, 100)
        ->and($risk['level'])->toBeIn(['LOW', 'MEDIUM', 'HIGH'])
        ->and($risk['factors'])->toBeArray();

    expect($report['priority_actions'])->toBeArray()->not->toBeEmpty();
});

it('parses a valid Ollama JSON response and returns structured AI insight', function () {
    $validOllamaJson = json_encode([
        'summary' => 'La conformité certifications est en ATTENTION à 68/100 car plusieurs certifications critiques arrivent à expiration.',
        'risk_explanation' => 'Le niveau de risque est HAUT principalement à cause de 2 certifications déjà expirées et 4 autres qui arrivent à échéance dans moins de 30 jours.',
        'key_insights' => [
            '2 certifications sont expirées',
            '4 certifications nécessitent un renouvellement imminent',
            '15 produits sont potentiellement impactés',
            'Seulement 58.8% du catalogue produit est couvert par une certification valide.',
        ],
        'priority_actions' => [
            ['priority' => 'HIGH', 'action' => 'Relancer d\'urgence les certifications expirées', 'reason' => '2 certifications sont expirées et impactent la conformité.'],
            ['priority' => 'MEDIUM', 'action' => 'Lancer une campagne de renouvellement des certifications ≤30j', 'reason' => '4 certifications expire dans 30 jours, soit 10 produits impactés.'],
        ],
        'business_impact' => 'Risque d\'écart à l\'audit, perte de l\'appellation Bio sur les rayons et amendes réglementaires potentielles.',
        'confidence' => 92,
    ]);

    Http::fake(['http://localhost:11434/api/generate' => Http::response([
        'model' => 'qwen2.5vl',
        'done' => true,
        'response' => $validOllamaJson,
    ])]);

    $ai = app(CertificationAIService::class);
    $result = $ai->analyze(deterministicSample(), true);

    expect($result['success'])->toBeTrue()
        ->and($result['used_persisted_cache'])->toBeFalse()
        ->and($result['error_message'])->toBeNull()
        ->and($result['analysis'])->toBeInstanceOf(CertificationAIAnalysis::class);

    $analysis = $result['analysis'];
    expect($analysis->summary)->toBeString()->not->toBeEmpty()
        ->and($analysis->risk_explanation)->toBeString()->not->toBeEmpty()
        ->and($analysis->business_impact)->toBeString()->not->toBeEmpty()
        ->and($analysis->key_insights)->toBeArray()->toHaveCount(4)
        ->and($analysis->priority_actions)->toBeArray()->toHaveCount(2)
        ->and($analysis->priority_actions[0]['priority'])->toBe('HIGH')
        ->and($analysis->priority_actions[0]['action'])->toBeString()
        ->and($analysis->priority_actions[0]['reason'])->toBeString()
        ->and($analysis->confidence)->toEqual(92, 0.01)
        ->and($analysis->model)->toBe('qwen2.5vl')
        ->and($analysis->prompt_version)->toBe(CertificationAIService::PROMPT_VERSION)
        ->and($analysis->compliance_score)->toBe(68)
        ->and($analysis->risk_score)->toBe(72)
        ->and($analysis->risk_level)->toBe('HIGH')
        ->and($analysis->compliance_level)->toBe('ATTENTION')
        ->and($analysis->duration_ms)->toBeInt()->toBeGreaterThanOrEqual(0);
    expect($analysis->status)->toBe('completed');
    Http::assertSent(fn ($request) => $request->url() === 'http://localhost:11434/api/generate'
        && $request['model'] === 'qwen2.5vl'
        && ! array_key_exists('images', $request->data()));
    expect(OllamaService::DEFAULT_TIMEOUT)->toBe(300)
        ->and(config('services.ollama.timeout'))->toBe(300);
});

it('handles malformed or missing JSON safely with a fallback entry persisted', function () {
    Http::fake(['http://localhost:11434/api/generate' => Http::response([
        'model' => 'qwen2.5vl',
        'done' => true,
        'response' => '{"broken": ',
    ])]);

    Log::shouldReceive('warning')->atLeast()->once();

    $ai = app(CertificationAIService::class);
    $result = $ai->analyze(deterministicSample(), true);

    expect($result['success'])->toBeFalse()
        ->and($result['error_message'])->toBe('Invalid AI response JSON')
        ->and($result['analysis'])->toBeInstanceOf(CertificationAIAnalysis::class);

    $analysis = $result['analysis'];
    expect($analysis->summary)->toBe(CertificationAIService::FALLBACK_SUMMARY)
        ->and($analysis->risk_explanation)->toBe(CertificationAIService::FALLBACK_RISK_EXPLANATION)
        ->and($analysis->business_impact)->toBe(CertificationAIService::FALLBACK_BUSINESS_IMPACT)
        ->and($analysis->key_insights)->toBeArray()->not->toBeEmpty()
        ->and($analysis->priority_actions)->toBeArray()->not->toBeEmpty()
        ->and($analysis->confidence)->toBeNull()
        ->and($analysis->model)->toStartWith('fallback_')
        ->and($analysis->status)->toBe('failed')
        ->and($analysis->error_message)->not->toBeEmpty();
});

it('handles unavailable Ollama (connection exception) with deterministic dashboard still usable', function () {
    /** @var \Tests\TestCase $this */
    $user = seededProducerUser();

    Queue::fake();
    Http::fake(['http://localhost:11434/api/generate' => function () {
        throw new ConnectionException('cURL error 28: Operation timed out after 300000 milliseconds');
    }]);

    $ai = app(CertificationAIService::class);
    $result = $ai->analyze(deterministicSample(), true);

    expect($result['success'])->toBeFalse()
        ->and($result['error_message'])->toBe('Ollama unreachable')
        ->and($result['analysis'])->toBeInstanceOf(CertificationAIAnalysis::class)
        ->and($result['analysis']->summary)->toBe(CertificationAIService::FALLBACK_SUMMARY)
        ->and($result['analysis']->error_message)->toContain('300000 milliseconds');

    $this->actingAs($user)
        ->get(route('admin.certifications.intelligence'))
        ->assertOk()
        ->assertSee('Conformité')
        ->assertSee('Certification AI Insight');

    $this->actingAs($user)
        ->post(route('admin.certifications.intelligence.analyze.store'))
        ->assertRedirect(route('admin.certifications.intelligence'))
        ->assertSessionHas('success');

    $pending = CertificationAIAnalysis::latestAttempt();
    expect($pending?->status)->toBe('pending');
    Queue::assertPushed(AnalyzeCertificationIntelligenceJob::class);

    $this->actingAs($user)
        ->get(route('admin.certifications.intelligence'))
        ->assertOk()
        ->assertSee('Analyse IA en cours')
        ->assertSee('Conformité');
});

it('persists an AI analysis on disk and restores cached version without calling Ollama again', function () {
    $ai = app(CertificationAIService::class);
    $initialPersisted = CertificationAIAnalysis::create([
        'summary' => 'Previous analysis stored in database.',
        'risk_explanation' => 'Risk HIGH because expired certifications exist.',
        'key_insights' => ['insight cached 1', 'insight cached 2'],
        'priority_actions' => [['priority' => 'LOW', 'action' => 'review later', 'reason' => 'test']],
        'business_impact' => 'Minor commercial risk.',
        'confidence' => 88,
        'compliance_score' => 74,
        'risk_score' => 42,
        'risk_level' => 'MEDIUM',
        'compliance_level' => 'GOOD',
        'model' => 'qwen2.5vl',
        'prompt_version' => CertificationAIService::PROMPT_VERSION,
        'duration_ms' => 123,
        'status' => 'completed',
    ]);

    Http::fake(['http://localhost:11434/api/generate' => fn () => throw new RequestException(
        new \Illuminate\Http\Client\Response(new \GuzzleHttp\Psr7\Response(500))
    )]);

    Http::shouldReceive('timeout->post')->never();

    $result = $ai->analyze(deterministicSample(), false);

    expect($result['success'])->toBeTrue()
        ->and($result['used_persisted_cache'])->toBeTrue()
        ->and($result['error_message'])->toBeNull()
        ->and($result['analysis']?->is($initialPersisted))->toBeTrue()
        ->and($result['analysis']?->summary)->toBe('Previous analysis stored in database.');

    $latest = CertificationAIAnalysis::latestSaved();
    expect($latest)->toBeInstanceOf(CertificationAIAnalysis::class)
        ->and($latest->id)->toBe($initialPersisted->id);
});

it('blocks unauthorized users from triggering the AI endpoint but keeps deterministic page accessible with permission', function () {
    /** @var \Tests\TestCase $this */
    $user = seededProducerUser();
    $unauthorized = User::factory()->create();

    $this->actingAs($unauthorized)
        ->get(route('admin.certifications.intelligence'))
        ->assertForbidden();

    $this->actingAs($unauthorized)
        ->post(route('admin.certifications.intelligence.analyze.store'))
        ->assertForbidden();

    $this->assertDatabaseCount('certification_ai_analyses', 0);

    $this->actingAs($user)
        ->get(route('admin.certifications.intelligence'))
        ->assertOk()
        ->assertSee('Certification Intelligence');
});

it('still renders the deterministic certification intelligence dashboard when Ollama is completely absent or throws during page load', function () {
    /** @var \Tests\TestCase $this */
    $user = seededProducerUser();

    Queue::fake();

    $this->actingAs($user)
        ->get(route('admin.certifications.intelligence'))
        ->assertOk()
        ->assertSeeText('Conformité')
        ->assertSeeText('Risque')
        ->assertSeeText('Certifications')
        ->assertSeeText('Produits')
        ->assertSeeText('Facteurs de risque')
        ->assertSeeText('Actions prioritaires — moteur déterministe')
        ->assertSeeText('🤖 Certification AI Insight')
        ->assertSeeText('Générer la première analyse IA');

    $this->actingAs($user)
        ->post(route('admin.certifications.intelligence.analyze.store'))
        ->assertRedirect(route('admin.certifications.intelligence'))
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->get(route('admin.certifications.intelligence'))
        ->assertOk()
        ->assertSeeText('Certification AI Insight')
        ->assertSeeText('Analyse IA en cours')
        ->assertSeeText('Conformité');
});

it('updates a pending analysis row when the queued Ollama analysis completes', function () {
    $analysis = CertificationAIAnalysis::query()->create([
        'summary' => 'Pending summary',
        'risk_explanation' => 'Pending risk explanation',
        'key_insights' => [],
        'priority_actions' => [],
        'business_impact' => 'Pending business impact',
        'status' => 'pending',
    ]);

    Http::fake(['http://localhost:11434/api/generate' => Http::response([
        'model' => 'qwen2.5vl',
        'done' => true,
        'response' => json_encode([
            'summary' => 'Completed summary',
            'risk_explanation' => 'Completed risk explanation',
            'key_insights' => ['Insight'],
            'priority_actions' => [['priority' => 'LOW', 'action' => 'Review', 'reason' => 'Routine']],
            'business_impact' => 'Completed business impact',
            'confidence' => 90,
        ]),
    ])]);

    (new AnalyzeCertificationIntelligenceJob($analysis, deterministicSample()))
        ->handle(app(CertificationAIService::class));

    expect($analysis->fresh()->status)->toBe('completed')
        ->and($analysis->fresh()->summary)->toBe('Completed summary')
        ->and(CertificationAIAnalysis::query()->count())->toBe(1);
});
