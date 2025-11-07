<?php $__env->startSection('title', 'Auto Reply - '.$mailbox->name); ?>

<?php $__env->startSection('content'); ?>
<div class="container">
    <div class="row">
        <div class="col-md-3">
            <?php echo $__env->make('mailboxes._partials.settings_nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Auto Reply Settings</h4>
                </div>
                <div class="card-body">
                    <?php if(session('success')): ?>
                        <div class="alert alert-success">
                            <?php echo e(session('success')); ?>

                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo e(route('mailboxes.auto_reply.save', $mailbox)); ?>">
                        <?php echo csrf_field(); ?>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="auto_reply_enabled" 
                                   name="auto_reply_enabled" value="1"
                                   <?php if(old('auto_reply_enabled', $mailbox->auto_reply_enabled)): echo 'checked'; endif; ?>>
                            <label class="form-check-label" for="auto_reply_enabled">
                                Enable Auto Reply
                            </label>
                            <div class="form-text">When enabled, automatically reply to incoming emails</div>
                        </div>

                        <div class="mb-3">
                            <label for="auto_reply_subject" class="form-label">Subject</label>
                            <input type="text" class="form-control <?php $__errorArgs = ['auto_reply_subject'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                   id="auto_reply_subject" name="auto_reply_subject"
                                   value="<?php echo e(old('auto_reply_subject', $mailbox->auto_reply_subject ?? 'Re: {%subject%}')); ?>">
                            <div class="form-text">Available variables: {%subject%}, {%mailbox_name%}</div>
                            <?php $__errorArgs = ['auto_reply_subject'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="mb-3">
                            <label for="auto_reply_message" class="form-label">Message</label>
                            <textarea class="form-control <?php $__errorArgs = ['auto_reply_message'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                      id="auto_reply_message" name="auto_reply_message" rows="8"><?php echo e(old('auto_reply_message', $mailbox->auto_reply_message)); ?></textarea>
                            <div class="form-text">Available variables: {%customer_name%}, {%mailbox_name%}</div>
                            <?php $__errorArgs = ['auto_reply_message'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="mb-3">
                            <label for="auto_bcc" class="form-label">BCC</label>
                            <input type="email" class="form-control <?php $__errorArgs = ['auto_bcc'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                   id="auto_bcc" name="auto_bcc"
                                   value="<?php echo e(old('auto_bcc', $mailbox->auto_bcc)); ?>"
                                   placeholder="bcc@example.com">
                            <div class="form-text">Optional: Send a copy of auto-replies to this address</div>
                            <?php $__errorArgs = ['auto_bcc'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <button type="submit" class="btn btn-primary">Save Settings</button>
                        <a href="<?php echo e(route('mailboxes.settings', $mailbox)); ?>" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/runner/work/freescout/freescout/resources/views/mailboxes/auto_reply.blade.php ENDPATH**/ ?>