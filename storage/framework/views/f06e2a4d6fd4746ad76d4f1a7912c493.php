<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['status']));

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

foreach (array_filter((['status']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
    $meta = match($status) {
        'verified', 'valid', 'active' => ['label' => strtoupper($status), 'class' => 'bg-emerald-500/10 text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300'],
        'pending', 'expiring' => ['label' => $status === 'expiring' ? 'EXPIRING SOON' : 'PENDING', 'class' => 'bg-amber-warm/10 text-amber-warm dark:bg-amber-300/10 dark:text-amber-300'],
        'expired', 'rejected', 'flagged', 'recalled' => ['label' => strtoupper($status), 'class' => 'bg-red-500/10 text-red-700 dark:bg-red-400/10 dark:text-red-300'],
        default => ['label' => strtoupper($status), 'class' => 'bg-ink/8 text-ink/60 dark:bg-white/10 dark:text-white/60'],
    };
?>
<span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold tracking-wider <?php echo e($meta['class']); ?>"><?php echo e($meta['label']); ?></span>
<?php /**PATH C:\Users\mdain\nutritrace1\nutritrace\resources\views/components/dashboard/status-badge.blade.php ENDPATH**/ ?>