<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('ADMIN_PASSWORD');

        if (blank($password)) {
            if (app()->environment('testing')) {
        $password = 'password';
    } else {
        throw new \RuntimeException('ADMIN_PASSWORD must be set before running AdminSeeder.');
    }
            throw new \RuntimeException('ADMIN_PASSWORD must be set before running AdminSeeder.');
        }

        // Les permissions du rôle `admin` sont provisionnées par
        // RolePermissionSeeder (source unique de vérité).
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin = User::firstOrCreate(
            ['email' => 'nour.hasni02@gmail.com'],
            [
                'name' => 'Nour Hasni',
                'password' => Hash::make(env('ADMIN_PASSWORD')),
                'email_verified_at' => now(),
            ],
        );

        if ($admin->email_verified_at === null) {
            $admin->forceFill(['email_verified_at' => now()])->save();
        }

        $admin->assignRole($adminRole);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}