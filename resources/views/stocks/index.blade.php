@extends('layouts.stock')
@use('App\Support\Fmt')
@use('App\Support\BatchAttributes')
@use('App\Models\Stock')

@section('eyebrow', 'Inventory workspace')
@section('title', 'Stocks par site')
@section('description', 'Quantités par produit et par lot sur chaque site actif. Les lots rappelés ou périmés restent comptés en stock physique mais ne sont pas disponibles.')
@section('page-action')
    <a class="btn btn-ghost" href="{{ route('stock-movements.create') }}">Enregistrer un mouvement</a>
    <a class="btn btn-primary" href="{{ route('stocks.create') }}">Ajouter une ligne de stock</a>
@endsection

@section('content')
@include('partials.impact')

<div class="figures">
    <div class="figure"><strong>{{ $kpis['sites'] }}</strong><span>sites affichés</span></div>
    <div class="figure"><strong>{{ Fmt::q($kpis['quantity']) }}</strong><span>unités en stock</span></div>
    <div @class(['figure', 'is-alert' => $kpis['low'] > 0])><strong>{{ $kpis['low'] }}</strong><span>produits sous leur seuil</span></div>
    <div @class(['figure', 'is-warn' => $kpis['expiring'] > 0])><strong>{{ $kpis['expiring'] }}</strong><span>lignes à DLC proche ou dépassée</span></div>
</div>

@if ($low->isNotEmpty())
    <section class="notice notice-error" role="alert">
        <p class="notice-title">Risque de rupture</p>
        <ul>
            @foreach ($low as $row)
                <li>
                    <b>{{ $row->product?->name }}</b> à {{ $row->site?->name }} :
                    {{ Fmt::q($row->available) }} disponible(s) pour un seuil de {{ Fmt::q($row->threshold) }}.
                    <a href="{{ route('stock-movements.create', ['type' => 'transfer']) }}">Réapprovisionner</a>
                </li>
            @endforeach
        </ul>
    </section>
@endif

@if ($predicted->isNotEmpty())
    <section class="notice notice-warning">
        <p class="notice-title">Ruptures prévues dans les 7 prochains jours</p>
        <ul>
            @foreach ($predicted as $row)
                <li>
                    <b>{{ $row->product?->name }}</b> à {{ $row->site?->name }} :
                    {{ Fmt::q($row->available) }} u. en stock pour environ {{ Fmt::n($row->daily, 1) }} u. vendues par jour,
                    rupture prévue <b>{{ $row->days === 0 ? 'aujourd’hui' : $row->date->locale('fr')->isoFormat('dddd DD/MM') }}</b>.
                    <a href="{{ route('forecast.index', ['site_id' => $row->site?->id, 'product_id' => $row->product?->id]) }}">Voir la prévision</a>
                </li>
            @endforeach
        </ul>
    </section>
@endif

<form class="filters" method="GET" action="{{ route('stocks.index') }}">
    <div class="field">
        <label for="f-site">Site</label>
        <select id="f-site" name="site_id">
            <option value="">Tous les sites</option>
            @foreach ($allSites as $s)
                <option value="{{ $s->id }}" @selected(($filters['site_id'] ?? null) == $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label for="f-product">Produit</label>
        <select id="f-product" name="product_id">
            <option value="">Tous les produits</option>
            @foreach ($products as $p)
                <option value="{{ $p->id }}" @selected(($filters['product_id'] ?? null) == $p->id)>{{ $p->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label for="f-state">État</label>
        <select id="f-state" name="state">
            <option value="">Tous les états</option>
            @foreach (['low', 'expiring', 'expired', 'recalled', 'ok'] as $state)
                <option value="{{ $state }}" @selected(($filters['state'] ?? null) === $state)>{{ Stock::stateLabel($state) }}</option>
            @endforeach
        </select>
    </div>
    <button class="btn btn-ghost" type="submit">Filtrer</button>
    @if ($isFiltered)
        <a class="btn btn-ghost" href="{{ route('stocks.index') }}">Effacer les filtres</a>
    @endif
</form>

@forelse ($sites as $site)
    @php $lines = $linesBySite->get($site->id, collect()); @endphp
    @continue($isFiltered && $lines->isEmpty() && empty($filters['site_id']))

    <section class="panel">
        <div class="panel-head">
            <div>
                <h2>{{ $site->name }}</h2>
                <p class="muted small">{{ $site->type->label() }}@if ($site->city), {{ $site->city }}@endif. Code {{ $site->code }}</p>
            </div>
            @include('stocks._rack', ['used' => $site->usedCapacity(), 'capacity' => $site->capacity])
        </div>

        @if ($lines->isEmpty())
            <p class="empty">Aucun stock ne correspond sur ce site. <a href="{{ route('stocks.create', ['site_id' => $site->id]) }}">Ajouter une ligne</a></p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Lot</th>
                        <th>DLC</th>
                        <th class="num">Quantité</th>
                        <th class="num">Disponible</th>
                        <th class="num">Seuil</th>
                        <th>État</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($lines as $line)
                        @php
                            $expiry = BatchAttributes::expiryDate($line->batch);
                            // DLC dynamique : vraie date d'après les températures du transport
                            $realExpiry = $line->batch ? BatchAttributes::effectiveExpiryDate($line->batch) : null;
                        @endphp
                        <tr>
                            <td>{{ $line->product?->name ?? 'Produit supprimé' }}</td>
                            <td class="code">{{ $line->batch ? BatchAttributes::code($line->batch) : 'Sans lot' }}</td>
                            <td>
                                @if ($expiry && $realExpiry && $realExpiry->lt($expiry))
                                    <s class="muted">{{ $expiry->format('d/m/Y') }}</s>
                                    <a href="{{ route('shelf-life.show', $line->batch_id) }}" class="real-dlc" title="DLC réelle prédite d’après les températures du transport">{{ $realExpiry->format('d/m/Y') }} 🌡️</a>
                                @else
                                    {{ $expiry?->format('d/m/Y') ?? '—' }}
                                @endif
                            </td>
                            <td class="num">{{ Fmt::q($line->quantity) }}</td>
                            <td class="num">{{ Fmt::q($line->availableQuantity()) }}</td>
                            <td class="num">{{ Fmt::q($line->min_threshold) }}</td>
                            <td><span class="tag tag-{{ $line->display_state }}">{{ Stock::stateLabel($line->display_state) }}</span></td>
                            <td class="row-actions">
                                <a href="{{ route('stocks.show', $line) }}">Historique</a>
                                <a href="{{ route('stocks.edit', $line) }}">Seuil</a>
                                @if ($line->batch && $line->availableQuantity() > 0)
                                    <a href="{{ route('optimization.create', ['batch_id' => $line->batch_id]) }}">Optimiser</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@empty
    <div class="panel empty">
        <p>Aucun site actif. Déclarez vos entrepôts et magasins pour commencer à suivre les stocks.</p>
        <a class="btn btn-primary" href="{{ route('sites.create') }}">Ajouter un site</a>
    </div>
@endforelse
@endsection

@push('styles')
<style>
    .real-dlc { display: block; color: var(--brick); font-weight: 700; text-decoration: none; }
</style>
@endpush
