<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interdit l'accès au back-office professionnel (dashboard, produits, lots,
 * NutriMind, stocks…) aux utilisateurs ayant le rôle `consommateur`.
 *
 * Le contrôle est effectué côté serveur : masquer les liens du menu ne suffit
 * pas à protéger les routes.
 */
class EnsureProfessionalRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasRole('consommateur')) {
            return redirect()->route('front.products.index');
        }

        return $next($request);
    }
}
