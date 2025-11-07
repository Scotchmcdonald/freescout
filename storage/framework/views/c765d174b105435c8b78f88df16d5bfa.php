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
            <?php echo e(__('Modules')); ?>

        </h2>
     <?php $__env->endSlot(); ?>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <?php if(session('success')): ?>
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    <?php echo e(session('success')); ?>

                </div>
            <?php endif; ?>

            <?php if(session('error')): ?>
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <?php echo e(session('error')); ?>

                </div>
            <?php endif; ?>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900">
                            <?php echo e(__('Installed Modules')); ?>

                        </h3>
                        <p class="mt-1 text-sm text-gray-600">
                            <?php echo e(__('Manage your installed modules. Enable or disable modules as needed.')); ?>

                        </p>
                    </div>

                    <?php if(count($modules) > 0): ?>
                        <div class="space-y-4">
                            <?php $__currentLoopData = $modules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition-colors"
                                     x-data="{ processing: false }">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center">
                                                <h4 class="text-lg font-medium text-gray-900">
                                                    <?php echo e($module['name']); ?>

                                                </h4>
                                                <span class="ml-3 px-2 py-1 text-xs rounded-full <?php echo e($module['enabled'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'); ?>">
                                                    <?php echo e($module['enabled'] ? __('Enabled') : __('Disabled')); ?>

                                                </span>
                                            </div>
                                            
                                            <?php if($module['description']): ?>
                                                <p class="mt-1 text-sm text-gray-600">
                                                    <?php echo e($module['description']); ?>

                                                </p>
                                            <?php endif; ?>

                                            <div class="mt-2 flex items-center space-x-4 text-xs text-gray-500">
                                                <span><?php echo e(__('Alias')); ?>: <code class="bg-gray-100 px-1 py-0.5 rounded"><?php echo e($module['alias']); ?></code></span>
                                                <span><?php echo e(__('Version')); ?>: <?php echo e($module['version']); ?></span>
                                            </div>
                                        </div>

                                        <div class="ml-4 flex-shrink-0 flex items-center space-x-2">
                                            <?php if($module['enabled']): ?>
                                                <button 
                                                    @click="
                                                        processing = true;
                                                        fetch('<?php echo e(route('modules.disable', $module['alias'])); ?>', {
                                                            method: 'POST',
                                                            headers: {
                                                                'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                                                                'Content-Type': 'application/json',
                                                                'Accept': 'application/json'
                                                            }
                                                        })
                                                        .then(response => response.json())
                                                        .then(data => {
                                                            if (data.status === 'success') {
                                                                window.location.reload();
                                                            } else {
                                                                alert(data.message);
                                                                processing = false;
                                                            }
                                                        })
                                                        .catch(error => {
                                                            alert('<?php echo e(__('An error occurred')); ?>');
                                                            processing = false;
                                                        });
                                                    "
                                                    :disabled="processing"
                                                    class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-md disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                                    <span x-show="!processing"><?php echo e(__('Disable')); ?></span>
                                                    <span x-show="processing"><?php echo e(__('Processing...')); ?></span>
                                                </button>
                                            <?php else: ?>
                                                <button 
                                                    @click="
                                                        processing = true;
                                                        fetch('<?php echo e(route('modules.enable', $module['alias'])); ?>', {
                                                            method: 'POST',
                                                            headers: {
                                                                'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                                                                'Content-Type': 'application/json',
                                                                'Accept': 'application/json'
                                                            }
                                                        })
                                                        .then(response => response.json())
                                                        .then(data => {
                                                            if (data.status === 'success') {
                                                                window.location.reload();
                                                            } else {
                                                                alert(data.message);
                                                                processing = false;
                                                            }
                                                        })
                                                        .catch(error => {
                                                            alert('<?php echo e(__('An error occurred')); ?>');
                                                            processing = false;
                                                        });
                                                    "
                                                    :disabled="processing"
                                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                                    <span x-show="!processing"><?php echo e(__('Enable')); ?></span>
                                                    <span x-show="processing"><?php echo e(__('Processing...')); ?></span>
                                                </button>
                                            <?php endif; ?>

                                            <button 
                                                @click="
                                                    if (confirm('<?php echo e(__('Are you sure you want to delete this module?')); ?>')) {
                                                        processing = true;
                                                        fetch('<?php echo e(route('modules.delete', $module['alias'])); ?>', {
                                                            method: 'DELETE',
                                                            headers: {
                                                                'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                                                                'Content-Type': 'application/json',
                                                                'Accept': 'application/json'
                                                            }
                                                        })
                                                        .then(response => response.json())
                                                        .then(data => {
                                                            if (data.status === 'success') {
                                                                window.location.reload();
                                                            } else {
                                                                alert(data.message);
                                                                processing = false;
                                                            }
                                                        })
                                                        .catch(error => {
                                                            alert('<?php echo e(__('An error occurred')); ?>');
                                                            processing = false;
                                                        });
                                                    }
                                                "
                                                :disabled="processing"
                                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                                <span x-show="!processing"><?php echo e(__('Delete')); ?></span>
                                                <span x-show="processing"><?php echo e(__('Deleting...')); ?></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900"><?php echo e(__('No modules installed')); ?></h3>
                            <p class="mt-1 text-sm text-gray-500">
                                <?php echo e(__('Install modules by placing them in the Modules directory.')); ?>

                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">
                            <?php echo e(__('Module Development')); ?>

                        </h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p><?php echo e(__('Create a new module using:')); ?></p>
                            <code class="block mt-2 bg-white px-2 py-1 rounded text-xs">php artisan module:make ModuleName</code>
                        </div>
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
<?php /**PATH /home/runner/work/freescout/freescout/resources/views/modules/index.blade.php ENDPATH**/ ?>