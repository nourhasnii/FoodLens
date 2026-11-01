<?php

namespace App\Services\Optimization;

use App\Enums\RecommendationStatus;
use App\Enums\StockMovementType;
use App\Models\Batch;
use App\Models\OptimizationRecommendation;
use App\Models\Site;
use App\Models\Stock;
use App\Models\User;
use App\Services\Forecast\DemandForecaster;
use App\Services\Stock\StockService;
use App\Support\BatchAttributes;
use App\Support\Fmt;
use App\Support\Geo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Plan anti-gaspillage d'un lot proche de sa DLC, en trois niveaux :
 *
 *  1. Transferts partiels : chaque magasin reçoit ce qu'il peut vendre avant la DLC.
 *     Règle FEFO : un magasin vend d'abord ce qui périme avant ce lot, puis ce lot.
 *  2. Promotion calculée : la plus petite remise (par paliers) qui permet d'écouler le reste,
 *     selon l'élasticité-prix configurée (ventes × (1 + élasticité × remise)).
 *  3. Don solidaire : le reste part vers les associations les plus proches, à temps pour être redistribué.
 *
 * Ce qui ne peut être ni vendu ni donné est signalé comme perte probable.
 */
class AntiWastePlanner
{
    public function __construct(
        private readonly StockService $stocks,
        private readonly DemandForecaster $forecaster,
    ) {}

    /** Calcule le plan pour une recommandation (sans rien enregistrer). */
    public function planFor(OptimizationRecommendation $recommendation): array
    {
        $recommendation->loadMissing(['batch.product', 'sourceSite']);

        return $this->plan($recommendation->batch, $recommendation->sourceSite, (float) $recommendation->quantity);
    }

    public function plan(Batch $batch, Site $source, float $quantity): array
    {
        $daysLeft = BatchAttributes::daysToEffectiveExpiry($batch); // DLC réelle (chaîne du froid)

        if ($daysLeft === null) {
            return $this->notApplicable('La DLC du lot n’est pas renseignée : le plan anti-gaspillage ne s’applique qu’aux produits datés.');
        }

        if ($daysLeft < 0) {
            return $this->notApplicable('Le lot a dépassé sa DLC : il ne peut plus être vendu ni donné.');
        }

        if (! $source->hasCoordinates()) {
            return $this->notApplicable('Renseignez les coordonnées GPS du site d’origine pour calculer le plan.');
        }

        $cfg = config('stock.optimization');
        $aw = config('stock.anti_waste');
        $productId = (int) $batch->product_id;
        $unitKg = BatchAttributes::unitWeightKg($batch->product);
        $expiry = BatchAttributes::effectiveExpiryDate($batch);
        $elasticity = (float) $aw['price_elasticity'];
        $notes = [];

        // --- Points de vente (le site d'origine compris s'il vend lui-même) --------------------
        $outlets = Site::query()->active()->logistics()->withSum('stocks', 'quantity')->get()
            ->filter(fn (Site $site) => $site->hasCoordinates())
            ->map(function (Site $site) use ($source, $batch, $productId, $daysLeft, $cfg, $aw, $expiry) {
                $isSource = $site->is($source);
                $distance = $isSource ? 0.0 : Geo::haversineKm($source->latitude, $source->longitude, $site->latitude, $site->longitude) * $cfg['road_factor'];
                $transitDays = $distance / max(1, $cfg['average_speed_kmh']) / 24;
                $window = $daysLeft - $transitDays;
                $daily = config('stock.forecast.enabled', true)
                    ? $this->forecaster->averageDaily($site->getKey(), $productId, now()->addDays($transitDays), max(1, $window))
                    : $this->stocks->averageDailyConsumption($site->getKey(), $productId, $cfg['demand_window_days']);

                if ($daily <= 0 || $window * 24 < $aw['min_selling_hours']) {
                    return null;
                }

                // FEFO : ce qui périme au plus tard en même temps que ce lot est vendu avant lui
                $before = $this->stockExpiringBefore($site, $productId, $batch, $expiry, $isSource);

                return [
                    'site' => $site,
                    'is_source' => $isSource,
                    'distance' => $distance,
                    'hours' => $transitDays * 24,
                    'window' => $window,
                    'daily' => $daily,
                    'before' => $before,
                    'free' => $isSource || (float) $site->capacity <= 0 ? INF : $site->freeCapacity(),
                ];
            })
            ->filter()
            ->sortBy('distance')
            ->values()
            ->all();

        $sellable = fn (array $o, float $discount) => max(0, $o['daily'] * (1 + $elasticity * $discount / 100) * $o['window'] - $o['before']);

        // --- Niveau 1 : transferts partiels sans remise ---------------------------------------
        $remaining = $quantity;
        $alloc = [];

        foreach ($outlets as $i => $o) {
            $take = floor(min($remaining, $sellable($o, 0), $o['free']));
            $alloc[$i] = ['base' => max(0, $take), 'promo' => 0.0];
            $remaining -= $alloc[$i]['base'];
        }

        // --- Niveau 2 : la plus petite remise qui écoule le reste -----------------------------
        $discount = 0;
        $afterLevelOne = $remaining;

        if ($remaining >= 1 && $outlets) {
            $extraAt = function (int $d) use ($outlets, $alloc, $sellable) {
                $extra = [];
                foreach ($outlets as $i => $o) {
                    $room = $o['free'] - $alloc[$i]['base'];
                    $extra[$i] = floor(max(0, min($sellable($o, $d) - $alloc[$i]['base'], $room)));
                }

                return $extra;
            };

            for ($d = (int) $aw['discount_step']; $d <= (int) $aw['max_discount']; $d += (int) $aw['discount_step']) {
                $discount = $d;
                if (array_sum($extraAt($d)) >= $remaining) {
                    break;
                }
            }

            $extra = $extraAt($discount);

            if (array_sum($extra) < 1) {
                $discount = 0;
            } else {
                foreach ($outlets as $i => $o) {
                    $take = min($remaining, $extra[$i]);
                    $alloc[$i]['promo'] = $take;
                    $remaining -= $take;
                }

                // Une remise inutilement haute n'est pas proposée : on garde le premier palier suffisant
            }
        }

        // Associations joignables à temps pour redistribuer le lot
        $associations = Site::query()->active()->associations()->get()
            ->filter(fn (Site $site) => $site->hasCoordinates())
            ->map(function (Site $site) use ($source, $cfg) {
                $distance = Geo::haversineKm($source->latitude, $source->longitude, $site->latitude, $site->longitude) * $cfg['road_factor'];

                return ['site' => $site, 'distance' => $distance, 'hours' => $distance / max(1, $cfg['average_speed_kmh'])];
            })
            ->filter(fn (array $a) => $a['hours'] <= $daysLeft * 24 - $aw['donation_min_hours_before_expiry'])
            ->sortBy('distance')
            ->values();

        // Envois trop petits pour être rentables : la quantité part plutôt en don
        $smallShipments = [];

        foreach ($outlets as $i => $o) {
            $total = $alloc[$i]['base'] + $alloc[$i]['promo'];

            // Sans association pour recevoir le don, mieux vaut un petit envoi qu'une perte
            if ($associations->isNotEmpty() && ! $o['is_source'] && $total > 0 && $total < $aw['min_transfer_quantity']) {
                $smallShipments[] = sprintf('%s (%s u.)', $o['site']->name, Fmt::q($total));
                $remaining += $total;
                $alloc[$i] = ['base' => 0.0, 'promo' => 0.0];
            }
        }

        if ($smallShipments) {
            $notes[] = sprintf(
                'Envois de moins de %s u. non retenus, car peu rentables : %s. Ces quantités sont ajoutées au don.',
                Fmt::q($aw['min_transfer_quantity']), implode(', ', $smallShipments)
            );
        }

        // --- Niveau 3 : dons aux associations les plus proches --------------------------------
        $donations = [];

        if ($remaining >= 1) {
            if ($associations->isEmpty()) {
                $notes[] = 'Aucune association active n’est joignable à temps : ajoutez-en une dans « Sites » (type « Association »).';
            }

            foreach ($associations as $a) {
                if ($remaining < 1) {
                    break;
                }

                $limit = (float) $a['site']->capacity > 0 ? (float) $a['site']->capacity : INF;
                $take = floor(min($remaining, $limit));

                if ($take < 1) {
                    continue;
                }

                $donations[] = $this->line($a['site'], $take, $a['distance'], $a['hours'], $unitKg, $cfg) + [
                    'kind' => 'donation',
                    'meals' => (int) floor($take * $unitKg / $aw['kg_per_meal']),
                    'arrives_days_before_expiry' => round($daysLeft - $a['hours'] / 24, 1),
                ];
                $remaining -= $take;
            }
        }

        // --- Mise en forme -------------------------------------------------------------------
        $transfers = [];

        foreach ($outlets as $i => $o) {
            $total = $alloc[$i]['base'] + $alloc[$i]['promo'];

            if ($total < 1) {
                continue;
            }

            $transfers[] = $this->line($o['site'], $total, $o['distance'], $o['hours'], $unitKg, $cfg) + [
                'kind' => $o['is_source'] ? 'keep' : 'transfer',
                'base_quantity' => $alloc[$i]['base'],
                'promo_quantity' => $alloc[$i]['promo'],
                'discount' => $alloc[$i]['promo'] > 0 ? $discount : 0,
                'daily' => round($o['daily'], 1),
                'boosted_daily' => round($o['daily'] * (1 + $elasticity * ($alloc[$i]['promo'] > 0 ? $discount : 0) / 100), 1),
                'window_days' => round($o['window'], 1),
                'sold_first' => round($o['before'], 1),
            ];
        }

        $sold = array_sum(array_column($transfers, 'quantity'));
        $donated = array_sum(array_column($donations, 'quantity'));
        $loss = max(0, round($quantity - $sold - $donated, 2));
        $transportCo2 = array_sum(array_column($transfers, 'co2_kg')) + array_sum(array_column($donations, 'co2_kg'));
        $savedKg = ($sold + $donated) * $unitKg;
        $promoTotal = array_sum(array_column($transfers, 'promo_quantity'));

        // Sans action, le lot reste où il est : seul ce que le site d'origine vend lui-même est sauvé
        $sourceOutlet = collect($outlets)->firstWhere('is_source', true);
        $withoutPlan = max(0, $quantity - floor(min($quantity, $sourceOutlet ? $sellable($sourceOutlet, 0) : 0)));

        $impact = [
            'saved_units' => $sold + $donated,
            'saved_kg' => round($savedKg, 1),
            'donated_kg' => round($donated * $unitKg, 1),
            'meals' => (int) floor($donated * $unitKg / $aw['kg_per_meal']),
            'co2_food_kg' => round($savedKg * $aw['co2_per_kg_food'], 1),
            'co2_transport_kg' => round($transportCo2, 1),
            'co2_net_kg' => round($savedKg * $aw['co2_per_kg_food'] - $transportCo2, 1),
            'loss_without_plan' => $withoutPlan,
            'loss_with_plan' => $loss,
        ];

        return [
            'applicable' => true,
            'reason' => null,
            'quantity' => $quantity,
            'days_to_expiry' => $daysLeft,
            'expiry_date' => $expiry?->toDateString(),
            'unit_kg' => $unitKg,
            'elasticity' => $elasticity,
            'discount' => $promoTotal > 0 ? $discount : 0,
            'transfers' => $transfers,
            'donations' => $donations,
            'loss' => $loss,
            'impact' => $impact,
            'source' => ['id' => $source->getKey(), 'name' => $source->name, 'lat' => $source->latitude, 'lng' => $source->longitude],
            'steps' => $this->explain($transfers, $donations, $loss, $promoTotal, $discount, $elasticity, $afterLevelOne, $aw),
            'notes' => $notes,
            'computed_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Applique le plan : transferts vers les magasins, sorties « don » vers les associations.
     * Le plan est recalculé au moment de l'application pour partir des stocks réels.
     */
    public function apply(OptimizationRecommendation $recommendation, ?User $user = null): OptimizationRecommendation
    {
        if ($recommendation->hasAppliedRescue()) {
            throw ValidationException::withMessages(['rescue' => 'Un plan anti-gaspillage a déjà été appliqué à ce lot.']);
        }

        if (! in_array($recommendation->status, [RecommendationStatus::Pending, RecommendationStatus::Rejected], true)) {
            throw ValidationException::withMessages(['rescue' => 'Le lot a déjà été transféré suite à cette recommandation.']);
        }

        $plan = $this->planFor($recommendation);

        if (! $plan['applicable']) {
            throw ValidationException::withMessages(['rescue' => $plan['reason']]);
        }

        $toMove = collect($plan['transfers'])->where('kind', 'transfer')->sum('quantity') + collect($plan['donations'])->sum('quantity');

        if ($toMove <= 0) {
            throw ValidationException::withMessages(['rescue' => 'Le plan ne prévoit aucun mouvement : rien à appliquer.']);
        }

        $line = $this->stocks->findLine($recommendation->source_site_id, (int) $recommendation->product_id, $recommendation->batch_id);

        if ((float) ($line?->quantity ?? 0) < $toMove) {
            throw ValidationException::withMessages([
                'rescue' => sprintf(
                    'Le site d’origine ne détient que %s u. de ce lot pour %s u. à déplacer.',
                    Fmt::q($line?->quantity ?? 0), Fmt::q($toMove)
                ),
            ]);
        }

        $id = $recommendation->getKey();

        return DB::transaction(function () use ($recommendation, $plan, $user, $id) {
            foreach ($plan['transfers'] as &$t) {
                if ($t['kind'] !== 'transfer') {
                    continue;
                }

                $t['movement_id'] = $this->stocks->record([
                    'type' => StockMovementType::Transfer,
                    'batch_id' => $recommendation->batch_id,
                    'product_id' => $recommendation->product_id,
                    'source_site_id' => $recommendation->source_site_id,
                    'destination_site_id' => $t['site_id'],
                    'quantity' => $t['quantity'],
                    'reason' => 'Plan anti-gaspillage #'.$id.($t['discount'] ? ' · promotion −'.$t['discount'].' %' : ''),
                    'notes' => $t['discount']
                        ? sprintf('Remise de %d %% sur ce produit jusqu’à la DLC : %s u. supplémentaires attendues.', $t['discount'], Fmt::q($t['promo_quantity']))
                        : null,
                ], $user)->getKey();
            }
            unset($t);

            foreach ($plan['donations'] as &$d) {
                $d['movement_id'] = $this->stocks->record([
                    'type' => StockMovementType::Out,
                    'batch_id' => $recommendation->batch_id,
                    'product_id' => $recommendation->product_id,
                    'source_site_id' => $recommendation->source_site_id,
                    'quantity' => $d['quantity'],
                    'reason' => 'Don solidaire · '.$d['site_name'],
                    'notes' => 'Plan anti-gaspillage #'.$id.' · environ '.$d['meals'].' repas',
                ], $user)->getKey();
            }
            unset($d);

            $decision = $recommendation->status === RecommendationStatus::Pending
                ? ['decided_by' => $user?->getKey(), 'decided_at' => now(), 'decision_note' => 'Plan anti-gaspillage appliqué.']
                : [];

            $recommendation->update($decision + [
                'status' => RecommendationStatus::Rescued,
                'rescue_plan' => $plan,
                'rescue_applied_at' => now(),
                'rescue_applied_by' => $user?->getKey(),
            ]);

            return $recommendation;
        });
    }

    /** Stock du produit sur le site qui périme au plus tard avec ce lot (vendu en premier selon FEFO). */
    private function stockExpiringBefore(Site $site, int $productId, Batch $batch, $expiry, bool $isSource): float
    {
        return (float) Stock::query()
            ->with('batch')
            ->where('site_id', $site->getKey())
            ->where('product_id', $productId)
            ->whereNotNull('batch_id')
            ->get()
            ->filter(function (Stock $line) use ($batch, $expiry, $isSource) {
                if ($line->batch_id === $batch->getKey()) {
                    // Au site d'origine, c'est le lot lui-même : il n'est pas « devant » lui
                    return ! $isSource;
                }

                $other = $line->batch ? BatchAttributes::effectiveExpiryDate($line->batch) : null;

                return $other !== null && $expiry !== null && $other->lte($expiry);
            })
            ->sum(fn (Stock $line) => $line->availableQuantity());
    }

    private function line(Site $site, float $quantity, float $distance, float $hours, float $unitKg, array $cfg): array
    {
        return [
            'site_id' => $site->getKey(),
            'site_name' => $site->name,
            'city' => $site->city,
            'lat' => $site->latitude,
            'lng' => $site->longitude,
            'quantity' => $quantity,
            'distance_km' => round($distance, 1),
            'hours' => round($hours, 1),
            'co2_kg' => round($distance * $quantity * $unitKg / 1000 * $cfg['emission_factor_kg_per_tkm'], 2),
        ];
    }

    private function explain(array $transfers, array $donations, float $loss, float $promoTotal, int $discount, float $elasticity, float $afterLevelOne, array $aw): array
    {
        $steps = [];

        $base = collect($transfers)->where('base_quantity', '>', 0);
        $steps[] = [
            'level' => 1,
            'title' => 'Transferts partiels',
            'quantity' => $base->sum('base_quantity'),
            'text' => $base->isEmpty()
                ? 'Aucun point de vente ne peut écouler ce lot à temps : le délai avant la DLC est trop court, ou leurs produits plus anciens doivent être vendus d’abord (règle FEFO).'
                : 'Chaque point de vente reçoit ce qu’il peut vendre avant la DLC, après ses produits qui périment plus tôt (règle FEFO) : '
                    .$base->map(fn ($t) => sprintf('%s %s u. (%s u./jour pendant %s j)', $t['site_name'], Fmt::q($t['base_quantity']), Fmt::n($t['daily'], 1), Fmt::n($t['window_days'], 1)))->join(', ', ' et ').'.',
        ];

        $steps[] = [
            'level' => 2,
            'title' => 'Promotion calculée',
            'quantity' => $promoTotal,
            'text' => $promoTotal > 0
                ? sprintf(
                    'Une remise de %d %% augmente les ventes de %s %% (élasticité-prix de %s) : %s u. supplémentaires vendues avant la DLC. %s',
                    $discount, Fmt::n($elasticity * $discount), Fmt::n($elasticity, 1), Fmt::q($promoTotal),
                    $discount >= $aw['max_discount']
                        ? sprintf('C’est la remise maximale autorisée (%d %%) : au-delà, la vente se ferait à perte et le don est préférable.', $aw['max_discount'])
                        : 'C’est le plus petit palier suffisant.'
                )
                : ($afterLevelOne >= 1
                    ? 'Même avec une remise, aucun point de vente ne peut vendre davantage avant la DLC.'
                    : 'Pas de promotion nécessaire : les transferts suffisent.'),
        ];

        $steps[] = [
            'level' => 3,
            'title' => 'Don solidaire',
            'quantity' => array_sum(array_column($donations, 'quantity')),
            'text' => $donations
                ? collect($donations)->map(fn ($d) => sprintf(
                    '%s u. à %s (%s km, reçus %s j avant la DLC), soit environ %d repas',
                    Fmt::q($d['quantity']), $d['site_name'], Fmt::n($d['distance_km']), Fmt::n($d['arrives_days_before_expiry'], 1), $d['meals']
                ))->join(' ; ').'.'
                : ($loss > 0
                    ? 'Aucune association n’est joignable à temps pour redistribuer ce lot.'
                    : 'Aucun don nécessaire : tout le lot peut être vendu.'),
        ];

        if ($loss > 0) {
            $steps[] = [
                'level' => 4,
                'title' => 'Perte probable',
                'quantity' => $loss,
                'text' => sprintf('%s u. ne peuvent être ni vendues ni données à temps : prévoyez une sortie « destruction » à la DLC.', Fmt::q($loss)),
            ];
        }

        return $steps;
    }

    private function notApplicable(string $reason): array
    {
        return ['applicable' => false, 'reason' => $reason, 'transfers' => [], 'donations' => [], 'steps' => [], 'notes' => [], 'impact' => []];
    }
}
