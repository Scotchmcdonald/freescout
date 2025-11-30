<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create a Mailbox') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <p class="text-sm text-gray-600">
                            {{ __('Customers email this address for help (e.g. support@domain.com)') }}
                        </p>
                    </div>

                    <form method="POST" action="{{ route('mailboxes.store') }}" class="space-y-6">
                        @csrf

                        <!-- Email Address -->
                        <div>
                            <x-input-label for="email" :value="__('Email Address')" />
                            <x-text-input id="email" 
                                          class="block mt-1 w-full" 
                                          type="email" 
                                          name="email" 
                                          :value="old('email')" 
                                          required 
                                          autofocus 
                                          maxlength="128" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            <p class="mt-2 text-sm text-gray-500">{{ __('You can edit this later') }}</p>
                        </div>

                        <!-- Mailbox Name -->
                        <div>
                            <x-input-label for="name" :value="__('Mailbox Name')" />
                            <x-text-input id="name" 
                                          class="block mt-1 w-full" 
                                          type="text" 
                                          name="name" 
                                          :value="old('name')" 
                                          required 
                                          maxlength="40" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <!-- From Name (Optional) -->
                        <div>
                            <x-input-label for="from_name" :value="__('From Name (Optional)')" />
                            <x-text-input id="from_name" 
                                          class="block mt-1 w-full" 
                                          type="text" 
                                          name="from_name" 
                                          :value="old('from_name')" 
                                          maxlength="255" />
                            <x-input-error :messages="$errors->get('from_name')" class="mt-2" />
                            <p class="mt-2 text-sm text-gray-500">{{ __('Name that will appear in the "From" field of outgoing emails') }}</p>
                        </div>

                        <!-- Connection Settings -->
                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Incoming Email Settings (IMAP)') }}</h3>
                            
                            <!-- Protocol -->
                            <div class="mt-4">
                                <x-input-label for="in_protocol" :value="__('Protocol')" />
                                <select id="in_protocol" name="in_protocol" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option value="imap" @if(old('in_protocol') == 'imap') selected @endif>IMAP</option>
                                    <option value="pop3" @if(old('in_protocol') == 'pop3') selected @endif>POP3</option>
                                </select>
                                <x-input-error :messages="$errors->get('in_protocol')" class="mt-2" />
                            </div>

                            <!-- Server -->
                            <div class="mt-4">
                                <x-input-label for="in_server" :value="__('IMAP Server')" />
                                <x-text-input id="in_server" class="block mt-1 w-full" type="text" name="in_server" :value="old('in_server')" />
                                <x-input-error :messages="$errors->get('in_server')" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-2 gap-4 mt-4">
                                <!-- Port -->
                                <div>
                                    <x-input-label for="in_port" :value="__('IMAP Port')" />
                                    <x-text-input id="in_port" class="block mt-1 w-full" type="number" name="in_port" :value="old('in_port')" />
                                    <x-input-error :messages="$errors->get('in_port')" class="mt-2" />
                                </div>
                                
                                <!-- Encryption -->
                                <div>
                                    <x-input-label for="in_encryption" :value="__('Encryption')" />
                                    <select id="in_encryption" name="in_encryption" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <option value="none" @if(old('in_encryption') == 'none') selected @endif>None</option>
                                        <option value="ssl" @if(old('in_encryption') == 'ssl') selected @endif>SSL</option>
                                        <option value="tls" @if(old('in_encryption') == 'tls') selected @endif>TLS</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('in_encryption')" class="mt-2" />
                                </div>
                            </div>

                            <!-- Username -->
                            <div class="mt-4">
                                <x-input-label for="in_username" :value="__('Username')" />
                                <x-text-input id="in_username" class="block mt-1 w-full" type="text" name="in_username" :value="old('in_username')" />
                                <x-input-error :messages="$errors->get('in_username')" class="mt-2" />
                            </div>

                            <!-- Password -->
                            <div class="mt-4">
                                <x-input-label for="in_password" :value="__('Password')" />
                                <x-text-input id="in_password" class="block mt-1 w-full" type="password" name="in_password" />
                                <x-input-error :messages="$errors->get('in_password')" class="mt-2" />
                            </div>
                        </div>

                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Outgoing Email Settings (SMTP)') }}</h3>
                            
                            <!-- Method -->
                            <div class="mt-4">
                                <x-input-label for="out_method" :value="__('Method')" />
                                <select id="out_method" name="out_method" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option value="smtp" @if(old('out_method') == 'smtp') selected @endif>SMTP</option>
                                    <option value="mail" @if(old('out_method') == 'mail') selected @endif>PHP Mail</option>
                                </select>
                                <x-input-error :messages="$errors->get('out_method')" class="mt-2" />
                            </div>

                            <!-- Server -->
                            <div class="mt-4">
                                <x-input-label for="out_server" :value="__('SMTP Server')" />
                                <x-text-input id="out_server" class="block mt-1 w-full" type="text" name="out_server" :value="old('out_server')" />
                                <x-input-error :messages="$errors->get('out_server')" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-2 gap-4 mt-4">
                                <!-- Port -->
                                <div>
                                    <x-input-label for="out_port" :value="__('SMTP Port')" />
                                    <x-text-input id="out_port" class="block mt-1 w-full" type="number" name="out_port" :value="old('out_port')" />
                                    <x-input-error :messages="$errors->get('out_port')" class="mt-2" />
                                </div>
                                
                                <!-- Encryption -->
                                <div>
                                    <x-input-label for="out_encryption" :value="__('Encryption')" />
                                    <select id="out_encryption" name="out_encryption" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <option value="none" @if(old('out_encryption') == 'none') selected @endif>None</option>
                                        <option value="ssl" @if(old('out_encryption') == 'ssl') selected @endif>SSL</option>
                                        <option value="tls" @if(old('out_encryption') == 'tls') selected @endif>TLS</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('out_encryption')" class="mt-2" />
                                </div>
                            </div>

                            <!-- Username -->
                            <div class="mt-4">
                                <x-input-label for="out_username" :value="__('Username')" />
                                <x-text-input id="out_username" class="block mt-1 w-full" type="text" name="out_username" :value="old('out_username')" />
                                <x-input-error :messages="$errors->get('out_username')" class="mt-2" />
                            </div>

                            <!-- Password -->
                            <div class="mt-4">
                                <x-input-label for="out_password" :value="__('Password')" />
                                <x-text-input id="out_password" class="block mt-1 w-full" type="password" name="out_password" />
                                <x-input-error :messages="$errors->get('out_password')" class="mt-2" />
                            </div>
                        </div>

                        <!-- Who Else Will Use This Mailbox -->
                        @if(isset($users) && $users->count() > 0)
                        <div>
                            <x-input-label for="users" :value="__('Who Else Will Use This Mailbox')" />
                            <div class="mt-2 space-y-2">
                                <div class="text-sm mb-2">
                                    <a href="#" class="text-blue-600 hover:text-blue-800" onclick="event.preventDefault(); document.querySelectorAll('input[name=\'users[]\']').forEach(el => el.checked = true);">{{ __('all') }}</a>
                                    /
                                    <a href="#" class="text-blue-600 hover:text-blue-800" onclick="event.preventDefault(); document.querySelectorAll('input[name=\'users[]\']').forEach(el => el.checked = false);">{{ __('none') }}</a>
                                </div>
                                <div class="space-y-2 max-h-64 overflow-y-auto border border-gray-300 rounded-md p-4">
                                    @foreach ($users as $user)
                                    <div class="flex items-center">
                                        <input type="checkbox" 
                                               name="users[]" 
                                               id="user-{{ $user->id }}" 
                                               value="{{ $user->id }}"
                                               @if (is_array(old('users')) && in_array($user->id, old('users'))) checked @endif
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        <label for="user-{{ $user->id }}" class="ml-2 text-sm text-gray-700">
                                            {{ $user->getFullName() }}
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            <x-input-error :messages="$errors->get('users')" class="mt-2" />
                        </div>
                        @endif

                        <!-- Submit Buttons -->
                        <div class="flex items-center justify-end gap-4">
                            <a href="{{ route('mailboxes.index') }}" 
                               class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                                {{ __('Cancel') }}
                            </a>
                            <x-primary-button>
                                {{ __('Create Mailbox') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
