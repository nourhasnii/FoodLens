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
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-warm">Traçabilité publique</p>
            <h1 class="mt-1 font-fraunces text-2xl font-semibold text-ink">Suivi d'un lot</h1>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="bg-cream py-10 sm:py-14">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            
            <div class="rounded-3xl bg-forest p-6 text-white shadow-sm sm:p-8">
                <h2 class="font-fraunces text-3xl font-bold">Suivez un produit en un clic</h2>
                <p class="mt-2 max-w-xl text-sm leading-6 text-white/70">
                    Saisissez le numéro de lot figurant sur l'étiquette pour visualiser son parcours, de la production à la distribution.
                </p>
                <form method="GET" action="<?php echo e(route('front.traceability.index')); ?>" class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <input
                        type="text"
                        name="lot"
                        value="<?php echo e($searchedLot); ?>"
                        required
                        placeholder="Ex. LOT-<?php echo e(date('Y')); ?>-OLIV-001"
                        class="w-full flex-1 rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-sm font-medium text-white placeholder-white/45 focus:border-amber-warm focus:outline-none focus:ring-2 focus:ring-amber-warm/40"
                    >
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-amber-warm px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-amber-warm/90">
                        Suivre le lot
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M5 12h14M13 5l7 7-7 7"/>
                        </svg>
                    </button>
                </form>
            </div>

            
            <?php if($batch): ?>
                <div class="mt-6 rounded-3xl border border-ink/8 bg-white p-6 shadow-sm sm:p-8">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-widest text-amber-warm">Lot suivi</p>
                            <h3 class="mt-1 font-fraunces text-2xl font-bold text-ink"><?php echo e($batch->product?->name ?? 'Produit'); ?></h3>
                            <p class="mt-1 text-sm font-semibold text-ink/55"><?php echo e($batch->lot_number); ?></p>
                        </div>
                        <?php
                            $statusStyles = [
                                'active' => 'bg-emerald-500/10 text-emerald-700',
                                'expired' => 'bg-amber-500/10 text-amber-700',
                                'recalled' => 'bg-red-500/10 text-red-600',
                            ];
                            $statusLabels = ['active' => 'Actif', 'expired' => 'Expiré', 'recalled' => 'Rappelé'];
                        ?>
                        <span class="rounded-full px-3 py-1.5 text-xs font-semibold <?php echo e($statusStyles[$batch->status] ?? 'bg-ink/10 text-ink/70'); ?>">
                            <?php echo e($statusLabels[$batch->status] ?? ucfirst($batch->status)); ?>

                        </span>
                    </div>

                    <dl class="mt-6 grid gap-4 border-t border-ink/8 pt-6 sm:grid-cols-3">
                        <div>
                            <dt class="text-xs uppercase tracking-wider text-ink/45">Production</dt>
                            <dd class="mt-1 text-sm font-semibold text-ink"><?php echo e($batch->production_date?->format('d/m/Y') ?? '—'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wider text-ink/45">Expiration</dt>
                            <dd class="mt-1 text-sm font-semibold text-ink"><?php echo e($batch->expiration_date?->format('d/m/Y') ?? '—'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wider text-ink/45">Quantité</dt>
                            <dd class="mt-1 text-sm font-semibold text-ink"><?php echo e($batch->quantity); ?> <?php echo e($batch->unit); ?></dd>
                        </div>
                    </dl>

                    <div class="mt-8">
                        <p class="text-xs font-bold uppercase tracking-widest text-amber-warm">Parcours du lot</p>
                        <?php echo $__env->make('front.partials.timeline', ['batch' => $batch], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                </div>
            <?php elseif($searchedLot !== ''): ?>
                <div class="mt-6 rounded-3xl border border-dashed border-ink/15 bg-white px-6 py-14 text-center shadow-sm">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-forest/10 text-forest">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="M21 21l-4.35-4.35"/>
                        </svg>
                    </div>
                    <h3 class="mt-4 font-fraunces text-xl font-bold text-ink">Aucun lot trouvé</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm text-ink/55">
                        Aucun lot ne correspond à « <span class="font-semibold text-ink/70"><?php echo e($searchedLot); ?></span> ». Vérifiez le numéro figurant sur l'étiquette puis réessayez.
                    </p>
                </div>
            <?php else: ?>
                <div class="mt-6 rounded-3xl border border-dashed border-ink/15 bg-white px-6 py-14 text-center shadow-sm">
                    <h3 class="font-fraunces text-xl font-bold text-ink">Entrez un numéro de lot</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm text-ink/55">
                        Le numéro de lot se trouve sur l'étiquette ou sur le QR code du produit.
                    </p>
                </div>
            <?php endif; ?>

            
            <?php if($featured): ?>
                <div class="mt-6 rounded-3xl border border-ink/8 bg-white p-6 shadow-sm sm:p-8">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-widest text-amber-warm">Exemple de traçabilité</p>
                            <p class="mt-1 font-fraunces text-lg font-bold text-ink"><?php echo e($featured->product?->name ?? 'Produit'); ?></p>
                            <p class="text-xs text-ink/50"><?php echo e($featured->lot_number); ?></p>
                        </div>
                        <a href="<?php echo e(route('front.traceability.show', $featured)); ?>" class="rounded-2xl bg-forest px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-forest-dark">
                            Ouvrir ce lot
                        </a>
                    </div>
                    <div class="mt-6">
                        <?php echo $__env->make('front.partials.timeline', ['batch' => $featured, 'compact' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="mt-8 text-center">
                <a href="<?php echo e(route('home')); ?>" class="text-sm font-semibold text-forest hover:text-amber-warm">Retour à l'accueil</a>
            </div>
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
<?php endif; ?>
<?php /**PATH C:\Users\mdain\nutritrace1\nutritrace\resources\views/front/traceability/show.blade.php ENDPATH**/ ?>