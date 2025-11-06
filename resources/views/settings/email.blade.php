<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Email Settings') }}
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
                    
                    @if($errors->any())
                        <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4">
                            <ul class="list-disc list-inside text-sm text-red-700">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <div class="mb-6 bg-blue-50 border-l-4 border-blue-400 p-4">
                        <p class="text-sm text-blue-700">
                            {{ __('These settings are used to send system emails (alerts and notifications).') }}
                        </p>
                    </div>
                    
                    <form method="POST" action="{{ route('settings.email.update') }}">
                        @csrf
                        
                        <div class="space-y-6">
                            <div>
                                <label for="mail_driver" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ __('Mail Driver') }} *
                                </label>
                                <select name="mail_driver" id="mail_driver" required
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="smtp" {{ old('mail_driver', $settings['mail_driver'] ?? 'smtp') == 'smtp' ? 'selected' : '' }}>SMTP</option>
                                    <option value="sendmail" {{ old('mail_driver', $settings['mail_driver'] ?? '') == 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                                    <option value="mailgun" {{ old('mail_driver', $settings['mail_driver'] ?? '') == 'mailgun' ? 'selected' : '' }}>Mailgun</option>
                                    <option value="ses" {{ old('mail_driver', $settings['mail_driver'] ?? '') == 'ses' ? 'selected' : '' }}>Amazon SES</option>
                                    <option value="postmark" {{ old('mail_driver', $settings['mail_driver'] ?? '') == 'postmark' ? 'selected' : '' }}>Postmark</option>
                                </select>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="mail_from_address" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('From Email') }} *
                                    </label>
                                    <input type="email" name="mail_from_address" id="mail_from_address" required
                                           value="{{ old('mail_from_address', $settings['mail_from_address'] ?? '') }}"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label for="mail_from_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('From Name') }} *
                                    </label>
                                    <input type="text" name="mail_from_name" id="mail_from_name" required
                                           value="{{ old('mail_from_name', $settings['mail_from_name'] ?? '') }}"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>
                            
                            <div class="border-t border-gray-200 pt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('SMTP Settings') }}</h3>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="mail_host" class="block text-sm font-medium text-gray-700 mb-2">
                                            {{ __('SMTP Host') }}
                                        </label>
                                        <input type="text" name="mail_host" id="mail_host"
                                               value="{{ old('mail_host', $settings['mail_host'] ?? '') }}"
                                               placeholder="smtp.example.com"
                                               class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                    
                                    <div class="grid grid-cols-3 gap-4">
                                        <div>
                                            <label for="mail_port" class="block text-sm font-medium text-gray-700 mb-2">
                                                {{ __('SMTP Port') }}
                                            </label>
                                            <input type="number" name="mail_port" id="mail_port"
                                                   value="{{ old('mail_port', $settings['mail_port'] ?? 587) }}"
                                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        </div>
                                        
                                        <div class="col-span-2">
                                            <label for="mail_encryption" class="block text-sm font-medium text-gray-700 mb-2">
                                                {{ __('Encryption') }}
                                            </label>
                                            <select name="mail_encryption" id="mail_encryption"
                                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                <option value="">None</option>
                                                <option value="tls" {{ old('mail_encryption', $settings['mail_encryption'] ?? 'tls') == 'tls' ? 'selected' : '' }}>TLS</option>
                                                <option value="ssl" {{ old('mail_encryption', $settings['mail_encryption'] ?? '') == 'ssl' ? 'selected' : '' }}>SSL</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label for="mail_username" class="block text-sm font-medium text-gray-700 mb-2">
                                            {{ __('SMTP Username') }}
                                        </label>
                                        <input type="text" name="mail_username" id="mail_username"
                                               value="{{ old('mail_username', $settings['mail_username'] ?? '') }}"
                                               class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                    
                                    <div>
                                        <label for="mail_password" class="block text-sm font-medium text-gray-700 mb-2">
                                            {{ __('SMTP Password') }}
                                        </label>
                                        <input type="password" name="mail_password" id="mail_password"
                                               value="{{ old('mail_password', $settings['mail_password'] ?? '') }}"
                                               class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <p class="mt-1 text-sm text-gray-500">Leave blank to keep current password</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-6 flex justify-end">
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                {{ __('Save Email Settings') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
