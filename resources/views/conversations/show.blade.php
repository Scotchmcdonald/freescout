<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Ticket #{{ $conversation->number }} - {{ $conversation->subject }}
            </h2>
            <div class="flex items-center gap-4">
                <span class="text-sm">
                    @if($conversation->status == 1)
                        Status: <span class="text-green-600 font-medium">Open</span>
                    @elseif($conversation->status == 2)
                        Status: <span class="text-orange-600 font-medium">Awaiting Client Response</span>
                    @elseif($conversation->status == 3)
                        @if(($conversation->meta['status_display'] ?? '') === 'resolved')
                            Status: <span class="text-blue-600 font-medium">Resolved</span>
                        @else
                            Status: <span class="text-gray-600 font-medium">Closed</span>
                        @endif
                    @else
                        Status: <span class="text-gray-600 font-medium">Pending</span>
                    @endif
                </span>
                <span class="text-sm text-gray-500">Last updated: {{ $conversation->updated_at->diffForHumans() }}</span>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            @php
                $meta = $conversation->meta ?? [];
                $clientName = '';
                if (\Illuminate\Support\Facades\Schema::hasTable('client_conversations')) {
                    $clientLink = \Illuminate\Support\Facades\DB::table('client_conversations')
                        ->where('conversation_id', $conversation->id)->first();
                    if ($clientLink && \Illuminate\Support\Facades\Schema::hasTable('clients')) {
                        $crmClient = \Illuminate\Support\Facades\DB::table('clients')
                            ->where('id', $clientLink->client_id)->first();
                        if ($crmClient) {
                            $clientName = $crmClient->name ?? '';
                        }
                    }
                }
            @endphp
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <!-- Conversation Header -->
                            <div class="mb-6 pb-6 border-b border-gray-200">
                                <div class="flex justify-between items-start mb-4">
                                    <div class="flex-1">
                                        <h3 class="text-2xl font-semibold mb-2">{{ $conversation->subject }}</h3>
                                        @action('conversation.after_subject', $conversation)
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @action('conversation.action_buttons', $conversation)
                                    </div>
                                </div>
                                <div class="flex items-center space-x-4 text-sm text-gray-600">
                                    @if($clientName)
                                        <span class="font-medium text-gray-900">{{ $clientName }}</span>
                                        <span>•</span>
                                    @endif
                                    <span>{{ $conversation->customer->getFullName() }}</span>
                                    <span>•</span>
                                    <span>{{ $conversation->customer_email }}</span>
                                    <span>•</span>
                                    <span>{{ $conversation->created_at->format('M d, Y g:i A') }}</span>
                                </div>
                                @if(isset($meta['closed_by']) && $meta['closed_by'] === 'client')
                                    <div class="mt-2 text-sm text-gray-500">Closed by client</div>
                                @endif
                                @if(isset($meta['reopened_reason']))
                                    <div class="mt-2 text-sm text-gray-500">Reopened: {{ $meta['reopened_reason'] }}</div>
                                @endif
                            </div>
                            
                            <!-- Threads -->
                            <div class="space-y-6">
                                @action('conversation.before_threads', $conversation)
                                @foreach($conversation->threads as $thread)
                                    <div class="border border-gray-200 rounded-lg p-4 {{ $thread->type == 2 ? 'bg-yellow-50' : '' }}">
                                        <div class="flex items-start justify-between mb-3">
                                            <div class="flex items-center space-x-3">
                                                @if($thread->user)
                                                    <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold">
                                                        {{ substr($thread->user->first_name, 0, 1) }}{{ substr($thread->user->last_name, 0, 1) }}
                                                    </div>
                                                    <div>
                                                        <div class="font-medium text-gray-900">{{ $thread->user->getFullName() }}</div>
                                                        <div class="text-sm text-gray-500">{{ $thread->created_at->diffForHumans() }}</div>
                                                    </div>
                                                @else
                                                    <div class="w-10 h-10 rounded-full bg-gray-400 flex items-center justify-center text-white font-semibold">
                                                        {{ substr($conversation->customer->first_name ?? 'C', 0, 1) }}
                                                    </div>
                                                    <div>
                                                        <div class="font-medium text-gray-900">{{ $conversation->customer->getFullName() }}</div>
                                                        <div class="text-sm text-gray-500">{{ $thread->created_at->diffForHumans() }}</div>
                                                    </div>
                                                @endif
                                            </div>
                                            
                                            @if($thread->type == 2)
                                                <span class="px-2 py-1 text-xs font-medium bg-yellow-200 text-yellow-800 rounded">Note</span>
                                            @endif
                                        </div>
                                        
                                        <div class="prose max-w-none">
                                            {!! nl2br(e($thread->body)) !!}
                                        </div>
                                        
                                        @if($thread->attachments && $thread->attachments->count())
                                            <div class="mt-4 pt-4 border-t border-gray-200">
                                                <div class="text-sm font-medium text-gray-700 mb-2">{{ $thread->attachments->count() }} attachment{{ $thread->attachments->count() > 1 ? 's' : '' }}</div>
                                                <div class="space-y-1">
                                                    @foreach($thread->attachments as $attachment)
                                                        <a href="{{ $attachment->url }}" dusk="view-attachment" class="text-sm text-blue-600 hover:underline flex items-center">
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                                            </svg>
                                                            {{ $attachment->file_name }} ({{ number_format($attachment->size / 1024, 2) }} KB)
                                                        </a>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                                @action('conversation.after_threads', $conversation)
                            </div>

                            @if(isset($meta['client_rating']))
                                <div class="mt-6 pt-6 border-t border-gray-200">
                                    <h4 class="font-semibold mb-2">Client Rating</h4>
                                    <div class="text-yellow-500">{{ str_repeat('★', (int)$meta['client_rating']) }}{{ str_repeat('☆', 5 - (int)$meta['client_rating']) }}</div>
                                    @if(isset($meta['client_feedback']))
                                        <p class="text-sm text-gray-600 mt-1">{{ $meta['client_feedback'] }}</p>
                                    @endif
                                </div>
                            @endif
                            
                            <!-- Reply Form -->
                            <div class="mt-6 pt-6 border-t border-gray-200">
                                <form action="{{ route('conversations.reply', $conversation) }}" method="POST">
                                    @csrf
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Reply</label>
                                        <textarea name="body" rows="6" required
                                                  dusk="reply-message"
                                                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                                    </div>
                                    
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <label class="flex items-center">
                                                <input type="radio" name="type" value="1" checked class="mr-1">
                                                <span class="text-sm">Reply</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input type="radio" name="type" value="2" class="mr-1">
                                                <span class="text-sm">Note</span>
                                            </label>
                                        </div>
                                        
                                        <button type="submit" dusk="send-reply-button" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                            Send Reply
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <!-- Ticket Management Form -->
                    <form action="{{ route('conversations.update', $conversation) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                            <h4 class="font-semibold mb-4">Ticket Management</h4>
                            
                            <div class="space-y-4">
                                <!-- Status -->
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select name="status" id="status" dusk="status-select"
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                        <option value="active" {{ $conversation->status == 1 ? 'selected' : '' }}>Active</option>
                                        <option value="pending" {{ $conversation->status == 2 ? 'selected' : '' }}>Pending</option>
                                        <option value="closed" {{ $conversation->status == 3 ? 'selected' : '' }}>Closed</option>
                                        <option value="resolved" {{ ($conversation->status == 3 && ($meta['status_display'] ?? '') === 'resolved') ? 'selected' : '' }}>Resolved</option>
                                    </select>
                                </div>

                                <!-- Resolution Notes -->
                                <div>
                                    <label for="resolution_notes" class="block text-sm font-medium text-gray-700 mb-1">Resolution Notes</label>
                                    <textarea name="resolution_notes" id="resolution_notes" rows="3"
                                              dusk="resolution-notes"
                                              class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                              placeholder="Notes about the resolution...">{{ $meta['resolution_notes'] ?? '' }}</textarea>
                                </div>

                                <!-- Billing Section -->
                                <div class="pt-4 border-t border-gray-200">
                                    <h5 class="text-sm font-semibold mb-3">Billing</h5>
                                    
                                    <div class="space-y-3">
                                        <label class="flex items-center">
                                            <input type="checkbox" name="is_billable" value="1"
                                                   dusk="billable-checkbox"
                                                   {{ !empty($meta['is_billable']) ? 'checked' : '' }}
                                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-700">Billable</span>
                                        </label>

                                        <div>
                                            <label for="billable_hours" class="block text-sm text-gray-700 mb-1">Hours</label>
                                            <input type="number" name="billable_hours" id="billable_hours"
                                                   dusk="billable-hours"
                                                   step="0.5" min="0"
                                                   value="{{ $meta['billable_hours'] ?? '' }}"
                                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                        </div>

                                        <div>
                                            <label for="billable_rate" class="block text-sm text-gray-700 mb-1">Rate ($/hr)</label>
                                            <input type="number" name="billable_rate" id="billable_rate"
                                                   dusk="billable-rate"
                                                   step="0.01" min="0"
                                                   value="{{ $meta['billable_rate'] ?? '' }}"
                                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" dusk="save-ticket-button"
                                        class="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm font-medium">
                                    Save Ticket
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Details Sidebar -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h4 class="font-semibold mb-4">Details</h4>
                        
                        <div class="space-y-4 text-sm">
                            <div>
                                <div class="text-gray-500 mb-1">Ticket Number</div>
                                <div class="font-medium" dusk="ticket-number">#{{ $conversation->number }}</div>
                            </div>

                            <div>
                                <div class="text-gray-500 mb-1">Mailbox</div>
                                <div class="font-medium">{{ $conversation->mailbox->name }}</div>
                            </div>
                            
                            <div>
                                <div class="text-gray-500 mb-1">Customer</div>
                                <a href="{{ route('customers.show', $conversation->customer) }}" class="font-medium text-blue-600 hover:underline">
                                    {{ $conversation->customer->getFullName() }}
                                </a>
                            </div>

                            @if($clientName)
                            <div>
                                <div class="text-gray-500 mb-1">Client</div>
                                <div class="font-medium">{{ $clientName }}</div>
                            </div>
                            @endif
                            
                            <div>
                                <div class="text-gray-500 mb-1">Assigned To</div>
                                <div class="font-medium">
                                    {{ $conversation->user ? $conversation->user->getFullName() : 'Unassigned' }}
                                </div>
                            </div>
                            
                            <div>
                                <div class="text-gray-500 mb-1">Created</div>
                                <div class="font-medium">{{ $conversation->created_at->format('M d, Y g:i A') }}</div>
                            </div>
                            
                            <div>
                                <div class="text-gray-500 mb-1">Last Reply</div>
                                <div class="font-medium">{{ $conversation->last_reply_at ? $conversation->last_reply_at->diffForHumans() : 'None' }}</div>
                            </div>
                        </div>
                        
                        @action('conversation.after_customer_sidebar', $conversation)

                        <div class="mt-6 pt-6 border-t border-gray-200">
                            @action('conversation.view.buttons', $conversation)
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
