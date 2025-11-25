<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Theme Preview') }}: {{ ucfirst($theme) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('Preview Mode') }}</h3>
                        <p class="text-sm text-gray-600">
                            {{ __('You are previewing the') }} <strong>{{ ucfirst($theme) }}</strong> {{ __('theme.') }}
                            {{ __('This preview is temporary and will not affect your saved theme preference.') }}
                        </p>
                    </div>

                    <div class="mt-6 border-t pt-6">
                        <h4 class="text-md font-medium text-gray-900 mb-4">{{ __('Sample Components') }}</h4>
                        
                        <div class="space-y-4">
                            <!-- Sample Button -->
                            <div>
                                <h5 class="text-sm font-medium text-gray-700 mb-2">{{ __('Buttons') }}</h5>
                                <div class="flex gap-2">
                                    <button class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                        {{ __('Primary Button') }}
                                    </button>
                                    <button class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">
                                        {{ __('Secondary Button') }}
                                    </button>
                                    <button class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                                        {{ __('Danger Button') }}
                                    </button>
                                </div>
                            </div>

                            <!-- Sample Form Elements -->
                            <div>
                                <h5 class="text-sm font-medium text-gray-700 mb-2">{{ __('Form Elements') }}</h5>
                                <div class="max-w-sm space-y-3">
                                    <input type="text" placeholder="{{ __('Text input') }}" 
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <select class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option>{{ __('Select option') }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Sample Alert -->
                            <div>
                                <h5 class="text-sm font-medium text-gray-700 mb-2">{{ __('Alerts') }}</h5>
                                <div class="space-y-2">
                                    <div class="bg-green-50 border-l-4 border-green-400 p-4">
                                        <p class="text-sm text-green-700">{{ __('Success message example') }}</p>
                                    </div>
                                    <div class="bg-red-50 border-l-4 border-red-400 p-4">
                                        <p class="text-sm text-red-700">{{ __('Error message example') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <a href="{{ route('themes') }}" 
                           class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                            {{ __('Back to Themes') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
