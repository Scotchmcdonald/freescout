{{-- Chats View - Separate view for chat-style conversations --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Chats') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="grid grid-cols-1 md:grid-cols-3 h-[calc(100vh-200px)]">
                    {{-- Chat List Sidebar --}}
                    <div class="md:col-span-1 border-r border-gray-200 overflow-y-auto">
                        <div class="p-4 border-b border-gray-200">
                            <input type="text" 
                                   placeholder="{{ __('Search chats...') }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        
                        <div class="divide-y divide-gray-200">
                            @forelse($conversations ?? [] as $conversation)
                                <a href="{{ route('conversations.chats', ['id' => $conversation->id]) }}" 
                                   class="block p-4 hover:bg-gray-50 transition {{ isset($active_conversation) && $active_conversation->id == $conversation->id ? 'bg-blue-50' : '' }}">
                                    <div class="flex items-start gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gray-400 flex items-center justify-center text-white font-semibold flex-shrink-0">
                                            {{ substr($conversation->customer->first_name, 0, 1) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="font-medium text-gray-900 truncate">
                                                    {{ $conversation->customer->getFullName(true) }}
                                                </span>
                                                <span class="text-xs text-gray-500 flex-shrink-0 ml-2">
                                                    {{ $conversation->last_reply_at ? $conversation->last_reply_at->diffForHumans() : '' }}
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-600 truncate">
                                                {{ $conversation->preview ?? $conversation->subject }}
                                            </p>
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <div class="p-8 text-center text-gray-500">
                                    {{ __('No chats yet') }}
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Chat Messages Area --}}
                    <div class="md:col-span-2 flex flex-col">
                        @if(isset($active_conversation))
                            {{-- Chat Header --}}
                            <div class="p-4 border-b border-gray-200 bg-white">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gray-400 flex items-center justify-center text-white font-semibold">
                                            {{ substr($active_conversation->customer->first_name, 0, 1) }}
                                        </div>
                                        <div>
                                            <h3 class="font-medium text-gray-900">
                                                {{ $active_conversation->customer->getFullName(true) }}
                                            </h3>
                                            <p class="text-sm text-gray-500">{{ $active_conversation->customer_email }}</p>
                                        </div>
                                    </div>
                                    <button type="button" class="text-gray-400 hover:text-gray-600">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            {{-- Messages --}}
                            <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50">
                                @foreach($active_conversation->threads as $thread)
                                    <div class="flex {{ $thread->type == 1 ? 'justify-start' : 'justify-end' }}">
                                        <div class="max-w-xs lg:max-w-md">
                                            <div class="rounded-lg px-4 py-2 {{ $thread->type == 1 ? 'bg-white border border-gray-200' : 'bg-blue-600 text-white' }}">
                                                <p class="text-sm">{{ $thread->body }}</p>
                                            </div>
                                            <div class="text-xs text-gray-500 mt-1 {{ $thread->type == 1 ? 'text-left' : 'text-right' }}">
                                                {{ $thread->created_at->format('g:i A') }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Quick Reply Box --}}
                            <div class="p-4 border-t border-gray-200 bg-white">
                                <form method="POST" action="{{ route('conversations.reply', $active_conversation) }}">
                                    @csrf
                                    <div class="flex gap-2">
                                        <input type="text" 
                                               name="body"
                                               placeholder="{{ __('Type a message...') }}"
                                               class="flex-1 border-gray-300 rounded-full shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                               required>
                                        <button type="submit" 
                                                class="px-6 py-2 bg-blue-600 text-white rounded-full hover:bg-blue-700 transition">
                                            {{ __('Send') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @else
                            <div class="flex-1 flex items-center justify-center text-gray-500">
                                <div class="text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                    <p class="mt-2">{{ __('Select a chat to start messaging') }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
