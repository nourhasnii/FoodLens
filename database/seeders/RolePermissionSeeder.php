<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Source unique de vérité pour les permissions du back-office et leur
 * attribution par rôle.
 *
 * Ce seeder est idempotent : il peut être ré-exécuté sans risque. Il est la
 * garanti que les permissions attendues par l'interface (ex. `manage_stocks`
 * pour les modules Stocks/Sites/Forecast/Optimization) existent réellement en
 * base — sans quoi les menus concernés disparaissent silencieusement.
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Toutes les permissions du back-office.
     *
     * @var array<int, string>
     */
    public const PERMISSIONS = [
        'manage_products',
        'manage_all_products',
        'manage_stocks',
        'launch_recall',
        'manage_users',
        'review_verifications',
    ];

    /**
     * Permissions attribuées à chaque rôle.
     *
     * @var array<string, array<int, string>>
     */
    public const ROLE_PERMISSIONS = [
        // Aucune permission : redirigé hors du dashboard professionnel.
        'consommateur' => [],
        // Accès limité à leurs propres produits et lots.
        'producteur' => ['manage_products'],
        'transformateur' => ['manage_products'],
        // Périmètre élargi : tous les produits, stocks et rappels.
        'distributeur' => ['manage_products', 'manage_all_products', 'manage_stocks', 'launch_recall'],
        // Administration complète.
        'admin' => [
            'manage_products',
            'manage_all_products',
            'manage_stocks',
            'launch_recall',
            'manage_users',
            'review_verifications',
        ],
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

            if ($permissions !== []) {
                // Attribution additive : on ne retire jamais de permission existante.
                $role->givePermissionTo($permissions);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
