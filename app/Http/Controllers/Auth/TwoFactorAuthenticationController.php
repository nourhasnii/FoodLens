<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TwoFactorAuthenticationController extends Controller
{
    /**
     * Generate a new secret + recovery codes and start the confirmation flow.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return back()->with('status', 'two-factor-already-enabled');
        }

        $user->forceFill([
            'two_factor_secret' => $user->generateTwoFactorSecretKey(),
            'two_factor_recovery_codes' => $user->generateTwoFactorRecoveryCodes(),
            'two_factor_confirmed_at' => null,
        ])->save();

        return back()->with('status', 'two-factor-enabling');
    }

    /**
     * Confirm (activate) two-factor authentication using a valid OTP.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ], [
            'code.required' => 'Veuillez saisir le code à 6 chiffres de votre application.',
        ]);

        $user = $request->user();

        if (is_null($user->two_factor_secret)) {
            return back()->withErrors([
                'code' => "Veuillez d'abord activer l'authentification à deux facteurs.",
            ], 'twoFactor');
        }

        if (! $user->verifyTwoFactorCode((string) $request->input('code'))) {
            throw ValidationException::withMessages([
                'code' => 'Le code saisi est invalide. Réessayez.',
            ])->errorBag('twoFactor');
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return back()->with('status', 'two-factor-confirmed');
    }

    /**
     * Disable two-factor authentication. A password confirmation is required
     * when 2FA is active; a pending (unconfirmed) setup can be cancelled without it.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasEnabledTwoFactorAuthentication()) {
            $request->validateWithBag('twoFactor', [
                'password' => ['required', 'current_password'],
            ], [
                'password.required' => 'Veuillez saisir votre mot de passe pour désactiver la double authentification.',
                'password.current_password' => 'Le mot de passe saisi est incorrect.',
            ]);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return back()->with('status', 'two-factor-disabled');
    }

    /**
     * Regenerate the recovery codes for a user who already has 2FA enabled.
     */
    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasEnabledTwoFactorAuthentication()) {
            return back();
        }

        $user->forceFill([
            'two_factor_recovery_codes' => $user->generateTwoFactorRecoveryCodes(),
        ])->save();

        return back()->with('status', 'recovery-codes-regenerated');
    }
}
