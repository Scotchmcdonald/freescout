{{-- Merge Conversations Dialog - Merge conversations interface --}}
<div class="merge-conv-dialog p-6">
    <form method="POST" 
          action="{{ route('conversations.merge', $conversation->id ?? 0) }}"
          onsubmit="return confirmMerge()">
        @csrf
        
        <div class="space-y-6">
            {{-- Warning Message --}}
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            <strong>{{ __('Warning:') }}</strong> {{ __('Merging conversations is permanent and cannot be undone.') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Current Conversation Info --}}
            @if(isset($conversation))
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-sm text-gray-600 mb-2">{{ __('Merging Conversation') }}</div>
                    <div class="font-medium text-gray-900">
                        #{{ $conversation->number }} - {{ $conversation->subject }}
                    </div>
                    <div class="text-sm text-gray-500 mt-1">
                        {{ $conversation->customer->getFullName(true) }} ({{ $conversation->customer_email }})
                    </div>
                </div>
            @endif

            {{-- Search for Target Conversation --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('Search for Target Conversation') }}
                </label>
                <p class="text-sm text-gray-500 mb-2">
                    {{ __('Find the conversation you want to merge this into.') }}
                </p>
                <input type="text" 
                       name="search_query"
                       placeholder="{{ __('Search by conversation number or subject...') }}"
                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                       @input.debounce.500ms="searchConversations($event.target.value)"
                       autocomplete="off">
            </div>

            {{-- Search Results --}}
            <div id="merge-search-results" class="space-y-2 hidden">
                {{-- Results will be populated via AJAX using merge_search_result.blade.php --}}
            </div>

            {{-- Selected Conversation Display --}}
            <div id="selected-conversation-display" class="hidden">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="text-sm text-blue-700 mb-1">{{ __('Selected Target Conversation') }}</div>
                    <div id="selected-conversation-info"></div>
                </div>
            </div>

            {{-- Merge Options --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('Merge Options') }}
                </label>
                <div class="space-y-2">
                    <label class="flex items-start">
                        <input type="checkbox" 
                               name="keep_threads" 
                               value="1" 
                               checked
                               class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">
                            {{ __('Keep all threads from both conversations') }}
                        </span>
                    </label>
                    <label class="flex items-start">
                        <input type="checkbox" 
                               name="update_customer" 
                               value="1"
                               class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">
                            {{ __('Update customer to match target conversation') }}
                        </span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="mt-6 flex items-center justify-end gap-3">
            <button type="button" 
                    onclick="window.parent.closeModal()"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 transition">
                {{ __('Cancel') }}
            </button>
            <button type="submit" 
                    id="merge-submit-btn"
                    disabled
                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                {{ __('Merge Conversations') }}
            </button>
        </div>
    </form>
</div>

<script>
    function searchConversations(query) {
        if (query.length < 2) {
            document.getElementById('merge-search-results').classList.add('hidden');
            return;
        }

        const currentConvId = {{ $conversation->id ?? 0 }};
        
        fetch('{{ route('conversations.search') }}?q=' + encodeURIComponent(query) + '&exclude=' + currentConvId, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            displayMergeResults(data.conversations || []);
        })
        .catch(error => {
            console.error('Search error:', error);
        });
    }

    function displayMergeResults(conversations) {
        const resultsContainer = document.getElementById('merge-search-results');
        
        if (conversations.length === 0) {
            resultsContainer.innerHTML = '<div class="text-sm text-gray-500 p-4 text-center">{{ __('No conversations found') }}</div>';
            resultsContainer.classList.remove('hidden');
            return;
        }

        // Render results - in production, this would use the merge_search_result partial
        resultsContainer.innerHTML = conversations.map(conv => `
            <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="radio" 
                           name="target_conversation_id" 
                           value="${conv.id}"
                           onchange="selectTargetConversation(${conv.id}, '${conv.number}', '${conv.subject}')"
                           class="mt-1 rounded-full border-gray-300 text-blue-600 focus:ring-blue-500">
                    <div class="flex-1">
                        <div class="font-medium text-gray-900">#${conv.number} - ${conv.subject}</div>
                        <div class="text-sm text-gray-500 mt-1">${conv.customer_name} (${conv.customer_email})</div>
                    </div>
                </label>
            </div>
        `).join('');
        
        resultsContainer.classList.remove('hidden');
    }

    function selectTargetConversation(id, number, subject) {
        const display = document.getElementById('selected-conversation-display');
        const info = document.getElementById('selected-conversation-info');
        const submitBtn = document.getElementById('merge-submit-btn');
        
        info.innerHTML = `<div class="font-medium">#${number} - ${subject}</div>`;
        display.classList.remove('hidden');
        submitBtn.disabled = false;
    }

    function confirmMerge() {
        return confirm('{{ __('Are you sure you want to merge these conversations? This action cannot be undone.') }}');
    }
</script>
