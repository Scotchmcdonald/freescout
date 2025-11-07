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
            <?php echo e(__('Search Results')); ?>: "<?php echo e($query); ?>"
        </h2>
     <?php $__env->endSlot(); ?>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <form method="GET" action="<?php echo e(route('conversations.search')); ?>" class="flex gap-2">
                            <input type="text" name="q" value="<?php echo e($query); ?>" 
                                   placeholder="Search conversations..."
                                   class="flex-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                Search
                            </button>
                        </form>
                    </div>
                    
                    <?php if($conversations->isEmpty()): ?>
                        <div class="text-center py-12 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <p class="mt-2">No conversations found matching "<?php echo e($query); ?>"</p>
                        </div>
                    <?php else: ?>
                        <div class="mb-4 text-sm text-gray-600">
                            Found <?php echo e($conversations->total()); ?> conversation(s)
                        </div>
                        
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
                                            
                                            <div class="flex items-center space-x-4 text-sm text-gray-600 mb-2">
                                                <span><?php echo e($conversation->mailbox->name); ?></span>
                                                <span>•</span>
                                                <span><?php echo e($conversation->customer->getFullName()); ?></span>
                                                <span>•</span>
                                                <span><?php echo e($conversation->customer_email); ?></span>
                                                <?php if($conversation->user): ?>
                                                    <span>•</span>
                                                    <span><?php echo e($conversation->user->getFullName()); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <p class="text-sm text-gray-600">
                                                <?php echo e(Str::limit($conversation->preview, 150)); ?>

                                            </p>
                                        </div>
                                        
                                        <div class="text-right text-sm text-gray-500 ml-4">
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
                            <?php echo e($conversations->appends(['q' => $query])->links()); ?>

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
<?php /**PATH /home/runner/work/freescout/freescout/resources/views/conversations/search.blade.php ENDPATH**/ ?>