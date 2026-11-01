<?php

namespace App\Services\NutriMind;

use App\Models\Alert;
use App\Models\Batch;
use App\Models\Category;
use App\Models\Certification;
use App\Models\OcrCheck;
use App\Models\Product;
use App\Models\QualityCheck;
use App\Models\TraceabilityEvent;
use App\Models\TransportCondition;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * NutriMind — graphe d'impact (différenciateur principal).
 *
 * À partir d'une entité à risque, explore les relations Eloquent existantes pour
 * identifier les fournisseurs, lots, produits, certifications et incidents
 * potentiellement affectés, en dépendance directe ou indirecte.
 *
 * Principes :
 *  - exploration bornée (profondeur et nombre de nœuds maximaux) ;
 *  - protection contre les cycles (un nœud n'est visité qu'une fois) ;
 *  - aucune base de graphe : uniquement les relations réelles du modèle ;
 *  - chaque entité indique son chemin de relation et sa preuve (signal détecté) ;
 *  - distinction entre impact confirmé, potentiel et incertain (données absentes).
 */
class ImpactAnalysisService
{
    /**
     * @return array<string, mixed>
     */
    public function analyze(Model $origin, ?int $maxDepth = null, ?int $maxNodes = null): array
    {
        if (! $this->supports($origin)) {
            throw new \InvalidArgumentException(sprintf(
                'NutriMind ne sait pas explorer le graphe depuis les entités de type %s.',
                $origin::class
            ));
        }

        $config = config('nutrimind.impact');
        $maxDepth ??= (int) $config['max_depth'];
        $maxNodes ??= (int) $config['max_nodes'];

        $originKey = $this->key($origin);

        $nodes = [$originKey => $this->describe($origin, 0)];
        $paths = [$originKey => []];
        $edges = [];
        $queue = [[$origin, 0]];
        $truncated = false;

        while ($queue !== []) {
            [$current, $depth] = array_shift($queue);

            if ($depth >= $maxDepth) {
                continue;
            }

            $currentKey = $this->key($current);

            foreach ($this->neighbours($current) as $neighbour) {
                $child = $neighbour['node'];
                $childKey = $this->key($child);
                $visited = isset($nodes[$childKey]);

                $edges[] = [
                    'from' => $currentKey,
                    'to' => $childKey,
                    'relation' => $neighbour['relation'],
                    'depth' => $depth + 1,
                    'dependency' => ($depth + 1) === 1 ? 'direct' : 'indirect',
                    'cycle' => $visited,
                ];

                if ($visited) {
                    continue;
                }

                if (count($nodes) >= $maxNodes) {
                    $truncated = true;
                    continue;
                }

                $nodes[$childKey] = $this->describe($child, $depth + 1);
                $paths[$childKey] = array_merge($paths[$currentKey], [[
                    'relation' => $neighbour['relation'],
                    'from' => $nodes[$currentKey]['label'],
                    'to' => $nodes[$childKey]['label'],
                ]]);

                $queue[] = [$child, $depth + 1];
            }
        }

        foreach ($nodes as $key => $node) {
            $nodes[$key]['path'] = $paths[$key];
        }

        $summary = ['confirmed' => 0, 'potential' => 0, 'unknown' => 0];
        foreach ($nodes as $node) {
            $summary[$node['impact']] = ($summary[$node['impact']] ?? 0) + 1;
        }

        return [
            'origin' => $originKey,
            'nodes' => array_values($nodes),
            'edges' => $edges,
            'summary' => $summary,
            'bounds' => [
                'max_depth' => $maxDepth,
                'max_nodes' => $maxNodes,
                'truncated' => $truncated,
            ],
        ];
    }

    public function supports(Model $entity): bool
    {
        return $entity instanceof Product
            || $entity instanceof Batch
            || $entity instanceof User;
    }

    // ------------------------------------------------------------------
    // Exploration
    // ------------------------------------------------------------------

    /**
     * Relations sortantes réellement disponibles pour un nœud.
     *
     * @return array<int, array{relation: string, node: Model}>
     */
    private function neighbours(Model $node): array
    {
        $neighbours = [];

        $add = function (string $relation, ?Model $model = null, ?iterable $models = null) use (&$neighbours): void {
            if ($model) {
                $neighbours[] = ['relation' => $relation, 'node' => $model];
            }

            foreach ($models ?? [] as $item) {
                $neighbours[] = ['relation' => $relation, 'node' => $item];
            }
        };

        match (true) {
            $node instanceof Product => (function () use ($node, $add): void {
                $add('producer', $node->producer()->first());
                $add('categoryModel', $node->categoryModel()->first());
                $add('batches', models: $node->batches()->get());
                $add('certifications', models: $node->certifications()->get());
                $add('qualityChecks', models: $node->qualityChecks()->get());
                $add('ocrChecks', models: OcrCheck::where('product_id', $node->getKey())->get());
            })(),

            $node instanceof Batch => (function () use ($node, $add): void {
                $add('product', $node->product()->first());
                $add('events', models: $node->events()->get());
                $add('qualityChecks', models: QualityCheck::where('batch_id', $node->getKey())->get());
                $add('ocrChecks', models: OcrCheck::where('batch_id', $node->getKey())->get());
                $add('transportConditions', models: TransportCondition::where('batch_id', $node->getKey())->get());
                $add('alerts', models: Alert::where('batch_id', $node->getKey())->get());
            })(),

            $node instanceof User => (function () use ($node, $add): void {
                $add('products', models: Product::where('producer_id', $node->getKey())->get());
            })(),

            $node instanceof Certification => (function () use ($node, $add): void {
                $add('product', $node->product()->first());
                $add('alerts', models: Alert::where('alertable_type', $node->getMorphClass())
                    ->where('alertable_id', $node->getKey())->get());
            })(),

            $node instanceof QualityCheck => (function () use ($node, $add): void {
                $add('product', $node->product()->first());
                $add('batch', $node->batch()->first());
            })(),

            $node instanceof OcrCheck => (function () use ($node, $add): void {
                $add('product', $node->product()->first());
                $add('batch', $node->batch()->first());
            })(),

            $node instanceof TransportCondition => (function () use ($node, $add): void {
                $add('batch', $node->batch()->first());
                $add('traceabilityEvent', $node->traceabilityEvent()->first());
                $add('alerts', models: $node->alerts()->get());
            })(),

            $node instanceof TraceabilityEvent => (function () use ($node, $add): void {
                $add('batch', $node->batch()->first());
                $add('actor', $node->actor()->first());
            })(),

            $node instanceof Alert => (function () use ($node, $add): void {
                $add('batch', $node->batch()->first());
                $add('alertable', $node->alertable()->first());
            })(),

            default => null,
        };

        return array_values(array_filter($neighbours, fn (array $entry) => $entry['node'] !== null));
    }

    // ------------------------------------------------------------------
    // Description et classification
    // ------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private function describe(Model $node, int $depth): array
    {
        [$impact, $signal] = $this->impactOf($node);

        return [
            'key' => $this->key($node),
            'type' => $node->getMorphClass(),
            'id' => $node->getKey(),
            'label' => $this->labelOf($node),
            'kind' => $this->kindOf($node),
            'depth' => $depth,
            'impact' => $impact,
            'signal' => $signal,
        ];
    }

    /**
     * @return array{0: string, 1: array<string, string>|null}
     */
    private function impactOf(Model $node): array
    {
        $signal = $this->signal($node);

        if ($signal) {
            return ['confirmed', $signal];
        }

        return [$this->hasData($node) ? 'potential' : 'unknown', null];
    }

    /**
     * Signal de risque confirmé porté par le nœud lui-même (preuve).
     *
     * @return array{label: string, severity: string}|null
     */
    private function signal(Model $node): ?array
    {
        return match (true) {
            $node instanceof Batch => match (true) {
                $node->status === 'recalled' => ['label' => 'Lot rappelé', 'severity' => 'critical'],
                $node->status === 'expired'
                    || ($node->expiration_date !== null && $node->expiration_date->isPast())
                    => ['label' => 'DLC dépassée', 'severity' => 'warning'],
                default => null,
            },

            $node instanceof Certification => match ($node->effective_status) {
                Certification::STATUS_EXPIRED => ['label' => 'Certification expirée', 'severity' => 'critical'],
                Certification::STATUS_EXPIRING => ['label' => 'Certification proche de l’expiration', 'severity' => 'warning'],
                default => null,
            },

            $node instanceof QualityCheck => match (true) {
                $node->decision === 'withdraw' => ['label' => 'Contrôle qualité : retrait', 'severity' => 'critical'],
                $node->quality_score !== null && (float) $node->quality_score < 50
                    => ['label' => 'Score qualité faible', 'severity' => 'warning'],
                default => null,
            },

            $node instanceof OcrCheck => match (true) {
                (float) ($node->inconsistency_score ?? 0) >= 70 => ['label' => 'Incohérence OCR critique', 'severity' => 'critical'],
                (float) ($node->inconsistency_score ?? 0) >= 40 => ['label' => 'Incohérence OCR', 'severity' => 'warning'],
                default => null,
            },

            $node instanceof TransportCondition => $this->isOutOfRange($node)
                ? ['label' => 'Température hors plage', 'severity' => 'warning']
                : null,

            $node instanceof Alert => $node->status === 'open' && in_array($node->severity, ['warning', 'critical'], true)
                ? ['label' => 'Alerte '.$node->severity, 'severity' => $node->severity]
                : null,

            default => null,
        };
    }

    private function isOutOfRange(TransportCondition $condition): bool
    {
        if ($condition->temperature === null) {
            return false;
        }

        $temperature = (float) $condition->temperature;

        return $temperature < TransportCondition::TEMPERATURE_MIN || $temperature > TransportCondition::TEMPERATURE_MAX;
    }

    /** Le nœud possède-t-il des données permettant de conclure à une absence de risque ? */
    private function hasData(Model $node): bool
    {
        return match (true) {
            $node instanceof Product => $node->batches()->exists()
                || $node->certifications()->exists()
                || $node->qualityChecks()->exists()
                || OcrCheck::where('product_id', $node->getKey())->exists(),

            $node instanceof Batch => $node->events()->exists()
                || QualityCheck::where('batch_id', $node->getKey())->exists()
                || OcrCheck::where('batch_id', $node->getKey())->exists()
                || TransportCondition::where('batch_id', $node->getKey())->exists(),

            $node instanceof User => Product::where('producer_id', $node->getKey())->exists(),

            default => true,
        };
    }

    private function key(Model $node): string
    {
        return $node->getMorphClass().':'.$node->getKey();
    }

    private function kindOf(Model $node): string
    {
        return match (true) {
            $node instanceof Product => 'produit',
            $node instanceof Batch => 'lot',
            $node instanceof User => 'fournisseur',
            $node instanceof Certification => 'certification',
            $node instanceof QualityCheck => 'contrôle qualité',
            $node instanceof OcrCheck => 'contrôle OCR',
            $node instanceof TransportCondition => 'relevé de température',
            $node instanceof TraceabilityEvent => 'événement de traçabilité',
            $node instanceof Alert => 'alerte',
            $node instanceof Category => 'catégorie',
            default => class_basename($node),
        };
    }

    private function labelOf(Model $node): string
    {
        return match (true) {
            $node instanceof Product => (string) $node->name,
            $node instanceof Batch => (string) $node->lot_number,
            $node instanceof User => (string) ($node->organization_name ?: $node->name),
            $node instanceof Certification => (string) $node->name,
            $node instanceof QualityCheck => 'Contrôle qualité #'.$node->getKey(),
            $node instanceof OcrCheck => 'Contrôle OCR #'.$node->getKey(),
            $node instanceof TransportCondition => 'Relevé #'.$node->getKey(),
            $node instanceof TraceabilityEvent => 'Événement '.$node->event_type.' #'.$node->getKey(),
            $node instanceof Alert => (string) ($node->title ?: 'Alerte #'.$node->getKey()),
            $node instanceof Category => (string) $node->name,
            default => class_basename($node).' #'.$node->getKey(),
        };
    }
}
