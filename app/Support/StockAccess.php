<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Droits d'accès du module Stocks & Optimisation.
 * Les professionnels de la chaîne gèrent les stocks ; le consommateur, non.
 */
class StockAccess
{
    public const PERMISSION = 'manage_stocks';

    /** Rôles qui reçoivent la permission manage_stocks. */
    public const ROLES = ['admin', 'producteur', 'transformateur', 'distributeur'];

    /** Crée la permission et la donne aux rôles existants (idempotent). */
    public static function install(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate(['name' => self::PERMISSION, 'guard_name' => 'web']);

        foreach (self::ROLES as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** Utilisateurs actifs autorisés à gérer les stocks (destinataires des alertes). */
    public static function managers(): Collection
    {
        if (! Permission::where('name', self::PERMISSION)->where('guard_name', 'web')->exists()) {
            return new Collection();
        }

        return User::permission(self::PERMISSION)
            ->get()
            ->filter(fn (User $user) => $user->isActive())
            ->values();
    }
}
