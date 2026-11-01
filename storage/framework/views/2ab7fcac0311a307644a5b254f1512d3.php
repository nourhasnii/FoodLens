<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['align' => 'right']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['align' => 'right']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $bellUser = auth()->user();
    $bellUnread = $bellUser ? $bellUser->unreadNotifications()->count() : 0;
    $bellRecent = $bellUser
        ? $bellUser->notifications()
            ->orderByDesc('data->priority')
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn ($notification) => \App\Support\NotificationPresenter::make($notification))
            ->values()
            ->all()
        : [];

    $bellConfig = [
        'unread' => $bellUnread,
        'recent' => $bellRecent,
        'endpoints' => [
            'recent' => route('notifications.recent'),
            'read' => route('notifications.read', ['notification' => '__ID__']),
            'readAll' => route('notifications.read-all'),
        ],
    ];
?>

<div class="relative" x-data="notificationBell(<?php echo \Illuminate\Support\Js::from($bellConfig)->toHtml() ?>)" @keydown.escape.window="open = false">
    <button
        type="button"
        @click="toggle()"
        class="relative inline-flex h-10 w-10 items-center justify-center rounded-full border border-ink/10 bg-white text-ink/55 transition hover:bg-cream dark:border-white/10 dark:bg-[#1E3527] dark:text-white/70 dark:hover:bg-white/10"
        :aria-expanded="open"
        aria-haspopup="true"
        aria-label="Notifications"
    >
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
        <span
            x-show="unread > 0"
            x-cloak
            x-text="badge()"
            class="absolute -right-1 -top-1 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-[#E3A23C] px-1 text-[10px] font-bold leading-none text-[#16281E] ring-2 ring-white dark:ring-[#1E3527]"
        ></span>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition
        @click.outside="open = false"
        class="absolute <?php echo e($align === 'right' ? 'right-0' : 'left-0'); ?> z-50 mt-3 w-[22rem] max-w-[calc(100vw-2rem)] overflow-hidden rounded-2xl border border-ink/10 bg-white shadow-[0_24px_60px_-30px_rgba(31,61,46,0.55)] dark:border-white/10 dark:bg-[#1E3527]"
        role="menu"
        aria-label="Notifications récentes"
    >
        <div class="flex items-center justify-between gap-3 border-b border-ink/5 px-4 py-3 dark:border-white/10">
            <div>
                <p class="text-sm font-bold text-ink dark:text-white">Notifications</p>
                <p class="text-[11px] text-ink/50 dark:text-white/50"><span x-text="unread"></span> non lue(s)</p>
            </div>
            <button
                type="button"
                x-show="unread > 0"
                @click="markAll()"
                class="rounded-full border border-[#E3A23C]/40 px-3 py-1 text-[11px] font-semibold text-[#8a5a12] transition hover:bg-[#E3A23C]/10 dark:border-[#E3A23C]/40 dark:text-[#E3A23C]"
            >
                Tout marquer comme lu
            </button>
        </div>

        <div class="max-h-96 overflow-y-auto">
            <template x-if="items.length === 0">
                <p class="px-4 py-8 text-center text-sm text-ink/50 dark:text-white/50">Aucune notification pour le moment.</p>
            </template>

            <template x-for="item in items" :key="item.id">
                <div
                    class="flex items-start gap-3 border-b border-ink/5 px-4 py-3 transition hover:bg-cream/60 dark:border-white/5 dark:hover:bg-white/5"
                    :class="!item.read ? 'bg-[#E3A23C]/5' : ''"
                >
                    <span
                        class="mt-1.5 h-2 w-2 shrink-0 rounded-full"
                        :class="{
                            'bg-rose-500': item.severity === 'critical',
                            'bg-amber-500': item.severity === 'warning',
                            'bg-emerald-500': item.severity === 'info',
                        }"
                    ></span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <p class="truncate text-sm font-semibold text-ink dark:text-white" x-text="item.title"></p>
                            <span class="shrink-0 text-[10px] text-ink/40 dark:text-white/40" x-text="item.created_human"></span>
                        </div>
                        <p class="mt-0.5 truncate text-xs text-ink/60 dark:text-white/60" x-text="item.message"></p>
                        <div class="mt-2 flex items-center gap-3">
                            <template x-if="item.action_url">
                                <a :href="item.action_url" class="text-[11px] font-semibold text-[#C17817] hover:underline" x-text="item.action_label || 'Ouvrir'"></a>
                            </template>
                            <button
                                type="button"
                                x-show="!item.read"
                                @click="markRead(item)"
                                class="text-[11px] font-semibold text-ink/50 hover:text-ink dark:text-white/50 dark:hover:text-white"
                            >
                                Marquer comme lu
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <a
            href="<?php echo e(route('notifications.index')); ?>"
            class="block bg-cream/70 px-4 py-3 text-center text-sm font-semibold text-[#1F3D2E] transition hover:bg-cream dark:bg-[#16281E] dark:text-white dark:hover:bg-[#16281E]/70"
        >
            Voir toutes les notifications
        </a>
    </div>
</div>
<?php /**PATH C:\Users\mdain\nutritrace1\nutritrace\resources\views/components/notification-bell.blade.php ENDPATH**/ ?>