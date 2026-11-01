<?php

namespace App\Services\NutriMind;

use App\Models\Batch;
use App\Models\Certification;
use App\Models\OcrCheck;
use App\Models\Product;
use App\Models\QualityCheck;
use Illuminate\Database\Eloquent\Model;

/**
 * NutriMind — recommandations correctives sûres.
 *
 * Traduit chaque constatation du moteur de risque en une recommandation métier
 * explicite. Le lien d'action n'est proposé que s'il pointe vers un workflow
 * réellement existant (route enregistrée) : sinon aucune action n'est affichée,
 * la recommandation textuelle seule est présentée (jamais de bouton inventé).
 *
 * Les actions « consequential » modifient un statut réglementé (rappel, retrait)
 * et doivent être confirmées par l'utilisateur. Aucune action n'est déclenchée
 * automatiquement : le moteur recommande, l'utilisateur décide.
 */
class RecommendationService
{
    public function __construct(private readonly NutriMindService $engine) {}

    public function supports(Model $entity): bool
    {
        return $this->engine->supports($entity);
    }

    /**
     * Construit les recommandations triées par priorité décroissante.
     *
     * @param  array<string, mixed>|null  $evaluation  Évaluation déjà calculée (évite un recalcul).
     * @return array<int, array<string, mixed>>
     */
    public function recommend(Model $entity, ?array $evaluation = null): array
    {
        if (! $this->supports($entity)) {
            throw new \InvalidArgumentException(sprintf(
                'NutriMind ne sait pas formuler de recommandation pour les entités de type %s.',
                $entity::class
            ));
        }

        $evaluation ??= $this->engine->evaluate($entity);

        return collect($evaluation['findings'])
            ->map(fn (array $finding) => $this->forFinding($entity, $finding))
            ->sortByDesc('priority')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $finding
     * @return array<string, mixed>
     */
    private function forFinding(Model $entity, array $finding): array
    {
        $config = config('nutrimind.recommendations.'.$finding['rule'], []);
        $source = $this->resolveSource($finding, $entity);

        return [
            'key' => $this->keyOf($finding),
            'rule' => $finding['rule'],
            'category' => $finding['category'] ?? 'general',
            'kind' => $finding['kind'] ?? 'risk',
            'severity' => $finding['severity'] ?? 'info',
            'title' => $config['label'] ?? ($finding['label'] ?? $finding['rule']),
            'guidance' => $config['guidance'] ?? null,
            'reason' => $finding['reason'] ?? null,
            'contribution' => round((float) ($finding['contribution'] ?? 0), 2),
            'priority' => $this->priority($finding),
            'source' => [
                'type' => $source?->getMorphClass() ?? ($finding['source_type'] ?? null),
                'id' => $source?->getKey() ?? ($finding['source_id'] ?? null),
                'label' => $finding['source_label'] ?? null,
            ],
            'action' => $this->actionFor($finding, $source),
            'consequential' => (bool) ($config['consequential'] ?? false),
            'hypothetical' => (bool) ($finding['hypothetical'] ?? false),
        ];
    }

    /**
     * Priorité déterministe (sévérité + nature confirmée + contribution).
     *
     * @param  array<string, mixed>  $finding
     */
    private function priority(array $finding): float
    {
        $severity = ['info' => 20.0, 'warning' => 45.0, 'critical' => 70.0][$finding['severity'] ?? 'info'] ?? 20.0;

        // Un risque confirmé prime sur une information manquante de même sévérité.
        $kindBonus = ($finding['kind'] ?? 'risk') === 'risk' ? 10.0 : 0.0;

        return round($severity + $kindBonus + (float) ($finding['contribution'] ?? 0), 2);
    }

    /**
     * Action associée à une constatation, en réutilisant un workflow existant.
     * `null` si aucun workflow n'existe : aucune action n'est inventée.
     *
     * @param  array<string, mixed>  $finding
     * @return array{url: string, label: string}|null
     */
    public function actionFor(array $finding, ?Model $source = null): ?array
    {
        $rule = $finding['rule'] ?? null;
        $source ??= $this->resolveSource($finding, null);

        return match ($rule) {
            'recall', 'batch_expired' => $source instanceof Batch
                ? $this->link('admin.batches.edit', $source, 'Gérer le lot')
                : null,

            'certification_expired', 'certification_expiring' => $source instanceof Certification
                ? $this->link('admin.certifications.show', $source, 'Voir la certification')
                : null,

            'missing_certification' => $this->link(
                'admin.certifications.index',
                null,
                'Ajouter une certification',
                array_filter(['product_id' => $this->productIdOf($source)])
            ),

            'quality_failed' => $source instanceof QualityCheck
                ? $this->link('admin.quality-checks.show', $source, 'Voir l’analyse qualité')
                : null,

            'missing_quality_check' => $this->link('admin.quality-checks.create', null, 'Planifier un contrôle'),

            'ocr_inconsistency' => $source instanceof OcrCheck
                ? $this->link('admin.ocr-checks.show', $source, 'Voir le contrôle OCR')
                : null,

            'missing_ocr_check' => $this->link('admin.ocr-checks.create', null, 'Lancer un contrôle OCR'),

            'cold_chain' => $source instanceof Batch
                ? $this->link('admin.batches.show', $source, 'Voir le lot')
                : null,

            'product_unverified' => $source instanceof Product
                ? $this->link('admin.products.show', $source, 'Voir le produit')
                : null,

            default => null,
        };
    }

    /**
     * Résout le modèle source cité par la constatation (à défaut, l'entité évaluée).
     *
     * @param  array<string, mixed>  $finding
     */
    private function resolveSource(array $finding, ?Model $entity): ?Model
    {
        $type = $finding['source_type'] ?? null;
        $id = $finding['source_id'] ?? null;

        if ($type && $id && class_exists($type) && is_subclass_of($type, Model::class)) {
            /** @var Model|null $model */
            $model = $type::query()->find($id);

            if ($model) {
                return $model;
            }
        }

        return $entity;
    }

    private function productIdOf(?Model $source): ?int
    {
        return match (true) {
            $source instanceof Product => (int) $source->getKey(),
            $source instanceof Batch => $source->product_id ? (int) $source->product_id : null,
            default => null,
        };
    }

    /**
     * Construit l'action uniquement si la route existe réellement.
     *
     * @param  array<string, mixed>  $params  Paramètres de requête additionnels.
     * @return array{url: string, label: string}|null
     */
    private function link(string $name, ?Model $model, string $label, array $params = []): ?array
    {
        if (! app('router')->has($name)) {
            return null;
        }

        $arguments = [];

        if ($model) {
            $arguments[] = $model;
        }

        foreach ($params as $key => $value) {
            $arguments[$key] = $value;
        }

        return ['url' => route($name, $arguments), 'label' => $label];
    }

    /** @param  array<string, mixed>  $finding */
    private function keyOf(array $finding): string
    {
        return $finding['key'] ?? implode('|', [
            $finding['rule'] ?? 'rule',
            $finding['source_type'] ?? '',
            $finding['source_id'] ?? '',
        ]);
    }
}
