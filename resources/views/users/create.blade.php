<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create New User') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if($errors->any())
                        <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4">
                            <ul class="list-disc list-inside text-sm text-red-700">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <form method="POST" action="{{ route('users.store') }}">
                        @csrf
                        
                        <div class="space-y-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('First Name') }} *
                                    </label>
                                    <input type="text" name="first_name" id="first_name" required
                                           value="{{ old('first_name') }}"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('Last Name') }}
                                    </label>
                                    <input type="text" name="last_name" id="last_name"
                                           value="{{ old('last_name') }}"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ __('Email') }} *
                                </label>
                                <input type="email" name="email" id="email" required
                                       value="{{ old('email') }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ __('Password') }} *
                                </label>
                                <input type="password" name="password" id="password" required
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p class="mt-1 text-sm text-gray-500">Minimum 8 characters</p>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="role" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('Role') }} *
                                    </label>
                                    <select name="role" id="role" required
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="2" {{ old('role') == 2 ? 'selected' : '' }}>User</option>
                                        <option value="1" {{ old('role') == 1 ? 'selected' : '' }}>Admin</option>
                                    </select>
                                    <p class="mt-1 text-sm text-gray-500">
                                        Admins have full access to all mailboxes and settings
                                    </p>
                                </div>
                                
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('Status') }} *
                                    </label>
                                    <select name="status" id="status" required
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="1" {{ old('status', 1) == 1 ? 'selected' : '' }}>Active</option>
                                        <option value="2" {{ old('status') == 2 ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="job_title" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('Job Title') }}
                                    </label>
                                    <input type="text" name="job_title" id="job_title"
                                           value="{{ old('job_title') }}"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('Phone') }}
                                    </label>
                                    <input type="text" name="phone" id="phone"
                                           value="{{ old('phone') }}"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="timezone" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('Timezone') }}
                                    </label>
                                    <select name="timezone" id="timezone"
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">System Default</option>
                                        <option value="America/New_York" {{ old('timezone') == 'America/New_York' ? 'selected' : '' }}>America/New_York</option>
                                        <option value="America/Chicago" {{ old('timezone') == 'America/Chicago' ? 'selected' : '' }}>America/Chicago</option>
                                        <option value="America/Denver" {{ old('timezone') == 'America/Denver' ? 'selected' : '' }}>America/Denver</option>
                                        <option value="America/Los_Angeles" {{ old('timezone') == 'America/Los_Angeles' ? 'selected' : '' }}>America/Los_Angeles</option>
                                        <option value="Europe/London" {{ old('timezone') == 'Europe/London' ? 'selected' : '' }}>Europe/London</option>
                                        <option value="Europe/Paris" {{ old('timezone') == 'Europe/Paris' ? 'selected' : '' }}>Europe/Paris</option>
                                        <option value="Asia/Tokyo" {{ old('timezone') == 'Asia/Tokyo' ? 'selected' : '' }}>Asia/Tokyo</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="locale" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('Language') }}
                                    </label>
                                    <select name="locale" id="locale"
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">System Default</option>
                                        <option value="en" {{ old('locale') == 'en' ? 'selected' : '' }}>English</option>
                                        <option value="es" {{ old('locale') == 'es' ? 'selected' : '' }}>Spanish</option>
                                        <option value="fr" {{ old('locale') == 'fr' ? 'selected' : '' }}>French</option>
                                        <option value="de" {{ old('locale') == 'de' ? 'selected' : '' }}>German</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-6 flex justify-end gap-3">
                            <a href="{{ route('users.index') }}" 
                               class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                {{ __('Cancel') }}
                            </a>
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                {{ __('Create User') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
