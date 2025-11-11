{{-- Mailbox View Sidebar - Folder Navigation --}}
<div class="bg-white border-r border-gray-200 w-64 flex flex-col">
    {{-- Mailbox Header --}}
    <div class="p-4 border-b border-gray-200">
        @if (isset($folder))
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
                {{ method_exists($folder, 'getTypeName') ? $folder->getTypeName() : ($folder->name ?? 'Folder') }} ({{ $folder->active_count ?? 0 }})
            </div>
        @endif
        <div class="flex items-start">
            @include('mailboxes.partials.mute_icon', ['mailbox' => $mailbox])
            <div class="flex-1 min-w-0">
                <div class="font-semibold text-gray-900 truncate">{{ $mailbox->name }}</div>
                @if ($mailbox->email)
                    <div class="text-xs text-gray-500 truncate">{{ $mailbox->email }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Folder List or Chat List --}}
    @php
        $is_in_chat_mode = $is_in_chat_mode ?? (
            isset($conversation) && (
                (method_exists($conversation, 'isInChatMode') && $conversation->isInChatMode()) ||
                ($conversation->is_chat ?? false)
            )
        );
    @endphp
    
    <nav class="flex-1 overflow-y-auto" id="folders">
        @if ($is_in_chat_mode)
            @include('mailboxes.partials.chat_list')
        @else
            @include('mailboxes.partials.folders')
        @endif
    </nav>

    {{-- Action Buttons --}}
    @if (!$is_in_chat_mode)
        <div class="p-4 border-t border-gray-200 space-y-2">
            {{-- Settings Button (if user has permission) --}}
            @php
                $show_settings_btn = Auth::user()->can('update', $mailbox);
            @endphp
            
            @if ($show_settings_btn)
                <div class="relative">
                    <button type="button"
                            class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            onclick="this.nextElementSibling.classList.toggle('hidden')">
                        <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        {{ __('Mailbox Settings') }}
                        <svg class="ml-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    
                    {{-- Settings Dropdown --}}
                    <div class="hidden absolute bottom-full mb-2 w-full bg-white rounded-md shadow-lg border border-gray-200 z-10">
                        @if (Auth::user()->isAdmin())
                            <a href="{{ route('mailboxes.settings', $mailbox) }}" 
                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-t-md">
                                <svg class="inline-block mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                {{ __('Edit Mailbox') }}
                            </a>
                            <a href="{{ route('mailboxes.connection.incoming', $mailbox) }}" 
                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <svg class="inline-block mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                                {{ __('Connection Settings') }}
                            </a>
                        @endif
                        <a href="{{ route('mailboxes.permissions', $mailbox) }}" 
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <svg class="inline-block mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            {{ __('Permissions') }}
                        </a>
                        <a href="{{ route('mailboxes.auto_reply', $mailbox) }}" 
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-b-md">
                            <svg class="inline-block mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                            </svg>
                            {{ __('Auto Reply') }}
                        </a>
                    </div>
                </div>
            @endif

            {{-- New Conversation Button --}}
            <a href="{{ route('conversations.create', ['mailbox_id' => $mailbox->id]) }}" 
               class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                {{ __('New Conversation') }}
            </a>
        </div>
    @endif
</div>
