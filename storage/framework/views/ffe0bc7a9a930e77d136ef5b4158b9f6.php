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
     <?php $__env->slot('header', null, []); ?> <p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-warm">Administration</p><h1 class="mt-1 font-fraunces text-2xl font-semibold text-ink dark:text-white">Users</h1> <?php $__env->endSlot(); ?>
    <div class="mx-auto max-w-[1380px] space-y-7"><div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"><?php if (isset($component)) { $__componentOriginal73e8909751d40929eb36b5991fae6a61 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal73e8909751d40929eb36b5991fae6a61 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard.kpi-card','data' => ['label' => 'Total users','value' => $totalUsers,'detail' => 'Registered accounts','tone' => 'forest','icon' => 'product']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard.kpi-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Total users','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totalUsers),'detail' => 'Registered accounts','tone' => 'forest','icon' => 'product']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal73e8909751d40929eb36b5991fae6a61)): ?>
<?php $attributes = $__attributesOriginal73e8909751d40929eb36b5991fae6a61; ?>
<?php unset($__attributesOriginal73e8909751d40929eb36b5991fae6a61); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal73e8909751d40929eb36b5991fae6a61)): ?>
<?php $component = $__componentOriginal73e8909751d40929eb36b5991fae6a61; ?>
<?php unset($__componentOriginal73e8909751d40929eb36b5991fae6a61); ?>
<?php endif; ?><?php if (isset($component)) { $__componentOriginal73e8909751d40929eb36b5991fae6a61 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal73e8909751d40929eb36b5991fae6a61 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard.kpi-card','data' => ['label' => 'Pending verification','value' => $pendingUsers,'detail' => 'Awaiting review','tone' => 'amber','icon' => 'alert']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard.kpi-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Pending verification','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($pendingUsers),'detail' => 'Awaiting review','tone' => 'amber','icon' => 'alert']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal73e8909751d40929eb36b5991fae6a61)): ?>
<?php $attributes = $__attributesOriginal73e8909751d40929eb36b5991fae6a61; ?>
<?php unset($__attributesOriginal73e8909751d40929eb36b5991fae6a61); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal73e8909751d40929eb36b5991fae6a61)): ?>
<?php $component = $__componentOriginal73e8909751d40929eb36b5991fae6a61; ?>
<?php unset($__componentOriginal73e8909751d40929eb36b5991fae6a61); ?>
<?php endif; ?><?php if (isset($component)) { $__componentOriginal73e8909751d40929eb36b5991fae6a61 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal73e8909751d40929eb36b5991fae6a61 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard.kpi-card','data' => ['label' => 'Active users','value' => $activeUsers,'detail' => 'Approved accounts','tone' => 'blue','icon' => 'batch']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard.kpi-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Active users','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($activeUsers),'detail' => 'Approved accounts','tone' => 'blue','icon' => 'batch']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal73e8909751d40929eb36b5991fae6a61)): ?>
<?php $attributes = $__attributesOriginal73e8909751d40929eb36b5991fae6a61; ?>
<?php unset($__attributesOriginal73e8909751d40929eb36b5991fae6a61); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal73e8909751d40929eb36b5991fae6a61)): ?>
<?php $component = $__componentOriginal73e8909751d40929eb36b5991fae6a61; ?>
<?php unset($__componentOriginal73e8909751d40929eb36b5991fae6a61); ?>
<?php endif; ?><?php if (isset($component)) { $__componentOriginal73e8909751d40929eb36b5991fae6a61 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal73e8909751d40929eb36b5991fae6a61 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard.kpi-card','data' => ['label' => 'Rejected / suspended','value' => $inactiveUsers,'detail' => 'Needs attention','tone' => 'rose','icon' => 'alert']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard.kpi-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Rejected / suspended','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($inactiveUsers),'detail' => 'Needs attention','tone' => 'rose','icon' => 'alert']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal73e8909751d40929eb36b5991fae6a61)): ?>
<?php $attributes = $__attributesOriginal73e8909751d40929eb36b5991fae6a61; ?>
<?php unset($__attributesOriginal73e8909751d40929eb36b5991fae6a61); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal73e8909751d40929eb36b5991fae6a61)): ?>
<?php $component = $__componentOriginal73e8909751d40929eb36b5991fae6a61; ?>
<?php unset($__componentOriginal73e8909751d40929eb36b5991fae6a61); ?>
<?php endif; ?></div>
        <section class="rounded-2xl border border-ink/8 bg-white shadow-sm dark:border-white/10 dark:bg-[#1b2923]"><form class="grid gap-3 border-b border-ink/8 p-5 md:grid-cols-[1fr_180px_180px_auto] dark:border-white/10"><input name="search" value="<?php echo e(request('search')); ?>" placeholder="Search name, email or organization" class="rounded-xl border-ink/10 bg-cream text-sm dark:border-white/10 dark:bg-white/5"><select name="role" class="rounded-xl border-ink/10 bg-cream text-sm dark:border-white/10 dark:bg-white/5"><option value="">All roles</option><?php $__currentLoopData = ['admin','producteur','distributeur','consommateur']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($role); ?>" <?php if(request('role') === $role): echo 'selected'; endif; ?>><?php echo e(ucfirst($role)); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select><select name="account_status" class="rounded-xl border-ink/10 bg-cream text-sm dark:border-white/10 dark:bg-white/5"><option value="">All statuses</option><?php $__currentLoopData = ['pending','information_required','active','rejected','suspended']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($status); ?>" <?php if(request('account_status') === $status): echo 'selected'; endif; ?>><?php echo e(str_replace('_', ' ', ucfirst($status))); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select><button class="rounded-xl bg-forest px-4 py-2 text-sm font-semibold text-white">Filter</button></form><div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="border-b border-ink/8 text-xs uppercase tracking-wider text-ink/45 dark:border-white/10 dark:text-white/45"><tr><th class="px-5 py-4">User</th><th class="px-5 py-4">Organization</th><th class="px-5 py-4">Role</th><th class="px-5 py-4">Status</th><th class="px-5 py-4">Registered</th><th class="px-5 py-4"></th></tr></thead><tbody class="divide-y divide-ink/8 dark:divide-white/10"><?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr class="hover:bg-cream/60 dark:hover:bg-white/5"><td class="px-5 py-4"><p class="font-semibold text-ink dark:text-white"><?php echo e($user->name); ?></p><p class="text-xs text-ink/50 dark:text-white/50"><?php echo e($user->email); ?></p></td><td class="px-5 py-4 text-ink/65 dark:text-white/65"><?php echo e($user->organization_name ?: 'Personal account'); ?></td><td class="px-5 py-4"><span class="rounded-full bg-forest/10 px-2.5 py-1 text-xs font-semibold text-forest dark:bg-emerald-300/10 dark:text-emerald-300"><?php echo e(ucfirst($user->getRoleNames()->first() ?: '—')); ?></span></td><td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold <?php echo e($user->account_status === 'active' ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300' : ($user->account_status === 'rejected' ? 'bg-rose-500/10 text-rose-700 dark:text-rose-300' : 'bg-amber-warm/10 text-amber-warm')); ?>"><?php echo e(str_replace('_', ' ', ucfirst($user->account_status))); ?></span></td><td class="whitespace-nowrap px-5 py-4 text-xs text-ink/50 dark:text-white/50"><?php echo e($user->created_at?->format('d M Y')); ?></td><td class="px-5 py-4 text-right"><a href="<?php echo e(route('admin.users.show', $user)); ?>" class="font-semibold text-forest dark:text-emerald-300">View</a></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="6" class="px-5 py-12 text-center text-sm text-ink/50 dark:text-white/50">No users match these filters.</td></tr><?php endif; ?></tbody></table></div><div class="p-5"><?php echo e($users->links()); ?></div></section>
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
<?php endif; ?><?php /**PATH C:\Users\mdain\nutritrace1\nutritrace\resources\views/admin/users/index.blade.php ENDPATH**/ ?>