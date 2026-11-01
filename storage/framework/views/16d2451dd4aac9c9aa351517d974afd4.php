<?php $__env->startComponent('emails.layout', ['title' => 'Verification update']); ?>
<p>Hello <?php echo e($user->name); ?>,</p>
<p>Your NutriTrace account has not been approved.</p>
<div style="padding:16px;background:#fff1f1;border-left:4px solid #c24141;"><strong>Reason:</strong><br><?php echo e($reason); ?></div>
<p>Review the reason above and contact the NutriTrace team if you believe additional clarification is appropriate.</p>
<?php echo $__env->renderComponent(); ?><?php /**PATH C:\Users\mdain\nutritrace1\nutritrace\resources\views/emails/account-rejected.blade.php ENDPATH**/ ?>