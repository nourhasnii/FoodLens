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
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-sm font-semibold uppercase tracking-widest text-amber-warm">Cold-chain workspace</p><h2 class="mt-1 font-fraunces text-3xl font-bold text-ink">Transport Conditions</h2><p class="mt-2 text-sm text-ink/55">Monitor temperature and humidity throughout transport.</p></div>
            <a href="<?php echo e(route('admin.transport-conditions.create')); ?>" class="inline-flex items-center justify-center rounded-xl bg-forest px-4 py-3 text-sm font-semibold text-white hover:bg-forest-dark">Add Condition</a>
        </div>
     <?php $__env->endSlot(); ?>
    <div class="py-10 sm:py-12"><div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <?php if(session('success')): ?><div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800"><?php echo e(session('success')); ?></div><?php endif; ?>
        <div class="rounded-2xl border border-ink/8 bg-white p-4 shadow-sm"><form method="GET" action="<?php echo e(route('admin.transport-conditions.index')); ?>" class="grid grid-cols-1 gap-3 md:grid-cols-[1fr_1fr_auto_auto]"><select name="batch_id" class="rounded-xl border-ink/10 bg-cream px-4 py-3 text-sm"><option value="">All batches</option><?php $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $batch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($batch->id); ?>" <?php if((string) request('batch_id') === (string) $batch->id): echo 'selected'; endif; ?>><?php echo e($batch->lot_number); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select><input type="date" name="date" value="<?php echo e(request('date')); ?>" class="rounded-xl border-ink/10 bg-cream px-4 py-3 text-sm"><label class="flex items-center gap-2 rounded-xl border border-ink/10 px-4 py-3 text-sm"><input type="checkbox" name="out_of_range" value="1" <?php if(request()->boolean('out_of_range')): echo 'checked'; endif; ?>> Out of range</label><button class="rounded-xl bg-ink px-5 py-3 text-sm font-semibold text-white hover:bg-forest">Filter</button></form></div>
        <div class="overflow-hidden rounded-2xl border border-ink/8 bg-white shadow-sm"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-ink/8"><thead class="bg-cream/70"><tr><th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-ink/45">Recorded</th><th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-ink/45">Batch</th><th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-ink/45">Temperature</th><th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-ink/45">Humidity</th><th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-ink/45">Location</th><th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-ink/45">Status</th><th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider text-ink/45">Actions</th></tr></thead><tbody class="divide-y divide-ink/8"><?php $__empty_1 = true; $__currentLoopData = $conditions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $condition): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php ($outOfRange = (float) $condition->temperature < \App\Models\TransportCondition::TEMPERATURE_MIN || (float) $condition->temperature > \App\Models\TransportCondition::TEMPERATURE_MAX); ?><tr class="hover:bg-cream/45"><td class="whitespace-nowrap px-6 py-5 text-sm text-ink/65"><?php echo e($condition->recorded_at?->format('d/m/Y H:i')); ?></td><td class="px-6 py-5"><a href="<?php echo e(route('admin.transport-conditions.show', $condition)); ?>" class="font-semibold text-forest hover:text-amber-warm"><?php echo e($condition->batch?->lot_number ?? '—'); ?></a></td><td class="px-6 py-5 text-sm font-semibold <?php echo e($outOfRange ? 'text-red-600' : 'text-ink'); ?>"><?php echo e($condition->temperature); ?> °C</td><td class="px-6 py-5 text-sm text-ink/65"><?php echo e($condition->humidity !== null ? $condition->humidity . ' %' : '—'); ?></td><td class="px-6 py-5 text-sm text-ink/65"><?php echo e($condition->location ?: '—'); ?></td><td class="px-6 py-5"><?php if($outOfRange): ?><?php if (isset($component)) { $__componentOriginal41cda017b93539193a6609497ccd9474 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal41cda017b93539193a6609497ccd9474 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard.status-badge','data' => ['status' => 'rejected']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard.status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => 'rejected']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal41cda017b93539193a6609497ccd9474)): ?>
<?php $attributes = $__attributesOriginal41cda017b93539193a6609497ccd9474; ?>
<?php unset($__attributesOriginal41cda017b93539193a6609497ccd9474); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal41cda017b93539193a6609497ccd9474)): ?>
<?php $component = $__componentOriginal41cda017b93539193a6609497ccd9474; ?>
<?php unset($__componentOriginal41cda017b93539193a6609497ccd9474); ?>
<?php endif; ?><?php else: ?><?php if (isset($component)) { $__componentOriginal41cda017b93539193a6609497ccd9474 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal41cda017b93539193a6609497ccd9474 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard.status-badge','data' => ['status' => 'valid']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard.status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => 'valid']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal41cda017b93539193a6609497ccd9474)): ?>
<?php $attributes = $__attributesOriginal41cda017b93539193a6609497ccd9474; ?>
<?php unset($__attributesOriginal41cda017b93539193a6609497ccd9474); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal41cda017b93539193a6609497ccd9474)): ?>
<?php $component = $__componentOriginal41cda017b93539193a6609497ccd9474; ?>
<?php unset($__componentOriginal41cda017b93539193a6609497ccd9474); ?>
<?php endif; ?><?php endif; ?></td><td class="whitespace-nowrap px-6 py-5 text-right"><a href="<?php echo e(route('admin.transport-conditions.show', $condition)); ?>" class="mr-3 text-sm font-semibold text-forest">View</a><a href="<?php echo e(route('admin.transport-conditions.edit', $condition)); ?>" class="mr-3 text-sm font-semibold text-ink/55">Edit</a><form method="POST" action="<?php echo e(route('admin.transport-conditions.destroy', $condition)); ?>" class="inline" onsubmit="return confirm('Delete this transport condition?')"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button class="text-sm font-semibold text-red-600">Delete</button></form></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="7" class="px-6 py-16 text-center"><h3 class="font-fraunces text-lg font-bold text-ink">No transport conditions found</h3><p class="mt-1 text-sm text-ink/55">Start recording cold-chain conditions.</p><a href="<?php echo e(route('admin.transport-conditions.create')); ?>" class="mt-5 inline-flex rounded-xl bg-forest px-4 py-2.5 text-sm font-semibold text-white">Add Condition</a></td></tr><?php endif; ?></tbody></table></div><?php if($conditions->hasPages()): ?><div class="border-t border-ink/8 px-6 py-4"><?php echo e($conditions->links()); ?></div><?php endif; ?></div>
    </div></div>
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
<?php /**PATH C:\Users\mdain\nutritrace1\nutritrace\resources\views/admin/transport-conditions/index.blade.php ENDPATH**/ ?>