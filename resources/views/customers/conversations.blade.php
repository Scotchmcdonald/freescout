<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $customer->getFullName() }} - {{ __('Conversations') }}
            </h2>
            @include('customers.profile_menu')
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Sidebar with customer info --}}
                <div class="lg:col-span-1">
                    @include('customers.profile_snippet')
                </div>
                
                {{-- Main content area --}}
                <div class="lg:col-span-2">
                    @include('customers.profile_tabs')
                    
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-4">
                            {{ __('Conversations') }} ({{ $conversations->total() }})
                        </h3>
                        
                        @if($conversations->isEmpty())
                            <div class="text-center py-12 text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                <p class="mt-4">{{ __('No conversations yet.') }}</p>
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach($conversations as $conversation)
                                    <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center space-x-2 mb-2">
                                                    <a href="{{ route('conversations.show', $conversation) }}" 
                                                       class="text-base font-medium text-gray-900 hover:text-blue-600 truncate">
                                                        {{ $conversation->subject }}
                                                    </a>
                                                    @if($conversation->status == 1)
                                                        <span class="flex-shrink-0 px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800 rounded">
                                                            {{ __('Active') }}
                                                        </span>
                                                    @elseif($conversation->status == 2)
                                                        <span class="flex-shrink-0 px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-800 rounded">
                                                            {{ __('Closed') }}
                                                        </span>
                                                    @elseif($conversation->status == 3)
                                                        <span class="flex-shrink-0 px-2 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800 rounded">
                                                            {{ __('Pending') }}
                                                        </span>
                                                    @endif
                                                </div>
                                                
                                                <div class="flex items-center space-x-4 text-sm text-gray-600">
                                                    <span>{{ $conversation->mailbox->name }}</span>
                                                    @if($conversation->folder)
                                                        <span>•</span>
                                                        <span>{{ $conversation->folder->name }}</span>
                                                    @endif
                                                    @if($conversation->user)
                                                        <span>•</span>
                                                        <span>{{ __('Assigned to') }} {{ $conversation->user->getFullName() }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            
                                            <div class="text-right text-sm text-gray-500 ml-4 flex-shrink-0">
                                                <div>{{ $conversation->last_reply_at?->diffForHumans() ?? $conversation->created_at->diffForHumans() }}</div>
                                                <div class="mt-1">
                                                    <span class="px-2 py-0.5 bg-gray-200 rounded text-xs">
                                                        {{ $conversation->threads_count ?? 0 }} {{ __('replies') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            
                            {{-- Pagination --}}
                            @if($conversations->hasPages())
                                <div class="mt-6">
                                    {{ $conversations->links() }}
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
