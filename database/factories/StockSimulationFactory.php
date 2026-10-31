<?php

namespace Database\Factories;

use App\Models\StockSimulation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Simulations du jumeau numérique pour tester l'historique et les écrans sans lancer le calcul.
 * Pour une vraie simulation : DigitalTwinSimulator::run().
 *
 * @extends Factory<StockSimulation>
 */
class StockSimulationFactory extends Factory
{
    protected $model = StockSimulation::class;

    public function definition(): array
    {
        $preset = $this->faker->randomElement(array_keys(StockSimulation::PRESETS));
        $baselineWaste = $this->faker->numberBetween(100, 900);
        $aiWaste = (int) round($baselineWaste * $this->faker->randomFloat(2, 0, 0.4));
        $baselineUnmet = $this->faker->numberBetween(0, 200);
        $aiUnmet = (int) round($baselineUnmet * $this->faker->randomFloat(2, 0, 0.5));
        $donated = $this->faker->numberBetween(0, 600);
        $totals = fn (float $wasted, float $unmet, float $donated) => [
            'demand' => 2000, 'sold' => 2000 - $unmet, 'unmet' => $unmet, 'wasted' => $wasted, 'donated' => $donated,
            'promo_units' => 0, 'transfers' => 8, 'transferred' => 500, 'km' => 900, 'co2_transport' => 20,
            'waste_kg' => $wasted, 'donated_kg' => $donated, 'remaining' => 1000,
            'service_rate' => round(100 * (2000 - $unmet) / 2000, 1), 'meals' => round($donated / 0.5), 'co2_waste' => $wasted * 2.5,
        ];

        return [
            'name' => StockSimulation::PRESETS[$preset].' · '.$this->faker->date('d/m'),
            'preset' => $preset,
            'scenario' => ['horizon' => 14],
            'horizon_days' => 14,
            'start_date' => now()->toDateString(),
            'baseline_wasted' => $baselineWaste,
            'ai_wasted' => $aiWaste,
            'baseline_unmet' => $baselineUnmet,
            'ai_unmet' => $aiUnmet,
            'ai_donated' => $donated,
            'results' => [
                'start' => now()->toDateString(), 'horizon' => 14, 'days' => [], 'sites' => [], 'products' => [],
                'initial' => ['lines' => 0, 'units' => 0, 'lots_with_expiry' => 0],
                'baseline' => ['totals' => $totals($baselineWaste, $baselineUnmet, 0), 'daily' => [], 'actions' => [], 'frames' => []],
                'ai' => ['totals' => $totals($aiWaste, $aiUnmet, $donated), 'daily' => [], 'actions' => [], 'frames' => []],
                'comparison' => [], 'explanation' => ['Simulation de démonstration (factory).'],
            ],
            'user_id' => null,
        ];
    }
}
