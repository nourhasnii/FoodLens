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
     <?php $__env->slot('header', null, []); ?> 
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-warm">Workspace</p>
            <h1 class="mt-1 font-fraunces text-3xl font-bold text-ink">Vue d'ensemble</h1>
            <p class="mt-2 text-sm text-ink/55 dark:text-white/55">Suivez l’activité, les alertes et les opérations de NutriTrace.</p>
        </div>
     <?php $__env->endSlot(); ?>

     <?php $__env->slot('headerActions', null, []); ?> 
        <div class="hidden items-center gap-3 lg:flex">
            <label for="dashboard-search" class="relative block w-64">
                <span class="sr-only">Rechercher</span>
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink/45" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                <input id="dashboard-search" type="search" class="w-full rounded-full border border-ink/10 bg-white py-2 pl-9 pr-14 text-sm text-ink placeholder:text-ink/40 focus:border-[#E3A23C] focus:outline-none focus:ring-2 focus:ring-[#E3A23C]/20 dark:border-white/10 dark:bg-[#1E3527] dark:text-white dark:placeholder:text-white/40" placeholder="Rechercher..." />
                <kbd class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 rounded-md border border-ink/10 bg-cream px-1.5 py-0.5 text-[10px] font-semibold text-ink/45">⌘K</kbd>
            </label>
            <?php if (isset($component)) { $__componentOriginal6541145ad4a57bfb6e6f221ba77eb386 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6541145ad4a57bfb6e6f221ba77eb386 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.notification-bell','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('notification-bell'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6541145ad4a57bfb6e6f221ba77eb386)): ?>
<?php $attributes = $__attributesOriginal6541145ad4a57bfb6e6f221ba77eb386; ?>
<?php unset($__attributesOriginal6541145ad4a57bfb6e6f221ba77eb386); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6541145ad4a57bfb6e6f221ba77eb386)): ?>
<?php $component = $__componentOriginal6541145ad4a57bfb6e6f221ba77eb386; ?>
<?php unset($__componentOriginal6541145ad4a57bfb6e6f221ba77eb386); ?>
<?php endif; ?>
            <div class="flex items-center gap-2">
                <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => $user,'size' => 'md','class' => 'bg-forest text-white ring-2 ring-white dark:ring-[#101815]']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($user),'size' => 'md','class' => 'bg-forest text-white ring-2 ring-white dark:ring-[#101815]']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e)): ?>
<?php $attributes = $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e; ?>
<?php unset($__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e)): ?>
<?php $component = $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e; ?>
<?php unset($__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e); ?>
<?php endif; ?>
                <span class="hidden text-sm font-semibold text-ink xl:inline"><?php echo e($user->name); ?></span>
            </div>
            <a href="<?php echo e(route('front.products.index')); ?>" target="_blank" rel="noopener" class="rounded-xl px-3 py-2 text-sm font-semibold text-ink/55 transition hover:bg-cream dark:text-white/60 dark:hover:bg-white/5" title="Catalogue public">Catalogue</a>
            <div class="rounded-full border border-ink/10 p-1 shadow-sm dark:border-white/10">
                <button type="button" role="switch" @click="theme = theme === 'light' ? 'dark' : 'light'; applyTheme()" class="relative h-8 w-16 rounded-full transition-colors" data-theme-switch :aria-checked="theme === 'dark'" :aria-label="theme === 'dark' ? 'Passer en mode clair' : 'Passer en mode sombre'" title="Changer de thème">
                    <svg x-cloak x-show="theme === 'dark'" class="absolute left-1.5 h-3 w-3 text-white/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l-1.42-1.42m11.3-11.3 1.42 1.42"/></svg>
                    <svg x-cloak x-show="theme === 'light'" class="absolute right-1.5 h-3 w-3 text-forest/45" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20.5 15.5A8.5 8.5 0 0 1 8.5 3.5 8.5 8.5 0 1 0 20.5 15.5Z"/></svg>
                    <span class="absolute inset-y-1 left-1 grid h-6 w-6 place-items-center rounded-full bg-white text-[#1F3D2E] shadow-md transition-transform duration-200" :class="theme === 'dark' ? 'translate-x-8' : 'translate-x-0'">
                        <svg x-cloak x-show="theme === 'light'" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
                        <svg x-cloak x-show="theme === 'dark'" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20.5 15.5A8.5 8.5 0 0 1 8.5 3.5 8.5 8.5 0 1 0 20.5 15.5Z"/></svg>
                    </span>
                </button>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <main class="space-y-8">
        <?php if(session('success')): ?>
            <div class="w-full rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-800">
                <?php echo e(session('success')); ?>

            </div>
        <?php endif; ?>
        <?php if(session('error')): ?>
            <div class="w-full rounded-xl border border-rose-500/20 bg-rose-500/10 px-4 py-3 text-sm font-medium text-rose-800">
                <?php echo e(session('error')); ?>

            </div>
        <?php endif; ?>

        <section class="w-full rounded-3xl bg-gradient-to-br from-[#20402E] to-[#16281E] p-8 flex justify-between items-center gap-6 shadow-lg shadow-[#16281E]/20">
            <div class="min-w-0 flex-1 space-y-3">
                <h2 class="text-2xl font-bold text-white">Bonjour <?php echo e($greetingName); ?> 👋</h2>
                <p class="text-white/80">
                    Vous avez
                    <span class="font-bold text-white">
                        <?php echo e($alertCount); ?> <?php echo e(\Illuminate\Support\Str::plural('alerte', $alertCount)); ?>

                    </span>
                    active<?php echo e($alertCount > 1 ? 's' : ''); ?> aujourd'hui.
                </p>
                <div class="pt-1">
                    <a
                        href="<?php echo e(app('router')->has('admin.batches.index') ? route('admin.batches.index') : '#'); ?>"
                        class="inline-flex items-center rounded-full bg-white px-5 py-2 font-semibold text-[#16281E] shadow-sm hover:bg-[#EEF3EC] mt-4"
                    >
                        Voir les alertes
                    </a>
                </div>
            </div>
            <div class="hidden sm:flex flex-none items-center justify-center">
                <div class="relative h-40 w-40 sm:h-44 sm:w-44 rounded-full bg-white/20 flex items-center justify-center backdrop-blur-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 160 160" class="h-28 w-28" role="img" aria-label="Scanner QR caisse légumes">
                        <g fill="none" stroke-width="0">
                            <circle cx="80" cy="80" r="68" fill="white" fill-opacity="0.15"/>
                            <rect x="42" y="38" width="76" height="58" rx="10" fill="white" fill-opacity="0.95"/>
                            <rect x="52" y="48" width="56" height="38" rx="6" fill="#EEF3EC"/>
                            <g fill="#E3A23C">
                                <rect x="58" y="54" width="10" height="10" rx="2"/>
                                <rect x="72" y="54" width="6"  height="6"  rx="1.5"/>
                                <rect x="82" y="54" width="6"  height="6"  rx="1.5"/>
                                <rect x="92" y="54" width="14" height="10" rx="2"/>
                                <rect x="58" y="68" width="6"  height="6"  rx="1.5"/>
                                <rect x="68" y="68" width="24" height="6"  rx="1.5"/>
                                <rect x="96" y="68" width="10" height="6"  rx="1.5"/>
                                <rect x="58" y="78" width="14" height="6"  rx="1.5"/>
                                <rect x="76" y="78" width="6"  height="6"  rx="1.5"/>
                                <rect x="86" y="78" width="20" height="6"  rx="1.5"/>
                            </g>
                            <rect x="36" y="46" width="6" height="36" rx="2" fill="#E3A23C" fill-opacity="0.55"/>
                            <rect x="118" y="46" width="6" height="36" rx="2" fill="#E3A23C" fill-opacity="0.55"/>
                            <circle cx="30" cy="74" r="4" fill="#E3A23C" fill-opacity="0.7"/>
                            <circle cx="130" cy="74" r="4" fill="#E3A23C" fill-opacity="0.7"/>
                            <path d="M72 100v14m-4-4h8" fill="none" stroke="white" stroke-width="3" stroke-linecap="round"/>
                            <path d="M90 106c2 5 7 8 13 8 7 0 12-3 14-8" fill="none" stroke="white" stroke-width="3" stroke-linecap="round"/>
                            <g fill="#E3A23C" fill-opacity="0.8">
                                <circle cx="54" cy="118" r="12"/>
                                <path d="M48 118c0-4 3-8 8-8 4 0 7 3 8 6-1 2-3 4-6 5-3 0-5-2-6-6s-4-7Z"/>
                            </g>
                            <circle cx="78" cy="122" r="10" fill="#E3A23C" fill-opacity="0.55"/>
                            <path d="M100 112c0-5 4-9 9-9s9 4 9 9-4 9-9 9c-3 0-6-1-8-4" fill="#E3A23C" fill-opacity="0.95"/>
                        </g>
                    </svg>
                </div>
            </div>
        </section>

        <section class="w-full rounded-2xl border border-[#DCE7DE] bg-white p-5 shadow-[0_14px_35px_-28px_rgba(31,61,46,0.7)] dark:border-white/10 dark:bg-[#1E3527] dark:shadow-[0_14px_35px_-25px_rgba(227,162,60,0.25)]">
            <div class="flex items-center justify-between gap-4">
                <h3 class="text-base font-bold text-ink dark:text-white">Quick Access</h3>
                <span class="text-xs font-medium uppercase tracking-[0.12em] text-gray-400 dark:text-white/40">Workspace</span>
            </div>
            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-7">
                <a href="<?php echo e(app('router')->has('admin.products.index') ? route('admin.products.index') : '#'); ?>" class="rounded-xl border border-[#DCE7DE] bg-[#EEF3EC] px-4 py-3 text-sm font-semibold text-ink transition hover:border-[#C7D9C9] dark:border-white/10 dark:bg-[#24452F] dark:text-white">Products</a>
                <a href="<?php echo e(app('router')->has('admin.batches.index') ? route('admin.batches.index') : '#'); ?>" class="rounded-xl border border-[#DCE7DE] bg-[#FBEFE0] px-4 py-3 text-sm font-semibold text-ink transition hover:border-[#E7D0A6] dark:border-white/10 dark:bg-[#5A421F] dark:text-white">Batches</a>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('manage_stocks')): ?>
                    <a href="<?php echo e(route('stocks.index')); ?>" class="rounded-xl border border-[#DCE7DE] bg-[#EAF3F0] px-4 py-3 text-sm font-semibold text-ink transition hover:border-[#C5D9D2] dark:border-white/10 dark:bg-[#1E4A42] dark:text-white">Stocks</a>
                    <a href="<?php echo e(route('stock-movements.index')); ?>" class="rounded-xl border border-[#DCE7DE] bg-[#F3F3E9] px-4 py-3 text-sm font-semibold text-ink transition hover:border-[#D7DAB3] dark:border-white/10 dark:bg-[#524E1F] dark:text-white">Movements</a>
                    <a href="<?php echo e(route('optimization.index')); ?>" class="rounded-xl border border-[#DCE7DE] bg-[#EEF3EC] px-4 py-3 text-sm font-semibold text-ink transition hover:border-[#C7D9C9] dark:border-white/10 dark:bg-[#24452F] dark:text-white">Optimization</a>
                    <a href="<?php echo e(route('forecast.index')); ?>" class="rounded-xl border border-[#DCE7DE] bg-[#F5E7E2] px-4 py-3 text-sm font-semibold text-ink transition hover:border-[#E5C5B7] dark:border-white/10 dark:bg-[#5A2B22] dark:text-white">Forecast</a>
                <?php endif; ?>
                <a href="<?php echo e(app('router')->has('admin.verification.index') ? route('admin.verification.index') : '#'); ?>" class="rounded-xl border border-[#DCE7DE] bg-[#F5E7E2] px-4 py-3 text-sm font-semibold text-ink transition hover:border-[#E5C5B7] dark:border-white/10 dark:bg-[#5A2B22] dark:text-white">Alerts / risks</a>
            </div>
        </section>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('manage_stocks')): ?>
        <section class="w-full rounded-2xl border border-[#DCE7DE] bg-white p-5 shadow-[0_14px_35px_-28px_rgba(31,61,46,0.7)] dark:border-white/10 dark:bg-[#1E3527] dark:shadow-[0_14px_35px_-25px_rgba(227,162,60,0.25)]">
            <div class="flex items-center justify-between gap-4">
                <h3 class="text-base font-bold text-ink dark:text-white">Stock &amp; Optimization</h3>
                <a href="<?php echo e(route('stocks.index')); ?>" class="text-sm font-semibold text-[#E3A23C] hover:text-[#C98B2E] hover:underline">Open module</a>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div class="rounded-xl border border-[#DCE7DE] bg-[#EEF3EC] p-4 dark:border-white/10 dark:bg-[#24452F]">
                    <p class="text-xs uppercase tracking-[0.12em] text-gray-500 dark:text-white/50">Sites</p>
                    <p class="mt-2 text-2xl font-bold text-ink dark:text-white"><?php echo e(number_format($stockOverview['sites'] ?? 0, 0, ',', ' ')); ?></p>
                </div>
                <div class="rounded-xl border border-[#DCE7DE] bg-[#FBEFE0] p-4 dark:border-white/10 dark:bg-[#5A421F]">
                    <p class="text-xs uppercase tracking-[0.12em] text-gray-500 dark:text-white/50">Units</p>
                    <p class="mt-2 text-2xl font-bold text-ink dark:text-white"><?php echo e(number_format((float) ($stockOverview['total_quantity'] ?? 0), 0, ',', ' ')); ?></p>
                </div>
                <div class="rounded-xl border border-[#DCE7DE] bg-[#F5E7E2] p-4 dark:border-white/10 dark:bg-[#5A2B22]">
                    <p class="text-xs uppercase tracking-[0.12em] text-gray-500 dark:text-white/50">Low stock</p>
                    <p class="mt-2 text-2xl font-bold text-ink dark:text-white"><?php echo e(number_format($stockOverview['low_stock'] ?? 0, 0, ',', ' ')); ?></p>
                </div>
                <div class="rounded-xl border border-[#DCE7DE] bg-[#EAF3F0] p-4 dark:border-white/10 dark:bg-[#1E4A42]">
                    <p class="text-xs uppercase tracking-[0.12em] text-gray-500 dark:text-white/50">Pending</p>
                    <p class="mt-2 text-2xl font-bold text-ink dark:text-white"><?php echo e(number_format($stockOverview['pending_optimizations'] ?? 0, 0, ',', ' ')); ?></p>
                </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-3">
                <a href="<?php echo e(route('stocks.index')); ?>" class="inline-flex items-center rounded-full bg-[#16281E] px-4 py-2 text-sm font-semibold text-white hover:bg-[#20402E]">Overview</a>
                <a href="<?php echo e(route('stock-movements.index')); ?>" class="inline-flex items-center rounded-full border border-[#DCE7DE] bg-white px-4 py-2 text-sm font-semibold text-ink hover:border-[#C7D9C9] dark:border-white/10 dark:bg-[#1E3527] dark:text-white">Movements</a>
                <a href="<?php echo e(route('optimization.index')); ?>" class="inline-flex items-center rounded-full border border-[#DCE7DE] bg-white px-4 py-2 text-sm font-semibold text-ink hover:border-[#C7D9C9] dark:border-white/10 dark:bg-[#1E3527] dark:text-white">Optimization</a>
                <a href="<?php echo e(route('forecast.index')); ?>" class="inline-flex items-center rounded-full border border-[#DCE7DE] bg-white px-4 py-2 text-sm font-semibold text-ink hover:border-[#C7D9C9] dark:border-white/10 dark:bg-[#1E3527] dark:text-white">Forecast</a>
            </div>
        </section>
        <?php endif; ?>

        <section class="w-full grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            <?php $__currentLoopData = $kpis; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kpi): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="w-full rounded-2xl border border-[#DCE7DE] bg-white p-5 shadow-[0_14px_35px_-28px_rgba(31,61,46,0.7)] dark:border-white/10 dark:bg-[#1E3527] dark:shadow-[0_14px_35px_-25px_rgba(227,162,60,0.25)]">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full <?php echo e($kpi['bg']); ?> <?php echo e($kpi['text']); ?> <?php echo e(match($kpi['icon']) { 'alert' => 'dark:bg-[#5A2B22] dark:text-[#F3A18A]', 'transit' => 'dark:bg-[#23414D] dark:text-[#9BC4D2]', 'batch' => 'dark:bg-[#5A421F] dark:text-[#F2BE70]', default => 'dark:bg-[#24452F] dark:text-[#A7C9A9]' }); ?>">
                        <?php if($kpi['icon'] === 'product'): ?>
                            <svg class="h-7 w-7" viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="M16 27C7 25 6 17 9 10c3 2 5 4 6 7 1-7 5-11 10-13 1 10-1 19-9 23Z" fill="currentColor"/><path d="M16 27c0-6 1-11 7-17" stroke="#EEF3EC" stroke-width="1.5" stroke-linecap="round"/></svg>
                        <?php elseif($kpi['icon'] === 'batch'): ?>
                            <svg class="h-7 w-7" viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="m5 11 11-6 11 6v14l-11 5-11-5V11Z" fill="currentColor"/><path d="m5 11 11 6 11-6M16 17v13M10 8l11 6" stroke="#FBEFE0" stroke-width="1.5" stroke-linejoin="round"/></svg>
                        <?php elseif($kpi['icon'] === 'certification'): ?>
                            <svg class="h-7 w-7" viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="M16 4 19 7l5-.5.5 5 3 3-3 3-.5 5-5-.5-3 3-3-3-5 .5-.5-5-3-3 3-3 .5-5 5 .5 3-3Z" fill="currentColor"/><path d="m11 16 3 3 7-7" stroke="#EEF3EC" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <?php elseif($kpi['icon'] === 'alert'): ?>
                            <svg class="h-7 w-7" viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="m16 4 13 23H3L16 4Z" fill="currentColor"/><path d="M16 12v7m0 4h.01" stroke="#F5E7E2" stroke-width="2" stroke-linecap="round"/></svg>
                        <?php else: ?>
                            <svg class="h-7 w-7" viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="M3 8h17v14H3zM20 13h5l4 4v5H20z" fill="currentColor"/><path d="M8 26a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm16 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" fill="currentColor"/><path d="M22 15h3l2 2h-5v-2Z" fill="#E9F1F4"/></svg>
                        <?php endif; ?>
                    </div>
                    <p class="mt-3 text-2xl font-bold text-ink dark:text-white">
                        <?php echo e(is_numeric($kpi['value']) ? number_format($kpi['value'], 0, ',', ' ') : $kpi['value']); ?>

                    </p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-white/60"><?php echo e($kpi['label']); ?></p>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </section>

        <section class="w-full grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-2 w-full space-y-4">
                <div class="w-full flex items-center justify-between gap-4">
                    <h3 class="text-lg font-bold text-ink dark:text-white">Recent Activity</h3>
                    <span class="text-xs font-medium uppercase tracking-[0.12em] text-gray-400 dark:text-white/40">Live</span>
                </div>
                <div class="w-full flex justify-between items-center gap-4">
                    <h3 class="text-lg font-bold text-ink dark:text-white">Derniers lots</h3>
                    <?php if(app('router')->has('admin.batches.index')): ?>
                        <a href="<?php echo e(route('admin.batches.index')); ?>" class="text-sm font-semibold text-[#E3A23C] hover:text-[#C98B2E] hover:underline">Voir tout</a>
                    <?php else: ?>
                        <span class="text-sm text-[#E3A23C]/60">Voir tout</span>
                    <?php endif; ?>
                </div>
                <div class="w-full overflow-hidden rounded-2xl border border-[#DCE7DE] bg-white shadow-[0_14px_35px_-28px_rgba(31,61,46,0.7)] dark:border-white/10 dark:bg-[#1E3527] dark:shadow-[0_14px_35px_-25px_rgba(227,162,60,0.25)]">
                    <div class="w-full overflow-x-auto">
                        <table class="table w-full text-sm">
                            <thead class="border-b border-gray-100 bg-gray-50 dark:border-white/10 dark:bg-[#16281E]">
                                <tr>
                                    <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-white/55">Lot</th>
                                    <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-white/55">Produit</th>
                                    <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-white/55">Statut</th>
                                    <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-white/55">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php $__currentLoopData = $recentBatches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $recentBatch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr class="hover:bg-gray-50/60 transition-colors">
                                        <td class="whitespace-nowrap px-5 py-4 font-mono text-sm font-semibold text-ink dark:text-white"><?php echo e($recentBatch['lot_number']); ?></td>
                                        <td class="px-5 py-4 text-ink/70 dark:text-white/75"><?php echo e($recentBatch['product_name']); ?></td>
                                        <td class="px-5 py-4">
                                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium <?php echo e(match($recentBatch['status_key']) { 'blue' => 'bg-blue-100 text-blue-700', 'orange' => 'bg-orange-100 text-orange-700', 'red' => 'bg-red-100 text-red-700', 'green' => 'bg-green-100 text-green-700', default => 'bg-orange-100 text-orange-700' }); ?>">
                                                <?php echo e($recentBatch['status_label']); ?>

                                            </span>
                                        </td>
                                        <td class="px-5 py-4 text-right whitespace-nowrap">
                                            <a href="<?php echo e($recentBatch['show_url']); ?>" class="inline-flex items-center rounded-full border border-[#E3A23C]/50 bg-white px-3.5 py-1.5 text-xs font-semibold text-[#16281E] hover:border-[#E3A23C] hover:bg-[#E3A23C]/10">
                                                Détails
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <?php if($recentBatches->isEmpty()): ?>
                                    <tr>
                                        <td colspan="4" class="px-5 py-12 text-center w-full">
                                            <div class="mx-auto max-w-sm">
                                                <p class="text-sm font-medium text-gray-700 dark:text-white/80">Aucun lot enregistré pour le moment.</p>
                                                <p class="mt-1 text-xs text-gray-500 dark:text-white/55">Commencez par créer votre premier lot de production.</p>
                                                <?php if(app('router')->has('admin.batches.create')): ?>
                                                    <a href="<?php echo e(route('admin.batches.create')); ?>" class="mt-4 inline-flex items-center rounded-full bg-[#E3A23C] px-5 py-2 text-sm font-semibold text-[#16281E] shadow-sm hover:bg-[#C98B2E]">
                                                        Créer un lot
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="md:col-span-1 w-full space-y-6">
                <div class="w-full rounded-2xl border border-[#DCE7DE] bg-white p-5 shadow-[0_14px_35px_-28px_rgba(31,61,46,0.7)] dark:border-white/10 dark:bg-[#1E3527] dark:shadow-[0_14px_35px_-25px_rgba(227,162,60,0.25)]">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <h3 class="text-base font-bold text-ink dark:text-white">Dernières décisions</h3>
                        <span class="text-xs font-medium text-gray-400 dark:text-white/45">4 derniers</span>
                    </div>
                    <?php if(count($lastDecisions)): ?>
                        <ul class="w-full space-y-3">
                            <?php $__currentLoopData = array_slice($lastDecisions, 0, 4); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $decision): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li class="w-full flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3 min-w-0 flex-1">
                                        <div class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-sm font-bold text-gray-500 dark:bg-white/10 dark:text-white/65">
                                            <?php echo e($decision['thumbnail']); ?>

                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-semibold text-ink dark:text-white"><?php echo e($decision['label']); ?></p>
                                            <p class="mt-0.5 text-xs text-gray-500 dark:text-white/55"><?php echo e($decision['meta']); ?></p>
                                        </div>
                                    </div>
                                    <span class="shrink-0 inline-flex items-center rounded-full px-3 py-1 text-xs font-medium <?php echo e($decision['decision_pill']); ?>">
                                        <?php echo e($decision['decision_type']); ?>

                                    </span>
                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    <?php else: ?>
                        <div class="py-8 text-center">
                            <p class="text-sm text-gray-500 dark:text-white/55">Aucune décision pour le moment.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="w-full rounded-2xl border border-[#DCE7DE] bg-white p-5 shadow-[0_14px_35px_-28px_rgba(31,61,46,0.7)] dark:border-white/10 dark:bg-[#1E3527] dark:shadow-[0_14px_35px_-25px_rgba(227,162,60,0.25)]">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <h3 class="text-base font-bold text-ink dark:text-white">À vérifier</h3>
                        <span class="inline-flex items-center rounded-full bg-orange-100 px-2.5 py-0.5 text-[11px] font-semibold text-orange-700"><?php echo e(count($toReview)); ?></span>
                    </div>
                    <?php if(count($toReview)): ?>
                        <ul class="w-full space-y-3">
                            <?php $__currentLoopData = $toReview; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $review): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li class="w-full flex items-center justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-ink dark:text-white"><?php echo e($review['title']); ?></p>
                                        <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-white/55"><?php echo e($review['meta']); ?></p>
                                    </div>
                                    <a href="<?php echo e($review['review_url']); ?>" class="shrink-0 inline-flex items-center rounded-full bg-[#E3A23C] px-4 py-1.5 text-sm font-semibold text-[#16281E] hover:bg-[#C98B2E]">
                                        Examiner
                                    </a>
                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    <?php else: ?>
                        <div class="py-8 text-center">
                            <p class="text-sm font-medium text-gray-700 dark:text-white/80">Rien à vérifier 👌</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-white/55">Toutes les certifications sont à jour.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
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
<?php /**PATH C:\Users\mdain\nutritrace1\nutritrace\resources\views/dashboard.blade.php ENDPATH**/ ?>