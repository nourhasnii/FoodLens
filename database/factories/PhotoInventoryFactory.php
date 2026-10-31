<?php

namespace Database\Factories;

use App\Models\PhotoInventory;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Inventaires par photo pour tester l'historique et l'écran de validation sans appeler l'IA.
 *
 * @extends Factory<PhotoInventory>
 */
class PhotoInventoryFactory extends Factory
{
    protected $model = PhotoInventory::class;

    public function definition(): array
    {
        return [
            'site_id' => SiteFactory::new()->retailStore(),
            'photo_path' => 'photo-inventories/demo.jpg',
            'status' => PhotoInventory::ANALYZED,
            'ai_model' => 'qwen2.5vl',
            'items' => [],
            'unmatched' => [],
            'ai_notes' => 'Rayon bien rangé, étiquettes lisibles.',
            'duration_ms' => $this->faker->numberBetween(4000, 60000),
        ];
    }

    public function failed(): static
    {
        return $this->state(['status' => PhotoInventory::FAILED, 'ai_model' => null, 'error' => 'Ollama injoignable.']);
    }

    public function at(Site $site): static
    {
        return $this->state(['site_id' => $site->getKey()]);
    }
}
