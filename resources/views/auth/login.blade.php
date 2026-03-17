<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-neutral-300 text-primary-600 shadow-sm focus:ring-primary-500" name="remember">
                <span class="ms-2 text-sm text-neutral-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-neutral-600 hover:text-neutral-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>

        <div class="mt-6">
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-neutral-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white text-neutral-500">Or continue with</span>
                </div>
            </div>

            <div class="mt-6">
                <a href="{{ route('auth.google') }}" class="w-full flex items-center justify-center px-4 py-2 border border-neutral-300 shadow-sm text-sm font-medium rounded-md text-neutral-700 bg-white hover:bg-neutral-50">
                    <svg class="h-5 w-5 mr-2" aria-hidden="true" viewBox="0 0 24 24">
                        <path d="M12.0003 20.45c-4.6667 0-8.45-3.7833-8.45-8.45 0-4.6667 3.7833-8.45 8.45-8.45 2.2833 0 4.35 0.8333 5.9667 2.2167l-2.5333 2.5333c-0.9333-0.9-2.15-1.45-3.4333-1.45-2.9167 0-5.2833 2.3667-5.2833 5.2833s2.3667 5.2833 5.2833 5.2833c2.6833 0 4.9-1.8833 5.2167-4.4333h-5.2167v-3.1667h8.5333c0.0833 0.5 0.1333 1.0167 0.1333 1.55 0 4.75-3.1667 8.1333-8.1333 8.1333z" fill="#4285F4" />
                    </svg>
                    Sign in with Google
                </a>
            </div>
        </div>
    </form>
</x-guest-layout>
