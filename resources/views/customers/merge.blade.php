<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $customer->getFullName() }} - {{ __('Merge Customer') }}
            </h2>
            @include('customers.profile_menu')
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Sidebar with customer info --}}
                <div class="lg:col-span-1">
                    @include('customers.profile_snippet')
                </div>
                
                {{-- Main content area --}}
                <div class="lg:col-span-2">
                    @include('customers.profile_tabs', ['extra_tab' => __('Merge')])
                    
                    {{-- Flash messages --}}
                    @if(session('success'))
                        <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex">
                                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <p class="ml-3 text-sm text-green-700">{{ session('success') }}</p>
                            </div>
                        </div>
                    @endif
                    
                    @if($errors->any())
                        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex">
                                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                <div class="ml-3">
                                    @foreach($errors->all() as $error)
                                        <p class="text-sm text-red-700">{{ $error }}</p>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-4">{{ __('Merge Customer') }}</h3>
                        
                        <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex">
                                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        <strong>{{ __('Warning:') }}</strong> {{ __('This action cannot be undone. All conversations, emails, and data from the source customer will be merged into the target customer.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <form method="POST" action="{{ route('customers.merge') }}" x-data="mergeForm()">
                            @csrf
                            
                            <input type="hidden" name="source_id" value="{{ $customer->id }}">
                            
                            {{-- Source customer (current) --}}
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ __('Source Customer (will be deleted)') }}
                                </label>
                                <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                                    <div class="font-medium text-gray-900">{{ $customer->getFullName() }}</div>
                                    <div class="text-sm text-gray-600">{{ $customer->getMainEmail() }}</div>
                                    <div class="text-sm text-gray-500 mt-1">
                                        {{ $customer->conversations()->count() }} {{ __('conversations') }}
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Target customer (search) --}}
                            <div class="mb-6">
                                <label for="target_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ __('Merge With (target customer)') }} <span class="text-red-500">*</span>
                                </label>
                                <select name="target_id" 
                                        id="target_id" 
                                        class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                        required>
                                    <option value="">{{ __('Search for a customer by name or email') }}...</option>
                                </select>
                                @error('target_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            {{-- Selected target preview --}}
                            <div x-show="selectedCustomer" 
                                 x-cloak 
                                 class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <div class="text-sm font-medium text-blue-900 mb-2">
                                    {{ __('Target Customer (data will be merged here)') }}
                                </div>
                                <div class="font-medium text-gray-900" x-text="selectedCustomer?.name"></div>
                                <div class="text-sm text-gray-600" x-text="selectedCustomer?.email"></div>
                            </div>
                            
                            {{-- Action buttons --}}
                            <div class="flex items-center justify-end space-x-3">
                                <a href="{{ route('customers.show', $customer) }}" 
                                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                                    {{ __('Cancel') }}
                                </a>
                                <button type="submit" 
                                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition">
                                    {{ __('Merge Customers') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        function mergeForm() {
            return {
                selectedCustomer: null,
                
                init() {
                    // Initialize Select2 for customer search
                    $('#target_id').select2({
                        ajax: {
                            url: '{{ route("customers.ajax") }}',
                            dataType: 'json',
                            delay: 250,
                            data: function (params) {
                                return {
                                    action: 'search',
                                    q: params.term,
                                    _token: '{{ csrf_token() }}'
                                };
                            },
                            processResults: function (data) {
                                return {
                                    results: data.results
                                };
                            },
                            cache: true
                        },
                        minimumInputLength: 2,
                        placeholder: '{{ __("Search for a customer by name or email") }}...'
                    }).on('select2:select', (e) => {
                        this.selectedCustomer = {
                            name: e.params.data.text.split('(')[0].trim(),
                            email: e.params.data.text.match(/\(([^)]+)\)/)?.[1] || ''
                        };
                    });
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
