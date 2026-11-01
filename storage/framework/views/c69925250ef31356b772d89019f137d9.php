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
        $entityLabel = match ($type) {
            'product' => $entity->name,
            'batch' => $entity->lot_number,
            'supplier' => $entity->organization_name ?: $entity->name,
            default => '#'.$entity->getKey(),
        };
        $entityTypeLabel = match ($type) {
            'product' => 'Produit',
            'batch' => 'Lot',
            'supplier' => 'Fournisseur',
            default => ucfirst($type),
        };
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
        $riskFindings = $evaluation['risk_findings'];
        $maxContribution = max(1, (float) (collect($riskFindings)->max('contribution') ?: 1));

        // --- Mise en page du graphe d'impact (SVG construit à la main) ---
        $graphNodes = collect($impact['nodes']);
        $byDepth = $graphNodes->groupBy('depth')->sortKeys();
        $maxDepth = (int) $graphNodes->max('depth');
        $columnWidth = 226;
        $nodeRadius = 26;
        $rowGap = 78;
        $paddingX = 44;
        $paddingY = 34;
        $maxRows = max(1, $byDepth->map->count()->max());
        $graphHeight = max(230, $maxRows * $rowGap + 2 * $paddingY);
        $graphWidth = $paddingX * 2 + $nodeRadius * 2 + $maxDepth * $columnWidth;

        $positions = [];
        foreach ($byDepth as $depth => $group) {
            $count = $group->count();
            $startY = ($graphHeight - ($count - 1) * $rowGap) / 2;
            foreach ($group->values() as $i => $node) {
                $positions[$node['key']] = [
                    'x' => $paddingX + $nodeRadius + $depth * $columnWidth,
                    'y' => $startY + $i * $rowGap,
                ];
            }
        }

        $impactColor = fn (string $impact): string => match ($impact) {
            'confirmed' => '#b23a3a',
            'potential' => '#c17817',
            default => '#9aa5a0',
        };
        $impactLabel = fn (string $impact): string => match ($impact) {
            'confirmed' => 'Confirmé',
            'potential' => 'Potentiel',
            default => 'Incertain',
        };
        $conclusionMeta = [
            'improvement_confirmed' => ['label' => 'Amélioration confirmée', 'class' => 'border-emerald-500/25 bg-emerald-500/10 text-emerald-800 dark:text-emerald-300'],
            'improvement_unconfirmed' => ['label' => 'Baisse non confirmée', 'class' => 'border-amber-warm/25 bg-amber-warm/10 text-amber-warm'],
            'regression' => ['label' => 'Régression du risque', 'class' => 'border-red-500/25 bg-red-500/10 text-red-700 dark:text-red-300'],
            'stable' => ['label' => 'Situation stable', 'class' => 'border-ink/10 bg-ink/5 text-ink/70 dark:border-white/10 dark:text-white/70'],
        ];
    ?>

     <?php $__env->slot('header', null, []); ?> 
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-warm">NutriMind · <?php echo e($entityTypeLabel); ?></p>
                <h1 class="mt-1 font-fraunces text-3xl font-bold leading-tight text-ink dark:text-white"><?php echo e($entityLabel); ?></h1>
                <p class="mt-2 text-sm text-ink/55 dark:text-white/55">Évaluation explicable, impact, simulation et recommandations correctives.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="<?php echo e(route('admin.nutrimind.index')); ?>"
                   class="inline-flex items-center gap-2 rounded-lg border border-ink/10 bg-white px-4 py-2.5 text-sm font-semibold text-ink transition-colors hover:bg-cream dark:border-white/10 dark:bg-[#1b2923] dark:text-white">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19 5 12l7-7"/></svg>
                    Vue d’ensemble
                </a>
                <form method="POST" action="<?php echo e(route('admin.nutrimind.assess', ['type' => $type, 'id' => $entity->getKey()])); ?>"
                      x-data="{ submitting: false }" @submit="submitting = true">
                    <?php echo csrf_field(); ?>
                    <button type="submit" :disabled="submitting"
                            class="inline-flex items-center gap-2 rounded-lg bg-forest px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-forest-dark disabled:cursor-not-allowed disabled:opacity-60">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
                        <span x-show="!submitting">Enregistrer l’évaluation</span>
                        <span x-show="submitting" x-cloak>Enregistrement…</span>
                    </button>
                </form>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="mx-auto max-w-[1440px] space-y-6">
        <?php if(session('success')): ?>
            <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-800 dark:text-emerald-300"><?php echo e(session('success')); ?></div>
        <?php endif; ?>

        
        <section class="grid grid-cols-1 gap-4 lg:grid-cols-4">
            <div class="flex flex-col items-center justify-center rounded-2xl border border-ink/5 bg-white p-5 shadow-lg shadow-ink/[0.03] dark:border-white/10 dark:bg-[#1b2923]">
                <?php if (isset($component)) { $__componentOriginal9477b4fc347bdb0f1c8b2ec3e1d4fddf = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9477b4fc347bdb0f1c8b2ec3e1d4fddf = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard.risk-gauge','data' => ['score' => $evaluation['score'],'severity' => $evaluation['severity'],'caption' => 'Score de risque']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard.risk-gauge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['score' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($evaluation['score']),'severity' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($evaluation['severity']),'caption' => 'Score de risque']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9477b4fc347bdb0f1c8b2ec3e1d4fddf)): ?>
<?php $attributes = $__attributesOriginal9477b4fc347bdb0f1c8b2ec3e1d4fddf; ?>
<?php unset($__attributesOriginal9477b4fc347bdb0f1c8b2ec3e1d4fddf); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9477b4fc347bdb0f1c8b2ec3e1d4fddf)): ?>
<?php $component = $__componentOriginal9477b4fc347bdb0f1c8b2ec3e1d4fddf; ?>
<?php unset($__componentOriginal9477b4fc347bdb0f1c8b2ec3e1d4fddf); ?>
<?php endif; ?>
                <?php if($evaluation['critical_override']): ?>
                    <p class="mt-3 rounded-lg bg-red-500/10 px-3 py-1.5 text-center text-[11px] font-semibold text-red-700 dark:text-red-300">
                        Incident de sécurité confirmé — sévérité critique forcée.
                    </p>
                <?php endif; ?>
            </div>

            <div class="rounded-2xl border border-ink/5 bg-white p-5 shadow-lg shadow-ink/[0.03] dark:border-white/10 dark:bg-[#1b2923]">
                <p class="text-[11px] font-bold uppercase tracking-wider text-ink/45 dark:text-white/45">Complétude des données</p>
                <p class="mt-2 font-fraunces text-3xl font-bold text-ink dark:text-white"><?php echo e(number_format($evaluation['data_completeness'], 0)); ?><span class="text-lg text-ink/40">%</span></p>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-ink/5 dark:bg-white/10">
                    <div class="h-full bg-forest transition-all" style="width: <?php echo e(max(0, min(100, (float) $evaluation['data_completeness']))); ?>%"></div>
                </div>
                <p class="mt-3 text-xs text-ink/55 dark:text-white/55"><?php echo e(count($evaluation['missing_findings'])); ?> information(s) manquante(s), hors score.</p>
            </div>

            <div class="rounded-2xl border border-ink/5 bg-white p-5 shadow-lg shadow-ink/[0.03] dark:border-white/10 dark:bg-[#1b2923]">
                <p class="text-[11px] font-bold uppercase tracking-wider text-ink/45 dark:text-white/45">Constatations</p>
                <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                    <div>
                        <p class="font-fraunces text-2xl font-bold text-red-600 dark:text-red-300"><?php echo e($evaluation['counts']['critical'] ?? 0); ?></p>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-ink/40 dark:text-white/40">Critiques</p>
                    </div>
                    <div>
                        <p class="font-fraunces text-2xl font-bold text-amber-warm"><?php echo e($evaluation['counts']['warning'] ?? 0); ?></p>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-ink/40 dark:text-white/40">Alerte</p>
                    </div>
                    <div>
                        <p class="font-fraunces text-2xl font-bold text-emerald-600 dark:text-emerald-300"><?php echo e($evaluation['counts']['info'] ?? 0); ?></p>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-ink/40 dark:text-white/40">Info</p>
                    </div>
                </div>
                <p class="mt-3 text-xs text-ink/55 dark:text-white/55">Version du jeu de règles : <?php echo e($evaluation['rule_set_version']); ?>.</p>
            </div>

            <div class="rounded-2xl border border-ink/5 bg-white p-5 shadow-lg shadow-ink/[0.03] dark:border-white/10 dark:bg-[#1b2923]">
                <p class="text-[11px] font-bold uppercase tracking-wider text-ink/45 dark:text-white/45">Impact (graphe)</p>
                <div class="mt-3 space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-ink/60 dark:text-white/60">Confirmé</span>
                        <span class="font-bold text-red-600 dark:text-red-300"><?php echo e($impact['summary']['confirmed'] ?? 0); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-ink/60 dark:text-white/60">Potentiel</span>
                        <span class="font-bold text-amber-warm"><?php echo e($impact['summary']['potential'] ?? 0); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-ink/60 dark:text-white/60">Incertain</span>
                        <span class="font-bold text-ink/70 dark:text-white/70"><?php echo e($impact['summary']['unknown'] ?? 0); ?></span>
                    </div>
                </div>
                <p class="mt-3 text-xs text-ink/55 dark:text-white/55"><?php echo e(count($impact['nodes'])); ?> entité(s) explorée(s), profondeur ≤ <?php echo e($impact['bounds']['max_depth']); ?>.</p>
            </div>
        </section>

        
        <section class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="rounded-2xl border border-ink/5 bg-white p-5 shadow-lg shadow-ink/[0.03] dark:border-white/10 dark:bg-[#1b2923] lg:col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="font-fraunces text-lg font-bold text-ink dark:text-white">Facteurs contributifs</h3>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-ink/40 dark:text-white/40">chaque point est justifié</span>
                </div>
                <ul class="space-y-3">
                    <?php $__empty_1 = true; $__currentLoopData = $riskFindings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $finding): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <li class="rounded-xl border border-ink/5 bg-cream/40 p-4 dark:border-white/10 dark:bg-white/5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider <?php echo e($severityBadge($finding['severity'])); ?>"><?php echo e($severityLabel($finding['severity'])); ?></span>
                                    <span class="font-semibold text-ink dark:text-white"><?php echo e($finding['label']); ?></span>
                                </div>
                                <span class="font-fraunces text-lg font-bold text-ink dark:text-white">+<?php echo e(rtrim(rtrim(number_format((float) $finding['contribution'], 2, '.', ''), '0'), '.')); ?></span>
                            </div>
                            <p class="mt-2 text-sm leading-relaxed text-ink/75 dark:text-white/75"><?php echo e($finding['reason']); ?></p>
                            <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-ink/5 dark:bg-white/10">
                                    <div class="h-full bg-red-500/70" style="width: <?php echo e(round((float) $finding['contribution'] / $maxContribution * 100)); ?>%"></div>
                                </div>
                                <span class="text-[11px] text-ink/50 dark:text-white/50">Source : <?php echo e($finding['source_label'] ?? '—'); ?></span>
                            </div>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <li class="rounded-xl border border-dashed border-emerald-500/20 bg-emerald-500/5 p-6 text-center text-sm italic text-emerald-700 dark:text-emerald-300">
                            Aucun risque confirmé — le score est de 0.
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="rounded-2xl border border-ink/5 bg-white p-5 shadow-lg shadow-ink/[0.03] dark:border-white/10 dark:bg-[#1b2923]">
                <h3 class="mb-4 font-fraunces text-lg font-bold text-ink dark:text-white">Informations manquantes</h3>
                <ul class="space-y-2.5">
                    <?php $__empty_1 = true; $__currentLoopData = $evaluation['missing_findings']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $finding): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <li class="flex items-start gap-3 rounded-xl bg-ink/5 p-3 dark:bg-white/5">
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-amber-warm/15 text-amber-warm">
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M12 8v5M12 16h.01"/></svg>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-ink dark:text-white"><?php echo e($finding['label']); ?></p>
                                <p class="mt-0.5 text-xs leading-relaxed text-ink/60 dark:text-white/60"><?php echo e($finding['reason']); ?></p>
                            </div>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <li class="rounded-xl border border-dashed border-ink/10 p-6 text-center text-sm italic text-ink/40 dark:border-white/10 dark:text-white/40">
                            Toutes les données attendues sont présentes.
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </section>

        
        <section class="rounded-2xl border border-ink/5 bg-white p-5 shadow-lg shadow-ink/[0.03] dark:border-white/10 dark:bg-[#1b2923]">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h3 class="font-fraunces text-lg font-bold text-ink dark:text-white">Recommandations correctives</h3>
                    <p class="text-xs text-ink/50 dark:text-white/50">Les actions réutilisent les workflows existants ; aucune action n’est déclenchée automatiquement.</p>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <?php $__empty_1 = true; $__currentLoopData = $recommendations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $recommendation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="flex flex-col rounded-xl border border-ink/5 bg-cream/40 p-4 dark:border-white/10 dark:bg-white/5">
                        <div class="flex items-start justify-between gap-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider <?php echo e($severityBadge($recommendation['severity'])); ?>"><?php echo e($severityLabel($recommendation['severity'])); ?></span>
                            <?php if($recommendation['consequential']): ?>
                                <span class="inline-flex items-center gap-1 rounded-full bg-red-500/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-red-700 dark:text-red-300">
                                    Action sensible
                                </span>
                            <?php endif; ?>
                        </div>
                        <p class="mt-2 font-semibold text-ink dark:text-white"><?php echo e($recommendation['title']); ?></p>
                        <?php if($recommendation['guidance']): ?>
                            <p class="mt-1 text-sm leading-relaxed text-ink/70 dark:text-white/70"><?php echo e($recommendation['guidance']); ?></p>
                        <?php endif; ?>
                        <p class="mt-2 text-xs text-ink/50 dark:text-white/50">Constatation : <?php echo e($recommendation['reason']); ?></p>

                        <div class="mt-3 flex flex-wrap items-center gap-2 pt-3 border-t border-ink/5 dark:border-white/10">
                            <?php if($recommendation['action']): ?>
                                <a href="<?php echo e($recommendation['action']['url']); ?>"
                                   <?php if($recommendation['consequential']): ?> onclick="return confirm('Action sensible : <?php echo e($recommendation['title']); ?>. Confirmer l’ouverture de ce workflow ?')" <?php endif; ?>
                                   class="inline-flex items-center gap-1.5 rounded-lg bg-forest/10 px-3 py-1.5 text-xs font-semibold text-forest transition-colors hover:bg-forest/20 dark:text-emerald-300">
                                    <?php echo e($recommendation['action']['label']); ?>

                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                                </a>
                            <?php else: ?>
                                <span class="text-[11px] italic text-ink/45 dark:text-white/45">Aucun workflow dédié — suivez la recommandation ci-dessus.</span>
                            <?php endif; ?>

                            <form method="POST" action="<?php echo e(route('admin.nutrimind.actions.store', ['type' => $type, 'id' => $entity->getKey()])); ?>" class="inline">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="rule" value="<?php echo e($recommendation['rule']); ?>">
                                <input type="hidden" name="label" value="<?php echo e($recommendation['title']); ?>">
                                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-ink/10 px-3 py-1.5 text-xs font-semibold text-ink/70 transition-colors hover:bg-white dark:border-white/10 dark:text-white/70">
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
                                    Journaliser
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="md:col-span-2 rounded-xl border border-dashed border-emerald-500/20 bg-emerald-500/5 p-6 text-center text-sm italic text-emerald-700 dark:text-emerald-300">
                        Aucune recommandation : aucune constatation de risque ni donnée manquante.
                    </div>
                <?php endif; ?>
            </div>
        </section>

        
        <section class="rounded-2xl border border-ink/5 bg-white p-5 shadow-lg shadow-ink/[0.03] dark:border-white/10 dark:bg-[#1b2923]">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="font-fraunces text-lg font-bold text-ink dark:text-white">Graphe d’impact</h3>
                    <p class="text-xs text-ink/50 dark:text-white/50">Dépendances directes et indirectes issues des relations réelles. Un nœud n’est visité qu’une fois.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3 text-[11px] font-semibold">
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full" style="background:#b23a3a"></span>Confirmé</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full" style="background:#c17817"></span>Potentiel</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full" style="background:#9aa5a0"></span>Incertain</span>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl bg-cream/40 p-2 dark:bg-white/5">
                <svg viewBox="0 0 <?php echo e($graphWidth); ?> <?php echo e($graphHeight); ?>" width="<?php echo e(max(640, $graphWidth)); ?>" height="<?php echo e($graphHeight); ?>" role="img" aria-label="Graphe d’impact des entités affectées" class="max-w-none">
                    <?php $__currentLoopData = $impact['edges']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $edge): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php ($from = $positions[$edge['from']] ?? null); ?>
                        <?php ($to = $positions[$edge['to']] ?? null); ?>
                        <?php if($from && $to): ?>
                            <line x1="<?php echo e($from['x']); ?>" y1="<?php echo e($from['y']); ?>" x2="<?php echo e($to['x']); ?>" y2="<?php echo e($to['y']); ?>"
                                  stroke="<?php echo e($edge['cycle'] ? '#d9c3a0' : '#c8d3cc'); ?>"
                                  stroke-width="<?php echo e($edge['cycle'] ? 1.2 : 1.6); ?>"
                                  <?php if($edge['cycle']): ?> stroke-dasharray="4 4" <?php endif; ?> />
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                    <?php $__currentLoopData = $impact['nodes']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $node): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php ($pos = $positions[$node['key']] ?? null); ?>
                        <?php if($pos): ?>
                            <circle cx="<?php echo e($pos['x']); ?>" cy="<?php echo e($pos['y']); ?>" r="<?php echo e($nodeRadius); ?>"
                                    fill="<?php echo e($impactColor($node['impact'])); ?>" fill-opacity="0.16"
                                    stroke="<?php echo e($impactColor($node['impact'])); ?>" stroke-width="2" />
                            <text x="<?php echo e($pos['x']); ?>" y="<?php echo e($pos['y'] - 2); ?>" text-anchor="middle" font-size="10" font-weight="700" fill="<?php echo e($impactColor($node['impact'])); ?>">
                                <?php echo e(\Illuminate\Support\Str::upper(mb_substr($node['kind'], 0, 10))); ?>

                            </text>
                            <text x="<?php echo e($pos['x']); ?>" y="<?php echo e($pos['y'] + 10); ?>" text-anchor="middle" font-size="9" fill="#5b6a62">
                                #<?php echo e($node['id']); ?> · p<?php echo e($node['depth']); ?>

                            </text>
                            <text x="<?php echo e($pos['x']); ?>" y="<?php echo e($pos['y'] + $nodeRadius + 13); ?>" text-anchor="middle" font-size="10" font-weight="600" fill="#1c1c1a">
                                <?php echo e(\Illuminate\Support\Str::limit($node['label'], 20)); ?>

                            </text>
                            <?php if(!empty($node['signal'])): ?>
                                <text x="<?php echo e($pos['x']); ?>" y="<?php echo e($pos['y'] + $nodeRadius + 25); ?>" text-anchor="middle" font-size="9" font-style="italic" fill="<?php echo e($impactColor($node['impact'])); ?>">
                                    <?php echo e($node['signal']['label']); ?>

                                </text>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </svg>
            </div>

            <?php if($impact['bounds']['truncated']): ?>
                <p class="mt-3 rounded-lg bg-amber-warm/10 px-3 py-2 text-xs font-medium text-amber-warm">
                    Exploration tronquée : le nombre de nœuds a atteint la limite (<?php echo e($impact['bounds']['max_nodes']); ?>).
                </p>
            <?php endif; ?>

            <details class="mt-4">
                <summary class="cursor-pointer text-sm font-semibold text-ink/70 dark:text-white/70">Détail des chemins de dépendance</summary>
                <ul class="mt-3 space-y-2">
                    <?php $__currentLoopData = $impact['nodes']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $node): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if(empty($node['path'])) continue; ?>
                        <li class="rounded-lg border border-ink/5 bg-cream/40 px-3 py-2 text-xs dark:border-white/10 dark:bg-white/5">
                            <span class="font-semibold text-ink dark:text-white"><?php echo e($node['label']); ?></span>
                            <span class="text-ink/50 dark:text-white/50">
                                ← <?php echo e(collect($node['path'])->map(fn ($step) => $step['from'].' → '.$step['to'].' ('.$step['relation'].')')->implode(' ; ')); ?>

                            </span>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </details>
        </section>

        
        <section class="rounded-2xl border border-ink/5 bg-white p-5 shadow-lg shadow-ink/[0.03] dark:border-white/10 dark:bg-[#1b2923]">
            <div class="mb-4">
                <h3 class="font-fraunces text-lg font-bold text-ink dark:text-white">Simulation « what-if »</h3>
                <p class="text-xs text-ink/50 dark:text-white/50">Compare l’état réel à un scénario hypothétique. Aucune donnée réelle n’est modifiée.</p>
            </div>

            <form method="GET" action="<?php echo e(route('admin.nutrimind.show', ['type' => $type, 'id' => $entity->getKey()])); ?>" class="mb-5">
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    <?php $__currentLoopData = $scenarios; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $scenario): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <label class="flex cursor-pointer items-start gap-2.5 rounded-xl border border-ink/10 bg-cream/40 p-3 text-sm transition-colors hover:border-forest/30 dark:border-white/10 dark:bg-white/5">
                            <input type="checkbox" name="scenarios[]" value="<?php echo e($scenario['key']); ?>" <?php if(in_array($scenario['key'], $selected, true)): echo 'checked'; endif; ?>
                                   class="mt-0.5 h-4 w-4 rounded border-ink/20 text-forest focus:ring-forest">
                            <span>
                                <span class="block font-semibold text-ink dark:text-white"><?php echo e($scenario['label']); ?></span>
                                <span class="mt-0.5 block text-xs text-ink/55 dark:text-white/55"><?php echo e($scenario['description']); ?></span>
                            </span>
                        </label>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <div class="mt-4 flex items-center gap-2">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-forest px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-forest-dark">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 3v4M3 5h4M6 17v4M4 19h4M13 3l2.5 6.5L22 12l-6.5 2.5L13 21l-2.5-6.5L4 12l6.5-2.5L13 3Z"/></svg>
                        Lancer la simulation
                    </button>
                    <?php if($simulation): ?>
                        <a href="<?php echo e(route('admin.nutrimind.show', ['type' => $type, 'id' => $entity->getKey()])); ?>" class="text-sm font-semibold text-ink/60 hover:text-ink dark:text-white/60">Réinitialiser</a>
                    <?php endif; ?>
                </div>
            </form>

            <?php if($simulation): ?>
                <div class="rounded-xl border border-amber-warm/25 bg-amber-warm/5 p-4">
                    <p class="mb-4 text-[11px] font-bold uppercase tracking-wider text-amber-warm">
                        Résultat hypothétique — scénario(s) : <?php echo e(collect($simulation['scenarios'])->pluck('label')->implode(', ')); ?>

                    </p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="rounded-xl border border-ink/5 bg-white p-4 text-center dark:border-white/10 dark:bg-[#1b2923]">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-ink/45 dark:text-white/45">Réel</p>
                            <p class="mt-1 font-fraunces text-3xl font-bold text-ink dark:text-white"><?php echo e(number_format($simulation['baseline']['score'], 0)); ?></p>
                            <span class="mt-1 inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider <?php echo e($severityBadge($simulation['baseline']['severity'])); ?>"><?php echo e($severityLabel($simulation['baseline']['severity'])); ?></span>
                        </div>
                        <div class="flex flex-col items-center justify-center rounded-xl border border-ink/5 bg-white p-4 text-center dark:border-white/10 dark:bg-[#1b2923]">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-ink/45 dark:text-white/45">Écart</p>
                            <p class="mt-1 font-fraunces text-3xl font-bold <?php echo e($simulation['delta']['score'] > 0 ? 'text-red-600 dark:text-red-300' : 'text-emerald-600 dark:text-emerald-300'); ?>">
                                <?php echo e($simulation['delta']['score'] > 0 ? '+' : ''); ?><?php echo e(number_format($simulation['delta']['score'], 0)); ?>

                            </p>
                            <?php if($simulation['delta']['severity_changed']): ?>
                                <p class="mt-1 text-xs text-ink/55 dark:text-white/55"><?php echo e($severityLabel($simulation['delta']['from'])); ?> → <?php echo e($severityLabel($simulation['delta']['to'])); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="rounded-xl border border-ink/5 bg-white p-4 text-center dark:border-white/10 dark:bg-[#1b2923]">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-ink/45 dark:text-white/45">Simulé</p>
                            <p class="mt-1 font-fraunces text-3xl font-bold text-ink dark:text-white"><?php echo e(number_format($simulation['simulated']['score'], 0)); ?></p>
                            <span class="mt-1 inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider <?php echo e($severityBadge($simulation['simulated']['severity'])); ?>"><?php echo e($severityLabel($simulation['simulated']['severity'])); ?></span>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                        <div class="rounded-lg border border-red-500/15 bg-white p-3 dark:border-white/10 dark:bg-[#1b2923]">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-red-600 dark:text-red-300">Nouvelles constatations</p>
                            <ul class="mt-2 space-y-1 text-xs text-ink/75 dark:text-white/75">
                                <?php $__empty_1 = true; $__currentLoopData = $simulation['findings']['new']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $finding): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <li>+ <?php echo e($finding['label']); ?> (+<?php echo e(rtrim(rtrim(number_format((float) $finding['contribution'], 2, '.', ''), '0'), '.')); ?>)</li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <li class="italic text-ink/40 dark:text-white/40">Aucune</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <div class="rounded-lg border border-ink/5 bg-white p-3 dark:border-white/10 dark:bg-[#1b2923]">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-ink/50 dark:text-white/50">Constatations maintenues</p>
                            <ul class="mt-2 space-y-1 text-xs text-ink/75 dark:text-white/75">
                                <?php $__empty_1 = true; $__currentLoopData = $simulation['findings']['remaining']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $finding): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <li>= <?php echo e($finding['label']); ?></li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <li class="italic text-ink/40 dark:text-white/40">Aucune</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <div class="rounded-lg border border-emerald-500/15 bg-white p-3 dark:border-white/10 dark:bg-[#1b2923]">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-300">Levrées par l’hypothèse</p>
                            <ul class="mt-2 space-y-1 text-xs text-ink/75 dark:text-white/75">
                                <?php $__empty_1 = true; $__currentLoopData = $simulation['findings']['resolved']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $finding): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <li>− <?php echo e($finding['label']); ?></li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <li class="italic text-ink/40 dark:text-white/40">Aucune</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        
        <section class="rounded-2xl border border-ink/5 bg-white p-5 shadow-lg shadow-ink/[0.03] dark:border-white/10 dark:bg-[#1b2923]">
            <div class="mb-4">
                <h3 class="font-fraunces text-lg font-bold text-ink dark:text-white">Historique & comparaison</h3>
                <p class="text-xs text-ink/50 dark:text-white/50">Les évaluations enregistrées ne sont jamais écrasées : la comparaison exige une preuve (constatation levée).</p>
            </div>

            <?php if($comparison): ?>
                <?php ($meta = $conclusionMeta[$comparison['conclusion']] ?? $conclusionMeta['stable']); ?>
                <div class="mb-4 rounded-xl border p-4 <?php echo e($meta['class']); ?>">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="font-semibold"><?php echo e($meta['label']); ?></p>
                        <p class="font-mono text-xs">
                            <?php echo e(rtrim(rtrim(number_format($comparison['before']['score'], 2, '.', ''), '0'), '.')); ?> → <?php echo e(rtrim(rtrim(number_format($comparison['after']['score'], 2, '.', ''), '0'), '.')); ?>

                            (<?php echo e($comparison['delta']['score'] > 0 ? '+' : ''); ?><?php echo e(rtrim(rtrim(number_format($comparison['delta']['score'], 2, '.', ''), '0'), '.')); ?>)
                        </p>
                    </div>

                    <?php if($comparison['conclusion'] === 'improvement_unconfirmed'): ?>
                        <p class="mt-2 text-xs">Le score a baissé mais aucune constatation n’a été levée : vérifiez l’intégrité des données avant de conclure à une amélioration.</p>
                    <?php endif; ?>

                    <?php if(!empty($comparison['evidence'])): ?>
                        <ul class="mt-3 space-y-1 text-xs">
                            <?php $__currentLoopData = $comparison['evidence']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $proof): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li>✓ <?php echo e($proof['label'] ?? $proof['rule']); ?> — <?php echo e($proof['source_label'] ?? 'source inconnue'); ?></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    <?php endif; ?>

                    <?php if(!empty($comparison['new'])): ?>
                        <p class="mt-3 text-xs font-semibold"><?php echo e(count($comparison['new'])); ?> nouvelle(s) constatation(s) apparue(s).</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left">
                            <th class="px-3 py-2">Date</th>
                            <th class="px-3 py-2">Score</th>
                            <th class="px-3 py-2">Sévérité</th>
                            <th class="px-3 py-2">Complétude</th>
                            <th class="px-3 py-2">Recommandation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $history; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="px-3 py-3 whitespace-nowrap font-mono text-xs text-ink/70 dark:text-white/70"><?php echo e($entry->created_at?->isoFormat('DD/MM/YYYY HH:mm')); ?></td>
                                <td class="px-3 py-3 font-bold text-ink dark:text-white"><?php echo e(number_format((float) $entry->score, 0)); ?><span class="text-xs font-medium text-ink/40">/100</span></td>
                                <td class="px-3 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider <?php echo e($severityBadge($entry->severity)); ?>"><?php echo e($severityLabel($entry->severity)); ?></span>
                                </td>
                                <td class="px-3 py-3 text-ink/60 dark:text-white/60"><?php echo e(number_format((float) $entry->data_completeness, 0)); ?>%</td>
                                <td class="px-3 py-3 text-ink/70 dark:text-white/70"><?php echo e($entry->recommendation ?: '—'); ?></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="5" class="px-3 py-8 text-center text-sm italic text-ink/40 dark:text-white/40">
                                    Aucune évaluation enregistrée. Utilisez « Enregistrer l’évaluation » pour créer une référence.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
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
<?php /**PATH C:\Users\mdain\nutritrace1\nutritrace\resources\views/admin/nutrimind/show.blade.php ENDPATH**/ ?>