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
            <?php echo e($customer->getFullName()); ?>

        </h2>
     <?php $__env->endSlot(); ?>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Customer Info -->
                <div class="lg:col-span-1">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold">Customer Details</h3>
                            <a href="<?php echo e(route('customers.edit', $customer)); ?>" 
                               class="text-blue-600 hover:text-blue-800 text-sm">Edit</a>
                        </div>
                        
                        <div class="space-y-4 text-sm">
                            <div>
                                <div class="text-gray-500 mb-1">Name</div>
                                <div class="font-medium"><?php echo e($customer->getFullName()); ?></div>
                            </div>
                            
                            <?php if($customer->emails && count($customer->emails)): ?>
                                <div>
                                    <div class="text-gray-500 mb-1">Email(s)</div>
                                    <?php $__currentLoopData = $customer->emails; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $email): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="font-medium"><?php echo e($email['email'] ?? ''); ?></div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if($customer->company): ?>
                                <div>
                                    <div class="text-gray-500 mb-1">Company</div>
                                    <div class="font-medium"><?php echo e($customer->company); ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if($customer->job_title): ?>
                                <div>
                                    <div class="text-gray-500 mb-1">Job Title</div>
                                    <div class="font-medium"><?php echo e($customer->job_title); ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if($customer->phones && count($customer->phones)): ?>
                                <div>
                                    <div class="text-gray-500 mb-1">Phone(s)</div>
                                    <?php $__currentLoopData = $customer->phones; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $phone): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="font-medium"><?php echo e($phone['number'] ?? ''); ?></div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if($customer->address): ?>
                                <div>
                                    <div class="text-gray-500 mb-1">Address</div>
                                    <div class="font-medium">
                                        <?php echo e($customer->address); ?><br>
                                        <?php if($customer->city || $customer->state || $customer->zip): ?>
                                            <?php echo e($customer->city); ?><?php echo e($customer->state ? ', ' . $customer->state : ''); ?> <?php echo e($customer->zip); ?><br>
                                        <?php endif; ?>
                                        <?php if($customer->country): ?>
                                            <?php echo e($customer->country); ?>

                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if($customer->notes): ?>
                                <div>
                                    <div class="text-gray-500 mb-1">Notes</div>
                                    <div class="font-medium text-gray-700"><?php echo e($customer->notes); ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <div>
                                <div class="text-gray-500 mb-1">Customer Since</div>
                                <div class="font-medium"><?php echo e($customer->created_at->format('M d, Y')); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Conversations -->
                <div class="lg:col-span-2">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-4">Conversations (<?php echo e($customer->conversations->count()); ?>)</h3>
                        
                        <?php if($customer->conversations->isEmpty()): ?>
                            <div class="text-center py-12 text-gray-500">
                                <p>No conversations yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php $__currentLoopData = $customer->conversations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $conversation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <div class="flex items-center space-x-2 mb-2">
                                                    <a href="<?php echo e(route('conversations.show', $conversation)); ?>" 
                                                       class="text-base font-medium text-gray-900 hover:text-blue-600">
                                                        <?php echo e($conversation->subject); ?>

                                                    </a>
                                                    <?php if($conversation->status == 1): ?>
                                                        <span class="px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800 rounded">Active</span>
                                                    <?php elseif($conversation->status == 2): ?>
                                                        <span class="px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-800 rounded">Closed</span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="flex items-center space-x-4 text-sm text-gray-600">
                                                    <span><?php echo e($conversation->mailbox->name); ?></span>
                                                    <span>•</span>
                                                    <span><?php echo e($conversation->folder->name); ?></span>
                                                    <?php if($conversation->user): ?>
                                                        <span>•</span>
                                                        <span>Assigned to <?php echo e($conversation->user->getFullName()); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="text-right text-sm text-gray-500">
                                                <div><?php echo e($conversation->last_reply_at->diffForHumans()); ?></div>
                                                <div class="mt-1">
                                                    <span class="px-2 py-0.5 bg-gray-200 rounded text-xs">
                                                        <?php echo e($conversation->threads_count); ?> replies
                                                    </span>
                                                </div>
                                            </div>
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
<?php /**PATH /home/runner/work/freescout/freescout/resources/views/customers/show.blade.php ENDPATH**/ ?>