{{-- Folder List Navigation --}}
<ul class="space-y-1 px-2 py-2">
    {{-- Chats Link (if available) --}}
    @if (method_exists(\App\Helpers\Helper::class, 'isChatModeAvailable') && \App\Helpers\Helper::isChatModeAvailable() && $mailbox->id >= 0)
        <li>
            <a href="{{ route('conversations.chats', ['mailbox_id' => $mailbox->id, 'chat_mode' => '1']) }}" 
               class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-50 hover:text-gray-900">
                <svg class="mr-3 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                <span class="flex-1">{{ __('Chats') }}</span>
            </a>
        </li>
    @endif

    {{-- Folders --}}
    @foreach ($folders as $folder_item)
        @php
            // Determine if folder should be shown
            $should_show = $folder->type == $folder_item->type || (
                ($folder_item->type != App\Models\Folder::TYPE_TRASH || ($folder_item->type == App\Models\Folder::TYPE_TRASH && $folder_item->total_count && $folder->type == App\Models\Folder::TYPE_TRASH)) && 
                ($folder_item->type != App\Models\Folder::TYPE_DRAFTS || ($folder_item->type == App\Models\Folder::TYPE_DRAFTS && $folder_item->total_count))
            );

            // Calculate active count
            if ($folder_item->type == App\Models\Folder::TYPE_SPAM) {
                $active_count = $folder_item->total_count ?? 0;
            } else {
                $active_count = $folder_item->active_count ?? 0;
            }

            // Get folder icon based on type
            $folder_icon = match($folder_item->type) {
                App\Models\Folder::TYPE_INBOX => 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4',
                App\Models\Folder::TYPE_SENT => 'M12 19l9 2-9-18-9 18 9-2zm0 0v-8',
                App\Models\Folder::TYPE_DRAFTS => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
                App\Models\Folder::TYPE_SPAM => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
                App\Models\Folder::TYPE_TRASH => 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16',
                App\Models\Folder::TYPE_ASSIGNED => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                App\Models\Folder::TYPE_MINE => 'M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                App\Models\Folder::TYPE_STARRED => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
                default => 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z'
            };
        @endphp

        @if ($should_show)
            <li data-folder-id="{{ $folder_item->id }}" data-active-count="{{ $active_count }}">
                <a href="{{ route('mailboxes.view', ['mailbox' => $mailbox->id, 'folder' => $folder_item->id]) }}" 
                   class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ $folder_item->id == $folder->id ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }} {{ !$active_count ? 'opacity-60' : '' }}">
                    <svg class="mr-3 h-5 w-5 {{ $folder_item->id == $folder->id ? 'text-blue-500' : 'text-gray-400' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $folder_icon }}" />
                    </svg>
                    <span class="flex-1">{{ method_exists($folder_item, 'getTypeName') ? $folder_item->getTypeName() : ($folder_item->name ?? 'Folder') }}</span>
                    
                    @if ($active_count)
                        @if (in_array($folder_item->type, [App\Models\Folder::TYPE_ASSIGNED, App\Models\Folder::TYPE_MINE]))
                            <span class="ml-auto font-semibold text-sm" title="{{ __('Active Conversations') }}">{{ $active_count }}</span>
                        @else
                            <span class="ml-auto text-sm" title="{{ __('Active Conversations') }}">{{ $active_count }}</span>
                        @endif
                    @endif
                </a>
            </li>
        @endif
    @endforeach
</ul>
