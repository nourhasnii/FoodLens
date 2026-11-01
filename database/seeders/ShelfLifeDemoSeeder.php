<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\TransportCondition;
use Illuminate\Database\Seeder;

/**
 * Relevés de température de démonstration pour la DLC dynamique (table du module Chaîne du froid) :
 *  - LOT-2026-001 (yaourt) : panne du groupe froid du camion près d'Enfidha → DLC réelle avancée d'environ 3 jours ;
 *  - LOT-2026-002 (lait)   : chaîne du froid respectée → DLC imprimée conservée (pas de fausse alerte).
 *
 * Prérequis : StockModuleDemoSeeder (qui appelle ce seeder à la fin). Rejouable sans risque.
 */
class ShelfLifeDemoSeeder extends Seeder
{
    public const MARK = 'Démo DLC dynamique';

    public function run(): void
    {
        $journeys = [
            'LOT-2026-001' => [
                // [jours avant aujourd'hui, heure, température, minutes, lieu]
                [2, '06:00', 3.6, 240, 'Usine Béja · chambre froide'],
                [2, '10:00', 4.1, 150, 'Camion frigorifique · A1 Béja → Sfax'],
                [2, '12:30', 9.8, 600, 'Camion frigorifique · panne du groupe froid près d’Enfidha'],
                [2, '22:30', 11.2, 300, 'Camion en attente du dépanneur · Enfidha'],
                [1, '03:30', 14.5, 120, 'Quai de déchargement · Entrepôt Sfax'],
                [1, '05:30', 3.8, 720, 'Entrepôt frigorifique Sfax · chambre 2'],
            ],
            'LOT-2026-002' => [
                [2, '05:00', 3.4, 180, 'Usine Béja · chambre froide'],
                [2, '08:00', 3.9, 300, 'Camion frigorifique · A1 Béja → Sfax'],
                [2, '13:00', 3.7, 60, 'Quai de déchargement · Entrepôt Sfax'],
                [2, '14:00', 3.5, 900, 'Entrepôt frigorifique Sfax · chambre 1'],
            ],
        ];

        $added = [];
        foreach ($journeys as $lot => $readings) {
            $batch = Batch::query()->firstWhere('lot_number', $lot);
            if (! $batch) {
                $this->command?->warn("Lot $lot absent : lancez d’abord « php artisan db:seed --class=StockModuleDemoSeeder ».");

                continue;
            }
            if (TransportCondition::query()->where('batch_id', $batch->id)->where('notes', 'like', self::MARK.'%')->exists()) {
                continue;
            }

            foreach ($readings as [$daysAgo, $time, $temp, $minutes, $location]) {
                TransportCondition::query()->create([
                    'batch_id' => $batch->id,
                    'recorded_at' => now()->subDays($daysAgo)->setTimeFromTimeString($time),
                    'temperature' => $temp,
                    'humidity' => 70,
                    'location' => $location,
                    'duration_minutes' => $minutes,
                    'notes' => self::MARK,
                ]);
            }
            $added[] = $lot;
        }

        $this->command?->info($added
            ? 'Relevés de température de démonstration ajoutés pour '.implode(', ', $added).'.'
            : 'Relevés de température de démonstration déjà présents.');
    }
}
