<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Permissions du back-office et attribution par rôle (source unique).
        $this->call(RolePermissionSeeder::class);

        $this->call(AdminSeeder::class);

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
        if (! $user->hasRole('producteur')) {
            $user->assignRole('producteur');
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
