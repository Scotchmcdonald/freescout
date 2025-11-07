<?php $__env->startSection('title', 'Mailbox Permissions - '.$mailbox->name); ?>

<?php $__env->startSection('content'); ?>
<div class="container">
    <div class="row">
        <div class="col-md-3">
            <?php echo $__env->make('mailboxes._partials.settings_nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Mailbox Permissions</h4>
                </div>
                <div class="card-body">
                    <p>Manage user access to this mailbox.</p>

                    <?php if(session('success')): ?>
                        <div class="alert alert-success">
                            <?php echo e(session('success')); ?>

                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo e(route('mailboxes.permissions.update', $mailbox)); ?>">
                        <?php echo csrf_field(); ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Access Level</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $userMailbox = $user->mailboxes->firstWhere('id', $mailbox->id);
                                        $currentAccess = $userMailbox?->pivot->access;
                                    ?>
                                    <tr>
                                        <td><?php echo e($user->getFullName()); ?></td>
                                        <td>
                                            <select name="permissions[<?php echo e($user->id); ?>]" class="form-control">
                                                <option value="">No Access</option>
                                                <option value="10" <?php if($currentAccess == 10): echo 'selected'; endif; ?>>View Only</option>
                                                <option value="20" <?php if($currentAccess == 20): echo 'selected'; endif; ?>>View and Reply</option>
                                                <option value="30" <?php if($currentAccess == 30): echo 'selected'; endif; ?>>Full Access (Admin)</option>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>

                        <button type="submit" class="btn btn-primary">Save Permissions</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/runner/work/freescout/freescout/resources/views/mailboxes/permissions.blade.php ENDPATH**/ ?>