<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl" style="color: var(--theme-text-main)">
            {{ __('Device Assignment Wizard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            {{-- Step 1: Find Asset --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">1. Select Device</h3>
                <form method="GET" action="{{ route('admin.assets.assign') }}" class="flex gap-4">
                    <div class="flex-grow">
                        <label for="search" class="sr-only">Search</label>
                        <input type="text" name="search" id="search" 
                               value="{{ request('search') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" 
                               placeholder="Enter Serial Number or Hostname...">
                    </div>
                    <button type="submit" class="px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Find Device
                    </button>
                </form>
            </div>

            @if(request('search') && !$selectedAsset)
                <div class="bg-red-50 border-l-4 border-red-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                No asset found matching "{{ request('search') }}"
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            @if($selectedAsset)
                {{-- Step 2: Confirm & Assign --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" style="border-top: 4px solid var(--theme-primary-500)">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">2. Assign to User</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 bg-gray-50 p-4 rounded-lg">
                        <div>
                            <span class="block text-xs text-gray-500 uppercase tracking-wide">Device</span>
                            <span class="block text-lg font-bold text-gray-900">{{ $selectedAsset->hostname ?? 'No Hostname' }}</span>
                            <span class="text-sm text-gray-600">{{ $selectedAsset->serial_number }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-gray-500 uppercase tracking-wide">Current Status</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $selectedAsset->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($selectedAsset->status) }}
                            </span>
                            @if($selectedAsset->assigned_user_email)
                                <div class="mt-1 text-sm text-yellow-600">
                                    ⚠️ Currently assigned to {{ $selectedAsset->assigned_user_email }}
                                </div>
                            @else
                                <div class="mt-1 text-sm text-green-600">
                                    ✓ Available for assignment
                                </div>
                            @endif
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.assets.store_assignment') }}">
                        @csrf
                        <input type="hidden" name="asset_id" value="{{ $selectedAsset->id }}">
                        
                        <div class="mb-6">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Assign User (Email Address)</label>
                            <input type="email" name="email" id="email" required
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" 
                                   placeholder="user@company.com"
                                   value="{{ old('email', $selectedAsset->assigned_user_email) }}">
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-end">
                            <a href="{{ route('admin.assets.inventory') }}" class="mr-3 text-sm text-gray-600 hover:text-gray-900 underline">Cancel</a>
                            <button type="submit" class="px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150" style="background-color: var(--theme-primary-600)">
                                Confirm Assignment
                            </button>
                        </div>
                    </form>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
