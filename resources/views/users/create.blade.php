<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-neutral-800 leading-tight">
            {{ __('Create New User') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-neutral-900">
                    @if($errors->any())
                        <div class="mb-6 bg-danger-50 border-l-4 border-danger-400 p-4">
                            <ul class="list-disc list-inside text-sm text-danger-700">
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
                                    <label for="first_name" class="block text-sm font-medium text-neutral-700 mb-2">
                                        {{ __('First Name') }} *
                                    </label>
                                    <input type="text" name="first_name" id="first_name" required
                                           value="{{ old('first_name') }}"
                                           class="w-full border-neutral-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                </div>
                                
                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-neutral-700 mb-2">
                                        {{ __('Last Name') }}
                                    </label>
                                    <input type="text" name="last_name" id="last_name"
                                           value="{{ old('last_name') }}"
                                           class="w-full border-neutral-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                </div>
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-neutral-700 mb-2">
                                    {{ __('Email') }} *
                                </label>
                                <input type="email" name="email" id="email" required
                                       value="{{ old('email') }}"
                                       class="w-full border-neutral-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            </div>
                            
                            <div>
                                <label for="password" class="block text-sm font-medium text-neutral-700 mb-2">
                                    {{ __('Password') }} *
                                </label>
                                <input type="password" name="password" id="password" required
                                       class="w-full border-neutral-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <p class="mt-1 text-sm text-neutral-500">Minimum 8 characters</p>
                            </div>
                            
                            {{-- User Type --}}
                            <div>
                                <label class="block text-sm font-medium text-neutral-700 mb-2">{{ __('User Type') }} *</label>
                                <div class="flex rounded-md shadow-sm" role="group">
                                    <label class="flex-1 cursor-pointer">
                                        <input type="radio" name="type" value="1" id="type_internal"
                                               class="sr-only peer"
                                               {{ old('type', 1) == 1 ? 'checked' : '' }}>
                                        <span class="block text-center px-4 py-2 text-sm font-medium border border-neutral-300 rounded-l-md
                                                     peer-checked:bg-primary-600 peer-checked:text-white peer-checked:border-primary-600
                                                     hover:bg-neutral-50 peer-checked:hover:bg-primary-700">
                                            🏢 {{ __('Internal Staff') }}
                                        </span>
                                    </label>
                                    <label class="flex-1 cursor-pointer">
                                        <input type="radio" name="type" value="2" id="type_external"
                                               class="sr-only peer"
                                               {{ old('type') == 2 ? 'checked' : '' }}>
                                        <span class="block text-center px-4 py-2 text-sm font-medium border border-neutral-300 border-l-0 rounded-r-md
                                                     peer-checked:bg-primary-600 peer-checked:text-white peer-checked:border-primary-600
                                                     hover:bg-neutral-50 peer-checked:hover:bg-primary-700">
                                            🌐 {{ __('External Client') }}
                                        </span>
                                    </label>
                                </div>
                                <p class="mt-1 text-sm text-neutral-500" id="type-hint-internal">Internal staff have access to mailboxes and helpdesk tools.</p>
                                <p class="mt-1 text-sm text-neutral-500 hidden" id="type-hint-external">External clients access the customer portal only.</p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                {{-- Internal roles --}}
                                <div id="section-internal-role">
                                    <label for="role" class="block text-sm font-medium text-neutral-700 mb-2">
                                        {{ __('Role') }} *
                                    </label>
                                    <select name="role" id="role"
                                            class="w-full border-neutral-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                        <option value="1" {{ old('role', 1) == 1 ? 'selected' : '' }}>Agent (Standard access)</option>
                                        <option value="2" {{ old('role') == 2 ? 'selected' : '' }}>Admin (Full access)</option>
                                        <option value="3" {{ old('role') == 3 ? 'selected' : '' }}>Reporter (Read-only)</option>
                                        <option value="4" {{ old('role') == 4 ? 'selected' : '' }}>Finance (Billing access)</option>
                                    </select>
                                </div>

                                {{-- External / Client roles --}}
                                <div id="section-external-role" class="hidden">
                                    <label for="client_role" class="block text-sm font-medium text-neutral-700 mb-2">
                                        {{ __('Client Role') }} *
                                    </label>
                                    <select name="client_role" id="client_role"
                                            class="w-full border-neutral-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                        <option value="Client User" {{ old('client_role', 'Client User') == 'Client User' ? 'selected' : '' }}>Client User (Standard)</option>
                                        <option value="Client Admin" {{ old('client_role') == 'Client Admin' ? 'selected' : '' }}>Client Admin (Manage team)</option>
                                        <option value="Client Finance" {{ old('client_role') == 'Client Finance' ? 'selected' : '' }}>Client Finance (Billing)</option>
                                    </select>
                                    <p class="mt-1 text-sm text-neutral-500">Grants access to the client portal.</p>
                                </div>

                                <div>
                                    <label for="status" class="block text-sm font-medium text-neutral-700 mb-2">
                                        {{ __('Status') }} *
                                    </label>
                                    <select name="status" id="status" required
                                            class="w-full border-neutral-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                        <option value="1" {{ old('status', 1) == 1 ? 'selected' : '' }}>Active</option>
                                        <option value="2" {{ old('status') == 2 ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="job_title" class="block text-sm font-medium text-neutral-700 mb-2">
                                        {{ __('Job Title') }}
                                    </label>
                                    <input type="text" name="job_title" id="job_title"
                                           value="{{ old('job_title') }}"
                                           class="w-full border-neutral-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                </div>
                                
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-neutral-700 mb-2">
                                        {{ __('Phone') }}
                                    </label>
                                    <input type="text" name="phone" id="phone"
                                           value="{{ old('phone') }}"
                                           class="w-full border-neutral-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="timezone" class="block text-sm font-medium text-neutral-700 mb-2">
                                        {{ __('Timezone') }}
                                    </label>
                                    <select name="timezone" id="timezone"
                                            class="w-full border-neutral-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500">
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
                                    <label for="locale" class="block text-sm font-medium text-neutral-700 mb-2">
                                        {{ __('Language') }}
                                    </label>
                                    <select name="locale" id="locale"
                                            class="w-full border-neutral-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500">
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
                               class="px-4 py-2 border border-neutral-300 rounded-md text-neutral-700 hover:bg-neutral-50">
                                {{ __('Cancel') }}
                            </a>
                            <button type="submit" 
                                    class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700">
                                {{ __('Create User') }}
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
    const sectionInternal = document.getElementById('section-internal-role');
    const sectionExternal = document.getElementById('section-external-role');
    const hintInternal    = document.getElementById('type-hint-internal');
    const hintExternal    = document.getElementById('type-hint-external');
    const roleSelect      = document.getElementById('role');
    const clientRoleSelect = document.getElementById('client_role');

    function applyType(isExternal) {
        sectionInternal.classList.toggle('hidden', isExternal);
        sectionExternal.classList.toggle('hidden', !isExternal);
        hintInternal.classList.toggle('hidden', isExternal);
        hintExternal.classList.toggle('hidden', !isExternal);
        roleSelect.required = !isExternal;
        clientRoleSelect.required = isExternal;
    }

    radios.forEach(function (r) {
        r.addEventListener('change', function () {
            applyType(this.value === '2');
        });
    });

    // Initialise on page load (handles old() values after a validation failure)
    const checked = document.querySelector('input[name="type"]:checked');
    if (checked) applyType(checked.value === '2');
}());
</script>
