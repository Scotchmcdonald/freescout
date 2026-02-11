<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Record Payment') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(session('success'))
                        <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(isset($errors) && $errors->any())
                        <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4">
                            <ul class="list-disc list-inside text-sm text-red-700">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.billing.payments.store') }}">
                        @csrf
                        
                        <div class="space-y-6">
                            <div>
                                <label for="client_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Client <span class="text-red-600">*</span>
                                </label>
                                <select name="client_id" 
                                        id="client_id" 
                                        dusk="client-select"
                                        required
                                        class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                    <option value="">Select a client</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                            {{ $client->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('client_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                                        Amount ($) <span class="text-red-600">*</span>
                                    </label>
                                    <input type="number" 
                                           name="amount" 
                                           id="amount" 
                                           dusk="amount"
                                           step="0.01" 
                                           min="0.01"
                                           required
                                           value="{{ old('amount') }}"
                                           placeholder="0.00"
                                           class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                    @error('amount')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="payment_date" class="block text-sm font-medium text-gray-700 mb-2">
                                        Payment Date <span class="text-red-600">*</span>
                                    </label>
                                    <input type="date" 
                                           name="payment_date" 
                                           id="payment_date" 
                                           required
                                           value="{{ old('payment_date', date('Y-m-d')) }}"
                                           class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                    @error('payment_date')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label for="payment_type" class="block text-sm font-medium text-gray-700 mb-2">
                                    Payment Type <span class="text-red-600">*</span>
                                </label>
                                <select name="payment_type" 
                                        id="payment_type" 
                                        dusk="payment-type"
                                        required
                                        class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                    <option value="check" {{ old('payment_type') == 'check' ? 'selected' : '' }}>Check</option>
                                    <option value="ach" {{ old('payment_type') == 'ach' ? 'selected' : '' }}>ACH Transfer</option>
                                    <option value="wire" {{ old('payment_type') == 'wire' ? 'selected' : '' }}>Wire Transfer</option>
                                    <option value="credit_card" {{ old('payment_type') == 'credit_card' ? 'selected' : '' }}>Credit Card</option>
                                    <option value="cash" {{ old('payment_type') == 'cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="prepayment" {{ old('payment_type', 'prepayment') == 'prepayment' ? 'selected' : '' }}>Prepayment/Credit</option>
                                    <option value="asset_prepayment" {{ old('payment_type') == 'asset_prepayment' ? 'selected' : '' }}>Asset Prepayment</option>
                                </select>
                                @error('payment_type')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="reference" class="block text-sm font-medium text-gray-700 mb-2">
                                    Reference Number
                                </label>
                                <input type="text" 
                                       name="reference" 
                                       id="reference" 
                                       value="{{ old('reference') }}"
                                       placeholder="Check #, Transaction ID, etc."
                                       class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                @error('reference')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                    Description
                                </label>
                                <input type="text" 
                                       name="description" 
                                       id="description" 
                                       dusk="description"
                                       value="{{ old('description') }}"
                                       placeholder="Purpose of payment"
                                       class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                @error('description')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Credit Expiration -->
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <div class="flex items-center space-x-2 mb-3">
                                    <input type="checkbox" 
                                           id="set_expiration" 
                                           name="set_expiration" 
                                           dusk="set-expiration"
                                           value="1"
                                           {{ old('set_expiration') ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <label for="set_expiration" class="text-sm font-medium text-gray-700">Set Credit Expiration</label>
                                </div>
                                
                                <div id="expiration-fields" class="mt-2" style="display: {{ old('set_expiration') ? 'block' : 'none' }};">
                                    <label for="expiration_days" class="block text-sm font-medium text-gray-700 mb-2">Expires in (days)</label>
                                    <input type="number" 
                                           name="expiration_days" 
                                           id="expiration_days"
                                           dusk="expiration-days"
                                           value="{{ old('expiration_days', 365) }}"
                                           min="1"
                                           placeholder="365"
                                           class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                    <p class="mt-1 text-xs text-gray-500">Credit will expire after this many days from the payment date</p>
                                    @error('expiration_days')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                                    Notes
                                </label>
                                <textarea name="notes" 
                                          id="notes" 
                                          rows="3"
                                          class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-blue-700">
                                            This payment will be recorded as a credit on the client's account and can be applied to open invoices.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-3">
                            <a href="{{ url()->previous() }}" 
                               class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                                Cancel
                            </a>
                            <button type="submit" 
                                    dusk="save-payment-button"
                                    class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                                Record Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkbox = document.getElementById('set_expiration');
        const fields = document.getElementById('expiration-fields');
        
        checkbox.addEventListener('change', function() {
            fields.style.display = this.checked ? 'block' : 'none';
        });
    });
    </script>
</x-app-layout>
