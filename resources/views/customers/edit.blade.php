<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Customer') }} - {{ $customer->getFullName() }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if($errors->any())
                        <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4">
                            <ul class="list-disc list-inside text-sm text-red-700">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    @if(session('success'))
                        <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4">
                            <p class="text-sm text-green-700">{{ session('success') }}</p>
                        </div>
                    @endif
                    
                    <form method="POST" action="{{ route('customers.update', $customer) }}" id="customerForm">
                        @csrf
                        @method('PATCH')
                        
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
                                            <button type="button" onclick="removeEmail(this)" class="px-3 py-2 text-red-600 hover:text-red-800">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                                <button type="button" onclick="addEmail()" class="mt-2 text-sm text-blue-600 hover:text-blue-800">
                                    + Add another email
                                </button>
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
                            
                            <div>
                                <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ __('Notes') }}
                                </label>
                                <textarea name="notes" id="notes" rows="4"
                                          class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes', $customer->notes) }}</textarea>
                            </div>
                        </div>
                        
                        <div class="mt-6 flex justify-between">
                            <a href="{{ route('customers.show', $customer) }}" 
                               class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                {{ __('Cancel') }}
                            </a>
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                {{ __('Save Customer') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let emailIndex = {{ count($emails) }};
        
        function addEmail() {
            const container = document.getElementById('emails-container');
            const newRow = document.createElement('div');
            newRow.className = 'email-row flex gap-2 mb-2';
            newRow.innerHTML = `
                <input type="email" name="emails[${emailIndex}][email]"
                       placeholder="email@example.com"
                       class="flex-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <select name="emails[${emailIndex}][type]"
                        class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="work">Work</option>
                    <option value="home">Home</option>
                    <option value="other">Other</option>
                </select>
                <button type="button" onclick="removeEmail(this)" class="px-3 py-2 text-red-600 hover:text-red-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;
            container.insertBefore(newRow, container.querySelector('button'));
            emailIndex++;
        }
        
        function removeEmail(button) {
            button.closest('.email-row').remove();
        }
    </script>
</x-app-layout>
