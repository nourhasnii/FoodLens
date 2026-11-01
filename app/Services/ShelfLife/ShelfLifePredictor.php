<?php

namespace App\Services\ShelfLife;

use App\Models\Batch;
use App\Models\ShelfLifeAssessment;
use App\Models\TransportCondition;
use App\Support\BatchAttributes;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * DLC dynamique : prédit la vraie date limite d'un lot à partir des températures qu'il a subies.
 *
 * Microbiologie prévisionnelle — modèle de la racine carrée de Ratkowsky (1982) :
 *   √(vitesse de croissance) = b · (T − Tmin)   ⇒   vitesse ∝ (T − Tmin)²
 * Vitesse relative à la température de stockage prévue (Tref) :
 *   r(T) = ((T − Tmin) / (Tref − Tmin))²
 * Une heure à la température T « consomme » r(T) heures de durée de vie.
 * Les heures perdues = Σ (r(T) − 1) × durée, pour chaque relevé plus chaud que Tref.
 * DLC réelle = DLC imprimée − heures perdues (jamais prolongée : principe de précaution).
 *
 * Fourchette : Tmin n'est connu qu'à ± 1,5 °C près (valeurs publiées par famille de produits) ;
 * le calcul est refait avec les deux bornes → DLC au plus tôt (pessimiste) et au plus tard (optimiste).
 */
class ShelfLifePredictor
{
    public const RISK_OK = 'ok';

    public const RISK_ATTENTION = 'attention';

    public const RISK_CRITICAL = 'critical';

    public const RISK_EXPIRED = 'expired';

    public const RISK_UNMONITORED = 'unmonitored';

    public const RISK_LABELS = [
        self::RISK_OK => 'Chaîne du froid respectée',
        self::RISK_ATTENTION => 'Durée de vie réduite',
        self::RISK_CRITICAL => 'Contrôle qualité requis',
        self::RISK_EXPIRED => 'DLC imprimée dépassée',
        self::RISK_UNMONITORED => 'Pas de relevé de température',
    ];

    /** Ordre d'affichage : le plus urgent d'abord */
    public const RISK_ORDER = [self::RISK_CRITICAL => 0, self::RISK_ATTENTION => 1, self::RISK_EXPIRED => 2, self::RISK_OK => 3, self::RISK_UNMONITORED => 4];

    private array $cfg;

    public function __construct()
    {
        $this->cfg = (array) config('stock.shelf_life', []) + [
            'enabled' => true,
            'default_reading_minutes' => 60,
            'max_relative_rate' => 40,
            'attention_days' => 0.5,
            'critical_minutes' => 120,
            'profiles' => [],
        ];
    }

    public function enabled(): bool
    {
        return (bool) $this->cfg['enabled'];
    }

    /**
     * Prédiction complète pour un lot. null si le lot n'a pas de DLC imprimée.
     *
     * @param  Collection<int, TransportCondition>|null  $readings  relevés déjà chargés (sinon lus en base)
     */
    public function predict(Batch $batch, ?Collection $readings = null): ?array
    {
        $printed = BatchAttributes::expiryDate($batch);
        if (! $printed) {
            return null;
        }

        $batch->loadMissing('product');
        [$profileKey, $profile] = $this->profileFor($batch);
        $readings ??= TransportCondition::query()->where('batch_id', $batch->getKey())->orderBy('recorded_at')->get();
        $readings = $readings->sortBy('recorded_at')->values();

        $exposures = [];
        $lostHours = 0.0;
        $lostLow = 0.0;   // Tmin optimiste (bactéries moins sensibles à la chaleur)
        $lostHigh = 0.0;  // Tmin pessimiste
        $criticalMinutes = 0;
        $totalMinutes = 0;
        [$tMinLow, $tMinHigh] = $profile['t_min_range'];

        foreach ($readings as $i => $reading) {
            $temp = (float) $reading->temperature;
            $minutes = $this->durationOf($reading, $readings[$i + 1] ?? null);
            $hours = $minutes / 60;
            $rate = $this->relativeRate($temp, $profile['reference_temp'], $profile['t_min']);
            $extra = max(0.0, $rate - 1) * $hours;

            // Bornes de la fourchette : Tmin plus bas → effet de la chaleur plus faible
            $lostLow += max(0.0, $this->relativeRate($temp, $profile['reference_temp'], $tMinLow) - 1) * $hours;
            $lostHigh += max(0.0, $this->relativeRate($temp, $profile['reference_temp'], $tMinHigh) - 1) * $hours;

            $lostHours += $extra;
            $totalMinutes += $minutes;
            if ($temp >= $profile['critical_temp']) {
                $criticalMinutes += $minutes;
            }

            $exposures[] = [
                'id' => $reading->getKey(),
                'at' => CarbonImmutable::parse($reading->recorded_at)->toIso8601String(),
                'location' => $reading->location,
                'temperature' => round($temp, 2),
                'minutes' => $minutes,
                'rate' => round($rate, 2),
                'lost_hours' => round($extra, 2),
                'above_reference' => $temp > $profile['reference_temp'],
                'critical' => $temp >= $profile['critical_temp'],
            ];
        }

        $predicted = $this->shift($printed, $lostHours);
        $earliest = $this->shift($printed, max($lostHours, $lostHigh));
        $latest = $this->shift($printed, min($lostHours, $lostLow));
        $daysLost = round($lostHours / 24, 2);

        $today = CarbonImmutable::today();
        $production = $this->productionDate($batch);
        $nominalDays = $production ? max(1, $production->diffInDays($printed)) : null;
        $elapsedDays = $production ? max(0, $production->diffInDays($today, false)) : null;
        $consumed = $nominalDays ? min(100, round(100 * ($elapsedDays + $daysLost) / $nominalDays, 1)) : null;

        $risk = match (true) {
            $printed->lt($today) => self::RISK_EXPIRED,
            $exposures === [] => self::RISK_UNMONITORED,
            default => $this->risk($daysLost, $criticalMinutes, $predicted, $today),
        };
        $assessment = ShelfLifeAssessment::query()->where('batch_id', $batch->getKey())->latest('id')->first();

        return [
            'batch_id' => $batch->getKey(),
            'lot' => BatchAttributes::code($batch),
            'product' => $batch->product?->name,
            'profile' => $profileKey,
            'profile_label' => $profile['label'],
            'model' => [
                'name' => 'Ratkowsky (racine carrée)',
                'reference_temp' => $profile['reference_temp'],
                't_min' => $profile['t_min'],
                't_min_range' => $profile['t_min_range'],
                'critical_temp' => $profile['critical_temp'],
            ],
            'printed' => $printed->toDateString(),
            'predicted' => $predicted->toDateString(),
            'earliest' => $earliest->toDateString(),
            'latest' => $latest->toDateString(),
            'lost_hours' => round($lostHours, 1),
            'days_lost' => $daysLost,
            'remaining_days' => (int) $today->diffInDays($predicted, false),
            'printed_remaining_days' => (int) $today->diffInDays($printed, false),
            'consumed_pct' => $consumed,
            'production' => $production?->toDateString(),
            'readings' => count($exposures),
            'monitored_hours' => round($totalMinutes / 60, 1),
            'critical_minutes' => $criticalMinutes,
            'exposures' => $exposures,
            'risk' => $risk,
            'risk_label' => self::RISK_LABELS[$risk],
            'explanation' => $this->explain($profile, $exposures, $lostHours, $printed, $predicted, $criticalMinutes, count($exposures)),
            'action' => $this->action($risk, $predicted, $today),
            'assessment' => $assessment ? [
                'decision' => $assessment->decision,
                'note' => $assessment->note,
                'at' => $assessment->created_at?->format('d/m/Y H:i'),
                'user_id' => $assessment->user_id,
            ] : null,
            // Date utilisée par le module : la DLC réelle, sauf si un responsable l'a rejetée
            'effective' => ($assessment?->decision === ShelfLifeAssessment::REJECTED ? $printed : $predicted)->toDateString(),
        ];
    }

    /**
     * Date utilisée par les décisions du module (FEFO, optimisation, anti-gaspillage, jumeau numérique).
     * Sans relevé de température, ou si le modèle est désactivé : la DLC imprimée.
     */
    public function effectiveExpiry(Batch $batch): ?CarbonImmutable
    {
        $printed = BatchAttributes::expiryDate($batch);
        if (! $printed || ! $this->enabled()) {
            return $printed;
        }
        if (! TransportCondition::query()->where('batch_id', $batch->getKey())->exists()) {
            return $printed;
        }

        $prediction = $this->predict($batch);

        return CarbonImmutable::parse($prediction['effective'])->startOfDay();
    }

    /** Vitesse de vieillissement relative à la température de référence (modèle de Ratkowsky). */
    public function relativeRate(float $temp, float $reference, float $tMin): float
    {
        if ($temp <= $tMin) {
            return 0.0; // trop froid : pas de croissance bactérienne
        }
        $rate = (($temp - $tMin) / ($reference - $tMin)) ** 2;

        return min((float) $this->cfg['max_relative_rate'], $rate);
    }

    /** Profil de la famille du produit (reconnue d'après son nom et sa catégorie). */
    public function profileFor(Batch $batch): array
    {
        $product = $batch->product;
        $text = Str::lower(Str::ascii(trim(($product?->name ?? '').' '.($product?->category ?? '').' '.($product?->categoryModel?->name ?? ''))));
        $profiles = $this->cfg['profiles'];

        foreach ($profiles as $key => $profile) {
            foreach ($profile['keywords'] ?? [] as $word) {
                if (preg_match('/\b'.preg_quote(Str::lower(Str::ascii($word)), '/').'/u', $text)) {
                    return [$key, $profile];
                }
            }
        }

        return ['default', $profiles['default'] ?? [
            'label' => 'Produits frais', 'reference_temp' => 4.0, 't_min' => -2.0, 't_min_range' => [-3.5, -0.5], 'critical_temp' => 10.0,
        ]];
    }

    /* ------------------------------------------------------------------ */

    /** Durée d'un relevé : renseignée, sinon jusqu'au relevé suivant (plafonnée), sinon valeur par défaut. */
    private function durationOf(TransportCondition $reading, ?TransportCondition $next): int
    {
        if ($reading->duration_minutes) {
            return (int) $reading->duration_minutes;
        }
        $default = (int) $this->cfg['default_reading_minutes'];
        if ($next) {
            $gap = (int) CarbonImmutable::parse($reading->recorded_at)->diffInMinutes(CarbonImmutable::parse($next->recorded_at));

            return max(1, min($gap, 4 * $default));
        }

        return $default;
    }

    /**
     * Recule la DLC des heures perdues, par journées entières : arrondi prudent, une tranche
     * de plus de N heures (6 par défaut) compte pour un jour (on ne vend pas un produit bon « jusqu'à midi »).
     */
    public function shift(CarbonImmutable $printed, float $lostHours): CarbonImmutable
    {
        $tolerance = (float) ($this->cfg['day_rounding_hours'] ?? 6);
        $days = (int) floor(($lostHours + 24 - $tolerance) / 24);

        return $printed->startOfDay()->subDays(max(0, $days));
    }

    private function productionDate(Batch $batch): ?CarbonImmutable
    {
        $value = $batch->getAttribute('production_date');

        return $value ? CarbonImmutable::parse($value)->startOfDay() : null;
    }

    private function risk(float $daysLost, int $criticalMinutes, CarbonImmutable $predicted, CarbonImmutable $today): string
    {
        if ($criticalMinutes >= $this->cfg['critical_minutes'] || $predicted->lt($today)) {
            return self::RISK_CRITICAL;
        }

        return $daysLost >= $this->cfg['attention_days'] ? self::RISK_ATTENTION : self::RISK_OK;
    }

    private function explain(array $profile, array $exposures, float $lostHours, CarbonImmutable $printed, CarbonImmutable $predicted, int $criticalMinutes, int $count): string
    {
        if ($count === 0) {
            return 'Aucun relevé de température pour ce lot : la DLC imprimée est conservée.';
        }

        $hot = collect($exposures)->where('lost_hours', '>', 0)->sortByDesc('lost_hours')->values();
        if ($hot->isEmpty()) {
            return sprintf(
                '%d relevé(s) de température, tous à %s °C ou moins : la chaîne du froid a été respectée, la DLC imprimée reste valable.',
                $count, $this->n($profile['reference_temp'])
            );
        }

        $worst = $hot->first();
        if ($predicted->equalTo($printed)) {
            return sprintf(
                'Sur %d relevé(s), le lot a eu un peu chaud (pire épisode : %s h à %s °C%s), mais seulement %s h de vie perdues : pas assez pour changer la DLC imprimée.',
                $count, $this->n($worst['minutes'] / 60), $this->n($worst['temperature']),
                $worst['location'] ? ', '.$worst['location'] : '', $this->n($lostHours)
            );
        }
        $text = sprintf(
            'Sur %d relevé(s), le lot a passé %s h au-dessus de %s °C. Le pire épisode : %s h à %s °C%s, où il a vieilli %s fois plus vite qu’au froid (%s h de vie perdues). Au total : %s h perdues, soit une DLC réelle au %s au lieu du %s.',
            $count, $this->n($hot->sum('minutes') / 60), $this->n($profile['reference_temp']),
            $this->n($worst['minutes'] / 60), $this->n($worst['temperature']), $worst['location'] ? ' ('.$worst['location'].')' : '',
            $this->n($worst['rate']), $this->n($worst['lost_hours']),
            $this->n($lostHours), $predicted->format('d/m/Y'), $printed->format('d/m/Y')
        );
        if ($criticalMinutes > 0) {
            $text .= sprintf(' %s h au-dessus de la température critique (%s °C).', $this->n($criticalMinutes / 60), $this->n($profile['critical_temp']));
        }

        return $text;
    }

    private function action(string $risk, CarbonImmutable $predicted, CarbonImmutable $today): string
    {
        if ($risk === self::RISK_UNMONITORED) {
            return 'Demander les relevés de température du transport au module Chaîne du froid ; en attendant, la DLC imprimée est utilisée.';
        }
        if ($risk === self::RISK_EXPIRED) {
            return 'La DLC imprimée est dépassée : retirer le lot de la vente et enregistrer une sortie « destruction ».';
        }
        if ($predicted->lt($today)) {
            return 'La DLC réelle est dépassée : bloquer le lot, faire un contrôle qualité, puis le détruire s’il n’est pas conforme.';
        }

        return match ($risk) {
            self::RISK_CRITICAL => 'Contrôle qualité avant toute vente (odeur, aspect, pH). S’il est conforme : vendre en priorité ou donner rapidement.',
            self::RISK_ATTENTION => 'Vendre ce lot avant les autres (FEFO sur la DLC réelle) ou le transférer vers le magasin qui vend le plus vite.',
            default => 'Aucune action : le lot peut suivre le circuit normal.',
        };
    }

    private function n(float $v): string
    {
        return number_format($v, fmod(round($v, 1), 1.0) == 0.0 ? 0 : 1, ',', ' ');
    }
}
