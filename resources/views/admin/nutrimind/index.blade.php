<x-dashboard-layout>
    @php
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
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-warm">Décision explicable</p>
                <h1 class="mt-1 font-fraunces text-3xl font-bold leading-tight text-ink dark:text-white">NutriMind</h1>
                <p class="mt-2 text-sm text-ink/55 dark:text-white/55">
                    Risque justifié, impact, simulation « what-if » et recommandations correctives — aucune donnée inventée.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.certifications.intelligence') }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-ink/10 bg-white px-4 py-2.5 text-sm font-semibold text-ink transition-colors hover:bg-cream dark:border-white/10 dark:bg-[#1b2923] dark:text-white">
                    Certification Intelligence
                </a>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-[1440px] space-y-6">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-800 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        {{-- KPI --}}
        <section aria-label="Synthèse du risque" class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
            <x-dashboard.kpi-card label="Produits évalués" :value="$summary['evaluated']" detail="Entités analysées en temps réel" tone="forest" icon="product" />
            <x-dashboard.kpi-card label="Risques critiques" :value="$summary['critical']" detail="Score ≥ 70 ou incident confirmé" tone="rose" icon="alert" />
            <x-dashboard.kpi-card label="À surveiller" :value="$summary['warning']" detail="Score entre 40 et 69" tone="amber" icon="certification" />
            <x-dashboard.kpi-card label="Score moyen" :value="round($summary['average'])" detail="Moyenne des scores de risque" tone="blue" icon="batch" />
        </section>

        {{-- Distribution + top risques --}}
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
                            @forelse($topRisks as $risk)
                                <tr>
                                    <td class="px-3 py-3 font-semibold text-ink dark:text-white">{{ $risk['label'] }}</td>
                                    <td class="px-3 py-3 font-fraunces text-lg font-bold text-ink dark:text-white">{{ number_format($risk['score'], 0) }}<span class="text-xs font-medium text-ink/40">/100</span></td>
                                    <td class="px-3 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider {{ $severityBadge($risk['severity']) }}">
                                            {{ $severityLabel($risk['severity']) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-ink/70 dark:text-white/70">{{ $risk['top']['label'] ?? '—' }}</td>
                                    <td class="px-3 py-3 text-ink/60 dark:text-white/60">{{ number_format($risk['completeness'], 0) }}%</td>
                                    <td class="px-3 py-3 text-right">
                                        <a href="{{ route('admin.nutrimind.show', ['type' => $risk['type'], 'id' => $risk['id']]) }}"
                                           class="inline-flex items-center gap-1.5 rounded-lg bg-forest/10 px-3 py-1.5 text-xs font-semibold text-forest transition-colors hover:bg-forest/20 dark:text-emerald-300">
                                            Analyser
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-8 text-center text-sm italic text-ink/40 dark:text-white/40">Aucun produit à évaluer.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl border border-ink/5 bg-white p-5 shadow-lg shadow-ink/[0.03] dark:border-white/10 dark:bg-[#1b2923]">
                <h3 class="mb-4 font-fraunces text-lg font-bold text-ink dark:text-white">Répartition du risque</h3>
                @php
                    $total = max(1, $summary['evaluated']);
                    $bars = [
                        ['label' => 'Critique', 'count' => $summary['critical'], 'class' => 'bg-red-500'],
                        ['label' => 'Avertissement', 'count' => $summary['warning'], 'class' => 'bg-amber-warm'],
                        ['label' => 'Info', 'count' => $summary['info'], 'class' => 'bg-emerald-500'],
                    ];
                @endphp
                <div class="space-y-4">
                    @foreach($bars as $bar)
                        <div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-ink/70 dark:text-white/70">{{ $bar['label'] }}</span>
                                <span class="font-bold text-ink dark:text-white">{{ $bar['count'] }}</span>
                            </div>
                            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-ink/5 dark:bg-white/10">
                                <div class="h-full {{ $bar['class'] }} transition-all" style="width: {{ round($bar['count'] / $total * 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="mt-5 text-[11px] leading-relaxed text-ink/45 dark:text-white/45">
                    Score = somme des contributions confirmées, plafonnée à 100. Un incident de sécurité (rappel) force la sévérité critique.
                </p>
            </div>
        </section>

        {{-- Historique des évaluations --}}
        <section class="rounded-2xl border border-ink/5 bg-white p-5 shadow-lg shadow-ink/[0.03] dark:border-white/10 dark:bg-[#1b2923]">
            <div class="mb-4">
                <h3 class="font-fraunces text-lg font-bold text-ink dark:text-white">Évaluations enregistrées</h3>
                <p class="text-xs text-ink/50 dark:text-white/50">Historique des décisions tracées — {{ $recentAssessments->count() }} dernière(s).</p>
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
                        @forelse($recentAssessments as $assessment)
                            <tr>
                                <td class="px-3 py-3 font-semibold text-ink dark:text-white">{{ $assessment->entity_label ?: '—' }}</td>
                                <td class="px-3 py-3 font-bold text-ink dark:text-white">{{ number_format((float) $assessment->score, 0) }}<span class="text-xs font-medium text-ink/40">/100</span></td>
                                <td class="px-3 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider {{ $severityBadge($assessment->severity) }}">
                                        {{ $severityLabel($assessment->severity) }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-ink/70 dark:text-white/70">{{ $assessment->recommendation ?: '—' }}</td>
                                <td class="px-3 py-3 text-ink/60 dark:text-white/60">{{ $assessment->creator?->name ?? '—' }}</td>
                                <td class="px-3 py-3 whitespace-nowrap font-mono text-xs text-ink/60 dark:text-white/60">{{ $assessment->created_at?->isoFormat('DD/MM/YYYY HH:mm') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-8 text-center text-sm italic text-ink/40 dark:text-white/40">
                                    Aucune évaluation enregistrée. Ouvrez une entité puis « Enregistrer l’évaluation ».
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <p class="py-2 text-center text-[11px] text-ink/35 dark:text-white/35">
            Source de vérité : données réelles des tables products, batches, certifications, ocr_checks, quality_checks, transport_conditions.
        </p>
    </div>
</x-dashboard-layout>
