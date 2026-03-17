<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-neutral-800 leading-tight">
                {{ __('Create New Theme') }}
            </h2>
            <a href="{{ route('themes.editor.index') }}" class="text-sm text-primary-600 hover:underline">{{ __('Back to List') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-neutral-900">
                    
                    @if($errors->any())
                        <div class="mb-6 bg-danger-50 border-l-4 border-danger-400 p-4">
                            <ul class="list-disc list-inside text-sm text-danger-700">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('themes.editor.store') }}" method="POST">
                        @csrf
                        
                        <div class="grid grid-cols-1 gap-6 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-neutral-700">Theme Name (ID)</label>
                                <input type="text" name="name" value="{{ old('name') }}" class="mt-1 block w-full rounded-md border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="my-custom-theme">
                                <p class="text-xs text-neutral-500 mt-1">Unique identifier. Only letters, numbers, and dashes.</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-neutral-700">Display Title</label>
                                <input type="text" name="title" value="{{ old('title') }}" class="mt-1 block w-full rounded-md border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="My Custom Theme">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-neutral-700">Base Theme (Clone from)</label>
                                <select name="base_theme" class="mt-1 block w-full rounded-md border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                    @foreach(\App\Models\Theme::all() as $theme)
                                        <option value="{{ $theme->id }}">{{ $theme->title }}</option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-neutral-500 mt-1">Select a theme to use as a starting point.</p>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded hover:bg-primary-700">Create Theme</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
