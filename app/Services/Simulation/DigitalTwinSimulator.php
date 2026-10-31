<?php

namespace App\Services\Simulation;

use App\Enums\SiteType;
use App\Models\Product;
use App\Models\Site;
use App\Models\Stock;
use App\Services\Forecast\DemandForecaster;
use App\Services\Stock\StockService;
use App\Support\BatchAttributes;
use App\Support\Geo;
use Carbon\CarbonImmutable;

/**
 * Jumeau numérique « Et si ? » du réseau de stocks.
 *
 * Copie l'état RÉEL des stocks d'aujourd'hui, applique un scénario (pic de demande, panne de camion,
 * magasin fermé, rupture de la chaîne du froid) puis rejoue les N jours suivants deux fois :
 *
 *  - « sans IA »  : pratique habituelle. Un magasin passé sous son seuil recommande à l'entrepôt
 *                   (premier lot trouvé, sans regarder la DLC) ; ce qui dépasse la DLC est jeté.
 *  - « avec IA »  : les outils du module. Prévision de la demande (calendrier tunisien),
 *                   vente FEFO, transferts anticipés vers les magasins qui vendront avant la DLC,
 *                   promotion calculée par élasticité-prix, don aux associations la veille de la DLC,
 *                   réassort avant la rupture.
 *
 * Rien n'est écrit dans les stocks réels : la simulation travaille sur une copie en mémoire.
 * Les mêmes événements et la même demande sont appliqués aux deux stratégies, pour une comparaison juste.
 */
class DigitalTwinSimulator
{
    public const MIN_HORIZON = 7;

    public const MAX_HORIZON = 30;

    /** Jours de demande couverts après l'arrivée d'un réassort (une livraison pour plusieurs jours) */
    private const COVER_DAYS = 5;

    private CarbonImmutable $start;

    private int $horizon;

    private array $scenario;

    /** @var array<int, array> sites du réseau (id => infos) */
    private array $sites = [];

    /** @var array<int, array> produits présents dans les stocks */
    private array $products = [];

    /** @var array<int, array> lignes de stock initiales */
    private array $initialLines = [];

    /** @var array<string, array<int, float>> demande de base prévue ["site-produit"][jour] */
    private array $baseDemand = [];

    /** @var array<string, float> seuils d'alerte ["site-produit"] */
    private array $thresholds = [];

    /** @var array<string, float> distances routières ["a-b"] en km */
    private array $distances = [];

    private array $cfg;

    public function __construct(
        private readonly DemandForecaster $forecaster,
        private readonly StockService $stocks,
    ) {}

    /**
     * Lance la simulation.
     *
     * @param  array{horizon?:int, demand?:array, closed?:array, transport?:array, cold_chain?:array}  $scenario
     */
    public function run(array $scenario): array
    {
        $this->scenario = $this->normalize($scenario);
        $this->horizon = $this->scenario['horizon'];
        $this->start = CarbonImmutable::today();
        $this->cfg = [
            'elasticity' => (float) config('stock.anti_waste.price_elasticity', 2.0),
            'max_discount' => (int) config('stock.anti_waste.max_discount', 30),
            'discount_step' => max(1, (int) config('stock.anti_waste.discount_step', 10)),
            'min_transfer' => (float) config('stock.anti_waste.min_transfer_quantity', 20),
            'kg_per_meal' => (float) config('stock.anti_waste.kg_per_meal', 0.5),
            'co2_per_kg_food' => (float) config('stock.anti_waste.co2_per_kg_food', 2.5),
            'road_factor' => (float) config('stock.optimization.road_factor', 1.25),
            'emission' => (float) config('stock.optimization.emission_factor_kg_per_tkm', 0.115),
        ];

        $this->loadNetwork();

        $baseline = $this->simulate('baseline');
        $ai = $this->simulate('ai');

        return [
            'start' => $this->start->toDateString(),
            'horizon' => $this->horizon,
            'scenario' => $this->scenario,
            'days' => array_map(fn (int $d) => $this->start->addDays($d)->toDateString(), range(0, $this->horizon - 1)),
            'sites' => array_values(array_map(fn (array $s) => array_intersect_key($s, array_flip(['id', 'code', 'name', 'type', 'city', 'lat', 'lng', 'sells', 'association'])), $this->sites)),
            'products' => array_values(array_map(fn (array $p) => array_intersect_key($p, array_flip(['id', 'name', 'unit'])), $this->products)),
            'initial' => [
                'lines' => count($this->initialLines),
                'units' => round(array_sum(array_column($this->initialLines, 'qty')), 2),
                'lots_with_expiry' => count(array_filter($this->initialLines, fn (array $l) => $l['expiry'] !== null)),
            ],
            'baseline' => $baseline,
            'ai' => $ai,
            'comparison' => $this->compare($baseline['totals'], $ai['totals']),
            'explanation' => $this->explain($baseline, $ai),
        ];
    }

    /** Scénario complété avec des valeurs par défaut et bornées. */
    public function normalize(array $s): array
    {
        $horizon = (int) max(self::MIN_HORIZON, min(self::MAX_HORIZON, (int) ($s['horizon'] ?? 14)));
        $last = $horizon - 1;
        $clampDay = fn ($v, int $default = 0) => (int) max(0, min($last, (int) ($v ?? $default)));

        $demand = $s['demand'] ?? [];
        $closed = $s['closed'] ?? [];
        $transport = $s['transport'] ?? [];
        $cold = $s['cold_chain'] ?? [];

        return [
            'horizon' => $horizon,
            'demand' => [
                'change' => (float) max(-90, min(200, (float) ($demand['change'] ?? 0))),
                'product_id' => ! empty($demand['product_id']) ? (int) $demand['product_id'] : null,
                'from' => $clampDay($demand['from'] ?? 0),
                'to' => $clampDay($demand['to'] ?? $last, $last),
            ],
            'closed' => [
                'site_id' => ! empty($closed['site_id']) ? (int) $closed['site_id'] : null,
                'from' => $clampDay($closed['from'] ?? 1, 1),
                'days' => (int) max(1, min($horizon, (int) ($closed['days'] ?? 3))),
            ],
            'transport' => [
                'delay' => (int) max(0, min(7, (int) ($transport['delay'] ?? 0))),
                'from' => $clampDay($transport['from'] ?? 0),
            ],
            'cold_chain' => [
                'site_id' => ! empty($cold['site_id']) ? (int) $cold['site_id'] : null,
                'day' => $clampDay($cold['day'] ?? 1, 1),
                'lost_days' => (int) max(1, min(14, (int) ($cold['lost_days'] ?? 3))),
            ],
        ];
    }

    /* ------------------------------------------------------------------
     | Chargement de l'état réel
     * ------------------------------------------------------------------ */

    private function loadNetwork(): void
    {
        $this->sites = Site::query()->active()->orderBy('id')->get()
            ->mapWithKeys(fn (Site $s) => [$s->getKey() => [
                'id' => $s->getKey(),
                'code' => $s->code,
                'name' => $s->name,
                'type' => $s->type?->value,
                'city' => $s->city,
                'lat' => $s->latitude !== null ? (float) $s->latitude : null,
                'lng' => $s->longitude !== null ? (float) $s->longitude : null,
                'capacity' => (float) $s->capacity,
                'association' => $s->type === SiteType::Association,
                'sells' => false, // calculé plus bas d'après l'historique de ventes
            ]])->all();

        $lines = Stock::query()
            ->with(['batch', 'product'])
            ->whereIn('site_id', array_keys($this->sites))
            ->where('quantity', '>', 0)
            ->orderBy('id')
            ->get();

        $this->initialLines = [];
        $this->products = [];

        foreach ($lines as $line) {
            if ($this->sites[$line->site_id]['association']) {
                continue; // le stock des associations est déjà donné
            }
            if ($line->batch && BatchAttributes::isBlocked($line->batch)) {
                continue; // lot rappelé ou périmé : invendable
            }

            $expiry = BatchAttributes::expiryDate($line->batch);
            if ($expiry && $expiry->lt($this->start)) {
                continue;
            }

            $productId = (int) $line->product_id;
            $this->products[$productId] ??= [
                'id' => $productId,
                'name' => (string) $line->product?->name,
                'unit' => (string) ($line->product?->unit ?? 'u.'),
                'weight' => BatchAttributes::unitWeightKg($line->product),
            ];

            $this->initialLines[] = [
                'id' => (int) $line->getKey(),
                'site' => (int) $line->site_id,
                'product' => $productId,
                'batch' => $line->batch_id ? (int) $line->batch_id : null,
                'lot' => $line->batch ? BatchAttributes::code($line->batch) : null,
                'expiry' => $expiry?->toDateString(),
                'qty' => round((float) $line->quantity, 2),
            ];
        }

        // Demande de base : prévision du module (historique + calendrier tunisien), jour par jour
        $this->baseDemand = [];
        $this->thresholds = [];
        foreach ($this->sites as $siteId => $site) {
            if ($site['association']) {
                continue;
            }
            foreach (array_keys($this->products) as $productId) {
                $key = $siteId.'-'.$productId;
                $forecast = $this->forecaster->forecast($siteId, $productId, $this->start, $this->horizon + 7);
                $values = $forecast['has_history'] ? array_map(fn (array $p) => (float) $p['qty'], $forecast['days']) : [];

                if (array_sum($values) > 0) {
                    $this->baseDemand[$key] = $values;
                    $this->sites[$siteId]['sells'] = true;
                }

                $threshold = $this->stocks->thresholdFor($siteId, $productId);
                if ($threshold > 0) {
                    $this->thresholds[$key] = $threshold;
                }
            }
        }
    }

    /* ------------------------------------------------------------------
     | Boucle de simulation
     * ------------------------------------------------------------------ */

    private function simulate(string $strategy): array
    {
        $isAi = $strategy === 'ai';
        $state = [
            'lines' => $this->initialLines,
            'inbound' => [],
            'promos' => [],
            'donated_to' => [],
            'next_id' => 1_000_000,
        ];
        $totals = array_fill_keys(['demand', 'sold', 'unmet', 'wasted', 'donated', 'promo_units', 'transfers', 'transferred', 'km', 'co2_transport'], 0.0);
        $totals['waste_kg'] = $totals['donated_kg'] = 0.0;
        $daily = [];
        $actions = [];
        $frames = [];

        for ($d = 0; $d < $this->horizon; $d++) {
            $date = $this->start->addDays($d);
            $day = ['sold' => 0.0, 'unmet' => 0.0, 'wasted' => 0.0, 'donated' => 0.0, 'demand' => 0.0];
            $moves = [];

            // 1. Arrivée des camions partis les jours précédents
            foreach ($state['inbound'] as $i => $shipment) {
                if ($shipment['arrive'] === $d) {
                    $this->addToLine($state, $shipment);
                    unset($state['inbound'][$i]);
                }
            }

            // 2. Événement : rupture de la chaîne du froid (les lots du site perdent des jours de DLC)
            $cold = $this->scenario['cold_chain'];
            if ($cold['site_id'] && $cold['day'] === $d) {
                $hit = 0.0;
                foreach ($state['lines'] as &$line) {
                    if ($line['site'] === $cold['site_id'] && $line['expiry'] && $line['qty'] > 0) {
                        $line['expiry'] = CarbonImmutable::parse($line['expiry'])->subDays($cold['lost_days'])->toDateString();
                        $hit += $line['qty'];
                    }
                }
                unset($line);
                if ($hit > 0) {
                    $actions[] = $this->action($d, 'event', sprintf(
                        'Rupture de la chaîne du froid à %s : %s unité(s) perdent %d jour(s) de DLC.',
                        $this->siteName($cold['site_id']), $this->q($hit), $cold['lost_days']
                    ));
                }
                // Les lots dont la nouvelle DLC est déjà passée sont perdus
                $this->expire($state, $date->subDay(), $d, $day, $totals, $actions, $isAi);
            }

            // 3. Ventes de la journée (FEFO : le lot qui périme le plus tôt part en premier)
            foreach ($this->baseDemand as $key => $values) {
                [$siteId, $productId] = array_map('intval', explode('-', $key));
                $base = $this->demandAt($siteId, $productId, $d);
                $totals['demand'] += $base;
                $day['demand'] += $base;

                if ($this->isClosed($siteId, $d)) {
                    $totals['unmet'] += $base;
                    $day['unmet'] += $base;

                    continue;
                }

                $promo = $isAi ? ($state['promos'][$key] ?? null) : null;
                $uplift = ($promo && $d <= $promo['until']) ? 1 + $this->cfg['elasticity'] * $promo['discount'] / 100 : 1.0;
                $wanted = $base * $uplift;
                $sold = $this->consume($state, $siteId, $productId, $wanted, $date);

                $totals['sold'] += $sold;
                $totals['promo_units'] += max(0, $sold - $base);
                $totals['unmet'] += max(0, $base - $sold);
                $day['sold'] += $sold;
                $day['unmet'] += max(0, $base - $sold);
            }

            // 4. Décisions de fin de journée (prennent effet le lendemain)
            if ($isAi) {
                $this->aiDecisions($state, $d, $totals, $actions, $moves);
            } else {
                $this->baselineDecisions($state, $d, $totals, $actions, $moves);
            }

            // 5. Fin de journée : ce qui atteint sa DLC sans être vendu est jeté
            $this->expire($state, $date, $d, $day, $totals, $actions, $isAi);

            $day['stock'] = round(array_sum(array_column($state['lines'], 'qty')) + array_sum(array_column($state['inbound'], 'qty')), 2);
            $daily[] = array_map(fn ($v) => round($v, 2), $day);
            $frames[] = ['sites' => $this->frameSites($state, $d), 'moves' => $moves];
        }

        $remaining = round(array_sum(array_column($state['lines'], 'qty')) + array_sum(array_column($state['inbound'], 'qty')), 2);
        $totals['remaining'] = $remaining;
        $totals['service_rate'] = $totals['demand'] > 0 ? round(100 * ($totals['demand'] - $totals['unmet']) / $totals['demand'], 1) : 100.0;
        $totals['meals'] = round($totals['donated_kg'] / max(0.01, $this->cfg['kg_per_meal']));
        $totals['co2_waste'] = round($totals['waste_kg'] * $this->cfg['co2_per_kg_food'], 1);
        $totals = array_map(fn ($v) => is_float($v) ? round($v, 2) : $v, $totals);

        return [
            'totals' => $totals,
            'daily' => $daily,
            'actions' => $actions,
            'frames' => $frames,
        ];
    }

    /* ------------------------------------------------------------------
     | Stratégie habituelle (sans IA)
     * ------------------------------------------------------------------ */

    private function baselineDecisions(array &$state, int $d, array &$totals, array &$actions, array &$moves): void
    {
        if ($d + 1 >= $this->horizon) {
            return;
        }

        foreach ($this->thresholds as $key => $threshold) {
            [$siteId, $productId] = array_map('intval', explode('-', $key));
            if (! $this->sites[$siteId]['sells'] || $this->isClosed($siteId, $d + 1)) {
                continue;
            }

            $cover = $this->available($state, $siteId, $productId) + $this->inboundQty($state, $siteId, $productId);
            if ($cover > $threshold) {
                continue;
            }

            // Commande « au seuil » : remonter à 2 × seuil, auprès de l'entrepôt le mieux fourni
            $need = 2 * $threshold - $cover;
            $source = $this->biggestDepot($state, $productId, $siteId);
            if (! $source || $need <= 0) {
                continue;
            }

            // Sans regarder la DLC : les lignes dans l'ordre où elles ont été reçues
            $sent = 0.0;
            foreach ($state['lines'] as $i => $line) {
                if ($sent >= $need) {
                    break;
                }
                if ($line['site'] !== $source || $line['product'] !== $productId || $line['qty'] <= 0) {
                    continue;
                }
                $qty = min($line['qty'], $need - $sent);
                $sent += $this->ship($state, $i, $siteId, $qty, $d, $totals, $moves, 'transfer');
            }

            if ($sent > 0) {
                $actions[] = $this->action($d, 'reorder', sprintf(
                    '%s passe sous son seuil (%s) : commande de %s %s à %s.',
                    $this->siteName($siteId), $this->q($threshold), $this->q($sent), $this->products[$productId]['name'], $this->siteName($source)
                ));
            }
        }
    }

    /* ------------------------------------------------------------------
     | Stratégie IA
     * ------------------------------------------------------------------ */

    private function aiDecisions(array &$state, int $d, array &$totals, array &$actions, array &$moves): void
    {
        $t = $d + 1;
        if ($t >= $this->horizon + 1) {
            return;
        }

        // A. Lots à risque : quantité qui ne sera pas vendue sur place avant la DLC
        foreach ($this->riskyLines($state, $t) as $risk) {
            [$i, $unsold, $expiryIdx] = $risk;
            $line = $state['lines'][$i];
            $productId = $line['product'];
            $arrive = $this->arrivalDay($d);

            // A1. Transfert vers les magasins qui peuvent vendre avant la DLC (le plus proche d'abord)
            if ($arrive <= $expiryIdx && $arrive < $this->horizon) {
                foreach ($this->destinationsFor($state, $line, $arrive, $expiryIdx) as [$destId, $spare]) {
                    if ($unsold < $this->cfg['min_transfer']) {
                        break;
                    }
                    $qty = floor(min($unsold, $spare, $state['lines'][$i]['qty']));
                    if ($qty < $this->cfg['min_transfer']) {
                        continue;
                    }
                    $sent = $this->ship($state, $i, $destId, $qty, $d, $totals, $moves, 'transfer');
                    $unsold -= $sent;
                    $actions[] = $this->action($d, 'transfer', sprintf(
                        'Transfert anticipé de %s %s (lot %s, DLC %s) de %s vers %s : vendus avant la DLC d’après la prévision.',
                        $this->q($sent), $this->products[$productId]['name'], $line['lot'] ?? '—',
                        CarbonImmutable::parse($line['expiry'])->format('d/m'), $this->siteName($line['site']), $this->siteName($destId)
                    ));
                }
            }

            // A2. Promotion calculée sur place (magasin qui vend, DLC dans 3 jours ou moins)
            $key = $line['site'].'-'.$productId;
            $daysLeft = $expiryIdx - $t + 1;
            if ($unsold >= 1 && $this->sites[$line['site']]['sells'] && $daysLeft <= 3 && ($state['promos'][$key]['until'] ?? -1) < $t) {
                $base = 0.0;
                for ($k = $t; $k <= min($expiryIdx, $this->horizon - 1); $k++) {
                    $base += $this->isClosed($line['site'], $k) ? 0 : $this->demandAt($line['site'], $productId, $k);
                }
                if ($base > 0) {
                    $neededUplift = ($base + $unsold) / $base;
                    $discount = (int) ceil((($neededUplift - 1) / max(0.1, $this->cfg['elasticity'])) * 100 / $this->cfg['discount_step']) * $this->cfg['discount_step'];
                    $discount = (int) max($this->cfg['discount_step'], min($this->cfg['max_discount'], $discount));
                    $state['promos'][$key] = ['discount' => $discount, 'until' => $expiryIdx];
                    $actions[] = $this->action($d, 'promo', sprintf(
                        'Promotion de -%d %% sur %s à %s jusqu’au %s : environ +%d %% de ventes pour écouler %s unité(s) à risque.',
                        $discount, $this->products[$productId]['name'], $this->siteName($line['site']),
                        CarbonImmutable::parse($line['expiry'])->format('d/m'), (int) round($this->cfg['elasticity'] * $discount), $this->q($unsold)
                    ));
                }
            }
        }

        // B. Don la veille de la DLC : ce qui ne sera pas vendu demain part vers une association
        foreach ($state['lines'] as $i => $line) {
            if ($line['qty'] <= 0 || ! $line['expiry'] || $this->dayIndex($line['expiry']) !== $t) {
                continue;
            }
            $key = $line['site'].'-'.$line['product'];
            $promo = $state['promos'][$key] ?? null;
            $uplift = $promo ? 1 + $this->cfg['elasticity'] * $promo['discount'] / 100 : 1.0;
            $tomorrow = ($this->sites[$line['site']]['sells'] && ! $this->isClosed($line['site'], $t))
                ? $this->demandAt($line['site'], $line['product'], $t) * $uplift : 0.0;
            // FEFO : les lignes qui périment avant celle-ci sur le même site passent d'abord
            $before = 0.0;
            foreach ($state['lines'] as $other) {
                if ($other['site'] === $line['site'] && $other['product'] === $line['product'] && $other['qty'] > 0
                    && $other['expiry'] && $other['expiry'] < $line['expiry']) {
                    $before += $other['qty'];
                }
            }
            $surplus = floor($line['qty'] - max(0, $tomorrow - $before));
            if ($surplus < 1) {
                continue;
            }
            $association = $this->nearestAssociation($line['site']);
            if (! $association) {
                continue;
            }
            $this->ship($state, $i, $association, $surplus, $d, $totals, $moves, 'donation');
            $kg = $surplus * $this->products[$line['product']]['weight'];
            $actions[] = $this->action($d, 'donation', sprintf(
                'Don de %s %s (DLC demain) de %s à %s : environ %s repas.',
                $this->q($surplus), $this->products[$line['product']]['name'], $this->siteName($line['site']),
                $this->siteName($association), $this->q(round($kg / max(0.01, $this->cfg['kg_per_meal'])))
            ));
        }

        // C. Réassort avant la rupture (prévision sur le délai de livraison + quelques jours)
        if ($t >= $this->horizon) {
            return;
        }
        $arrive = $this->arrivalDay($d);
        foreach ($this->baseDemand as $key => $values) {
            [$siteId, $productId] = array_map('intval', explode('-', $key));
            if ($this->isClosed($siteId, min($arrive, $this->horizon - 1))) {
                continue;
            }
            $cover = $this->available($state, $siteId, $productId) + $this->inboundQty($state, $siteId, $productId);
            // Déclenchement : le stock ne tiendra pas jusqu'au lendemain de l'arrivée du camion
            $trigger = 0.0;
            $target = 0.0;
            for ($k = $t; $k <= $arrive + self::COVER_DAYS; $k++) {
                $q = $this->isClosed($siteId, $k) ? 0 : $this->demandAt($siteId, $productId, $k);
                $target += $q;
                if ($k <= $arrive + 1) {
                    $trigger += $q;
                }
            }
            if ($cover >= $trigger) {
                continue;
            }
            // Une seule livraison pour couvrir plusieurs jours (moins de camions)
            $missing = ceil($target - $cover);
            if ($missing < 1) {
                continue;
            }

            $sentTotal = 0.0;
            foreach ($this->replenishmentSources($state, $siteId, $productId, $arrive) as [$i, $available]) {
                if ($missing <= 0) {
                    break;
                }
                $qty = floor(min($missing, $available));
                if ($qty < 1) {
                    continue;
                }
                $from = $state['lines'][$i]['site'];
                $sent = $this->ship($state, $i, $siteId, $qty, $d, $totals, $moves, 'transfer');
                $missing -= $sent;
                $sentTotal += $sent;
                $actions[] = $this->action($d, 'reorder', sprintf(
                    'Rupture évitée à %s : %s %s envoyés depuis %s (lot qui périme le plus tôt en premier).',
                    $this->siteName($siteId), $this->q($sent), $this->products[$productId]['name'], $this->siteName($from)
                ));
            }
        }
    }

    /**
     * Lignes qui ne seront pas entièrement vendues sur place avant leur DLC.
     *
     * @return list<array{0:int,1:float,2:int}>  [index de ligne, quantité invendue prévue, jour de la DLC]
     */
    private function riskyLines(array $state, int $t): array
    {
        $groups = [];
        foreach ($state['lines'] as $i => $line) {
            if ($line['qty'] > 0 && $line['expiry']) {
                $groups[$line['site'].'-'.$line['product']][] = $i;
            }
        }

        $risky = [];
        foreach ($groups as $key => $indexes) {
            [$siteId, $productId] = array_map('intval', explode('-', $key));
            usort($indexes, fn ($a, $b) => $state['lines'][$a]['expiry'] <=> $state['lines'][$b]['expiry']);

            $promo = $state['promos'][$key] ?? null;
            $consumedBefore = 0.0;
            foreach ($indexes as $i) {
                $line = $state['lines'][$i];
                $expiryIdx = $this->dayIndex($line['expiry']);
                $demand = 0.0;
                if ($this->sites[$siteId]['sells']) {
                    for ($k = $t; $k <= min($expiryIdx, $this->horizon + 6); $k++) {
                        if ($this->isClosed($siteId, $k)) {
                            continue;
                        }
                        $uplift = ($promo && $k <= $promo['until']) ? 1 + $this->cfg['elasticity'] * $promo['discount'] / 100 : 1.0;
                        $demand += $this->demandAt($siteId, $productId, $k) * $uplift;
                    }
                }
                $sellable = max(0, $demand - $consumedBefore);
                $unsold = $line['qty'] - $sellable;
                $consumedBefore += $line['qty'];

                if ($unsold >= 1 && $expiryIdx >= $t) {
                    $risky[] = [$i, $unsold, $expiryIdx];
                }
            }
        }

        return $risky;
    }

    /**
     * Magasins pouvant vendre une partie du lot avant sa DLC, du plus proche au plus lointain.
     *
     * @return list<array{0:int,1:float}> [site, demande libre avant la DLC]
     */
    private function destinationsFor(array $state, array $line, int $arrive, int $expiryIdx): array
    {
        $result = [];
        foreach ($this->sites as $siteId => $site) {
            if ($siteId === $line['site'] || ! $site['sells'] || $site['association'] || $site['lat'] === null) {
                continue;
            }
            $demand = 0.0;
            for ($k = $arrive; $k <= min($expiryIdx, $this->horizon + 6); $k++) {
                $demand += $this->isClosed($siteId, $k) ? 0 : $this->demandAt($siteId, $line['product'], $k);
            }
            // Ce qui sera vendu avant ce lot sur place (FEFO) : lignes qui périment avant ou en même temps, et ce qui arrive
            $ahead = $this->inboundQty($state, $siteId, $line['product'], $line['expiry']);
            foreach ($state['lines'] as $other) {
                if ($other['site'] === $siteId && $other['product'] === $line['product'] && $other['qty'] > 0
                    && $other['expiry'] && $other['expiry'] <= $line['expiry']) {
                    $ahead += $other['qty'];
                }
            }
            $spare = $demand - $ahead;
            if ($spare >= $this->cfg['min_transfer']) {
                $result[] = [$siteId, $spare, $this->distance($line['site'], $siteId)];
            }
        }
        usort($result, fn ($a, $b) => $a[2] <=> $b[2]);

        return array_map(fn ($r) => [$r[0], $r[1]], $result);
    }

    /**
     * Lignes qui peuvent réapprovisionner un magasin sans créer de rupture ailleurs.
     *
     * @return list<array{0:int,1:float}> [index de ligne, quantité disponible]
     */
    private function replenishmentSources(array $state, int $destId, int $productId, int $arrive): array
    {
        $candidates = [];
        foreach ($state['lines'] as $i => $line) {
            if ($line['site'] === $destId || $line['product'] !== $productId || $line['qty'] <= 0) {
                continue;
            }
            if ($line['expiry'] && $this->dayIndex($line['expiry']) < $arrive) {
                continue; // arriverait périmé
            }
            $source = $this->sites[$line['site']];
            if ($source['lat'] === null || $this->sites[$destId]['lat'] === null) {
                continue;
            }
            $available = $line['qty'];
            if ($source['sells']) {
                // Un magasin ne cède que ce qu'il ne vendra pas lui-même sur l'horizon
                $own = 0.0;
                for ($k = 0; $k < $this->horizon; $k++) {
                    $own += $this->demandAt($line['site'], $productId, $k);
                }
                $available = min($available, $this->available($state, $line['site'], $productId) - $own);
            }
            if ($available >= 1) {
                $candidates[] = [$i, $available, $source['sells'] ? 1 : 0, $line['expiry'] ?? '9999-12-31', $this->distance($line['site'], $destId)];
            }
        }
        // Entrepôts d'abord, puis le lot qui périme le plus tôt, puis le plus proche
        usort($candidates, fn ($a, $b) => [$a[2], $a[3], $a[4]] <=> [$b[2], $b[3], $b[4]]);

        return array_map(fn ($c) => [$c[0], $c[1]], $candidates);
    }

    /* ------------------------------------------------------------------
     | Outils
     * ------------------------------------------------------------------ */

    /** Vend jusqu'à $wanted unités sur un site, lot le plus proche de sa DLC en premier. */
    private function consume(array &$state, int $siteId, int $productId, float $wanted, CarbonImmutable $date): float
    {
        $indexes = [];
        foreach ($state['lines'] as $i => $line) {
            if ($line['site'] === $siteId && $line['product'] === $productId && $line['qty'] > 0
                && (! $line['expiry'] || $line['expiry'] >= $date->toDateString())) {
                $indexes[] = $i;
            }
        }
        usort($indexes, fn ($a, $b) => ($state['lines'][$a]['expiry'] ?? '9999-12-31') <=> ($state['lines'][$b]['expiry'] ?? '9999-12-31'));

        $sold = 0.0;
        foreach ($indexes as $i) {
            if ($sold >= $wanted) {
                break;
            }
            $take = min($state['lines'][$i]['qty'], $wanted - $sold);
            $state['lines'][$i]['qty'] = round($state['lines'][$i]['qty'] - $take, 4);
            $sold += $take;
        }

        return $sold;
    }

    /** Jette ce qui atteint sa DLC à la fin de la journée. */
    private function expire(array &$state, CarbonImmutable $date, int $d, array &$day, array &$totals, array &$actions, bool $isAi): void
    {
        $lost = [];
        foreach ($state['lines'] as $i => $line) {
            if ($line['qty'] > 0 && $line['expiry'] && $line['expiry'] <= $date->toDateString()) {
                $kg = $line['qty'] * $this->products[$line['product']]['weight'];
                $totals['wasted'] += $line['qty'];
                $totals['waste_kg'] += $kg;
                $day['wasted'] += $line['qty'];
                $k = $line['site'].'-'.$line['product'];
                $lost[$k] = ($lost[$k] ?? 0) + $line['qty'];
                $state['lines'][$i]['qty'] = 0.0;
            }
        }
        foreach ($lost as $k => $qty) {
            [$siteId, $productId] = array_map('intval', explode('-', $k));
            $actions[] = $this->action($d, 'waste', sprintf(
                '%s %s jeté(s) à %s : DLC atteinte sans être vendus.',
                $this->q($qty), $this->products[$productId]['name'], $this->siteName($siteId)
            ));
        }
    }

    /** Fait partir un camion (transfert ou don). Le don est compté tout de suite. */
    private function ship(array &$state, int $lineIndex, int $destId, float $qty, int $d, array &$totals, array &$moves, string $kind): float
    {
        $line = &$state['lines'][$lineIndex];
        $qty = round(min($qty, $line['qty']), 2);
        if ($qty <= 0) {
            return 0.0;
        }
        $line['qty'] = round($line['qty'] - $qty, 4);

        $km = $this->distance($line['site'], $destId);
        $tonnes = $qty * $this->products[$line['product']]['weight'] / 1000;
        $totals['km'] += $km;
        $totals['co2_transport'] += $km * $tonnes * $this->cfg['emission'];

        if ($kind === 'donation') {
            $totals['donated'] += $qty;
            $totals['donated_kg'] += $qty * $this->products[$line['product']]['weight'];
        } else {
            $totals['transfers']++;
            $totals['transferred'] += $qty;
            $state['inbound'][] = [
                'arrive' => $this->arrivalDay($d),
                'site' => $destId,
                'product' => $line['product'],
                'batch' => $line['batch'],
                'lot' => $line['lot'],
                'expiry' => $line['expiry'],
                'qty' => $qty,
            ];
        }

        $moves[] = [
            'kind' => $kind,
            'from' => $line['site'],
            'to' => $destId,
            'product' => $this->products[$line['product']]['name'],
            'qty' => $qty,
        ];

        return $qty;
    }

    private function addToLine(array &$state, array $shipment): void
    {
        foreach ($state['lines'] as $i => $line) {
            if ($line['site'] === $shipment['site'] && $line['product'] === $shipment['product'] && $line['batch'] === $shipment['batch']
                && $line['expiry'] === $shipment['expiry']) {
                $state['lines'][$i]['qty'] = round($line['qty'] + $shipment['qty'], 4);

                return;
            }
        }
        $state['lines'][] = [
            'id' => $state['next_id']++,
            'site' => $shipment['site'],
            'product' => $shipment['product'],
            'batch' => $shipment['batch'],
            'lot' => $shipment['lot'],
            'expiry' => $shipment['expiry'],
            'qty' => $shipment['qty'],
        ];
    }

    private function demandAt(int $siteId, int $productId, int $d): float
    {
        $values = $this->baseDemand[$siteId.'-'.$productId] ?? null;
        if (! $values) {
            return 0.0;
        }
        $base = $values[min($d, count($values) - 1)] ?? 0.0;
        $shock = $this->scenario['demand'];
        if ($shock['change'] != 0 && $d >= $shock['from'] && $d <= $shock['to']
            && ($shock['product_id'] === null || $shock['product_id'] === $productId)) {
            $base *= 1 + $shock['change'] / 100;
        }

        return max(0.0, $base);
    }

    private function isClosed(int $siteId, int $d): bool
    {
        $c = $this->scenario['closed'];

        return $c['site_id'] === $siteId && $d >= $c['from'] && $d < $c['from'] + $c['days'];
    }

    /** Jour d'arrivée d'un camion qui part le soir du jour $d. */
    private function arrivalDay(int $d): int
    {
        $t = $this->scenario['transport'];
        $delay = ($t['delay'] > 0 && $d >= $t['from']) ? $t['delay'] : 0;

        return $d + 1 + $delay;
    }

    private function available(array $state, int $siteId, int $productId): float
    {
        $sum = 0.0;
        foreach ($state['lines'] as $line) {
            if ($line['site'] === $siteId && $line['product'] === $productId) {
                $sum += $line['qty'];
            }
        }

        return $sum;
    }

    private function inboundQty(array $state, int $siteId, int $productId, ?string $expiringBy = null): float
    {
        $sum = 0.0;
        foreach ($state['inbound'] as $s) {
            if ($s['site'] === $siteId && $s['product'] === $productId
                && ($expiringBy === null || ($s['expiry'] && $s['expiry'] <= $expiringBy))) {
                $sum += $s['qty'];
            }
        }

        return $sum;
    }

    /** Site (hors magasins et associations) qui détient le plus de ce produit. */
    private function biggestDepot(array $state, int $productId, int $exceptSite): ?int
    {
        $best = null;
        $bestQty = 0.0;
        foreach ($this->sites as $siteId => $site) {
            if ($siteId === $exceptSite || $site['sells'] || $site['association']) {
                continue;
            }
            $qty = $this->available($state, $siteId, $productId);
            if ($qty > $bestQty) {
                [$best, $bestQty] = [$siteId, $qty];
            }
        }

        return $best;
    }

    private function nearestAssociation(int $siteId): ?int
    {
        $best = null;
        $bestKm = INF;
        foreach ($this->sites as $id => $site) {
            if ($site['association'] && $site['lat'] !== null) {
                $km = $this->distance($siteId, $id);
                if ($km < $bestKm) {
                    [$best, $bestKm] = [$id, $km];
                }
            }
        }

        return $best;
    }

    private function distance(int $a, int $b): float
    {
        if ($a === $b) {
            return 0.0;
        }
        $key = min($a, $b).'-'.max($a, $b);
        if (! isset($this->distances[$key])) {
            $sa = $this->sites[$a] ?? null;
            $sb = $this->sites[$b] ?? null;
            $this->distances[$key] = ($sa && $sb && $sa['lat'] !== null && $sb['lat'] !== null)
                ? round(Geo::haversineKm($sa['lat'], $sa['lng'], $sb['lat'], $sb['lng']) * $this->cfg['road_factor'], 1)
                : 0.0;
        }

        return $this->distances[$key];
    }

    private function dayIndex(string $date): int
    {
        return (int) $this->start->diffInDays(CarbonImmutable::parse($date), false);
    }

    /** État des sites en fin de journée, pour la carte animée. */
    private function frameSites(array $state, int $d): array
    {
        $frame = [];
        foreach ($this->sites as $siteId => $site) {
            if ($site['lat'] === null) {
                continue;
            }
            $units = 0.0;
            $status = 'ok';
            foreach (array_keys($this->products) as $productId) {
                $qty = $this->available($state, $siteId, $productId);
                $units += $qty;
                $key = $siteId.'-'.$productId;
                if (isset($this->baseDemand[$key]) && $qty < 1) {
                    $status = 'out';
                } elseif ($status === 'ok' && isset($this->thresholds[$key]) && $qty <= $this->thresholds[$key]) {
                    $status = 'low';
                }
            }
            if ($this->isClosed($siteId, $d)) {
                $status = 'closed';
            }
            $frame[] = ['id' => $siteId, 'units' => round($units), 'status' => $status];
        }

        return $frame;
    }

    private function compare(array $baseline, array $ai): array
    {
        $delta = fn (string $k) => round($ai[$k] - $baseline[$k], 2);
        $pct = fn (string $k) => $baseline[$k] > 0 ? round(100 * ($baseline[$k] - $ai[$k]) / $baseline[$k]) : null;

        return [
            'wasted' => ['delta' => $delta('wasted'), 'reduction_pct' => $pct('wasted')],
            'unmet' => ['delta' => $delta('unmet'), 'reduction_pct' => $pct('unmet')],
            'sold' => ['delta' => $delta('sold')],
            'service_rate' => ['delta' => round($ai['service_rate'] - $baseline['service_rate'], 1)],
            'waste_kg_avoided' => round(max(0, $baseline['waste_kg'] - $ai['waste_kg']), 1),
            'co2_avoided' => round(max(0, $baseline['co2_waste'] - $ai['co2_waste']) - max(0, $ai['co2_transport'] - $baseline['co2_transport']), 1),
            'meals' => $ai['meals'],
        ];
    }

    /** Explication en français, à lire à l'oral. */
    private function explain(array $baseline, array $ai): array
    {
        $b = $baseline['totals'];
        $a = $ai['totals'];
        $lines = [];

        $events = [];
        $s = $this->scenario;
        if ($s['demand']['change'] != 0) {
            $events[] = sprintf('demande %+d %%%s du jour %d au jour %d', $s['demand']['change'],
                $s['demand']['product_id'] ? ' sur '.($this->products[$s['demand']['product_id']]['name'] ?? Product::query()->whereKey($s['demand']['product_id'])->value('name')) : '',
                $s['demand']['from'] + 1, $s['demand']['to'] + 1);
        }
        if ($s['closed']['site_id']) {
            $events[] = sprintf('%s fermé %d jour(s) à partir du jour %d', $this->siteName($s['closed']['site_id']), $s['closed']['days'], $s['closed']['from'] + 1);
        }
        if ($s['transport']['delay'] > 0) {
            $events[] = sprintf('camions retardés de %d jour(s) à partir du jour %d', $s['transport']['delay'], $s['transport']['from'] + 1);
        }
        if ($s['cold_chain']['site_id']) {
            $events[] = sprintf('rupture du froid à %s le jour %d (-%d jour(s) de DLC)', $this->siteName($s['cold_chain']['site_id']), $s['cold_chain']['day'] + 1, $s['cold_chain']['lost_days']);
        }
        $lines[] = 'Point de départ : l’état réel des stocks aujourd’hui ('.$this->q(array_sum(array_column($this->initialLines, 'qty'))).' unités sur '
            .count(array_unique(array_column($this->initialLines, 'site'))).' sites), puis '.$this->horizon.' jours simulés'
            .($events ? ' avec : '.implode(' ; ', $events).'.' : ' sans incident.');

        $lines[] = sprintf('Sans IA : %s unité(s) jetée(s), %s vente(s) perdue(s), taux de service %s %%.',
            $this->q($b['wasted']), $this->q($b['unmet']), $this->q1($b['service_rate']));
        $lines[] = sprintf('Avec IA : %s unité(s) jetée(s), %s vente(s) perdue(s), taux de service %s %%, %s unité(s) données (≈ %s repas).',
            $this->q($a['wasted']), $this->q($a['unmet']), $this->q1($a['service_rate']), $this->q($a['donated']), $this->q($a['meals']));

        if ($b['wasted'] > 0 && $a['wasted'] < $b['wasted']) {
            $kinds = array_count_values(array_column($ai['actions'], 'kind'));
            $levers = array_filter([
                ! empty($kinds['transfer']) ? $kinds['transfer'].' transfert(s) anticipé(s)' : null,
                ! empty($kinds['promo']) ? $kinds['promo'].' promotion(s) calculée(s)' : null,
                ! empty($kinds['donation']) ? $kinds['donation'].' don(s) la veille de la DLC' : null,
            ]);
            $lines[] = sprintf('Le gaspillage baisse de %d %%%s.',
                (int) round(100 * ($b['wasted'] - $a['wasted']) / $b['wasted']),
                $levers ? ' grâce à '.$this->joinFr($levers) : '');
        } elseif ($a['wasted'] > 0 && $a['wasted'] >= $b['wasted']) {
            $lines[] = 'Une partie des pertes n’est pas évitable dans ce scénario (lot abîmé avant de pouvoir être déplacé ou donné).';
        }
        if ($a['unmet'] < $b['unmet']) {
            $lines[] = sprintf('%s rupture(s) de vente évitée(s) : l’IA réapprovisionne avant que le stock ne tombe à zéro, en tenant compte du délai des camions.',
                $this->q($b['unmet'] - $a['unmet']));
        }
        if ($a['co2_transport'] > $b['co2_transport']) {
            $lines[] = sprintf('Contrepartie : %s kg CO₂e de transport en plus, à comparer aux %s kg CO₂e évités en ne jetant pas la nourriture.',
                $this->q1($a['co2_transport'] - $b['co2_transport']), $this->q1(max(0, $b['co2_waste'] - $a['co2_waste'])));
        }

        return $lines;
    }

    private function joinFr(array $items): string
    {
        $items = array_values($items);
        $last = array_pop($items);

        return $items ? implode(', ', $items).' et '.$last : (string) $last;
    }

    private function action(int $d, string $kind, string $text): array
    {
        return ['day' => $d, 'date' => $this->start->addDays($d)->toDateString(), 'kind' => $kind, 'text' => $text];
    }

    private function siteName(int $siteId): string
    {
        return $this->sites[$siteId]['name'] ?? ('site #'.$siteId);
    }

    private function q(float $v): string
    {
        return number_format(round($v), 0, ',', ' ');
    }

    private function q1(float $v): string
    {
        return number_format($v, 1, ',', ' ');
    }
}
