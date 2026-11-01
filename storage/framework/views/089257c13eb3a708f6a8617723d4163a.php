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
    <?php
        $severityBadge = fn (string $severity): string => match ($severity) {
            'critical' => 'bg-red-500/12 text-red-700 border border-red-500/15 dark:text-red-300',
            'warning' => 'bg-amber-warm/12 text-amber-warm border border-amber-warm/20',
            default => 'bg-emerald-500/12 text-emerald-700 border border-emerald-500/15 dark:text-emerald-300',
        };
        $severityLabel = fn (string $severity): string => match ($severity) {
            'critical' => 'Critique',
            'warning' => 'Avertissement',
            default => 'Info',
        };
    ?>

     <?php $__env->slot('header', null, []); ?> 
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-warm">Décision explicable</p>
                <h1 class="mt-1 font-fraunces text-3xl font-bold leading-tight text-ink dark:text-white">NutriMind</h1>
                <p class="mt-2 text-sm text-ink/55 dark:text-white/55">
                    Risque justifié, impact, simulation « what-if » et recommandations correctives — aucune donnée inventée.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="<?php echo e(route('admin.certifications.intelligence')); ?>"
                   class="inline-flex items-center gap-2 rounded-lg border border-ink/10 bg-white px-4 py-2.5 text-sm font-semibold text-ink transition-colors hover:bg-cream dark:border-white/10 dark:bg-[#1b2923] dark:text-white">
                    Certification Intelligence
                </a>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="mx-auto max-w-[1440px] space-y-6">
        <?php if(session('success')): ?>
            <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-800 dark:text-emerald-300">
                <?php echo e(session('success')); ?>

            </div>
        <?php endif; ?>

        
        <section aria-label="Synthèse du risque" class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
            <?php if (isset($component)) { $__componentOriginal73e8909751d40929eb36b5991fae6a61 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal73e8909751d40929eb36b5991fae6a61 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard.kpi-card','data' => ['label' => 'Produits évalués','value' => $summary['evaluated'],'detail' => 'Entités analysées en temps réel','tone' => 'forest','icon' => 'product']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard.kpi-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Produits évalués','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($summary['evaluated']),'detail' => 'Entités analysées en temps réel','tone' => 'forest','icon' => 'product']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal73e8909751d40929eb36b5991fae6a61)): ?>
<?php $attributes = $__attributesOriginal73e8909751d40929eb36b5991fae6a61; ?>
<?php unset($__attributesOriginal73e8909751d40929eb36b5991fae6a61); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal73e8909751d40929eb36b5991fae6a61)): ?>
<?php $component = $__componentOriginal73e8909751d40929eb36b5991fae6a61; ?>
<?php unset($__componentOriginal73e8909751d40929eb36b5991fae6a61); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal73e8909751d40929eb36b5991fae6a61 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal73e8909751d40929eb36b5991fae6a61 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard.kpi-card','data' => ['label' => 'Risques critiques','value' => $summary['critical'],'detail' => 'Score ≥ 70 ou incident confirmé','tone' => 'rose','icon' => 'alert']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard.kpi-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Risques critiques','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($summary['critical']),'detail' => 'Score ≥ 70 ou incident confirmé','tone' => 'rose','icon' => 'alert']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal73e8909751d40929eb36b5991fae6a61)): ?>
<?php $attributes = $__attributesOriginal73e8909751d40929eb36b5991fae6a61; ?>
<?php unset($__attributesOriginal73e8909751d40929eb36b5991fae6a61); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal73e8909751d40929eb36b5991fae6a61)): ?>
<?php $component = $__componentOriginal73e8909751d40929eb36b5991fae6a61; ?>
<?php unset($__componentOriginal73e8909751d40929eb36b5991fae6a61); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal73e8909751d40929eb36b5991fae6a61 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal73e8909751d40929eb36b5991fae6a61 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard.kpi-card','data' => ['label' => 'À surveiller','value' => $summary['warning'],'detail' => 'Score entre 40 et 69','tone' => 'amber','icon' => 'certification']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard.kpi-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'À surveiller','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($summary['warning']),'detail' => 'Score entre 40 et 69','tone' => 'amber','icon' => 'certification']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal73e8909751d40929eb36b5991fae6a61)): ?>
<?php $attributes = $__attributesOriginal73e8909751d40929eb36b5991fae6a61; ?>
<?php unset($__attributesOriginal73e8909751d40929eb36b5991fae6a61); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal73e8909751d40929eb36b5991fae6a61)): ?>
<?php $component = $__componentOriginal73e8909751d40929eb36b5991fae6a61; ?>
<?php unset($__componentOriginal73e8909751d40929eb36b5991fae6a61); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal73e8909751d40929eb36b5991fae6a61 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal73e8909751d40929eb36b5991fae6a61 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard.kpi-card','data' => ['label' => 'Score moyen','value' => round($summary['average']),'detail' => 'Moyenne des scores de risque','tone' => 'blue','icon' => 'batch']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard.kpi-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Score moyen','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(round($summary['average'])),'detail' => 'Moyenne des scores de risque','tone' => 'blue','icon' => 'batch']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal73e8909751d40929eb36b5991fae6a61)): ?>
<?php $attributes = $__attributesOriginal73e8909751d40929eb36b5991fae6a61; ?>
<?php unset($__attributesOriginal73e8909751d40929eb36b5991fae6a61); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal73e8909751d40929eb36b5991fae6a61)): ?>
<?php $component = $__componentOriginal73e8909751d40929eb36b5991fae6a61; ?>
<?php unset($__componentOriginal73e8909751d40929eb36b5991fae6a61); ?>
<?php endif; ?>
        </section>

        
        <section class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="rounded-2xl border border-ink/5 bg-white p-5 shadow-lg shadow-ink/[0.03] dark:border-white/10 dark:bg-[#1b2923] lg:col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="font-fraunces text-lg font-bold text-ink dark:text-white">Entités les plus à risque</h3>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-ink/40 dark:text-white/40">top 10</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left">
                                <th class="px-3 py-2">Produit</th>
                                <th class="px-3 py-2">Score</th>
                                <th class="px-3 py-2">Sévérité</th>
                                <th class="px-3 py-2">Cause principale</th>
                                <th class="px-3 py-2">Complétude</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $topRisks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $risk): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td class="px-3 py-3 font-semibold text-ink dark:text-white"><?php echo e($risk['label']); ?></td>
                                    <td class="px-3 py-3 font-fraunces text-lg font-bold text-ink dark:text-white"><?php echo e(number_format($risk['score'], 0)); ?><span class="text-xs font-medium text-ink/40">/100</span></td>
                                    <td class="px-3 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider <?php echo e($severityBadge($risk['severity'])); ?>">
                                            <?php echo e($severityLabel($risk['severity'])); ?>

                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-ink/70 dark:text-white/70"><?php echo e($risk['top']['label'] ?? '—'); ?></td>
                                    <td class="px-3 py-3 text-ink/60 dark:text-white/60"><?php echo e(number_format($risk['completeness'], 0)); ?>%</td>
                                    <td class="px-3 py-3 text-right">
                                        <a href="<?php echo e(route('admin.nutrimind.show', ['type' => $risk['type'], 'id' => $risk['id']])); ?>"
                                           class="inline-flex items-center gap-1.5 rounded-lg bg-forest/10 px-3 py-1.5 text-xs font-semibold text-forest transition-colors hover:bg-forest/20 dark:text-emerald-300">
                                            Analyser
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="6" class="px-3 py-8 text-center text-sm italic text-ink/40 dark:text-white/40">Aucun produit à évaluer.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl border border-ink/5 bg-white p-5 shadow-lg shadow-ink/[0.03] dark:border-white/10 dark:bg-[#1b2923]">
                <h3 class="mb-4 font-fraunces text-lg font-bold text-ink dark:text-white">Répartition du risque</h3>
                <?php
                    $total = max(1, $summary['evaluated']);
                    $bars = [
                        ['label' => 'Critique', 'count' => $summary['critical'], 'class' => 'bg-red-500'],
                        ['label' => 'Avertissement', 'count' => $summary['warning'], 'class' => 'bg-amber-warm'],
                        ['label' => 'Info', 'count' => $summary['info'], 'class' => 'bg-emerald-500'],
                    ];
                ?>
                <div class="space-y-4">
                    <?php $__currentLoopData = $bars; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bar): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-ink/70 dark:text-white/70"><?php echo e($bar['label']); ?></span>
                                <span class="font-bold text-ink dark:text-white"><?php echo e($bar['count']); ?></span>
                            </div>
                            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-ink/5 dark:bg-white/10">
                                <div class="h-full <?php echo e($bar['class']); ?> transition-all" style="width: <?php echo e(round($bar['count'] / $total * 100)); ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <p class="mt-5 text-[11px] leading-relaxed text-ink/45 dark:text-white/45">
                    Score = somme des contributions confirmées, plafonnée à 100. Un incident de sécurité (rappel) force la sévérité critique.
                </p>
            </div>
        </section>

        
        <section class="rounded-2xl border border-ink/5 bg-white p-5 shadow-lg shadow-ink/[0.03] dark:border-white/10 dark:bg-[#1b2923]">
            <div class="mb-4">
                <h3 class="font-fraunces text-lg font-bold text-ink dark:text-white">Évaluations enregistrées</h3>
                <p class="text-xs text-ink/50 dark:text-white/50">Historique des décisions tracées — <?php echo e($recentAssessments->count()); ?> dernière(s).</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left">
                            <th class="px-3 py-2">Entité</th>
                            <th class="px-3 py-2">Score</th>
                            <th class="px-3 py-2">Sévérité</th>
                            <th class="px-3 py-2">Recommandation principale</th>
                            <th class="px-3 py-2">Par</th>
                            <th class="px-3 py-2">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $recentAssessments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assessment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="px-3 py-3 font-semibold text-ink dark:text-white"><?php echo e($assessment->entity_label ?: '—'); ?></td>
                                <td class="px-3 py-3 font-bold text-ink dark:text-white"><?php echo e(number_format((float) $assessment->score, 0)); ?><span class="text-xs font-medium text-ink/40">/100</span></td>
                                <td class="px-3 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider <?php echo e($severityBadge($assessment->severity)); ?>">
                                        <?php echo e($severityLabel($assessment->severity)); ?>

                                    </span>
                                </td>
                                <td class="px-3 py-3 text-ink/70 dark:text-white/70"><?php echo e($assessment->recommendation ?: '—'); ?></td>
                                <td class="px-3 py-3 text-ink/60 dark:text-white/60"><?php echo e($assessment->creator?->name ?? '—'); ?></td>
                                <td class="px-3 py-3 whitespace-nowrap font-mono text-xs text-ink/60 dark:text-white/60"><?php echo e($assessment->created_at?->isoFormat('DD/MM/YYYY HH:mm')); ?></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="6" class="px-3 py-8 text-center text-sm italic text-ink/40 dark:text-white/40">
                                    Aucune évaluation enregistrée. Ouvrez une entité puis « Enregistrer l’évaluation ».
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <p class="py-2 text-center text-[11px] text-ink/35 dark:text-white/35">
            Source de vérité : données réelles des tables products, batches, certifications, ocr_checks, quality_checks, transport_conditions.
        </p>
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
<?php /**PATH C:\Users\mdain\nutritrace1\nutritrace\resources\views/admin/nutrimind/index.blade.php ENDPATH**/ ?>