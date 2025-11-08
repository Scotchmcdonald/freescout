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
            <?php echo e(__('Mailboxes')); ?>

        </h2>
     <?php $__env->endSlot(); ?>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-4">Your Mailboxes</h3>
                        
                        <?php if($mailboxes->isEmpty()): ?>
                            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                                <p class="text-sm text-yellow-700">
                                    You don't have access to any mailboxes yet.
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php $__currentLoopData = $mailboxes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mailbox): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition">
                                        <div class="flex items-center justify-between mb-3">
                                            <h4 class="text-lg font-semibold text-gray-900">
                                                <?php echo e($mailbox->name); ?>

                                            </h4>
                                            <?php if($mailbox->is_default): ?>
                                                <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded">
                                                    Default
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <p class="text-sm text-gray-600 mb-4"><?php echo e($mailbox->email); ?></p>
                                        
                                        <div class="space-y-2 text-sm">
                                            <div class="flex justify-between">
                                                <span class="text-gray-500">Active Conversations:</span>
                                                <span class="font-medium text-blue-600">
                                                    <?php echo e($mailbox->conversations()->where('status', 1)->count()); ?>

                                                </span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-500">Unassigned:</span>
                                                <span class="font-medium text-orange-600">
                                                    <?php echo e($mailbox->conversations()->whereNull('user_id')->where('status', 1)->count()); ?>

                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-4 pt-4 border-t border-gray-200 flex gap-2">
                                            <a href="<?php echo e(route('mailboxes.view', $mailbox)); ?>" 
                                               class="flex-1 text-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded hover:bg-blue-700">
                                                View
                                            </a>
                                            <a href="<?php echo e(route('conversations.create', $mailbox)); ?>" 
                                               class="flex-1 text-center px-4 py-2 bg-gray-200 text-gray-800 text-sm font-medium rounded hover:bg-gray-300">
                                                New
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        <?php endif; ?>
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
<?php /**PATH /var/www/html/resources/views/mailboxes/index.blade.php ENDPATH**/ ?>