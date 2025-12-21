<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @auth
            <meta name="user-id" content="{{ auth()->id() }}">
        @endauth
        @if(isset($conversation))
            <meta name="conversation-id" content="{{ $conversation->id }}">
        @endif

        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        <script nonce="{{ csp_nonce() }}">
            window.themeUpdateUrl = "{{ route('themes.update') }}";
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.theme-styles')
        @action('layout.head')
    </head>
    <body class="font-sans antialiased" x-data="dynamicFavicon">
        @action('layout.body_start')
        <div class="min-h-screen bg-gray-100">
            <x-layouts.navigation />
            
            <!-- Update Banner for Admins -->
            @include('partials.update-banner')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                @hasSection('content')
                    @yield('content')
                @else
                    {{ $slot ?? '' }}
                @endif
            </main>
        </div>
        
        <!-- Global Activity Drawer -->
        <x-activity-drawer />
        
        <!-- Activity Trigger (Bottom Right) -->
        <div class="fixed bottom-4 right-4 z-40" x-data>
            <button @click="$dispatch('open-activity-drawer')" class="bg-white p-3 rounded-full shadow-lg border border-gray-200 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" title="View Activity">
                <svg class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                </svg>
            </button>
        </div>

        @action('layout.body_end')
        @stack('scripts')
    </body>
</html>
