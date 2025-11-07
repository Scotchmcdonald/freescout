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
            <?php echo e(__('System Logs')); ?>

        </h2>
     <?php $__env->endSlot(); ?>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Tab Navigation -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px">
                        <a href="<?php echo e(route('system.logs', ['type' => 'application'])); ?>" 
                           class="px-6 py-3 border-b-2 font-medium text-sm <?php echo e($currentType === 'application' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'); ?>">
                            Application Logs
                        </a>
                        <a href="<?php echo e(route('system.logs', ['type' => 'email'])); ?>" 
                           class="px-6 py-3 border-b-2 font-medium text-sm <?php echo e($currentType === 'email' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'); ?>">
                            Email Logs
                        </a>
                        <a href="<?php echo e(route('system.logs', ['type' => 'activity'])); ?>" 
                           class="px-6 py-3 border-b-2 font-medium text-sm <?php echo e($currentType === 'activity' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'); ?>">
                            Activity Logs
                        </a>
                    </nav>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <?php if($currentType === 'application'): ?>
                        <!-- Application Logs -->
                        <div class="mb-4 flex justify-between items-center">
                            <h3 class="text-lg font-semibold"><?php echo e(__('Recent Log Entries')); ?></h3>
                            <span class="text-sm text-gray-600">Showing last 100 lines</span>
                        </div>
                        
                        <?php if(empty($lines) || count($lines) === 0): ?>
                            <div class="text-center py-12 text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="mt-2">No log entries found</p>
                            </div>
                        <?php else: ?>
                            <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                                <pre class="text-xs text-gray-100 font-mono whitespace-pre-wrap"><?php $__currentLoopData = $lines; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $line): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php echo e($line); ?>

<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></pre>
                            </div>
                        <?php endif; ?>

                    <?php elseif($currentType === 'email'): ?>
                        <!-- Email Logs -->
                        <h3 class="text-lg font-semibold mb-4"><?php echo e(__('Email Send Logs')); ?></h3>
                        
                        <?php if($sendLogs->isEmpty()): ?>
                            <div class="text-center py-12 text-gray-500">
                                <p>No email logs found</p>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Recipient</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php $__currentLoopData = $sendLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo e($log->id); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    <?php if($log->user_id): ?>
                                                        <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">User</span>
                                                    <?php else: ?>
                                                        <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">Customer</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-600"><?php echo e($log->email); ?></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    <?php if($log->status == 1): ?>
                                                        <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">Sent</span>
                                                    <?php else: ?>
                                                        <span class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded">Failed</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                    <?php echo e($log->created_at->format('Y-m-d H:i:s')); ?>

                                                </td>
                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-6">
                                <?php echo e($sendLogs->appends(['type' => 'email'])->links()); ?>

                            </div>
                        <?php endif; ?>

                    <?php elseif($currentType === 'activity'): ?>
                        <!-- Activity Logs -->
                        <h3 class="text-lg font-semibold mb-4"><?php echo e(__('Activity Logs')); ?></h3>
                        
                        <?php if($activityLogs->isEmpty()): ?>
                            <div class="text-center py-12 text-gray-500">
                                <p>No activity logs found</p>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php $__currentLoopData = $activityLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium"><?php echo e($log->event); ?></td>
                                                <td class="px-6 py-4 text-sm text-gray-600">
                                                    <?php echo e($log->causer?->getFullName() ?? 'System'); ?>

                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-600">
                                                    <?php echo e($log->subject_type); ?> #<?php echo e($log->subject_id); ?>

                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                    <?php echo e($log->created_at->format('Y-m-d H:i:s')); ?>

                                                </td>
                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-6">
                                <?php echo e($activityLogs->appends(['type' => 'activity'])->links()); ?>

                            </div>
                        <?php endif; ?>
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
<?php /**PATH /home/runner/work/freescout/freescout/resources/views/system/logs.blade.php ENDPATH**/ ?>