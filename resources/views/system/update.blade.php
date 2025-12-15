<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('System Update') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('status'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('status') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
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
                                <code class="bg-gray-100 px-2 py-1 rounded text-sm">{{ $update_info['current_commit'] }}</code>
                            </div>
                            <div class="flex items-center justify-between py-3 border-b">
                                <span class="text-gray-700">{{ __('Branch') }}:</span>
                                <span class="font-mono text-gray-900">{{ $update_info['branch'] }}</span>
                            </div>
                        @endif
                    </div>

                    @if($update_available && !empty($update_info))
                        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex items-start">
                                <svg class="h-5 w-5 text-blue-600 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div class="flex-1">
                                    <h4 class="text-blue-900 font-medium">{{ __('Update Available') }}</h4>
                                    <p class="mt-1 text-sm text-blue-700">
                                        {{ __('Your application is :count commits behind', ['count' => $update_info['commits_behind'] ?? 0]) }}
                                    </p>
                                    <p class="mt-2 text-sm text-blue-600">
                                        <strong>{{ __('Latest commit') }}:</strong> {{ $update_info['remote_commit'] }} - {{ $update_info['latest_message'] }}
                                    </p>
                                    <div class="mt-4">
                                        <button type="button" 
                                            onclick="updateApplication()" 
                                            id="update-btn"
                                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                                            {{ __('Pull Update') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="mt-6 bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-green-800">{{ __('You are running the latest version.') }}</p>
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

                    <form action="{{ route('system.perform_update') }}" method="POST" onsubmit="return confirm('{{ __('Run database migrations and clear caches?') }}')">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                            {{ __('Run Migrations & Clear Cache') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateApplication() {
            const btn = document.getElementById('update-btn');
            const originalText = btn.innerText;
            btn.innerText = '{{ __('Updating...') }}';
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
                    alert(data.message);
                    if (data.needs_migration) {
                        // Refresh page to show post-update tasks
                        window.location.reload();
                    }
                } else {
                    alert('Error: ' + data.message);
                    btn.innerText = originalText;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error(error);
                alert('{{ __('An error occurred while updating') }}');
                btn.innerText = originalText;
                btn.disabled = false;
            });
        }
    </script>
</x-app-layout>
