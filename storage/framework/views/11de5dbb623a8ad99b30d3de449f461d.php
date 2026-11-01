<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['score' => 0, 'severity' => 'info', 'size' => 168, 'caption' => null]));

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

foreach (array_filter((['score' => 0, 'severity' => 'info', 'size' => 168, 'caption' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
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
?>
<div <?php echo e($attributes->merge(['class' => 'relative inline-flex items-center justify-center'])); ?> style="width: <?php echo e((int) $size); ?>px; height: <?php echo e((int) $size); ?>px;">
    <svg viewBox="0 0 128 128" class="h-full w-full -rotate-90" role="img"
         aria-label="<?php echo e($caption ?? 'Score de risque'); ?> : <?php echo e(number_format($value, 0)); ?> sur 100, <?php echo e($meta['label']); ?>">
        <circle cx="64" cy="64" r="<?php echo e($radius); ?>" fill="none" stroke="currentColor" class="text-ink/10 dark:text-white/10" stroke-width="10"/>
        <circle cx="64" cy="64" r="<?php echo e($radius); ?>" fill="none" stroke="<?php echo e($meta['stroke']); ?>" stroke-width="10"
                stroke-linecap="round" stroke-dasharray="<?php echo e(round($circumference, 2)); ?>"
                stroke-dashoffset="<?php echo e(round($offset, 2)); ?>" style="transition: stroke-dashoffset .6s ease"/>
    </svg>
    <div class="absolute inset-0 flex flex-col items-center justify-center">
        <span class="font-fraunces text-4xl font-bold text-ink dark:text-white"><?php echo e(number_format($value, 0)); ?></span>
        <span class="text-[10px] font-bold uppercase tracking-[0.18em] <?php echo e($meta['text']); ?>"><?php echo e($meta['label']); ?></span>
        <?php if($caption): ?>
            <span class="mt-1 text-[10px] uppercase tracking-wider text-ink/40 dark:text-white/40"><?php echo e($caption); ?></span>
        <?php endif; ?>
    </div>
</div>
<?php /**PATH C:\Users\mdain\nutritrace1\nutritrace\resources\views/components/dashboard/risk-gauge.blade.php ENDPATH**/ ?>