<?php $__env->startComponent('emails.layout', ['title' => 'Verification information resubmitted']); ?>
<p>An applicant has provided additional verification information.</p>
<p><strong>User:</strong> <?php echo e($user->name); ?><br><strong>Organization:</strong> <?php echo e($user->organization_name ?: '—'); ?><br><strong>Status:</strong> Pending verification</p>
<p><a href="<?php echo e(route('admin.verification.show', $verificationRequest)); ?>" style="display:inline-block;background:#1f3d2e;color:#fff;padding:12px 18px;border-radius:8px;text-decoration:none;font-weight:bold;">Review the resubmitted information</a></p>
<?php echo $__env->renderComponent(); ?><?php /**PATH C:\Users\mdain\nutritrace1\nutritrace\resources\views/emails/admin-information-submitted.blade.php ENDPATH**/ ?>