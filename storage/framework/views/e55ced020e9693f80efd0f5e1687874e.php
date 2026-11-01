<?php $__env->startComponent('emails.layout', ['title' => 'Additional information required']); ?>
<p>Hello <?php echo e($user->name); ?>,</p>
<p>Additional information is required for your NutriTrace account.</p>
<div style="padding:16px;background:#fff8ed;border-left:4px solid #c17817;"><strong>Information requested:</strong><br><?php echo e($reason); ?></div>
<p>Please sign in to NutriTrace and provide the requested information so our team can continue the review.</p>
<p><a href="<?php echo e(route('login')); ?>" style="color:#1f3d2e;font-weight:bold;">Return to NutriTrace</a></p>
<?php echo $__env->renderComponent(); ?><?php /**PATH C:\Users\mdain\nutritrace1\nutritrace\resources\views/emails/information-required.blade.php ENDPATH**/ ?>