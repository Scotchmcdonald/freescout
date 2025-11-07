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
            <?php echo e(__('System Information')); ?>

        </h2>
     <?php $__env->endSlot(); ?>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- System Info -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4"><?php echo e(__('System Information')); ?></h3>
                    
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <dt class="font-medium text-gray-700">PHP Version</dt>
                            <dd class="text-gray-900"><?php echo e($settings['php_version']); ?></dd>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <dt class="font-medium text-gray-700">Laravel Version</dt>
                            <dd class="text-gray-900"><?php echo e($settings['laravel_version']); ?></dd>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <dt class="font-medium text-gray-700">Database Connection</dt>
                            <dd class="text-gray-900"><?php echo e($settings['db_connection']); ?></dd>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <dt class="font-medium text-gray-700">Cache Driver</dt>
                            <dd class="text-gray-900"><?php echo e($settings['cache_driver']); ?></dd>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <dt class="font-medium text-gray-700">Queue Connection</dt>
                            <dd class="text-gray-900"><?php echo e($settings['queue_connection']); ?></dd>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <dt class="font-medium text-gray-700">Session Driver</dt>
                            <dd class="text-gray-900"><?php echo e($settings['session_driver']); ?></dd>
                        </div>
                    </dl>
                </div>
                
                <!-- Quick Actions -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4"><?php echo e(__('System Tools')); ?></h3>
                    
                    <div class="space-y-3">
                        <button onclick="clearCache()" 
                                class="w-full px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg text-left flex justify-between items-center">
                            <span class="font-medium"><?php echo e(__('Clear Cache')); ?></span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                        
                        <button onclick="runMigrations()" 
                                class="w-full px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg text-left flex justify-between items-center">
                            <span class="font-medium"><?php echo e(__('Run Migrations')); ?></span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                            </svg>
                        </button>
                        
                        <a href="<?php echo e(route('system.logs')); ?>" 
                           class="block w-full px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg text-left flex justify-between items-center">
                            <span class="font-medium"><?php echo e(__('View Logs')); ?></span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </a>
                    </div>
                    
                    <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                        <h4 class="text-sm font-semibold text-blue-900 mb-2"><?php echo e(__('System Status')); ?></h4>
                        <p class="text-sm text-blue-700">All systems operational</p>
                    </div>
                </div>
            </div>
            
            <!-- Response Messages -->
            <div id="responseMessage" class="hidden mt-6"></div>
        </div>
    </div>
    
    <script>
        function clearCache() {
            if (!confirm('Are you sure you want to clear all caches?')) return;
            
            fetch('<?php echo e(route('settings.cache.clear')); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                showMessage(data.success ? 'success' : 'error', data.message);
            })
            .catch(error => {
                showMessage('error', 'Failed to clear cache: ' + error);
            });
        }
        
        function runMigrations() {
            if (!confirm('Are you sure you want to run database migrations?')) return;
            
            fetch('<?php echo e(route('settings.migrate')); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                showMessage(data.success ? 'success' : 'error', data.message);
            })
            .catch(error => {
                showMessage('error', 'Failed to run migrations: ' + error);
            });
        }
        
        function showMessage(type, message) {
            const messageDiv = document.getElementById('responseMessage');
            const bgColor = type === 'success' ? 'bg-green-50 border-green-400 text-green-700' : 'bg-red-50 border-red-400 text-red-700';
            messageDiv.className = `p-4 border-l-4 ${bgColor}`;
            messageDiv.textContent = message;
            messageDiv.classList.remove('hidden');
            
            setTimeout(() => {
                messageDiv.classList.add('hidden');
            }, 5000);
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
<?php /**PATH /home/runner/work/freescout/freescout/resources/views/settings/system.blade.php ENDPATH**/ ?>