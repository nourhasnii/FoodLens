<?php $__env->startComponent('emails.layout', ['title' => 'Your account is ready']); ?>
<p>Hello <?php echo e($user->name); ?>,</p>
<p>Welcome to NutriTrace! Your consumer account is ready to use.</p>
<p>You can now browse verified products and explore the traceability information for every item in our catalog.</p>
<p><a href="<?php echo e(route('login')); ?>" style="display:inline-block;background:#1f3d2e;color:#fff;padding:12px 18px;border-radius:8px;text-decoration:none;font-weight:bold;">Sign in to NutriTrace</a></p>
<?php echo $__env->renderComponent(); ?>
<?php /**PATH C:\Users\mdain\nutritrace1\nutritrace\resources\views/emails/welcome-consumer.blade.php ENDPATH**/ ?>