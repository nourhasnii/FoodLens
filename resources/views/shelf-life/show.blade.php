@extends('layouts.stock')
@use('App\Support\Fmt')
@use('App\Services\ShelfLife\ShelfLifePredictor')
@use('App\Models\ShelfLifeAssessment')
@use('Carbon\CarbonImmutable')

@php
    $printed = CarbonImmutable::parse($p['printed']);
    $predicted = CarbonImmutable::parse($p['predicted']);
    $earliest = CarbonImmutable::parse($p['earliest']);
    $latest = CarbonImmutable::parse($p['latest']);
    $shorter = $predicted->lt($printed);
    $daysCut = (int) abs($printed->diffInDays($predicted));
    $m = $p['model'];
    $riskTag = [
        ShelfLifePredictor::RISK_CRITICAL => 'tag-out', ShelfLifePredictor::RISK_ATTENTION => 'tag-expiring',
        ShelfLifePredictor::RISK_EXPIRED => 'tag-expired', ShelfLifePredictor::RISK_OK => 'tag-ok',
        ShelfLifePredictor::RISK_UNMONITORED => '',
    ];
    $worst = collect($p['exposures'])->sortByDesc('lost_hours')->first();

    // Courbe des températures : un palier par relevé (début → début + durée)
    $segments = collect($p['exposures'])->map(function ($e) {
        $start = CarbonImmutable::parse($e['at']);
        return $e + ['start' => $start, 'end' => $start->addMinutes($e['minutes'])];
    })->values();
    $hasChart = $segments->isNotEmpty();
    if ($hasChart) {
        $W = 960; $H = 300; $L = 48; $R = 16; $T = 18; $B = 40;
        $t0 = $segments->min('start')->timestamp;
        $t1 = max($segments->max('end')->timestamp, $t0 + 3600);
        $tMin = floor(min($segments->min('temperature'), $m['reference_temp']) - 2);
        $tMax = ceil(max($segments->max('temperature'), $m['critical_temp']) + 2);
        $x = fn ($ts) => $L + ($W - $L - $R) * ($ts - $t0) / ($t1 - $t0);
        $y = fn ($t) => $T + ($H - $T - $B) * (1 - ($t - $tMin) / max(1, $tMax - $tMin));
        $zone = fn ($t) => $t >= $m['critical_temp'] ? 'hot' : ($t > $m['reference_temp'] ? 'warm' : 'cold');
        $tickStep = collect([1, 2, 5, 10])->first(fn ($s) => ($tMax - $tMin) / $s <= 8) ?? 10;
        $hours = ($t1 - $t0) / 3600;
        $hourStep = collect([1, 2, 3, 6, 12, 24])->first(fn ($s) => $hours / $s <= 10) ?? 24;
    }
@endphp

@section('eyebrow', 'Dynamic shelf life · '.$p['risk_label'])
@section('title', 'Lot '.$p['lot'].' · '.$p['product'])
@section('description', 'Vraie durée de vie calculée à partir de '.$p['readings'].' relevé(s) de température du transport (profil « '.$p['profile_label'].' »).')

@section('page-action')
    <a class="btn btn-ghost" href="{{ route('shelf-life.index') }}">← Tous les lots</a>
@endsection

@section('content')
<div class="figures">
    <div class="figure">
        <strong @class(['strike' => $shorter])>{{ $printed->format('d/m/Y') }}</strong>
        <span>DLC imprimée sur l’emballage ({{ $p['printed_remaining_days'] >= 0 ? 'dans '.$p['printed_remaining_days'].' j' : 'dépassée' }})</span>
    </div>
    <div @class(['figure', 'is-alert' => $shorter])>
        <strong>{{ $predicted->format('d/m/Y') }}</strong>
        <span>DLC réelle prédite{{ $shorter ? ' : '.$daysCut.' jour(s) de moins' : '' }}{{ $p['earliest'] !== $p['latest'] ? ' · fourchette '.$earliest->format('d/m').' – '.$latest->format('d/m') : '' }}</span>
    </div>
    <div @class(['figure', 'is-warn' => $p['lost_hours'] > 0])>
        <strong>{{ Fmt::n($p['lost_hours'], 1) }} h</strong>
        <span>de durée de vie perdues à cause de la chaleur ({{ Fmt::n($p['days_lost'], 1) }} jour(s))</span>
    </div>
    <div class="figure">
        <strong>{{ $p['consumed_pct'] !== null ? Fmt::n($p['consumed_pct']).' %' : '—' }}</strong>
        <span>de la durée de vie déjà consommée</span>
        @if ($p['consumed_pct'] !== null)
            <div class="rack {{ $p['consumed_pct'] >= 80 ? 'is-high' : ($p['consumed_pct'] >= 50 ? 'is-mid' : '') }}" style="margin-top: 10px"><span style="width: {{ $p['consumed_pct'] }}%"></span></div>
        @endif
    </div>
</div>

<div @class(['verdict', 'is-'.$p['risk']])>
    <span class="tag {{ $riskTag[$p['risk']] }}">{{ $p['risk_label'] }}</span>
    <p>{{ $p['explanation'] }}</p>
    <p class="verdict-action"><b>Que faire ?</b> {{ $p['action'] }}</p>
</div>

@if ($hasChart)
<section class="panel">
    <div class="panel-head">
        <div>
            <h2>Températures pendant le transport</h2>
            <p class="muted small">Chaque palier est un relevé. Au-dessus de {{ Fmt::n($m['reference_temp'], 1) }} °C, le produit vieillit plus vite ; au-dessus de {{ Fmt::n($m['critical_temp'], 1) }} °C, un contrôle qualité est conseillé.</p>
        </div>
    </div>
    <div class="chart-wrap">
        <svg viewBox="0 0 {{ $W }} {{ $H }}" class="chart" role="img" aria-label="Courbe des températures du lot {{ $p['lot'] }}">
            {{-- Zones : froid / plus chaud que prévu / critique --}}
            <rect x="{{ $L }}" y="{{ $y($tMax) }}" width="{{ $W - $L - $R }}" height="{{ max(0, $y($m['critical_temp']) - $y($tMax)) }}" class="z-hot"/>
            <rect x="{{ $L }}" y="{{ $y($m['critical_temp']) }}" width="{{ $W - $L - $R }}" height="{{ max(0, $y($m['reference_temp']) - $y($m['critical_temp'])) }}" class="z-warm"/>

            @for ($v = $tMin; $v <= $tMax; $v += $tickStep)
                <line x1="{{ $L }}" x2="{{ $W - $R }}" y1="{{ $y($v) }}" y2="{{ $y($v) }}" class="c-grid"/>
                <text x="{{ $L - 8 }}" y="{{ $y($v) + 4 }}" text-anchor="end">{{ Fmt::n($v) }} °C</text>
            @endfor

            @php $firstTick = true; @endphp
            @for ($ts = (int) (ceil($t0 / ($hourStep * 3600)) * $hourStep * 3600); $ts <= $t1; $ts += $hourStep * 3600)
                <line x1="{{ $x($ts) }}" x2="{{ $x($ts) }}" y1="{{ $T }}" y2="{{ $H - $B }}" class="c-grid soft"/>
                <text x="{{ $x($ts) }}" y="{{ $H - 22 }}" text-anchor="middle">{{ CarbonImmutable::createFromTimestamp($ts)->format('H\hi') }}</text>
                @if ($firstTick || CarbonImmutable::createFromTimestamp($ts)->format('H') === '00')
                    <text x="{{ $x($ts) }}" y="{{ $H - 8 }}" text-anchor="middle" class="c-day">{{ CarbonImmutable::createFromTimestamp($ts)->format('d/m') }}</text>
                @endif
                @php $firstTick = false; @endphp
            @endfor

            <line x1="{{ $L }}" x2="{{ $W - $R }}" y1="{{ $y($m['reference_temp']) }}" y2="{{ $y($m['reference_temp']) }}" class="c-ref"/>
            <text x="{{ $W - $R - 4 }}" y="{{ $y($m['reference_temp']) - 6 }}" text-anchor="end" class="c-ref-label">Stockage prévu {{ Fmt::n($m['reference_temp'], 1) }} °C</text>
            <line x1="{{ $L }}" x2="{{ $W - $R }}" y1="{{ $y($m['critical_temp']) }}" y2="{{ $y($m['critical_temp']) }}" class="c-crit"/>
            <text x="{{ $W - $R - 4 }}" y="{{ $y($m['critical_temp']) - 6 }}" text-anchor="end" class="c-crit-label">Critique {{ Fmt::n($m['critical_temp'], 1) }} °C</text>

            @foreach ($segments as $i => $s)
                @if ($i > 0)
                    <line x1="{{ $x($s['start']->timestamp) }}" x2="{{ $x($s['start']->timestamp) }}" y1="{{ $y($segments[$i - 1]['temperature']) }}" y2="{{ $y($s['temperature']) }}" class="c-step"/>
                @endif
                <line x1="{{ $x($s['start']->timestamp) }}" x2="{{ $x($s['end']->timestamp) }}" y1="{{ $y($s['temperature']) }}" y2="{{ $y($s['temperature']) }}" class="c-seg {{ $zone($s['temperature']) }}">
                    <title>{{ $s['location'] }} · {{ $s['start']->format('d/m H:i') }} · {{ Fmt::n($s['minutes'] / 60, 1) }} h à {{ Fmt::n($s['temperature'], 1) }} °C · vieillit ×{{ Fmt::n($s['rate'], 1) }} · {{ Fmt::n($s['lost_hours'], 1) }} h perdues</title>
                </line>
                @if ($s['lost_hours'] >= 1)
                    <text x="{{ ($x($s['start']->timestamp) + $x($s['end']->timestamp)) / 2 }}" y="{{ $y($s['temperature']) - 9 }}" text-anchor="middle" class="c-lost">−{{ Fmt::n($s['lost_hours']) }} h</text>
                @endif
            @endforeach
        </svg>
    </div>
    <p class="legend small">
        <span><i class="sw cold"></i>Au froid prévu</span>
        <span><i class="sw warm"></i>Plus chaud : vieillit plus vite</span>
        <span><i class="sw hot"></i>Au-dessus du seuil critique</span>
        <span class="muted">Survolez un palier pour le détail.</span>
    </p>
</section>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2>Le calcul, relevé par relevé</h2>
            <p class="muted small">Modèle de Ratkowsky · profil « {{ $p['profile_label'] }} » : T° de référence {{ Fmt::n($m['reference_temp'], 1) }} °C, Tmin {{ Fmt::n($m['t_min'], 1) }} °C. Vitesse = ((T − Tmin) / (Tréf − Tmin))².</p>
        </div>
    </div>
    <div class="table-wrap">
        <table class="calc">
            <thead><tr><th>Où</th><th>Début</th><th class="num">Durée</th><th class="num">T°</th><th class="num">Vieillit</th><th class="num">Heures perdues</th></tr></thead>
            <tbody>
                @foreach ($segments as $s)
                    <tr @class(['is-hot' => $s['critical'], 'is-warm' => ! $s['critical'] && $s['above_reference']])>
                        <td>{{ $s['location'] ?? '—' }}</td>
                        <td class="muted">{{ $s['start']->format('d/m H:i') }}</td>
                        <td class="num">{{ Fmt::n($s['minutes'] / 60, 1) }} h</td>
                        <td class="num"><b>{{ Fmt::n($s['temperature'], 1) }} °C</b></td>
                        <td class="num">× {{ Fmt::n($s['rate'], 2) }}</td>
                        <td class="num">{{ $s['lost_hours'] > 0 ? Fmt::n($s['lost_hours'], 1).' h' : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr><td colspan="5"><b>Total</b> → DLC imprimée {{ $printed->format('d/m') }} − {{ Fmt::n($p['lost_hours'], 1) }} h = <b>DLC réelle {{ $predicted->format('d/m/Y') }}</b></td><td class="num"><b>{{ Fmt::n($p['lost_hours'], 1) }} h</b></td></tr>
            </tfoot>
        </table>
    </div>
    @if ($worst && $worst['lost_hours'] > 0)
        <p class="formula small">
            Exemple, pire épisode : (({{ Fmt::n($worst['temperature'], 1) }} − ({{ Fmt::n($m['t_min'], 1) }})) / ({{ Fmt::n($m['reference_temp'], 1) }} − ({{ Fmt::n($m['t_min'], 1) }})))² = <b>× {{ Fmt::n($worst['rate'], 2) }}</b>.
            Pendant {{ Fmt::n($worst['minutes'] / 60, 1) }} h, le produit a vieilli comme en {{ Fmt::n($worst['rate'] * $worst['minutes'] / 60, 1) }} h au froid : {{ Fmt::n($worst['lost_hours'], 1) }} h perdues.
        </p>
    @endif
</section>
@else
<section class="panel">
    <p class="muted">Aucun relevé de température pour ce lot dans le module Chaîne du froid : la DLC imprimée est conservée.</p>
</section>
@endif

<div class="two-cols">
    <section class="panel">
        <div class="panel-head">
            <div>
                <h2>Où est ce lot ?</h2>
                <p class="muted small">Stock actuel dans les sites du module.</p>
            </div>
        </div>
        @if ($lines->isEmpty())
            <p class="muted">Ce lot n’est plus en stock dans le module.</p>
        @else
            <ul class="where">
                @foreach ($lines as $line)
                    <li>
                        <span><b>{{ $line->site?->name }}</b> <span class="muted small">{{ $line->site?->city }}</span></span>
                        <span>{{ Fmt::q($line->quantity) }} u.</span>
                    </li>
                @endforeach
            </ul>
            @if ($shorter && $p['remaining_days'] >= 0)
                <a class="btn btn-primary" href="{{ route('optimization.create', ['batch_id' => $batch->id]) }}">Trouver la meilleure destination avant le {{ $predicted->format('d/m') }} →</a>
            @endif
        @endif
    </section>

    <section class="panel">
        <div class="panel-head">
            <div>
                <h2>Décision du responsable</h2>
                <p class="muted small">
                    Le module utilise actuellement la DLC du <b>{{ CarbonImmutable::parse($p['effective'])->format('d/m/Y') }}</b>
                    {{ $p['effective'] === $p['predicted'] && $shorter ? '(DLC réelle)' : '(DLC imprimée)' }}.
                </p>
            </div>
        </div>
        @if ($shorter)
            <form method="POST" action="{{ route('shelf-life.decide', $batch) }}" class="decide">
                @csrf
                <label for="note">Commentaire <span class="muted">(obligatoire pour garder la DLC imprimée)</span></label>
                <textarea id="note" name="note" rows="2" maxlength="1000" placeholder="Ex. : contrôle qualité conforme le 01/11, pH 4,4">{{ old('note') }}</textarea>
                @error('note')<p class="error">{{ $message }}</p>@enderror
                <div class="actions">
                    <button class="btn btn-primary" type="submit" name="decision" value="{{ ShelfLifeAssessment::ACCEPTED }}">✓ Valider la DLC réelle</button>
                    <button class="btn btn-ghost" type="submit" name="decision" value="{{ ShelfLifeAssessment::REJECTED }}">Garder la DLC imprimée</button>
                </div>
            </form>
        @else
            <p class="muted">Aucune décision nécessaire : la DLC imprimée reste valable.</p>
        @endif

        @if ($history->isNotEmpty())
            <h3 class="hist-title">Historique</h3>
            <ul class="hist">
                @foreach ($history as $h)
                    <li>
                        <span @class(['tag', 'tag-accepted' => $h->decision === ShelfLifeAssessment::ACCEPTED, 'tag-rejected' => $h->decision === ShelfLifeAssessment::REJECTED])>{{ $h->decisionLabel() }}</span>
                        <span class="small">{{ $h->created_at->format('d/m/Y H:i') }} · {{ $h->user?->name ?? '—' }} · DLC réelle {{ $h->predicted_expiry->format('d/m') }} ({{ Fmt::n($h->days_lost, 1) }} j perdus)</span>
                        @if ($h->note)<div class="muted small">« {{ $h->note }} »</div>@endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
@endsection

@push('styles')
<style>
    .strike { text-decoration: line-through; color: var(--ink-soft) !important; }
    .verdict { display: grid; gap: 8px; padding: 16px 20px; border-radius: 16px; margin-bottom: 20px; border: 1px solid var(--rule); background: var(--pine-wash); line-height: 1.6; }
    .verdict.is-critical { background: var(--brick-wash); border-color: #efc1ba; }
    .verdict.is-attention { background: var(--amber-wash); border-color: #efd29a; }
    .verdict p { margin: 0; }
    .verdict .tag { justify-self: start; }
    .verdict-action { font-size: .93rem; }
    .chart-wrap { overflow-x: auto; }
    .chart { width: 100%; min-width: 640px; height: auto; display: block; }
    .chart text { font: 500 11px var(--font); fill: var(--ink-soft); }
    .chart .c-day { font-weight: 700; fill: var(--ink); }
    .chart .c-grid { stroke: var(--rule); stroke-width: 1; }
    .chart .c-grid.soft { stroke-dasharray: 2 4; }
    .chart .z-warm { fill: rgba(193, 120, 23, .08); }
    .chart .z-hot { fill: rgba(178, 58, 58, .08); }
    .chart .c-ref { stroke: #2d6a4f; stroke-width: 1.5; stroke-dasharray: 6 5; }
    .chart .c-crit { stroke: var(--brick); stroke-width: 1.5; stroke-dasharray: 6 5; }
    .chart .c-ref-label { fill: #2d6a4f; font-weight: 700; }
    .chart .c-crit-label { fill: var(--brick); font-weight: 700; }
    .chart .c-step { stroke: #8a9a95; stroke-width: 1.5; }
    .chart .c-seg { stroke-width: 6; stroke-linecap: round; }
    .chart .c-seg.cold { stroke: #245d8a; }
    .chart .c-seg.warm { stroke: #c48a1a; }
    .chart .c-seg.hot { stroke: #a3261d; }
    .chart .c-lost { fill: var(--brick); font-weight: 700; }
    .legend { display: flex; flex-wrap: wrap; gap: 6px 18px; margin-top: 8px; color: var(--ink-soft); }
    .sw { display: inline-block; width: 22px; height: 6px; border-radius: 3px; margin-right: 6px; vertical-align: 2px; }
    .sw.cold { background: #245d8a; } .sw.warm { background: #c48a1a; } .sw.hot { background: #a3261d; }
    .calc td.num, .calc th.num { text-align: right; white-space: nowrap; }
    .calc tr.is-hot td { background: var(--brick-wash); }
    .calc tr.is-warm td { background: var(--amber-wash); }
    .calc tfoot td { border-top: 2px solid var(--rule); }
    .formula { margin: 14px 0 0; padding: 10px 14px; border-radius: 10px; background: var(--pine-wash); line-height: 1.6; }
    .two-cols { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; }
    .two-cols .panel { margin-bottom: 0; }
    .where { list-style: none; margin: 0 0 14px; padding: 0; display: grid; gap: 8px; }
    .where li { display: flex; justify-content: space-between; gap: 12px; padding: 8px 0; border-bottom: 1px solid var(--rule); }
    .decide { display: grid; gap: 8px; }
    .decide .actions { margin-top: 4px; }
    .hist-title { margin: 18px 0 8px; font-size: .95rem; }
    .hist { list-style: none; margin: 0; padding: 0; display: grid; gap: 10px; }
</style>
@endpush
