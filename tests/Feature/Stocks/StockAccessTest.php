<?php

/*
| Module Stocks & Optimisation — contrôle d'accès par rôle (permission manage_stocks).
*/

use App\Models\User;

require_once __DIR__.'/StocksTestHelpers.php';

beforeEach(fn () => $this->withoutVite());

$pages = ['stocks.index', 'stock-movements.index', 'sites.index', 'forecast.index', 'optimization.index'];

it('redirige un visiteur non connecté vers la connexion', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with($pages);

it('ouvre les pages du module aux rôles professionnels', function (string $role) use ($pages) {
    $user = stockUser($role);

    foreach ($pages as $route) {
        $this->actingAs($user)->get(route($route))->assertOk();
    }
})->with(['admin', 'producteur', 'transformateur', 'distributeur']);

it('refuse le module au consommateur (403)', function (string $route) {
    $this->actingAs(stockUser('consommateur'))->get(route($route))->assertForbidden();
})->with($pages);

it('empêche un consommateur d’enregistrer un mouvement de stock', function () {
    $product = stockProduct();
    $site = \App\Models\Site::factory()->warehouse()->create();

    $this->actingAs(stockUser('consommateur'))
        ->post(route('stock-movements.store'), [
            'type' => 'in', 'product_id' => $product->id,
            'destination_site_id' => $site->id, 'quantity' => 50,
        ])
        ->assertForbidden();

    expect(\App\Models\StockMovement::count())->toBe(0);
});

it('affiche les liens du module dans le menu seulement pour les rôles autorisés', function () {
    $this->actingAs(stockUser('distributeur'))->get(route('dashboard'))
        ->assertOk()->assertSee(route('stocks.index'), false);

    // Le consommateur est redirigé vers le catalogue ; on vérifie avec un compte sans permission
    $this->actingAs(User::factory()->create())->get(route('dashboard'))
        ->assertOk()->assertDontSee(route('stocks.index'), false);
});

it('refuse un utilisateur sans aucun rôle', function () {
    $this->actingAs(User::factory()->create())->get(route('stocks.index'))->assertForbidden();
});
