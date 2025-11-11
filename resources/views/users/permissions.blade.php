<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('User Permissions') }} - {{ $user->getFullName() }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                {{-- Sidebar --}}
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        @include('users.sidebar_menu')
                    </div>
                </div>
                
                {{-- Main content --}}
                <div class="lg:col-span-3">
                    {{-- Flash messages --}}
                    @if(session('success'))
                        <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex">
                                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <p class="ml-3 text-sm text-green-700">{{ session('success') }}</p>
                            </div>
                        </div>
                    @endif
                    
                    @if($errors->any())
                        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex">
                                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                <div class="ml-3">
                                    @foreach($errors->all() as $error)
                                        <p class="text-sm text-red-700">{{ $error }}</p>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                    
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
                                                class="text-blue-600 hover:text-blue-800 select-all-link"
                                                onclick="document.querySelectorAll('.mailbox-checkbox').forEach(cb => cb.checked = true); return false;">
                                            {{ __('all') }}
                                        </button>
                                        <span class="text-gray-500">/</span>
                                        <button type="button" 
                                                class="text-blue-600 hover:text-blue-800 select-none-link"
                                                onclick="document.querySelectorAll('.mailbox-checkbox').forEach(cb => cb.checked = false); return false;">
                                            {{ __('none') }}
                                        </button>
                                    </div>
                                    
                                    <div class="space-y-2">
                                        @foreach($mailboxes as $mailbox)
                                            <label class="flex items-center p-3 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer transition">
                                                <input type="checkbox" 
                                                       name="mailboxes[]" 
                                                       id="mailbox-{{ $mailbox->id }}" 
                                                       value="{{ $mailbox->id }}" 
                                                       @if($user_mailboxes->contains($mailbox->id)) checked @endif
                                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mailbox-checkbox">
                                                <span class="ml-3 text-sm font-medium text-gray-900">
                                                    {{ $mailbox->name }}
                                                </span>
                                                <span class="ml-2 text-xs text-gray-500">
                                                    ({{ $mailbox->email }})
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="mb-8 p-4 bg-gray-50 rounded-lg text-sm text-gray-600">
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
                                            <label class="flex items-center p-3 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer transition">
                                                <input type="checkbox" 
                                                       name="user_permissions[]" 
                                                       value="{{ $permKey }}" 
                                                       id="user_permission_{{ $permKey }}"
                                                       @if(in_array($permKey, $user->permissions ?? [])) checked @endif
                                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                <span class="ml-3 text-sm font-medium text-gray-900">
                                                    {{ $permName }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="mb-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                    <div class="flex">
                                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                        </svg>
                                        <p class="ml-3 text-sm text-blue-700">
                                            {{ __('Administrator users have access to all features and mailboxes by default.') }}
                                        </p>
                                    </div>
                                </div>
                            @endif
                            
                            {{-- Action buttons --}}
                            <div class="flex items-center justify-end space-x-3">
                                <a href="{{ route('users.show', $user) }}" 
                                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                                    {{ __('Cancel') }}
                                </a>
                                <button type="submit" 
                                        class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
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
