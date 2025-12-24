<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('System Update') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('status'))
                <div class="mb-4 border px-4 py-3 rounded" style="background-color: var(--theme-status-success-bg); border-color: var(--theme-status-success-bg); color: var(--theme-status-success-text)">
                    {{ session('status') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 border px-4 py-3 rounded" style="background-color: var(--theme-status-error-bg); border-color: var(--theme-status-error-bg); color: var(--theme-status-error-text)">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        {{ __('Application Version') }}
                    </h3>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between py-3 border-b">
                            <span class="text-gray-700">{{ __('Version') }}:</span>
                            <span class="font-mono text-gray-900">{{ config('app.version', '1.0.0') }}</span>
                        </div>
                        
                        @if(!empty($update_info))
                            <div class="flex items-center justify-between py-3 border-b">
                                <span class="text-gray-700">{{ __('Current Commit') }}:</span>
                                <code class="px-2 py-1 rounded text-sm" style="background-color: var(--theme-bg-input)">{{ $update_info['current_commit'] }}</code>
                            </div>
                            <div class="flex items-center justify-between py-3 border-b">
                                <span class="text-gray-700">{{ __('Branch') }}:</span>
                                <span class="font-mono text-gray-900">{{ $update_info['branch'] }}</span>
                            </div>
                        @endif
                    </div>

                    @if($update_available && !empty($update_info))
                        <div class="mt-6 border rounded-lg p-4" style="background-color: var(--theme-status-info-bg); border-color: var(--theme-status-info-bg)">
                            <div class="flex items-start">
                                <svg class="h-5 w-5 mt-0.5 mr-3" style="color: var(--theme-status-info-text)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div class="flex-1">
                                    <h4 class="font-medium" style="color: var(--theme-status-info-text)">{{ __('Update Available') }}</h4>
                                    <p class="mt-1 text-sm" style="color: var(--theme-status-info-text)">
                                        {{ __('Your application is :count commits behind', ['count' => $update_info['commits_behind'] ?? 0]) }}
                                    </p>
                                    @if(!empty($update_info['remote_commit']))
                                    <p class="mt-2 text-sm" style="color: var(--theme-status-info-text)">
                                        <strong>{{ __('Latest commit') }}:</strong> 
                                        @if(!empty($update_info['remote_commit_url']))
                                            <a href="{{ $update_info['remote_commit_url'] }}" target="_blank" class="hover:underline font-mono" style="color: var(--theme-primary-600)">{{ $update_info['remote_commit'] }}</a>
                                        @else
                                            <span class="font-mono">{{ $update_info['remote_commit'] }}</span>
                                        @endif
                                        @if(!empty($update_info['latest_message']))
                                            - {{ $update_info['latest_message'] }}
                                        @endif
                                    </p>
                                    @endif
                                    <div class="mt-4">
                                        <button type="button" 
                                            onclick="updateApplication(this)" 
                                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring disabled:opacity-25 transition ease-in-out duration-150"
                                            style="background-color: var(--theme-primary-600)">
                                            <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white hidden" id="update-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <span id="update-text">{{ __('Pull Update') }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="mt-6 border rounded-lg p-4" style="background-color: var(--theme-status-success-bg); border-color: var(--theme-status-success-bg)">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 mr-3" style="color: var(--theme-status-success-text)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p style="color: var(--theme-status-success-text)">{{ __('You are running the latest version.') }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        {{ __('Post-Update Tasks') }}
                    </h3>
                    
                    <p class="text-gray-600 mb-4">
                        {{ __('After pulling updates, run migrations and clear caches to ensure everything works properly.') }}
                    </p>

                    <form action="{{ route('system.perform_update') }}" method="POST" x-data="{ loading: false }" x-on:submit="if(!confirm('{{ __('Run database migrations and clear caches?') }}')) { $event.preventDefault(); return; } loading = true;">
                        @csrf
                        <button type="submit" :disabled="loading" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                            <svg x-show="loading" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="loading ? '{{ __('Processing...') }}' : '{{ __('Run Migrations & Clear Cache') }}'"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateApplication(btn) {
            const spinner = document.getElementById('update-spinner');
            const textSpan = document.getElementById('update-text');
            const originalText = textSpan.innerText;
            
            textSpan.innerText = '{{ __('Updating...') }}';
            spinner.classList.remove('hidden');
            btn.disabled = true;
            
            fetch('{{ route('system.pull_update') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    if (data.needs_migration) {
                        // Refresh page to show post-update tasks
                        setTimeout(() => window.location.reload(), 1500);
                    }
                } else {
                    showToast('Error: ' + data.message, 'error');
                    resetButton();
                }
            })
            .catch(error => {
                console.error(error);
                showToast('{{ __('An error occurred while updating') }}', 'error');
                resetButton();
            });

            function resetButton() {
                textSpan.innerText = originalText;
                spinner.classList.add('hidden');
                btn.disabled = false;
            }
        }
    </script>
</x-app-layout>
