@extends('layouts.stock')
@use('App\Support\Fmt')
@use('Carbon\CarbonImmutable')

@php
    $r = $simulation->results;
    $b = $r['baseline']['totals'];
    $a = $r['ai']['totals'];
    $days = $r['days'] ?? [];
    $n = count($days);

    // Lignes du tableau comparatif : [libellé, clé, décimales, plus petit = mieux ?, unité]
    $rows = [
        ['Ventes réalisées', 'sold', 0, false, 'u.'],
        ['Ventes perdues (ruptures, site fermé)', 'unmet', 0, true, 'u.'],
        ['Taux de service', 'service_rate', 1, false, '%'],
        ['Unités jetées (DLC dépassée)', 'wasted', 0, true, 'u.'],
        ['Nourriture jetée', 'waste_kg', 0, true, 'kg'],
        ['Unités données aux associations', 'donated', 0, false, 'u.'],
        ['Repas offerts', 'meals', 0, false, ''],
        ['Ventes en plus grâce aux promotions', 'promo_units', 0, false, 'u.'],
        ['Livraisons (camions)', 'transfers', 0, null, ''],
        ['Distance parcourue', 'km', 0, null, 'km'],
        ['CO₂ du transport', 'co2_transport', 1, true, 'kg'],
        ['CO₂ de la nourriture jetée', 'co2_waste', 1, true, 'kg'],
    ];

    $wasteCut = $b['wasted'] > 0 ? (int) round(100 * ($b['wasted'] - $a['wasted']) / $b['wasted']) : null;
    $co2Net = ($b['co2_waste'] + $b['co2_transport']) - ($a['co2_waste'] + $a['co2_transport']);

    // Séries cumulées pour les graphiques
    $cumulate = function (array $daily, string $key) {
        $sum = 0; $out = [];
        foreach ($daily as $d) { $sum += $d[$key]; $out[] = $sum; }
        return $out;
    };
    $charts = [
        ['title' => 'Unités jetées (cumul)', 'key' => 'wasted', 'color' => 'var(--brick)'],
        ['title' => 'Ventes perdues (cumul)', 'key' => 'unmet', 'color' => 'var(--amber)'],
    ];

    $mapData = [
        'days' => $days,
        'sites' => $r['sites'] ?? [],
        'frames' => ['baseline' => $r['baseline']['frames'], 'ai' => $r['ai']['frames']],
        'actions' => ['baseline' => $r['baseline']['actions'], 'ai' => $r['ai']['actions']],
    ];
    $kinds = [
        'transfer' => ['Transfert', 'tag-transfer'],
        'reorder' => ['Réassort', 'tag-in'],
        'promo' => ['Promotion', 'tag-expiring'],
        'donation' => ['Don', 'tag-rescued'],
        'waste' => ['Jeté', 'tag-out'],
        'event' => ['Incident', 'tag-pending'],
    ];
@endphp

@section('eyebrow', 'Digital twin · '.$simulation->presetLabel())
@section('title', $simulation->name)
@section('description', 'Simulation de '.$simulation->horizon_days.' jours à partir du '.$simulation->start_date->format('d/m/Y').' sur une copie des stocks réels. Même demande et mêmes incidents pour les deux stratégies.')

@section('page-action')
    <a class="btn btn-ghost" href="{{ route('simulations.index') }}">← Nouvelle simulation</a>
@endsection

@section('content')
<div class="figures">
    <div class="figure {{ $wasteCut !== null && $wasteCut > 0 ? '' : 'is-warn' }}">
        <strong>{{ $wasteCut !== null ? ($wasteCut > 0 ? '-' : '').abs($wasteCut).' %' : '—' }}</strong>
        <span>de gaspillage avec l’IA ({{ Fmt::n($b['wasted']) }} → {{ Fmt::n($a['wasted']) }} u. jetées)</span>
    </div>
    <div class="figure">
        <strong>{{ Fmt::n(max(0, $b['unmet'] - $a['unmet'])) }}</strong>
        <span>ventes perdues évitées (taux de service {{ Fmt::n($b['service_rate'], 1) }} % → {{ Fmt::n($a['service_rate'], 1) }} %)</span>
    </div>
    <div class="figure">
        <strong>{{ Fmt::n($a['meals']) }}</strong>
        <span>repas offerts aux associations ({{ Fmt::n($a['donated']) }} u. données la veille de la DLC)</span>
    </div>
    <div class="figure {{ $co2Net < 0 ? 'is-alert' : '' }}">
        <strong>{{ Fmt::n($co2Net, 0) }} kg</strong>
        <span>de CO₂e évités au total (nourriture non jetée moins transport en plus)</span>
    </div>
</div>

<div class="impact">
    <span class="impact-icon" aria-hidden="true">🔮</span>
    <div>
        @foreach ($r['explanation'] ?? [] as $i => $line)
            <p @class(['muted' => $i === 0])>{{ $line }}</p>
        @endforeach
    </div>
</div>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2>Sans IA ou avec IA ?</h2>
            <p class="muted small">
                <b>Sans IA</b> : un magasin recommande quand il passe sous son seuil, l’entrepôt envoie le premier lot trouvé, ce qui dépasse la DLC est jeté.
                <b>Avec IA</b> : prévision de la demande, vente du lot le plus proche de sa DLC d’abord, transferts anticipés, promotions calculées, dons la veille de la DLC, réassort avant la rupture.
            </p>
        </div>
    </div>
    <div class="table-wrap">
        <table class="compare">
            <thead>
                <tr><th>Indicateur</th><th class="num">Sans IA</th><th class="num">Avec IA</th><th class="num">Écart</th></tr>
            </thead>
            <tbody>
                @foreach ($rows as [$label, $key, $dec, $lowerIsBetter, $unit])
                    @php
                        $diff = round($a[$key] - $b[$key], $dec);
                        $better = $lowerIsBetter === null || $diff == 0 ? null : ($lowerIsBetter ? $diff < 0 : $diff > 0);
                    @endphp
                    <tr>
                        <td>{{ $label }}</td>
                        <td class="num">{{ Fmt::n($b[$key], $dec) }} {{ $unit }}</td>
                        <td class="num"><b>{{ Fmt::n($a[$key], $dec) }} {{ $unit }}</b></td>
                        <td class="num">
                            @if ($diff == 0)
                                <span class="muted">=</span>
                            @else
                                <span @class(['delta', 'is-good' => $better === true, 'is-bad' => $better === false])>{{ $diff > 0 ? '+' : '' }}{{ Fmt::n($diff, $dec) }} {{ $unit }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

@if ($n > 0)
<div class="chart-grid">
    @foreach ($charts as $chart)
        @php
            $sb = $cumulate($r['baseline']['daily'], $chart['key']);
            $sa = $cumulate($r['ai']['daily'], $chart['key']);
            $W = 520; $H = 230; $L = 48; $R = 14; $T = 16; $B = 34;
            $max = max(1, ...$sb, ...$sa) * 1.1;
            $step = collect([1, 2, 5, 10, 20, 25, 50, 100, 200, 250, 500, 1000, 2000, 5000])->first(fn ($s) => $max / $s <= 4) ?? 5000;
            $top = ceil($max / $step) * $step;
            $x = fn ($i) => $L + ($n > 1 ? $i * ($W - $L - $R) / ($n - 1) : 0);
            $y = fn ($v) => $T + ($H - $T - $B) * (1 - $v / $top);
            $pts = fn (array $s) => collect($s)->map(fn ($v, $i) => round($x($i), 1).','.round($y($v), 1))->join(' ');
            $labelEvery = max(1, (int) ceil($n / 7));
        @endphp
        <section class="panel">
            <h2 class="chart-title">{{ $chart['title'] }}</h2>
            <svg viewBox="0 0 {{ $W }} {{ $H }}" class="chart" role="img" aria-label="{{ $chart['title'] }} : sans IA {{ Fmt::n(end($sb)) }}, avec IA {{ Fmt::n(end($sa)) }}">
                @for ($v = 0; $v <= $top; $v += $step)
                    <line x1="{{ $L }}" x2="{{ $W - $R }}" y1="{{ $y($v) }}" y2="{{ $y($v) }}" class="c-grid"/>
                    <text x="{{ $L - 8 }}" y="{{ $y($v) + 4 }}" text-anchor="end">{{ Fmt::n($v) }}</text>
                @endfor
                @foreach ($days as $i => $day)
                    @if ($i % $labelEvery === 0)
                        <text x="{{ $x($i) }}" y="{{ $H - 12 }}" text-anchor="middle">{{ CarbonImmutable::parse($day)->format('d/m') }}</text>
                    @endif
                @endforeach
                <polyline points="{{ $pts($sb) }}" fill="none" stroke="{{ $chart['color'] }}" stroke-width="2.5" stroke-dasharray="6 5" stroke-linejoin="round"/>
                <polyline points="{{ $pts($sa) }}" fill="none" stroke="var(--pine)" stroke-width="3" stroke-linejoin="round"/>
                <circle cx="{{ $x($n - 1) }}" cy="{{ $y(end($sb)) }}" r="4" fill="{{ $chart['color'] }}"><title>Sans IA : {{ Fmt::n(end($sb)) }}</title></circle>
                <circle cx="{{ $x($n - 1) }}" cy="{{ $y(end($sa)) }}" r="4" fill="var(--pine)"><title>Avec IA : {{ Fmt::n(end($sa)) }}</title></circle>
            </svg>
            <p class="legend small">
                <span><i class="sw dashed" style="border-color: {{ $chart['color'] }}"></i>Sans IA : {{ Fmt::n(end($sb)) }}</span>
                <span><i class="sw" style="border-color: var(--pine)"></i>Avec IA : {{ Fmt::n(end($sa)) }}</span>
            </p>
        </section>
    @endforeach
</div>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2>Carte animée du réseau</h2>
            <p class="muted small">État de chaque site en fin de journée et camions partis ce jour-là. Appuyez sur ▶ pour rejouer la simulation.</p>
        </div>
        <div class="seg" role="group" aria-label="Stratégie affichée">
            <button type="button" class="seg-btn" data-strategy="baseline">Sans IA</button>
            <button type="button" class="seg-btn is-on" data-strategy="ai">Avec IA</button>
        </div>
    </div>

    <div class="player">
        <button type="button" class="btn btn-primary play" id="sim-play" aria-label="Lecture">▶</button>
        <input type="range" id="sim-day" min="0" max="{{ $n - 1 }}" value="0" aria-label="Jour affiché">
        <output id="sim-day-label" class="day-label"></output>
    </div>

    <div id="sim-map" class="map"></div>
    <p class="map-legend">
        <span><i style="background: var(--pine)"></i>Stock suffisant</span>
        <span><i style="background: #c48a1a"></i>Sous le seuil</span>
        <span><i style="background: #a3261d"></i>Rupture</span>
        <span><i style="background: #8a9a95"></i>Fermé</span>
        <span><i style="background: #6b4f9a"></i>Association</span>
        <span><i class="line" style="border-color: #245d8a"></i>Transfert</span>
        <span><i class="line dashed" style="border-color: #0f6b58"></i>Don</span>
    </p>
    <ul class="day-actions" id="sim-day-actions" aria-live="polite"></ul>
</section>
@endif

<section class="panel">
    <div class="panel-head">
        <div>
            <h2>Journal des décisions</h2>
            <p class="muted small">Ce qui s’est passé, jour après jour, dans chaque stratégie.</p>
        </div>
        <div class="seg" role="tablist" aria-label="Journal">
            <button type="button" class="seg-btn is-on" data-log="ai" role="tab" aria-selected="true">Avec IA ({{ count($r['ai']['actions']) }})</button>
            <button type="button" class="seg-btn" data-log="baseline" role="tab" aria-selected="false">Sans IA ({{ count($r['baseline']['actions']) }})</button>
        </div>
    </div>
    @foreach (['ai', 'baseline'] as $strategy)
        <div class="log" data-log-panel="{{ $strategy }}" @if ($strategy !== 'ai') hidden @endif>
            @forelse (collect($r[$strategy]['actions'])->groupBy('day') as $day => $actions)
                <h3 class="log-day">Jour {{ $day + 1 }} · {{ CarbonImmutable::parse($actions->first()['date'])->locale('fr')->isoFormat('dddd D MMMM') }}</h3>
                <ul class="log-list">
                    @foreach ($actions as $action)
                        <li><span class="tag {{ $kinds[$action['kind']][1] ?? '' }}">{{ $kinds[$action['kind']][0] ?? $action['kind'] }}</span> {{ $action['text'] }}</li>
                    @endforeach
                </ul>
            @empty
                <p class="muted">Aucune action : rien à signaler dans cette stratégie.</p>
            @endforelse
        </div>
    @endforeach
</section>

<section class="panel">
    <h2>Comment ça marche ?</h2>
    <ol class="how">
        <li><b>Copie de l’état réel.</b> Le jumeau numérique lit les stocks d’aujourd’hui (quantités, lots, DLC) et les sites avec leurs coordonnées GPS. Les lots rappelés ou périmés sont exclus.</li>
        <li><b>Demande prévue.</b> Pour chaque magasin et chaque produit, la demande jour par jour vient de la prévision du module (historique des ventes + calendrier tunisien), puis le scénario la modifie.</li>
        <li><b>Deux stratégies, mêmes conditions.</b> Chaque jour : arrivée des camions, incidents, ventes, décisions, puis on jette ce qui atteint sa DLC. Seules les décisions changent entre « sans IA » et « avec IA ».</li>
        <li><b>Décisions de l’IA.</b> Transfert vers le magasin le plus proche qui vendra avant la DLC, promotion calculée avec l’élasticité-prix ({{ config('stock.anti_waste.price_elasticity') }}), don à l’association la plus proche la veille de la DLC, réassort quand le stock ne tiendra pas jusqu’à la prochaine livraison.</li>
        <li><b>Bilan vérifiable.</b> Pour chaque stratégie : stock de départ = vendu + jeté + donné + restant. Rien n’est écrit dans les stocks réels.</li>
    </ol>
</section>
@endsection

@if ($n > 0)
    @include('partials.map')
@endif

@push('styles')
<style>
    .compare td.num, .compare th.num { text-align: right; white-space: nowrap; }
    .delta { font-weight: 700; }
    .delta.is-good { color: #2d6a4f; }
    .delta.is-bad { color: var(--brick); }
    .dark .delta.is-good { color: #a7d8bc; }
    .chart-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; }
    .chart-grid .panel { margin-bottom: 0; }
    .chart-title { margin-bottom: 8px; }
    .chart { width: 100%; height: auto; display: block; }
    .chart text { font: 500 11px var(--font); fill: var(--ink-soft); }
    .chart .c-grid { stroke: var(--rule); stroke-width: 1; }
    .legend { display: flex; flex-wrap: wrap; gap: 6px 18px; margin-top: 8px; color: var(--ink-soft); }
    .sw { display: inline-block; width: 22px; height: 0; border-top: 3px solid; margin-right: 6px; vertical-align: 3px; }
    .sw.dashed { border-top-style: dashed; }
    .seg { display: inline-flex; border: 1px solid var(--rule); border-radius: 10px; overflow: hidden; }
    .seg-btn { padding: 8px 14px; background: transparent; border: 0; font: 600 .85rem var(--font); color: var(--ink-soft); cursor: pointer; }
    .seg-btn + .seg-btn { border-left: 1px solid var(--rule); }
    .seg-btn.is-on { background: var(--pine); color: #fff; }
    .player { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; flex-wrap: wrap; }
    .player input[type=range] { flex: 1 1 240px; accent-color: #c17817; }
    .play { min-width: 46px; padding: 8px 12px; }
    .day-label { font-weight: 700; min-width: 190px; font-variant-numeric: tabular-nums; }
    .day-actions { list-style: none; margin: 12px 0 0; padding: 0; display: grid; gap: 6px; font-size: .88rem; }
    .day-actions:empty::before { content: 'Aucune décision ce jour-là.'; color: var(--ink-soft); }
    .log-day { margin: 16px 0 8px; font-size: .95rem; text-transform: capitalize; }
    .log-day:first-child { margin-top: 0; }
    .log-list { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; }
    .log-list li { line-height: 1.5; }
    .how { margin: 8px 0 0; padding-left: 20px; display: grid; gap: 8px; line-height: 1.55; }
    .sim-marker { display: grid; place-items: center; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 2px 6px rgba(0,0,0,.3); color: #fff; font: 700 10px/1 var(--font); transition: width .3s, height .3s; }
    .sim-move { animation: dash 1.2s linear infinite; }
    @keyframes dash { to { stroke-dashoffset: -20; } }
    @media (prefers-reduced-motion: reduce) { .sim-move { animation: none; } }
</style>
@endpush

@if ($n > 0)
@push('scripts')
<script>
(() => {
    const data = @json($mapData);
    const C = NtMap.colors;
    const statusColor = { ok: C.pine, low: C.amber, out: C.brick, closed: C.grey };
    const kindLabel = { transfer: 'Transfert', reorder: 'Réassort', promo: 'Promotion', donation: 'Don', waste: 'Jeté', event: 'Incident' };
    const sites = Object.fromEntries(data.sites.filter((s) => s.lat !== null).map((s) => [s.id, s]));

    let strategy = 'ai';
    let day = 0;
    let timer = null;

    const map = NtMap.create(document.getElementById('sim-map'));
    const slider = document.getElementById('sim-day');
    const label = document.getElementById('sim-day-label');
    const list = document.getElementById('sim-day-actions');
    const playBtn = document.getElementById('sim-play');
    const markers = {};
    let movesLayer = null;

    if (map) {
        Object.values(sites).forEach((s) => {
            markers[s.id] = L.marker([s.lat, s.lng], { icon: L.divIcon({ className: '', html: '' }) })
                .bindTooltip(NtMap.esc(s.name), { direction: 'top', offset: [0, -14] })
                .addTo(map);
        });
        movesLayer = L.layerGroup().addTo(map);
        NtMap.fit(map, Object.values(sites).map((s) => [s.lat, s.lng]), { maxZoom: 8 });
    }

    const fmtDay = (iso) => new Date(iso + 'T12:00:00').toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' });

    function render() {
        const frame = data.frames[strategy][day] || { sites: [], moves: [] };
        label.textContent = `Jour ${day + 1} / ${data.days.length} · ${fmtDay(data.days[day])}`;

        if (map) {
            const byId = Object.fromEntries(frame.sites.map((s) => [s.id, s]));
            Object.values(sites).forEach((s) => {
                const f = byId[s.id] || { units: 0, status: 'ok' };
                const color = s.association ? C.violet : statusColor[f.status];
                const size = s.association ? 18 : Math.round(18 + Math.min(26, Math.sqrt(f.units) * 0.9));
                markers[s.id].setIcon(L.divIcon({
                    className: '',
                    html: `<div class="sim-marker" style="width:${size}px;height:${size}px;background:${color}">${s.association ? '♥' : (f.units > 0 ? NtMap.fmt(f.units) : '0')}</div>`,
                    iconSize: [size, size],
                    iconAnchor: [size / 2, size / 2],
                }));
                const state = s.association ? 'Association' : { ok: 'Stock suffisant', low: 'Sous le seuil', out: 'Rupture', closed: 'Fermé' }[f.status];
                markers[s.id].setTooltipContent(`<b>${NtMap.esc(s.name)}</b><br>${state}${s.association ? '' : ' · ' + NtMap.fmt(f.units) + ' u.'}`);
            });

            movesLayer.clearLayers();
            frame.moves.forEach((m) => {
                const from = sites[m.from], to = sites[m.to];
                if (!from || !to) return;
                const donation = m.kind === 'donation';
                L.polyline([[from.lat, from.lng], [to.lat, to.lng]], {
                    color: donation ? C.pine : C.sky, weight: Math.min(8, 2 + Math.sqrt(m.qty) / 4),
                    opacity: .85, dashArray: donation ? '6 8' : '10 10', className: 'sim-move',
                }).bindTooltip(`${donation ? 'Don' : 'Transfert'} : ${NtMap.fmt(m.qty)} ${NtMap.esc(m.product)}<br>${NtMap.esc(from.name)} → ${NtMap.esc(to.name)}`, { sticky: true })
                  .addTo(movesLayer);
            });
        }

        list.innerHTML = data.actions[strategy]
            .filter((a) => a.day === day)
            .map((a) => `<li><b>${kindLabel[a.kind] || a.kind}</b> · ${NtMap.esc(a.text)}</li>`)
            .join('');
    }

    function stop() { clearInterval(timer); timer = null; playBtn.textContent = '▶'; playBtn.setAttribute('aria-label', 'Lecture'); }
    function play() {
        if (day >= data.days.length - 1) day = 0;
        playBtn.textContent = '❚❚'; playBtn.setAttribute('aria-label', 'Pause');
        timer = setInterval(() => {
            if (day >= data.days.length - 1) { stop(); return; }
            day++; slider.value = day; render();
        }, 1100);
        render();
    }

    playBtn.addEventListener('click', () => (timer ? stop() : play()));
    slider.addEventListener('input', () => { stop(); day = Number(slider.value); render(); });
    document.querySelectorAll('[data-strategy]').forEach((btn) => btn.addEventListener('click', () => {
        strategy = btn.dataset.strategy;
        document.querySelectorAll('[data-strategy]').forEach((b) => b.classList.toggle('is-on', b === btn));
        render();
    }));
    render();
})();
</script>
@endpush
@endif

@push('scripts')
<script>
    // Onglets du journal (Avec IA / Sans IA)
    document.querySelectorAll('[data-log]').forEach((btn) => btn.addEventListener('click', () => {
        document.querySelectorAll('[data-log]').forEach((b) => {
            b.classList.toggle('is-on', b === btn);
            b.setAttribute('aria-selected', b === btn ? 'true' : 'false');
        });
        document.querySelectorAll('[data-log-panel]').forEach((p) => { p.hidden = p.dataset.logPanel !== btn.dataset.log; });
    }));
</script>
@endpush
