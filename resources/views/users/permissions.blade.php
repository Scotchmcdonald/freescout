<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-neutral-800 leading-tight">
            {{ __('User Permissions') }} - {{ $user->getFullName() }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                {{-- Sidebar --}}
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-sm border border-neutral-200 p-4">
                        <x-user-sidebar-menu :user="$user" :users="$users ?? collect()" />
                    </div>
                </div>
                
                {{-- Main content --}}
                <div class="lg:col-span-3">
                    {{-- Flash messages --}}
                    <x-flash-messages />
                    
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <form method="POST" action="{{ route('users.permissions', $user) }}">
                            @csrf
                            
                            {{-- Mailbox Access --}}
                            @if($mailboxes->count() > 0)
                                <div class="mb-8">
                                    <h3 class="text-lg font-semibold mb-4">
                                        {{ __(':first_name has access to the selected mailboxes:', ['first_name' => $user->first_name]) }}
                                    </h3>
                                    
                                    <div class="mb-4 text-sm">
                                        <button type="button" 
                                                class="text-primary-600 hover:text-primary-800 select-all-link"
                                                onclick="document.querySelectorAll('.mailbox-checkbox').forEach(cb => cb.checked = true); return false;">
                                            {{ __('all') }}
                                        </button>
                                        <span class="text-neutral-500">/</span>
                                        <button type="button" 
                                                class="text-primary-600 hover:text-primary-800 select-none-link"
                                                onclick="document.querySelectorAll('.mailbox-checkbox').forEach(cb => cb.checked = false); return false;">
                                            {{ __('none') }}
                                        </button>
                                    </div>
                                    
                                    <div class="space-y-2">
                                        @foreach($mailboxes as $mailbox)
                                            <label class="flex items-center p-3 rounded-lg border border-neutral-200 hover:bg-neutral-50 cursor-pointer transition">
                                                <input type="checkbox" 
                                                       name="mailboxes[]" 
                                                       id="mailbox-{{ $mailbox->id }}" 
                                                       value="{{ $mailbox->id }}" 
                                                       @if($user_mailboxes->contains($mailbox->id)) checked @endif
                                                       class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500 mailbox-checkbox">
                                                <span class="ml-3 text-sm font-medium text-neutral-900">
                                                    {{ $mailbox->name }}
                                                </span>
                                                <span class="ml-2 text-xs text-neutral-500">
                                                    ({{ $mailbox->email }})
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="mb-8 p-4 bg-neutral-50 rounded-lg text-sm text-neutral-600">
                                    {{ __('No mailboxes available. Please create a mailbox first.') }}
                                </div>
                            @endif
                            
                            {{-- User Permissions (for non-admin users) --}}
                            @if(!$user->isAdmin())
                                <div class="mb-8">
                                    <h3 class="text-lg font-semibold mb-4">{{ __('User Permissions') }}</h3>
                                    
                                    <div class="space-y-2">
                                        @php
                                            $userPermissions = [
                                                'manage_users' => __('Manage Users'),
                                                'manage_settings' => __('Manage Settings'),
                                                'view_reports' => __('View Reports'),
                                                'manage_tags' => __('Manage Tags'),
                                                'delete_conversations' => __('Delete Conversations'),
                                            ];
                                        @endphp
                                        
                                        @foreach($userPermissions as $permKey => $permName)
                                            <label class="flex items-center p-3 rounded-lg border border-neutral-200 hover:bg-neutral-50 cursor-pointer transition">
                                                <input type="checkbox" 
                                                       name="user_permissions[]" 
                                                       value="{{ $permKey }}" 
                                                       id="user_permission_{{ $permKey }}"
                                                       @if(in_array($permKey, $user->permissions ?? [])) checked @endif
                                                       class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500">
                                                <span class="ml-3 text-sm font-medium text-neutral-900">
                                                    {{ $permName }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="mb-8 p-4 border rounded-lg" style="background-color: var(--theme-primary-50); border-color: var(--theme-primary-200);">
                                    <div class="flex">
                                        <svg class="h-5 w-5" style="color: var(--theme-primary-400);" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                        </svg>
                                        <p class="ml-3 text-sm" style="color: var(--theme-primary-700);">
                                            {{ __('Administrator users have access to all features and mailboxes by default.') }}
                                        </p>
                                    </div>
                                </div>
                            @endif
                            
                            {{-- Action buttons --}}
                            <div class="flex items-center justify-end space-x-3">
                                <a href="{{ route('users.show', $user) }}" 
                                   class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-lg hover:bg-neutral-50 transition">
                                    {{ __('Cancel') }}
                                </a>
                                <button type="submit" 
                                        class="px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition">
                                    {{ __('Save Permissions') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
