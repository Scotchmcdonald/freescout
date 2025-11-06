<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Modules') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900">
                            {{ __('Installed Modules') }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ __('Manage your installed modules. Enable or disable modules as needed.') }}
                        </p>
                    </div>

                    @if(count($modules) > 0)
                        <div class="space-y-4">
                            @foreach($modules as $module)
                                <div class="border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition-colors"
                                     x-data="{ processing: false }">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center">
                                                <h4 class="text-lg font-medium text-gray-900">
                                                    {{ $module['name'] }}
                                                </h4>
                                                <span class="ml-3 px-2 py-1 text-xs rounded-full {{ $module['enabled'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                                    {{ $module['enabled'] ? __('Enabled') : __('Disabled') }}
                                                </span>
                                            </div>
                                            
                                            @if($module['description'])
                                                <p class="mt-1 text-sm text-gray-600">
                                                    {{ $module['description'] }}
                                                </p>
                                            @endif

                                            <div class="mt-2 flex items-center space-x-4 text-xs text-gray-500">
                                                <span>{{ __('Alias') }}: <code class="bg-gray-100 px-1 py-0.5 rounded">{{ $module['alias'] }}</code></span>
                                                <span>{{ __('Version') }}: {{ $module['version'] }}</span>
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

            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">
                            {{ __('Module Development') }}
                        </h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>{{ __('Create a new module using:') }}</p>
                            <code class="block mt-2 bg-white px-2 py-1 rounded text-xs">php artisan module:make ModuleName</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
