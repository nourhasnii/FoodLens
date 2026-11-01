<?php

namespace App\Services\NutriMind;

use App\Models\Batch;
use App\Models\Certification;
use App\Models\OcrCheck;
use App\Models\Product;
use App\Models\QualityCheck;
use App\Models\TransportCondition;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * NutriMind — règles de risque métier (déterministes et configurables).
 *
 * Chaque constatation s'appuie sur une donnée réelle existante et porte une clé
 * déterministe `règle:portée:id`. Une même cause racine n'est jamais comptée deux
 * fois : plusieurs certifications expirées d'un même produit ne produisent qu'une
 * constatation (la plus grave / la plus proche de l'expiration).
 *
 * Deux natures de constatation :
 *  - `kind = risk`    : risque confirmé (entre dans le score) ;
 *  - `kind = missing` : information manquante (signalée séparément, hors score).
 */
class RiskRules
{
    public function supports(Model $entity): bool
    {
        return $entity instanceof Product
            || $entity instanceof Batch
            || $entity instanceof User;
    }

    /**
     * Analyse une entité et renvoie ses constatations et la présence des données attendues.
     *
     * @param  array<string, mixed>  $context  Données préchargées (ex. pire contrôle qualité)
     *                                         pour éviter les requêtes N+1 sur les vues de liste.
     * @return array{findings: array<string, array<string, mixed>>, checks: array<string, bool>}
     */
    public function analyse(Model $entity, array $context = []): array
    {
        return match (true) {
            $entity instanceof Product => $this->analyseProduct($entity, $context),
            $entity instanceof Batch => $this->analyseBatch($entity),
            $entity instanceof User => $this->analyseSupplier($entity),
            default => throw new InvalidArgumentException(sprintf(
                'NutriMind ne sait pas évaluer les entités de type %s.',
                $entity::class
            )),
        };
    }

    // ------------------------------------------------------------------
    // Produit
    // ------------------------------------------------------------------

    /**
     * @param  array{quality?: QualityCheck|null, ocr?: OcrCheck|null, batches?: Collection<int, Batch>}  $context
     * @return array{findings: array<string, array<string, mixed>>, checks: array<string, bool>}
     */
    public function analyseProduct(Product $product, array $context = []): array
    {
        $findings = [];
        $checks = [];

        $certifications = $product->relationLoaded('certifications')
            ? $product->certifications
            : $product->certifications()->get();

        $this->certificationFindings($product, $certifications, $findings, $checks);

        // Produit non vérifié (information manquante).
        $checks['verification'] = $product->verification_status === 'verified';
        if (! $checks['verification']) {
            $this->push($findings, 'product_unverified:'.Product::class.':'.$product->getKey(),
                $this->finding('product_unverified', $product,
                    'Le produit n’a pas encore été vérifié par la plateforme (statut : '
                    .($product->verification_status ?: 'inconnu').').', 0.0));
        }

        // Contrôle qualité (le plus défavorable).
        $quality = array_key_exists('quality', $context)
            ? $context['quality']
            : $this->worstQualityCheck('product_id', $product->getKey());

        $checks['quality'] = $quality !== null;
        if ($quality) {
            $finding = $this->qualityFinding($quality);
            if ($finding) {
                $this->push($findings, 'quality_failed:'.Product::class.':'.$product->getKey(), $finding);
            }
        } else {
            $this->push($findings, 'missing_quality_check:'.Product::class.':'.$product->getKey(),
                $this->finding('missing_quality_check', $product,
                    'Aucun contrôle qualité n’a été réalisé : la conformité sanitaire reste à établir.', 0.0));
        }

        // Contrôle OCR (le plus incohérent).
        $ocr = array_key_exists('ocr', $context)
            ? $context['ocr']
            : $this->worstOcrCheck('product_id', $product->getKey());

        $checks['ocr'] = $ocr !== null;
        if ($ocr) {
            $finding = $this->ocrFinding($ocr);
            if ($finding) {
                $this->push($findings, 'ocr_inconsistency:'.Product::class.':'.$product->getKey(), $finding);
            }
        } else {
            $this->push($findings, 'missing_ocr_check:'.Product::class.':'.$product->getKey(),
                $this->finding('missing_ocr_check', $product,
                    'Aucun contrôle OCR n’a été réalisé sur les étiquettes de ce produit.', 0.0));
        }

        // Lots rappelés et périmés (un événement par lot).
        $batches = $context['batches']
            ?? ($product->relationLoaded('batches') ? $product->batches : $product->batches()->get());

        foreach ($batches as $batch) {
            $this->batchStateFindings($batch, $findings);
        }

        return ['findings' => $findings, 'checks' => $checks];
    }

    // ------------------------------------------------------------------
    // Lot
    // ------------------------------------------------------------------

    /** @return array{findings: array<string, array<string, mixed>>, checks: array<string, bool>} */
    public function analyseBatch(Batch $batch): array
    {
        $findings = [];
        $checks = [];

        $this->batchStateFindings($batch, $findings);
        $checks['freshness'] = $batch->status !== 'expired';

        // Certifications du produit parent (portée produit : une seule constatation).
        $product = $batch->relationLoaded('product')
            ? $batch->product
            : $batch->product()->first();

        if ($product) {
            $certifications = $product->relationLoaded('certifications')
                ? $product->certifications
                : $product->certifications()->get();

            $this->certificationFindings($product, $certifications, $findings, $checks);
        }

        // Contrôle qualité du lot.
        $quality = $this->worstQualityCheck('batch_id', $batch->getKey());
        $checks['quality'] = $quality !== null;
        if ($quality) {
            $finding = $this->qualityFinding($quality);
            if ($finding) {
                $this->push($findings, 'quality_failed:'.Batch::class.':'.$batch->getKey(), $finding);
            }
        } else {
            $this->push($findings, 'missing_quality_check:'.Batch::class.':'.$batch->getKey(),
                $this->finding('missing_quality_check', $batch,
                    'Aucun contrôle qualité n’a été réalisé sur ce lot.', 0.0));
        }

        // Contrôle OCR du lot.
        $ocr = $this->worstOcrCheck('batch_id', $batch->getKey());
        $checks['ocr'] = $ocr !== null;
        if ($ocr) {
            $finding = $this->ocrFinding($ocr);
            if ($finding) {
                $this->push($findings, 'ocr_inconsistency:'.Batch::class.':'.$batch->getKey(), $finding);
            }
        } else {
            $this->push($findings, 'missing_ocr_check:'.Batch::class.':'.$batch->getKey(),
                $this->finding('missing_ocr_check', $batch,
                    'Aucun contrôle OCR n’a été réalisé sur les étiquettes de ce lot.', 0.0));
        }

        // Chaîne du froid.
        $conditions = TransportCondition::where('batch_id', $batch->getKey())->get();
        $checks['cold_chain'] = $conditions->isNotEmpty();
        $coldChain = $this->coldChainFinding($batch, $conditions);
        if ($coldChain) {
            $this->push($findings, 'cold_chain:'.Batch::class.':'.$batch->getKey(), $coldChain);
        }

        return ['findings' => $findings, 'checks' => $checks];
    }

    // ------------------------------------------------------------------
    // Fournisseur (utilisateur producteur)
    // ------------------------------------------------------------------

    /** @return array{findings: array<string, array<string, mixed>>, checks: array<string, bool>} */
    public function analyseSupplier(User $supplier): array
    {
        $products = Product::where('producer_id', $supplier->getKey())
            ->with(['certifications', 'batches'])
            ->get();

        $findings = [];
        $checks = [];

        // Vérification agrégée : coût en requêtes borné (pas de N+1).
        $productIds = $products->pluck('id');

        $qualityByProduct = QualityCheck::whereIn('product_id', $productIds)
            ->where('status', 'completed')->whereNotNull('quality_score')
            ->orderBy('quality_score')
            ->get()->groupBy('product_id')->map->first();

        $ocrByProduct = OcrCheck::whereIn('product_id', $productIds)
            ->where('status', 'completed')->whereNotNull('inconsistency_score')
            ->orderByDesc('inconsistency_score')
            ->get()->groupBy('product_id')->map->first();

        foreach ($products as $product) {
            $analysis = $this->analyseProduct($product, [
                'quality' => $qualityByProduct->get($product->getKey()),
                'ocr' => $ocrByProduct->get($product->getKey()),
                'batches' => $product->batches,
            ]);

            $findings += $analysis['findings'];
            $checks = $checks + $analysis['checks'];
        }

        $checks['products'] = $products->isNotEmpty();

        return ['findings' => $findings, 'checks' => $checks];
    }

    // ------------------------------------------------------------------
    // Détecteurs élémentaires
    // ------------------------------------------------------------------

    /**
     * Pire état de certification d'un produit (une seule constatation par produit).
     *
     * @param  Collection<int, Certification>  $certifications
     * @param  array<string, array<string, mixed>>  $findings
     * @param  array<string, bool>  $checks
     */
    private function certificationFindings(Product $product, Collection $certifications, array &$findings, array &$checks): void
    {
        $nonPending = $certifications->filter(
            fn (Certification $certification) => $certification->status !== Certification::STATUS_PENDING
        );

        $checks['certification'] = $nonPending->isNotEmpty();

        if ($nonPending->isEmpty()) {
            $this->push($findings, 'missing_certification:'.Product::class.':'.$product->getKey(),
                $this->finding('missing_certification', $product,
                    'Aucune certification active n’est enregistrée : la conformité ne peut pas être démontrée.', 0.0));

            return;
        }

        $expired = $nonPending
            ->filter(fn (Certification $c) => $c->effective_status === Certification::STATUS_EXPIRED)
            ->sortBy(fn (Certification $c) => $c->expires_at?->timestamp ?? 0)
            ->first();

        if ($expired) {
            $weight = (float) config('nutrimind.rules.certification_expired.weight');

            $this->push($findings, 'certification_expired:'.Product::class.':'.$product->getKey(),
                $this->finding('certification_expired', $expired,
                    sprintf('La certification « %s » a expiré le %s.',
                        $expired->name, optional($expired->expires_at)->format('d/m/Y')),
                    $weight));

            return;
        }

        $expiring = $nonPending
            ->filter(fn (Certification $c) => $c->effective_status === Certification::STATUS_EXPIRING)
            ->sortBy(fn (Certification $c) => $c->expires_at?->timestamp ?? 0)
            ->first();

        if ($expiring) {
            $cfg = config('nutrimind.rules.certification_expiring');
            $window = max(1, (int) $cfg['window_days']);
            $days = (int) floor(($expiring->expires_at->startOfDay()->timestamp - today()->timestamp) / 86400);
            $ratio = $this->clamp(($window - $days) / $window, 0.1, 1.0);

            $this->push($findings, 'certification_expiring:'.Product::class.':'.$product->getKey(),
                $this->finding('certification_expiring', $expiring,
                    sprintf('La certification « %s » expire dans %d jour(s) (le %s).',
                        $expiring->name, max(0, $days), optional($expiring->expires_at)->format('d/m/Y')),
                    (float) $cfg['weight'] * $ratio));
        }
    }

    /** @param  array<string, array<string, mixed>>  $findings */
    private function batchStateFindings(Batch $batch, array &$findings): void
    {
        if ($batch->status === 'recalled') {
            $weight = (float) config('nutrimind.rules.recall.weight');

            $this->push($findings, 'recall:'.Batch::class.':'.$batch->getKey(),
                $this->finding('recall', $batch,
                    sprintf('Le lot %s est au statut « rappelé » : incident de sécurité confirmé.', $batch->lot_number),
                    $weight));
        }

        $expiredByDate = $batch->expiration_date !== null && $batch->expiration_date->isPast();

        if ($batch->status === 'expired' || $expiredByDate) {
            $weight = (float) config('nutrimind.rules.batch_expired.weight');

            $this->push($findings, 'batch_expired:'.Batch::class.':'.$batch->getKey(),
                $this->finding('batch_expired', $batch,
                    sprintf('Le lot %s a dépassé sa date limite (%s).',
                        $batch->lot_number,
                        $batch->expiration_date ? $batch->expiration_date->format('d/m/Y') : 'date non renseignée'),
                    $weight));
        }
    }

    private function qualityFinding(QualityCheck $check): ?array
    {
        if ($check->quality_score === null) {
            return null;
        }

        $cfg = config('nutrimind.rules.quality_failed');
        $score = (float) $check->quality_score;

        if ($score >= (float) $cfg['threshold']) {
            return null;
        }

        $ratio = $this->clamp((((float) $cfg['threshold']) - $score) / (float) $cfg['threshold'], 0.0, 1.0);
        $critical = $score < (float) $cfg['critical_threshold'] || $check->decision === 'withdraw';

        $finding = $this->finding('quality_failed', $check,
            sprintf('Le contrôle qualité obtient %.0f/100 (décision : %s).',
                $score, $check->decision ?: 'non définie'),
            (float) $cfg['weight'] * $ratio);

        if ($critical) {
            $finding['severity'] = 'critical';
        }

        return $finding;
    }

    private function ocrFinding(OcrCheck $check): ?array
    {
        if ($check->inconsistency_score === null) {
            return null;
        }

        $cfg = config('nutrimind.rules.ocr_inconsistency');
        $score = (float) $check->inconsistency_score;
        $hasCriticalField = collect($check->mismatches ?? [])
            ->contains(fn ($mismatch) => ($mismatch['severity'] ?? null) === 'critical');

        if ($score < (float) $cfg['threshold'] && ! $hasCriticalField) {
            return null;
        }

        $ratio = max($score / 100, $hasCriticalField ? 0.6 : 0.0);

        $finding = $this->finding('ocr_inconsistency', $check,
            sprintf('Le contrôle OCR présente un score d’incohérence de %.0f/100 entre l’étiquette et les données déclarées.', $score),
            (float) $cfg['weight'] * $ratio);

        if ($score >= (float) $cfg['critical_threshold'] || $hasCriticalField) {
            $finding['severity'] = 'critical';
        }

        return $finding;
    }

    /** @param  Collection<int, TransportCondition>  $conditions */
    private function coldChainFinding(Batch $batch, Collection $conditions): ?array
    {
        $total = $conditions->count();

        if ($total === 0) {
            return null;
        }

        $min = TransportCondition::TEMPERATURE_MIN;
        $max = TransportCondition::TEMPERATURE_MAX;

        $outOfRange = $conditions->filter(function (TransportCondition $condition) use ($min, $max) {
            if ($condition->temperature === null) {
                return false;
            }

            $temperature = (float) $condition->temperature;

            return $temperature < $min || $temperature > $max;
        })->count();

        if ($outOfRange === 0) {
            return null;
        }

        $cfg = config('nutrimind.rules.cold_chain');
        $ratio = $this->clamp($outOfRange / $total, (float) ($cfg['min_ratio'] ?? 0.3), 1.0);

        return $this->finding('cold_chain', $batch,
            sprintf('%d relevé(s) sur %d hors plage autorisée (%.1f–%.1f °C).', $outOfRange, $total, $min, $max),
            (float) $cfg['weight'] * $ratio);
    }

    private function worstQualityCheck(string $column, int $id): ?QualityCheck
    {
        return QualityCheck::where($column, $id)
            ->where('status', 'completed')
            ->whereNotNull('quality_score')
            ->orderBy('quality_score')
            ->first();
    }

    private function worstOcrCheck(string $column, int $id): ?OcrCheck
    {
        return OcrCheck::where($column, $id)
            ->where('status', 'completed')
            ->whereNotNull('inconsistency_score')
            ->orderByDesc('inconsistency_score')
            ->first();
    }

    // ------------------------------------------------------------------
    // Scénarios hypothétiques (« what-if »)
    // ------------------------------------------------------------------

    /**
     * Injecte des constatations hypothétiques sans jamais toucher aux données réelles.
     *
     * La clé de portée est alignée sur celle des règles réelles (`scope`), afin
     * qu'un scénario qui recouvre une règle déjà déclenchée ne soit pas compté deux fois.
     *
     * @param  array<string, array<string, mixed>>  $findings
     * @param  array<int, array<string, mixed>>  $scenarios
     * @return array<string, array<string, mixed>>
     */
    public function applyScenarios(array $findings, Model $entity, array $scenarios): array
    {
        foreach ($scenarios as $scenario) {
            $rule = $scenario['rule'];
            $config = config('nutrimind.rules.'.$rule);

            [$scopeType, $scopeId] = $this->scenarioScope($entity, $scenario['scope'] ?? 'entity');

            $this->push($findings, $rule.':'.$scopeType.':'.$scopeId, [
                'rule' => $rule,
                'label' => $config['label'],
                'category' => $config['category'],
                'kind' => 'risk',
                'severity' => $scenario['severity'] ?? $config['severity'],
                'critical_override' => (bool) ($config['critical_override'] ?? false),
                'source_type' => $entity->getMorphClass(),
                'source_id' => $entity->getKey(),
                'source_label' => $this->labelOf($entity),
                'reason' => $scenario['reason'] ?? 'Scénario hypothétique.',
                'contribution' => round((float) ($scenario['contribution'] ?? ($config['weight'] ?? 0)), 2),
                'hypothetical' => true,
                'scenario' => $scenario['key'] ?? null,
            ]);
        }

        return $findings;
    }

    /** @return array{0: class-string, 1: int} Portée d'une constatation simulée. */
    private function scenarioScope(Model $entity, string $scope): array
    {
        return match ($scope) {
            'product' => $entity instanceof Batch
                ? [Product::class, (int) $entity->getAttribute('product_id')]
                : [$entity instanceof Product ? Product::class : $entity->getMorphClass(), $entity->getKey()],

            'batch' => $entity instanceof Batch
                ? [Batch::class, $entity->getKey()]
                : [$entity instanceof Product ? Product::class : $entity->getMorphClass(), $entity->getKey()],

            default => [$entity->getMorphClass(), $entity->getKey()],
        };
    }

    // ------------------------------------------------------------------
    // Fabrique de constatation
    // ------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function finding(string $rule, Model $source, string $reason, float $contribution, array $extra = []): array
    {
        $cfg = config('nutrimind.rules.'.$rule);

        return array_merge([
            'rule' => $rule,
            'label' => $cfg['label'],
            'category' => $cfg['category'],
            'kind' => $cfg['kind'] ?? 'risk',
            'severity' => $cfg['severity'],
            'critical_override' => (bool) ($cfg['critical_override'] ?? false),
            'source_type' => $source->getMorphClass(),
            'source_id' => $source->getKey(),
            'source_label' => $this->labelOf($source),
            'reason' => $reason,
            'contribution' => round($contribution, 2),
        ], $extra);
    }

    /**
     * Enregistre une constatation en dédupliquant par clé (pas de double comptage).
     *
     * @param  array<string, array<string, mixed>>  $findings
     * @param  array<string, mixed>  $finding
     */
    private function push(array &$findings, string $key, array $finding): void
    {
        if (! isset($findings[$key]) || $finding['contribution'] > $findings[$key]['contribution']) {
            // La clé de déduplication est conservée dans la constatation : elle permet
            // de comparer deux évaluations (réelle / simulée) sans ambiguïté.
            $findings[$key] = ['key' => $key] + $finding;
        }
    }

    private function labelOf(Model $model): string
    {
        return match (true) {
            $model instanceof Product => (string) $model->name,
            $model instanceof Batch => (string) $model->lot_number,
            $model instanceof Certification => (string) $model->name,
            $model instanceof User => (string) $model->name,
            $model instanceof QualityCheck => 'Contrôle qualité #'.$model->getKey(),
            $model instanceof OcrCheck => 'Contrôle OCR #'.$model->getKey(),
            default => $model::class.' #'.$model->getKey(),
        };
    }

    private function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }
}
