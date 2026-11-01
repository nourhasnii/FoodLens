<?php

namespace App\Services\Optimization;

use App\Enums\RecommendationStatus;
use App\Enums\StockMovementType;
use App\Events\BatchDestinationValidated;
use App\Models\Batch;
use App\Models\OptimizationRecommendation;
use App\Models\Site;
use App\Models\User;
use App\Services\Forecast\DemandForecaster;
use App\Services\Stock\StockService;
use App\Support\BatchAttributes;
use App\Support\Fmt;
use App\Support\Geo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Moteur de recommandation de destination d'un lot.
 *
 * Méthode : score multicritère pondéré (0-100) après filtrage par contraintes bloquantes.
 *  1. Contraintes : coordonnées GPS connues, capacité libre suffisante, arrivée avant la DLC.
 *  2. Cinq critères notés de 0 à 100 : distance, capacité, écoulement avant DLC, CO₂, demande.
 *  3. Score final = Σ poids × note ; les poids DLC et distance sont renforcés si le lot est urgent.
 * Chaque note est accompagnée de sa justification : le résultat est entièrement explicable.
 */
class OptimizationEngine
{
    public const CRITERIA = [
        'demand' => 'Demande du site',
        'expiry' => 'Écoulement avant DLC',
        'distance' => 'Proximité',
        'capacity' => 'Capacité disponible',
        'co2' => 'Impact CO₂',
    ];

    public function __construct(
        private readonly StockService $stocks,
        private readonly DemandForecaster $forecaster,
    ) {}

    public function recommend(Batch $batch, Site $source, float $quantity, ?User $user = null): OptimizationRecommendation
    {
        $cfg = config('stock.optimization');
        $quantity = round($quantity, 2);

        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => 'La quantité doit être supérieure à zéro.']);
        }

        if (BatchAttributes::isBlocked($batch)) {
            throw ValidationException::withMessages(['batch_id' => 'Ce lot est rappelé : il ne peut pas être redistribué.']);
        }

        $daysLeft = BatchAttributes::daysToEffectiveExpiry($batch); // DLC réelle (chaîne du froid)

        if ($daysLeft !== null && $daysLeft < 0) {
            $printedLeft = BatchAttributes::daysToExpiry($batch);
            throw ValidationException::withMessages([
                'batch_id' => $printedLeft !== null && $printedLeft >= 0
                    ? 'La DLC réelle de ce lot est dépassée (chaîne du froid interrompue, voir « DLC dynamique ») : faites un contrôle qualité avant toute redistribution.'
                    : 'Ce lot a dépassé sa DLC : enregistrez une sortie (destruction) au lieu de le redistribuer.',
            ]);
        }

        if (! $source->hasCoordinates()) {
            throw ValidationException::withMessages([
                'source_site_id' => 'Renseignez la latitude et la longitude du site d’origine pour calculer les distances.',
            ]);
        }

        [$weights, $weightNotes] = $this->weightsFor($daysLeft, $cfg);
        $tonnes = $quantity * BatchAttributes::unitWeightKg($batch->product) / 1000;

        $candidates = Site::query()
            ->active()
            ->logistics()
            ->whereKeyNot($source->getKey())
            ->withSum('stocks', 'quantity')
            ->orderBy('name')
            ->get()
            ->map(fn (Site $site) => $this->evaluate($site, $source, $batch, $quantity, $tonnes, $daysLeft, $cfg))
            ->all();

        $candidates = $this->scoreCo2($candidates);

        foreach ($candidates as &$candidate) {
            $weighted = 0.0;
            foreach ($weights as $criterion => $weight) {
                $weighted += $weight * $candidate['scores'][$criterion];
            }
            $candidate['total'] = $candidate['feasible'] ? round($weighted, 1) : 0.0;
        }
        unset($candidate);

        usort($candidates, fn (array $a, array $b) => [$b['feasible'], $b['total']] <=> [$a['feasible'], $a['total']]);

        $feasible = array_values(array_filter($candidates, fn (array $c) => $c['feasible']));
        $best = $feasible[0] ?? null;

        // DLC dynamique : le calcul utilise la DLC réelle prédite d'après les températures du transport
        $printedExpiry = BatchAttributes::expiryDate($batch);
        $effectiveExpiry = BatchAttributes::effectiveExpiryDate($batch);
        if ($printedExpiry && $effectiveExpiry && $effectiveExpiry->lt($printedExpiry)) {
            $weightNotes[] = sprintf(
                'DLC réelle prédite le %s au lieu du %s (%d jour(s) de vie perdus pendant le transport) : c’est cette date qui est utilisée.',
                $effectiveExpiry->format('d/m/Y'), $printedExpiry->format('d/m/Y'), (int) abs($effectiveExpiry->diffInDays($printedExpiry))
            );
        }

        $sourceLine = $this->stocks->findLine($source->getKey(), (int) $batch->product_id, $batch->getKey());
        if ((float) ($sourceLine?->quantity ?? 0) < $quantity) {
            $weightNotes[] = sprintf(
                'Le site d’origine ne détient que %s unité(s) de ce lot : le transfert ne pourra être enregistré qu’avec un stock suffisant.',
                Fmt::q($sourceLine?->quantity ?? 0)
            );
        }

        return OptimizationRecommendation::create([
            'batch_id' => $batch->getKey(),
            'product_id' => $batch->product_id,
            'source_site_id' => $source->getKey(),
            'recommended_site_id' => $best['site_id'] ?? null,
            'quantity' => $quantity,
            'score' => $best['total'] ?? 0,
            'distance_km' => $best['metrics']['distance_km'] ?? null,
            'co2_kg' => $best['metrics']['co2_kg'] ?? null,
            'days_to_expiry' => $daysLeft,
            'weights' => $weights,
            'candidates' => $candidates,
            'explanation' => $this->explain($batch, $quantity, $best, $feasible[1] ?? null, $candidates, $weights, $weightNotes),
            'status' => RecommendationStatus::Pending,
            'requested_by' => $user?->getKey(),
        ]);
    }

    /**
     * Valide, modifie ou rejette une recommandation. En cas de validation, le transfert
     * de stock peut être enregistré automatiquement.
     */
    public function applyDecision(
        OptimizationRecommendation $recommendation,
        string $action,
        ?int $chosenSiteId,
        ?string $note,
        bool $createMovement,
        ?User $user = null,
    ): OptimizationRecommendation {
        if ($recommendation->status !== RecommendationStatus::Pending) {
            throw ValidationException::withMessages(['action' => 'Cette recommandation a déjà été traitée.']);
        }

        return DB::transaction(function () use ($recommendation, $action, $chosenSiteId, $note, $createMovement, $user) {
            $decision = [
                'decision_note' => $note,
                'decided_by' => $user?->getKey(),
                'decided_at' => now(),
            ];

            if ($action === 'reject') {
                $recommendation->update($decision + ['status' => RecommendationStatus::Rejected]);

                return $recommendation;
            }

            $targetId = $action === 'accept' ? $recommendation->recommended_site_id : $chosenSiteId;

            if (! $targetId) {
                throw ValidationException::withMessages([
                    'action' => 'Aucune destination n’a été recommandée : choisissez un site manuellement.',
                ]);
            }

            if ($action === 'modify' && (int) $targetId === (int) $recommendation->recommended_site_id) {
                throw ValidationException::withMessages([
                    'chosen_site_id' => 'Ce site est déjà la recommandation : choisissez « Valider » ou un autre site.',
                ]);
            }

            if ((int) $targetId === (int) $recommendation->source_site_id) {
                throw ValidationException::withMessages([
                    'chosen_site_id' => 'La destination doit être différente du site d’origine.',
                ]);
            }

            $movement = null;

            if ($createMovement) {
                $movement = $this->stocks->record([
                    'type' => StockMovementType::Transfer,
                    'batch_id' => $recommendation->batch_id,
                    'product_id' => $recommendation->product_id,
                    'source_site_id' => $recommendation->source_site_id,
                    'destination_site_id' => $targetId,
                    'quantity' => $recommendation->quantity,
                    'reason' => 'Optimisation #'.$recommendation->getKey(),
                    'notes' => $note,
                ], $user);
            }

            $recommendation->update($decision + [
                'status' => $action === 'accept' ? RecommendationStatus::Accepted : RecommendationStatus::Modified,
                'chosen_site_id' => $targetId,
                'stock_movement_id' => $movement?->getKey(),
            ]);

            BatchDestinationValidated::dispatch($recommendation);

            return $recommendation;
        });
    }

    private function evaluate(Site $site, Site $source, Batch $batch, float $qty, float $tonnes, ?int $daysLeft, array $cfg): array
    {
        $candidate = [
            'site_id' => $site->getKey(),
            'site_code' => $site->code,
            'site_name' => $site->name,
            'site_type' => $site->type?->label(),
            'city' => $site->city,
            'feasible' => true,
            'blocking' => [],
            'scores' => array_fill_keys(array_keys(self::CRITERIA), 0.0),
            'reasons' => [],
            'metrics' => [],
            'total' => 0.0,
        ];

        if (! $site->hasCoordinates()) {
            $candidate['feasible'] = false;
            $candidate['blocking'][] = 'Coordonnées GPS manquantes';

            return $candidate;
        }

        $productId = (int) $batch->product_id;
        $distance = Geo::haversineKm($source->latitude, $source->longitude, $site->latitude, $site->longitude) * $cfg['road_factor'];
        $transitDays = $distance / max(1, $cfg['average_speed_kmh']) / 24;
        $co2 = $distance * $tonnes * $cfg['emission_factor_kg_per_tkm'];

        $capacity = (float) $site->capacity;
        $used = $site->usedCapacity();
        $free = max(0, $capacity - $used);

        // Horizon de vente du lot sur ce site
        $horizon = $daysLeft !== null
            ? max(1, min($cfg['max_horizon_days'], (int) floor($daysLeft - $transitDays)))
            : (int) $cfg['default_horizon_days'];

        // Demande journalière : prévision (calendrier tunisien) sur la période de vente, sinon moyenne simple
        $forecasting = (bool) config('stock.forecast.enabled', true);
        $avgDaily = $forecasting
            ? $this->forecaster->averageDaily($site->getKey(), $productId, now()->addDays($transitDays), $horizon)
            : $this->stocks->averageDailyConsumption($site->getKey(), $productId, $cfg['demand_window_days']);
        $current = $this->stocks->availableAt($site->getKey(), $productId);
        $threshold = $this->stocks->thresholdFor($site->getKey(), $productId);

        // Contraintes bloquantes
        if ($capacity > 0 && $free < $qty) {
            $candidate['feasible'] = false;
            $candidate['blocking'][] = sprintf('Capacité insuffisante (%s place(s) libre(s) pour %s)', Fmt::q($free), Fmt::q($qty));
        }

        if ($daysLeft !== null && $transitDays >= $daysLeft) {
            $candidate['feasible'] = false;
            $candidate['blocking'][] = 'Le lot arriverait après sa DLC';
        }

        // Proximité : décroissance linéaire jusqu'à la distance maximale
        $maxKm = max(1, $cfg['max_distance_km']);
        $candidate['scores']['distance'] = 100 * (1 - min($distance, $maxKm) / $maxKm);
        $candidate['reasons']['distance'] = sprintf('%s km par la route, environ %s h de trajet', Fmt::n($distance), Fmt::n($transitDays * 24, 1));

        // Capacité : note maximale si une marge confortable reste libre après réception
        if ($capacity > 0) {
            $occupancyAfter = min(1, ($used + $qty) / $capacity);
            $candidate['scores']['capacity'] = 100 * $this->clamp((1 - $occupancyAfter) / max(0.01, $cfg['comfortable_free_ratio']));
            $candidate['reasons']['capacity'] = sprintf(
                'Occupation après réception : %s %% (%s sur %s)',
                Fmt::n($occupancyAfter * 100), Fmt::q($used + $qty), Fmt::q($capacity)
            );
        } else {
            $candidate['scores']['capacity'] = 50;
            $candidate['reasons']['capacity'] = 'Capacité non renseignée : critère neutre';
        }

        // Demande : besoin estimé sur l'horizon de vente du lot
        $need = max(0, $avgDaily * $horizon - $current, $threshold - $current);
        $candidate['scores']['demand'] = 100 * min(1, $need / $qty);
        $candidate['reasons']['demand'] = sprintf(
            'Besoin estimé : %s u. (%s %s u./jour sur %d jours, stock actuel %s u.)%s',
            Fmt::q(round($need)), $forecasting ? 'prévision' : 'ventes', Fmt::n($avgDaily, 1), $horizon, Fmt::q($current),
            $threshold > 0 && $current <= $threshold ? ', déjà sous le seuil de rupture' : ''
        );

        // Écoulement avant DLC : le site peut-il vendre stock actuel + lot avant la date limite ?
        if ($daysLeft === null) {
            $candidate['scores']['expiry'] = 70;
            $candidate['reasons']['expiry'] = 'DLC non renseignée : critère neutre';
        } elseif ($avgDaily <= 0) {
            $candidate['scores']['expiry'] = 15;
            $candidate['reasons']['expiry'] = $forecasting
                ? 'Aucune vente prévue pour ce produit sur ce site : risque d’invendus'
                : sprintf('Aucune vente de ce produit sur %d jours : risque d’invendus', $cfg['demand_window_days']);
        } else {
            $daysToSell = ($current + $qty) / $avgDaily;
            $remaining = max(0, $daysLeft - $transitDays);
            $candidate['scores']['expiry'] = $daysToSell <= $remaining ? 100 : 100 * $remaining / $daysToSell;
            $candidate['reasons']['expiry'] = sprintf(
                'Écoulement estimé en %s jours pour %s jours restants avant la DLC',
                Fmt::n($daysToSell, 1), Fmt::n($remaining, 1)
            );
        }

        $candidate['reasons']['co2'] = sprintf('%s kg CO₂e estimés pour %s t transportées', Fmt::n($co2, 1), Fmt::n($tonnes, 2));

        $candidate['metrics'] = [
            'distance_km' => round($distance, 1),
            'transit_hours' => round($transitDays * 24, 1),
            'co2_kg' => round($co2, 2),
            'capacity' => $capacity,
            'used' => round($used, 2),
            'free' => round($free, 2),
            'avg_daily_out' => round($avgDaily, 2),
            'current_stock' => round($current, 2),
            'threshold' => $threshold,
            'need' => round($need, 2),
            'horizon_days' => $horizon,
        ];

        foreach ($candidate['scores'] as $key => $score) {
            $candidate['scores'][$key] = round($score, 1);
        }

        return $candidate;
    }

    /** Le CO₂ est noté relativement au site faisable le moins émetteur (100 = meilleur choix). */
    private function scoreCo2(array $candidates): array
    {
        $min = collect($candidates)
            ->filter(fn (array $c) => $c['feasible'] && ($c['metrics']['co2_kg'] ?? 0) > 0)
            ->min(fn (array $c) => $c['metrics']['co2_kg']);

        foreach ($candidates as &$candidate) {
            $co2 = $candidate['metrics']['co2_kg'] ?? null;

            if ($co2 === null) {
                continue;
            }

            $candidate['scores']['co2'] = round(($co2 <= 0 || ! $min) ? 100 : 100 * min(1, $min / $co2), 1);
        }
        unset($candidate);

        return $candidates;
    }

    /** @return array{0: array<string, float>, 1: list<string>} */
    private function weightsFor(?int $daysLeft, array $cfg): array
    {
        $weights = array_intersect_key($cfg['weights'], self::CRITERIA) + array_fill_keys(array_keys(self::CRITERIA), 0);
        $notes = [];

        if ($daysLeft !== null && $daysLeft <= $cfg['urgent_expiry_days']) {
            $multiplier = $cfg['urgency_multiplier'];
            $weights['expiry'] *= $multiplier;
            $weights['distance'] *= $multiplier;
            $notes[] = sprintf(
                'Lot à %d jour(s) de sa DLC : les critères « écoulement avant DLC » et « proximité » pèsent %s fois plus.',
                $daysLeft, Fmt::n($multiplier, 1)
            );
        }

        $sum = array_sum($weights) ?: 1;

        return [array_map(fn ($w) => round($w / $sum, 4), $weights), $notes];
    }

    private function explain(Batch $batch, float $qty, ?array $best, ?array $runnerUp, array $candidates, array $weights, array $notes): array
    {
        $excluded = [];
        foreach ($candidates as $candidate) {
            if (! $candidate['feasible']) {
                $excluded[$candidate['site_name']] = $candidate['blocking'];
            }
        }

        if (! $best) {
            return [
                'summary' => 'Aucune destination ne respecte les contraintes pour ce lot.',
                'points' => array_merge($notes, [
                    'Réduisez la quantité à transférer, libérez de la capacité ou vérifiez les coordonnées des sites.',
                ]),
                'excluded' => $excluded,
            ];
        }

        $points = [];

        $contribution = [];
        foreach ($weights as $criterion => $weight) {
            $contribution[$criterion] = $weight * $best['scores'][$criterion];
        }
        arsort($contribution);
        $strengths = array_slice(array_keys($contribution), 0, 2);

        foreach ($strengths as $criterion) {
            $points[] = sprintf(
                'Point fort, %s : %s/100. %s.',
                $this->label($criterion), Fmt::n($best['scores'][$criterion]), $best['reasons'][$criterion]
            );
        }

        $weakest = array_search(min($best['scores']), $best['scores'], true);
        if ($weakest !== false && ! in_array($weakest, $strengths, true) && $best['scores'][$weakest] < 60) {
            $points[] = sprintf(
                'À surveiller, %s : %s/100. %s.',
                $this->label($weakest), Fmt::n($best['scores'][$weakest]), $best['reasons'][$weakest]
            );
        }

        if ($runnerUp) {
            $gap = $best['total'] - $runnerUp['total'];
            $advantages = [];
            foreach (self::CRITERIA as $criterion => $label) {
                $advantages[$criterion] = $runnerUp['scores'][$criterion] - $best['scores'][$criterion];
            }
            arsort($advantages);
            $runnerBest = array_key_first($advantages);

            $points[] = $advantages[$runnerBest] > 5
                ? sprintf(
                    '%s arrive en second (%s/100) : meilleur sur « %s », mais %s point(s) derrière au total.',
                    $runnerUp['site_name'], Fmt::n($runnerUp['total']), $this->label($runnerBest), Fmt::n($gap, 1)
                )
                : sprintf(
                    '%s arrive en second (%s/100), sans avantage notable sur aucun critère.',
                    $runnerUp['site_name'], Fmt::n($runnerUp['total'])
                );
        }

        return [
            'summary' => sprintf(
                'Envoyer %s unité(s) du lot %s vers %s.',
                Fmt::q($qty), BatchAttributes::code($batch), $best['site_name']
            ),
            'points' => array_merge($points, $notes),
            'excluded' => $excluded,
        ];
    }

    /** Libellé du critère en milieu de phrase : seule la première lettre passe en minuscule (DLC, CO₂ conservés). */
    private function label(string $criterion): string
    {
        $text = self::CRITERIA[$criterion];

        return mb_strtolower(mb_substr($text, 0, 1)).mb_substr($text, 1);
    }

    private function clamp(float $value, float $min = 0.0, float $max = 1.0): float
    {
        return max($min, min($max, $value));
    }
}
