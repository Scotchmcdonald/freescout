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
            <?php echo e(__('System Dashboard')); ?>

        </h2>
     <?php $__env->endSlot(); ?>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500 mb-1"><?php echo e(__('Total Users')); ?></div>
                    <div class="text-3xl font-bold text-gray-900"><?php echo e($stats['users']); ?></div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500 mb-1"><?php echo e(__('Mailboxes')); ?></div>
                    <div class="text-3xl font-bold text-gray-900"><?php echo e($stats['mailboxes']); ?></div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500 mb-1"><?php echo e(__('Total Conversations')); ?></div>
                    <div class="text-3xl font-bold text-gray-900"><?php echo e($stats['conversations']); ?></div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500 mb-1"><?php echo e(__('Customers')); ?></div>
                    <div class="text-3xl font-bold text-gray-900"><?php echo e($stats['customers']); ?></div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4"><?php echo e(__('Active Conversations')); ?></h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 bg-green-50 rounded-lg">
                            <div class="text-2xl font-bold text-green-600"><?php echo e($stats['active_conversations']); ?></div>
                            <div class="text-sm text-green-700">Active</div>
                        </div>
                        <div class="p-4 bg-orange-50 rounded-lg">
                            <div class="text-2xl font-bold text-orange-600"><?php echo e($stats['unassigned_conversations']); ?></div>
                            <div class="text-sm text-orange-700">Unassigned</div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4"><?php echo e(__('System Information')); ?></h3>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-600">PHP Version</dt>
                            <dd class="font-medium"><?php echo e($systemInfo['php_version']); ?></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Laravel Version</dt>
                            <dd class="font-medium"><?php echo e($systemInfo['laravel_version']); ?></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Database Version</dt>
                            <dd class="font-medium"><?php echo e(Str::limit($systemInfo['db_version'], 20)); ?></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Memory Limit</dt>
                            <dd class="font-medium"><?php echo e($systemInfo['memory_limit']); ?></dd>
                        </div>
                    </dl>
                </div>
            </div>
            
            <!-- System Tools -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4"><?php echo e(__('System Tools')); ?></h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <button onclick="clearCache()" 
                            class="px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg font-medium">
                        <?php echo e(__('Clear Cache')); ?>

                    </button>
                    
                    <button onclick="optimizeApp()" 
                            class="px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg font-medium">
                        <?php echo e(__('Optimize Application')); ?>

                    </button>
                    
                    <button onclick="runDiagnostics()" 
                            class="px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg font-medium">
                        <?php echo e(__('Run Diagnostics')); ?>

                    </button>
                    
                    <a href="<?php echo e(route('system.logs')); ?>" 
                       class="px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg font-medium text-center">
                        <?php echo e(__('View Logs')); ?>

                    </a>
                </div>
                
                <div id="systemMessage" class="mt-4 hidden"></div>
            </div>
        </div>
    </div>
    
    <script>
        function clearCache() {
            executeSystemAction('clear_cache', 'Clearing cache...');
        }
        
        function optimizeApp() {
            executeSystemAction('optimize', 'Optimizing application...');
        }
        
        function runDiagnostics() {
            fetch('<?php echo e(route('system.diagnostics')); ?>', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let message = 'Diagnostics Results:\n';
                    for (const [key, result] of Object.entries(data.checks)) {
                        message += `\n${key}: ${result.status.toUpperCase()} - ${result.message}`;
                    }
                    alert(message);
                } else {
                    showMessage('error', 'Diagnostics failed');
                }
            });
        }
        
        function executeSystemAction(action, loadingMessage) {
            showMessage('info', loadingMessage);
            
            fetch('<?php echo e(route('system.ajax')); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ action: action })
            })
            .then(response => response.json())
            .then(data => {
                showMessage(data.success ? 'success' : 'error', data.message);
            })
            .catch(error => {
                showMessage('error', 'Operation failed: ' + error);
            });
        }
        
        function showMessage(type, message) {
            const messageDiv = document.getElementById('systemMessage');
            let bgColor = 'bg-blue-50 border-blue-400 text-blue-700';
            
            if (type === 'success') bgColor = 'bg-green-50 border-green-400 text-green-700';
            if (type === 'error') bgColor = 'bg-red-50 border-red-400 text-red-700';
            
            messageDiv.className = `p-4 border-l-4 ${bgColor}`;
            messageDiv.textContent = message;
            messageDiv.classList.remove('hidden');
            
            if (type !== 'info') {
                setTimeout(() => {
                    messageDiv.classList.add('hidden');
                }, 5000);
            }
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
<?php /**PATH /var/www/html/resources/views/system/index.blade.php ENDPATH**/ ?>