<?php if (isset($component)) { $__componentOriginal0de143e5b61900e6d7b990ac144ae3fb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0de143e5b61900e6d7b990ac144ae3fb = $attributes; } ?>
<?php $component = App\View\Components\DashboardLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\DashboardLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('header', null, []); ?> 
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-warm">Production workspace</p>
                <h2 class="mt-1 font-fraunces text-3xl font-bold text-ink">Détails du produit</h2>
            </div>
            <div class="flex gap-2">
                <a href="<?php echo e(route('admin.products.edit', $product)); ?>" class="rounded bg-indigo-500 px-4 py-2 font-bold text-white hover:bg-indigo-700">Modifier</a>
                <a href="<?php echo e(route('admin.products.index')); ?>" class="rounded bg-gray-500 px-4 py-2 font-bold text-white hover:bg-gray-700">Retour</a>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="grid grid-cols-1 gap-8 p-6 text-ink dark:text-white md:grid-cols-2">
                    <div class="md:col-span-2">
                        <div class="mx-auto flex min-h-[240px] max-w-2xl items-center justify-center rounded-xl border border-ink/10 bg-cream/40 p-6 dark:border-white/10 dark:bg-white/5">
                            <?php if($product->image_url): ?>
                                <img src="<?php echo e($product->image_url); ?>" alt="<?php echo e($product->name); ?>" class="max-h-[420px] w-full rounded-lg object-contain">
                            <?php else: ?>
                                <div class="flex flex-col items-center gap-3 text-ink/35 dark:text-white/35">
                                    <svg class="h-12 w-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                                        <circle cx="8.5" cy="8.5" r="1.5"/>
                                        <path d="M21 15L16 10L5 21"/>
                                    </svg>
                                    <p class="text-sm">Aucune image disponible</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <h3 class="mb-4 text-lg font-semibold">Informations générales</h3>
                        <dl class="space-y-3">
                            <div><dt class="text-sm font-medium text-gray-500">Nom</dt><dd><?php echo e($product->name); ?></dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">Description</dt><dd><?php echo e($product->description ?: '-'); ?></dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">Catégorie</dt><dd><?php echo e($product->categoryModel?->name ?? $product->category); ?></dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">Unité</dt><dd><?php echo e($product->unit); ?></dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">Code-barres</dt><dd><?php echo e($product->barcode ?: '-'); ?></dd></div>
                        </dl>
                    </div>
                    <div>
                        <h3 class="mb-4 text-lg font-semibold">Traçabilité</h3>
                        <dl class="space-y-3">
                            <div><dt class="text-sm font-medium text-gray-500">Pays d'origine</dt><dd><?php echo e($product->origin_country); ?></dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">Région d'origine</dt><dd><?php echo e($product->origin_region ?: '-'); ?></dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">Producteur</dt><dd><?php echo e($product->producer?->name ?: '-'); ?></dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">Empreinte carbone</dt><dd><?php echo e($product->carbon_footprint !== null ? $product->carbon_footprint . ' kg CO₂' : '-'); ?></dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">Biologique</dt><dd><?php echo e($product->is_organic ? 'Oui' : 'Non'); ?></dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">Vérification</dt><dd><?php echo e(ucfirst($product->verification_status)); ?></dd></div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0de143e5b61900e6d7b990ac144ae3fb)): ?>
<?php $attributes = $__attributesOriginal0de143e5b61900e6d7b990ac144ae3fb; ?>
<?php unset($__attributesOriginal0de143e5b61900e6d7b990ac144ae3fb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0de143e5b61900e6d7b990ac144ae3fb)): ?>
<?php $component = $__componentOriginal0de143e5b61900e6d7b990ac144ae3fb; ?>
<?php unset($__componentOriginal0de143e5b61900e6d7b990ac144ae3fb); ?>
<?php endif; ?>
<?php /**PATH C:\Users\mdain\nutritrace1\nutritrace\resources\views/admin/products/show.blade.php ENDPATH**/ ?>