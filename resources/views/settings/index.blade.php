<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('General Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col md:flex-row">
            @include('settings.partials.sidebar')
            
            <div class="flex-1 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(session('success'))
                        <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4">
                            <p class="text-sm text-green-700">{{ session('success') }}</p>
                        </div>
                    @endif
                    
                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf
                        
                        <div class="space-y-6">
                            <!-- Company Information -->
                            <div class="bg-white shadow rounded-lg p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Company Information') }}</h3>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="company_name" class="block text-sm font-medium text-gray-700 mb-2">
                                            {{ __('Company Name') }}
                                        </label>
                                        <input type="text" name="company_name" id="company_name" maxlength="60"
                                               value="{{ old('company_name', $settings['company_name'] ?? '') }}"
                                               class="w-full max-w-lg border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <p class="mt-1 text-sm text-gray-500">
                                            {{ __('Used in email signatures and customer-facing communications') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Conversation Numbering -->
                            <div class="bg-white shadow rounded-lg p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Conversation Numbering') }}</h3>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Conversation Number Type') }}</label>
                                        <div class="space-y-2">
                                            <label class="flex items-center">
                                                <input type="radio" name="custom_number" value="0" 
                                                       {{ !old('custom_number', $settings['custom_number'] ?? false) ? 'checked' : '' }}
                                                       class="rounded-full border-gray-300 text-blue-600 focus:ring-blue-500"
                                                       onchange="document.getElementById('next_ticket_container').classList.add('hidden')">
                                                <span class="ml-2 text-sm text-gray-700">{{ __('Equal to conversation ID') }}</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input type="radio" name="custom_number" value="1"
                                                       {{ old('custom_number', $settings['custom_number'] ?? false) ? 'checked' : '' }}
                                                       class="rounded-full border-gray-300 text-blue-600 focus:ring-blue-500"
                                                       onchange="document.getElementById('next_ticket_container').classList.remove('hidden')">
                                                <span class="ml-2 text-sm text-gray-700">{{ __('Custom') }}</span>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div id="next_ticket_container" class="{{ old('custom_number', $settings['custom_number'] ?? false) ? '' : 'hidden' }}">
                                        <label for="next_ticket" class="block text-sm font-medium text-gray-700 mb-2">
                                            {{ __('Next Conversation Number') }}
                                        </label>
                                        <div class="flex items-center">
                                            <input type="number" name="next_ticket" id="next_ticket" min="1"
                                                   value="{{ old('next_ticket', $settings['next_ticket'] ?? 1) }}"
                                                   class="w-32 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-500" title="{{ __('This number is not visible to customers. It is only used to track conversations internally.') }}">
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Localization -->
                            <div class="bg-white shadow rounded-lg p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Localization') }}</h3>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="locale" class="block text-sm font-medium text-gray-700 mb-2">
                                            {{ __('Default Language') }}
                                        </label>
                                        <select id="locale" name="locale" class="w-full max-w-lg border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            @include('partials.locale_options', ['selected' => old('locale', $settings['locale'] ?? config('app.locale'))])
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label for="timezone" class="block text-sm font-medium text-gray-700 mb-2">
                                            {{ __('Timezone') }}
                                        </label>
                                        <select id="timezone" name="timezone" class="w-full max-w-lg border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            @include('partials.timezone_options', ['current_timezone' => old('timezone', $settings['timezone'] ?? config('app.timezone'))])
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Time Format') }}</label>
                                        <div class="space-y-2">
                                            <label class="flex items-center">
                                                <input type="radio" name="time_format" value="12"
                                                       {{ old('time_format', $settings['time_format'] ?? '24') == '12' ? 'checked' : '' }}
                                                       class="rounded-full border-gray-300 text-blue-600 focus:ring-blue-500">
                                                <span class="ml-2 text-sm text-gray-700">{{ __('12-hour clock (e.g. 2:13pm)') }}</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input type="radio" name="time_format" value="24"
                                                       {{ old('time_format', $settings['time_format'] ?? '24') == '24' ? 'checked' : '' }}
                                                       class="rounded-full border-gray-300 text-blue-600 focus:ring-blue-500">
                                                <span class="ml-2 text-sm text-gray-700">{{ __('24-hour clock (e.g. 14:13)') }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Emails to Customers -->
                            <div class="bg-white shadow rounded-lg p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Emails to Customers') }}</h3>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="email_conv_history" class="block text-sm font-medium text-gray-700 mb-2">
                                            {{ __('Conversation History') }}
                                        </label>
                                        <select id="email_conv_history" name="email_conv_history" class="w-full max-w-lg border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="none" {{ old('email_conv_history', $settings['email_conv_history'] ?? 'none') == 'none' ? 'selected' : '' }}>
                                                {{ __('Do not include previous messages') }}
                                            </option>
                                            <option value="last" {{ old('email_conv_history', $settings['email_conv_history'] ?? 'none') == 'last' ? 'selected' : '' }}>
                                                {{ __('Include the last message') }}
                                            </option>
                                            <option value="full" {{ old('email_conv_history', $settings['email_conv_history'] ?? 'none') == 'full' ? 'selected' : '' }}>
                                                {{ __('Include full conversation history') }}
                                            </option>
                                        </select>
                                        <p class="mt-1 text-sm text-gray-500">
                                            {{ __('Controls how much conversation history to include in customer emails') }}
                                        </p>
                                    </div>
                                    
                                    <div class="flex items-center">
                                        <input type="checkbox" name="email_branding" id="email_branding" value="1"
                                               {{ old('email_branding', $settings['email_branding'] ?? false) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <label for="email_branding" class="ml-2 text-sm text-gray-700">
                                            {{ __('Include "Powered by FreeScout" in emails') }}
                                        </label>
                                    </div>
                                    
                                    <div class="flex items-center">
                                        <input type="checkbox" name="open_tracking" id="open_tracking" value="1"
                                               {{ old('open_tracking', $settings['open_tracking'] ?? false) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <label for="open_tracking" class="ml-2 text-sm text-gray-700">
                                            {{ __('Track email opens') }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Customer Data -->
                            <div class="bg-white shadow rounded-lg p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Customer Data') }}</h3>
                                
                                <div class="flex items-center">
                                    <input type="checkbox" name="enrich_customer_data" id="enrich_customer_data" value="1"
                                           {{ old('enrich_customer_data', $settings['enrich_customer_data'] ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <label for="enrich_customer_data" class="ml-2 text-sm text-gray-700">
                                        {{ __('Automatically enrich customer profiles with public data') }}
                                    </label>
                                </div>
                            </div>
                            
                            <!-- User Permissions -->
                            @if(isset($userPermissions) && count($userPermissions) > 0)
                            <div class="bg-white shadow rounded-lg p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('User Permissions') }}</h3>
                                <p class="text-sm text-gray-500 mb-4">{{ __('Enable these global permissions for all users') }}</p>
                                
                                <div class="space-y-2">
                                    @foreach($userPermissions as $permissionId => $permissionName)
                                        <label class="flex items-center">
                                            <input type="checkbox" 
                                                   name="user_permissions[]" 
                                                   value="{{ $permissionId }}"
                                                   {{ in_array($permissionId, old('user_permissions', $settings['user_permissions'] ?? [])) ? 'checked' : '' }}
                                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-700">{{ $permissionName }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                        
                        <div class="mt-6 flex justify-end">
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ __('Save Settings') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
