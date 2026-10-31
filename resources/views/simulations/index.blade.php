@extends('layouts.stock')
@use('App\Support\Fmt')

@section('eyebrow', 'Digital twin')
@section('title', 'Jumeau numérique « Et si ? »')
@section('description', 'Copie de vos stocks réels d’aujourd’hui, rejouée sur les jours à venir avec un incident : comparez ce qui se passe sans IA et avec les décisions de l’IA. Les stocks réels ne sont jamais modifiés.')

@section('content')
<form method="POST" action="{{ route('simulations.store') }}" id="sim-form">
    @csrf

    <section class="panel">
        <div class="panel-head">
            <div>
                <h2>1. Choisissez un scénario</h2>
                <p class="muted small">Chaque scénario remplit les réglages ci-dessous ; vous pouvez ensuite les modifier.</p>
            </div>
        </div>

        <div class="presets" role="radiogroup" aria-label="Scénario">
            @foreach ($presets as $key => $preset)
                <label class="preset">
                    <input type="radio" name="preset" value="{{ $key }}" data-values='@json($preset['values'])'
                        @checked(old('preset', 'normal') === $key)>
                    <span class="preset-card">
                        <span class="preset-icon" aria-hidden="true">{{ $preset['icon'] }}</span>
                        <b>{{ $preset['label'] }}</b>
                        <span class="muted small">{{ $preset['text'] }}</span>
                    </span>
                </label>
            @endforeach
        </div>
    </section>

    <section class="panel">
        <div class="panel-head">
            <div>
                <h2>2. Réglez les événements</h2>
                <p class="muted small">Les jours sont comptés à partir d’aujourd’hui (jour 1). Laissez vide ce qui ne doit pas arriver.</p>
            </div>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="name">Nom de la simulation <span class="muted">(facultatif)</span></label>
                <input id="name" name="name" value="{{ old('name') }}" maxlength="120" placeholder="Ex. : Ramadan 2027 à Sousse">
            </div>
            <div class="field">
                <label for="horizon">Durée simulée</label>
                <select id="horizon" name="horizon">
                    @foreach ([7, 14, 21, 30] as $h)
                        <option value="{{ $h }}" @selected((int) old('horizon', 14) === $h)>{{ $h }} jours</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="events">
            <fieldset class="event">
                <legend>📈 Variation de la demande</legend>
                <div class="field">
                    <label for="demand_change">Variation (%)</label>
                    <input type="number" id="demand_change" name="demand_change" value="{{ old('demand_change') }}" min="-90" max="200" step="5" placeholder="0">
                    <span class="hint">+35 = 35 % de ventes en plus ; -30 = 30 % en moins.</span>
                </div>
                <div class="field">
                    <label for="demand_product_id">Produit concerné</label>
                    <select id="demand_product_id" name="demand_product_id">
                        <option value="">Tous les produits</option>
                        @foreach ($products as $p)
                            <option value="{{ $p->id }}" @selected((int) old('demand_product_id') === $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field-pair">
                    <div class="field">
                        <label for="demand_from">Du jour</label>
                        <input type="number" id="demand_from" name="demand_from" value="{{ old('demand_from') }}" min="1" max="30" placeholder="1">
                    </div>
                    <div class="field">
                        <label for="demand_to">Au jour</label>
                        <input type="number" id="demand_to" name="demand_to" value="{{ old('demand_to') }}" min="1" max="30" placeholder="fin">
                    </div>
                </div>
            </fieldset>

            <fieldset class="event">
                <legend>🚚 Retard des camions</legend>
                <div class="field">
                    <label for="transport_delay">Retard (jours)</label>
                    <input type="number" id="transport_delay" name="transport_delay" value="{{ old('transport_delay') }}" min="0" max="7" placeholder="0">
                    <span class="hint">Une livraison met normalement 1 jour.</span>
                </div>
                <div class="field">
                    <label for="transport_from">À partir du jour</label>
                    <input type="number" id="transport_from" name="transport_from" value="{{ old('transport_from') }}" min="1" max="30" placeholder="1">
                </div>
            </fieldset>

            <fieldset class="event">
                <legend>🏪 Site fermé</legend>
                <div class="field">
                    <label for="closed_site_id">Site</label>
                    <select id="closed_site_id" name="closed_site_id">
                        <option value="">Aucun</option>
                        @foreach ($sites as $s)
                            <option value="{{ $s->id }}" @selected((int) old('closed_site_id') === $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field-pair">
                    <div class="field">
                        <label for="closed_from">Du jour</label>
                        <input type="number" id="closed_from" name="closed_from" value="{{ old('closed_from') }}" min="1" max="30" placeholder="2">
                    </div>
                    <div class="field">
                        <label for="closed_days">Pendant (jours)</label>
                        <input type="number" id="closed_days" name="closed_days" value="{{ old('closed_days') }}" min="1" max="30" placeholder="3">
                    </div>
                </div>
            </fieldset>

            <fieldset class="event">
                <legend>🌡️ Rupture de la chaîne du froid</legend>
                <div class="field">
                    <label for="cold_site_id">Site touché</label>
                    <select id="cold_site_id" name="cold_site_id">
                        <option value="">Aucun</option>
                        @foreach ($sites as $s)
                            <option value="{{ $s->id }}" @selected((int) old('cold_site_id') === $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field-pair">
                    <div class="field">
                        <label for="cold_day">Jour</label>
                        <input type="number" id="cold_day" name="cold_day" value="{{ old('cold_day') }}" min="1" max="30" placeholder="2">
                    </div>
                    <div class="field">
                        <label for="cold_lost_days">DLC perdue (jours)</label>
                        <input type="number" id="cold_lost_days" name="cold_lost_days" value="{{ old('cold_lost_days') }}" min="1" max="14" placeholder="3">
                    </div>
                </div>
            </fieldset>
        </div>

        <div class="form-foot">
            <button class="btn btn-primary" type="submit">▶ Lancer la simulation</button>
            <span class="hint align-self-center">Deux stratégies sont rejouées avec la même demande et les mêmes incidents.</span>
        </div>
    </section>
</form>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2>Simulations précédentes</h2>
            <p class="muted small">Chaque résultat est enregistré : vous pouvez le rouvrir pour la soutenance.</p>
        </div>
    </div>

    @if ($history->isEmpty())
        <p class="muted">Aucune simulation pour l’instant : lancez la première ci-dessus.</p>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Simulation</th>
                        <th>Date</th>
                        <th class="num">Jeté sans IA</th>
                        <th class="num">Jeté avec IA</th>
                        <th class="num">Ventes perdues (sans → avec)</th>
                        <th class="num">Dons</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($history as $sim)
                        <tr>
                            <td>
                                <a class="code" href="{{ route('simulations.show', $sim) }}">{{ $sim->name }}</a>
                                <div class="muted small">{{ $sim->presetLabel() }} · {{ $sim->horizon_days }} jours</div>
                            </td>
                            <td class="muted">{{ $sim->created_at->format('d/m/Y H:i') }}<div class="small">{{ $sim->user?->name }}</div></td>
                            <td class="num">{{ Fmt::n($sim->baseline_wasted) }}</td>
                            <td class="num">
                                {{ Fmt::n($sim->ai_wasted) }}
                                @if ($sim->wasteReduction() !== null)
                                    <span class="tag tag-ok">-{{ $sim->wasteReduction() }} %</span>
                                @endif
                            </td>
                            <td class="num">{{ Fmt::n($sim->baseline_unmet) }} → {{ Fmt::n($sim->ai_unmet) }}</td>
                            <td class="num">{{ Fmt::n($sim->ai_donated) }}</td>
                            <td class="row-actions">
                                <a href="{{ route('simulations.show', $sim) }}">Voir</a>
                                <form method="POST" action="{{ route('simulations.destroy', $sim) }}" onsubmit="return confirm('Supprimer cette simulation ?')">
                                    @csrf @method('DELETE')
                                    <button class="linklike" type="submit">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pager">{{ $history->links() }}</div>
    @endif
</section>
@endsection

@push('styles')
<style>
    .presets { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; }
    .preset { position: relative; cursor: pointer; }
    .preset input { position: absolute; opacity: 0; pointer-events: none; }
    .preset-card { display: grid; gap: 6px; height: 100%; padding: 14px 16px; border: 1.5px solid var(--rule); border-radius: 14px; background: #fff; transition: border-color .15s, box-shadow .15s; }
    .dark .preset-card { background: #152a20; border-color: rgba(255,255,255,.12); }
    .preset:hover .preset-card { border-color: rgba(193, 120, 23, .5); }
    .preset input:checked + .preset-card { border-color: var(--amber); box-shadow: 0 0 0 3px var(--amber-wash); }
    .preset input:focus-visible + .preset-card { outline: 2px solid var(--amber); outline-offset: 2px; }
    .preset-icon { font-size: 1.5rem; line-height: 1; }
    .events { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; margin-top: 20px; }
    .event { border: 1px solid var(--rule); border-radius: 14px; padding: 12px 16px 16px; display: grid; gap: 12px; margin: 0; min-width: 0; }
    .dark .event { border-color: rgba(255,255,255,.1); }
    .event legend { padding: 0 6px; font-weight: 700; }
    .field-pair { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .align-self-center { align-self: center; }
    .pager { margin-top: 14px; }
</style>
@endpush

@push('scripts')
<script>
    // Un scénario prêt à l'emploi remplit les champs (vide les autres) ; « personnalisé » ne touche à rien.
    (() => {
        const form = document.getElementById('sim-form');
        const fields = ['demand_change', 'demand_product_id', 'demand_from', 'demand_to', 'transport_delay', 'transport_from',
            'closed_site_id', 'closed_from', 'closed_days', 'cold_site_id', 'cold_day', 'cold_lost_days'];
        form.querySelectorAll('input[name="preset"]').forEach((radio) => {
            radio.addEventListener('change', () => {
                if (!radio.checked || radio.value === 'custom') return;
                const values = JSON.parse(radio.dataset.values || '{}');
                fields.forEach((name) => {
                    const el = form.elements[name];
                    if (el) el.value = values[name] ?? '';
                });
            });
        });
        // Modifier un réglage à la main bascule sur « personnalisé »
        fields.forEach((name) => form.elements[name]?.addEventListener('input', (e) => {
            if (e.isTrusted) form.querySelector('input[name="preset"][value="custom"]').checked = true;
        }));
    })();
</script>
@endpush
