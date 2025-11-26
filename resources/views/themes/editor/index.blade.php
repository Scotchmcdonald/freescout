<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Theme Editor') }}
            </h2>
            <a href="{{ route('themes') }}" class="text-sm text-blue-600 hover:underline">{{ __('Back to Selection') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    @if(session('error'))
                        <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4">
                            <p class="text-sm text-red-700">{{ session('error') }}</p>
                        </div>
                    @endif
                    
                    @if(session('success'))
                        <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4">
                            <p class="text-sm text-green-700">{{ session('success') }}</p>
                        </div>
                    @endif

                    <div class="mb-8 flex justify-end">
                        <a href="{{ route('themes.editor.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Create New Theme</a>
                    </div>

                    <h3 class="text-lg font-medium mb-4">Available Themes</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($themes as $theme)
                                    <tr class="{{ isset($activeTheme) && $activeTheme === $theme->name ? 'bg-blue-50' : '' }}">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $theme->title }}
                                                @if(isset($activeTheme) && $activeTheme === $theme->name)
                                                    <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Active</span>
                                                @endif
                                            </div>
                                            <div class="text-sm text-gray-500">{{ $theme->name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($theme->is_system)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">System</span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">User</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('themes.editor.show', $theme) }}" class="text-blue-600 hover:text-blue-900">Edit Palette</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
