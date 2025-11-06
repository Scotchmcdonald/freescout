<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('General Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(session('success'))
                        <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4">
                            <p class="text-sm text-green-700">{{ session('success') }}</p>
                        </div>
                    @endif
                    
                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf
                        
                        <div class="space-y-6">
                            <div>
                                <label for="company_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ __('Company Name') }}
                                </label>
                                <input type="text" name="company_name" id="company_name"
                                       value="{{ old('company_name', $settings['company_name'] ?? '') }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p class="mt-1 text-sm text-gray-500">
                                    Used in email signatures and customer-facing communications
                                </p>
                            </div>
                            
                            <div>
                                <label for="next_ticket" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ __('Next Conversation Number') }}
                                </label>
                                <input type="number" name="next_ticket" id="next_ticket" min="1"
                                       value="{{ old('next_ticket', $settings['next_ticket'] ?? 1) }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p class="mt-1 text-sm text-gray-500">
                                    Internal tracking number for conversations (not visible to customers)
                                </p>
                            </div>
                            
                            <div class="border-t border-gray-200 pt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Email Settings') }}</h3>
                                
                                <div class="space-y-4">
                                    <div class="flex items-center">
                                        <input type="checkbox" name="email_branding" id="email_branding" value="1"
                                               {{ old('email_branding', $settings['email_branding'] ?? false) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <label for="email_branding" class="ml-2 text-sm text-gray-700">
                                            {{ __('Include company branding in emails') }}
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
                            
                            <div class="border-t border-gray-200 pt-6">
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
                        </div>
                        
                        <div class="mt-6 flex justify-end">
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                {{ __('Save Settings') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
