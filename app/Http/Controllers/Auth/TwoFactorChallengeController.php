<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    /**
     * Show the two-factor challenge screen after a valid password login.
     */
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('login.id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    /**
     * Validate the OTP (or a recovery code) and complete the login.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $id = $request->session()->get('login.id');
        $remember = (bool) $request->session()->get('login.remember', false);

        if (is_null($id)) {
            return redirect()->route('login');
        }

        $user = User::find($id);

        if (! $user || ! $user->hasEnabledTwoFactorAuthentication()) {
            $request->session()->forget(['login.id', 'login.remember']);

            return redirect()->route('login')->withErrors([
                'email' => 'Votre session de connexion a expiré. Veuillez vous reconnecter.',
            ]);
        }

        $isRecoveryAttempt = filled($request->input('recovery_code'));
        $field = $isRecoveryAttempt ? 'recovery_code' : 'code';

        $valid = $isRecoveryAttempt
            ? $user->consumeTwoFactorRecoveryCode((string) $request->input('recovery_code'))
            : $user->verifyTwoFactorCode((string) $request->input('code'));

        if (! $valid) {
            throw ValidationException::withMessages([
                $field => $isRecoveryAttempt
                    ? 'Ce code de secours est invalide.'
                    : 'Le code saisi est invalide.',
            ]);
        }

        Auth::guard('web')->loginUsingId($user->getKey(), $remember);

        $request->session()->forget(['login.id', 'login.remember']);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Abandon a pending two-factor login and return to the login screen.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget(['login.id', 'login.remember']);

        return redirect()->route('login');
    }
}
