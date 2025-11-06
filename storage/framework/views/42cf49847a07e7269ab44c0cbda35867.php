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
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                <?php echo e($conversation->subject); ?>

            </h2>
            <div class="flex gap-2">
                <select class="border-gray-300 rounded-md text-sm" onchange="updateStatus(this.value)">
                    <option value="1" <?php echo e($conversation->status == 1 ? 'selected' : ''); ?>>Active</option>
                    <option value="2" <?php echo e($conversation->status == 2 ? 'selected' : ''); ?>>Closed</option>
                    <option value="3" <?php echo e($conversation->status == 3 ? 'selected' : ''); ?>>Pending</option>
                </select>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <!-- Conversation Header -->
                            <div class="mb-6 pb-6 border-b border-gray-200">
                                <h3 class="text-2xl font-semibold mb-2"><?php echo e($conversation->subject); ?></h3>
                                <div class="flex items-center space-x-4 text-sm text-gray-600">
                                    <span><?php echo e($conversation->customer->getFullName()); ?></span>
                                    <span>•</span>
                                    <span><?php echo e($conversation->customer_email); ?></span>
                                    <span>•</span>
                                    <span><?php echo e($conversation->created_at->format('M d, Y g:i A')); ?></span>
                                </div>
                            </div>
                            
                            <!-- Threads -->
                            <div class="space-y-6">
                                <?php $__currentLoopData = $conversation->threads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $thread): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="border border-gray-200 rounded-lg p-4 <?php echo e($thread->type == 2 ? 'bg-yellow-50' : ''); ?>">
                                        <div class="flex items-start justify-between mb-3">
                                            <div class="flex items-center space-x-3">
                                                <?php if($thread->user): ?>
                                                    <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold">
                                                        <?php echo e(substr($thread->user->first_name, 0, 1)); ?><?php echo e(substr($thread->user->last_name, 0, 1)); ?>

                                                    </div>
                                                    <div>
                                                        <div class="font-medium text-gray-900"><?php echo e($thread->user->getFullName()); ?></div>
                                                        <div class="text-sm text-gray-500"><?php echo e($thread->created_at->diffForHumans()); ?></div>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="w-10 h-10 rounded-full bg-gray-400 flex items-center justify-center text-white font-semibold">
                                                        <?php echo e(substr($conversation->customer->first_name, 0, 1)); ?>

                                                    </div>
                                                    <div>
                                                        <div class="font-medium text-gray-900"><?php echo e($conversation->customer->getFullName()); ?></div>
                                                        <div class="text-sm text-gray-500"><?php echo e($thread->created_at->diffForHumans()); ?></div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if($thread->type == 2): ?>
                                                <span class="px-2 py-1 text-xs font-medium bg-yellow-200 text-yellow-800 rounded">Note</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="prose max-w-none">
                                            <?php echo nl2br(e($thread->body)); ?>

                                        </div>
                                        
                                        <?php if($thread->attachments->count()): ?>
                                            <div class="mt-4 pt-4 border-t border-gray-200">
                                                <div class="text-sm font-medium text-gray-700 mb-2">Attachments:</div>
                                                <div class="space-y-1">
                                                    <?php $__currentLoopData = $thread->attachments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attachment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <a href="<?php echo e($attachment->url); ?>" class="text-sm text-blue-600 hover:underline flex items-center">
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                                            </svg>
                                                            <?php echo e($attachment->file_name); ?> (<?php echo e(number_format($attachment->size / 1024, 2)); ?> KB)
                                                        </a>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                            
                            <!-- Reply Form -->
                            <div class="mt-6 pt-6 border-t border-gray-200">
                                <form id="replyForm" onsubmit="submitReply(event)">
                                    <?php echo csrf_field(); ?>
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Reply</label>
                                        <textarea name="body" rows="6" required
                                                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                                    </div>
                                    
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <label class="flex items-center">
                                                <input type="radio" name="type" value="1" checked class="mr-1">
                                                <span class="text-sm">Reply</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input type="radio" name="type" value="2" class="mr-1">
                                                <span class="text-sm">Note</span>
                                            </label>
                                        </div>
                                        
                                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                            Send Reply
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h4 class="font-semibold mb-4">Details</h4>
                        
                        <div class="space-y-4 text-sm">
                            <div>
                                <div class="text-gray-500 mb-1">Mailbox</div>
                                <div class="font-medium"><?php echo e($conversation->mailbox->name); ?></div>
                            </div>
                            
                            <div>
                                <div class="text-gray-500 mb-1">Customer</div>
                                <a href="<?php echo e(route('customers.show', $conversation->customer)); ?>" class="font-medium text-blue-600 hover:underline">
                                    <?php echo e($conversation->customer->getFullName()); ?>

                                </a>
                            </div>
                            
                            <div>
                                <div class="text-gray-500 mb-1">Status</div>
                                <div class="font-medium">
                                    <?php if($conversation->status == 1): ?>
                                        <span class="text-green-600">Active</span>
                                    <?php elseif($conversation->status == 2): ?>
                                        <span class="text-gray-600">Closed</span>
                                    <?php else: ?>
                                        <span class="text-orange-600">Pending</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div>
                                <div class="text-gray-500 mb-1">Assigned To</div>
                                <div class="font-medium">
                                    <?php echo e($conversation->user ? $conversation->user->getFullName() : 'Unassigned'); ?>

                                </div>
                            </div>
                            
                            <div>
                                <div class="text-gray-500 mb-1">Folder</div>
                                <div class="font-medium"><?php echo e($conversation->folder->name); ?></div>
                            </div>
                            
                            <div>
                                <div class="text-gray-500 mb-1">Created</div>
                                <div class="font-medium"><?php echo e($conversation->created_at->format('M d, Y g:i A')); ?></div>
                            </div>
                            
                            <div>
                                <div class="text-gray-500 mb-1">Last Reply</div>
                                <div class="font-medium"><?php echo e($conversation->last_reply_at->diffForHumans()); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function submitReply(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            
            fetch('<?php echo e(route('conversations.reply', $conversation)); ?>', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to send reply'));
                }
            })
            .catch(error => {
                alert('Error: ' + error);
            });
        }
        
        function updateStatus(status) {
            fetch('<?php echo e(route('conversations.ajax')); ?>', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'change_status',
                    conversation_id: <?php echo e($conversation->id); ?>,
                    status: status
                }),
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
    </script>
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
<?php /**PATH /var/www/html/resources/views/conversations/show.blade.php ENDPATH**/ ?>