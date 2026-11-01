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
     <?php $__env->slot('header', null, []); ?> <p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-warm">Account review</p><h1 class="mt-1 font-fraunces text-2xl font-semibold text-ink dark:text-white"><?php echo e($verificationRequest->user->name); ?></h1> <?php $__env->endSlot(); ?>
    <div class="mx-auto max-w-4xl space-y-6">
        <section class="rounded-2xl border border-ink/8 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-[#1b2923]"><div class="grid gap-5 sm:grid-cols-2"><?php $__currentLoopData = [['Applicant', $verificationRequest->user->name], ['Role', strtoupper($verificationRequest->user->getRoleNames()->first() ?: '—')], ['Organization', $verificationRequest->user->organization_name ?: '—'], ['Contact', $verificationRequest->user->email . ' · ' . ($verificationRequest->user->phone ?: 'No phone')], ['Region', collect([$verificationRequest->user->region, $verificationRequest->user->country])->filter()->join(', ') ?: '—'], ['Submitted', $verificationRequest->submitted_at?->format('d M Y, H:i')]]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$label, $value]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><div><p class="text-xs text-ink/45 dark:text-white/45"><?php echo e($label); ?></p><p class="mt-1 font-semibold dark:text-white"><?php echo e($value); ?></p></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div></section>
        <section class="rounded-2xl border border-ink/8 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-[#1b2923]"><p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-warm">Verification documents</p><div class="mt-4"><?php $__empty_1 = true; $__currentLoopData = $verificationRequest->documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><p class="border-b border-ink/8 py-3 text-sm dark:border-white/10"><span class="dark:text-white"><?php echo e($document->document_type); ?></span> · <a class="font-semibold text-forest dark:text-emerald-300" href="<?php echo e(route('admin.verification.documents.download', $document)); ?>"><?php echo e($document->original_name); ?></a></p><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><p class="text-sm text-ink/50">No documents submitted yet.</p><?php endif; ?></div></section>
        <?php if(in_array($verificationRequest->status, ['pending', 'information_required'], true)): ?><section x-data="{ action: null }" class="rounded-2xl border border-ink/8 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-[#1b2923]"><p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-warm">Admin decision</p><div class="mt-5 flex flex-wrap gap-3"><form method="POST" action="<?php echo e(route('admin.verification.approve', $verificationRequest)); ?>"><?php echo csrf_field(); ?><button class="rounded-xl bg-forest px-4 py-2.5 text-sm font-semibold text-white">Approve account</button></form><button @click="action = 'information'" class="rounded-xl border border-amber-warm/30 px-4 py-2.5 text-sm font-semibold text-amber-warm">Request information</button><button @click="action = 'reject'" class="rounded-xl border border-rose-500/30 px-4 py-2.5 text-sm font-semibold text-rose-600 dark:text-rose-300">Reject account</button></div><div x-show="action" x-cloak class="mt-5 rounded-xl bg-cream p-4 dark:bg-white/5"><form method="POST" :action="action === 'reject' ? '<?php echo e(route('admin.verification.reject', $verificationRequest)); ?>' : '<?php echo e(route('admin.verification.information', $verificationRequest)); ?>'"><?php echo csrf_field(); ?><label class="text-sm font-semibold dark:text-white">Reason <span class="text-rose-500">*</span></label><textarea name="reason" required minlength="10" maxlength="2000" rows="4" class="mt-2 block w-full rounded-xl border-ink/10 bg-white dark:border-white/10 dark:bg-white/5"><?php echo e(old('reason')); ?></textarea><?php $__errorArgs = ['reason'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="field-validation-error mt-1.5 text-sm text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><button class="mt-3 rounded-xl bg-ink px-4 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-ink">Submit decision</button></form></div></section><?php endif; ?>
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
<?php endif; ?><?php /**PATH C:\Users\mdain\nutritrace1\nutritrace\resources\views/admin/verification/show.blade.php ENDPATH**/ ?>