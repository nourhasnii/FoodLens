{{--
    Public traceability timeline (read-only).
    Expects: $batch (Batch with product + events loaded), optional $compact (bool).
    Renders a simplified stage stepper (Production → Transport → Stockage → Distribution)
    followed by the actual journey of the lot.
--}}
@php
    $compact = $compact ?? false;

    $labels = [
        'production' => 'Production',
        'processing' => 'Transformation',
        'transport' => 'Transport',
        'storage' => 'Stockage',
        'distribution' => 'Distribution',
    ];

    $styles = [
        'production' => ['dot' => 'bg-forest', 'text' => 'text-forest', 'panel' => 'bg-forest/5', 'icon' => 'M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5'],
        'processing' => ['dot' => 'bg-slate-500', 'text' => 'text-slate-600', 'panel' => 'bg-slate-50', 'icon' => 'M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83'],
        'transport' => ['dot' => 'bg-sky-600', 'text' => 'text-sky-600', 'panel' => 'bg-sky-50', 'icon' => 'M1 3h15v13H1zM16 8h4l3 3v5h-7zM5.5 21a2.5 2.5 0 100-5 2.5 2.5 0 000 5zM18.5 21a2.5 2.5 0 100-5 2.5 2.5 0 000 5z'],
        'storage' => ['dot' => 'bg-amber-warm', 'text' => 'text-amber-warm', 'panel' => 'bg-amber-50', 'icon' => 'M3 21V8l9-5 9 5v13M9 21v-6h6v6'],
        'distribution' => ['dot' => 'bg-emerald-600', 'text' => 'text-emerald-600', 'panel' => 'bg-emerald-50', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
    ];

    $stages = ['production', 'transport', 'storage', 'distribution'];
    $events = $batch->events;
    $present = $events->pluck('event_type')->unique();
@endphp

{{-- Simplified stage schema --}}
<div class="flex flex-wrap items-center gap-x-1 gap-y-2">
    @foreach ($stages as $stage)
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold {{ $present->contains($stage) ? 'bg-forest text-white' : 'bg-ink/5 text-ink/40' }}">
                <span class="h-1.5 w-1.5 rounded-full {{ $present->contains($stage) ? 'bg-amber-warm' : 'bg-ink/20' }}"></span>
                {{ $labels[$stage] }}
            </span>
            @if (! $loop->last)
                <svg class="h-3.5 w-3.5 shrink-0 text-ink/25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M5 12h14M13 5l7 7-7 7"/>
                </svg>
            @endif
        </div>
    @endforeach
</div>

{{-- Actual journey --}}
@if ($events->isEmpty())
    <div class="mt-6 rounded-2xl border border-dashed border-ink/15 bg-cream/60 px-6 py-10 text-center">
        <p class="font-fraunces text-lg font-bold text-ink">Aucun événement enregistré</p>
        <p class="mt-1 text-sm text-ink/55">La traçabilité de ce lot n'a pas encore été renseignée.</p>
    </div>
@else
    <ol class="relative mt-6">
        @foreach ($events as $event)
            @php $style = $styles[$event->event_type] ?? ['dot' => 'bg-ink/30', 'text' => 'text-ink/60', 'panel' => 'bg-ink/5', 'icon' => 'M12 6v6l4 2']; @endphp
            <li class="relative pb-6 pl-11 last:pb-0">
                <span class="absolute left-0 top-0 flex h-7 w-7 items-center justify-center rounded-full {{ $style['panel'] }} ring-1 ring-ink/5">
                    <svg class="h-3.5 w-3.5 {{ $style['text'] }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="{{ $style['icon'] }}"/>
                    </svg>
                </span>
                @if (! $loop->last)
                    <span class="absolute left-[13px] top-7 h-[calc(100%-1.75rem)] w-px bg-ink/10"></span>
                @endif
                <div class="rounded-2xl border border-ink/8 {{ $style['panel'] }} px-4 py-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-[11px] font-bold uppercase tracking-widest {{ $style['text'] }}">{{ $labels[$event->event_type] ?? ucfirst($event->event_type) }}</p>
                        <p class="text-xs font-semibold text-ink/60">{{ $event->event_date?->format('d/m/Y · H:i') }}</p>
                    </div>
                    @if ($event->location)
                        <p class="mt-1.5 text-sm font-medium text-ink">{{ $event->location }}</p>
                    @endif
                    @if ($event->description && ! $compact)
                        <p class="mt-1 text-sm leading-6 text-ink/60">{{ $event->description }}</p>
                    @endif
                    @if (! $compact)
                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-ink/55">
                            @if ($event->temperature !== null)<span>Température {{ $event->temperature }} °C</span>@endif
                            @if ($event->distance_km !== null)<span>{{ number_format((float) $event->distance_km, 1, ',', ' ') }} km</span>@endif
                            @if ($event->carbon_emission !== null)<span>CO₂ {{ number_format((float) $event->carbon_emission, 2, ',', ' ') }} kg</span>@endif
                        </div>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
@endif
