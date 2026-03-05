<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit User') }} - {{ $user->getFullName() }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if($errors->any())
                        <div class="mb-6 border-l-4 p-4" style="background-color: var(--theme-status-error-bg); border-color: var(--theme-status-error-bg)">
                            <ul class="list-disc list-inside text-sm" style="color: var(--theme-status-error-text)">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    @if(session('success'))
                        <div class="mb-6 border-l-4 p-4" style="background-color: var(--theme-status-success-bg); border-color: var(--theme-status-success-bg)">
                            <p class="text-sm" style="color: var(--theme-status-success-text)">{{ session('success') }}</p>
                        </div>
                    @endif
                    
                    <form method="POST" action="{{ route('users.update', $user) }}">
                        @csrf
                        @method('PATCH')
                        
                        <div class="space-y-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('First Name') }} *
                                    </label>
                                    <input type="text" name="first_name" id="first_name" required
                                           value="{{ old('first_name', $user->first_name) }}"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('Last Name') }}
                                    </label>
                                    <input type="text" name="last_name" id="last_name"
                                           value="{{ old('last_name', $user->last_name) }}"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ __('Email') }} *
                                </label>
                                <input type="email" name="email" id="email" required
                                       value="{{ old('email', $user->email) }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ __('Password') }}
                                </label>
                                <input type="password" name="password" id="password"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <p class="mt-1 text-sm text-gray-500">Leave blank to keep current password</p>
                            </div>
                            
                            @if(auth()->check() && auth()->user()->isAdmin())
                                @php
                                    $currentType = old('type', $user->type ?? 1);
                                    $currentClientRole = old('client_role', $clientRoleName ?? 'Client User');
                                @endphp

                                {{-- User Type --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('User Type') }} *</label>
                                    <div class="flex rounded-md shadow-sm" role="group">
                                        <label class="flex-1 cursor-pointer">
                                            <input type="radio" name="type" value="1" id="type_internal"
                                                   class="sr-only peer"
                                                   {{ $currentType == 1 ? 'checked' : '' }}>
                                            <span class="block text-center px-4 py-2 text-sm font-medium border border-gray-300 rounded-l-md
                                                         peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-600
                                                         hover:bg-gray-50 peer-checked:hover:bg-blue-700">
                                                🏢 {{ __('Internal Staff') }}
                                            </span>
                                        </label>
                                        <label class="flex-1 cursor-pointer">
                                            <input type="radio" name="type" value="2" id="type_external"
                                                   class="sr-only peer"
                                                   {{ $currentType == 2 ? 'checked' : '' }}>
                                            <span class="block text-center px-4 py-2 text-sm font-medium border border-gray-300 border-l-0 rounded-r-md
                                                         peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600
                                                         hover:bg-gray-50 peer-checked:hover:bg-indigo-700">
                                                🌐 {{ __('External Client') }}
                                            </span>
                                        </label>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500 {{ $currentType == 2 ? 'hidden' : '' }}" id="type-hint-internal">Internal staff have access to mailboxes and helpdesk tools.</p>
                                    <p class="mt-1 text-sm text-gray-500 {{ $currentType != 2 ? 'hidden' : '' }}" id="type-hint-external">External clients access the customer portal only.</p>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    {{-- Internal roles --}}
                                    <div id="section-internal-role" class="{{ $currentType == 2 ? 'hidden' : '' }}">
                                        <label for="role" class="block text-sm font-medium text-gray-700 mb-2">
                                            {{ __('Role') }} *
                                        </label>
                                        <select name="role" id="role"
                                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="1" {{ old('role', $user->role) == 1 ? 'selected' : '' }}>Agent (Standard access)</option>
                                            <option value="2" {{ old('role', $user->role) == 2 ? 'selected' : '' }}>Admin (Full access)</option>
                                            <option value="3" {{ old('role', $user->role) == 3 ? 'selected' : '' }}>Reporter (Read-only)</option>
                                            <option value="4" {{ old('role', $user->role) == 4 ? 'selected' : '' }}>Finance (Billing access)</option>
                                        </select>
                                    </div>

                                    {{-- External / Client roles --}}
                                    <div id="section-external-role" class="{{ $currentType != 2 ? 'hidden' : '' }}">
                                        <label for="client_role" class="block text-sm font-medium text-gray-700 mb-2">
                                            {{ __('Client Role') }} *
                                        </label>
                                        <select name="client_role" id="client_role"
                                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="Client User" {{ $currentClientRole == 'Client User' ? 'selected' : '' }}>Client User (Standard)</option>
                                            <option value="Client Admin" {{ $currentClientRole == 'Client Admin' ? 'selected' : '' }}>Client Admin (Manage team)</option>
                                            <option value="Client Finance" {{ $currentClientRole == 'Client Finance' ? 'selected' : '' }}>Client Finance (Billing)</option>
                                        </select>
                                        <p class="mt-1 text-sm text-gray-500">Grants access to the client portal.</p>
                                    </div>

                                    <div>
                                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                            {{ __('Status') }} *
                                        </label>
                                        <select name="status" id="status" required
                                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="1" {{ old('status', $user->status) == 1 ? 'selected' : '' }}>Active</option>
                                            <option value="2" {{ old('status', $user->status) == 2 ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            @endif
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="job_title" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('Job Title') }}
                                    </label>
                                    <input type="text" name="job_title" id="job_title"
                                           value="{{ old('job_title', $user->job_title) }}"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('Phone') }}
                                    </label>
                                    <input type="text" name="phone" id="phone"
                                           value="{{ old('phone', $user->phone) }}"
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
                                        <option value="America/New_York" {{ old('timezone', $user->timezone) == 'America/New_York' ? 'selected' : '' }}>America/New_York</option>
                                        <option value="America/Chicago" {{ old('timezone', $user->timezone) == 'America/Chicago' ? 'selected' : '' }}>America/Chicago</option>
                                        <option value="America/Denver" {{ old('timezone', $user->timezone) == 'America/Denver' ? 'selected' : '' }}>America/Denver</option>
                                        <option value="America/Los_Angeles" {{ old('timezone', $user->timezone) == 'America/Los_Angeles' ? 'selected' : '' }}>America/Los_Angeles</option>
                                        <option value="Europe/London" {{ old('timezone', $user->timezone) == 'Europe/London' ? 'selected' : '' }}>Europe/London</option>
                                        <option value="Europe/Paris" {{ old('timezone', $user->timezone) == 'Europe/Paris' ? 'selected' : '' }}>Europe/Paris</option>
                                        <option value="Asia/Tokyo" {{ old('timezone', $user->timezone) == 'Asia/Tokyo' ? 'selected' : '' }}>Asia/Tokyo</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="locale" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('Language') }}
                                    </label>
                                    <select name="locale" id="locale"
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">System Default</option>
                                        <option value="en" {{ old('locale', $user->locale) == 'en' ? 'selected' : '' }}>English</option>
                                        <option value="es" {{ old('locale', $user->locale) == 'es' ? 'selected' : '' }}>Spanish</option>
                                        <option value="fr" {{ old('locale', $user->locale) == 'fr' ? 'selected' : '' }}>French</option>
                                        <option value="de" {{ old('locale', $user->locale) == 'de' ? 'selected' : '' }}>German</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-6 flex justify-between">
                            <a href="{{ route('users.show', $user) }}" 
                               class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                {{ __('Cancel') }}
                            </a>
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                {{ __('Save Changes') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
(function () {
    const radios = document.querySelectorAll('input[name="type"]');
    if (!radios.length) return;

    const sectionInternal  = document.getElementById('section-internal-role');
    const sectionExternal  = document.getElementById('section-external-role');
    const hintInternal     = document.getElementById('type-hint-internal');
    const hintExternal     = document.getElementById('type-hint-external');
    const roleSelect       = document.getElementById('role');
    const clientRoleSelect = document.getElementById('client_role');

    function applyType(isExternal) {
        if (sectionInternal) sectionInternal.classList.toggle('hidden', isExternal);
        if (sectionExternal) sectionExternal.classList.toggle('hidden', !isExternal);
        if (hintInternal) hintInternal.classList.toggle('hidden', isExternal);
        if (hintExternal) hintExternal.classList.toggle('hidden', !isExternal);
        if (roleSelect) roleSelect.required = !isExternal;
        if (clientRoleSelect) clientRoleSelect.required = isExternal;
    }

    radios.forEach(function (r) {
        r.addEventListener('change', function () {
            applyType(this.value === '2');
        });
    });

    // Initialise – the server-side @php already classes hidden/visible,
    // but JS keeps required attributes in sync.
    const checked = document.querySelector('input[name="type"]:checked');
    if (checked) applyType(checked.value === '2');
}());
</script>
