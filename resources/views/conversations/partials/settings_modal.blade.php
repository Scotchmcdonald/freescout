{{-- Settings Modal - Conversation settings dialog --}}
<div class="modal-overlay fixed inset-0 bg-gray-900 bg-opacity-50 z-50 hidden" 
     id="settings-modal"
     x-data="{ open: false }"
     x-show="open"
     @keydown.escape.window="open = false"
     style="display: none;">
    
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full" @click.away="open = false">
            {{-- Modal Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">
                    {{ __('Conversation Settings') }}
                </h3>
                <button type="button" 
                        @click="open = false"
                        class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Modal Body --}}
            <form method="POST" 
                  action="{{ route('conversations.update_settings', $conversation->id ?? 0) }}"
                  class="p-6">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    {{-- Tags Section --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            {{ __('Tags') }}
                        </label>
                        <input type="text" 
                               name="tags" 
                               value="{{ old('tags', isset($conversation) ? implode(', ', $conversation->meta['tags'] ?? []) : '') }}"
                               placeholder="{{ __('Add tags (comma separated)') }}"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-500">
                            {{ __('Separate multiple tags with commas') }}
                        </p>
                    </div>

                    {{-- Priority Section --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            {{ __('Priority') }}
                        </label>
                        <select name="priority" 
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="normal" {{ old('priority', $conversation->meta['priority'] ?? 'normal') == 'normal' ? 'selected' : '' }}>
                                {{ __('Normal') }}
                            </option>
                            <option value="high" {{ old('priority', $conversation->meta['priority'] ?? 'normal') == 'high' ? 'selected' : '' }}>
                                {{ __('High') }}
                            </option>
                            <option value="urgent" {{ old('priority', $conversation->meta['priority'] ?? 'normal') == 'urgent' ? 'selected' : '' }}>
                                {{ __('Urgent') }}
                            </option>
                        </select>
                    </div>

                    {{-- Custom Fields Section --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            {{ __('Custom Fields') }}
                        </label>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">{{ __('Field 1') }}</label>
                                <input type="text" 
                                       name="custom_field_1" 
                                       value="{{ old('custom_field_1', $conversation->meta['custom_field_1'] ?? '') }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">{{ __('Field 2') }}</label>
                                <input type="text" 
                                       name="custom_field_2" 
                                       value="{{ old('custom_field_2', $conversation->meta['custom_field_2'] ?? '') }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>
                        </div>
                    </div>

                    {{-- Notes Section --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            {{ __('Internal Notes') }}
                        </label>
                        <textarea name="internal_notes" 
                                  rows="4"
                                  placeholder="{{ __('Add internal notes (not visible to customer)') }}"
                                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('internal_notes', $conversation->meta['internal_notes'] ?? '') }}</textarea>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" 
                            @click="open = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 transition">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition">
                        {{ __('Save Settings') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Open settings modal
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[data-trigger="settings-modal"]').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const modal = document.getElementById('settings-modal');
                if (modal) {
                    Alpine.store('settingsModal', { open: true });
                    modal.style.display = 'block';
                }
            });
        });
    });
</script>
