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
            <?php echo e(__('Conversations')); ?> - <?php echo e($mailbox->name); ?>

        </h2>
     <?php $__env->endSlot(); ?>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <?php if($conversations->isEmpty()): ?>
                        <div class="text-center py-8 text-gray-500">
                            <p>No conversations found in <?php echo e($mailbox->name); ?>.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php $__currentLoopData = $conversations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $conversation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="border-b pb-4 last:border-b-0">
                                    <a href="<?php echo e(route('conversations.show', $conversation)); ?>" class="block hover:bg-gray-50 p-2 rounded">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <h3 class="font-semibold text-lg"><?php echo e($conversation->subject); ?></h3>
                                                <p class="text-sm text-gray-600">
                                                    <?php echo e($conversation->customer->first_name); ?> <?php echo e($conversation->customer->last_name); ?>

                                                    &lt;<?php echo e($conversation->customer_email); ?>&gt;
                                                </p>
                                                <p class="text-sm text-gray-500 mt-1"><?php echo e($conversation->preview); ?></p>
                                            </div>
                                            <div class="text-right text-sm text-gray-500">
                                                <p><?php echo e($conversation->mailbox->name); ?></p>
                                                <p><?php echo e($conversation->last_reply_at?->diffForHumans()); ?></p>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>

                        <div class="mt-6">
                            <?php echo e($conversations->links()); ?>

                        </div>
                    <?php endif; ?>
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
<?php /**PATH /home/runner/work/freescout/freescout/resources/views/conversations/index.blade.php ENDPATH**/ ?>