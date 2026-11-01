<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['label', 'value', 'detail' => '', 'tone' => 'forest', 'icon' => 'product']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['label', 'value', 'detail' => '', 'tone' => 'forest', 'icon' => 'product']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
    $tones = ['forest' => 'bg-forest/10 text-forest dark:bg-emerald-400/10 dark:text-emerald-300', 'blue' => 'bg-sky-500/10 text-sky-700 dark:bg-sky-400/10 dark:text-sky-300', 'amber' => 'bg-amber-warm/10 text-amber-warm dark:bg-amber-300/10 dark:text-amber-300', 'rose' => 'bg-rose-500/10 text-rose-700 dark:bg-rose-400/10 dark:text-rose-300'];
    $toneClass = $tones[$tone] ?? $tones['forest'];
?>
<div class="group rounded-2xl border border-ink/8 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-white/10 dark:bg-[#1b2923]">
    <div class="flex items-center justify-between gap-3">
        <span class="flex h-12 w-12 items-center justify-center rounded-xl <?php echo e($toneClass); ?>">
            <?php if($icon === 'batch'): ?><svg class="h-7 w-7" viewBox="0 0 32 32" fill="none"><path d="m5 11 11-6 11 6v14l-11 5-11-5V11Z" fill="currentColor"/><path d="m5 11 11 6 11-6M16 17v13" stroke="#FBEFE0" stroke-width="1.5"/></svg>
            <?php elseif($icon === 'certification'): ?><svg class="h-7 w-7" viewBox="0 0 32 32" fill="none"><path d="M16 4 19 7l5-.5.5 5 3 3-3 3-.5 5-5-.5-3 3-3-3-5 .5-.5-5-3-3 3-3 .5-5 5 .5 3-3Z" fill="currentColor"/><path d="m11 16 3 3 7-7" stroke="#EEF3EC" stroke-width="2" stroke-linecap="round"/></svg>
            <?php elseif($icon === 'alert'): ?><svg class="h-7 w-7" viewBox="0 0 32 32" fill="none"><path d="m16 4 13 23H3L16 4Z" fill="currentColor"/><path d="M16 12v7m0 4h.01" stroke="#F5E7E2" stroke-width="2" stroke-linecap="round"/></svg>
            <?php else: ?><svg class="h-7 w-7" viewBox="0 0 32 32" fill="none"><path d="M16 27C7 25 6 17 9 10c3 2 5 4 6 7 1-7 5-11 10-13 1 10-1 19-9 23Z" fill="currentColor"/><path d="M16 27c0-6 1-11 7-17" stroke="#EEF3EC" stroke-width="1.5" stroke-linecap="round"/></svg><?php endif; ?>
        </span>
        <span class="text-xs font-medium text-ink/35 dark:text-white/35">Live</span>
    </div>
    <p class="mt-5 text-xs font-bold uppercase tracking-wider text-ink/45 dark:text-white/45"><?php echo e($label); ?></p>
    <p class="mt-1 font-fraunces text-3xl font-bold text-ink dark:text-white"><?php echo e(number_format($value)); ?></p>
    <p class="mt-1 text-xs text-ink/55 dark:text-white/55"><?php echo e($detail); ?></p>
</div><?php /**PATH C:\Users\mdain\nutritrace1\nutritrace\resources\views/components/dashboard/kpi-card.blade.php ENDPATH**/ ?>