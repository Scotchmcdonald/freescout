<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl" style="color: var(--theme-text-main)">
            {{ __('Billing Template Architect') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="billingWizard()">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            
            {{-- Progress Stepper --}}
            <div class="mb-8">
                <div class="flex items-center justify-between relative">
                    <div class="absolute left-0 top-1/2 transform -translate-y-1/2 w-full h-1 bg-gray-200 -z-10"></div>
                    
                    {{-- Step 1 --}}
                    <div class="flex flex-col items-center bg-white px-2">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm transition-colors duration-200"
                             :class="step >= 1 ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-500'">
                            1
                        </div>
                        <span class="text-xs mt-1 font-medium text-gray-600">Basics</span>
                    </div>

                    {{-- Step 2 --}}
                    <div class="flex flex-col items-center bg-white px-2">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm transition-colors duration-200"
                             :class="step >= 2 ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-500'">
                            2
                        </div>
                        <span class="text-xs mt-1 font-medium text-gray-600">Product</span>
                    </div>

                    {{-- Step 3 --}}
                    <div class="flex flex-col items-center bg-white px-2">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm transition-colors duration-200"
                             :class="step >= 3 ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-500'">
                            3
                        </div>
                        <span class="text-xs mt-1 font-medium text-gray-600">Config</span>
                    </div>

                    {{-- Step 4 --}}
                    <div class="flex flex-col items-center bg-white px-2">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm transition-colors duration-200"
                             :class="step >= 4 ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-500'">
                            4
                        </div>
                        <span class="text-xs mt-1 font-medium text-gray-600">Review</span>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.billing.templates.store') }}" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @csrf
                
                {{-- STEP 1: Basic Info --}}
                <div x-show="step === 1" class="p-6 space-y-6">
                    <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Client & Template Details</h3>
                    
                    <div>
                        <label for="client_id" class="block text-sm font-medium text-gray-700">Client</label>
                        <select id="client_id" name="client_id" x-model="formData.client_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">Select a Client</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Template Name</label>
                        <input type="text" name="name" id="name" x-model="formData.name" placeholder="e.g. Monthly Hardware Payment" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>

                    <div>
                        <label for="billing_cycle" class="block text-sm font-medium text-gray-700">Billing Cycle</label>
                        <select id="billing_cycle" name="billing_cycle" x-model="formData.billing_cycle" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                </div>

                {{-- STEP 2: Product Selection --}}
                <div x-show="step === 2" class="p-6 space-y-6" style="display: none;">
                    <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Select Product Type</h3>
                    
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <!-- Silver Plan -->
                        <div class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-gray-400 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                            <div class="flex-shrink-0">
                                <input type="radio" name="product_type" value="silver_plan" x-model="formData.product_type" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="absolute inset-0" aria-hidden="true"></span>
                                <p class="text-sm font-medium text-gray-900">Silver Plan</p>
                                <p class="text-xs text-gray-500 truncate">Managed Services (Base + User)</p>
                            </div>
                        </div>

                        <!-- Rent To Own -->
                        <div class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-gray-400 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                            <div class="flex-shrink-0">
                                <input type="radio" name="product_type" value="rent_to_own" x-model="formData.product_type" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="absolute inset-0" aria-hidden="true"></span>
                                <p class="text-sm font-medium text-gray-900">Rent-to-Own</p>
                                <p class="text-xs text-gray-500 truncate">Hardware Financing</p>
                            </div>
                        </div>

                        <!-- Gold Plan (Placeholder) -->
                         <div class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-gray-400 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500 opacity-50 cursor-not-allowed">
                            <div class="flex-shrink-0">
                                <input type="radio" name="product_type" value="gold_plan" x-model="formData.product_type" disabled class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="absolute inset-0" aria-hidden="true"></span>
                                <p class="text-sm font-medium text-gray-900">Gold Plan</p>
                                <p class="text-xs text-gray-500 truncate">Planned feature</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- STEP 3: Configuration --}}
                <div x-show="step === 3" class="p-6 space-y-6" style="display: none;">
                    <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Configuration</h3>

                    <div x-show="formData.product_type === 'rent_to_own'" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Total Goal Amount ($)</label>
                            <input type="number" step="0.01" name="product_config[goal_amount]" x-model="formData.config.goal_amount" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            <p class="mt-2 text-sm text-gray-500">The total value of the hardware to be paid off.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Monthly Installment ($)</label>
                            <input type="number" step="0.01" name="product_config[monthly_installment]" x-model="formData.config.monthly_installment" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>
                    </div>

                    <div x-show="formData.product_type === 'silver_plan'" class="space-y-6">
                         <div>
                            <label class="block text-sm font-medium text-gray-700">Base Rate ($)</label>
                            <input type="number" step="0.01" name="product_config[base_rate]" x-model="formData.config.base_rate" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Per User Rate ($)</label>
                            <input type="number" step="0.01" name="product_config[per_user_rate]" x-model="formData.config.per_user_rate" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>
                    </div>
                </div>

                {{-- STEP 4: Review --}}
                <div x-show="step === 4" class="p-6 space-y-6" style="display: none;">
                    <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Review Template</h3>

                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Client ID</dt>
                            <dd class="mt-1 text-sm text-gray-900" x-text="formData.client_id"></dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Template Name</dt>
                            <dd class="mt-1 text-sm text-gray-900" x-text="formData.name"></dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Billing Cycle</dt>
                            <dd class="mt-1 text-sm text-gray-900" x-text="formData.billing_cycle"></dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Product Type</dt>
                            <dd class="mt-1 text-sm text-gray-900 capitalize" x-text="formData.product_type.replace('_', ' ')"></dd>
                        </div>
                        
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Configuration</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <ul class="border border-gray-200 rounded-md divide-y divide-gray-200">
                                    <template x-for="(value, key) in formData.config" :key="key">
                                        <li class="pl-3 pr-4 py-3 flex items-center justify-between text-sm">
                                            <div class="w-0 flex-1 flex items-center">
                                                <span class="ml-2 flex-1 w-0 truncate capitalize" x-text="key.replace('_', ' ') + ': $' + value"></span>
                                            </div>
                                        </li>
                                    </template>
                                </ul>
                            </dd>
                        </div>
                    </dl>
                </div>

                {{-- Actions --}}
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse justify-between">
                    <div class="flex flex-row-reverse gap-2">
                        <button type="submit" x-show="step === 4" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Create Template
                        </button>
                        
                        <button type="button" @click="nextStep()" x-show="step < 4" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Next
                        </button>
                    </div>

                    <button type="button" @click="step--" x-show="step > 1" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Back
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function billingWizard() {
            return {
                step: 1,
                formData: {
                    client_id: '{{ $selectedClientId ?? '' }}',
                    name: '',
                    billing_cycle: 'monthly',
                    product_type: '',
                    config: {}
                },
                nextStep() {
                    if (this.step === 1) {
                        if (!this.formData.client_id || !this.formData.name) {
                            alert('Please fill in all fields.');
                            return;
                        }
                    }
                    if (this.step === 2) {
                        if (!this.formData.product_type) {
                            alert('Please select a product type.');
                            return;
                        }
                    }
                    if (this.step === 3) {
                         // Validate config based on type
                        if (this.formData.product_type === 'rent_to_own') {
                            if (!this.formData.config.goal_amount || !this.formData.config.monthly_installment) {
                                alert('Please complete the configuration.');
                                return;
                            }
                        }
                        if (this.formData.product_type === 'silver_plan') {
                            if (!this.formData.config.base_rate || !this.formData.config.per_user_rate) {
                                alert('Please complete the configuration.');
                                return;
                            }
                        }
                    }
                    this.step++;
                }
            }
        }
    </script>
</x-app-layout>
