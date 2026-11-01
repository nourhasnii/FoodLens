<?php

namespace App\Services\NutriMind;

use App\Models\Batch;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * NutriMind — simulation « et si ? » (what-if).
 *
 * Compare une évaluation réelle (état actuel des données) à une évaluation
 * hypothétique dans laquelle un ou plusieurs scénarios de risque sont injectés.
 *
 * Garanties :
 *  - aucune donnée réelle n'est modifiée : la simulation est purement calculée ;
 *  - les constatations hypothétiques sont explicitement marquées (`hypothetical`) ;
 *  - le résultat est marqué `is_simulation` et n'est jamais présenté comme un fait ;
 *  - la comparaison s'appuie sur la clé de déduplication des constatations, donc un
 *    scénario qui recouvre une règle déjà déclenchée n'entraîne aucun double comptage.
 */
class ScenarioSimulator
{
    public function __construct(
        private readonly NutriMindService $engine,
        private readonly ImpactAnalysisService $impact,
    ) {}

    public function supports(Model $entity): bool
    {
        return $this->engine->supports($entity);
    }

    /**
     * Catalogue des scénarios disponibles (issus de la configuration).
     *
     * @return array<int, array<string, mixed>>
     */
    public function scenarios(): array
    {
        return collect(config('nutrimind.scenarios', []))
            ->map(fn (array $scenario, string $key) => ['key' => $key] + $scenario)
            ->values()
            ->all();
    }

    /**
     * Simule un ou plusieurs scénarios et compare au scénario de référence.
     *
     * @param  array<int, string>  $scenarioKeys  Clés de scénarios (config/nutrimind.php).
     * @return array<string, mixed>
     */
    public function simulate(Model $entity, array $scenarioKeys): array
    {
        if (! $this->supports($entity)) {
            throw new \InvalidArgumentException(sprintf(
                'NutriMind ne sait pas simuler les entités de type %s.',
                $entity::class
            ));
        }

        $selected = $this->resolve($scenarioKeys);

        $baseline = $this->engine->evaluate($entity);
        $simulated = $scenarioKeys === [] ? $baseline : $this->engine->evaluate($entity, $selected);

        return [
            'is_simulation' => true,
            'entity_type' => $entity->getMorphClass(),
            'entity_id' => $entity->getKey(),
            'entity_label' => $this->labelOf($entity),
            'scenarios' => $selected,
            'baseline' => $this->summary($baseline),
            'simulated' => $this->summary($simulated),
            'delta' => $this->delta($baseline, $simulated),
            'findings' => $this->compareFindings($baseline, $simulated),
            // Périmètre projeté : le graphe d'impact reflète l'état réel des relations.
            'impact' => $this->impact->analyze($entity),
        ];
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<int, array<string, mixed>>
     */
    private function resolve(array $keys): array
    {
        $catalogue = collect($this->scenarios())->keyBy('key');

        return collect($keys)
            ->map(function (string $key) use ($catalogue) {
                if (! $catalogue->has($key)) {
                    throw new \InvalidArgumentException(sprintf('Scénario NutriMind inconnu : %s.', $key));
                }

                return $catalogue->get($key);
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function summary(array $result): array
    {
        return [
            'score' => $result['score'],
            'severity' => $result['severity'],
            'critical_override' => $result['critical_override'],
            'data_completeness' => $result['data_completeness'],
            'counts' => $result['counts'],
        ];
    }

    /**
     * @param  array<string, mixed>  $baseline
     * @param  array<string, mixed>  $simulated
     * @return array<string, mixed>
     */
    private function delta(array $baseline, array $simulated): array
    {
        return [
            'score' => round((float) $simulated['score'] - (float) $baseline['score'], 2),
            'severity_changed' => $baseline['severity'] !== $simulated['severity'],
            'from' => $baseline['severity'],
            'to' => $simulated['severity'],
        ];
    }

    /**
     * @param  array<string, mixed>  $baseline
     * @param  array<string, mixed>  $simulated
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function compareFindings(array $baseline, array $simulated): array
    {
        $baseByKey = collect($baseline['findings'])->keyBy(fn (array $finding) => $this->keyOf($finding));
        $simByKey = collect($simulated['findings'])->keyBy(fn (array $finding) => $this->keyOf($finding));

        return [
            // Constatations levées par le scénario (présentes, puis absentes).
            'resolved' => $baseByKey->diffKeys($simByKey)->values()->all(),
            // Constatations qui subsistent dans les deux évaluations.
            'remaining' => $baseByKey->intersectByKeys($simByKey)->values()->all(),
            // Constatations nouvelles introduites par le scénario.
            'new' => $simByKey->diffKeys($baseByKey)->values()->all(),
        ];
    }

    private function keyOf(array $finding): string
    {
        return $finding['key']
            ?? ($finding['rule'].'|'.$finding['source_type'].'|'.$finding['source_id']);
    }

    private function labelOf(Model $entity): string
    {
        return match (true) {
            $entity instanceof Product => (string) $entity->name,
            $entity instanceof Batch => (string) $entity->lot_number,
            $entity instanceof User => (string) ($entity->organization_name ?: $entity->name),
            default => $entity::class.' #'.$entity->getKey(),
        };
    }
}
