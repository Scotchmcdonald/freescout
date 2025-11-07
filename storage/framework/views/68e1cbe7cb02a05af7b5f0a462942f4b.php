<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('header', null, []); ?> 
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <?php echo e(__('Dashboard')); ?>

        </h2>
     <?php $__env->endSlot(); ?>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium mb-4">Welcome, <?php echo e($user->full_name); ?>!</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-blue-100 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-blue-800"><?php echo e($mailboxes->count()); ?></div>
                            <div class="text-sm text-blue-600">Mailboxes</div>
                        </div>
                        
                        <div class="bg-green-100 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-green-800"><?php echo e($activeConversations); ?></div>
                            <div class="text-sm text-green-600">Active Conversations</div>
                        </div>
                        
                        <div class="bg-yellow-100 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-yellow-800"><?php echo e($unassignedConversations); ?></div>
                            <div class="text-sm text-yellow-600">Unassigned</div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h4 class="font-semibold mb-2">Your Mailboxes</h4>
                        <ul class="space-y-2">
                            <?php $__empty_1 = true; $__currentLoopData = $mailboxes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mailbox): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <li class="border-l-4 border-blue-500 pl-3">
                                    <a href="<?php echo e(route('mailboxes.view', $mailbox->id)); ?>" class="text-blue-600 hover:text-blue-800">
                                        <?php echo e($mailbox->name); ?> (<?php echo e($mailbox->email); ?>)
                                    </a>
                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <li class="text-gray-500">No mailboxes assigned</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/dashboard.blade.php ENDPATH**/ ?>