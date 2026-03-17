<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-neutral-800 leading-tight">
                {{ $customer->getFullName() }} - {{ __('Merge Customer') }}
            </h2>
            <x-customer-profile-menu :customer="$customer" />
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Sidebar with customer info --}}
                <div class="lg:col-span-1">
                    <x-customer-profile-snippet :customer="$customer" />
                </div>
                
                {{-- Main content area --}}
                <div class="lg:col-span-2">
                    <x-customer-profile-tabs :customer="$customer" :extra-tab="__('Merge')" />
                    
                    {{-- Flash messages --}}
                    @if(session('success'))
                        <div class="mb-6 border rounded-lg p-4" style="background-color: var(--theme-status-success-bg); border-color: var(--theme-status-success-bg)">
                            <div class="flex">
                                <svg class="h-5 w-5" style="color: var(--theme-status-success-text)" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <p class="ml-3 text-sm" style="color: var(--theme-status-success-text)">{{ session('success') }}</p>
                            </div>
                        </div>
                    @endif
                    
                    @if($errors->any())
                        <div class="mb-6 border rounded-lg p-4" style="background-color: var(--theme-status-error-bg); border-color: var(--theme-status-error-bg)">
                            <div class="flex">
                                <svg class="h-5 w-5" style="color: var(--theme-status-error-text)" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                <div class="ml-3">
                                    @foreach($errors->all() as $error)
                                        <p class="text-sm" style="color: var(--theme-status-error-text)">{{ $error }}</p>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-4">{{ __('Merge Customer') }}</h3>
                        
                        <div class="mb-6 p-4 border rounded-lg" style="background-color: var(--theme-status-warning-bg); border-color: var(--theme-status-warning-bg)">
                            <div class="flex">
                                <svg class="h-5 w-5" style="color: var(--theme-status-warning-text)" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <div class="ml-3">
                                    <p class="text-sm" style="color: var(--theme-status-warning-text)">
                                        <strong>{{ __('Warning:') }}</strong> {{ __('This action cannot be undone. All conversations, emails, and data from the source customer will be merged into the target customer.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <form method="POST" action="{{ route('customers.merge') }}" x-data="customerMerge('{{ route('customers.ajax') }}', '{{ csrf_token() }}')">
                            @csrf
                            
                            <input type="hidden" name="source_id" value="{{ $customer->id }}">
                            
                            {{-- Source customer (current) --}}
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-neutral-700 mb-2">
                                    {{ __('Source Customer (will be deleted)') }}
                                </label>
                                <div class="p-4 border rounded-lg" style="background-color: var(--theme-bg-hover); border-color: var(--theme-border)">
                                    <div class="font-medium text-neutral-900">{{ $customer->getFullName() }}</div>
                                    <div class="text-sm text-neutral-600">{{ $customer->getMainEmail() }}</div>
                                    <div class="text-sm text-neutral-500 mt-1">
                                        {{ $customer->conversations()->count() }} {{ __('conversations') }}
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Target customer (search) --}}
                            <div class="mb-6">
                                <label for="target_id" class="block text-sm font-medium text-neutral-700 mb-2">
                                    {{ __('Merge With (target customer)') }} <span class="text-danger-500">*</span>
                                </label>
                                <select name="target_id" 
                                        id="target_id" 
                                        class="w-full rounded-lg border-neutral-300 focus:border-primary-500 focus:ring-primary-500"
                                        required>
                                    <option value="">{{ __('Search for a customer by name or email') }}...</option>
                                </select>
                                @error('target_id')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            {{-- Selected target preview --}}
                            <div x-show="selectedCustomer" 
                                 x-cloak 
                                 class="mb-6 p-4 border rounded-lg"
                                 style="background-color: var(--theme-status-info-bg); border-color: var(--theme-status-info-bg)">
                                <div class="text-sm font-medium mb-2" style="color: var(--theme-status-info-text)">
                                    {{ __('Target Customer (data will be merged here)') }}
                                </div>
                                <div class="font-medium text-neutral-900" x-text="selectedCustomer?.name"></div>
                                <div class="text-sm text-neutral-600" x-text="selectedCustomer?.email"></div>
                            </div>
                            
                            {{-- Action buttons --}}
                            <div class="flex items-center justify-end space-x-3">
                                <a href="{{ route('customers.show', $customer) }}" 
                                   class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-lg hover:bg-neutral-50 transition">
                                    {{ __('Cancel') }}
                                </a>
                                <button type="submit" 
                                        class="px-4 py-2 text-sm font-medium text-white border border-transparent rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 transition"
                                        style="background-color: var(--theme-status-error-text)">
                                    {{ __('Merge Customers') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
