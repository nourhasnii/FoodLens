<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function verificationRoles(): void
{
    foreach (['admin', 'producteur', 'distributeur', 'consommateur'] as $name) {
        Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }
    Permission::firstOrCreate(['name' => 'manage_users', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'review_verifications', 'guard_name' => 'web']);
}

it('activates consumers immediately and holds professional registrations for review', function () {
    /** @var \Tests\TestCase $this */
    verificationRoles();
    config(['services.brevo.api_key' => 'test-key', 'services.brevo.sender_email' => 'sender@example.com', 'services.brevo.admin_email' => 'admin@example.com']);
    Http::fake(['https://api.brevo.com/*' => Http::response(['messageId' => 'registration-message'], 201)]);

    $this->post('/register', ['name' => 'Consumer', 'email' => 'consumer@example.com', 'password' => 'password', 'password_confirmation' => 'password', 'role' => 'consommateur'])->assertRedirect(route('dashboard'));
    expect(User::where('email', 'consumer@example.com')->first()->account_status)->toBe('active');

    $this->post('/logout');
    $this->post('/register', ['name' => 'Producer', 'email' => 'producer@example.com', 'password' => 'password', 'password_confirmation' => 'password', 'role' => 'producteur', 'organization_name' => 'Ferme El Amal'])->assertRedirect(route('dashboard'));
    $producer = User::where('email', 'producer@example.com')->first();
    expect($producer->account_status)->toBe('pending')->and($producer->verificationRequests)->toHaveCount(1);
    Http::assertSentCount(3);
    Http::assertSent(fn ($request) => $request['subject'] === 'Your NutriTrace account is ready' && $request['to'][0]['email'] === 'consumer@example.com');
    Http::assertSent(fn ($request) => $request['subject'] === 'Your NutriTrace application has been received' && $request['to'][0]['email'] === 'producer@example.com');
    Http::assertSent(fn ($request) => $request['subject'] === 'New NutriTrace verification request' && $request['to'][0]['email'] === 'admin@example.com');
});

it('never allows public registration to assign the admin role', function () {
    /** @var \Tests\TestCase $this */
    verificationRoles();

    $this->post('/register', ['name' => 'Attacker', 'email' => 'attacker@example.com', 'password' => 'password', 'password_confirmation' => 'password', 'role' => 'admin'])->assertSessionHasErrors('role');
    expect(User::where('email', 'attacker@example.com')->exists())->toBeFalse();
});

it('allows an authorized admin to request information and reject with a reason', function () {
    /** @var \Tests\TestCase $this */
    verificationRoles();
    $admin = User::factory()->create(['account_status' => 'active']);
    $admin->assignRole('admin');
    $admin->givePermissionTo(['manage_users', 'review_verifications']);
    $producer = User::factory()->create(['account_status' => 'pending', 'organization_name' => 'Farm']);
    $producer->assignRole('producteur');
    $verification = $producer->verificationRequests()->create(['status' => 'pending', 'submitted_at' => now()]);

    config(['services.brevo.api_key' => 'test-key', 'services.brevo.sender_email' => 'sender@example.com']);
    Http::fake(['https://api.brevo.com/*' => Http::response(['messageId' => 'review-message'], 201)]);
    $this->actingAs($admin)->post(route('admin.verification.information', $verification), ['reason' => 'Please provide your professional registration document.'])->assertRedirect();
    expect($producer->fresh()->account_status)->toBe('information_required');

    $verification->refresh();
    $this->actingAs($admin)->post(route('admin.verification.reject', $verification), ['reason' => 'The submitted organization information could not be verified.'])->assertRedirect();
    expect($producer->fresh()->account_status)->toBe('rejected')->and($producer->fresh()->rejection_reason)->not->toBeNull();
    Http::assertSentCount(2);
    Http::assertSent(fn ($request) => $request['subject'] === 'Additional information required for your NutriTrace application' && str_contains($request['htmlContent'], 'Please provide your professional registration document.'));
    Http::assertSent(fn ($request) => $request['subject'] === 'Your NutriTrace application has been rejected' && str_contains($request['htmlContent'], 'The submitted organization information could not be verified.'));
});

it('sends an activation URL through Brevo when an admin approves an account', function () {
    /** @var \Tests\TestCase $this */
    verificationRoles();
    config(['services.brevo.api_key' => 'test-key', 'services.brevo.sender_email' => 'sender@example.com', 'app.url' => 'http://localhost']);
    Http::fake(['https://api.brevo.com/*' => Http::response(['messageId' => 'approval-message'], 201)]);
    $admin = User::factory()->create(['account_status' => 'active']);
    $admin->assignRole('admin');
    $admin->givePermissionTo(['manage_users', 'review_verifications']);
    $producer = User::factory()->create(['account_status' => 'pending']);
    $producer->assignRole('producteur');
    $verification = $producer->verificationRequests()->create(['status' => 'pending', 'submitted_at' => now()]);

    $this->actingAs($admin)->post(route('admin.verification.approve', $verification))->assertRedirect()->assertSessionHas('success');

    Http::assertSent(fn ($request) => $request['subject'] === 'Your NutriTrace account has been approved' && str_contains($request['htmlContent'], '/account/activate/'));
    expect($producer->fresh()->account_status)->toBe('pending')->and($producer->accountActivationTokens()->exists())->toBeTrue();
});

it('handles Brevo API failure without exposing the API key', function () {
    /** @var \Tests\TestCase $this */
    verificationRoles();
    config(['services.brevo.api_key' => 'secret-test-key', 'services.brevo.sender_email' => 'sender@example.com']);
    Http::fake(['https://api.brevo.com/*' => Http::response(['message' => 'rejected'], 401)]);
    $mail = app(\App\Services\BrevoMailService::class);

    expect(fn () => $mail->sendTestEmail('recipient@example.com'))->toThrow(\RuntimeException::class, 'HTTP status: 401. Message: rejected');
    Http::assertSent(fn ($request) => $request->hasHeader('api-key', 'secret-test-key'));
});

it('requires the existing admin permission for the verification center', function () {
    /** @var \Tests\TestCase $this */
    verificationRoles();
    $user = User::factory()->create(['account_status' => 'active']);
    $user->assignRole('producteur');

    $this->actingAs($user)->get(route('admin.verification.index'))->assertForbidden();
});

it('stores activation tokens as hashes and prevents reuse', function () {
    /** @var \Tests\TestCase $this */
    verificationRoles();
    $user = User::factory()->create(['account_status' => 'pending']);
    $user->assignRole('producteur');
    $plainToken = 'known-secure-token';
    $activation = $user->accountActivationTokens()->create(['token_hash' => Hash::make($plainToken), 'expires_at' => now()->addHour()]);

    app(\App\Services\AccountActivationService::class)->activate($plainToken, 'new-password');
    expect($user->fresh()->account_status)->toBe('active')->and($activation->fresh()->used_at)->not->toBeNull();
    expect(fn () => app(\App\Services\AccountActivationService::class)->activate($plainToken, 'another-password'))->toThrow(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
});

it('sends distributor registration through Brevo and keeps it pending', function () {
    /** @var \Tests\TestCase $this */
    verificationRoles();
    config(['services.brevo.api_key' => 'test-key', 'services.brevo.sender_email' => 'sender@example.com', 'services.brevo.admin_email' => 'admin@example.com']);
    Http::fake(['https://api.brevo.com/*' => Http::response(['messageId' => 'distributor-message'], 201)]);

    $this->post('/register', ['name' => 'Distributor', 'email' => 'distributor@example.com', 'password' => 'password', 'password_confirmation' => 'password', 'role' => 'distributeur', 'organization_name' => 'Fresh Market'])->assertRedirect(route('dashboard'));

    expect(User::where('email', 'distributor@example.com')->first()->account_status)->toBe('pending');
    Http::assertSent(fn ($request) => $request['subject'] === 'Your NutriTrace application has been received');
});

it('notifies the administrator when a user resubmits requested information', function () {
    /** @var \Tests\TestCase $this */
    verificationRoles();
    config(['services.brevo.api_key' => 'test-key', 'services.brevo.sender_email' => 'sender@example.com', 'services.brevo.admin_email' => 'admin@example.com']);
    Http::fake(['https://api.brevo.com/*' => Http::response(['messageId' => 'resubmit-message'], 201)]);
    Storage::fake('local');
    $user = User::factory()->create(['account_status' => 'information_required', 'organization_name' => 'Farm']);
    $user->assignRole('producteur');
    $user->verificationRequests()->create(['status' => 'information_required', 'submitted_at' => now(), 'information_request_reason' => 'Need a registration document.']);
    $this->actingAs($user)->post(route('account.verification.update'), [
        'organization_name' => 'Farm Updated',
        'country' => 'Tunisia',
        'region' => 'Nabeul',
        'address' => 'Farm Road',
        'document_type' => 'Registration document',
        'document' => UploadedFile::fake()->create('registration.pdf', 10, 'application/pdf'),
    ])->assertRedirect(route('dashboard'));

    Http::assertSent(fn ($request) => $request['subject'] === 'NutriTrace application resubmitted' && $request['to'][0]['email'] === 'admin@example.com');
});

it('keeps a pending professional on the waiting screen even after verifying their email', function () {
    /** @var \Tests\TestCase $this */
    verificationRoles();
    $producer = User::factory()->unverified()->create(['account_status' => 'pending']);
    $producer->assignRole('producteur');
    $producer->markEmailAsVerified();

    expect($producer->hasVerifiedEmail())->toBeTrue();

    // Le dashboard redirige désormais vers l'écran d'attente dédié.
    $this->actingAs($producer)
        ->get(route('dashboard'))
        ->assertRedirect(route('account.pending'));

    // L'écran d'attente est autonome : ni sidebar, ni menu dashboard.
    $this->actingAs($producer)
        ->get(route('account.pending'))
        ->assertOk()
        ->assertSee('Merci pour votre inscription')
        ->assertSee('Se déconnecter')
        ->assertDontSee('Dashboard');

    expect($producer->fresh()->account_status)->toBe('pending');
});

it('blocks a pending professional from the back office and redirects to the waiting page', function () {
    /** @var \Tests\TestCase $this */
    verificationRoles();
    $producer = User::factory()->create(['account_status' => 'pending']);
    $producer->assignRole('producteur');

    $this->actingAs($producer)
        ->get(route('admin.products.index'))
        ->assertRedirect(route('account.pending'));
});

it('lets an approved professional reach the dashboard and skips the waiting page', function () {
    /** @var \Tests\TestCase $this */
    verificationRoles();
    $producer = User::factory()->create(['account_status' => 'active']);
    $producer->assignRole('producteur');

    $this->actingAs($producer)
        ->get(route('dashboard'))
        ->assertOk();

    $this->actingAs($producer)
        ->get(route('account.pending'))
        ->assertRedirect(route('dashboard'));
});