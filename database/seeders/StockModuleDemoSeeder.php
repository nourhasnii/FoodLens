<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Démo complète du module Stocks & Optimisation sur FoodLens :
 * 2 produits laitiers et 2 lots (créés par la BatchFactory de l'équipe) si absents,
 * puis sites, associations, stocks et 30 jours de ventes.
 * Prérequis : au moins un utilisateur (php artisan db:seed crée l'admin et le Test User).
 * Lancement : php artisan db:seed --class=StockModuleDemoSeeder
 */
class StockModuleDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(StockPermissionSeeder::class);

        $producerId = User::query()->value('id');

        if (! $producerId) {
            $this->command?->warn('Aucun utilisateur : lancez d’abord « php artisan db:seed ».');

            return;
        }

        // Produits fixes pour la démo ; lots créés par la BatchFactory de l'équipe
        $product = fn (string $name, string $unit) => Product::query()->firstWhere('name', $name)
            ?? Product::query()->create([
                'name' => $name, 'unit' => $unit, 'producer_id' => $producerId,
                'category' => 'Produits Laitiers', 'origin_country' => 'Tunisie', 'verification_status' => 'verified',
                'description' => 'Produit de démonstration du module Stocks & Optimisation.',
            ]);

        $yaourt = $product('Yaourt nature 125 g', 'pot');
        $lait = $product('Lait frais 1 L', 'L');

        $batch = fn (Product $p, string $lot, int $days, string $unit) => Batch::query()->firstWhere('lot_number', $lot)
            ?? Batch::factory()->for($p, 'product')->active()->create([
                'lot_number' => $lot, 'quantity' => 1000, 'unit' => $unit,
                'production_date' => now()->subDays(2)->toDateString(),
                'expiration_date' => now()->addDays($days)->toDateString(),
            ]);

        // Le yaourt périme dans 12 jours, le lait dans 2 jours (pour la démo du plan anti-gaspillage)
        $batch($yaourt, 'LOT-2026-001', 12, 'pot');
        $batch($lait, 'LOT-2026-002', 2, 'L');

        $this->command?->info('Produits et lots de démonstration prêts.');

        $this->call(StockDemoSeeder::class);

        // Températures du transport des lots de démo, pour la DLC dynamique
        $this->call(ShelfLifeDemoSeeder::class);
    }
}
