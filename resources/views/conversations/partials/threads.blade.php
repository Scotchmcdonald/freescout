{{-- Threads List Partial - Displays all threads in a conversation --}}
@if(isset($threads) && $threads->count() > 0)
    <div class="threads-list space-y-4">
        @foreach($threads as $thread)
            @include('conversations.partials.thread', [
                'thread' => $thread,
                'conversation' => $conversation ?? $thread->conversation,
                'loop' => $loop
            ])
        @endforeach
    </div>
@else
    {{-- Empty State --}}
    <div class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('No threads yet') }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ __('This conversation has no messages yet.') }}</p>
    </div>
@endif
