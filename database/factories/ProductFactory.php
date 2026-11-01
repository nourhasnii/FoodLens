<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    private static $productNames = [
        'Huile d\'Olive Extra Vierge de Provence',
        'Miel de Lavande des Alpes',
        'Fromage de Chèvre AOP',
        'Tomates Cerises Heirloom',
        'Saumon Atlantique Élevé Durablement',
        'Poulet Fermier Label Rouge',
        'Riz Basmati Bio du Punjab',
        'Chocolat Noir 72% Grand Cru',
        'Café Arabica Colombien',
        'Pommes Golden Delicious du Limousin',
        'Vin Rouge Bordeaux Grand Cru',
        'Lentilles Vertes du Puy AOC',
        'Amandes de la Vallée du Rhône',
        'Pain au Levain Traditionnel',
    ];

    private static $categories = [
        'Epicerie',
        'Produits Laitiers',
        'Fruits & Légumes',
        'Viandes & Volailles',
        'Poissons & Fruits de Mer',
        'Boissons',
        'Boulangerie',
        'Confiserie',
    ];

    private static $countries = [
        ['country' => 'France', 'regions' => ['Provence', 'Bretagne', 'Normandie', 'Bourgogne', 'Limousin', 'Rhône-Alpes']],
        ['country' => 'Italie', 'regions' => ['Toscane', 'Sicile', 'Piémont', 'Campanie']],
        ['country' => 'Espagne', 'regions' => ['Andalousie', 'Catalogne', 'Basse-Aragon']],
        ['country' => 'Belgique', 'regions' => ['Wallonie', 'Flandre']],
        ['country' => 'Suisse', 'regions' => ['Valais', 'Vaud', 'Genève']],
        ['country' => 'Colombie', 'regions' => ['Huila', 'Nariño', 'Caldas']],
        ['country' => 'Inde', 'regions' => ['Pendjab', 'Kerala', 'Tamil Nadu']],
        ['country' => 'Maroc', 'regions' => ['Souss', 'Haouz', 'Gharb']],
    ];

    private static $units = ['kg', 'g', 'L', 'mL', 'pièce', 'paquet', 'botte', 'douzaine'];

    public function definition(): array
    {
        $name = $this->faker->randomElement(self::$productNames) . ' ' . $this->faker->randomNumber(2);
        $countryData = $this->faker->randomElement(self::$countries);

        return [
            'name' => $name,
            'description' => $this->faker->optional(0.85)->realText(200),
            'category' => $this->faker->randomElement(self::$categories),
            'origin_country' => $countryData['country'],
            'origin_region' => $this->faker->optional(0.7)->randomElement($countryData['regions']),
            'producer_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            'unit' => $this->faker->randomElement(self::$units),
            'image' => null,
            'barcode' => $this->faker->boolean(60) ? $this->faker->unique()->ean13() : null,
            'is_organic' => $this->faker->boolean(35),
            'carbon_footprint' => $this->faker->optional(0.7)->randomFloat(2, 0.1, 15.0),
            'verification_status' => $this->faker->randomElement(['pending', 'verified', 'verified', 'verified', 'rejected']),
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'verified',
        ]);
    }

    public function organic(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_organic' => true,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'pending',
        ]);
    }
}
