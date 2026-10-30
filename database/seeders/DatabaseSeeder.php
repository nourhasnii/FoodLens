<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $manageProducts = Permission::firstOrCreate(['name' => 'manage_products', 'guard_name' => 'web']);
        $manageUsers = Permission::firstOrCreate(['name' => 'manage_users', 'guard_name' => 'web']);
        $reviewVerifications = Permission::firstOrCreate(['name' => 'review_verifications', 'guard_name' => 'web']);

        $roleProducteur = Role::firstOrCreate(['name' => 'producteur', 'guard_name' => 'web']);
        $roleTransformateur = Role::firstOrCreate(['name' => 'transformateur', 'guard_name' => 'web']);
        $roleDistributeur = Role::firstOrCreate(['name' => 'distributeur', 'guard_name' => 'web']);
        $roleConsommateur = Role::firstOrCreate(['name' => 'consommateur', 'guard_name' => 'web']);

        $roleProducteur->givePermissionTo($manageProducts);
        $roleTransformateur->givePermissionTo($manageProducts);
        $roleDistributeur->givePermissionTo($manageProducts);

        $this->call(AdminSeeder::class);
        $this->call(StockPermissionSeeder::class);

        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => \Illuminate\Database\Eloquent\Factories\Factory::$password
                    ?? \Illuminate\Support\Facades\Hash::make('password'),
                'email_verified_at' => now(),
                'remember_token' => \Illuminate\Support\Str::random(10),
            ]
        );
        if (! $user->hasRole($roleProducteur)) {
            $user->assignRole($roleProducteur);
        }

        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            CertificationSeeder::class,
            BatchSeeder::class,
            QualityCheckSeeder::class,
            TraceabilityEventSeeder::class,
            TransportConditionSeeder::class,
            AlertSeeder::class,
        ]);
    }
}
