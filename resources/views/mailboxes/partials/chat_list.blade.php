{{-- Chat Conversation List --}}
<ul class="divide-y divide-gray-200">
    {{-- Exit Chat Mode Link --}}
    @if (isset($folder))
        <li class="px-4 py-3 hover:bg-gray-50">
            <a href="{{ route('mailboxes.view', ['mailbox' => $mailbox->id, 'folder' => $folder->id, 'chat_mode' => 0]) }}" 
               class="flex items-center text-sm">
                <svg class="mr-2 h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                <span class="text-gray-700">{{ __('Chats') }}</span>
                <span class="ml-1 text-gray-500 italic">({{ __('Exit') }})</span>
            </a>
        </li>
    @elseif (!empty($is_in_chat_mode))
        <li class="px-4 py-3 hover:bg-gray-50">
            <a href="{{ route('mailboxes.view', ['mailbox' => $mailbox->id, 'chat_mode' => 0]) }}" 
               class="flex items-center text-sm">
                <svg class="mr-2 h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                <span class="text-gray-700">{{ __('Chats') }}</span>
                <span class="ml-1 text-gray-500 italic">({{ __('Exit') }})</span>
            </a>
        </li>
    @endif

    {{-- Chat Conversations --}}
    @php
        // Get chats - use static method if available, otherwise get recent conversations
        if (method_exists(App\Models\Conversation::class, 'getChats')) {
            $chats = App\Models\Conversation::getChats($mailbox->id, $offset ?? 0);
        } else {
            $chats = $mailbox->conversations()
                ->where('is_chat', true)
                ->orderBy('last_reply_at', 'desc')
                ->limit(20)
                ->get();
        }
        $chat_list_size = defined('App\Models\Conversation::CHATS_LIST_SIZE') ? App\Models\Conversation::CHATS_LIST_SIZE : 20;
    @endphp

    @foreach ($chats as $chat_i => $chat)
        @if ($chat_i < $chat_list_size)
            <li class="px-4 py-3 hover:bg-gray-50 {{ isset($conversation) && $chat->id == $conversation->id ? 'bg-blue-50' : '' }} {{ (method_exists($chat, 'isActive') && $chat->isActive()) || $chat->status == 1 ? 'border-l-4 border-blue-500' : '' }}" 
                data-chat-id="{{ $chat->id }}">
                <a href="{{ method_exists($chat, 'url') ? $chat->url(null, null, ['chat_mode' => 1]) : route('conversations.show', $chat) }}" class="block">
                    <div class="flex items-start justify-between mb-1">
                        <div class="font-semibold text-sm text-gray-900 truncate flex-1">
                            {{ $chat->customer->getFullName(true) ?? $chat->customer->email }}
                        </div>
                        <div class="ml-2 text-xs text-gray-500 flex-shrink-0" title="{{ $chat->last_reply_at?->format('M d, Y g:i A') }}">
                            {{ $chat->last_reply_at?->diffForHumans() }}
                        </div>
                    </div>
                    
                    <div class="text-xs text-gray-600 truncate mb-2">
                        {{ $chat->preview ?? $chat->subject }}
                    </div>
                    
                    <div class="flex flex-wrap gap-1">
                        @if (method_exists($chat, 'getChannelName'))
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                {{ $chat->getChannelName() }}
                            </span>
                        @endif
                        @if (!$chat->user_id)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                {{ __('Unassigned') }}
                            </span>
                        @endif
                    </div>
                </a>
            </li>
        @else
            {{-- Load More Button --}}
            <li class="px-4 py-3 hover:bg-gray-50">
                <a href="#" 
                   class="flex justify-center items-center text-sm text-gray-600 hover:text-gray-900 chats-load-more" 
                   data-loading-text="···">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </a>
            </li>
            @break
        @endif
    @endforeach
</ul>
