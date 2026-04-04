<x-guest-layout>
    <div x-data="{ showCredentials: {{ $errors->any() || old('email') ? 'true' : 'false' }} }" class="space-y-6">
        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <a href="{{ route('auth.google') }}"
            class="w-full inline-flex items-center justify-center gap-3 px-4 py-3 border border-neutral-300 shadow-sm text-sm font-medium rounded-md text-neutral-700 bg-white hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors duration-200">
            <svg class="h-5 w-5" aria-hidden="true" viewBox="0 0 24 24">
                <path fill="#EA4335"
                    d="M12 10.2v3.9h5.5c-.2 1.3-1.6 3.9-5.5 3.9-3.3 0-6-2.7-6-6s2.7-6 6-6c1.9 0 3.2.8 3.9 1.5l2.7-2.6C16.9 3.3 14.6 2.4 12 2.4 6.9 2.4 2.8 6.5 2.8 11.6S6.9 20.8 12 20.8c6.9 0 9.1-4.8 9.1-7.3 0-.5-.1-.9-.1-1.3H12z" />
            </svg>
            Continue with Google
        </a>

        <div class="text-center">
            <button type="button" @click="showCredentials = !showCredentials"
                class="text-xs font-medium text-neutral-500 hover:text-neutral-700 underline decoration-dotted underline-offset-2">
                Sign in with other credentials
            </button>
        </div>

        <div x-show="showCredentials" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1" x-cloak>
            <div class="relative my-5">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-neutral-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white text-neutral-500">Other credentials</span>
                </div>
            </div>

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <!-- Email Address -->
                <div>
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" class="block mt-1 w-full" type="email" name="email"
                        :value="old('email')" required autofocus autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <!-- Password -->
                <div class="mt-4">
                    <x-input-label for="password" :value="__('Password')" />

                    <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required
                        autocomplete="current-password" />

                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <!-- Remember Me -->
                <div class="block mt-4">
                    <label for="remember_me" class="inline-flex items-center">
                        <input id="remember_me" type="checkbox"
                            class="rounded border-neutral-300 text-primary-600 shadow-sm focus:ring-primary-500"
                            name="remember">
                        <span class="ms-2 text-sm text-neutral-600">{{ __('Remember me') }}</span>
                    </label>
                </div>

                <div class="flex items-center justify-end mt-4">
                    @if (Route::has('password.request'))
                        <a class="underline text-sm text-neutral-600 hover:text-neutral-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                            href="{{ route('password.request') }}">
                            {{ __('Forgot your password?') }}
                        </a>
                    @endif

                    <x-primary-button class="ms-3">
                        {{ __('Log in') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
