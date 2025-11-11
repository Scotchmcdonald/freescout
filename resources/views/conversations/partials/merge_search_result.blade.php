{{-- Merge Search Result - Display conversation search results for merging --}}
@if(isset($conversation))
    <div class="merge-search-result border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
        <label class="flex items-start gap-3 cursor-pointer">
            {{-- Selection Radio Button --}}
            <input type="radio" 
                   name="target_conversation_id" 
                   value="{{ $conversation->id }}"
                   class="mt-1 rounded-full border-gray-300 text-blue-600 focus:ring-blue-500">
            
            {{-- Conversation Details --}}
            <div class="flex-1 min-w-0">
                {{-- Conversation Header --}}
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-gray-900">
                            #{{ $conversation->number }}
                        </span>
                        @include('conversations.partials.badges', ['conversation' => $conversation])
                    </div>
                    <span class="text-xs text-gray-500">
                        {{ $conversation->created_at->diffForHumans() }}
                    </span>
                </div>

                {{-- Subject --}}
                <div class="mb-2">
                    <a href="{{ route('conversations.show', $conversation) }}" 
                       target="_blank"
                       class="text-sm font-medium text-blue-600 hover:underline">
                        {{ $conversation->subject }}
                    </a>
                </div>

                {{-- Customer Info --}}
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span>{{ $conversation->customer->getFullName(true) }}</span>
                    <span class="text-gray-400">•</span>
                    <span>{{ $conversation->customer_email }}</span>
                </div>

                {{-- Metadata --}}
                <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                    <span class="flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        {{ $conversation->threads_count }} {{ __('replies') }}
                    </span>
                    
                    @if($conversation->user)
                        <span class="flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            {{ $conversation->user->getFullName() }}
                        </span>
                    @endif

                    <span class="flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ $conversation->last_reply_at ? $conversation->last_reply_at->diffForHumans() : __('No replies') }}
                    </span>
                </div>

                {{-- Preview --}}
                @if($conversation->preview)
                    <div class="mt-2 text-sm text-gray-600 line-clamp-2">
                        {{ $conversation->preview }}
                    </div>
                @endif
            </div>
        </label>
    </div>
@endif
