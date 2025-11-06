<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Search Results') }}: "{{ $query }}"
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <form method="GET" action="{{ route('conversations.search') }}" class="flex gap-2">
                            <input type="text" name="q" value="{{ $query }}" 
                                   placeholder="Search conversations..."
                                   class="flex-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                Search
                            </button>
                        </form>
                    </div>
                    
                    @if($conversations->isEmpty())
                        <div class="text-center py-12 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <p class="mt-2">No conversations found matching "{{ $query }}"</p>
                        </div>
                    @else
                        <div class="mb-4 text-sm text-gray-600">
                            Found {{ $conversations->total() }} conversation(s)
                        </div>
                        
                        <div class="space-y-2">
                            @foreach($conversations as $conversation)
                                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2 mb-2">
                                                <a href="{{ route('conversations.show', $conversation) }}" 
                                                   class="text-lg font-medium text-gray-900 hover:text-blue-600">
                                                    {{ $conversation->subject }}
                                                </a>
                                                @if($conversation->status == 1)
                                                    <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded">Active</span>
                                                @elseif($conversation->status == 2)
                                                    <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded">Closed</span>
                                                @endif
                                            </div>
                                            
                                            <div class="flex items-center space-x-4 text-sm text-gray-600 mb-2">
                                                <span>{{ $conversation->mailbox->name }}</span>
                                                <span>•</span>
                                                <span>{{ $conversation->customer->getFullName() }}</span>
                                                <span>•</span>
                                                <span>{{ $conversation->customer_email }}</span>
                                                @if($conversation->user)
                                                    <span>•</span>
                                                    <span>{{ $conversation->user->getFullName() }}</span>
                                                @endif
                                            </div>
                                            
                                            <p class="text-sm text-gray-600">
                                                {{ Str::limit($conversation->preview, 150) }}
                                            </p>
                                        </div>
                                        
                                        <div class="text-right text-sm text-gray-500 ml-4">
                                            <div>{{ $conversation->last_reply_at->diffForHumans() }}</div>
                                            <div class="mt-1">
                                                <span class="px-2 py-1 bg-gray-200 rounded">{{ $conversation->threads_count }} replies</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="mt-6">
                            {{ $conversations->appends(['q' => $query])->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
