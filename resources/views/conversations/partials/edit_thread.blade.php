{{-- Edit Thread Partial - Inline thread editing form --}}
<div class="edit-thread-form" x-data="{ saving: false }">
    <form method="POST" 
          action="{{ route('conversations.update_thread', ['conversation' => $conversation->id ?? $thread->conversation_id, 'thread' => $thread->id]) }}"
          @submit.prevent="saving = true; $el.submit()">
        @csrf
        @method('PUT')
        
        <div class="bg-white rounded-lg border border-gray-300 p-4">
            {{-- Rich Text Editor --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('Message') }}
                </label>
                <textarea name="body" 
                          rows="10" 
                          required
                          class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                          :disabled="saving">{{ old('body', $thread->body) }}</textarea>
                @error('body')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Attachments Section --}}
            @if($thread->has_attachments && $thread->attachments && $thread->attachments->count() > 0)
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ __('Current Attachments') }}
                    </label>
                    <div class="space-y-2">
                        @foreach($thread->attachments as $attachment)
                            <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                <span class="text-sm text-gray-700">{{ $attachment->file_name }}</span>
                                <button type="button" 
                                        class="text-red-600 hover:text-red-800 text-sm"
                                        onclick="if(confirm('{{ __('Are you sure you want to remove this attachment?') }}')) { /* handle removal */ }">
                                    {{ __('Remove') }}
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Add New Attachments --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('Add Attachments') }}
                </label>
                <input type="file" 
                       name="attachments[]" 
                       multiple
                       class="block w-full text-sm text-gray-500
                              file:mr-4 file:py-2 file:px-4
                              file:rounded file:border-0
                              file:text-sm file:font-semibold
                              file:bg-blue-50 file:text-blue-700
                              hover:file:bg-blue-100"
                       :disabled="saving">
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center justify-end gap-2">
                <button type="button" 
                        class="px-4 py-2 text-sm bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition cancel-edit-trigger"
                        :disabled="saving">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" 
                        class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 transition flex items-center gap-2"
                        :disabled="saving">
                    <span x-show="!saving">{{ __('Save Changes') }}</span>
                    <span x-show="saving" style="display: none;">
                        <svg class="animate-spin h-4 w-4 mr-1 inline-block" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ __('Saving...') }}
                    </span>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    // Simple inline editing toggle
    document.addEventListener('DOMContentLoaded', function() {
        // Show edit form when edit button clicked
        document.querySelectorAll('.edit-draft-trigger, .edit-thread-trigger').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const threadId = this.closest('[data-thread-id]').dataset.threadId;
                const threadEl = document.getElementById('thread-' + threadId);
                const editForm = threadEl.querySelector('.edit-thread-form');
                const threadBody = threadEl.querySelector('.thread-body');
                
                if (editForm && threadBody) {
                    threadBody.style.display = 'none';
                    editForm.style.display = 'block';
                }
            });
        });
        
        // Cancel editing
        document.querySelectorAll('.cancel-edit-trigger').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const editForm = this.closest('.edit-thread-form');
                const threadEl = this.closest('[data-thread-id]');
                const threadBody = threadEl.querySelector('.thread-body');
                
                if (editForm && threadBody) {
                    editForm.style.display = 'none';
                    threadBody.style.display = 'block';
                }
            });
        });
    });
</script>
