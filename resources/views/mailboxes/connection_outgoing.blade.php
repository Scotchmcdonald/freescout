<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('mailboxes.settings', $mailbox) }}">{{ $mailbox->name }}</a> &raquo; Outgoing Connection
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

                    <form method="POST" action="{{ route('mailboxes.connection.outgoing', $mailbox) }}">
                        @csrf

                        <!-- From Name -->
                        <div class="mt-4">
                            <x-input-label for="from_name" :value="__('From Name')" />
                            <x-text-input id="from_name" class="block mt-1 w-full" type="text" name="from_name" :value="old('from_name', $mailbox->from_name)" />
                            <x-input-error :messages="$errors->get('from_name')" class="mt-2" />
                        </div>

                        <!-- Method -->
                        <div class="mt-4">
                            <x-input-label for="out_method" :value="__('Method')" />
                            <select id="out_method" name="out_method" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="smtp" @if(old('out_method', $mailbox->out_method) == 'smtp') selected @endif>SMTP</option>
                                <option value="mail" @if(old('out_method', $mailbox->out_method) == 'mail') selected @endif>PHP Mail</option>
                            </select>
                            <x-input-error :messages="$errors->get('out_method')" class="mt-2" />
                        </div>

                        <!-- Server -->
                        <div class="mt-4">
                            <x-input-label for="out_server" :value="__('SMTP Server')" />
                            <x-text-input id="out_server" class="block mt-1 w-full" type="text" name="out_server" :value="old('out_server', $mailbox->out_server)" />
                            <x-input-error :messages="$errors->get('out_server')" class="mt-2" />
                        </div>

                        <!-- Port -->
                        <div class="mt-4">
                            <x-input-label for="out_port" :value="__('SMTP Port')" />
                            <x-text-input id="out_port" class="block mt-1 w-full" type="number" name="out_port" :value="old('out_port', $mailbox->out_port)" />
                            <x-input-error :messages="$errors->get('out_port')" class="mt-2" />
                        </div>
                        
                        <!-- Encryption -->
                        <div class="mt-4">
                            <x-input-label for="out_encryption" :value="__('Encryption')" />
                            <select id="out_encryption" name="out_encryption" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="none" @if(old('out_encryption', $mailbox->out_encryption) == 'none') selected @endif>None</option>
                                <option value="ssl" @if(old('out_encryption', $mailbox->out_encryption) == 'ssl') selected @endif>SSL</option>
                                <option value="tls" @if(old('out_encryption', $mailbox->out_encryption) == 'tls') selected @endif>TLS</option>
                            </select>
                            <x-input-error :messages="$errors->get('out_encryption')" class="mt-2" />
                        </div>

                        <!-- Username -->
                        <div class="mt-4">
                            <x-input-label for="out_username" :value="__('Username')" />
                            <x-text-input id="out_username" class="block mt-1 w-full" type="text" name="out_username" :value="old('out_username', $mailbox->out_username)" />
                            <x-input-error :messages="$errors->get('out_username')" class="mt-2" />
                        </div>

                        <!-- Password -->
                        <div class="mt-4">
                            <x-input-label for="out_password" :value="__('Password')" />
                            <x-text-input id="out_password" class="block mt-1 w-full" type="password" name="out_password" />
                            <p class="text-sm text-gray-500 mt-1">Leave blank to keep the current password.</p>
                            <x-input-error :messages="$errors->get('out_password')" class="mt-2" />
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
