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
        $complianceBadgeClass = match($intelligence['compliance']['level'] ?? 'UNKNOWN') {
            'EXCELLENT' => 'bg-emerald-500/12 text-emerald-700 border border-emerald-500/15',
            'GOOD' => 'bg-forest/12 text-forest border border-forest/15',
            'ATTENTION' => 'bg-amber-warm/12 text-amber-warm border border-amber-warm/20',
            default => 'bg-red-500/12 text-red-700 border border-red-500/15',
        };

        $riskBadgeClass = match($intelligence['risk']['level'] ?? 'UNKNOWN') {
            'LOW' => 'bg-emerald-500/12 text-emerald-700 border border-emerald-500/15',
            'MEDIUM' => 'bg-amber-warm/12 text-amber-warm border border-amber-warm/20',
            default => 'bg-red-500/12 text-red-700 border border-red-500/15',
        };

        $priorityBadgeClass = fn (mixed $priority): string => match($priority ?? 'MEDIUM') {
            'HIGH' => 'bg-red-500/12 text-red-700 border border-red-500/15',
            'MEDIUM' => 'bg-amber-warm/12 text-amber-warm border border-amber-warm/20',
            default => 'bg-forest/12 text-forest border border-forest/15',
        };

        $aiRiskBadgeClass = match($ai_analysis?->risk_level ?? 'MEDIUM') {
            'LOW' => 'text-emerald-600',
            'MEDIUM' => 'text-amber-warm',
            default => 'text-red-600',
        };

        $historyRiskBadgeClass = fn (mixed $riskLevel): string => match($riskLevel ?? 'MEDIUM') {
            'LOW' => 'bg-emerald-500/12 text-emerald-700',
            'MEDIUM' => 'bg-amber-warm/12 text-amber-warm',
            default => 'bg-red-500/12 text-red-700',
        };
    ?>

     <?php $__env->slot('header', null, []); ?> 
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-warm">Certification workspace</p>
                <h1 class="mt-1 font-fraunces font-bold text-3xl text-ink leading-tight dark:text-white">
                    <?php echo e(__('Certification Intelligence')); ?>

                </h1>
                <p class="text-sm text-ink/55 dark:text-white/55 mt-2">Analyse déterministe et IA locale de la conformité des certifications.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="<?php echo e(route('admin.certifications.index')); ?>" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white hover:bg-cream text-ink border border-ink/10 rounded-lg text-sm font-semibold transition-colors">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19L5 12L12 5"/>
                    </svg>
                    Retour liste
                </a>
                <form method="POST" action="<?php echo e(route('admin.certifications.intelligence.analyze.store')); ?>" class="inline">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="refresh" value="1">
                    <button type="submit" <?php if(($analysis_status ?? null) === 'pending'): echo 'disabled'; endif; ?> class="inline-flex items-center gap-2 px-5 py-2.5 bg-forest hover:bg-forest-dark text-white font-semibold rounded-lg transition-all duration-150 shadow-sm shadow-forest/20 hover:shadow-forest/30 disabled:cursor-not-allowed disabled:opacity-60">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/>
                        </svg>
                        <?php if(($analysis_status ?? null) === 'pending'): ?>
                            <?php echo e(__('Analyse IA en cours…')); ?>

                        <?php elseif($ai_analysis): ?>
                            <?php echo e(__('Rafraîchir l’analyse IA')); ?>

                        <?php else: ?>
                            <?php echo e(__('Générer l’analyse IA')); ?>

                        <?php endif; ?>
                    </button>
                </form>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <?php if(session('success')): ?>
                <div class="px-5 py-4 rounded-xl bg-forest/5 border border-forest/15 text-forest text-sm font-medium flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-forest/10 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M20 6L9 17L4 12"/>
                        </svg>
                    </div>
                    <?php echo e(session('success')); ?>

                </div>
            <?php endif; ?>

            <?php if(session('warning')): ?>
                <div class="px-5 py-4 rounded-xl bg-amber-warm/8 border border-amber-warm/25 text-amber-warm text-sm font-medium flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-warm/12 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <path d="M12 9V13M12 17H12.01M10.29 3.86L1.82 18A2 2 0 0 0 3.56 21H20.44A2 2 0 0 0 22.18 18L13.71 3.86A2 2 0 0 0 10.29 3.86Z"/>
                        </svg>
                    </div>
                    <?php echo e(session('warning')); ?>

                </div>
            <?php endif; ?>

            <?php if(($analysis_status ?? null) === 'pending'): ?>
                <div role="status" class="px-5 py-4 rounded-xl bg-amber-warm/8 border border-amber-warm/25 text-amber-warm text-sm font-medium">
                    Analyse IA en cours. Les métriques déterministes ci-dessous restent la source de vérité; les résultats apparaîtront automatiquement.
                </div>
                <script>window.setTimeout(() => window.location.reload(), 5000);</script>
            <?php elseif(($analysis_status ?? null) === 'failed'): ?>
                <div role="alert" class="px-5 py-4 rounded-xl bg-amber-warm/8 border border-amber-warm/25 text-amber-warm text-sm font-medium">
                    L’analyse IA a échoué. Les métriques déterministes restent disponibles; vous pouvez relancer l’analyse.
                </div>
            <?php endif; ?>

            
            <section aria-label="Métriques déterministes">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                    <div class="bg-white rounded-2xl border border-ink/5 shadow-lg shadow-ink/[0.03] p-5">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-[11px] font-bold tracking-wider uppercase text-ink/45">Conformité</p>
                                <p class="font-fraunces font-bold text-3xl text-ink mt-2"><?php echo e($intelligence['compliance']['score']); ?><span class="text-lg font-semibold text-ink/40">/100</span></p>
                                <span class="inline-flex items-center gap-1 mt-3 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide <?php echo e($complianceBadgeClass); ?>">
                                    <?php echo e($intelligence['compliance']['level']); ?>

                                </span>
                            </div>
                            <div class="w-10 h-10 rounded-xl bg-forest/10 flex items-center justify-center text-forest">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                    <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z"/>
                                    <path d="M9 12L11 14L15 10"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-ink/5 shadow-lg shadow-ink/[0.03] p-5">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-[11px] font-bold tracking-wider uppercase text-ink/45">Risque</p>
                                <p class="font-fraunces font-bold text-3xl text-ink mt-2"><?php echo e($intelligence['risk']['score']); ?><span class="text-lg font-semibold text-ink/40">/100</span></p>
                                <span class="inline-flex items-center gap-1 mt-3 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide <?php echo e($riskBadgeClass); ?>">
                                    <?php echo e($intelligence['risk']['level']); ?>

                                </span>
                            </div>
                            <div class="w-10 h-10 rounded-xl bg-red-500/10 flex items-center justify-center text-red-600">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                    <path d="M12 9V13M12 17H12.01M10.29 3.86L1.82 18A2 2 0 0 0 3.56 21H20.44A2 2 0 0 0 22.18 18L13.71 3.86A2 2 0 0 0 10.29 3.86Z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-ink/5 shadow-lg shadow-ink/[0.03] p-5">
                        <div>
                            <p class="text-[11px] font-bold tracking-wider uppercase text-ink/45">Certifications</p>
                            <div class="grid grid-cols-3 gap-2 mt-3 text-center">
                                <div>
                                    <p class="font-fraunces font-bold text-2xl text-emerald-600"><?php echo e($intelligence['counts']['valid_certifications']); ?></p>
                                    <p class="text-[10px] uppercase font-bold tracking-wider text-ink/40">Valides</p>
                                </div>
                                <div>
                                    <p class="font-fraunces font-bold text-2xl text-amber-warm"><?php echo e($intelligence['counts']['expiring_soon']); ?></p>
                                    <p class="text-[10px] uppercase font-bold tracking-wider text-ink/40">≤30j</p>
                                </div>
                                <div>
                                    <p class="font-fraunces font-bold text-2xl text-red-600"><?php echo e($intelligence['counts']['expired_certifications']); ?></p>
                                    <p class="text-[10px] uppercase font-bold tracking-wider text-ink/40">Expirées</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-ink/5 shadow-lg shadow-ink/[0.03] p-5">
                        <div>
                            <p class="text-[11px] font-bold tracking-wider uppercase text-ink/45">Produits</p>
                            <div class="mt-3">
                                <div class="flex items-baseline justify-between">
                                    <span class="text-sm text-ink/60">Couverture</span>
                                    <span class="font-bold text-ink">
                                        <?php $coverage = $intelligence['impact_analysis']['coverage_percent'] ?? 0.0; ?>
                                        <?php echo e($coverage); ?>%
                                    </span>
                                </div>
                                <div class="h-2 mt-2 rounded-full bg-ink/5 overflow-hidden">
                                    <div class="h-full bg-forest transition-all" style="width: <?php echo e(max(0, min(100, (float) $coverage))); ?>%"></div>
                                </div>
                                <div class="flex justify-between mt-2 text-[11px] text-ink/50 font-medium">
                                    <span><?php echo e($intelligence['counts']['certified_products']); ?> certifiés</span>
                                    <span><?php echo e($intelligence['counts']['uncertified_products']); ?> sans certif</span>
                                    <span><?php echo e($intelligence['counts']['affected_products']); ?> impactés</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            
            <section class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl border border-ink/5 shadow-lg shadow-ink/[0.03] p-5 lg:col-span-2">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-fraunces font-bold text-lg text-ink">Facteurs de risque</h3>
                        <span class="text-[11px] uppercase tracking-wider font-bold text-ink/40">moteur déterministe</span>
                    </div>
                    <ul class="space-y-2.5">
                        <?php $__empty_1 = true; $__currentLoopData = $intelligence['risk']['factors']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $factor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <li class="flex items-start gap-3 p-3 rounded-xl bg-cream/40 border border-ink/5">
                                <span class="w-5 h-5 mt-0.5 rounded-full bg-red-500/10 text-red-600 shrink-0 flex items-center justify-center">
                                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                        <path d="M12 17L5 10L19 10"/>
                                    </svg>
                                </span>
                                <p class="text-sm text-ink/80 leading-relaxed"><?php echo e($factor); ?></p>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <li class="p-4 text-center text-sm text-ink/40 italic">Aucun facteur détecté</li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="bg-white rounded-2xl border border-ink/5 shadow-lg shadow-ink/[0.03] p-5">
                    <h3 class="font-fraunces font-bold text-lg text-ink mb-4">Expiration — 30 prochains jours</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center justify-between py-2 border-b border-ink/5">
                            <span class="text-ink/60">Déjà expirées</span>
                            <span class="font-bold text-red-600"><?php echo e($intelligence['expiration_analysis']['groups']['already_expired'] ?? 0); ?></span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-ink/5">
                            <span class="text-ink/60">Exp. 0–7 jours</span>
                            <span class="font-bold text-red-500"><?php echo e($intelligence['expiration_analysis']['groups']['0_to_7_days'] ?? 0); ?></span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-ink/5">
                            <span class="text-ink/60">Exp. 8–30 jours</span>
                            <span class="font-bold text-amber-warm"><?php echo e($intelligence['expiration_analysis']['groups']['8_to_30_days'] ?? 0); ?></span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-ink/5">
                            <span class="text-ink/60">Exp. 31–90 jours</span>
                            <span class="font-bold text-ink/80"><?php echo e($intelligence['expiration_analysis']['groups']['31_to_90_days'] ?? 0); ?></span>
                        </div>
                        <div class="flex items-center justify-between py-2">
                            <span class="text-ink/60">≥ 90 jours</span>
                            <span class="font-bold text-emerald-600"><?php echo e($intelligence['expiration_analysis']['groups']['over_90_days'] ?? 0); ?></span>
                        </div>
                    </div>
                    <?php if(!empty($intelligence['expiration_analysis']['nearest'])): ?>
                        <div class="mt-4 pt-4 border-t border-ink/5">
                            <p class="text-[11px] uppercase font-bold tracking-wider text-ink/40 mb-2">Prochaines à surveiller</p>
                            <ul class="space-y-1.5 max-h-40 overflow-auto pr-1">
                                <?php $__currentLoopData = $intelligence['expiration_analysis']['nearest']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li class="flex items-center justify-between text-xs">
                                        <span class="text-ink/70 truncate"><?php echo e($item['name']); ?></span>
                                        <span class="font-mono font-semibold <?php echo e($item['days_left'] <= 7 ? 'text-red-600' : 'text-amber-warm'); ?>">J-<?php echo e($item['days_left']); ?></span>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            
            <section class="bg-white rounded-2xl border border-ink/5 shadow-lg shadow-ink/[0.03] p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-fraunces font-bold text-lg text-ink">Actions prioritaires — moteur déterministe</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <?php $__currentLoopData = $intelligence['priority_actions']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $action): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="rounded-xl border border-ink/5 bg-cream/40 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <span class="inline-flex shrink-0 items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-widest <?php echo e($priorityBadgeClass($action['priority'] ?? 'MEDIUM')); ?>">
                                    <?php echo e($action['priority'] ?? 'MEDIUM'); ?>

                                </span>
                            </div>
                            <p class="mt-2 font-semibold text-ink"><?php echo e($action['action'] ?? ''); ?></p>
                            <p class="mt-1 text-xs text-ink/60 leading-relaxed"><?php echo e($action['reason'] ?? ''); ?></p>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </section>

            
            <section aria-label="Analyse IA" class="bg-gradient-to-br from-forest/[0.04] via-white to-amber-warm/[0.04] rounded-2xl border border-forest/10 shadow-lg shadow-forest/[0.04] p-6">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-forest to-forest-dark flex items-center justify-center shrink-0 shadow-md shadow-forest/30">
                            <svg class="w-6 h-6 text-amber-light" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-fraunces font-bold text-xl text-ink flex items-center gap-2">
                                🤖 Certification AI Insight
                                <?php if($used_ai_cache ?? false): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-ink/5 text-[10px] uppercase font-bold tracking-wider text-ink/50 border border-ink/10">Cache</span>
                                <?php endif; ?>
                            </h3>
                            <p class="text-sm text-ink/50 mt-0.5">Analyse par IA locale Ollama — les métriques déterministes restent source de vérité.</p>
                        </div>
                    </div>

                    <?php if($ai_analysis): ?>
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <?php if($ai_analysis->compliance_score !== null && $ai_analysis->risk_level !== null): ?>
                                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white border border-ink/10 shadow-sm">
                                    <span class="text-ink/45 uppercase tracking-wider text-[10px] font-bold">Snapshot</span>
                                    <span class="font-bold text-forest">C: <?php echo e($ai_analysis->compliance_score); ?></span>
                                    <span class="text-ink/20">·</span>
                                    <span class="font-bold <?php echo e($aiRiskBadgeClass); ?>">R: <?php echo e($ai_analysis->risk_level); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if($ai_analysis->model): ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-ink/10 text-ink/60 font-mono text-[11px] shadow-sm">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    <?php echo e($ai_analysis->model); ?>

                                </span>
                            <?php endif; ?>
                            <?php if($ai_analysis->created_at): ?>
                                <span class="text-ink/40 font-mono text-[11px]">
                                    <?php echo e($ai_analysis->created_at->isoFormat('DD/MM/YYYY HH:mm')); ?>

                                </span>
                            <?php endif; ?>
                            <?php if($ai_analysis->confidence !== null): ?>
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-ink/10 text-ink/60 text-[11px] shadow-sm">
                                    <svg class="w-3.5 h-3.5 text-forest" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                        <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z"/>
                                        <path d="M9 12L11 14L15 10"/>
                                    </svg>
                                    <span class="uppercase tracking-wider text-[10px] font-bold text-ink/45">Confiance IA</span>
                                    <span class="font-bold text-ink"><?php echo e($ai_analysis->confidence); ?>%</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if($ai_analysis): ?>
                    <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
                        <div class="lg:col-span-3 space-y-4">
                            <div class="bg-white rounded-xl border border-ink/5 shadow-sm p-4">
                                <p class="text-[11px] uppercase tracking-wider font-bold text-forest/80 mb-2">Synthèse IA</p>
                                <p class="text-ink/85 leading-relaxed"><?php echo e($ai_analysis->summary); ?></p>
                            </div>

                            <div class="bg-white rounded-xl border border-ink/5 shadow-sm p-4">
                                <p class="text-[11px] uppercase tracking-wider font-bold text-red-600/80 mb-2">Explication du risque</p>
                                <p class="text-ink/85 leading-relaxed"><?php echo e($ai_analysis->risk_explanation); ?></p>
                            </div>

                            <div class="bg-white rounded-xl border border-ink/5 shadow-sm p-4">
                                <p class="text-[11px] uppercase tracking-wider font-bold text-amber-warm mb-2">Impact métier</p>
                                <p class="text-ink/85 leading-relaxed"><?php echo e($ai_analysis->business_impact); ?></p>
                            </div>
                        </div>

                        <div class="lg:col-span-2 space-y-4">
                            <div class="bg-white rounded-xl border border-ink/5 shadow-sm p-4">
                                <p class="text-[11px] uppercase tracking-wider font-bold text-ink/50 mb-3">Points clés</p>
                                <ul class="space-y-2">
                                    <?php $__empty_1 = true; $__currentLoopData = $ai_analysis->key_insights; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $insight): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <li class="flex items-start gap-2.5">
                                            <span class="mt-1 w-1.5 h-1.5 rounded-full bg-forest shrink-0"></span>
                                            <p class="text-sm text-ink/80 leading-relaxed"><?php echo e($insight); ?></p>
                                        </li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <li class="text-sm text-ink/40 italic">Aucun insight IA disponible</li>
                                    <?php endif; ?>
                                </ul>
                            </div>

                            <div class="bg-white rounded-xl border border-ink/5 shadow-sm p-4">
                                <p class="text-[11px] uppercase tracking-wider font-bold text-ink/50 mb-3">Actions priorisées (IA)</p>
                                <div class="space-y-2.5">
                                    <?php $__empty_1 = true; $__currentLoopData = $ai_analysis->priority_actions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $action): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <div class="rounded-lg border border-ink/5 bg-cream/40 p-3">
                                            <div class="flex items-center justify-between gap-2 mb-1.5">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-widest <?php echo e($priorityBadgeClass($action['priority'] ?? 'MEDIUM')); ?>">
                                                    <?php echo e($action['priority'] ?? 'MEDIUM'); ?>

                                                </span>
                                            </div>
                                            <p class="text-sm font-semibold text-ink"><?php echo e($action['action'] ?? ''); ?></p>
                                            <?php if(!empty($action['reason'])): ?>
                                                <p class="text-[12px] text-ink/55 mt-1 leading-relaxed"><?php echo e($action['reason']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <p class="text-sm text-ink/40 italic">Aucune action IA disponible</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-10 rounded-xl border border-dashed border-forest/15 bg-white/40">
                        <div class="w-14 h-14 rounded-2xl bg-forest/10 text-forest flex items-center justify-center mx-auto mb-4">
                            <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/>
                            </svg>
                        </div>
                        <?php if(($analysis_status ?? null) === 'pending'): ?>
                            <p class="font-semibold text-ink mb-1">Analyse IA en attente</p>
                            <p class="text-sm text-ink/50 max-w-md mx-auto">Le résultat apparaîtra ici dès que le traitement en arrière-plan sera terminé.</p>
                        <?php else: ?>
                            <p class="font-semibold text-ink mb-1">Aucune analyse IA sauvegardée</p>
                            <p class="text-sm text-ink/50 mb-5 max-w-md mx-auto">Cliquez sur « Générer l’analyse IA » pour synthétiser les métriques déterministes via le modèle Qwen local Ollama.</p>
                            <form method="POST" action="<?php echo e(route('admin.certifications.intelligence.analyze.store')); ?>" class="inline">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-forest hover:bg-forest-dark text-white text-sm font-semibold rounded-lg transition-colors shadow-md shadow-forest/20">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                        <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/>
                                    </svg>
                                    Générer la première analyse IA
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>

            
            <?php if($history->isNotEmpty()): ?>
                <section class="bg-white rounded-2xl border border-ink/5 shadow-lg shadow-ink/[0.03] p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="font-fraunces font-bold text-lg text-ink">Historique analyses IA</h3>
                            <p class="text-xs text-ink/50 mt-0.5">Comparatif des analyses précédentes — <?php echo e($history->count()); ?> dernière(s) entrée(s).</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-ink/5">
                            <thead class="bg-cream/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold tracking-wider text-ink/50 uppercase">Date</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold tracking-wider text-ink/50 uppercase">Statut</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold tracking-wider text-ink/50 uppercase">Conformité</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold tracking-wider text-ink/50 uppercase">Risque</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold tracking-wider text-ink/50 uppercase">Modèle</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold tracking-wider text-ink/50 uppercase">Durée</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-bold tracking-wider text-ink/50 uppercase">Confiance IA</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-ink/5 bg-white text-sm">
                                <?php $__currentLoopData = $history; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr class="hover:bg-cream/30 transition-colors">
                                        <td class="px-4 py-3 whitespace-nowrap font-mono text-ink/80"><?php echo e($entry->created_at?->isoFormat('DD/MM/YYYY HH:mm') ?? '—'); ?></td>
                                        <td class="px-4 py-3"><span class="rounded-full px-2 py-1 text-[10px] font-bold uppercase <?php echo e($entry->status === 'pending' ? 'bg-amber-warm/12 text-amber-warm' : ($entry->status === 'failed' ? 'bg-red-500/10 text-red-600' : 'bg-emerald-500/10 text-emerald-700')); ?>"><?php echo e($entry->status); ?></span></td>
                                        <td class="px-4 py-3">
                                            <?php if($entry->compliance_score !== null): ?>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-ink"><?php echo e($entry->compliance_score); ?>/100</span>
                                                    <?php if($entry->compliance_level): ?>
                                                        <span class="text-[10px] uppercase tracking-wider font-bold text-ink/50"><?php echo e($entry->compliance_level); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-ink/30">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <?php if($entry->risk_level || $entry->risk_score !== null): ?>
                                                <div class="flex items-center gap-2">
                                                    <?php if($entry->risk_score !== null): ?>
                                                        <span class="font-bold text-ink"><?php echo e($entry->risk_score); ?></span>
                                                    <?php endif; ?>
                                                    <?php if($entry->risk_level): ?>
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider <?php echo e($historyRiskBadgeClass($entry->risk_level)); ?>">
                                                            <?php echo e($entry->risk_level); ?>

                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-ink/30">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap font-mono text-xs text-ink/60"><?php echo e($entry->model ?? '—'); ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <?php if($entry->duration_ms !== null): ?>
                                                <span class="font-mono text-xs text-ink/60"><?php echo e(number_format($entry->duration_ms, 0, ',', ' ')); ?> ms</span>
                                            <?php else: ?>
                                                <span class="text-ink/30">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <?php if($entry->confidence !== null): ?>
                                                <span class="font-bold text-forest"><?php echo e($entry->confidence); ?>%</span>
                                            <?php else: ?>
                                                <span class="text-[11px] uppercase tracking-wider text-ink/40 font-bold">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>

            <p class="text-center text-[11px] text-ink/35 py-2">
                Source de vérité : Laravel · Données structurées générées <?php echo e($intelligence['snapshot']['generated_at'] ?? ''); ?>

            </p>
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
<?php /**PATH C:\Users\mdain\nutritrace1\nutritrace\resources\views/admin/certifications/intelligence.blade.php ENDPATH**/ ?>