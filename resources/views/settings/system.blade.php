<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('System Information') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="adminActions('{{ route('settings.cache.clear') }}', '{{ route('settings.migrate') }}', '{{ csrf_token() }}')">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col md:flex-row">
            <x-settings-sidebar :sections="$sections" :current-section="$currentSection" />
            
            <div class="flex-1">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- System Info -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-4">{{ __('System Information') }}</h3>
                        
                        <dl class="space-y-3 text-sm">
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <dt class="font-medium text-gray-700">App Version</dt>
                            <dd class="text-gray-900">{{ config('app.version', '1.0.0') }}</dd>
                        </div>
                        @if($updateInfo)
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <dt class="font-medium text-gray-700">Current Commit</dt>
                            <dd class="text-gray-900 font-mono">{{ $updateInfo['current_commit'] }}</dd>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <dt class="font-medium text-gray-700">Branch</dt>
                            <dd class="text-gray-900">{{ $updateInfo['branch'] }}</dd>
                        </div>
                        @if($updateInfo['has_update'])
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <dt class="font-medium text-gray-700">Update Status</dt>
                            <dd class="text-yellow-600 font-medium">{{ $updateInfo['commits_behind'] }} commits behind</dd>
                        </div>
                        @endif
                        @endif
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <dt class="font-medium text-gray-700">PHP Version</dt>
                            <dd class="text-gray-900">{{ $settings['php_version'] }}</dd>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <dt class="font-medium text-gray-700">Laravel Version</dt>
                            <dd class="text-gray-900">{{ $settings['laravel_version'] }}</dd>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <dt class="font-medium text-gray-700">Database Connection</dt>
                            <dd class="text-gray-900">{{ $settings['db_connection'] }}</dd>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <dt class="font-medium text-gray-700">Cache Driver</dt>
                            <dd class="text-gray-900">{{ $settings['cache_driver'] }}</dd>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <dt class="font-medium text-gray-700">Queue Connection</dt>
                            <dd class="text-gray-900">{{ $settings['queue_connection'] }}</dd>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <dt class="font-medium text-gray-700">Session Driver</dt>
                            <dd class="text-gray-900">{{ $settings['session_driver'] }}</dd>
                        </div>
                    </dl>
                </div>
                
                <!-- Quick Actions -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">{{ __('System Tools') }}</h3>
                    
                    <div class="space-y-3">
                        @if($updateInfo && $updateInfo['has_update'])
                        <a href="{{ route('system.update') }}" 
                           class="block w-full px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-left flex justify-between items-center">
                            <div>
                                <span class="font-medium">{{ __('Update Application') }}</span>
                                <span class="block text-xs mt-1 opacity-90">{{ $updateInfo['commits_behind'] }} commits behind</span>
                            </div>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                        </a>
                        @endif
                        
                        <button @click="clearCache()" 
                                :disabled="loading"
                                class="w-full px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg text-left flex justify-between items-center disabled:opacity-50">
                            <span class="font-medium">{{ __('Clear Cache') }}</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                        
                        <button @click="runMigrations()" 
                                :disabled="loading"
                                class="w-full px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg text-left flex justify-between items-center disabled:opacity-50">
                            <span class="font-medium">{{ __('Run Migrations') }}</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                            </svg>
                        </button>
                        
                        <a href="{{ route('system.logs') }}" 
                           class="block w-full px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg text-left flex justify-between items-center">
                            <span class="font-medium">{{ __('View Logs') }}</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </a>
                    </div>
                    
                    <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                        <h4 class="text-sm font-semibold text-blue-900 mb-2">{{ __('System Status') }}</h4>
                        <p class="text-sm text-blue-700">All systems operational</p>
                    </div>
                </div>
            </div>
            
            <!-- Response Messages -->
            <div x-show="message" 
                 x-cloak
                 class="mt-6 p-4 border-l-4"
                 :class="messageType === 'success' ? 'bg-green-50 border-green-400 text-green-700' : 'bg-red-50 border-red-400 text-red-700'"
                 x-text="message">
            </div>
            </div>
        </div>
    </div>
</x-app-layout>
