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
        <div class="min-h-screen bg-gray-100" x-data="guidedTour" @start-tour.window="startTour($event.detail.tourId)" @dismiss-tour.window="dismissTour($event.detail.tourId)">
            <x-layouts.navigation />
            
            <!-- Impersonation Banner -->
            <x-impersonation-banner />
            
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
                @if(session('success'))
                   <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
                        <div class="bg-green-50 border-l-4 border-green-400 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                                </div>
                            </div>
                        </div>
                   </div>
                @endif

                @hasSection('content')
                    @yield('content')
                @else
                    {{ $slot ?? '' }}
                @endif
            </main>
        </div>
        
        <!-- Global Activity Drawer -->
        <x-activity-drawer />
        
        <!-- Contextual Help Trigger (Bottom Right) -->
        @php
            $currentRoute = Route::currentRouteName();
            $suggestedTour = null;
            $kbSections = config('knowledgebase.features.sections', []);
            foreach ($kbSections as $section) {
                foreach ($section['pages'] ?? [] as $page) {
                    if (in_array($currentRoute, $page['routes'] ?? []) && isset($page['tour_id'])) {
                        $suggestedTour = [
                            'id' => $page['tour_id'],
                            'name' => $page['name'] . ' Tour',
                        ];
                        break 2;
                    }
                }
            }
            
            // Check if the user has completed this tour
            $tourIsCompleted = false;
            if ($suggestedTour && Auth::check()) {
                $tourProgress = \Modules\KnowledgeBase\Models\UserTourProgress::where('user_id', Auth::id())
                    ->where('tour_id', $suggestedTour['id'])
                    ->first();
                $tourIsCompleted = $tourProgress && $tourProgress->is_completed;
            }
        @endphp

        @if($suggestedTour && !$tourIsCompleted)
        <div class="fixed bottom-4 right-4 z-50 flex flex-col items-end gap-2" 
             x-data="{ showCard: false }" 
             @click.outside="showCard = false">
            
            <div x-show="showCard" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-2"
                 class="w-80 bg-white dark:bg-gray-800 rounded-lg shadow-xl p-4 border border-gray-200 dark:border-gray-700"
                 style="display: none;">
                <h3 class="font-bold text-lg mb-2 text-gray-900 dark:text-gray-100">{{ __('Interactive Tour Available') }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    {{ __('Learn about the features on this page with a guided tour.') }}
                </p>

                <div class="mb-4">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">{{ __($suggestedTour['name']) }}</p>
                    
                    <div class="flex flex-col gap-2">
                        <button @click="$dispatch('start-tour', { tourId: '{{ $suggestedTour['id'] }}' }); showCard = false" 
                                class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ __('Start Tour') }}
                        </button>
                        
                        <button @click="$dispatch('dismiss-tour', { tourId: '{{ $suggestedTour['id'] }}' }); showCard = false" 
                                class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors flex items-center justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ __('Mark as Complete') }}
                        </button>
                    </div>
                </div>
                
                <div class="flex justify-end items-center mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <button @click="showCard = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-sm">{{ __('Close') }}</button>
                </div>
            </div>
            
            <button @click="showCard = !showCard" class="bg-white dark:bg-gray-800 p-3 rounded-full shadow-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200" title="{{ __('Need Help?') }}">
                <svg class="h-6 w-6 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
                </svg>
            </button>
        </div>
        @endif

        <!-- Toast Container -->
        <div id="toast-container" class="fixed bottom-4 right-4 z-50 flex flex-col gap-2" style="pointer-events: none;"></div>

        <!-- Global Confirmation Modal -->
        <div x-data="{ 
            show: false, 
            title: '', 
            message: '', 
            onConfirm: null,
            open(title, message, callback) {
                this.title = title;
                this.message = message;
                this.onConfirm = callback;
                this.show = true;
            },
            confirm() {
                if (this.onConfirm) this.onConfirm();
                this.show = false;
            },
            close() {
                this.show = false;
            }
        }"
        @open-confirm-modal.window="open($event.detail.title, $event.detail.message, $event.detail.onConfirm)"
        x-show="show" 
        style="display: none;"
        class="fixed inset-0 z-50 overflow-y-auto" 
        aria-labelledby="modal-title" 
        role="dialog" 
        aria-modal="true">
            <!-- Backdrop -->
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="close()"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title" x-text="title"></h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500" x-text="message"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" @click="confirm()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Confirm
                        </button>
                        <button type="button" @click="close()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        window.showToast = function(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            
            const bgColor = type === 'success' ? 'bg-green-500' : (type === 'error' ? 'bg-red-500' : 'bg-blue-500');
            const icon = type === 'success' 
                ? '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>'
                : (type === 'error' 
                    ? '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>'
                    : '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>');

            toast.className = `${bgColor} text-white px-4 py-3 rounded-lg shadow-lg flex items-center transform transition-all duration-300 translate-y-10 opacity-0 pointer-events-auto`;
            toast.innerHTML = `${icon}<span class="font-bold text-sm">${message}</span>`;
            
            container.appendChild(toast);
            
            // Animate in
            requestAnimationFrame(() => {
                toast.classList.remove('translate-y-10', 'opacity-0');
            });
            
            // Remove after 3 seconds
            setTimeout(() => {
                toast.classList.add('translate-y-10', 'opacity-0');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 3000);
        }

        window.confirmAction = function(title, message, callback) {
            window.dispatchEvent(new CustomEvent('open-confirm-modal', { 
                detail: { title, message, onConfirm: callback } 
            }));
        }
        </script>

        @if(session('success'))
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    window.showToast("{{ session('success') }}", 'success');
                });
            </script>
        @endif

        @if(session('error'))
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    window.showToast("{{ session('error') }}", 'error');
                });
            </script>
        @endif

        @if(view()->exists('knowledgebase::partials.guided-tour'))
            @include('knowledgebase::partials.guided-tour')
        @endif

        @action('layout.body_end')
        @stack('scripts')
    </body>
</html>
