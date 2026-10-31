@extends('layouts.stock')
@use('App\Support\Fmt')
@use('App\Models\PhotoInventory')

@php
    $items = $inventory->items ?? [];
    $applied = $inventory->isApplied();
    $failed = $inventory->status === PhotoInventory::FAILED;
@endphp

@section('eyebrow', 'Photo inventory · '.$inventory->statusLabel())
@section('title', 'Inventaire photo #'.$inventory->id.' · '.$inventory->site->name)
@section('description', 'Photo du '.$inventory->created_at->format('d/m/Y à H:i').($inventory->user ? ' par '.$inventory->user->name : '').'. L’IA propose un comptage ; vous le corrigez si besoin, puis vous l’appliquez.')

@section('page-action')
    <a class="btn btn-ghost" href="{{ route('photo-inventories.index') }}">← Nouvelle photo</a>
@endsection

@section('content')
<div class="pi-grid">
    <section class="panel pi-photo">
        <h2>Photo</h2>
        <a href="{{ route('photo-inventories.photo', $inventory) }}" target="_blank" rel="noopener">
            <img src="{{ route('photo-inventories.photo', $inventory) }}" alt="Photo du rayon inventorié à {{ $inventory->site->name }}">
        </a>
        <dl class="pi-meta small">
            <div><dt>Modèle</dt><dd>{{ $inventory->ai_model ?? '—' }}</dd></div>
            <div><dt>Durée d’analyse</dt><dd>{{ $inventory->duration_ms ? Fmt::n($inventory->duration_ms / 1000, 1).' s' : '—' }}</dd></div>
            @if ($applied)
                <div><dt>Appliqué</dt><dd>{{ $inventory->applied_at->format('d/m/Y H:i') }}{{ $inventory->applier ? ' par '.$inventory->applier->name : '' }}</dd></div>
            @endif
        </dl>
    </section>

    <section class="panel">
        <div class="panel-head">
            <div>
                <h2>Comptage</h2>
                <p class="muted small">
                    @if ($applied)
                        Comptage validé : {{ $inventory->adjustments_count }} mouvement(s) d’ajustement enregistré(s).
                    @elseif ($failed)
                        Saisissez la quantité comptée pour chaque produit. Laissez vide un produit que vous n’avez pas compté.
                    @else
                        Quantités proposées par l’IA, pré-remplies. Corrigez-les si besoin : c’est vous qui validez.
                    @endif
                </p>
            </div>
            @unless ($applied)
                <form method="POST" action="{{ route('photo-inventories.analyze', $inventory) }}" class="retry-form">
                    @csrf
                    <button class="btn btn-ghost" type="submit">↻ Relancer l’analyse IA</button>
                </form>
            @endunless
        </div>

        @if ($failed && $inventory->error)
            <div class="notice notice-error pi-notice" role="alert">{{ $inventory->error }}</div>
        @endif

        @if ($inventory->ai_notes)
            <p class="ai-notes"><b>Remarque de l’IA :</b> {{ $inventory->ai_notes }}</p>
        @endif

        @if (empty($items))
            <p class="muted">Aucun produit n’est stocké sur ce site : rien à comparer.</p>
        @else
            <form method="POST" action="{{ route('photo-inventories.apply', $inventory) }}" id="apply-form">
                @csrf
                <div class="table-wrap">
                    <table class="pi-table">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th class="num">Stock enregistré</th>
                                <th class="num">Compté par l’IA</th>
                                <th class="num">Quantité retenue</th>
                                <th class="num">Écart</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                @php
                                    $proposed = $applied ? $item['final_qty'] : old('counts.'.$item['product_id'], $item['ai_count']);
                                    $low = $item['confidence'] !== null && $item['confidence'] < $lowConfidence;
                                @endphp
                                <tr data-system="{{ $item['system_qty'] }}">
                                    <td>
                                        <b>{{ $item['name'] }}</b>
                                        @if ($item['ai_label'] && $item['ai_label'] !== $item['name'])
                                            <div class="muted small">Lu par l’IA : « {{ $item['ai_label'] }} »</div>
                                        @endif
                                    </td>
                                    <td class="num">{{ Fmt::q($item['system_qty']) }} {{ $item['unit'] }}</td>
                                    <td class="num">
                                        @if ($item['ai_count'] !== null)
                                            {{ Fmt::q($item['ai_count']) }}
                                            @if ($item['confidence'] !== null)
                                                <span @class(['tag', 'tag-expiring' => $low, 'tag-ok' => ! $low]) title="Confiance de l’IA">{{ (int) round($item['confidence'] * 100) }} %{{ $low ? ' · à vérifier' : '' }}</span>
                                            @endif
                                        @else
                                            <span class="muted">{{ $failed ? '—' : 'non vu' }}</span>
                                        @endif
                                    </td>
                                    <td class="num">
                                        @if ($applied)
                                            {{ $item['final_qty'] !== null ? Fmt::q($item['final_qty']) : '—' }}
                                        @else
                                            <input type="number" class="count-input" name="counts[{{ $item['product_id'] }}]" value="{{ $proposed }}" min="0" step="any" inputmode="decimal" aria-label="Quantité comptée pour {{ $item['name'] }}" placeholder="non compté">
                                        @endif
                                    </td>
                                    <td class="num delta-cell"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @unless ($applied)
                    <div class="form-foot">
                        <button class="btn btn-primary" type="submit" onclick="return confirm('Enregistrer les écarts comme ajustements de stock ?')">✓ Valider et ajuster le stock</button>
                        <span class="hint align-self-center">Chaque écart crée un mouvement « Ajustement » visible dans l’historique des mouvements.</span>
                    </div>
                @endunless
            </form>
        @endif

        @if (! empty($inventory->unmatched))
            <div class="unmatched">
                <h3>Vu par l’IA mais pas en stock sur ce site</h3>
                <ul>
                    @foreach ($inventory->unmatched as $u)
                        <li>{{ $u['product'] }} : {{ Fmt::q($u['count']) }}{{ $u['confidence'] !== null ? ' (confiance '.(int) round($u['confidence'] * 100).' %)' : '' }}</li>
                    @endforeach
                </ul>
                <p class="muted small">À vérifier : produit mal rangé, ou réception pas encore enregistrée (faites un mouvement d’entrée).</p>
            </div>
        @endif
    </section>
</div>

<section class="panel">
    <h2>Comment ça marche ?</h2>
    <ol class="how">
        <li><b>Vision.</b> La photo est envoyée au modèle de vision local (Ollama), avec la liste des produits stockés sur ce site.</li>
        <li><b>Comptage + confiance.</b> L’IA renvoie un JSON : produit, nombre d’unités, confiance. En dessous de {{ (int) ($lowConfidence * 100) }} %, la ligne est marquée « à vérifier ».</li>
        <li><b>Rapprochement.</b> Les noms lus sur la photo sont associés aux produits du site par similarité de texte (sans accents ni majuscules).</li>
        <li><b>Validation humaine.</b> L’IA propose, l’utilisateur décide : rien ne change dans le stock avant la validation.</li>
        <li><b>Traçabilité.</b> Chaque écart devient un mouvement d’ajustement (motif « Inventaire photo #{{ $inventory->id }} ») ; un manque est retiré d’abord du stock sans lot, puis des lots les plus proches de leur DLC.</li>
    </ol>
</section>
@endsection

@push('styles')
<style>
    .pi-grid { display: grid; grid-template-columns: minmax(240px, 2fr) minmax(0, 3fr); gap: 20px; align-items: start; }
    @media (max-width: 900px) { .pi-grid { grid-template-columns: 1fr; } }
    .pi-grid .panel { margin-bottom: 0; }
    .pi-photo img { width: 100%; max-height: 460px; object-fit: contain; border-radius: 12px; border: 1px solid var(--rule); background: var(--slot); margin-top: 10px; display: block; }
    .pi-meta { display: grid; gap: 6px; margin-top: 12px; }
    .pi-meta div { display: flex; justify-content: space-between; gap: 12px; }
    .pi-meta dt { color: var(--ink-soft); }
    .pi-meta dd { margin: 0; font-weight: 600; }
    .pi-notice { margin-bottom: 14px; }
    .ai-notes { margin: 0 0 14px; padding: 10px 14px; border-radius: 10px; background: var(--pine-wash); font-size: .9rem; }
    .pi-table td.num, .pi-table th.num { text-align: right; white-space: nowrap; }
    .pi-table td:first-child { min-width: 190px; }
    .count-input { width: 110px; text-align: right; }
    .delta-cell b.is-down { color: var(--brick); }
    .delta-cell b.is-up { color: #2d6a4f; }
    .unmatched { margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--rule); }
    .unmatched h3 { font-size: .95rem; margin-bottom: 6px; }
    .unmatched ul { margin: 0 0 6px; padding-left: 20px; }
    .retry-form { margin: 0; }
    .align-self-center { align-self: center; }
    .how { margin: 8px 0 0; padding-left: 20px; display: grid; gap: 8px; line-height: 1.55; }
</style>
@endpush

@push('scripts')
<script>
    // Écart en direct : quantité retenue - stock enregistré
    (() => {
        const fmt = (v) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 }).format(v);
        document.querySelectorAll('.pi-table tbody tr').forEach((row) => {
            const system = Number(row.dataset.system);
            const input = row.querySelector('.count-input');
            const cell = row.querySelector('.delta-cell');
            const update = () => {
                const raw = input ? input.value : (row.children[3].textContent.trim().replace(/\s/g, '').replace(',', '.'));
                if (raw === '' || raw === '—' || isNaN(Number(raw))) { cell.innerHTML = '<span class="muted">—</span>'; return; }
                const d = Math.round((Number(raw) - system) * 100) / 100;
                cell.innerHTML = d === 0 ? '<span class="muted">=</span>' : `<b class="${d < 0 ? 'is-down' : 'is-up'}">${d > 0 ? '+' : ''}${fmt(d)}</b>`;
            };
            input?.addEventListener('input', update);
            update();
        });
    })();
</script>
@endpush
