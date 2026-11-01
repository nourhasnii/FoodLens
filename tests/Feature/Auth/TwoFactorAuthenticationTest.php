<?php

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

function enableTwoFactorFor(User $user): User
{
    $user->forceFill([
        'two_factor_secret' => $user->generateTwoFactorSecretKey(),
        'two_factor_recovery_codes' => $user->generateTwoFactorRecoveryCodes(),
        'two_factor_confirmed_at' => now(),
    ])->save();

    return $user->fresh();
}

test('profile page renders the two-factor security section', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee("Authentification à deux facteurs", false);
});

test('profile page renders the QR code while two-factor awaits confirmation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('two-factor.enable'));

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('<svg', false)
        ->assertSee('Clé de configuration', false);
});

test('profile page shows recovery codes right after activation', function () {
    $user = enableTwoFactorFor(User::factory()->create());

    $this->actingAs($user)
        ->withSession(['status' => 'two-factor-confirmed'])
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Codes de secours', false)
        ->assertSee($user->two_factor_recovery_codes[0], false);
});

test('enabling two-factor generates a secret and recovery codes pending confirmation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('two-factor.enable'))
        ->assertRedirect();

    $user->refresh();

    expect($user->two_factor_secret)->not->toBeNull()
        ->and($user->two_factor_recovery_codes)->toHaveCount(8)
        ->and($user->hasPendingTwoFactorConfirmation())->toBeTrue()
        ->and($user->hasEnabledTwoFactorAuthentication())->toBeFalse();
});

test('two-factor is activated after confirming with a valid OTP', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('two-factor.enable'));
    $user->refresh();

    $code = app(Google2FA::class)->getCurrentOtp($user->two_factor_secret);

    $this->actingAs($user)
        ->post(route('two-factor.confirm'), ['code' => $code])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($user->fresh()->hasEnabledTwoFactorAuthentication())->toBeTrue();
});

test('two-factor is not activated with an invalid OTP', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('two-factor.enable'));
    $user->refresh();

    $this->actingAs($user)
        ->post(route('two-factor.confirm'), ['code' => '000000'])
        ->assertSessionHasErrors('code', null, 'twoFactor');

    expect($user->fresh()->hasEnabledTwoFactorAuthentication())->toBeFalse();
});

test('login with two-factor enabled redirects to the challenge without authenticating', function () {
    $user = enableTwoFactorFor(User::factory()->create());

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('two-factor.challenge'));

    $this->assertGuest();
});

test('login can be completed with a valid TOTP code', function () {
    $user = enableTwoFactorFor(User::factory()->create());

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $code = app(Google2FA::class)->getCurrentOtp($user->two_factor_secret);

    $this->post(route('two-factor.challenge'), ['code' => $code])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('login can be completed with a recovery code which is then consumed', function () {
    $user = enableTwoFactorFor(User::factory()->create());
    $recoveryCode = $user->two_factor_recovery_codes[0];

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->post(route('two-factor.challenge'), ['recovery_code' => $recoveryCode])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);

    $remaining = $user->fresh()->two_factor_recovery_codes;
    expect($remaining)->toHaveCount(7)
        ->and($remaining)->not->toContain($recoveryCode);
});

test('login is not completed with an invalid two-factor code', function () {
    $user = enableTwoFactorFor(User::factory()->create());

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->post(route('two-factor.challenge'), ['code' => '000000'])
        ->assertSessionHasErrors('code');

    $this->assertGuest();
});

test('a pending two-factor login can be cancelled', function () {
    $user = enableTwoFactorFor(User::factory()->create());

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('two-factor.challenge'));

    $this->delete(route('two-factor.cancel'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
    $this->get(route('two-factor.challenge'))
        ->assertRedirect(route('login'));
});

test('two-factor can be disabled with the correct password', function () {
    $user = enableTwoFactorFor(User::factory()->create());

    $this->actingAs($user)
        ->delete(route('two-factor.disable'), ['password' => 'password'])
        ->assertRedirect();

    expect($user->fresh()->hasEnabledTwoFactorAuthentication())->toBeFalse();
});

test('two-factor is not disabled with an incorrect password', function () {
    $user = enableTwoFactorFor(User::factory()->create());

    $this->actingAs($user)
        ->delete(route('two-factor.disable'), ['password' => 'wrong-password'])
        ->assertSessionHasErrors('password', null, 'twoFactor');

    expect($user->fresh()->hasEnabledTwoFactorAuthentication())->toBeTrue();
});

test('recovery codes can be regenerated', function () {
    $user = enableTwoFactorFor(User::factory()->create());
    $original = $user->two_factor_recovery_codes;

    $this->actingAs($user)
        ->post(route('two-factor.recovery-codes'))
        ->assertRedirect();

    $regenerated = $user->fresh()->two_factor_recovery_codes;

    expect($regenerated)->toHaveCount(8)
        ->and($regenerated)->not->toEqual($original);
});

test('a pending two-factor setup can be cancelled without a password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('two-factor.enable'));

    $this->actingAs($user)
        ->delete(route('two-factor.disable'))
        ->assertRedirect();

    $user->refresh();

    expect($user->two_factor_secret)->toBeNull()
        ->and($user->hasEnabledTwoFactorAuthentication())->toBeFalse();
});
