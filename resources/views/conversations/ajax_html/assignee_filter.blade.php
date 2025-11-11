{{-- Assignee Filter - AJAX-loaded assignee filter dropdown --}}
@php
    $users = \App\Models\User::where('status', 1)->orderBy('first_name')->orderBy('last_name')->get();
    $selected_user_id = $selected_user_id ?? null;
@endphp

<div class="assignee-filter">
    {{-- Search Field --}}
    <div class="p-3 border-b border-gray-200">
        <input type="text" 
               placeholder="{{ __('Search users...') }}"
               class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
               x-data
               @input="filterUsers($event.target.value)">
    </div>

    {{-- User List --}}
    <div class="max-h-64 overflow-y-auto">
        {{-- Unassigned Option --}}
        <button type="button" 
                data-user-id=""
                class="assignee-option w-full text-left px-4 py-2 hover:bg-gray-100 transition {{ !$selected_user_id ? 'bg-blue-50' : '' }}"
                onclick="selectAssignee(null)">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center">
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-900">{{ __('Unassigned') }}</div>
                    <div class="text-xs text-gray-500">{{ __('Remove assignment') }}</div>
                </div>
            </div>
        </button>

        {{-- User Options --}}
        @foreach($users as $user)
            <button type="button" 
                    data-user-id="{{ $user->id }}"
                    data-user-name="{{ $user->getFullName() }}"
                    class="assignee-option w-full text-left px-4 py-2 hover:bg-gray-100 transition {{ $selected_user_id == $user->id ? 'bg-blue-50' : '' }}"
                    onclick="selectAssignee({{ $user->id }}, '{{ addslashes($user->getFullName()) }}')">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold text-sm">
                        {{ substr($user->first_name, 0, 1) }}{{ substr($user->last_name, 0, 1) }}
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-900">{{ $user->getFullName() }}</div>
                        <div class="text-xs text-gray-500">{{ $user->email }}</div>
                    </div>
                </div>
            </button>
        @endforeach
    </div>
</div>

<script>
    function filterUsers(searchTerm) {
        const options = document.querySelectorAll('.assignee-option');
        const search = searchTerm.toLowerCase();
        
        options.forEach(option => {
            const userName = (option.dataset.userName || '').toLowerCase();
            const shouldShow = !search || userName.includes(search) || option.dataset.userId === '';
            option.style.display = shouldShow ? 'block' : 'none';
        });
    }

    function selectAssignee(userId, userName) {
        // This function will be called from parent context
        // You can emit a custom event or call a parent function
        if (window.parent && window.parent.handleAssigneeSelect) {
            window.parent.handleAssigneeSelect(userId, userName);
        } else {
            // Fallback: submit form or make AJAX request
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = window.location.href;
            
            const csrfField = document.createElement('input');
            csrfField.type = 'hidden';
            csrfField.name = '_token';
            csrfField.value = document.querySelector('meta[name="csrf-token"]')?.content || '';
            
            const userField = document.createElement('input');
            userField.type = 'hidden';
            userField.name = 'user_id';
            userField.value = userId || '';
            
            form.appendChild(csrfField);
            form.appendChild(userField);
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>
