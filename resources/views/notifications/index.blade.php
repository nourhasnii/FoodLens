<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-fraunces text-2xl font-bold text-ink">Centre de notifications</h2>
                <p class="mt-1 text-sm text-ink/55">Alertes de risque NutriTrace : certifications, OCR, rappels, qualité et couverture.</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-2 rounded-full bg-[#FBEFE0] px-4 py-2 text-sm font-semibold text-[#8a5a12]">
                    {{ $unreadCount }} non lue(s)
                </span>
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button
                        type="submit"
                        class="rounded-full bg-[#1F3D2E] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#16281E] disabled:cursor-not-allowed disabled:opacity-50"
                        @disabled($unreadCount === 0)
                    >
                        Tout marquer comme lu
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            <form method="GET" class="grid grid-cols-1 gap-3 rounded-2xl border border-ink/10 bg-white p-4 sm:grid-cols-2 lg:grid-cols-4">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-ink/50">Source</span>
                    <select name="source" onchange="this.form.submit()" class="w-full rounded-xl border-ink/15 bg-cream/50 text-sm text-ink focus:border-[#C17817] focus:ring-[#C17817]/30">
                        <option value="">Toutes les sources</option>
                        @foreach($sources as $value => $label)
                            <option value="{{ $value }}" @selected($filters['source'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-ink/50">Sévérité</span>
                    <select name="severity" onchange="this.form.submit()" class="w-full rounded-xl border-ink/15 bg-cream/50 text-sm text-ink focus:border-[#C17817] focus:ring-[#C17817]/30">
                        <option value="">Toutes les sévérités</option>
                        @foreach($severities as $value => $label)
                            <option value="{{ $value }}" @selected($filters['severity'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-ink/50">État</span>
                    <select name="state" onchange="this.form.submit()" class="w-full rounded-xl border-ink/15 bg-cream/50 text-sm text-ink focus:border-[#C17817] focus:ring-[#C17817]/30">
                        <option value="">Toutes</option>
                        <option value="unread" @selected($filters['state'] === 'unread')>Non lues</option>
                        <option value="read" @selected($filters['state'] === 'read')>Lues</option>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-ink/50">Tri</span>
                    <select name="sort" onchange="this.form.submit()" class="w-full rounded-xl border-ink/15 bg-cream/50 text-sm text-ink focus:border-[#C17817] focus:ring-[#C17817]/30">
                        <option value="priority" @selected($filters['sort'] === 'priority')>Priorité (décroissante)</option>
                        <option value="recent" @selected($filters['sort'] === 'recent')>Plus récentes</option>
                    </select>
                </label>
            </form>

            <div class="space-y-3">
                @forelse($notifications as $notification)
                    <article class="flex items-start gap-4 rounded-2xl border border-ink/10 bg-white p-4 shadow-[0_14px_35px_-30px_rgba(31,61,46,0.7)] {{ $notification['read'] ? '' : 'ring-1 ring-[#E3A23C]/40' }}">
                        <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ match($notification['severity']) {
                            'critical' => 'bg-rose-500',
                            'warning' => 'bg-amber-500',
                            default => 'bg-emerald-500',
                        } }}"></span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-3">
                                <h3 class="text-sm font-bold text-ink">{{ $notification['title'] }}</h3>
                                <span class="inline-flex items-center rounded-full bg-cream px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-ink/60">
                                    {{ $notification['source_label'] }}
                                </span>
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ match($notification['severity']) {
                                    'critical' => 'bg-rose-100 text-rose-700',
                                    'warning' => 'bg-amber-100 text-amber-700',
                                    default => 'bg-emerald-100 text-emerald-700',
                                } }}">
                                    {{ $notification['severity_label'] }}
                                </span>
                                <span class="text-[11px] font-semibold text-ink/40">Priorité {{ number_format($notification['priority'], 0, ',', ' ') }}/100</span>
                                @unless($notification['read'])
                                    <span class="inline-flex items-center rounded-full bg-[#E3A23C] px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-[#16281E]">Nouveau</span>
                                @endunless
                            </div>

                            <p class="mt-2 text-sm text-ink/70">{{ $notification['message'] }}</p>

                            <div class="mt-3 flex flex-wrap items-center gap-4">
                                <span class="text-xs text-ink/40">{{ $notification['created_human'] }}</span>

                                @if($notification['action_url'])
                                    <a href="{{ $notification['action_url'] }}" class="text-xs font-semibold text-[#C17817] hover:underline">
                                        {{ $notification['action_label'] ?? 'Ouvrir' }}
                                    </a>
                                @endif

                                @unless($notification['read'])
                                    <form method="POST" action="{{ route('notifications.read', $notification['id']) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-xs font-semibold text-ink/50 transition hover:text-ink">Marquer comme lu</button>
                                    </form>
                                @endunless
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-ink/15 bg-white px-6 py-16 text-center">
                        <p class="text-sm font-semibold text-ink/70">Aucune notification pour ces critères.</p>
                        <p class="mt-1 text-xs text-ink/45">Les alertes apparaissent dès qu'un risque est détecté par le NutriAlert Hub.</p>
                    </div>
                @endforelse
            </div>

            @if($notifications->hasPages())
                <div class="pt-2">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
