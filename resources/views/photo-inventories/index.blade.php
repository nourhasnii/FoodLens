@extends('layouts.stock')
@use('App\Support\Fmt')

@section('eyebrow', 'Photo inventory')
@section('title', 'Inventaire par photo')
@section('description', 'Prenez en photo un rayon ou une palette : l’IA de vision compte les produits, vous vérifiez, et les écarts deviennent des ajustements de stock tracés.')

@section('content')
<section class="panel">
    <div class="panel-head">
        <div>
            <h2>Nouvelle photo</h2>
            <p class="muted small">Analyse locale par le modèle de vision <b>{{ $model }}</b> (Ollama). Aucune photo n’est envoyée sur Internet. L’analyse peut prendre une minute.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('photo-inventories.store') }}" enctype="multipart/form-data" id="photo-form">
        @csrf
        <div class="form-grid">
            <div class="field">
                <label for="site_id">Site inventorié</label>
                <select id="site_id" name="site_id" required>
                    <option value="">Choisir un site…</option>
                    @foreach ($sites as $s)
                        <option value="{{ $s->id }}" @selected((int) old('site_id') === $s->id)>{{ $s->name }}{{ $s->city ? ' · '.$s->city : '' }}</option>
                    @endforeach
                </select>
                <span class="hint">L’IA cherche les produits stockés sur ce site.</span>
            </div>
            <div class="field">
                <label for="photo">Photo du rayon ou de la palette</label>
                <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp" capture="environment" required>
                <span class="hint">JPG, PNG ou WebP, {{ (int) (config('stock.photo_inventory.max_photo_kb') / 1024) }} Mo maximum. Sur téléphone, l’appareil photo s’ouvre directement.</span>
            </div>
        </div>

        <figure class="preview" id="photo-preview" hidden>
            <img alt="Aperçu de la photo choisie">
        </figure>

        <div class="tips">
            <b>Pour un bon comptage :</b> photo de face, bien éclairée, étiquettes visibles, une seule étagère ou palette à la fois.
        </div>

        <div class="form-foot">
            <button class="btn btn-primary" type="submit" id="photo-submit">📷 Analyser la photo</button>
            <span class="hint align-self-center" id="photo-wait" hidden>Analyse en cours… ne fermez pas la page.</span>
        </div>
    </form>
</section>

<section class="panel">
    <div class="panel-head">
        <div>
            <h2>Inventaires précédents</h2>
            <p class="muted small">Chaque photo, le comptage de l’IA et le comptage validé sont conservés.</p>
        </div>
    </div>

    @if ($history->isEmpty())
        <p class="muted">Aucun inventaire par photo pour l’instant.</p>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Date</th><th>Site</th><th>État</th><th class="num">Produits</th><th class="num">Écarts</th><th class="num">Ajustements</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach ($history as $inv)
                        <tr>
                            <td>{{ $inv->created_at->format('d/m/Y H:i') }}<div class="muted small">{{ $inv->user?->name }}</div></td>
                            <td>{{ $inv->site?->name }}</td>
                            <td><span class="tag {{ $inv->statusTag() }}">{{ $inv->statusLabel() }}</span></td>
                            <td class="num">{{ count($inv->items ?? []) }}</td>
                            <td class="num">{{ $inv->discrepancies() }}</td>
                            <td class="num">{{ $inv->isApplied() ? $inv->adjustments_count : '—' }}</td>
                            <td class="row-actions"><a href="{{ route('photo-inventories.show', $inv) }}">{{ $inv->isApplied() ? 'Voir' : 'Vérifier' }}</a></td>
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
    .preview { margin: 18px 0 0; }
    .preview img { max-width: 100%; max-height: 320px; border-radius: 12px; border: 1px solid var(--rule); display: block; }
    .tips { margin-top: 16px; padding: 12px 14px; border-radius: 12px; background: var(--amber-wash); font-size: .88rem; line-height: 1.5; }
    .align-self-center { align-self: center; }
    .pager { margin-top: 14px; }
</style>
@endpush

@push('scripts')
<script>
    (() => {
        const input = document.getElementById('photo');
        const preview = document.getElementById('photo-preview');
        input.addEventListener('change', () => {
            const file = input.files[0];
            preview.hidden = !file;
            if (file) preview.querySelector('img').src = URL.createObjectURL(file);
        });
        document.getElementById('photo-form').addEventListener('submit', () => {
            const btn = document.getElementById('photo-submit');
            btn.disabled = true;
            btn.textContent = '⏳ Analyse en cours…';
            document.getElementById('photo-wait').hidden = false;
        });
    })();
</script>
@endpush
