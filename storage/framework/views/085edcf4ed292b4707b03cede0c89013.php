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
            <?php echo e($mailbox->name); ?>

        </h2>
     <?php $__env->endSlot(); ?>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6 flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-semibold"><?php echo e($mailbox->name); ?></h3>
                            <p class="text-sm text-gray-600"><?php echo e($mailbox->email); ?></p>
                        </div>
                        <a href="<?php echo e(route('conversations.create', $mailbox)); ?>" 
                           class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            New Conversation
                        </a>
                    </div>
                    
                    <?php if($folders->count()): ?>
                        <div class="mb-6">
                            <div class="flex space-x-2 border-b border-gray-200">
                                <?php $__currentLoopData = $folders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $folder): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <a href="<?php echo e(route('mailboxes.view', ['mailbox' => $mailbox, 'folder' => $folder->id])); ?>" 
                                       class="px-4 py-2 text-sm font-medium <?php echo e((request('folder') == $folder->id || (!request('folder') && $folder->type == 1)) ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-gray-900'); ?>">
                                        <?php echo e($folder->name); ?>

                                        <?php if($folder->conversations()->count() > 0): ?>
                                            <span class="ml-1 px-2 py-0.5 text-xs bg-gray-200 rounded">
                                                <?php echo e($folder->conversations()->count()); ?>

                                            </span>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($conversations->isEmpty()): ?>
                        <div class="text-center py-12 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <p class="mt-2">No conversations in this folder</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-2">
                            <?php $__currentLoopData = $conversations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $conversation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2 mb-2">
                                                <a href="<?php echo e(route('conversations.show', $conversation)); ?>" 
                                                   class="text-lg font-medium text-gray-900 hover:text-blue-600">
                                                    <?php echo e($conversation->subject); ?>

                                                </a>
                                                <?php if($conversation->status == 1): ?>
                                                    <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded">Active</span>
                                                <?php elseif($conversation->status == 2): ?>
                                                    <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded">Closed</span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="flex items-center space-x-4 text-sm text-gray-600">
                                                <span><?php echo e($conversation->customer->getFullName()); ?></span>
                                                <span>•</span>
                                                <span><?php echo e($conversation->customer_email); ?></span>
                                                <?php if($conversation->user): ?>
                                                    <span>•</span>
                                                    <span>Assigned to <?php echo e($conversation->user->getFullName()); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <p class="mt-2 text-sm text-gray-600">
                                                <?php echo e(Str::limit($conversation->preview, 100)); ?>

                                            </p>
                                        </div>
                                        
                                        <div class="text-right text-sm text-gray-500">
                                            <div><?php echo e($conversation->last_reply_at->diffForHumans()); ?></div>
                                            <div class="mt-1">
                                                <span class="px-2 py-1 bg-gray-200 rounded"><?php echo e($conversation->threads_count); ?> replies</span>
                                            </div>
                                        </div>
                                    </div>
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
<?php /**PATH /home/runner/work/freescout/freescout/resources/views/mailboxes/show.blade.php ENDPATH**/ ?>