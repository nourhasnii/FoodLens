<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Garantit l'existence des permissions du back-office et leur attribution aux
 * rôles sur chaque environnement (`php artisan migrate`).
 *
 * Sans cela, une base dont les rôles ont été créés avant l'ajout de nouvelles
 * permissions (ex. `manage_stocks`) masque silencieusement les modules de la
 * sidebar protégés par ces permissions — y compris pour l'admin.
 *
 * La migration est additive et idempotente : elle ne supprime rien.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (\Database\Seeders\RolePermissionSeeder::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (\Database\Seeders\RolePermissionSeeder::ROLE_PERMISSIONS as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

            if ($permissions !== []) {
                $role->givePermissionTo($permissions);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Additif : aucune permission ni rôle n'est retiré (ils peuvent être
        // utilisés par les données métier et les policies).
    }
};
