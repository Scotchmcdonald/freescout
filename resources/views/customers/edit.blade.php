<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Client') }} - {{ $customer->getFullName() }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="customerForm({{ count(old('emails', $customer->emails ?? [['email' => '', 'type' => 'work']])) }})">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if($errors->any())
                        <div class="mb-6 border-l-4 p-4" style="background-color: var(--theme-status-error-bg); border-color: var(--theme-status-error-bg)">
                            <ul class="list-disc list-inside text-sm" style="color: var(--theme-status-error-text)">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    @if(session('success'))
                        <div class="mb-6 border-l-4 p-4" style="background-color: var(--theme-status-success-bg); border-color: var(--theme-status-success-bg)">
                            <p class="text-sm" style="color: var(--theme-status-success-text)">{{ session('success') }}</p>
                        </div>
                    @endif
                    
                    <form method="POST" action="{{ route('customers.update', $customer) }}" id="customerForm">
                        @csrf
                        @method('PATCH')
                        
                        <x-billing::tabs :tabs="[
                            ['id' => 'profile', 'label' => 'Profile', 'icon' => 'user'],
                            ['id' => 'contact', 'label' => 'Contact Info', 'icon' => 'address-card'],
                            ['id' => 'notes', 'label' => 'Notes', 'icon' => 'sticky-note']
                        ]" active="profile">
                        
                            <x-billing::tab-panel id="profile">
                                <div class="space-y-6">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">
                                                {{ __('First Name') }} *
                                            </label>
                                            <input type="text" name="first_name" id="first_name" required
                                                   value="{{ old('first_name', $customer->first_name) }}"
                                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        </div>
                                        
                                        <div>
                                            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">
                                                {{ __('Last Name') }}
                                            </label>
                                            <input type="text" name="last_name" id="last_name"
                                                   value="{{ old('last_name', $customer->last_name) }}"
                                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label for="company" class="block text-sm font-medium text-gray-700 mb-2">
                                                {{ __('Company') }}
                                            </label>
                                            <input type="text" name="company" id="company"
                                                   value="{{ old('company', $customer->company) }}"
                                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        </div>
                                        
                                        <div>
                                            <label for="job_title" class="block text-sm font-medium text-gray-700 mb-2">
                                                {{ __('Job Title') }}
                                            </label>
                                            <input type="text" name="job_title" id="job_title"
                                                   value="{{ old('job_title', $customer->job_title) }}"
                                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        </div>
                                    </div>
                                </div>
                            </x-billing::tab-panel>

                            <x-billing::tab-panel id="contact">
                                <div class="space-y-6">
                                    <div id="emails-container">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            {{ __('Email Addresses') }}
                                        </label>
                                        @php
                                            $emails = old('emails', $customer->emails ?? [['email' => '', 'type' => 'work']]);
                                        @endphp
                                        @foreach($emails as $index => $email)
                                            <div class="email-row flex gap-2 mb-2">
                                                <input type="email" name="emails[{{ $index }}][email]"
                                                       value="{{ is_array($email) ? ($email['email'] ?? '') : $email }}"
                                                       placeholder="email@example.com"
                                                       class="flex-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                <select name="emails[{{ $index }}][type]"
                                                        class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                    <option value="work" {{ (is_array($email) && ($email['type'] ?? '') == 'work') ? 'selected' : '' }}>Work</option>
                                                    <option value="home" {{ (is_array($email) && ($email['type'] ?? '') == 'home') ? 'selected' : '' }}>Home</option>
                                                    <option value="other" {{ (is_array($email) && ($email['type'] ?? '') == 'other') ? 'selected' : '' }}>Other</option>
                                                </select>
                                                @if($index > 0)
                                                    <button type="button" @click="removeEmail($event)" class="px-3 py-2 hover:opacity-75" style="color: var(--theme-status-error-text)">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    </button>
                                                @endif
                                            </div>
                                        @endforeach
                                        <button type="button" @click="addEmail()" class="mt-2 text-sm hover:underline" style="color: var(--theme-primary-600)">
                                            + Add another email
                                        </button>
                                    </div>

                                    <div>
                                        <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                                            {{ __('Address') }}
                                        </label>
                                        <input type="text" name="address" id="address"
                                               value="{{ old('address', $customer->address) }}"
                                               class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                    
                                    <div class="grid grid-cols-3 gap-4">
                                        <div>
                                            <label for="city" class="block text-sm font-medium text-gray-700 mb-2">
                                                {{ __('City') }}
                                            </label>
                                            <input type="text" name="city" id="city"
                                                   value="{{ old('city', $customer->city) }}"
                                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        </div>
                                        
                                        <div>
                                            <label for="state" class="block text-sm font-medium text-gray-700 mb-2">
                                                {{ __('State/Province') }}
                                            </label>
                                            <input type="text" name="state" id="state"
                                                   value="{{ old('state', $customer->state) }}"
                                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        </div>
                                        
                                        <div>
                                            <label for="zip" class="block text-sm font-medium text-gray-700 mb-2">
                                                {{ __('ZIP/Postal Code') }}
                                            </label>
                                            <input type="text" name="zip" id="zip"
                                                   value="{{ old('zip', $customer->zip) }}"
                                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label for="country" class="block text-sm font-medium text-gray-700 mb-2">
                                            {{ __('Country') }}
                                        </label>
                                        <input type="text" name="country" id="country" maxlength="2"
                                               value="{{ old('country', $customer->country) }}"
                                               placeholder="US"
                                               class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                </div>
                            </x-billing::tab-panel>

                            <x-billing::tab-panel id="notes">
                                <div class="space-y-6">
                                    <div>
                                        <label for="default_hourly_rate" class="block text-sm font-medium text-gray-700 mb-2">
                                            {{ __('Default Hourly Rate') }}
                                        </label>
                                        <input type="number" 
                                               name="default_hourly_rate" 
                                               id="default_hourly_rate"
                                               dusk="default-hourly-rate"
                                               step="0.01"
                                               min="0"
                                               value="{{ old('default_hourly_rate', $customer->default_hourly_rate ?? '') }}"
                                               placeholder="150.00"
                                               class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <p class="mt-1 text-sm text-gray-500">Set a custom hourly billing rate for this client</p>
                                    </div>
                                    
                                    <div>
                                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                                            {{ __('Notes') }}
                                        </label>
                                        <textarea name="notes" id="notes" rows="4"
                                                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes', $customer->notes) }}</textarea>
                                    </div>
                                </div>
                            </x-billing::tab-panel>

                        </x-billing::tabs>
                        
                        <div class="mt-6 flex justify-between">
                            <a href="{{ route('customers.show', $customer) }}" 
                               class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                {{ __('Cancel') }}
                            </a>
                            <button type="submit" 
                                    dusk="save-client-button"
                                    class="px-4 py-2 text-white rounded-md transition"
                                    style="background-color: var(--theme-primary-600)">
                                {{ __('Save Customer') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
