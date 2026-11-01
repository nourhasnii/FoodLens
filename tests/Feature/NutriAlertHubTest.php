<?php

use App\Models\Alert;
use App\Models\Batch;
use App\Models\Certification;
use App\Models\OcrCheck;
use App\Models\Product;
use App\Models\QualityCheck;
use App\Models\User;
use App\Notifications\RiskAlertNotification;
use App\Services\NutriAlertService;
use Illuminate\Notifications\Notification;
use Spatie\Permission\Models\Role;

/**
 * Notification de test persistée en base (canal « database » uniquement),
 * utilisée pour vérifier le centre de notifications, les filtres et le compteur.
 */
class NutriAlertTestNotification extends Notification
{
    public function __construct(
        private string $source = 'certification',
        private string $severity = 'critical',
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Alerte '.$this->source,
            'message' => 'Message '.$this->source,
            'severity' => $this->severity,
            'source' => $this->source,
            'priority' => $this->severity === 'critical' ? 92.0 : 20.0,
            'action_url' => null,
            'action_label' => null,
        ];
    }
}

/** Crée un utilisateur disposant du rôle Spatie « admin ». */
function nutrialertAdmin(): User
{
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

// ---------------------------------------------------------------------------
// 1. Calcul de priorité (déterministe)
// ---------------------------------------------------------------------------

it('calcule un score de priorité déterministe et borné', function () {
    // Base par sévérité.
    expect(NutriAlertService::priorityScore('info'))->toBe(20.0);
    expect(NutriAlertService::priorityScore('warning'))->toBe(45.0);
    expect(NutriAlertService::priorityScore('critical'))->toBe(70.0);

    // Urgence d'expiration.
    expect(NutriAlertService::priorityScore('critical', 0))->toBe(100.0); // 70 + 30
    expect(NutriAlertService::priorityScore('warning', 2))->toBe(67.0);   // 45 + 22
    expect(NutriAlertService::priorityScore('warning', 6))->toBe(60.0);   // 45 + 15
    expect(NutriAlertService::priorityScore('warning', 12))->toBe(53.0);  // 45 + 8
    expect(NutriAlertService::priorityScore('warning', 40))->toBe(45.0);

    // Impact métier (lignes affectées).
    expect(NutriAlertService::priorityScore('warning', 12, 3))->toBe(59.0); // 45 + 8 + 6

    // Rappel : urgence maximale + plafond à 100.
    expect(NutriAlertService::priorityScore('critical', null, 50, true))->toBe(100.0);
});

// ---------------------------------------------------------------------------
// 2. Règles de détection métier
// ---------------------------------------------------------------------------

it('détecte les certifications expirées et proches de l’expiration', function () {
    $product = Product::factory()->create();

    $expired = Certification::factory()->for($product)->create([
        'status' => Certification::STATUS_EXPIRED,
        'expires_at' => today()->subDays(5),
    ]);
    $critical = Certification::factory()->for($product)->create([
        'status' => Certification::STATUS_VALID,
        'expires_at' => today()->addDays(2),
    ]);
    $warning = Certification::factory()->for($product)->create([
        'status' => Certification::STATUS_VALID,
        'expires_at' => today()->addDays(6),
    ]);
    $info = Certification::factory()->for($product)->create([
        'status' => Certification::STATUS_VALID,
        'expires_at' => today()->addDays(12),
    ]);

    app(NutriAlertService::class)->sync();

    expect(Alert::where('dedup_key', "certification:{$expired->id}")->value('severity'))->toBe('critical');
    expect(Alert::where('dedup_key', "certification:{$critical->id}")->value('severity'))->toBe('critical');
    expect(Alert::where('dedup_key', "certification:{$warning->id}")->value('severity'))->toBe('warning');
    expect(Alert::where('dedup_key', "certification:{$info->id}")->value('severity'))->toBe('info');
});

it('alerte de manière critique sur les lots rappelés sans en déclencher automatiquement', function () {
    $product = Product::factory()->create();
    $batch = Batch::factory()->for($product)->create(['status' => 'recalled']);

    app(NutriAlertService::class)->sync();

    $alert = Alert::where('dedup_key', "recall:{$batch->id}")->first();

    expect($alert)->not->toBeNull();
    expect($alert->severity)->toBe('critical');
    expect($alert->type)->toBe('recall');
    // Aucun rappel n'est déclenché ou modifié automatiquement.
    expect($batch->fresh()->status)->toBe('recalled');
});

it('détecte les scores qualité faibles avec la bonne sévérité', function () {
    $product = Product::factory()->create();

    $critical = QualityCheck::factory()->for($product)->create([
        'status' => 'completed', 'quality_score' => 25, 'decision' => 'withdraw',
    ]);
    $warning = QualityCheck::factory()->for($product)->create([
        'status' => 'completed', 'quality_score' => 45, 'decision' => 'inspect',
    ]);
    $ok = QualityCheck::factory()->for($product)->create([
        'status' => 'completed', 'quality_score' => 72, 'decision' => 'sell',
    ]);

    app(NutriAlertService::class)->sync();

    expect(Alert::where('dedup_key', "quality:{$critical->id}")->value('severity'))->toBe('critical');
    expect(Alert::where('dedup_key', "quality:{$warning->id}")->value('severity'))->toBe('warning');
    expect(Alert::where('dedup_key', "quality:{$ok->id}")->exists())->toBeFalse();
});

it('détecte les incohérences OCR selon le seuil', function () {
    $product = Product::factory()->create();

    $critical = OcrCheck::create([
        'product_id' => $product->id,
        'image_path' => 'labels/a.jpg',
        'status' => 'completed',
        'inconsistency_score' => 82,
        'mismatches' => [['field' => 'lot_number', 'severity' => 'critical']],
    ]);
    $warning = OcrCheck::create([
        'product_id' => $product->id,
        'image_path' => 'labels/b.jpg',
        'status' => 'completed',
        'inconsistency_score' => 55,
        'mismatches' => [['field' => 'quantity', 'severity' => 'moderate']],
    ]);
    $ok = OcrCheck::create([
        'product_id' => $product->id,
        'image_path' => 'labels/c.jpg',
        'status' => 'completed',
        'inconsistency_score' => 10,
        'mismatches' => [],
    ]);

    app(NutriAlertService::class)->sync();

    expect(Alert::where('dedup_key', "ocr:{$critical->id}")->value('severity'))->toBe('critical');
    expect(Alert::where('dedup_key', "ocr:{$warning->id}")->value('severity'))->toBe('warning');
    expect(Alert::where('dedup_key', "ocr:{$ok->id}")->exists())->toBeFalse();
});

it('alerte lorsqu’un produit actif n’a aucune certification valide, puis résout le risque', function () {
    $product = Product::factory()->create();
    Batch::factory()->for($product)->create(['status' => 'active']);

    app(NutriAlertService::class)->sync();
    expect(Alert::where('dedup_key', "coverage:{$product->id}")->value('severity'))->toBe('warning');

    // Une certification valide fait disparaître le risque.
    Certification::factory()->for($product)->create([
        'status' => Certification::STATUS_VALID,
        'expires_at' => today()->addYear(),
    ]);

    app(NutriAlertService::class)->sync();
    expect(Alert::where('dedup_key', "coverage:{$product->id}")->value('status'))->toBe('resolved');
});

// ---------------------------------------------------------------------------
// 3. Déduplication, mise à jour et résolution
// ---------------------------------------------------------------------------

it('agrège les alertes : aucun doublon et aucune re-notification si le risque est inchangé', function () {
    nutrialertAdmin();

    $product = Product::factory()->create();
    Certification::factory()->for($product)->create([
        'status' => Certification::STATUS_VALID,
        'expires_at' => today()->addDays(4),
    ]);

    $service = app(NutriAlertService::class);

    $first = $service->sync();
    expect($first['created'])->toBe(1);
    expect($first['notified'])->toBeGreaterThan(0);
    expect(Alert::where('type', 'certification')->count())->toBe(1);

    $second = $service->sync();
    expect($second['created'])->toBe(0);
    expect($second['updated'])->toBe(1);
    expect($second['notified'])->toBe(0);
    expect(Alert::where('type', 'certification')->count())->toBe(1);
});

it('met à jour la même alerte et la résout lorsque le risque disparaît', function () {
    $product = Product::factory()->create();
    $cert = Certification::factory()->for($product)->create([
        'status' => Certification::STATUS_VALID,
        'expires_at' => today()->addDays(5),
    ]);

    $service = app(NutriAlertService::class);
    $service->sync();

    expect(Alert::where('dedup_key', "certification:{$cert->id}")->value('status'))->toBe('open');

    $cert->update(['expires_at' => today()->addYears(3)]);
    $stats = $service->sync();

    expect($stats['resolved'])->toBeGreaterThanOrEqual(1);
    expect(Alert::where('dedup_key', "certification:{$cert->id}")->value('status'))->toBe('resolved');
});

it('exécute la commande de détection avec succès', function () {
    nutrialertAdmin();
    $product = Product::factory()->create();
    Batch::factory()->for($product)->create(['status' => 'recalled']);

    $this->artisan('nutritrace:scan-risks')->assertSuccessful();

    expect(Alert::where('type', 'recall')->exists())->toBeTrue();
});

// ---------------------------------------------------------------------------
// 4. Canaux de notification (base + e-mail critique uniquement)
// ---------------------------------------------------------------------------

it('n’envoie l’e-mail que pour les alertes critiques', function () {
    $user = User::factory()->create();

    $critical = new Alert(['type' => 'recall', 'severity' => 'critical', 'title' => 'T', 'message' => 'M', 'status' => 'open']);
    $info = new Alert(['type' => 'certification', 'severity' => 'info', 'title' => 'T', 'message' => 'M', 'status' => 'open']);

    config(['nutrialert.mail' => true]);
    expect((new RiskAlertNotification($critical))->via($user))->toBe(['database', 'mail']);
    expect((new RiskAlertNotification($info))->via($user))->toBe(['database']);

    config(['nutrialert.mail' => false]);
    expect((new RiskAlertNotification($critical))->via($user))->toBe(['database']);
});

it('persiste les notifications en base et cible les administrateurs et le producteur', function () {
    $admin = nutrialertAdmin();
    $producer = User::factory()->create();
    $product = Product::factory()->create(['producer_id' => $producer->id]);
    Batch::factory()->for($product)->create(['status' => 'recalled']);

    app(NutriAlertService::class)->sync();

    expect($admin->fresh()->notifications()->count())->toBeGreaterThan(0);
    expect($producer->fresh()->notifications()->count())->toBeGreaterThan(0);

    $data = $admin->notifications()->first()->data;
    expect($data['source'])->toBe('recall');
    expect($data['severity'])->toBe('critical');
});

// ---------------------------------------------------------------------------
// 5. Sécurité et centre de notifications
// ---------------------------------------------------------------------------

it('protège le centre de notifications par l’authentification', function () {
    $this->get(route('notifications.index'))->assertRedirect(route('login'));
    $this->getJson(route('notifications.recent'))->assertUnauthorized();
});

it('permet à un utilisateur authentifié de consulter et gérer ses notifications', function () {
    $user = User::factory()->create();
    $user->notify(new NutriAlertTestNotification);

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('Centre de notifications')
        ->assertSee('Alerte certification');

    expect($user->fresh()->unreadNotifications()->count())->toBe(1);

    $this->actingAs($user)
        ->getJson(route('notifications.unread-count'))
        ->assertOk()
        ->assertJson(['unread' => 1]);

    $id = $user->notifications()->first()->id;

    $this->actingAs($user)
        ->patchJson(route('notifications.read', ['notification' => $id]))
        ->assertOk()
        ->assertJson(['ok' => true, 'unread' => 0]);

    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});

it('marque toutes les notifications comme lues pour l’utilisateur courant', function () {
    $user = User::factory()->create();
    $user->notify(new NutriAlertTestNotification);
    $user->notify(new NutriAlertTestNotification('recall', 'critical'));

    expect($user->fresh()->unreadNotifications()->count())->toBe(2);

    $this->actingAs($user)
        ->postJson(route('notifications.read-all'))
        ->assertOk()
        ->assertJson(['unread' => 0]);

    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});

it('empêche un utilisateur de marquer la notification d’un autre', function () {
    $owner = User::factory()->create();
    $owner->notify(new NutriAlertTestNotification);

    $intruder = User::factory()->create();
    $id = $owner->notifications()->first()->id;

    $this->actingAs($intruder)
        ->patchJson(route('notifications.read', ['notification' => $id]))
        ->assertNotFound();

    expect($owner->fresh()->unreadNotifications()->count())->toBe(1);
});

it('filtre par source, sévérité et état et trie par priorité', function () {
    $user = User::factory()->create();
    $user->notify(new NutriAlertTestNotification('certification', 'critical'));
    $user->notify(new NutriAlertTestNotification('recall', 'critical'));
    $user->notify(new NutriAlertTestNotification('quality', 'warning'));

    // Chaque carte du centre de notifications est un <article> (la cloche n'en utilise pas).
    $bySource = $this->actingAs($user)
        ->get(route('notifications.index', ['source' => 'recall']))
        ->assertOk()
        ->getContent();
    expect(substr_count($bySource, '<article'))->toBe(1);
    expect($bySource)->toContain('Alerte recall');

    $bySeverity = $this->actingAs($user)
        ->get(route('notifications.index', ['severity' => 'warning']))
        ->assertOk()
        ->getContent();
    expect(substr_count($bySeverity, '<article'))->toBe(1);
    expect($bySeverity)->toContain('Alerte quality');

    $user->notifications()->where('data->source', 'quality')->first()->markAsRead();

    $byState = $this->actingAs($user)
        ->get(route('notifications.index', ['state' => 'read']))
        ->assertOk()
        ->getContent();
    expect(substr_count($byState, '<article'))->toBe(1);
    expect($byState)->toContain('Alerte quality');

    // Tri par priorité (décroissante) : les deux critiques (92) précèdent l'avertissement (20).
    $ordered = $this->actingAs($user)->get(route('notifications.index'))->assertOk()->getContent();
    preg_match_all('/<h3 class="text-sm font-bold text-ink">([^<]+)<\/h3>/', $ordered, $matches);

    expect($matches[1])->toHaveCount(3);
    expect($matches[1][2])->toBe('Alerte quality');
});
