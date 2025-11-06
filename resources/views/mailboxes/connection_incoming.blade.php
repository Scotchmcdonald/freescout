<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('mailboxes.settings', $mailbox) }}">{{ $mailbox->name }}</a> &raquo; Incoming Connection
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('mailboxes.connection.incoming', $mailbox) }}">
                        @csrf

                        <!-- Protocol -->
                        <div class="mt-4">
                            <x-input-label for="in_protocol" :value="__('Protocol')" />
                            <select id="in_protocol" name="in_protocol" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="imap" @if(old('in_protocol', $mailbox->in_protocol) == 'imap') selected @endif>IMAP</option>
                                <option value="pop3" @if(old('in_protocol', $mailbox->in_protocol) == 'pop3') selected @endif>POP3</option>
                            </select>
                            <x-input-error :messages="$errors->get('in_protocol')" class="mt-2" />
                        </div>

                        <!-- Server -->
                        <div class="mt-4">
                            <x-input-label for="in_server" :value="__('Server')" />
                            <x-text-input id="in_server" class="block mt-1 w-full" type="text" name="in_server" :value="old('in_server', $mailbox->in_server)" required />
                            <x-input-error :messages="$errors->get('in_server')" class="mt-2" />
                        </div>

                        <!-- Port -->
                        <div class="mt-4">
                            <x-input-label for="in_port" :value="__('Port')" />
                            <x-text-input id="in_port" class="block mt-1 w-full" type="number" name="in_port" :value="old('in_port', $mailbox->in_port)" required />
                            <x-input-error :messages="$errors->get('in_port')" class="mt-2" />
                        </div>
                        
                        <!-- Encryption -->
                        <div class="mt-4">
                            <x-input-label for="in_encryption" :value="__('Encryption')" />
                            <select id="in_encryption" name="in_encryption" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="none" @if(old('in_encryption', $mailbox->in_encryption) == 'none') selected @endif>None</option>
                                <option value="ssl" @if(old('in_encryption', $mailbox->in_encryption) == 'ssl') selected @endif>SSL</option>
                                <option value="tls" @if(old('in_encryption', $mailbox->in_encryption) == 'tls') selected @endif>TLS</option>
                            </select>
                            <x-input-error :messages="$errors->get('in_encryption')" class="mt-2" />
                        </div>

                        <!-- Username -->
                        <div class="mt-4">
                            <x-input-label for="in_username" :value="__('Username')" />
                            <x-text-input id="in_username" class="block mt-1 w-full" type="text" name="in_username" :value="old('in_username', $mailbox->in_username)" required />
                            <x-input-error :messages="$errors->get('in_username')" class="mt-2" />
                        </div>

                        <!-- Password -->
                        <div class="mt-4">
                            <x-input-label for="in_password" :value="__('Password')" />
                            <x-text-input id="in_password" class="block mt-1 w-full" type="password" name="in_password" />
                            <p class="text-sm text-gray-500 mt-1">Leave blank to keep the current password.</p>
                            <x-input-error :messages="$errors->get('in_password')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <x-primary-button class="ml-4">
                                {{ __('Save Settings') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
