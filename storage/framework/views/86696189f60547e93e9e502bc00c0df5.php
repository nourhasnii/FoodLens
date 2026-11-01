<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('header', null, []); ?> 
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-warm">NutriTrace catalogue</p>
            <h1 class="mt-1 font-fraunces text-2xl font-semibold text-ink">Products</h1>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="bg-cream py-10 sm:py-14">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-8 max-w-2xl">
                <p class="text-sm leading-6 text-ink/60">Explore products and follow their journey through the NutriTrace network.</p>
            </div>

            <?php if($categories->isNotEmpty()): ?>
                <div class="mb-8 flex gap-2.5 overflow-x-auto pb-2 sidebar-scrollbar-hidden">
                    <a href="<?php echo e(route('front.products.index')); ?>"
                       class="shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition-colors <?php echo e(blank($category) ? 'bg-forest text-white' : 'bg-white border border-ink/10 text-ink/70 hover:border-forest hover:text-forest'); ?>">
                        All categories
                    </a>
                    <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $categoryOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route('front.products.index', ['category' => $categoryOption->slug])); ?>"
                           class="shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition-colors <?php echo e((string) $category === (string) $categoryOption->slug || (string) $category === (string) $categoryOption->id ? 'bg-forest text-white' : 'bg-white border border-ink/10 text-ink/70 hover:border-forest hover:text-forest'); ?>">
                            <?php echo e($categoryOption->name); ?>

                            <span class="ml-1 text-xs font-normal opacity-70"><?php echo e($categoryOption->products_count); ?></span>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>

            <?php if($products->isEmpty()): ?>
                <div class="rounded-2xl border border-dashed border-ink/15 bg-white px-6 py-16 text-center shadow-sm">
                    <h2 class="font-fraunces text-xl font-semibold text-ink">No products available yet</h2>
                    <p class="mt-2 text-sm text-ink/55">Products will appear here once they are added to the catalogue.</p>
                </div>
            <?php else: ?>
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route('front.products.show', $product)); ?>" class="group overflow-hidden rounded-2xl border border-ink/8 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-lg">
                            <div class="flex h-44 items-center justify-center bg-forest/5">
                                <?php if($product->image_url): ?>
                                    <img src="<?php echo e($product->image_url); ?>" alt="<?php echo e($product->name); ?>" class="h-full w-full object-cover">
                                <?php else: ?>
                                    <span class="font-fraunces text-5xl font-semibold text-forest/25"><?php echo e(strtoupper(substr($product->name, 0, 1))); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h2 class="font-fraunces text-xl font-semibold text-ink group-hover:text-forest"><?php echo e($product->name); ?></h2>
                                        <p class="mt-1 text-sm text-ink/50"><?php echo e($product->categoryModel?->name ?? $product->category); ?></p>
                                    </div>
                                    <?php if($product->verification_status === 'verified'): ?>
                                        <span class="rounded-full bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold text-emerald-700">Verified</span>
                                    <?php endif; ?>
                                </div>
                                <p class="mt-4 line-clamp-2 text-sm leading-5 text-ink/60"><?php echo e($product->description ?: 'Trace this product from origin to distribution.'); ?></p>
                                <p class="mt-5 text-xs font-semibold text-forest">View product <span aria-hidden="true">→</span></p>
                            </div>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>

                <div class="mt-8"><?php echo e($products->links()); ?></div>
            <?php endif; ?>
        </div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?><?php /**PATH C:\Users\mdain\nutritrace1\nutritrace\resources\views/front/products/index.blade.php ENDPATH**/ ?>