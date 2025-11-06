<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('System Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500 mb-1">{{ __('Total Users') }}</div>
                    <div class="text-3xl font-bold text-gray-900">{{ $stats['users'] }}</div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500 mb-1">{{ __('Mailboxes') }}</div>
                    <div class="text-3xl font-bold text-gray-900">{{ $stats['mailboxes'] }}</div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500 mb-1">{{ __('Total Conversations') }}</div>
                    <div class="text-3xl font-bold text-gray-900">{{ $stats['conversations'] }}</div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500 mb-1">{{ __('Customers') }}</div>
                    <div class="text-3xl font-bold text-gray-900">{{ $stats['customers'] }}</div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">{{ __('Active Conversations') }}</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 bg-green-50 rounded-lg">
                            <div class="text-2xl font-bold text-green-600">{{ $stats['active_conversations'] }}</div>
                            <div class="text-sm text-green-700">Active</div>
                        </div>
                        <div class="p-4 bg-orange-50 rounded-lg">
                            <div class="text-2xl font-bold text-orange-600">{{ $stats['unassigned_conversations'] }}</div>
                            <div class="text-sm text-orange-700">Unassigned</div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">{{ __('System Information') }}</h3>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-600">PHP Version</dt>
                            <dd class="font-medium">{{ $systemInfo['php_version'] }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Laravel Version</dt>
                            <dd class="font-medium">{{ $systemInfo['laravel_version'] }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Database Version</dt>
                            <dd class="font-medium">{{ Str::limit($systemInfo['db_version'], 20) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Memory Limit</dt>
                            <dd class="font-medium">{{ $systemInfo['memory_limit'] }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
            
            <!-- System Tools -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">{{ __('System Tools') }}</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <button onclick="clearCache()" 
                            class="px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg font-medium">
                        {{ __('Clear Cache') }}
                    </button>
                    
                    <button onclick="optimizeApp()" 
                            class="px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg font-medium">
                        {{ __('Optimize Application') }}
                    </button>
                    
                    <button onclick="runDiagnostics()" 
                            class="px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg font-medium">
                        {{ __('Run Diagnostics') }}
                    </button>
                    
                    <a href="{{ route('system.logs') }}" 
                       class="px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg font-medium text-center">
                        {{ __('View Logs') }}
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
            fetch('{{ route('system.diagnostics') }}', {
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
            
            fetch('{{ route('system.ajax') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
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
</x-app-layout>
