<?php $__env->startComponent('emails.layout', ['title' => 'Your account is now active']); ?>
<p>Hello <?php echo e($user->name); ?>,</p>
<p>Your NutriTrace account is now active.</p>
<p>You can sign in and access your workspace.</p>
<p><a href="<?php echo e(route('login')); ?>" style="display:inline-block;background:#1f3d2e;color:#fff;padding:12px 18px;border-radius:8px;text-decoration:none;font-weight:bold;">Sign in to NutriTrace</a></p>
<?php echo $__env->renderComponent(); ?><?php /**PATH C:\Users\mdain\nutritrace1\nutritrace\resources\views/emails/account-activated.blade.php ENDPATH**/ ?>