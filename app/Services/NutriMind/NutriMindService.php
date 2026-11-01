<?php

namespace App\Services\NutriMind;

use App\Models\RiskAssessment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * NutriMind — moteur de décision explicable.
 *
 * Agrège les constatations des règles métier (App\Services\NutriMind\RiskRules)
 * en un score de risque 0-100 entièrement justifié :
 *  - le score est la somme des contributions des risques confirmés, plafonnée à 100 ;
 *  - les informations manquantes sont listées séparément (hors score) ;
 *  - un incident de sécurité confirmé impose une sévérité critique et un plancher
 *    de score, indépendamment du total numérique.
 *
 * L'évaluation est réalisée sans écriture (`evaluate`) ou persistée dans
 * l'historique (`assess`), afin de tracer les décisions dans le temps.
 */
class NutriMindService
{
    public function __construct(private readonly RiskRules $rules) {}

    public function supports(Model $entity): bool
    {
        return $this->rules->supports($entity);
    }

    /**
     * Calcule l'évaluation d'une entité sans rien persister.
     *
     * @param  array<int, array<string, mixed>>  $scenarios  Constatations hypothétiques (what-if).
     * @param  array<string, mixed>  $context  Données préchargées (évite les requêtes N+1 sur les listes).
     * @return array<string, mixed>
     */
    public function evaluate(Model $entity, array $scenarios = [], array $context = []): array
    {
        if (! $this->rules->supports($entity)) {
            throw new \InvalidArgumentException(sprintf(
                'NutriMind ne sait pas évaluer les entités de type %s.',
                $entity::class
            ));
        }

        $analysis = $this->rules->analyse($entity, $context);
        $findings = $analysis['findings'];

        if ($scenarios !== []) {
            $findings = $this->rules->applyScenarios($findings, $entity, $scenarios);

            // Une donnée simulée rend caduque l'information « manquante » de même catégorie.
            $suppressedCategories = collect($scenarios)
                ->map(fn (array $scenario) => config('nutrimind.rules.'.$scenario['rule'].'.category'))
                ->filter()
                ->unique()
                ->all();

            $findings = array_filter(
                $findings,
                fn (array $finding) => ! ($finding['kind'] === 'missing'
                    && in_array($finding['category'], $suppressedCategories, true))
            );
        }

        $sorted = collect($findings)->sortByDesc('contribution')->values();

        $riskFindings = $sorted->where('kind', 'risk')->values();
        $missingFindings = $sorted->where('kind', 'missing')->values();

        $criticalOverride = $riskFindings->contains(fn (array $finding) => ! empty($finding['critical_override']));

        $score = min(100.0, round((float) $riskFindings->sum('contribution'), 2));

        if ($criticalOverride) {
            $score = max($score, (float) config('nutrimind.critical_override.floor_score', 70));
        }

        $counts = ['critical' => 0, 'warning' => 0, 'info' => 0];
        foreach ($riskFindings as $finding) {
            $counts[$finding['severity']] = ($counts[$finding['severity']] ?? 0) + 1;
        }

        return [
            'entity_type' => $entity->getMorphClass(),
            'entity_id' => $entity->getKey(),
            'entity_label' => $this->labelOf($entity),
            'score' => round($score, 2),
            'severity' => self::severityFor($score, $criticalOverride),
            'critical_override' => $criticalOverride,
            'findings' => $sorted->all(),
            'risk_findings' => $riskFindings->all(),
            'missing_findings' => $missingFindings->all(),
            'evidence' => $this->evidence($sorted->all()),
            'data_completeness' => $this->completeness($analysis['checks']),
            'checks' => $analysis['checks'],
            'counts' => $counts,
            'is_hypothetical' => $scenarios !== [],
            'scenarios' => array_values(array_filter(array_column($scenarios, 'key'))),
            'rule_set_version' => (string) config('nutrimind.rule_set_version', '1.0'),
        ];
    }

    /**
     * Évalue une entité et conserve le résultat dans l'historique.
     */
    public function assess(Model $entity, ?User $actor = null, ?string $recommendation = null): RiskAssessment
    {
        $result = $this->evaluate($entity);

        return RiskAssessment::create([
            'assessable_type' => $result['entity_type'],
            'assessable_id' => $result['entity_id'],
            'entity_label' => $result['entity_label'],
            'score' => $result['score'],
            'severity' => $result['severity'],
            'findings' => $result['findings'],
            'evidence' => $result['evidence'],
            'recommendation' => $recommendation,
            'data_completeness' => $result['data_completeness'],
            'critical_override' => $result['critical_override'],
            'rule_set_version' => $result['rule_set_version'],
            'is_simulation' => false,
            'created_by' => $actor?->getKey(),
        ]);
    }

    /**
     * Historique des évaluations réelles d'une entité (les plus récentes d'abord).
     *
     * @return \Illuminate\Support\Collection<int, RiskAssessment>
     */
    public function history(Model $entity, int $limit = 10): \Illuminate\Support\Collection
    {
        return RiskAssessment::query()
            ->real()
            ->where('assessable_type', $entity->getMorphClass())
            ->where('assessable_id', $entity->getKey())
            ->latest('created_at')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Compare deux évaluations persistées (avant / après action corrective).
     *
     * Aucune amélioration n'est affirmée sans preuve : le verdict
     * « improvement_confirmed » exige qu'au moins une constatation ait réellement
     * disparu entre les deux évaluations. Une baisse de score sans constatation
     * levée reste « improvement_unconfirmed ».
     *
     * @return array<string, mixed>
     */
    public function compare(RiskAssessment $before, RiskAssessment $after): array
    {
        $delta = round((float) $after->score - (float) $before->score, 2);

        $beforeFindings = collect($before->findings ?? [])->keyBy(fn (array $finding) => $this->findingKey($finding));
        $afterFindings = collect($after->findings ?? [])->keyBy(fn (array $finding) => $this->findingKey($finding));

        $resolved = $beforeFindings->reject(fn ($finding, $key) => $afterFindings->has($key))->values()->all();
        $new = $afterFindings->reject(fn ($finding, $key) => $beforeFindings->has($key))->values()->all();
        $remaining = $afterFindings->filter(fn ($finding, $key) => $beforeFindings->has($key))->values()->all();

        $direction = match (true) {
            $delta < 0 => 'improved',
            $delta > 0 => 'worsened',
            default => 'stable',
        };

        // Preuve d'amélioration : constatations réellement levées et leurs sources.
        $evidence = collect($resolved)->map(fn (array $finding) => [
            'rule' => $finding['rule'] ?? null,
            'label' => $finding['label'] ?? null,
            'source_label' => $finding['source_label'] ?? null,
            'reason' => $finding['reason'] ?? null,
        ])->values()->all();

        $conclusion = match (true) {
            $direction === 'improved' && $resolved !== [] => 'improvement_confirmed',
            $direction === 'improved' => 'improvement_unconfirmed',
            $direction === 'worsened' => 'regression',
            default => 'stable',
        };

        return [
            'before' => $this->snapshot($before),
            'after' => $this->snapshot($after),
            'delta' => [
                'score' => $delta,
                'direction' => $direction,
                'severity_changed' => $before->severity !== $after->severity,
                'from' => $before->severity,
                'to' => $after->severity,
            ],
            'resolved' => $resolved,
            'new' => $new,
            'remaining' => $remaining,
            'evidence' => $evidence,
            'conclusion' => $conclusion,
        ];
    }

    /** @return array<string, mixed> */
    private function snapshot(RiskAssessment $assessment): array
    {
        return [
            'id' => $assessment->getKey(),
            'score' => (float) $assessment->score,
            'severity' => $assessment->severity,
            'data_completeness' => (float) $assessment->data_completeness,
            'at' => optional($assessment->created_at)->toIso8601String(),
        ];
    }

    /** @param  array<string, mixed>  $finding */
    private function findingKey(array $finding): string
    {
        return $finding['key'] ?? implode('|', [
            $finding['rule'] ?? 'rule',
            $finding['source_type'] ?? '',
            $finding['source_id'] ?? '',
        ]);
    }

    /**
     * Sévérité du score global. Un incident critique confirmé force « critical ».
     */
    public static function severityFor(float $score, bool $criticalOverride = false): string
    {
        if ($criticalOverride) {
            return 'critical';
        }

        $thresholds = config('nutrimind.severity_thresholds');

        return match (true) {
            $score >= (float) $thresholds['critical'] => 'critical',
            $score >= (float) $thresholds['warning'] => 'warning',
            default => 'info',
        };
    }

    /**
     * Références de preuve citées par chaque constatation (traçabilité).
     *
     * @param  array<int, array<string, mixed>>  $findings
     * @return array<int, array<string, mixed>>
     */
    private function evidence(array $findings): array
    {
        return collect($findings)->map(fn (array $finding) => [
            'rule' => $finding['rule'],
            'source_type' => $finding['source_type'],
            'source_id' => $finding['source_id'],
            'source_label' => $finding['source_label'],
            'kind' => $finding['kind'],
            'contribution' => $finding['contribution'],
        ])->values()->all();
    }

    /**
     * Taux de complétude des données attendues (0-100).
     *
     * @param  array<string, bool>  $checks
     */
    private function completeness(array $checks): float
    {
        if ($checks === []) {
            return 0.0;
        }

        $present = count(array_filter($checks));

        return round($present / count($checks) * 100, 2);
    }

    private function labelOf(Model $entity): string
    {
        return match (true) {
            $entity instanceof \App\Models\Product => (string) $entity->name,
            $entity instanceof \App\Models\Batch => (string) $entity->lot_number,
            $entity instanceof \App\Models\User => (string) ($entity->organization_name ?: $entity->name),
            default => $entity::class.' #'.$entity->getKey(),
        };
    }
}
