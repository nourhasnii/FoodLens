<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['user' => null, 'size' => 'md']));

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

foreach (array_filter((['user' => null, 'size' => 'md']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
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
?>

<span
    <?php echo e($attributes->class([
        'inline-flex shrink-0 select-none items-center justify-center overflow-hidden rounded-full font-fraunces font-bold leading-none',
        $sizeClasses,
    ])); ?>

>
    <?php if($photo): ?>
        <img src="<?php echo e($photo); ?>" alt="<?php echo e($user->name); ?>" class="h-full w-full object-cover">
    <?php else: ?>
        <?php echo e($initials); ?>

    <?php endif; ?>
</span>
<?php /**PATH C:\Users\mdain\nutritrace1\nutritrace\resources\views/components/user-avatar.blade.php ENDPATH**/ ?>