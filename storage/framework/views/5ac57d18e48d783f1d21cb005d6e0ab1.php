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
            <?php echo e($user->getFullName()); ?>

        </h2>
     <?php $__env->endSlot(); ?>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- User Info -->
                <div class="lg:col-span-1">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold"><?php echo e(__('User Details')); ?></h3>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $user)): ?>
                                <a href="<?php echo e(route('users.edit', $user)); ?>" 
                                   class="text-blue-600 hover:text-blue-800 text-sm">Edit</a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex items-center mb-6">
                            <div class="h-20 w-20 rounded-full bg-blue-600 flex items-center justify-center text-white text-2xl font-semibold">
                                <?php echo e(substr($user->first_name, 0, 1)); ?><?php echo e(substr($user->last_name ?? '', 0, 1)); ?>

                            </div>
                            <div class="ml-4">
                                <div class="text-xl font-semibold"><?php echo e($user->getFullName()); ?></div>
                                <div class="text-sm text-gray-600"><?php echo e($user->email); ?></div>
                            </div>
                        </div>
                        
                        <div class="space-y-4 text-sm">
                            <div>
                                <div class="text-gray-500 mb-1"><?php echo e(__('Role')); ?></div>
                                <div class="font-medium">
                                    <?php if($user->role == 1): ?>
                                        <span class="px-2 py-1 text-xs font-medium bg-purple-100 text-purple-800 rounded">Administrator</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded">User</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div>
                                <div class="text-gray-500 mb-1"><?php echo e(__('Status')); ?></div>
                                <div class="font-medium">
                                    <?php if($user->status == 1): ?>
                                        <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded">Active</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded">Inactive</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if($user->job_title): ?>
                                <div>
                                    <div class="text-gray-500 mb-1"><?php echo e(__('Job Title')); ?></div>
                                    <div class="font-medium"><?php echo e($user->job_title); ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if($user->phone): ?>
                                <div>
                                    <div class="text-gray-500 mb-1"><?php echo e(__('Phone')); ?></div>
                                    <div class="font-medium"><?php echo e($user->phone); ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if($user->timezone): ?>
                                <div>
                                    <div class="text-gray-500 mb-1"><?php echo e(__('Timezone')); ?></div>
                                    <div class="font-medium"><?php echo e($user->timezone); ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <div>
                                <div class="text-gray-500 mb-1"><?php echo e(__('Member Since')); ?></div>
                                <div class="font-medium"><?php echo e($user->created_at->format('M d, Y')); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mailboxes -->
                    <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-4"><?php echo e(__('Mailboxes')); ?> (<?php echo e($user->mailboxes->count()); ?>)</h3>
                        
                        <?php if($user->mailboxes->isEmpty()): ?>
                            <p class="text-sm text-gray-500">No mailboxes assigned</p>
                        <?php else: ?>
                            <div class="space-y-2">
                                <?php $__currentLoopData = $user->mailboxes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mailbox): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <a href="<?php echo e(route('mailboxes.view', $mailbox)); ?>" 
                                       class="block p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                                        <div class="font-medium text-gray-900"><?php echo e($mailbox->name); ?></div>
                                        <div class="text-sm text-gray-600"><?php echo e($mailbox->email); ?></div>
                                    </a>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Activity -->
                <div class="lg:col-span-2">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-4"><?php echo e(__('Recent Conversations')); ?></h3>
                        
                        <?php if($user->conversations->isEmpty()): ?>
                            <div class="text-center py-12 text-gray-500">
                                <p>No conversations assigned to this user</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php $__currentLoopData = $user->conversations->take(10); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $conversation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
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
                                                    <span><?php echo e($conversation->customer->getFullName()); ?></span>
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
<?php /**PATH /var/www/html/resources/views/users/show.blade.php ENDPATH**/ ?>