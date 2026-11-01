<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\ShelfLifeAssessment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Décisions sur la DLC dynamique, pour tester l'historique sans lancer le modèle.
 *
 * @extends Factory<ShelfLifeAssessment>
 */
class ShelfLifeAssessmentFactory extends Factory
{
    protected $model = ShelfLifeAssessment::class;

    public function definition(): array
    {
        $printed = now()->addDays($this->faker->numberBetween(4, 15))->startOfDay();
        $lost = $this->faker->randomFloat(2, 0.5, 4);

        return [
            'batch_id' => Batch::factory(),
            'printed_expiry' => $printed->toDateString(),
            'predicted_expiry' => $printed->copy()->subHours((int) round($lost * 24))->toDateString(),
            'predicted_earliest' => $printed->copy()->subHours((int) round($lost * 30))->toDateString(),
            'predicted_latest' => $printed->copy()->subHours((int) round($lost * 18))->toDateString(),
            'days_lost' => $lost,
            'risk' => $lost >= 2 ? 'critical' : 'attention',
            'decision' => ShelfLifeAssessment::ACCEPTED,
            'note' => $this->faker->optional()->sentence(),
            'model' => ['profile' => 'laitier', 'reference_temp' => 4.0, 't_min' => -2.0],
        ];
    }

    public function rejected(): static
    {
        return $this->state(['decision' => ShelfLifeAssessment::REJECTED]);
    }
}
