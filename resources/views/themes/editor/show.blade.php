<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Theme Details: ') }} {{ $theme->title }}
            </h2>
            <div class="flex gap-4">
                <a href="{{ route('themes.editor.index') }}" class="text-sm text-gray-600 hover:underline">{{ __('Back to List') }}</a>
                @if(!$theme->is_system)
                    <a href="{{ route('themes.editor.edit', $theme) }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">Edit Theme</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    @if($theme->is_system)
                        <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                            <p class="text-sm text-yellow-700">This is a system theme. Colors are currently read-only in this editor.</p>
                        </div>
                    @endif

                    <div class="overflow-x-auto border rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/3">Property</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/3">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                            Light Mode
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/3">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                                            Dark Mode
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <!-- Primary Colors -->
                                <tr class="bg-gray-50"><td colspan="3" class="px-6 py-2 text-xs font-bold text-gray-500 uppercase">Primary Colors</td></tr>
                                @foreach(['50', '100', '500', '600', '700'] as $shade)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Primary {{ $shade }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <input type="color" value="{{ $theme->config['light']['primary'][$shade] }}" disabled class="h-8 w-14 p-0 border-0 rounded cursor-not-allowed">
                                                <code class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">{{ $theme->config['light']['primary'][$shade] }}</code>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <input type="color" value="{{ $theme->config['dark']['primary'][$shade] }}" disabled class="h-8 w-14 p-0 border-0 rounded cursor-not-allowed">
                                                <code class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">{{ $theme->config['dark']['primary'][$shade] }}</code>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach

                                <!-- Backgrounds -->
                                <tr class="bg-gray-50"><td colspan="3" class="px-6 py-2 text-xs font-bold text-gray-500 uppercase">Backgrounds</td></tr>
                                @foreach(['main', 'card', 'input', 'hover'] as $bg)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ ucfirst($bg) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <input type="color" value="{{ $theme->config['light']['bg'][$bg] ?? '#ffffff' }}" disabled class="h-8 w-14 p-0 border-0 rounded cursor-not-allowed">
                                                <code class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">{{ $theme->config['light']['bg'][$bg] ?? 'N/A' }}</code>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <input type="color" value="{{ $theme->config['dark']['bg'][$bg] ?? '#000000' }}" disabled class="h-8 w-14 p-0 border-0 rounded cursor-not-allowed">
                                                <code class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">{{ $theme->config['dark']['bg'][$bg] ?? 'N/A' }}</code>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach

                                <!-- Text -->
                                <tr class="bg-gray-50"><td colspan="3" class="px-6 py-2 text-xs font-bold text-gray-500 uppercase">Text</td></tr>
                                @foreach(['main', 'muted', 'inverted'] as $text)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ ucfirst($text) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <input type="color" value="{{ $theme->config['light']['text'][$text] }}" disabled class="h-8 w-14 p-0 border-0 rounded cursor-not-allowed">
                                                <code class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">{{ $theme->config['light']['text'][$text] }}</code>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <input type="color" value="{{ $theme->config['dark']['text'][$text] }}" disabled class="h-8 w-14 p-0 border-0 rounded cursor-not-allowed">
                                                <code class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">{{ $theme->config['dark']['text'][$text] }}</code>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach

                                <!-- Border -->
                                <tr class="bg-gray-50"><td colspan="3" class="px-6 py-2 text-xs font-bold text-gray-500 uppercase">Border</td></tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Border Color</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <input type="color" value="{{ $theme->config['light']['border'] }}" disabled class="h-8 w-14 p-0 border-0 rounded cursor-not-allowed">
                                            <code class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">{{ $theme->config['light']['border'] }}</code>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <input type="color" value="{{ $theme->config['dark']['border'] }}" disabled class="h-8 w-14 p-0 border-0 rounded cursor-not-allowed">
                                            <code class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">{{ $theme->config['dark']['border'] }}</code>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Status -->
                                <tr class="bg-gray-50"><td colspan="3" class="px-6 py-2 text-xs font-bold text-gray-500 uppercase">Status Colors</td></tr>
                                @foreach(['success', 'warning', 'info', 'error'] as $status)
                                    @foreach(['bg', 'text'] as $type)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ ucfirst($status) }} {{ ucfirst($type) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center gap-2">
                                                    <input type="color" value="{{ $theme->config['light']['status'][$status][$type] }}" disabled class="h-8 w-14 p-0 border-0 rounded cursor-not-allowed">
                                                    <code class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">{{ $theme->config['light']['status'][$status][$type] }}</code>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center gap-2">
                                                    <input type="color" value="{{ $theme->config['dark']['status'][$status][$type] }}" disabled class="h-8 w-14 p-0 border-0 rounded cursor-not-allowed">
                                                    <code class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">{{ $theme->config['dark']['status'][$status][$type] }}</code>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
