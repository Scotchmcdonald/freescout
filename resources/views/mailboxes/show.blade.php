<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-neutral-800 leading-tight">
            {{ $mailbox->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-neutral-900">
                    <div class="mb-6 flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-semibold">{{ $mailbox->name }}</h3>
                            <p class="text-sm text-neutral-600">{{ $mailbox->email }}</p>
                        </div>
                        <div class="flex gap-2">
                            @action('mailbox.view.actions', $mailbox)
                            <a href="{{ route('conversations.create', $mailbox) }}" 
                               class="px-4 py-2 bg-primary-600 text-white rounded hover:bg-primary-700">
                                New Conversation
                            </a>
                        </div>
                    </div>
                    
                    @if($folders->count())
                        <div class="mb-6">
                            <div class="flex space-x-2 border-b border-neutral-200">
                                @foreach($folders as $folder)
                                    <a href="{{ route('mailboxes.view', ['mailbox' => $mailbox, 'folder' => $folder->id]) }}" 
                                       class="px-4 py-2 text-sm font-medium {{ (request('folder') == $folder->id || (!request('folder') && $folder->type == 1)) ? 'border-b-2 border-primary-600 text-primary-600' : 'text-neutral-600 hover:text-neutral-900' }}">
                                        {{ $folder->name }}
                                        @if($folder->conversations()->count() > 0)
                                            <span class="ml-1 px-2 py-0.5 text-xs bg-neutral-200 rounded">
                                                {{ $folder->conversations()->count() }}
                                            </span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    @if($conversations->isEmpty())
                        @action('mailbox.view.before_conversations', $mailbox)
                        <div class="text-center py-12 text-neutral-500">
                            <svg class="mx-auto h-12 w-12 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <p class="mt-2">No conversations in this folder</p>
                        </div>
                        @action('mailbox.view.after_conversations', $mailbox)
                    @else
                        @action('mailbox.view.before_conversations', $mailbox)
                        <div class="space-y-2">
                            @foreach($conversations as $conversation)
                                <div class="border border-neutral-200 rounded-lg p-4 hover:bg-neutral-50 transition">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2 mb-2">
                                                <a href="{{ route('conversations.show', $conversation) }}" 
                                                   class="text-lg font-medium text-neutral-900 hover:text-primary-600">
                                                    {{ $conversation->subject }}
                                                </a>
                                                @if($conversation->status == 1)
                                                    <span class="px-2 py-1 text-xs font-medium bg-success-100 text-success-800 rounded">Active</span>
                                                @elseif($conversation->status == 2)
                                                    <span class="px-2 py-1 text-xs font-medium bg-neutral-100 text-neutral-800 rounded">Closed</span>
                                                @endif
                                            </div>
                                            
                                            <div class="flex items-center space-x-4 text-sm text-neutral-600">
                                                <span>{{ $conversation->customer?->getFullName() ?? $conversation->customer_email ?? 'Unknown' }}</span>
                                                <span>•</span>
                                                <span>{{ $conversation->customer_email }}</span>
                                                @if($conversation->user)
                                                    <span>•</span>
                                                    <span>Assigned to {{ $conversation->user->getFullName() }}</span>
                                                @endif
                                            </div>
                                            
                                            <p class="mt-2 text-sm text-neutral-600">
                                                {{ Str::limit($conversation->preview, 100) }}
                                            </p>
                                        </div>
                                        
                                        <div class="text-right text-sm text-neutral-500">
                                            <div>{{ $conversation->last_reply_at->diffForHumans() }}</div>
                                            <div class="mt-1">
                                                <span class="px-2 py-1 bg-neutral-200 rounded">{{ $conversation->threads_count }} replies</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="mt-6">
                            {{ $conversations->links() }}
                        </div>
                        @action('mailbox.view.after_conversations', $mailbox)
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
