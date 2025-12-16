<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Modules') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(!empty($flashes))
                @foreach($flashes as $flash)
                    <div class="mb-4 {{ $flash['type'] == 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700' }} border px-4 py-3 rounded">
                        @if(!empty($flash['unescaped']))
                            {!! $flash['text'] !!}
                        @else
                            {{ $flash['text'] }}
                        @endif
                    </div>
                @endforeach
            @endif

            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <style>
                pre.margin-top {
                    margin-top: 1rem;
                    background-color: #f3f4f6;
                    padding: 0.5rem;
                    border-radius: 0.375rem;
                    font-size: 0.875rem;
                    overflow-x: auto;
                }
            </style>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">
                                {{ __('Install Module from GitHub') }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ __('Install a module directly from a GitHub repository with advanced options.') }}
                            </p>
                        </div>
                        <div class="flex space-x-3">
                            <a href="{{ route('modules.activity') }}" class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg transition-colors inline-flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                {{ __('Activity Log') }}
                            </a>
                            <a href="{{ route('modules.install.form') }}" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors inline-flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                {{ __('Install from GitHub') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="mb-6 flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">
                                {{ __('Installed Modules') }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ __('Manage your installed modules. Enable or disable modules as needed.') }}
                            </p>
                        </div>
                        <button type="button" id="check-updates-btn" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                            {{ __('Check for Updates') }}
                        </button>
                    </div>

                    @if(count($modules) > 0)
                        <div class="space-y-4">
                            @foreach($modules as $module)
                                <div class="border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition-colors module-item"
                                     data-alias="{{ $module['alias'] }}"
                                     x-data="{ processing: false, updating: false, updated: false }">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center">
                                                <h4 class="text-lg font-medium text-gray-900">
                                                    {{ $module['name'] }}
                                                </h4>
                                                <span class="ml-3 px-2 py-1 text-xs rounded-full {{ $module['enabled'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                                    {{ $module['enabled'] ? __('Enabled') : __('Disabled') }}
                                                </span>
                                                <span class="update-status-badge ml-2 px-2 py-1 text-xs rounded-full hidden"></span>
                                            </div>
                                            
                                            @if($module['description'])
                                                <p class="mt-1 text-sm text-gray-600">
                                                    {{ $module['description'] }}
                                                </p>
                                            @endif

                                            <div class="mt-2 flex items-center space-x-4 text-xs text-gray-500">
                                                <span>{{ __('Alias') }}: <code class="bg-gray-100 px-1 py-0.5 rounded">{{ $module['alias'] }}</code></span>
                                                <span>{{ __('Version') }}: {{ $module['version'] }}</span>
                                                @if($module['commit'])
                                                    <span>{{ __('Commit') }}: 
                                                        <span class="module-commit-wrapper">
                                                            @if($module['commit_url'])
                                                                <a href="{{ $module['commit_url'] }}" target="_blank" class="text-blue-600 hover:text-blue-800 hover:underline">
                                                                    <code class="bg-gray-100 px-1 py-0.5 rounded font-mono">{{ $module['commit'] }}</code>
                                                                </a>
                                                            @else
                                                                <code class="bg-gray-100 px-1 py-0.5 rounded font-mono">{{ $module['commit'] }}</code>
                                                            @endif
                                                        </span>
                                                    </span>
                                                @endif
                                                <span class="update-info hidden text-indigo-600 font-bold ml-2"></span>
                                                <button class="update-btn hidden ml-2 inline-flex items-center px-2 py-1 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring disabled:opacity-25 transition ease-in-out duration-150"
                                                    @click="
                                                        updating = true;
                                                        const statusBadge = $el.closest('.module-item').querySelector('.update-status-badge');
                                                        const updateInfo = $el.closest('.module-item').querySelector('.update-info');
                                                        const updateBtn = $el;
                                                        
                                                        if (statusBadge) {
                                                            statusBadge.innerText = '{{ __('Updating...') }}';
                                                            statusBadge.classList.remove('hidden', 'bg-yellow-100', 'text-yellow-800', 'bg-green-100', 'text-green-800');
                                                            statusBadge.classList.add('bg-blue-100', 'text-blue-800');
                                                        }
                                                        
                                                        fetch('{{ route('modules.ajax') }}', {
                                                            method: 'POST',
                                                            headers: {
                                                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                                'Content-Type': 'application/json',
                                                                'Accept': 'application/json'
                                                            },
                                                            body: JSON.stringify({
                                                                action: 'update_module',
                                                                alias: '{{ $module['alias'] }}'
                                                            })
                                                        })
                                                        .then(response => response.json())
                                                        .then(data => {
                                                            if (data.success) {
                                                                // Update the commit hash display if provided
                                                                if (data.new_commit) {
                                                                    const commitWrapper = $el.closest('.module-item').querySelector('.module-commit-wrapper');
                                                                    if (commitWrapper && data.new_commit_url) {
                                                                        const link = document.createElement('a');
                                                                        link.href = data.new_commit_url;
                                                                        link.target = '_blank';
                                                                        link.className = 'text-blue-600 hover:text-blue-800 hover:underline';
                                                                        const code = document.createElement('code');
                                                                        code.className = 'bg-gray-100 px-1 py-0.5 rounded font-mono';
                                                                        code.textContent = data.new_commit;
                                                                        link.appendChild(code);
                                                                        commitWrapper.innerHTML = '';
                                                                        commitWrapper.appendChild(link);
                                                                    } else if (commitWrapper) {
                                                                        const code = document.createElement('code');
                                                                        code.className = 'bg-gray-100 px-1 py-0.5 rounded font-mono';
                                                                        code.textContent = data.new_commit;
                                                                        commitWrapper.innerHTML = '';
                                                                        commitWrapper.appendChild(code);
                                                                    }
                                                                }
                                                                
                                                                updated = true;
                                                                updating = false;
                                                                
                                                                if (statusBadge) {
                                                                    statusBadge.innerText = '{{ __('Updated!') }}';
                                                                    statusBadge.classList.remove('bg-blue-100', 'text-blue-800');
                                                                    statusBadge.classList.add('bg-green-100', 'text-green-800');
                                                                    
                                                                    // Fade out update info and button after 2 seconds
                                                                    setTimeout(() => {
                                                                        if (updateInfo) {
                                                                            updateInfo.style.transition = 'opacity 0.5s';
                                                                            updateInfo.style.opacity = '0';
                                                                            setTimeout(() => updateInfo.classList.add('hidden'), 500);
                                                                        }
                                                                        if (updateBtn) {
                                                                            updateBtn.style.transition = 'opacity 0.5s';
                                                                            updateBtn.style.opacity = '0';
                                                                            setTimeout(() => updateBtn.classList.add('hidden'), 500);
                                                                        }
                                                                        // Hide status badge after 3 seconds total
                                                                        setTimeout(() => {
                                                                            statusBadge.style.transition = 'opacity 0.5s';
                                                                            statusBadge.style.opacity = '0';
                                                                            setTimeout(() => statusBadge.classList.add('hidden'), 500);
                                                                        }, 1000);
                                                                    }, 2000);
                                                                }
                                                            } else {
                                                                if (statusBadge) {
                                                                    statusBadge.innerText = '{{ __('Update Failed') }}';
                                                                    statusBadge.classList.remove('bg-blue-100', 'text-blue-800');
                                                                    statusBadge.classList.add('bg-red-100', 'text-red-800');
                                                                    setTimeout(() => statusBadge.classList.add('hidden'), 3000);
                                                                }
                                                                alert(data.message);
                                                                updating = false;
                                                            }
                                                        })
                                                        .catch(error => {
                                                            if (statusBadge) {
                                                                statusBadge.innerText = '{{ __('Update Failed') }}';
                                                                statusBadge.classList.remove('bg-blue-100', 'text-blue-800');
                                                                statusBadge.classList.add('bg-red-100', 'text-red-800');
                                                                setTimeout(() => statusBadge.classList.add('hidden'), 3000);
                                                            }
                                                            alert('{{ __('An error occurred') }}');
                                                            updating = false;
                                                        });
                                                    "
                                                    :disabled="updating || updated"
                                                    :class="{ 'bg-green-600 hover:bg-green-500 focus:border-green-700 focus:ring-green-300': updated, 'bg-indigo-600 hover:bg-indigo-500 focus:border-indigo-700 focus:ring-indigo-300': !updated }">
                                                    <span x-show="!updating && !updated">{{ __('Update') }}</span>
                                                    <span x-show="updating" class="flex items-center">
                                                        <svg class="animate-spin -ml-1 mr-2 h-3 w-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        {{ __('Updating...') }}
                                                    </span>
                                                    <span x-show="updated" class="flex items-center">
                                                        <svg class="-ml-1 mr-2 h-3 w-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                        {{ __('Updated') }}
                                                    </span>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="ml-4 flex-shrink-0 flex items-center space-x-2">
                                            @if($module['enabled'])
                                                <button 
                                                    @click="
                                                        processing = true;
                                                        fetch('{{ route('modules.disable', $module['alias']) }}', {
                                                            method: 'POST',
                                                            headers: {
                                                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
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
                                                            alert('{{ __('An error occurred') }}');
                                                            processing = false;
                                                        });
                                                    "
                                                    :disabled="processing"
                                                    class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-md disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                                    <span x-show="!processing">{{ __('Disable') }}</span>
                                                    <span x-show="processing">{{ __('Processing...') }}</span>
                                                </button>
                                            @else
                                                <button 
                                                    @click="
                                                        processing = true;
                                                        fetch('{{ route('modules.enable', $module['alias']) }}', {
                                                            method: 'POST',
                                                            headers: {
                                                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
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
                                                            alert('{{ __('An error occurred') }}');
                                                            processing = false;
                                                        });
                                                    "
                                                    :disabled="processing"
                                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                                    <span x-show="!processing">{{ __('Enable') }}</span>
                                                    <span x-show="processing">{{ __('Processing...') }}</span>
                                                </button>
                                            @endif

                                            @if(File::isDirectory($module['path'] . '/.git'))
                                                <button 
                                                    x-data="{ resetting: false }"
                                                    @click="
                                                        if (confirm('This will DELETE the module and re-clone from GitHub. All local changes will be lost. Continue?')) {
                                                            resetting = true;
                                                            const btn = $el;
                                                            fetch('{{ route('modules.ajax') }}', {
                                                                method: 'POST',
                                                                headers: {
                                                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                                    'Content-Type': 'application/json',
                                                                    'Accept': 'application/json'
                                                                },
                                                                body: JSON.stringify({
                                                                    action: 'reset_module',
                                                                    alias: '{{ $module['alias'] }}'
                                                                })
                                                            })
                                                            .then(response => response.json())
                                                            .then(data => {
                                                                if (data.success) {
                                                                    if (data.new_commit) {
                                                                        const commitWrapper = btn.closest('.module-item').querySelector('.module-commit-wrapper');
                                                                        if (commitWrapper && data.new_commit_url) {
                                                                            const link = document.createElement('a');
                                                                            link.href = data.new_commit_url;
                                                                            link.target = '_blank';
                                                                            link.className = 'text-blue-600 hover:text-blue-800 hover:underline';
                                                                            const code = document.createElement('code');
                                                                            code.className = 'bg-gray-100 px-1 py-0.5 rounded font-mono';
                                                                            code.textContent = data.new_commit;
                                                                            link.appendChild(code);
                                                                            commitWrapper.innerHTML = '';
                                                                            commitWrapper.appendChild(link);
                                                                        } else if (commitWrapper) {
                                                                            const code = document.createElement('code');
                                                                            code.className = 'bg-gray-100 px-1 py-0.5 rounded font-mono';
                                                                            code.textContent = data.new_commit;
                                                                            commitWrapper.innerHTML = '';
                                                                            commitWrapper.appendChild(code);
                                                                        }
                                                                    }
                                                                    alert(data.message);
                                                                    window.location.reload();
                                                                } else {
                                                                    alert(data.message);
                                                                    resetting = false;
                                                                }
                                                            })
                                                            .catch(error => {
                                                                alert('An error occurred');
                                                                resetting = false;
                                                            });
                                                        }
                                                    "
                                                    :disabled="resetting"
                                                    class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium rounded-md disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                                    <span x-show="!resetting">{{ __('Hard Refresh') }}</span>
                                                    <span x-show="resetting">{{ __('Refreshing...') }}</span>
                                                </button>
                                            @endif

                                            <button 
                                                @click="
                                                    if (confirm('{{ __('Are you sure you want to delete this module?') }}')) {
                                                        processing = true;
                                                        fetch('{{ route('modules.delete', $module['alias']) }}', {
                                                            method: 'DELETE',
                                                            headers: {
                                                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
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
                                                            alert('{{ __('An error occurred') }}');
                                                            processing = false;
                                                        });
                                                    }
                                                "
                                                :disabled="processing"
                                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                                <span x-show="!processing">{{ __('Delete') }}</span>
                                                <span x-show="processing">{{ __('Deleting...') }}</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('No modules installed') }}</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ __('Install modules by placing them in the Modules directory.') }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6" x-data="{ open: false }">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="mb-6 flex justify-between items-center cursor-pointer" @click="open = !open">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">
                                {{ __('Official Freescout Module Roster') }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ __('Browse and install modules from the repository.') }}
                            </p>
                        </div>
                        <svg class="w-6 h-6 transform transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>

                    <div x-show="open" x-transition>
                        @if(isset($remoteModules) && count($remoteModules) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($remoteModules as $module)
                                <div class="border border-gray-200 rounded-lg p-4 flex flex-col h-full hover:border-gray-300 transition-colors">
                                    <div class="flex items-center mb-4">
                                        @if(!empty($module['icon']))
                                            <img src="{{ $module['icon'] }}" alt="{{ $module['name'] }}" class="w-10 h-10 mr-3">
                                        @else
                                            <div class="w-10 h-10 mr-3 bg-gray-100 rounded-full flex items-center justify-center text-gray-400">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                            </div>
                                        @endif
                                        <div>
                                            <h4 class="text-lg font-medium text-gray-900">{{ $module['name'] }}</h4>
                                            <span class="text-xs text-gray-500">{{ $module['version'] ?? '' }}</span>
                                        </div>
                                    </div>
                                    
                                    <p class="text-sm text-gray-600 flex-grow mb-4">
                                        {{ Str::limit($module['details'] ?? $module['description'] ?? '', 100) }}
                                    </p>

                                    <div class="mt-auto pt-4 border-t border-gray-100 flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-900">
                                            {{ $module['price'] ?? 'Free' }}
                                        </span>
                                        
                                        {{-- Check if already installed --}}
                                        @php
                                            $isInstalled = collect($modules)->contains('alias', $module['alias'] ?? Str::kebab($module['name']));
                                        @endphp

                                        @if($isInstalled)
                                            <span class="px-3 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                                                {{ __('Installed') }}
                                            </span>
                                        @else
                                            <form action="{{ route('modules.install') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="alias" value="{{ $module['alias'] ?? '' }}">
                                                <button type="submit" class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition-colors">
                                                    {{ __('Install') }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12 text-gray-500">
                            {{ __('No modules available at the moment.') }}
                        </div>
                    @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkUpdatesBtn = document.getElementById('check-updates-btn');
            let hideTimeouts = []; // Track timeouts for cleanup
            let isCheckingUpdates = false; // Global flag to prevent concurrent requests
            
            // Function to check for updates
            function checkForUpdates(isAutoCheck = false) {
                // Prevent multiple simultaneous requests
                if (isCheckingUpdates) {
                    return;
                }
                
                isCheckingUpdates = true;
                
                // Clear any existing auto-hide timeouts
                hideTimeouts.forEach(timeout => clearTimeout(timeout));
                hideTimeouts = [];
                
                const btn = checkUpdatesBtn;
                const originalText = btn ? btn.innerText : '';
                
                if (btn && !isAutoCheck) {
                    btn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> {{ __('Checking...') }}';
                    btn.disabled = true;
                }
                
                // Reset all update indicators and badges
                document.querySelectorAll('.update-info').forEach(el => el.classList.add('hidden'));
                document.querySelectorAll('.update-btn').forEach(el => el.classList.add('hidden'));
                
                // Show "Checking..." on all modules immediately (for both auto-check and manual)
                document.querySelectorAll('.module-item').forEach(item => {
                    const badge = item.querySelector('.update-status-badge');
                    if (badge) {
                        badge.innerText = '{{ __('Checking...') }}';
                        badge.classList.remove('hidden', 'bg-green-100', 'text-green-800', 'bg-yellow-100', 'text-yellow-800', 'bg-blue-100', 'text-blue-800');
                        badge.classList.add('bg-blue-100', 'text-blue-800');
                        badge.classList.remove('hidden');
                    }
                });
                
                fetch('{{ route('modules.ajax') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'check_updates'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const updates = data.updates;
                        let count = 0;
                        
                        // After a brief delay, update with results
                        setTimeout(() => {
                            document.querySelectorAll('.module-item').forEach(item => {
                                const alias = item.dataset.alias;
                                const badge = item.querySelector('.update-status-badge');
                                
                                if (badge) {
                                    // Clear existing classes
                                    badge.classList.remove('bg-blue-100', 'text-blue-800', 'bg-green-100', 'text-green-800', 'bg-yellow-100', 'text-yellow-800');
                                    
                                    if (updates[alias]) {
                                        // Has update
                                        const infoSpan = item.querySelector('.update-info');
                                        const updateBtn = item.querySelector('.update-btn');
                                        
                                        if (infoSpan) {
                                            let msg = updates[alias].commits_behind 
                                                ? updates[alias].commits_behind + ' {{ __('commits behind') }}'
                                                : '{{ __('New version:') }} ' + updates[alias].available;
                                            
                                            // Add commit link if available
                                            if (updates[alias].remote_commit_url && updates[alias].remote_commit) {
                                                msg += ' → <a href="' + updates[alias].remote_commit_url + '" target="_blank" class="text-blue-600 hover:text-blue-800 hover:underline font-mono">' + updates[alias].remote_commit + '</a>';
                                            }
                                            
                                            infoSpan.innerHTML = msg;
                                            infoSpan.classList.remove('hidden');
                                        }
                                        
                                        if (updateBtn) {
                                            updateBtn.classList.remove('hidden');
                                        }
                                        
                                        badge.innerText = '{{ __('Update Available') }}';
                                        badge.classList.remove('hidden');
                                        badge.classList.add('bg-yellow-100', 'text-yellow-800');
                                        count++;
                                    } else {
                                        // Show "Up to Date" for all checks (both auto and manual)
                                        badge.innerText = '{{ __('Up to Date') }}';
                                        badge.classList.remove('hidden');
                                        badge.classList.add('bg-green-100', 'text-green-800');
                                        
                                        // Auto-hide "Up to Date" after 3 seconds
                                        const timeout = setTimeout(() => {
                                            badge.classList.add('hidden');
                                        }, 3000);
                                        hideTimeouts.push(timeout);
                                    }
                                }
                            });
                            
                            // Re-enable button after showing results
                            if (btn && !isAutoCheck) {
                                btn.innerText = originalText;
                                btn.disabled = false;
                            }
                            
                            isCheckingUpdates = false;
                        }, 500);
                    } else {
                        if (!isAutoCheck) {
                            alert(data.message || '{{ __('Failed to check for updates') }}');
                        }
                        if (btn && !isAutoCheck) {
                            btn.innerText = originalText;
                            btn.disabled = false;
                        }
                        
                        // Hide all badges on error
                        document.querySelectorAll('.update-status-badge').forEach(el => {
                            el.classList.add('hidden');
                        });
                        
                        isCheckingUpdates = false;
                    }
                })
                .catch(error => {
                    console.error(error);
                    if (!isAutoCheck) {
                        alert('{{ __('An error occurred while checking for updates') }}');
                    }
                    if (btn && !isAutoCheck) {
                        btn.innerText = originalText;
                        btn.disabled = false;
                    }
                    
                    // Hide all badges on error
                    document.querySelectorAll('.update-status-badge').forEach(el => {
                        el.classList.add('hidden');
                    });
                    
                    isCheckingUpdates = false;
                });
            }
            
            // Attach click handler to button
            if (checkUpdatesBtn) {
                // Remove any existing event listeners by cloning
                const newBtn = checkUpdatesBtn.cloneNode(true);
                checkUpdatesBtn.parentNode.replaceChild(newBtn, checkUpdatesBtn);
                
                // Attach new click handler
                newBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    checkForUpdates(false);
                });
            }
            
            // Automatically check for updates on page load
            setTimeout(() => {
                checkForUpdates(true);
            }, 500);
        });
    </script>
</x-app-layout>
