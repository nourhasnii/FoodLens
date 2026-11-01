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
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-fraunces text-2xl font-bold text-ink">Centre de notifications</h2>
                <p class="mt-1 text-sm text-ink/55">Alertes de risque NutriTrace : certifications, OCR, rappels, qualité et couverture.</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-2 rounded-full bg-[#FBEFE0] px-4 py-2 text-sm font-semibold text-[#8a5a12]">
                    <?php echo e($unreadCount); ?> non lue(s)
                </span>
                <form method="POST" action="<?php echo e(route('notifications.read-all')); ?>">
                    <?php echo csrf_field(); ?>
                    <button
                        type="submit"
                        class="rounded-full bg-[#1F3D2E] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#16281E] disabled:cursor-not-allowed disabled:opacity-50"
                        <?php if($unreadCount === 0): echo 'disabled'; endif; ?>
                    >
                        Tout marquer comme lu
                    </button>
                </form>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <?php if(session('success')): ?>
                <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-800">
                    <?php echo e(session('success')); ?>

                </div>
            <?php endif; ?>

            <form method="GET" class="grid grid-cols-1 gap-3 rounded-2xl border border-ink/10 bg-white p-4 sm:grid-cols-2 lg:grid-cols-4">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-ink/50">Source</span>
                    <select name="source" onchange="this.form.submit()" class="w-full rounded-xl border-ink/15 bg-cream/50 text-sm text-ink focus:border-[#C17817] focus:ring-[#C17817]/30">
                        <option value="">Toutes les sources</option>
                        <?php $__currentLoopData = $sources; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>" <?php if($filters['source'] === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-ink/50">Sévérité</span>
                    <select name="severity" onchange="this.form.submit()" class="w-full rounded-xl border-ink/15 bg-cream/50 text-sm text-ink focus:border-[#C17817] focus:ring-[#C17817]/30">
                        <option value="">Toutes les sévérités</option>
                        <?php $__currentLoopData = $severities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>" <?php if($filters['severity'] === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-ink/50">État</span>
                    <select name="state" onchange="this.form.submit()" class="w-full rounded-xl border-ink/15 bg-cream/50 text-sm text-ink focus:border-[#C17817] focus:ring-[#C17817]/30">
                        <option value="">Toutes</option>
                        <option value="unread" <?php if($filters['state'] === 'unread'): echo 'selected'; endif; ?>>Non lues</option>
                        <option value="read" <?php if($filters['state'] === 'read'): echo 'selected'; endif; ?>>Lues</option>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-ink/50">Tri</span>
                    <select name="sort" onchange="this.form.submit()" class="w-full rounded-xl border-ink/15 bg-cream/50 text-sm text-ink focus:border-[#C17817] focus:ring-[#C17817]/30">
                        <option value="priority" <?php if($filters['sort'] === 'priority'): echo 'selected'; endif; ?>>Priorité (décroissante)</option>
                        <option value="recent" <?php if($filters['sort'] === 'recent'): echo 'selected'; endif; ?>>Plus récentes</option>
                    </select>
                </label>
            </form>

            <div class="space-y-3">
                <?php $__empty_1 = true; $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <article class="flex items-start gap-4 rounded-2xl border border-ink/10 bg-white p-4 shadow-[0_14px_35px_-30px_rgba(31,61,46,0.7)] <?php echo e($notification['read'] ? '' : 'ring-1 ring-[#E3A23C]/40'); ?>">
                        <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full <?php echo e(match($notification['severity']) {
                            'critical' => 'bg-rose-500',
                            'warning' => 'bg-amber-500',
                            default => 'bg-emerald-500',
                        }); ?>"></span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-3">
                                <h3 class="text-sm font-bold text-ink"><?php echo e($notification['title']); ?></h3>
                                <span class="inline-flex items-center rounded-full bg-cream px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-ink/60">
                                    <?php echo e($notification['source_label']); ?>

                                </span>
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold <?php echo e(match($notification['severity']) {
                                    'critical' => 'bg-rose-100 text-rose-700',
                                    'warning' => 'bg-amber-100 text-amber-700',
                                    default => 'bg-emerald-100 text-emerald-700',
                                }); ?>">
                                    <?php echo e($notification['severity_label']); ?>

                                </span>
                                <span class="text-[11px] font-semibold text-ink/40">Priorité <?php echo e(number_format($notification['priority'], 0, ',', ' ')); ?>/100</span>
                                <?php if (! ($notification['read'])): ?>
                                    <span class="inline-flex items-center rounded-full bg-[#E3A23C] px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-[#16281E]">Nouveau</span>
                                <?php endif; ?>
                            </div>

                            <p class="mt-2 text-sm text-ink/70"><?php echo e($notification['message']); ?></p>

                            <div class="mt-3 flex flex-wrap items-center gap-4">
                                <span class="text-xs text-ink/40"><?php echo e($notification['created_human']); ?></span>

                                <?php if($notification['action_url']): ?>
                                    <a href="<?php echo e($notification['action_url']); ?>" class="text-xs font-semibold text-[#C17817] hover:underline">
                                        <?php echo e($notification['action_label'] ?? 'Ouvrir'); ?>

                                    </a>
                                <?php endif; ?>

                                <?php if (! ($notification['read'])): ?>
                                    <form method="POST" action="<?php echo e(route('notifications.read', $notification['id'])); ?>">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PATCH'); ?>
                                        <button type="submit" class="text-xs font-semibold text-ink/50 transition hover:text-ink">Marquer comme lu</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="rounded-2xl border border-dashed border-ink/15 bg-white px-6 py-16 text-center">
                        <p class="text-sm font-semibold text-ink/70">Aucune notification pour ces critères.</p>
                        <p class="mt-1 text-xs text-ink/45">Les alertes apparaissent dès qu'un risque est détecté par le NutriAlert Hub.</p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if($notifications->hasPages()): ?>
                <div class="pt-2">
                    <?php echo e($notifications->links()); ?>

                </div>
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
<?php endif; ?>
<?php /**PATH C:\Users\mdain\nutritrace1\nutritrace\resources\views/notifications/index.blade.php ENDPATH**/ ?>