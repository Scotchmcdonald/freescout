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
                        <div class="mb-6 border-l-4 p-4" style="background-color: var(--theme-status-error-bg); border-color: var(--theme-status-error-bg)">
                            <p class="text-sm" style="color: var(--theme-status-error-text)">{{ session('error') }}</p>
                        </div>
                    @endif
                    
                    @if(session('success'))
                        <div class="mb-6 border-l-4 p-4" style="background-color: var(--theme-status-success-bg); border-color: var(--theme-status-success-bg)">
                            <p class="text-sm" style="color: var(--theme-status-success-text)">{{ session('success') }}</p>
                        </div>
                    @endif

                    <div class="mb-8 flex justify-end">
                        <a href="{{ route('themes.editor.create') }}" class="px-4 py-2 text-white rounded transition" style="background-color: var(--theme-primary-600)">Create New Theme</a>
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
                                    <tr style="{{ isset($activeTheme) && $activeTheme === $theme->name ? 'background-color: var(--theme-primary-50)' : '' }}">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $theme->title }}
                                                @if(isset($activeTheme) && $activeTheme === $theme->name)
                                                    <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full" style="background-color: var(--theme-primary-100); color: var(--theme-primary-700)">Active</span>
                                                @endif
                                            </div>
                                            <div class="text-sm text-gray-500">{{ $theme->name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($theme->is_system)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" style="background-color: var(--theme-bg-input); color: var(--theme-text-muted)">System</span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" style="background-color: var(--theme-status-success-bg); color: var(--theme-status-success-text)">User</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            @if($theme->is_system)
                                                <a href="{{ route('themes.editor.show', $theme) }}" class="mr-3" style="color: var(--theme-text-muted)">View Palette</a>
                                            @else
                                                <a href="{{ route('themes.editor.edit', $theme) }}" class="mr-3" style="color: var(--theme-primary-600)">Edit Palette</a>
                                                <form action="{{ route('themes.editor.destroy', $theme) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this theme?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" style="color: var(--theme-status-error-text)">Delete</button>
                                                </form>
                                            @endif
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
