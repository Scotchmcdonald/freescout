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
