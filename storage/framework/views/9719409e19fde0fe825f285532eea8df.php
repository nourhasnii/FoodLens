<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['active']));

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

foreach (array_filter((['active']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-4 py-2 border-b-2 border-amber-warm text-sm font-medium leading-5 text-forest focus:outline-none transition duration-150 ease-in-out bg-amber-warm/5 rounded-t-lg'
            : 'inline-flex items-center px-4 py-2 border-b-2 border-transparent text-sm font-medium leading-5 text-ink/60 hover:text-ink hover:border-ink/20 focus:outline-none focus:text-ink focus:border-ink/20 transition duration-150 ease-in-out';
?>

<a <?php echo e($attributes->merge(['class' => $classes])); ?>>
    <?php echo e($slot); ?>

</a>
<?php /**PATH C:\Users\mdain\nutritrace1\nutritrace\resources\views/components/nav-link.blade.php ENDPATH**/ ?>