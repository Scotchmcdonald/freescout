{{-- Conversations Table - Table view of conversations (alternative to list) --}}
@php
    $conversations = $conversations ?? collect([]);
@endphp

<div class="conversations-table overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="w-12 px-3 py-3 text-left">
                    <input type="checkbox" 
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                           onclick="toggleAllConversations(this)">
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" onclick="sortTable('number')">
                    <div class="flex items-center gap-1">
                        {{ __('#') }}
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                        </svg>
                    </div>
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" onclick="sortTable('status')">
                    {{ __('Status') }}
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" onclick="sortTable('subject')">
                    {{ __('Subject') }}
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" onclick="sortTable('customer')">
                    {{ __('Customer') }}
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" onclick="sortTable('assigned')">
                    {{ __('Assigned') }}
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" onclick="sortTable('updated')">
                    {{ __('Last Activity') }}
                </th>
                <th scope="col" class="relative px-6 py-3">
                    <span class="sr-only">{{ __('Actions') }}</span>
                </th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($conversations as $conversation)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-3 py-4 whitespace-nowrap">
                        <input type="checkbox" 
                               class="conversation-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                               value="{{ $conversation->id }}">
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <a href="{{ route('conversations.show', $conversation) }}" 
                           class="text-sm font-medium text-blue-600 hover:underline">
                            #{{ $conversation->number }}
                        </a>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @include('conversations.partials.badges', ['conversation' => $conversation])
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">
                            <a href="{{ route('conversations.show', $conversation) }}" 
                               class="hover:text-blue-600">
                                {{ $conversation->subject }}
                            </a>
                        </div>
                        @if($conversation->preview)
                            <div class="text-sm text-gray-500 truncate max-w-md">
                                {{ $conversation->preview }}
                            </div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-8 w-8">
                                <div class="h-8 w-8 rounded-full bg-gray-400 flex items-center justify-center text-white text-xs font-semibold">
                                    {{ substr($conversation->customer->first_name, 0, 1) }}{{ substr($conversation->customer->last_name ?? '', 0, 1) }}
                                </div>
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $conversation->customer->getFullName(true) }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ $conversation->customer_email }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($conversation->user)
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-6 w-6">
                                    <div class="h-6 w-6 rounded-full bg-blue-600 flex items-center justify-center text-white text-xs font-semibold">
                                        {{ substr($conversation->user->first_name, 0, 1) }}{{ substr($conversation->user->last_name, 0, 1) }}
                                    </div>
                                </div>
                                <div class="ml-2 text-sm text-gray-900">
                                    {{ $conversation->user->getFullName() }}
                                </div>
                            </div>
                        @else
                            <span class="text-sm text-gray-500">{{ __('Unassigned') }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <div>{{ $conversation->updated_at->diffForHumans() }}</div>
                        <div class="text-xs text-gray-400">
                            {{ $conversation->updated_at->format('M d, Y') }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('conversations.show', $conversation) }}" 
                               class="text-blue-600 hover:text-blue-900">
                                {{ __('View') }}
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center">
                        <div class="text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                            <p class="mt-2">{{ __('No conversations found') }}</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
    function toggleAllConversations(checkbox) {
        const checkboxes = document.querySelectorAll('.conversation-checkbox');
        checkboxes.forEach(cb => cb.checked = checkbox.checked);
    }

    function sortTable(column) {
        // Implement sorting logic
        const url = new URL(window.location.href);
        url.searchParams.set('sort', column);
        url.searchParams.set('direction', url.searchParams.get('direction') === 'asc' ? 'desc' : 'asc');
        window.location.href = url.toString();
    }
</script>
