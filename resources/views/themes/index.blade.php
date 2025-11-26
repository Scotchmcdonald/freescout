<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Themes') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(session('success'))
                        <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4">
                            <p class="text-sm text-green-700">{{ session('success') }}</p>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4">
                            <p class="text-sm text-red-700">{{ session('error') }}</p>
                        </div>
                    @endif

                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('Select Theme') }}</h3>
                        <p class="text-sm text-gray-600">
                            {{ __('Choose a theme to customize the appearance of the application. Your preference will be remembered for future sessions.') }}
                        </p>
                    </div>

                    <form method="POST" action="{{ route('themes.update') }}">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- Default Theme Option -->
                            <label class="relative cursor-pointer">
                                <input type="radio" name="theme" value="default"
                                       {{ !$currentTheme ? 'checked' : '' }}
                                       class="peer sr-only">
                                <div class="border-2 rounded-lg p-4 transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:border-gray-400">
                                    <div class="flex items-center justify-between mb-3">
                                        <h4 class="font-medium text-gray-900">{{ __('Default') }}</h4>
                                        <div class="w-4 h-4 rounded-full border-2 border-gray-300 peer-checked:border-blue-500 peer-checked:bg-blue-500 flex items-center justify-center">
                                            <svg class="w-2 h-2 text-white hidden peer-checked:block" fill="currentColor" viewBox="0 0 8 8">
                                                <circle cx="4" cy="4" r="4"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-600">{{ __('The default application theme') }}</p>
                                    <div class="mt-3 flex gap-1">
                                        <div class="w-6 h-6 rounded bg-blue-600"></div>
                                        <div class="w-6 h-6 rounded bg-gray-100"></div>
                                        <div class="w-6 h-6 rounded bg-white border"></div>
                                    </div>
                                </div>
                            </label>

                            <!-- Available Themes -->
                            @forelse($themes as $theme)
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="theme" value="{{ $theme['name'] }}"
                                           {{ $currentTheme === $theme['name'] ? 'checked' : '' }}
                                           class="peer sr-only">
                                    <div class="border-2 rounded-lg p-4 transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:border-gray-400">
                                        <div class="flex items-center justify-between mb-3">
                                            <h4 class="font-medium text-gray-900">{{ $theme['title'] }}</h4>
                                            <div class="w-4 h-4 rounded-full border-2 border-gray-300 peer-checked:border-blue-500 peer-checked:bg-blue-500"></div>
                                        </div>
                                        @if($theme['description'])
                                            <p class="text-sm text-gray-600">{{ $theme['description'] }}</p>
                                        @else
                                            <p class="text-sm text-gray-500 italic">{{ __('No description available') }}</p>
                                        @endif
                                        @if($theme['preview'])
                                            <div class="mt-3">
                                                <img src="{{ $theme['preview'] }}" alt="{{ $theme['title'] }} preview" class="rounded border w-full h-20 object-cover">
                                            </div>
                                        @else
                                            <div class="mt-3 flex gap-1">
                                                <div class="w-6 h-6 rounded bg-gray-400"></div>
                                                <div class="w-6 h-6 rounded bg-gray-200"></div>
                                                <div class="w-6 h-6 rounded bg-gray-100"></div>
                                            </div>
                                        @endif
                                    </div>
                                </label>
                            @empty
                                {{-- No custom themes message shown only if themes array is empty --}}
                            @endforelse
                        </div>

                        @if(count($themes) === 0)
                            <div class="mt-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <p class="text-sm text-gray-600">
                                    <span class="font-medium">{{ __('No custom themes available.') }}</span>
                                    {{ __('To add themes, create theme folders in the') }}
                                    <code class="px-1 py-0.5 bg-gray-100 rounded text-xs">themes/</code>
                                    {{ __('directory.') }}
                                </p>
                            </div>
                        @endif

                        <div class="mt-6 flex justify-end">
                            <button type="submit"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                {{ __('Save Theme') }}
                            </button>
                        </div>
                    </form>

                    <div class="mt-8 border-t pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('Creating Custom Themes') }}</h3>
                        <div class="prose prose-sm text-gray-600">
                            <p>{{ __('To create a custom theme:') }}</p>
                            <ol class="list-decimal list-inside space-y-1 mt-2">
                                <li>{{ __('Create a folder in the') }} <code class="px-1 py-0.5 bg-gray-100 rounded text-xs">themes/</code> {{ __('directory') }}</li>
                                <li>{{ __('Add your custom views in') }} <code class="px-1 py-0.5 bg-gray-100 rounded text-xs">themes/your-theme/views/</code></li>
                                <li>{{ __('Optionally, add a') }} <code class="px-1 py-0.5 bg-gray-100 rounded text-xs">theme.json</code> {{ __('file with title and description') }}</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
