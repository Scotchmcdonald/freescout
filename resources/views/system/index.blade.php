<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('System Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="systemTools('{{ route('system.ajax') }}', '{{ route('system.diagnostics') }}', '{{ csrf_token() }}')">
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
                            <dt class="text-gray-600">App Version</dt>
                            <dd class="font-medium">{{ config('app.version', '1.0.0') }}</dd>
                        </div>
                        @if($updateInfo)
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Current Commit</dt>
                            <dd class="font-medium font-mono">
                                @if(!empty($updateInfo['current_commit_url']))
                                    <a href="{{ $updateInfo['current_commit_url'] }}" target="_blank" class="text-blue-600 hover:text-blue-800 hover:underline">{{ $updateInfo['current_commit'] }}</a>
                                @else
                                    {{ $updateInfo['current_commit'] }}
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Branch</dt>
                            <dd class="font-medium">{{ $updateInfo['branch'] }}</dd>
                        </div>
                        @if($updateInfo['has_update'])
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Update Available</dt>
                            <dd class="font-medium text-yellow-600">{{ $updateInfo['commits_behind'] ?? 0 }} commits behind</dd>
                        </div>
                        @endif
                        @endif
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
                            <dd class="font-medium">{{ $systemInfo['db_version'] }}</dd>
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
                    @if($updateInfo && $updateInfo['has_update'])
                    <a href="{{ route('system.update') }}" 
                       class="px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium text-center">
                        {{ __('Update Application') }}
                    </a>
                    @endif
                    
                    <button @click="clearCache()" 
                            :disabled="loading"
                            class="px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg font-medium disabled:opacity-50">
                        {{ __('Clear Cache') }}
                    </button>
                    
                    <button @click="optimizeApp()" 
                            :disabled="loading"
                            class="px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg font-medium disabled:opacity-50">
                        {{ __('Optimize Application') }}
                    </button>

                    <button @click="rebuildNpm()" 
                            :disabled="loading"
                            class="px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg font-medium disabled:opacity-50">
                        {{ __('Rebuild Assets') }}
                    </button>
                    
                    <button @click="runDiagnostics()" 
                            :disabled="loading"
                            class="px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg font-medium disabled:opacity-50">
                        {{ __('Run Diagnostics') }}
                    </button>
                    
                    <a href="{{ route('system.logs') }}" 
                       class="px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg font-medium text-center">
                        {{ __('View Logs') }}
                    </a>
                </div>
                
                <!-- Status Message -->
                <div x-show="message" 
                     x-transition
                     class="mt-4 p-4 border-l-4"
                     :class="{
                         'bg-blue-50 border-blue-400 text-blue-700': messageType === 'info',
                         'bg-green-50 border-green-400 text-green-700': messageType === 'success',
                         'bg-red-50 border-red-400 text-red-700': messageType === 'error'
                     }">
                    <span x-text="message"></span>
                </div>

                <!-- Diagnostics Results -->
                <div x-show="diagnosticsResults" x-transition class="mt-6 border-t pt-6">
                    <h4 class="text-lg font-semibold mb-4" x-text="resultsTitle || '{{ __('Diagnostics Results') }}'"></h4>
                    <div class="overflow-x-auto border rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="(result, key) in diagnosticsResults" :key="key">
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="key"></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                                  :class="{
                                                      'bg-green-100 text-green-800': result.status === 'ok',
                                                      'bg-red-100 text-red-800': result.status === 'error',
                                                      'bg-yellow-100 text-yellow-800': result.status === 'warning'
                                                  }"
                                                  x-text="result.status ? result.status.toUpperCase() : 'UNKNOWN'">
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500" x-text="result.message"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
