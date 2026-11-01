<section
    x-data="{
        preview: null,
        pick() { this.$refs.photoInput.click(); },
        onChange(event) {
            const file = event.target.files[0];
            if (! file) return;
            this.preview = URL.createObjectURL(file);
            this.$refs.photoForm.submit();
        }
    }"
>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            <?php echo e(__('Photo de profil')); ?>

        </h2>

        <p class="mt-1 text-sm text-gray-600">
            <?php echo e(__('Formats acceptés : JPG, PNG, WEBP (max 2 Mo)')); ?>

        </p>
    </header>

    <div class="mt-6 flex flex-col items-start gap-6 sm:flex-row sm:items-center">
        
        <div class="relative shrink-0">
            <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => $user,'size' => '2xl','class' => 'bg-forest/10 text-forest/60 ring-2 ring-amber-warm/40 ring-offset-2 ring-offset-white']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($user),'size' => '2xl','class' => 'bg-forest/10 text-forest/60 ring-2 ring-amber-warm/40 ring-offset-2 ring-offset-white']); ?>
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

            <template x-if="preview">
                <img
                    :src="preview"
                    alt=""
                    class="absolute inset-0 h-full w-full rounded-full object-cover ring-2 ring-amber-warm/40 ring-offset-2 ring-offset-white"
                >
            </template>
        </div>

        <div class="space-y-3">
            
            <form x-ref="photoForm" method="post" action="<?php echo e(route('profile.photo.update')); ?>" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <input x-ref="photoInput" type="file" name="photo" accept="image/*" class="hidden" @change="onChange">
                <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => ['type' => 'button','@click' => 'pick()']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','@click' => 'pick()']); ?>
                    <?php echo e(__('Changer la photo')); ?>

                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $attributes = $__attributesOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__attributesOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $component = $__componentOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__componentOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
            </form>

            
            <?php if($user->profile_photo_url): ?>
                <form method="post" action="<?php echo e(route('profile.photo.destroy')); ?>"
                      onsubmit="return confirm('<?php echo e(__('Supprimer votre photo de profil ?')); ?>')">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('delete'); ?>
                    <button type="submit" class="text-sm font-medium text-ink/50 transition-colors hover:text-red-600">
                        <?php echo e(__('Supprimer la photo')); ?>

                    </button>
                </form>
            <?php endif; ?>

            <?php $__errorArgs = ['photo', 'updatePhoto'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="field-validation-error text-sm text-red-600"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

            <?php if(session('status') === 'photo-updated'): ?>
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)"
                   class="text-sm text-forest"><?php echo e(__('Votre photo de profil a été mise à jour.')); ?></p>
            <?php elseif(session('status') === 'photo-removed'): ?>
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)"
                   class="text-sm text-forest"><?php echo e(__('Votre photo de profil a été supprimée.')); ?></p>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php /**PATH C:\Users\mdain\nutritrace1\nutritrace\resources\views/profile/partials/update-profile-photo-form.blade.php ENDPATH**/ ?>