@props(['score' => 0, 'severity' => 'info', 'size' => 168, 'caption' => null])
@php
    $value = max(0, min(100, (float) $score));
    $radius = 54;
    $circumference = 2 * M_PI * $radius;
    $offset = $circumference * (1 - $value / 100);

    $palette = [
        'info' => ['stroke' => '#2d6a4f', 'text' => 'text-emerald-700 dark:text-emerald-300', 'label' => 'Info'],
        'warning' => ['stroke' => '#c17817', 'text' => 'text-amber-700 dark:text-amber-300', 'label' => 'Avertissement'],
        'critical' => ['stroke' => '#b23a3a', 'text' => 'text-red-700 dark:text-red-300', 'label' => 'Critique'],
    ];
    $meta = $palette[$severity] ?? $palette['info'];
@endphp
<div {{ $attributes->merge(['class' => 'relative inline-flex items-center justify-center']) }} style="width: {{ (int) $size }}px; height: {{ (int) $size }}px;">
    <svg viewBox="0 0 128 128" class="h-full w-full -rotate-90" role="img"
         aria-label="{{ $caption ?? 'Score de risque' }} : {{ number_format($value, 0) }} sur 100, {{ $meta['label'] }}">
        <circle cx="64" cy="64" r="{{ $radius }}" fill="none" stroke="currentColor" class="text-ink/10 dark:text-white/10" stroke-width="10"/>
        <circle cx="64" cy="64" r="{{ $radius }}" fill="none" stroke="{{ $meta['stroke'] }}" stroke-width="10"
                stroke-linecap="round" stroke-dasharray="{{ round($circumference, 2) }}"
                stroke-dashoffset="{{ round($offset, 2) }}" style="transition: stroke-dashoffset .6s ease"/>
    </svg>
    <div class="absolute inset-0 flex flex-col items-center justify-center">
        <span class="font-fraunces text-4xl font-bold text-ink dark:text-white">{{ number_format($value, 0) }}</span>
        <span class="text-[10px] font-bold uppercase tracking-[0.18em] {{ $meta['text'] }}">{{ $meta['label'] }}</span>
        @if($caption)
            <span class="mt-1 text-[10px] uppercase tracking-wider text-ink/40 dark:text-white/40">{{ $caption }}</span>
        @endif
    </div>
</div>
