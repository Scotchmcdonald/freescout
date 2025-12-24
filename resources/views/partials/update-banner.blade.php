@if(auth()->check() && auth()->user()->isAdmin())
    <div id="update-banner" class="hidden">
        <div class="text-white shadow-lg" style="background-color: var(--theme-primary-600)">
            <div class="max-w-7xl mx-auto py-3 px-3 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between flex-wrap">
                    <div class="w-0 flex-1 flex items-center">
                        <span class="flex p-2 rounded-lg" style="background-color: var(--theme-primary-700)">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                        </span>
                        <p class="ml-3 font-medium">
                            <span id="update-message">{{ __('A new application update is available') }}</span>
                        </p>
                    </div>
                    <div class="order-3 mt-2 flex-shrink-0 w-full sm:order-2 sm:mt-0 sm:w-auto">
                        <a href="{{ route('system.update') }}" class="flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium bg-white transition" style="color: var(--theme-primary-600)">
                            {{ __('Update Now') }}
                        </a>
                    </div>
                    <div class="order-2 flex-shrink-0 sm:order-3 sm:ml-3">
                        <button type="button" onclick="dismissUpdateBanner()" class="-mr-1 flex p-2 rounded-md focus:outline-none focus:ring-2 focus:ring-white sm:-mr-2" style="background-color: transparent">
                            <span class="sr-only">{{ __('Dismiss') }}</span>
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Check for updates on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Don't show banner on update page itself
            if (window.location.pathname.includes('/system/update')) {
                return;
            }
            
            // Check if banner was dismissed (session storage)
            if (sessionStorage.getItem('update_banner_dismissed')) {
                return;
            }
            
            fetch('{{ route('system.check_update_banner') }}', {
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.has_update && data.update_info) {
                    const commitInfo = data.update_info.remote_commit ? ` (${data.update_info.remote_commit})` : '';
                    const message = `{{ __('Application update available') }}: ${data.update_info.commits_behind || 0} {{ __('commits behind') }}${commitInfo}`;
                    document.getElementById('update-message').textContent = message;
                    document.getElementById('update-banner').classList.remove('hidden');
                }
            })
            .catch(error => console.error('Failed to check for updates:', error));
        });
        
        function dismissUpdateBanner() {
            document.getElementById('update-banner').classList.add('hidden');
            sessionStorage.setItem('update_banner_dismissed', 'true');
        }
    </script>
@endif
