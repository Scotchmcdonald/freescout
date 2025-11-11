{{-- Change Customer Dialog - AJAX dialog to change conversation customer --}}
<div class="change-customer-dialog p-6">
    <form method="POST" 
          action="{{ route('conversations.change_customer', $conversation->id ?? 0) }}"
          x-data="{ searching: false, creating: false }">
        @csrf
        
        <div class="space-y-6">
            {{-- Current Customer Display --}}
            @if(isset($conversation) && $conversation->customer)
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-sm text-gray-600 mb-1">{{ __('Current Customer') }}</div>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-gray-400 flex items-center justify-center text-white font-semibold">
                            {{ substr($conversation->customer->first_name, 0, 1) }}{{ substr($conversation->customer->last_name ?? '', 0, 1) }}
                        </div>
                        <div>
                            <div class="font-medium text-gray-900">{{ $conversation->customer->getFullName(true) }}</div>
                            <div class="text-sm text-gray-500">{{ $conversation->customer_email }}</div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Customer Search --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('Search for Customer') }}
                </label>
                <input type="text" 
                       name="customer_search"
                       placeholder="{{ __('Enter name or email address...') }}"
                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                       @input.debounce.500ms="searching = true; searchCustomers($event.target.value)"
                       autocomplete="off">
                
                {{-- Search Results --}}
                <div id="customer-search-results" class="mt-2 space-y-2 hidden">
                    {{-- Results will be populated via AJAX --}}
                </div>
            </div>

            {{-- Hidden Selected Customer ID --}}
            <input type="hidden" name="customer_id" id="selected-customer-id" value="">

            {{-- OR Divider --}}
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white text-gray-500">{{ __('OR') }}</span>
                </div>
            </div>

            {{-- Create New Customer --}}
            <div>
                <button type="button"
                        @click="creating = !creating"
                        class="text-sm text-blue-600 hover:underline">
                    {{ __('+ Create New Customer') }}
                </button>

                <div x-show="creating" 
                     class="mt-4 space-y-4 p-4 bg-gray-50 rounded-lg"
                     style="display: none;">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('First Name') }}
                            </label>
                            <input type="text" 
                                   name="new_customer_first_name"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('Last Name') }}
                            </label>
                            <input type="text" 
                                   name="new_customer_last_name"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('Email Address') }} <span class="text-red-600">*</span>
                        </label>
                        <input type="email" 
                               name="new_customer_email"
                               required
                               class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                </div>
            </div>

            {{-- Validation Message --}}
            <div id="validation-message" class="hidden">
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                    <div class="flex">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700" id="validation-message-text"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="mt-6 flex items-center justify-end gap-3">
            <button type="button" 
                    onclick="window.parent.closeModal()"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 transition">
                {{ __('Cancel') }}
            </button>
            <button type="submit" 
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition">
                {{ __('Change Customer') }}
            </button>
        </div>
    </form>
</div>

<script>
    function searchCustomers(query) {
        if (query.length < 2) {
            document.getElementById('customer-search-results').classList.add('hidden');
            return;
        }

        fetch('{{ route('customers.search') }}?q=' + encodeURIComponent(query), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            displaySearchResults(data.customers || []);
        })
        .catch(error => {
            console.error('Search error:', error);
        });
    }

    function displaySearchResults(customers) {
        const resultsContainer = document.getElementById('customer-search-results');
        
        if (customers.length === 0) {
            resultsContainer.innerHTML = '<div class="text-sm text-gray-500 p-2">{{ __('No customers found') }}</div>';
            resultsContainer.classList.remove('hidden');
            return;
        }

        resultsContainer.innerHTML = customers.map(customer => `
            <button type="button" 
                    onclick="selectCustomer(${customer.id}, '${customer.full_name}', '${customer.email}')"
                    class="w-full text-left p-3 border border-gray-200 rounded hover:bg-gray-50 transition">
                <div class="font-medium text-gray-900">${customer.full_name}</div>
                <div class="text-sm text-gray-500">${customer.email}</div>
            </button>
        `).join('');
        
        resultsContainer.classList.remove('hidden');
    }

    function selectCustomer(id, name, email) {
        document.getElementById('selected-customer-id').value = id;
        document.querySelector('[name="customer_search"]').value = name + ' (' + email + ')';
        document.getElementById('customer-search-results').classList.add('hidden');
    }
</script>
