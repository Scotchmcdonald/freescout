<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Editing Theme: ') }} {{ $theme->title }}
            </h2>
            <div class="flex gap-4">
                <a href="{{ route('themes.editor.show', $theme) }}" class="text-sm text-gray-600 hover:underline">{{ __('Cancel') }}</a>
                <button form="edit-theme-form" type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">Save Changes</button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    @if($errors->any())
                        <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4">
                            <ul class="list-disc list-inside text-sm text-red-700">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="edit-theme-form" action="{{ route('themes.editor.update', $theme) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700">Display Title</label>
                            <input type="text" name="title" value="{{ old('title', $theme->title) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

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
                                                    <input type="color" name="config[light][primary][{{ $shade }}]" value="{{ old("config.light.primary.$shade", $theme->config['light']['primary'][$shade]) }}" class="h-8 w-14 p-0 border-0 rounded cursor-pointer">
                                                    <input type="text" name="config[light][primary][{{ $shade }}]" value="{{ old("config.light.primary.$shade", $theme->config['light']['primary'][$shade]) }}" class="text-xs text-gray-500 bg-gray-50 border-gray-300 rounded w-24">
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center gap-2">
                                                    <input type="color" name="config[dark][primary][{{ $shade }}]" value="{{ old("config.dark.primary.$shade", $theme->config['dark']['primary'][$shade]) }}" class="h-8 w-14 p-0 border-0 rounded cursor-pointer">
                                                    <input type="text" name="config[dark][primary][{{ $shade }}]" value="{{ old("config.dark.primary.$shade", $theme->config['dark']['primary'][$shade]) }}" class="text-xs text-gray-500 bg-gray-50 border-gray-300 rounded w-24">
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
                                                    <input type="color" name="config[light][bg][{{ $bg }}]" value="{{ old("config.light.bg.$bg", $theme->config['light']['bg'][$bg] ?? '#ffffff') }}" class="h-8 w-14 p-0 border-0 rounded cursor-pointer">
                                                    <input type="text" name="config[light][bg][{{ $bg }}]" value="{{ old("config.light.bg.$bg", $theme->config['light']['bg'][$bg] ?? '#ffffff') }}" class="text-xs text-gray-500 bg-gray-50 border-gray-300 rounded w-24">
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center gap-2">
                                                    <input type="color" name="config[dark][bg][{{ $bg }}]" value="{{ old("config.dark.bg.$bg", $theme->config['dark']['bg'][$bg] ?? '#000000') }}" class="h-8 w-14 p-0 border-0 rounded cursor-pointer">
                                                    <input type="text" name="config[dark][bg][{{ $bg }}]" value="{{ old("config.dark.bg.$bg", $theme->config['dark']['bg'][$bg] ?? '#000000') }}" class="text-xs text-gray-500 bg-gray-50 border-gray-300 rounded w-24">
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
                                                    <input type="color" name="config[light][text][{{ $text }}]" value="{{ old("config.light.text.$text", $theme->config['light']['text'][$text]) }}" class="h-8 w-14 p-0 border-0 rounded cursor-pointer">
                                                    <input type="text" name="config[light][text][{{ $text }}]" value="{{ old("config.light.text.$text", $theme->config['light']['text'][$text]) }}" class="text-xs text-gray-500 bg-gray-50 border-gray-300 rounded w-24">
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center gap-2">
                                                    <input type="color" name="config[dark][text][{{ $text }}]" value="{{ old("config.dark.text.$text", $theme->config['dark']['text'][$text]) }}" class="h-8 w-14 p-0 border-0 rounded cursor-pointer">
                                                    <input type="text" name="config[dark][text][{{ $text }}]" value="{{ old("config.dark.text.$text", $theme->config['dark']['text'][$text]) }}" class="text-xs text-gray-500 bg-gray-50 border-gray-300 rounded w-24">
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
                                                <input type="color" name="config[light][border]" value="{{ old("config.light.border", $theme->config['light']['border']) }}" class="h-8 w-14 p-0 border-0 rounded cursor-pointer">
                                                <input type="text" name="config[light][border]" value="{{ old("config.light.border", $theme->config['light']['border']) }}" class="text-xs text-gray-500 bg-gray-50 border-gray-300 rounded w-24">
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <input type="color" name="config[dark][border]" value="{{ old("config.dark.border", $theme->config['dark']['border']) }}" class="h-8 w-14 p-0 border-0 rounded cursor-pointer">
                                                <input type="text" name="config[dark][border]" value="{{ old("config.dark.border", $theme->config['dark']['border']) }}" class="text-xs text-gray-500 bg-gray-50 border-gray-300 rounded w-24">
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
                                                        <input type="color" name="config[light][status][{{ $status }}][{{ $type }}]" value="{{ old("config.light.status.$status.$type", $theme->config['light']['status'][$status][$type] ?? ($status === 'error' ? ($type === 'bg' ? '#fee2e2' : '#991b1b') : '')) }}" class="h-8 w-14 p-0 border-0 rounded cursor-pointer">
                                                        <input type="text" name="config[light][status][{{ $status }}][{{ $type }}]" value="{{ old("config.light.status.$status.$type", $theme->config['light']['status'][$status][$type] ?? ($status === 'error' ? ($type === 'bg' ? '#fee2e2' : '#991b1b') : '')) }}" class="text-xs text-gray-500 bg-gray-50 border-gray-300 rounded w-24">
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center gap-2">
                                                        <input type="color" name="config[dark][status][{{ $status }}][{{ $type }}]" value="{{ old("config.dark.status.$status.$type", $theme->config['dark']['status'][$status][$type] ?? ($status === 'error' ? ($type === 'bg' ? '#450a0a' : '#fca5a5') : '')) }}" class="h-8 w-14 p-0 border-0 rounded cursor-pointer">
                                                        <input type="text" name="config[dark][status][{{ $status }}][{{ $type }}]" value="{{ old("config.dark.status.$status.$type", $theme->config['dark']['status'][$status][$type] ?? ($status === 'error' ? ($type === 'bg' ? '#450a0a' : '#fca5a5') : '')) }}" class="text-xs text-gray-500 bg-gray-50 border-gray-300 rounded w-24">
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
