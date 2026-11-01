<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Écran d'attente dédié aux comptes professionnels en cours de vérification.
 * Cet écran est volontairement autonome (sans sidebar ni menu dashboard).
 */
class AccountPendingController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        // Un compte déjà actif (ou un administrateur) n'a rien à faire ici.
        if ($user->isActive() || $user->hasRole('admin')) {
            return redirect()->route('dashboard');
        }

        return view('account.pending', ['user' => $user]);
    }
}
