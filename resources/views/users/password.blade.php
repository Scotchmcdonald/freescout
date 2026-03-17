<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-neutral-800 leading-tight">
            {{ __('Change Password') }}: {{ $user->getFullName() }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col md:flex-row">
            <x-user-sidebar-menu :user="$user" :users="$users ?? collect()" />
            
            <div class="flex-1 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-neutral-900">
                    <x-flash-messages />
                    
                    <form method="POST" action="{{ route('users.password.update', $user) }}">
                        @csrf
                        
                        <div class="space-y-6">
                            <!-- Current Password (only for own account) -->
                            @if(auth()->id() === $user->id)
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-neutral-700 mb-2">
                                    {{ __('Current Password') }} <span class="text-danger-500">*</span>
                                </label>
                                <input type="password" name="current_password" id="current_password" required
                                       class="w-full max-w-lg border-neutral-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                       autocomplete="current-password">
                                @error('current_password')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                            @else
                            <div class="bg-warning-50 border-l-4 border-warning-400 p-4 mb-4">
                                <p class="text-sm text-warning-700">
                                    {{ __("You are changing another user's password. They will receive an email notification.") }}
                                </p>
                            </div>
                            @endif
                            
                            <!-- New Password -->
                            <div>
                                <label for="password" class="block text-sm font-medium text-neutral-700 mb-2">
                                    {{ __('New Password') }} <span class="text-danger-500">*</span>
                                </label>
                                <input type="password" name="password" id="password" required minlength="8"
                                       class="w-full max-w-lg border-neutral-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                       autocomplete="new-password">
                                <p class="mt-1 text-sm text-neutral-500">{{ __('Minimum 8 characters') }}</p>
                                @error('password')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <!-- Confirm New Password -->
                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-neutral-700 mb-2">
                                    {{ __('Confirm New Password') }} <span class="text-danger-500">*</span>
                                </label>
                                <input type="password" name="password_confirmation" id="password_confirmation" required minlength="8"
                                       class="w-full max-w-lg border-neutral-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                       autocomplete="new-password">
                                @error('password_confirmation')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="mt-6 flex items-center justify-end space-x-4">
                            <a href="{{ route('users.show', $user) }}" 
                               class="px-4 py-2 border border-neutral-300 rounded-md text-sm font-medium text-neutral-700 bg-white hover:bg-neutral-50">
                                {{ __('Cancel') }}
                            </a>
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 transition">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                </svg>
                                {{ __('Update Password') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
