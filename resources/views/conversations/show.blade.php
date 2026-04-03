<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-neutral-800 leading-tight">
                Ticket #{{ $conversation->number }} - {{ $conversation->subject }}
            </h2>
            <div class="flex items-center gap-4">
                <span class="text-sm">
                    @if ($conversation->status == 1)
                        Status: <span class="text-success-600 font-medium">Open</span>
                    @elseif($conversation->status == 2)
                        Status: <span class="text-warning-600 font-medium">Awaiting Client Response</span>
                    @elseif($conversation->status == 3)
                        @if (($conversation->meta['status_display'] ?? '') === 'resolved')
                            Status: <span class="text-primary-600 font-medium">Resolved</span>
                        @else
                            Status: <span class="text-neutral-600 font-medium">Closed</span>
                        @endif
                    @else
                        Status: <span class="text-neutral-600 font-medium">Pending</span>
                    @endif
                </span>
                <span class="text-sm text-neutral-500">Last updated:
                    {{ $conversation->updated_at->diffForHumans() }}</span>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Success/Error Messages -->
            @if (session('success'))
                <div class="mb-4 bg-success-50 border border-success-200 text-success-800 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 bg-danger-50 border border-danger-200 text-danger-800 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            @php
                $meta = $conversation->meta ?? [];
                $clientName = '';
                if (\Illuminate\Support\Facades\Schema::hasTable('client_conversations')) {
                    $clientLink = \Illuminate\Support\Facades\DB::table('client_conversations')
                        ->where('conversation_id', $conversation->id)
                        ->first();
                    if ($clientLink && \Illuminate\Support\Facades\Schema::hasTable('clients')) {
                        $crmClient = \Illuminate\Support\Facades\DB::table('clients')
                            ->where('id', $clientLink->client_id)
                            ->first();
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
                        <div class="p-6 text-neutral-900">
                            <div class="mb-6 rounded-lg border border-info-200 bg-info-50 p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wider text-info-700">Time
                                            Since Last Contact</p>
                                        <p class="mt-1 text-2xl font-bold text-info-900">
                                            {{ $conversation->time_since_last_contact ?? 'No contact yet' }}</p>
                                    </div>
                                    @if ($conversation->last_contact_at)
                                        <p class="text-xs text-info-700">Last contact:
                                            {{ $conversation->last_contact_at->format('M d, Y g:i A') }}</p>
                                    @endif
                                </div>
                            </div>

                            <!-- Conversation Header -->
                            <div class="mb-6 pb-6 border-b border-neutral-200">
                                <div class="flex justify-between items-start mb-4">
                                    <div class="flex-1">
                                        <h3 class="text-2xl font-semibold mb-2">{{ $conversation->subject }}</h3>
                                        @action('conversation.after_subject', $conversation)
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @action('conversation.action_buttons', $conversation)
                                    </div>
                                </div>
                                <div class="flex items-center space-x-4 text-sm text-neutral-600">
                                    @if ($clientName)
                                        <span class="font-medium text-neutral-900">{{ $clientName }}</span>
                                        <span>•</span>
                                    @endif
                                    <span>{{ $conversation->customer?->getFullName() ?? ($conversation->sender_name ?? __('Unknown')) }}</span>
                                    <span>•</span>
                                    <span>{{ $conversation->customer_email }}</span>
                                    <span>•</span>
                                    <span>{{ $conversation->created_at->format('M d, Y g:i A') }}</span>
                                </div>
                                @if (isset($meta['closed_by']) && $meta['closed_by'] === 'client')
                                    <div class="mt-2 text-sm text-neutral-500">Closed by client</div>
                                @endif
                                @if (isset($meta['reopened_reason']))
                                    <div class="mt-2 text-sm text-neutral-500">Reopened: {{ $meta['reopened_reason'] }}
                                    </div>
                                @endif
                            </div>

                            <!-- Threads -->
                            <div class="space-y-6">
                                @action('conversation.before_threads', $conversation)
                                @foreach ($conversation->threads as $thread)
                                    <div
                                        class="border border-neutral-200 rounded-lg p-4 {{ $thread->type == 2 ? 'bg-warning-50' : '' }}">
                                        <div class="flex items-start justify-between mb-3">
                                            <div class="flex items-center space-x-3">
                                                @if ($thread->user)
                                                    <div
                                                        class="w-10 h-10 rounded-full bg-primary-600 flex items-center justify-center text-white font-semibold">
                                                        {{ substr($thread->user->first_name, 0, 1) }}{{ substr($thread->user->last_name, 0, 1) }}
                                                    </div>
                                                    <div>
                                                        <div class="font-medium text-neutral-900">
                                                            {{ $thread->user->getFullName() }}</div>
                                                        <div class="text-sm text-neutral-500">
                                                            {{ $thread->created_at->diffForHumans() }}</div>
                                                    </div>
                                                @else
                                                    <div
                                                        class="w-10 h-10 rounded-full bg-neutral-400 flex items-center justify-center text-white font-semibold">
                                                        {{ substr($conversation->customer->first_name ?? ($conversation->sender_name ?? 'C'), 0, 1) }}
                                                    </div>
                                                    <div>
                                                        <div class="font-medium text-neutral-900">
                                                            {{ $conversation->customer?->getFullName() ?? ($conversation->sender_name ?? __('Unknown')) }}
                                                        </div>
                                                        <div class="text-sm text-neutral-500">
                                                            {{ $thread->created_at->diffForHumans() }}</div>
                                                    </div>
                                                @endif
                                            </div>

                                            @if ($thread->type == 2)
                                                <span
                                                    class="px-2 py-1 text-xs font-medium bg-warning-200 text-warning-800 rounded">Note</span>
                                            @endif
                                        </div>

                                        <div class="prose max-w-none thread-body">
                                            @php
                                                $threadBody = (string) ($thread->body ?? '');
                                                $containsHtml = $threadBody !== strip_tags($threadBody);
                                                $renderedBody = $containsHtml
                                                    ? clean($threadBody, 'default')
                                                    : nl2br(e($threadBody));
                                            @endphp
                                            {!! $renderedBody !!}
                                        </div>

                                        @if ($thread->attachments && $thread->attachments->count())
                                            <div class="mt-4 pt-4 border-t border-neutral-200">
                                                <div class="text-sm font-medium text-neutral-700 mb-2">
                                                    {{ $thread->attachments->count() }}
                                                    attachment{{ $thread->attachments->count() > 1 ? 's' : '' }}</div>
                                                <div class="space-y-1">
                                                    @foreach ($thread->attachments as $attachment)
                                                        <a href="{{ $attachment->url }}" dusk="view-attachment"
                                                            class="text-sm text-primary-600 hover:underline flex items-center">
                                                            <svg class="w-4 h-4 mr-1" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                                            </svg>
                                                            {{ $attachment->file_name }}
                                                            ({{ number_format($attachment->size / 1024, 2) }} KB)
                                                        </a>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                                @action('conversation.after_threads', $conversation)
                            </div>

                            @if (isset($meta['client_rating']))
                                <div class="mt-6 pt-6 border-t border-neutral-200">
                                    <h4 class="font-semibold mb-2">Client Rating</h4>
                                    <div class="text-warning-500">
                                        {{ str_repeat('★', (int) $meta['client_rating']) }}{{ str_repeat('☆', 5 - (int) $meta['client_rating']) }}
                                    </div>
                                    @if (isset($meta['client_feedback']))
                                        <p class="text-sm text-neutral-600 mt-1">{{ $meta['client_feedback'] }}</p>
                                    @endif
                                </div>
                            @endif

                            <!-- Reply Form -->
                            <div class="mt-6 pt-6 border-t border-neutral-200">
                                <form action="{{ route('conversations.reply', $conversation) }}" method="POST">
                                    @csrf
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-neutral-700 mb-2">Reply</label>
                                        <textarea name="body" rows="6" required dusk="reply-message"
                                            class="w-full border-neutral-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <label class="flex items-center">
                                                <input type="radio" name="type" value="1" checked
                                                    class="mr-1">
                                                <span class="text-sm">Reply</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input type="radio" name="type" value="2" class="mr-1">
                                                <span class="text-sm">Note</span>
                                            </label>
                                        </div>

                                        <button type="submit" dusk="send-reply-button"
                                            class="px-4 py-2 bg-primary-600 text-white rounded hover:bg-primary-700">
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
                                    <label for="status"
                                        class="block text-sm font-medium text-neutral-700 mb-1">Status</label>
                                    <select name="status" id="status" dusk="status-select"
                                        class="w-full border-neutral-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                                        <option value="active" {{ $conversation->status == 1 ? 'selected' : '' }}>
                                            Active</option>
                                        <option value="pending" {{ $conversation->status == 2 ? 'selected' : '' }}>
                                            Pending</option>
                                        <option value="closed" {{ $conversation->status == 3 ? 'selected' : '' }}>
                                            Closed</option>
                                        <option value="resolved"
                                            {{ $conversation->status == 3 && ($meta['status_display'] ?? '') === 'resolved' ? 'selected' : '' }}>
                                            Resolved</option>
                                    </select>
                                </div>

                                <!-- Resolution Notes -->
                                <div>
                                    <label for="resolution_notes"
                                        class="block text-sm font-medium text-neutral-700 mb-1">Resolution
                                        Notes</label>
                                    <textarea name="resolution_notes" id="resolution_notes" rows="3" dusk="resolution-notes"
                                        class="w-full border-neutral-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
                                        placeholder="Notes about the resolution...">{{ $meta['resolution_notes'] ?? '' }}</textarea>
                                </div>

                                <div class="pt-4 border-t border-neutral-200">
                                    <h5 class="text-sm font-semibold mb-3">Focus & Follow-Up</h5>

                                    <div class="space-y-3">
                                        <div>
                                            <label for="waiting_on_user_id"
                                                class="block text-sm text-neutral-700 mb-1">Waiting On</label>
                                            <select name="waiting_on_user_id" id="waiting_on_user_id"
                                                class="w-full border-neutral-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                                                <option value="">Unassigned</option>
                                                @foreach ($waitingOnUsers as $waitingUser)
                                                    <option value="{{ $waitingUser->id }}"
                                                        {{ (int) $conversation->waiting_on_user_id === (int) $waitingUser->id ? 'selected' : '' }}>
                                                        {{ $waitingUser->getFullName() }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label for="waiting_reason"
                                                class="block text-sm text-neutral-700 mb-1">Reason</label>
                                            <select name="waiting_reason" id="waiting_reason"
                                                class="w-full border-neutral-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                                                <option value="">Not set</option>
                                                @foreach ($waitingReasons as $reasonValue => $reasonLabel)
                                                    <option value="{{ $reasonValue }}"
                                                        {{ $conversation->waiting_reason === $reasonValue ? 'selected' : '' }}>
                                                        {{ $reasonLabel }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label for="next_follow_up"
                                                class="block text-sm text-neutral-700 mb-1">Next Follow-Up</label>
                                            <input type="datetime-local" name="next_follow_up" id="next_follow_up"
                                                value="{{ $conversation->next_follow_up?->format('Y-m-d\TH:i') }}"
                                                class="w-full border-neutral-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                                        </div>
                                    </div>
                                </div>

                                <!-- Billing Section -->
                                <div class="pt-4 border-t border-neutral-200">
                                    <h5 class="text-sm font-semibold mb-3">Billing</h5>

                                    <div class="space-y-3">
                                        <label class="flex items-center">
                                            <input type="checkbox" name="is_billable" value="1"
                                                dusk="billable-checkbox"
                                                {{ !empty($meta['is_billable']) ? 'checked' : '' }}
                                                class="rounded border-neutral-300 text-primary-600 shadow-sm focus:ring-primary-500">
                                            <span class="ml-2 text-sm text-neutral-700">Billable</span>
                                        </label>

                                        <div>
                                            <label for="billable_hours"
                                                class="block text-sm text-neutral-700 mb-1">Hours</label>
                                            <input type="number" name="billable_hours" id="billable_hours"
                                                dusk="billable-hours" step="0.5" min="0"
                                                value="{{ $meta['billable_hours'] ?? '' }}"
                                                class="w-full border-neutral-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                                        </div>

                                        <div>
                                            <label for="billable_rate"
                                                class="block text-sm text-neutral-700 mb-1">Rate
                                                ($/hr)</label>
                                            <input type="number" name="billable_rate" id="billable_rate"
                                                dusk="billable-rate" step="0.01" min="0"
                                                value="{{ $meta['billable_rate'] ?? '' }}"
                                                class="w-full border-neutral-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" dusk="save-ticket-button"
                                    class="w-full px-4 py-2 bg-primary-600 text-white rounded hover:bg-primary-700 text-sm font-medium">
                                    Save Ticket
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                        <h4 class="font-semibold mb-4">Quick Snooze</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <form method="POST" action="{{ route('tickets.snooze', $conversation) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="add_hours" value="2">
                                <button type="submit"
                                    class="w-full px-3 py-2 rounded border border-primary-200 bg-primary-50 text-primary-700 hover:bg-primary-100 text-sm font-medium">
                                    +2 Hours
                                </button>
                            </form>
                            <form method="POST" action="{{ route('tickets.snooze', $conversation) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="add_days" value="1">
                                <button type="submit"
                                    class="w-full px-3 py-2 rounded border border-primary-200 bg-primary-50 text-primary-700 hover:bg-primary-100 text-sm font-medium">
                                    +1 Day
                                </button>
                            </form>
                            <form method="POST" action="{{ route('tickets.snooze', $conversation) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="to_next_week" value="1">
                                <button type="submit"
                                    class="w-full px-3 py-2 rounded border border-primary-200 bg-primary-50 text-primary-700 hover:bg-primary-100 text-sm font-medium">
                                    +Next Week
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Details Sidebar -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h4 class="font-semibold mb-4">Details</h4>

                        <div class="space-y-4 text-sm">
                            <div>
                                <div class="text-neutral-500 mb-1">Ticket Number</div>
                                <div class="font-medium" dusk="ticket-number">#{{ $conversation->number }}</div>
                            </div>

                            <div>
                                <div class="text-neutral-500 mb-1">Mailbox</div>
                                <div class="font-medium">{{ $conversation->mailbox->name }}</div>
                            </div>

                            <div>
                                <div class="text-neutral-500 mb-1">Customer</div>
                                @if ($conversation->customer)
                                    <a href="{{ route('customers.show', $conversation->customer) }}"
                                        class="font-medium text-primary-600 hover:underline">
                                        {{ $conversation->customer->getFullName() }}
                                    </a>
                                @else
                                    <span
                                        class="font-medium">{{ $conversation->sender_name ?? ($conversation->customer_email ?? __('Unknown')) }}</span>
                                @endif
                            </div>

                            @if ($clientName)
                                <div>
                                    <div class="text-neutral-500 mb-1">Client</div>
                                    <div class="font-medium">{{ $clientName }}</div>
                                </div>
                            @endif

                            <div>
                                <div class="text-neutral-500 mb-1">Assigned To</div>
                                <div class="font-medium">
                                    {{ $conversation->user ? $conversation->user->getFullName() : 'Unassigned' }}
                                </div>
                            </div>

                            <div>
                                <div class="text-neutral-500 mb-1">Created</div>
                                <div class="font-medium">{{ $conversation->created_at->format('M d, Y g:i A') }}</div>
                            </div>

                            <div>
                                <div class="text-neutral-500 mb-1">Last Reply</div>
                                <div class="font-medium">
                                    {{ $conversation->last_reply_at ? $conversation->last_reply_at->diffForHumans() : 'None' }}
                                </div>
                            </div>

                            <div>
                                <div class="text-neutral-500 mb-1">Waiting On</div>
                                <div class="font-medium">
                                    {{ $conversation->waitingOnUser?->getFullName() ?? 'Unassigned' }}</div>
                            </div>

                            <div>
                                <div class="text-neutral-500 mb-1">Waiting Reason</div>
                                <div class="font-medium">{{ $conversation->waiting_reason ?? 'Not set' }}</div>
                            </div>

                            <div>
                                <div class="text-neutral-500 mb-1">Next Follow-Up</div>
                                <div class="font-medium">
                                    {{ $conversation->next_follow_up?->format('M d, Y g:i A') ?? 'Not scheduled' }}
                                </div>
                            </div>
                        </div>

                        @action('conversation.after_customer_sidebar', $conversation)

                        <div class="mt-6 pt-6 border-t border-neutral-200">
                            @action('conversation.view.buttons', $conversation)
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
