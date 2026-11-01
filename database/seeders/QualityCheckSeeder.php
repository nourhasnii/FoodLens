<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Product;
use App\Models\QualityCheck;
use App\Models\User;
use Illuminate\Database\Seeder;

class QualityCheckSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::where('verification_status', 'verified')->get();
        if ($products->isEmpty()) {
            $products = Product::all();
        }

        if ($products->isEmpty()) {
            $this->command->warn('Aucun produit trouvé. Exécutez d\'abord ProductSeeder.');

            return;
        }

        $inspector = User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->first();
        if (! $inspector) {
            $inspector = User::first() ?? User::factory()->create();
        }

        $qualityInspector = User::where('email', 'like', '%quality%')->first();
        if (! $qualityInspector) {
            $qualityInspector = User::factory()->create([
                'name' => 'Service Qualité',
                'email' => 'qualite+seeder@example.com',
            ]);
        }

        // Le module Qualité est protégé par la permission `manage_products` :
        // l'inspecteur doit posséder un rôle professionnel pour y accéder.
        if (! $qualityInspector->hasRole('producteur')) {
            $qualityInspector->assignRole('producteur');
        }

        $seedChecks = [
            [
                'product_name' => 'Tomates Cerises Heirloom Bio',
                'decision' => 'sell',
                'quality_score' => 92.50,
                'freshness_score' => 94.20,
                'confidence' => 96.80,
                'explanation' => 'Aspect visuel excellent, couleurs vives et texture ferme. Aucun défaut détecté sur l\'échantillon de 50 pièces analysées.',
                'batch_status' => 'active',
            ],
            [
                'product_name' => 'Fromage de Chèvre AOP Crottin de Chavignol',
                'decision' => 'sell',
                'quality_score' => 88.40,
                'freshness_score' => 85.90,
                'confidence' => 93.50,
                'explanation' => 'Croûte naturelle homogène, pâte bien affinée. Odeur caractéristique sans défauts. Conforme au référentiel AOP.',
                'batch_status' => 'active',
            ],
            [
                'product_name' => 'Poulet Fermier Label Rouge Plein Air',
                'decision' => 'withdraw',
                'quality_score' => 38.20,
                'freshness_score' => 31.50,
                'confidence' => 97.10,
                'explanation' => 'Coloration anormale de la chair et odeur légèrement acide détectée. Date DLC atteinte. Retrait immédiat requis.',
                'batch_status' => 'expired',
            ],
            [
                'product_name' => 'Pommes Golden Delicious Bio du Limousin',
                'decision' => 'donate',
                'quality_score' => 64.80,
                'freshness_score' => 58.30,
                'confidence' => 88.20,
                'explanation' => 'Produit consommable mais présentant des défauts esthétiques mineurs (taches de lenticelles, légères déformations). Convient pour la distribution alimentaire.',
                'batch_status' => 'active',
            ],
            [
                'product_name' => 'Saumon Atlantique Élevé Durablement Label Rouge',
                'decision' => 'sell',
                'quality_score' => 90.10,
                'freshness_score' => 93.60,
                'confidence' => 95.30,
                'explanation' => 'Chair orange vif, texture ferme, absence de décoloration périmarginal. Indice K = 91% : fraîcheur excellente.',
                'batch_status' => 'active',
            ],
            [
                'product_name' => 'Miel de Lavande des Hautes-Alpes',
                'decision' => 'inspect',
                'quality_score' => 52.40,
                'freshness_score' => 71.80,
                'confidence' => 68.50,
                'explanation' => 'Cristallisation hétérogène suspecte, présence d\'inclusions dont l\'origine n\'est pas certaine. Nécessite une vérification manuelle par laboratoire.',
                'batch_status' => 'active',
            ],
            [
                'product_name' => 'Pain au Levain Traditionnel de Campagne',
                'decision' => 'sell',
                'quality_score' => 86.30,
                'freshness_score' => 89.10,
                'confidence' => 91.40,
                'explanation' => 'Mie alvéolée régulière, croûte bien dorée. Acidité typique du levain dans la norme. Poids conforme.',
                'batch_status' => 'active',
            ],
            [
                'product_name' => 'Lentilles Vertes du Puy AOC',
                'decision' => 'withdraw',
                'quality_score' => 29.70,
                'freshness_score' => 22.10,
                'confidence' => 94.60,
                'explanation' => 'Présence de corps étrangers minéraux et détection d\'insectes à 1.2%. Non conforme aux critères sanitaires. Retrait total.',
                'batch_status' => 'expired',
            ],
            [
                'product_name' => 'Riz Basmati Bio du Punjab',
                'decision' => 'donate',
                'quality_score' => 59.90,
                'freshness_score' => 67.20,
                'confidence' => 84.10,
                'explanation' => 'Grains légèrement cassés (+12% par rapport à la spécification) et mélange de calibres non conforme. Valeur gustative intacte.',
                'batch_status' => 'active',
            ],
            [
                'product_name' => 'Chocolat Noir 72% Grand Cru Pérou',
                'decision' => 'sell',
                'quality_score' => 94.80,
                'freshness_score' => 91.30,
                'confidence' => 97.90,
                'explanation' => 'Surface lisse, cassure nette, absence de bloom. Teneur en beurre de cacao et granulométrie conformes au cahier des charges Grand Cru.',
                'batch_status' => 'active',
            ],
        ];

        $createdCount = 0;
        foreach ($seedChecks as $checkData) {
            $product = Product::where('name', $checkData['product_name'])->first();
            if (! $product) {
                continue;
            }

            $batch = Batch::where('product_id', $product->id)
                ->where('status', $checkData['batch_status'])
                ->inRandomOrder()
                ->first()
                ?? Batch::where('product_id', $product->id)->inRandomOrder()->first();

            $check = QualityCheck::create([
                'product_id' => $product->id,
                'batch_id' => $batch?->id,
                'image_path' => null,
                'vision_result' => [
                    'labels' => [
                        ['label' => 'produit_alimentaire', 'score' => 0.99],
                        ['label' => $this->inferVisionLabel($checkData['decision']), 'score' => $checkData['confidence'] / 100],
                    ],
                    'objects_detected' => 1,
                    'resolution' => '2560x1440',
                ],
                'quality_score' => $checkData['quality_score'],
                'freshness_score' => $checkData['freshness_score'],
                'decision' => $checkData['decision'],
                'confidence' => $checkData['confidence'],
                'explanation' => $checkData['explanation'],
                'factors' => [
                    'couleur' => round(min(100, $checkData['quality_score'] + $this->jitter()), 2),
                    'texture' => round(min(100, $checkData['freshness_score'] + $this->jitter()), 2),
                    'forme' => round(min(100, $checkData['quality_score'] - 5 + $this->jitter()), 2),
                    'uniformite' => round(max(0, min(100, ($checkData['confidence'] - 10) + $this->jitter())), 2),
                ],
                'status' => 'completed',
                'created_by' => $this->either($inspector->id, $qualityInspector->id),
            ]);
            $createdCount++;
        }

        $extraSell = QualityCheck::factory()->count(2)->sell()->create(['created_by' => $qualityInspector->id]);
        $extraDonate = QualityCheck::factory()->count(1)->donate()->create(['created_by' => $qualityInspector->id]);
        $extraWithdraw = QualityCheck::factory()->count(1)->withdraw()->create(['created_by' => $qualityInspector->id]);
        $extraInspect = QualityCheck::factory()->count(1)->inspect()->create(['created_by' => $inspector->id]);

        $totalExtra = $extraSell->count() + $extraDonate->count() + $extraWithdraw->count() + $extraInspect->count();

        $this->command->info("QualityCheckSeeder : {$createdCount} contrôles explicites + {$totalExtra} contrôles factory créés.");
        $this->command->line("  sell: {$extraSell->count()} | donate: {$extraDonate->count()} | withdraw: {$extraWithdraw->count()} | inspect: {$extraInspect->count()}");
    }

    private function inferVisionLabel(string $decision): string
    {
        return match ($decision) {
            'sell' => 'qualite_excellente',
            'donate' => 'defauts_esthetiques',
            'withdraw' => 'deterioration_detectee',
            'inspect' => 'anomalie_a_confirmer',
            default => 'analyse_en_cours',
        };
    }

    private function jitter(): float
    {
        return (mt_rand() / mt_getrandmax()) * 10 - 5;
    }

    private function either(int $a, int $b): int
    {
        return (mt_rand(0, 1) === 0) ? $a : $b;
    }
}
