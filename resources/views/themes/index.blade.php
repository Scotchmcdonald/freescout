<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Themes & Style Guide') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="themeSelector('{{ route('themes.update') }}', '{{ csrf_token() }}')">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(session('success'))
                        <div class="mb-6 border-l-4 p-4" style="background-color: var(--theme-status-success-bg); border-color: var(--theme-status-success-bg); color: var(--theme-status-success-text)">
                            <p class="text-sm">{{ session('success') }}</p>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-6 border-l-4 p-4" style="background-color: var(--theme-status-error-bg); border-color: var(--theme-status-error-bg); color: var(--theme-status-error-text)">
                            <p class="text-sm">{{ session('error') }}</p>
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

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($themes as $theme)
                                <label class="relative cursor-pointer group">
                                    <input type="radio" name="theme" value="{{ $theme->name }}"
                                           {{ $currentTheme === $theme->name ? 'checked' : '' }}
                                           class="peer sr-only theme-selector"
                                           @change="selectTheme('{{ $theme->name }}')">
                                    <div class="theme-selection-card rounded-2xl p-4 transition-all peer-checked:ring-2 peer-checked:ring-offset-2 h-full flex flex-col bg-white shadow-sm border border-gray-200">
                                        <div class="flex items-center justify-between mb-3">
                                            <div class="flex items-center gap-2">
                                                <h4 class="font-medium text-gray-900">{{ $theme->title }}</h4>
                                                @if($currentTheme === $theme->name)
                                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold" style="background-color: var(--theme-primary-100); color: var(--theme-primary-700)">Active</span>
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
                </div>
            </div>

            <!-- Theme Palette -->
            <section class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold mb-4 border-b pb-2">Theme Palette</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Primary Colors -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-500 mb-2">Primary Colors</h4>
                        <div class="grid grid-cols-5 gap-2">
                            <div class="space-y-1 text-center">
                                <div class="w-full h-12 rounded border" style="background-color: var(--theme-primary-50)"></div>
                                <span class="text-xs text-gray-500">50</span>
                            </div>
                            <div class="space-y-1 text-center">
                                <div class="w-full h-12 rounded border" style="background-color: var(--theme-primary-100)"></div>
                                <span class="text-xs text-gray-500">100</span>
                            </div>
                            <div class="space-y-1 text-center">
                                <div class="w-full h-12 rounded shadow-sm" style="background-color: var(--theme-primary-500)"></div>
                                <span class="text-xs text-gray-500">500</span>
                            </div>
                            <div class="space-y-1 text-center">
                                <div class="w-full h-12 rounded shadow-sm" style="background-color: var(--theme-primary-600)"></div>
                                <span class="text-xs text-gray-500">600</span>
                            </div>
                            <div class="space-y-1 text-center">
                                <div class="w-full h-12 rounded shadow-sm" style="background-color: var(--theme-primary-700)"></div>
                                <span class="text-xs text-gray-500">700</span>
                            </div>
                        </div>
                    </div>

                    <!-- Status Colors -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-500 mb-2">Status Colors</h4>
                        <div class="grid grid-cols-4 gap-4">
                            <div class="space-y-2">
                                <div class="p-3 rounded text-sm font-medium text-center border" style="background-color: var(--theme-status-success-bg); color: var(--theme-status-success-text); border-color: var(--theme-status-success-bg)">Success</div>
                                <div class="h-2 w-full rounded-full" style="background-color: var(--theme-status-success-text)"></div>
                            </div>
                            <div class="space-y-2">
                                <div class="p-3 rounded text-sm font-medium text-center border" style="background-color: var(--theme-status-warning-bg); color: var(--theme-status-warning-text); border-color: var(--theme-status-warning-bg)">Warning</div>
                                <div class="h-2 w-full rounded-full" style="background-color: var(--theme-status-warning-text)"></div>
                            </div>
                            <div class="space-y-2">
                                <div class="p-3 rounded text-sm font-medium text-center border" style="background-color: var(--theme-status-error-bg); color: var(--theme-status-error-text); border-color: var(--theme-status-error-bg)">Danger</div>
                                <div class="h-2 w-full rounded-full" style="background-color: var(--theme-status-error-text)"></div>
                            </div>
                            <div class="space-y-2">
                                <div class="p-3 rounded text-sm font-medium text-center border" style="background-color: var(--theme-status-info-bg); color: var(--theme-status-info-text); border-color: var(--theme-status-info-bg)">Info</div>
                                <div class="h-2 w-full rounded-full" style="background-color: var(--theme-status-info-text)"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Status Badges -->
            <section class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold mb-4 border-b pb-2">Status Badges</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-500 mb-2">Success</h4>
                        <div class="space-y-2">
                            <x-status-badge status="success" />
                            <x-status-badge status="completed" />
                            <x-status-badge status="synced" />
                        </div>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-500 mb-2">Processing (Pulse)</h4>
                        <div class="space-y-2">
                            <x-status-badge status="processing" />
                            <x-status-badge status="migrating" />
                            <x-status-badge status="scanning" />
                        </div>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-500 mb-2">Warning</h4>
                        <div class="space-y-2">
                            <x-status-badge status="warning" />
                            <x-status-badge status="pending" />
                            <x-status-badge status="paused" />
                        </div>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-500 mb-2">Danger</h4>
                        <div class="space-y-2">
                            <x-status-badge status="danger" />
                            <x-status-badge status="failed" />
                            <x-status-badge status="error" />
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <h4 class="text-sm font-semibold text-gray-500 mb-2">Custom Text</h4>
                    <x-status-badge status="success" text="Custom Success Text" />
                </div>
            </section>

            <!-- Progress Bars -->
            <section class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold mb-4 border-b pb-2">Progress Bars</h3>
                <div class="space-y-6">
                    <x-progress-bar :percent="25" label="25% Progress" />
                    <x-progress-bar :percent="50" label="50% Progress (Warning)" color="warning" />
                    <x-progress-bar :percent="75" label="75% Progress (Success)" color="success" />
                    <x-progress-bar :percent="100" label="100% Progress (Danger)" color="danger" />
                    
                    <div>
                        <h4 class="text-sm font-semibold text-gray-500 mb-2">Alpine.js Binding</h4>
                        <div x-data="{ progress: 0 }" x-init="setInterval(() => { progress = (progress + 10) % 110 }, 1000)">
                            <x-progress-bar alpine="progress" label="Live Progress" />
                        </div>
                    </div>
                </div>
            </section>

            <!-- Smart Stepper -->
            <section class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold mb-4 border-b pb-2">Smart Stepper</h3>
                @php
                    $steps = ['Discovery', 'Mapping', 'Verification', 'Execution'];
                @endphp
                <div class="space-y-8">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-500 mb-2">Step 1</h4>
                        <x-smart-stepper :steps="$steps" :currentStep="1" />
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-500 mb-2">Step 2</h4>
                        <x-smart-stepper :steps="$steps" :currentStep="2" />
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-500 mb-2">Step 3</h4>
                        <x-smart-stepper :steps="$steps" :currentStep="3" />
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-500 mb-2">Completed</h4>
                        <x-smart-stepper :steps="$steps" :currentStep="5" />
                    </div>
                </div>
            </section>

            <!-- Troubleshooting Cards -->
            <section class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold mb-4 border-b pb-2">Troubleshooting Cards</h3>
                <div class="space-y-4">
                    <x-troubleshooting-card 
                        title="Connection Failed" 
                        body="Unable to connect to the IMAP server. Please check your credentials and try again." 
                        type="error"
                        actionText="Retry Connection"
                    />
                    
                    <x-troubleshooting-card 
                        title="System Throttled" 
                        body="The remote server is limiting connection attempts. We have paused the migration." 
                        type="warning"
                        code="429 Too Many Requests"
                    />

                    <x-troubleshooting-card 
                        title="Migration Complete" 
                        body="All mailboxes have been successfully migrated." 
                        type="success"
                        actionText="View Report"
                    />
                </div>
            </section>

            <!-- Activity Drawer Trigger -->
            <section class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold mb-4 border-b pb-2">Activity Drawer</h3>
                <button @click="$dispatch('open-activity-drawer')" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                    Open Activity Drawer
                </button>
            </section>

            <!-- Legacy Preview Elements -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" x-data="{ open: false }">
                <button @click="open = !open" class="flex items-center justify-between w-full text-left">
                    <h3 class="text-lg font-bold text-gray-900">{{ __('Legacy Theme Preview Elements') }}</h3>
                    <svg class="w-5 h-5 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div x-show="open" class="mt-6 border-t pt-6" style="display: none;">
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
                                    <button class="px-4 py-2 text-white rounded hover:opacity-90" style="background-color: var(--theme-primary-600)">Primary</button>
                                    <button class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">Secondary</button>
                                    <button class="px-4 py-2 text-white rounded hover:opacity-90" style="background-color: var(--theme-status-success-text)">Success</button>
                                    <button class="px-4 py-2 text-white rounded hover:opacity-90" style="background-color: var(--theme-status-error-text)">Danger</button>
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
                                    <div class="border-l-4 p-4" style="background-color: var(--theme-status-info-bg); border-color: var(--theme-status-info-bg); color: var(--theme-status-info-text)">
                                        <p class="text-sm">Info alert message.</p>
                                    </div>
                                    <div class="border-l-4 p-4" style="background-color: var(--theme-status-success-bg); border-color: var(--theme-status-success-bg); color: var(--theme-status-success-text)">
                                        <p class="text-sm">Success alert message.</p>
                                    </div>
                                    <div class="border-l-4 p-4" style="background-color: var(--theme-status-warning-bg); border-color: var(--theme-status-warning-bg); color: var(--theme-status-warning-text)">
                                        <p class="text-sm">Warning alert message.</p>
                                    </div>
                                    <div class="border-l-4 p-4" style="background-color: var(--theme-status-error-bg); border-color: var(--theme-status-error-bg); color: var(--theme-status-error-text)">
                                        <p class="text-sm">Error alert message.</p>
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
