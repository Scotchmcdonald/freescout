<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Themes') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="themeSelector('{{ route('themes.update') }}', '{{ csrf_token() }}')">
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

                    <div class="mb-6 flex justify-between items-end">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('Select Theme') }}</h3>
                            <p class="text-sm text-gray-600">
                                {{ __('Choose a theme to customize the appearance of the application. Your preference will be remembered for future sessions.') }}
                            </p>
                        </div>
                        <div>
                            <a href="{{ route('themes.editor.index') }}" class="text-sm text-blue-600 hover:underline">Theme Editor</a>
                            @if(auth()->check() && auth()->user()->isAdmin())
                                <span class="mx-2 text-gray-300">|</span>
                                <form method="POST" action="{{ route('themes.seed') }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-sm text-blue-600 hover:underline" onclick="return confirm('Are you sure you want to re-seed themes? This will overwrite default theme configurations.')">
                                        {{ __('Reload Themes') }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <form method="POST" action="{{ route('themes.update') }}" id="theme-form">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                            @foreach($themes as $theme)
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="theme" value="{{ $theme->name }}"
                                           {{ $currentTheme === $theme->name ? 'checked' : '' }}
                                           class="peer sr-only theme-selector"
                                           @change="selectTheme('{{ $theme->name }}')">
                                    <div class="theme-selection-card rounded-2xl p-4 transition-all peer-checked:ring-2 peer-checked:ring-offset-2 h-full flex flex-col bg-white shadow-sm">
                                        <div class="flex items-center justify-between mb-3">
                                            <div class="flex items-center gap-2">
                                                <h4 class="font-medium text-gray-900">{{ $theme->title }}</h4>
                                                @if($currentTheme === $theme->name)
                                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-700">Active</span>
                                                @endif
                                            </div>
                                            <div class="theme-selection-check w-4 h-4 rounded-full border-2 border-gray-300 flex items-center justify-center">
                                                <svg class="w-2 h-2 text-white hidden peer-checked:block" fill="currentColor" viewBox="0 0 8 8">
                                                    <circle cx="4" cy="4" r="4"/>
                                                </svg>
                                            </div>
                                        </div>
                                        <p class="text-sm text-gray-600 mb-4 flex-grow">
                                            {{ $theme->is_system ? 'System Theme' : 'Custom Theme' }}
                                        </p>
                                        <div class="mt-auto">
                                            <div class="flex items-center gap-2">
                                                <div class="flex gap-1">
                                                    <!-- Light Mode Preview -->
                                                    <div class="w-6 h-6 rounded border" style="background-color: {{ $theme->config['light']['primary']['500'] ?? '#ccc' }}" title="Primary"></div>
                                                    <div class="w-6 h-6 rounded border" style="background-color: {{ $theme->config['light']['bg']['main'] ?? '#fff' }}" title="Background"></div>
                                                </div>
                                                <span class="text-gray-400">/</span>
                                                <div class="flex gap-1">
                                                    <!-- Dark Mode Preview -->
                                                    <div class="w-6 h-6 rounded border" style="background-color: {{ $theme->config['dark']['primary']['500'] ?? '#ccc' }}" title="Primary"></div>
                                                    <div class="w-6 h-6 rounded border" style="background-color: {{ $theme->config['dark']['bg']['main'] ?? '#000' }}" title="Background"></div>
                                                </div>
                                            </div>
                                            <div class="text-xs text-gray-500 mt-1">Light / Dark</div>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </form>

                    <div class="mt-12 border-t pt-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">{{ __('Theme Preview Elements') }}</h3>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Typography & Buttons -->
                            <div class="space-y-6">
                                <div class="bg-gray-50 p-4 rounded-lg border">
                                    <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Typography</h4>
                                    <h1 class="text-4xl font-bold mb-2">Heading 1</h1>
                                    <h2 class="text-3xl font-bold mb-2">Heading 2</h2>
                                    <h3 class="text-2xl font-bold mb-2">Heading 3</h3>
                                    <p class="text-gray-600">This is a paragraph of text to demonstrate body copy. It contains <strong>bold text</strong>, <em>italic text</em>, and <a href="#" class="text-blue-600 hover:underline">links</a>.</p>
                                </div>

                                <div class="bg-gray-50 p-4 rounded-lg border">
                                    <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Buttons</h4>
                                    <div class="flex flex-wrap gap-2">
                                        <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Primary</button>
                                        <button class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">Secondary</button>
                                        <button class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Success</button>
                                        <button class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Danger</button>
                                        <button class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded hover:bg-gray-50">Outline</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Forms & Alerts -->
                            <div class="space-y-6">
                                <div class="bg-gray-50 p-4 rounded-lg border">
                                    <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Form Elements</h4>
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Text Input</label>
                                            <input type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Sample text...">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Select</label>
                                            <select class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                <option>Option 1</option>
                                                <option>Option 2</option>
                                            </select>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <label class="flex items-center">
                                                <input type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" checked>
                                                <span class="ml-2 text-sm text-gray-600">Checkbox</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input type="radio" class="border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" checked>
                                                <span class="ml-2 text-sm text-gray-600">Radio</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-gray-50 p-4 rounded-lg border">
                                    <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Alerts</h4>
                                    <div class="space-y-2">
                                        <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                                            <p class="text-sm text-blue-700">Info alert message.</p>
                                        </div>
                                        <div class="bg-blue-50 border-l-4 border-green-400 p-4">
                                            <p class="text-sm text-blue-700">Success alert message.</p>
                                        </div>
                                        <div class="bg-blue-50 border-l-4 border-yellow-400 p-4">
                                            <p class="text-sm text-blue-700">Warning alert message.</p>
                                        </div>
                                        <div class="bg-blue-50 border-l-4 border-red-400 p-4">
                                            <p class="text-sm text-blue-700">Error alert message.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
