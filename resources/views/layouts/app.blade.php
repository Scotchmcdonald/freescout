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
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.theme-styles')
        @action('layout.head')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Dynamic Favicon Color
                const updateFavicon = () => {
                    const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--theme-primary-600').trim() || '#22c55e';
                    
                    const svgContent = `<svg fill="${primaryColor}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M20 10c0-1.361-.758-2.616-2.031-3.622-.002-.001-.004-.001-.005-.003C17.602 2.803 14.177 0 10 0S2.398 2.803 2.036 6.375c-.001.002-.003.002-.005.003C.758 7.384 0 8.639 0 10c0 3.112 3.947 5.669 9 5.97V17c0 1-1.821 1.911-1.821 1.911a.227.227 0 0 0-.109.277S7.375 20 8 20s1.124-.5 2.374-.5 2.439.432 2.439.432a.342.342 0 0 0 .329-.073l.717-.717c.078-.078.058-.173-.046-.212 0 0-1.812-.68-1.812-1.93v-1.121C16.565 15.324 20 12.903 20 10zM2 10c0-1.019.768-1.945 2.022-2.651C4.012 7.233 4 7.117 4 7c0-2.762 2.687-5 6-5s6 2.238 6 5c0 .117-.012.233-.021.349C17.232 8.055 18 8.981 18 10c0 1.864-2.551 3.424-5.999 3.869v-.668a.53.53 0 0 1 .145-.337l1.833-1.726a.534.534 0 0 0 .146-.337V9.95c0-.11-.078-.155-.172-.099l-1.779 1.047c-.096.056-.173.012-.173-.099V7.2c0-.11-.085-.172-.19-.137l-2.621.874a.297.297 0 0 0-.189.263v2.6c0 .11-.079.158-.177.107L6.802 9.843a.289.289 0 0 0-.318.048l-.342.342a.185.185 0 0 0 .009.273l2.7 2.361c.083.073.15.222.15.332v.765C5.056 13.719 2 12.04 2 10z"/></svg>`;
                    
                    const link = document.querySelector('link[rel="icon"]') || document.createElement('link');
                    link.type = 'image/svg+xml';
                    link.rel = 'icon';
                    link.href = `data:image/svg+xml;base64,${btoa(svgContent)}`;
                    
                    if (!document.head.contains(link)) {
                        document.head.appendChild(link);
                    }
                };
                
                updateFavicon();

                const toggleBtn = document.getElementById('theme-toggle');
                const darkIcon = document.getElementById('theme-toggle-dark-icon');
                const lightIcon = document.getElementById('theme-toggle-light-icon');

                if (toggleBtn) {
                    toggleBtn.addEventListener('click', function() {
                        // Toggle icons
                        darkIcon.classList.toggle('hidden');
                        lightIcon.classList.toggle('hidden');

                        // Determine new state
                        const isDarkMode = lightIcon.classList.contains('hidden');

                        // Send AJAX request to update preference
                        fetch('{{ route('themes.update') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                dark_mode: isDarkMode
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                window.location.reload();
                            }
                        })
                        .catch(error => console.error('Error:', error));
                    });
                }
            });
        </script>
    </head>
    <body class="font-sans antialiased">
        @action('layout.body_start')
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

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
        @action('layout.body_end')
    </body>
</html>
