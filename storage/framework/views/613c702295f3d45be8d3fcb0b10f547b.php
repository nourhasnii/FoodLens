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
    <style>
        @media print {
            body * { visibility: hidden !important; }
            #batch-qr-card, #batch-qr-card * { visibility: visible !important; }
            #batch-qr-card { position: absolute; inset: 0 auto auto 0; width: 100%; box-shadow: none !important; }
            #batch-qr-card .qr-actions { display: none !important; }
        }
    </style>
     <?php $__env->slot('header', null, []); ?> 
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><p class="text-sm font-semibold uppercase tracking-widest text-amber-warm">Traceability workspace</p><h2 class="mt-1 font-fraunces text-3xl font-bold text-ink">Batch Overview</h2></div><div class="flex flex-wrap gap-2"><a href="<?php echo e(route('admin.batches.traceability', $batch)); ?>" class="rounded-xl bg-amber-warm px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-warm/90">View Traceability</a><a href="<?php echo e(route('admin.batches.edit', $batch)); ?>" class="rounded-xl bg-forest px-4 py-2.5 text-sm font-semibold text-white hover:bg-forest-dark">Edit batch</a><a href="<?php echo e(route('admin.batches.index')); ?>" class="rounded-xl border border-ink/10 px-4 py-2.5 text-sm font-semibold text-ink/65 hover:bg-ink/5">Back</a></div></div>
     <?php $__env->endSlot(); ?>
    <div class="py-10 sm:py-12"><div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="rounded-2xl bg-forest p-6 text-white shadow-sm sm:p-8"><div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between"><div><p class="text-sm font-medium text-white/60">Lot number</p><h1 class="mt-1 font-fraunces text-3xl font-bold"><?php echo e($batch->lot_number); ?></h1><p class="mt-2 text-white/70"><?php echo e($batch->product?->name ?? 'Product unavailable'); ?></p></div><span class="inline-flex w-fit rounded-full bg-white/15 px-3 py-1.5 text-xs font-bold uppercase tracking-wider"><?php echo e($batch->status); ?></span></div></div>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3"><div class="rounded-2xl border border-ink/8 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-[#17211B] lg:col-span-2"><h3 class="font-fraunces text-xl font-bold text-ink dark:text-white">Batch Overview</h3><dl class="mt-6 grid grid-cols-1 gap-x-8 gap-y-6 sm:grid-cols-2"><div><dt class="text-xs font-semibold uppercase tracking-wider text-ink/45 dark:text-white/45">Product</dt><dd class="mt-1 font-semibold text-ink dark:text-white"><?php echo e($batch->product?->name ?? 'Product unavailable'); ?></dd></div><div><dt class="text-xs font-semibold uppercase tracking-wider text-ink/45 dark:text-white/45">Quantity</dt><dd class="mt-1 font-semibold text-ink dark:text-white"><?php echo e($batch->quantity); ?> <?php echo e($batch->unit); ?></dd></div><div><dt class="text-xs font-semibold uppercase tracking-wider text-ink/45 dark:text-white/45">Production date</dt><dd class="mt-1 text-ink dark:text-white/80"><?php echo e($batch->production_date?->format('d/m/Y')); ?></dd></div><div><dt class="text-xs font-semibold uppercase tracking-wider text-ink/45 dark:text-white/45">Expiration date</dt><dd class="mt-1 text-ink dark:text-white/80"><?php echo e($batch->expiration_date?->format('d/m/Y')); ?></dd></div><div><dt class="text-xs font-semibold uppercase tracking-wider text-ink/45 dark:text-white/45">Carbon footprint</dt><dd class="mt-1 text-ink dark:text-white/80"><?php echo e($batch->carbon_footprint !== null ? $batch->carbon_footprint . ' kg CO₂' : 'Not specified'); ?></dd></div><div><dt class="text-xs font-semibold uppercase tracking-wider text-ink/45 dark:text-white/45">Created</dt><dd class="mt-1 text-ink dark:text-white/80"><?php echo e($batch->created_at?->format('d/m/Y H:i')); ?></dd></div></dl></div>
            <section id="batch-qr-card" class="rounded-2xl border border-ink/8 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-[#17211B]" aria-labelledby="batch-qr-heading">
                <div class="flex items-start justify-between gap-3"><div><p class="text-xs font-semibold uppercase tracking-wider text-amber-warm">Batch identity</p><h3 id="batch-qr-heading" class="mt-1 font-fraunces text-xl font-bold text-ink dark:text-white">QR Code</h3></div><span class="rounded-full bg-forest/10 px-2.5 py-1 text-xs font-semibold text-forest dark:bg-[#E3A23C]/15 dark:text-[#E3A23C]"><?php echo e($batch->lot_number); ?></span></div>
                <div class="mt-5 flex min-h-56 items-center justify-center rounded-xl border border-ink/8 bg-cream/70 p-4 dark:border-white/10 dark:bg-[#101815]">
                    <?php if($batch->qr_code_path && $qrCodeExists): ?>
                        <img src="<?php echo e(asset('storage/' . $batch->qr_code_path)); ?>" alt="QR code for batch <?php echo e($batch->lot_number); ?>" class="h-48 w-48 max-w-full object-contain" width="192" height="192">
                    <?php else: ?>
                        <div class="text-center"><p class="text-sm font-semibold text-ink dark:text-white">QR code unavailable</p><p class="mt-1 text-xs text-ink/55 dark:text-white/55">Regenerate it to create a new code.</p></div>
                    <?php endif; ?>
                </div>
                <p class="mt-3 text-center text-sm text-ink/55 dark:text-white/55">Scan to view batch traceability</p>
                <div class="qr-actions mt-5 flex flex-wrap gap-2">
                    <?php if($qrCodeExists): ?>
                        <a href="<?php echo e(route('admin.batches.qr', $batch)); ?>" class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-forest px-3 py-2.5 text-sm font-semibold text-white transition hover:bg-forest-dark dark:bg-[#E3A23C] dark:text-[#16281E] dark:hover:bg-[#F0B956]">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5M12 15V3"/></svg>Download
                        </a>
                        <button type="button" onclick="window.print()" class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg border border-ink/15 px-3 py-2.5 text-sm font-semibold text-ink transition hover:bg-ink/5 dark:border-white/15 dark:text-white dark:hover:bg-white/5"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>Print</button>
                    <?php endif; ?>
                    <form method="POST" action="<?php echo e(route('admin.batches.regenerate-qr', $batch)); ?>" class="flex-1"><?php echo csrf_field(); ?><button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-ink/15 px-3 py-2.5 text-sm font-semibold text-ink transition hover:bg-ink/5 dark:border-white/15 dark:text-white dark:hover:bg-white/5"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 7v5h-5M4 17v-5h5"/><path d="M5.6 9a7 7 0 0 1 11.5-2L20 12M4 12l2.9 5a7 7 0 0 0 11.5-2"/></svg>Regenerate</button></form>
                </div>
            </section>
        </div>
    </div></div>
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
<?php /**PATH C:\Users\mdain\nutritrace1\nutritrace\resources\views/admin/batches/show.blade.php ENDPATH**/ ?>