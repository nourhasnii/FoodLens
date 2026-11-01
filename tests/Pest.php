<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Crée une permission Spatie (idempotent) pour les tests.
 */
function testPermission(string $name): \Spatie\Permission\Models\Permission
{
    return \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
}

/**
 * Crée un rôle Spatie (idempotent) et lui attribue les permissions données.
 *
 * @param  array<int, string>  $permissions
 */
function testRole(string $name, array $permissions = []): \Spatie\Permission\Models\Role
{
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    $role->givePermissionTo(array_map('testPermission', $permissions));

    return $role;
}

/**
 * Crée un utilisateur professionnel (rôle `producteur` + permission
 * `manage_products`) pour les tests d'accès au back-office.
 */
function professionalUser(array $attributes = []): \App\Models\User
{
    $role = testRole('producteur', ['manage_products']);

    $user = \App\Models\User::factory()->create($attributes);
    $user->assignRole($role);

    return $user;
}

/**
 * Crée un transformateur (même périmètre qu'un producteur : ses propres données).
 */
function transformateurUser(array $attributes = []): \App\Models\User
{
    $role = testRole('transformateur', ['manage_products']);

    $user = \App\Models\User::factory()->create($attributes);
    $user->assignRole($role);

    return $user;
}

/**
 * Crée un distributeur (périmètre élargi : tous les produits, stocks, rappels).
 */
function distributorUser(array $attributes = []): \App\Models\User
{
    $role = testRole('distributeur', ['manage_products', 'manage_all_products', 'manage_stocks', 'launch_recall']);

    $user = \App\Models\User::factory()->create($attributes);
    $user->assignRole($role);

    return $user;
}

/**
 * Crée un consommateur (aucun accès au back-office professionnel).
 */
function consumerUser(array $attributes = []): \App\Models\User
{
    $role = testRole('consommateur');

    $user = \App\Models\User::factory()->create($attributes);
    $user->assignRole($role);

    return $user;
}

/**
 * Crée un administrateur (périmètre global + gestion des comptes).
 */
function adminUser(array $attributes = []): \App\Models\User
{
    $role = testRole('admin', [
        'manage_products',
        'manage_all_products',
        'manage_stocks',
        'launch_recall',
        'manage_users',
        'review_verifications',
    ]);

    $user = \App\Models\User::factory()->create($attributes);
    $user->assignRole($role);

    return $user;
}
