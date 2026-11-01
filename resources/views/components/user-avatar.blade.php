@props(['user' => null, 'size' => 'md'])

@php
    $user = $user ?? auth()->user();

    $sizes = [
        'xs' => 'h-6 w-6 text-[10px]',
        'sm' => 'h-7 w-7 text-xs',
        'md' => 'h-9 w-9 text-sm',
        'lg' => 'h-10 w-10 text-sm',
        'xl' => 'h-20 w-20 text-2xl',
        '2xl' => 'h-28 w-28 text-3xl',
    ];

    $sizeClasses = $sizes[$size] ?? $sizes['md'];
    $photo = $user?->profile_photo_url;
    $initials = $user?->profile_initials ?? '?';
@endphp

<span
    {{ $attributes->class([
        'inline-flex shrink-0 select-none items-center justify-center overflow-hidden rounded-full font-fraunces font-bold leading-none',
        $sizeClasses,
    ]) }}
>
    @if ($photo)
        <img src="{{ $photo }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
    @else
        {{ $initials }}
    @endif
</span>
