{{-- Editor Bottom Toolbar - Toolbar below rich text editor --}}
<div class="editor-bottom-toolbar border-t border-gray-200 bg-gray-50 p-3 rounded-b-lg">
    <div class="flex items-center justify-between">
        {{-- Left Side - Attachments & Templates --}}
        <div class="flex items-center gap-3">
            {{-- Attachment Button --}}
            <label class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 cursor-pointer transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                </svg>
                {{ __('Attach Files') }}
                <input type="file" name="attachments[]" multiple class="hidden" onchange="handleFileSelect(this)">
            </label>

            {{-- Template Selector --}}
            @if(isset($templates) && $templates->count() > 0)
                <div class="relative" x-data="{ open: false }">
                    <button type="button" 
                            @click="open = !open"
                            class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        {{ __('Templates') }}
                        <svg class="w-4 h-4 ml-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    
                    <div x-show="open" 
                         @click.away="open = false"
                         class="absolute left-0 bottom-full mb-2 w-64 bg-white rounded-md shadow-lg z-10 max-h-64 overflow-y-auto"
                         style="display: none;">
                        <div class="py-1">
                            @foreach($templates as $template)
                                <button type="button"
                                        onclick="insertTemplate({{ $template->id }}, '{{ addslashes($template->body) }}')"
                                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    {{ $template->name }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Saved Replies / Canned Responses --}}
            <div class="relative" x-data="{ open: false }">
                <button type="button" 
                        @click="open = !open"
                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                    {{ __('Saved Replies') }}
                </button>
                
                <div x-show="open" 
                     @click.away="open = false"
                     class="absolute left-0 bottom-full mb-2 w-64 bg-white rounded-md shadow-lg z-10"
                     style="display: none;">
                    <div class="p-2">
                        <input type="text" 
                               placeholder="{{ __('Search replies...') }}"
                               class="w-full border-gray-300 rounded-md text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="py-1 text-center text-sm text-gray-500">
                        {{ __('No saved replies') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Side - Action Buttons --}}
        <div class="flex items-center gap-2">
            {{-- Draft Save Button --}}
            <button type="button" 
                    onclick="saveDraft()"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                </svg>
                {{ __('Save Draft') }}
            </button>

            {{-- Send Button --}}
            <button type="submit" 
                    class="inline-flex items-center px-6 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
                {{ __('Send') }}
            </button>
        </div>
    </div>

    {{-- Attached Files Display --}}
    <div id="attached-files" class="mt-3 space-y-2 hidden"></div>

    {{-- Keyboard Shortcuts Info --}}
    <div class="mt-3 pt-3 border-t border-gray-200">
        <details class="text-xs text-gray-500">
            <summary class="cursor-pointer hover:text-gray-700">{{ __('Keyboard Shortcuts') }}</summary>
            <div class="mt-2 space-y-1">
                <div><kbd class="px-2 py-1 bg-gray-200 rounded">Ctrl+Enter</kbd> {{ __('Send message') }}</div>
                <div><kbd class="px-2 py-1 bg-gray-200 rounded">Ctrl+S</kbd> {{ __('Save draft') }}</div>
                <div><kbd class="px-2 py-1 bg-gray-200 rounded">Ctrl+K</kbd> {{ __('Insert link') }}</div>
                <div><kbd class="px-2 py-1 bg-gray-200 rounded">Ctrl+B</kbd> {{ __('Bold text') }}</div>
                <div><kbd class="px-2 py-1 bg-gray-200 rounded">Ctrl+I</kbd> {{ __('Italic text') }}</div>
            </div>
        </details>
    </div>
</div>

<script>
    function handleFileSelect(input) {
        const filesContainer = document.getElementById('attached-files');
        if (!input.files || input.files.length === 0) {
            filesContainer.classList.add('hidden');
            return;
        }

        filesContainer.classList.remove('hidden');
        filesContainer.innerHTML = '';

        Array.from(input.files).forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'flex items-center justify-between p-2 bg-white border border-gray-200 rounded';
            fileItem.innerHTML = `
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="text-sm text-gray-700">${file.name}</span>
                    <span class="text-xs text-gray-500">(${(file.size / 1024).toFixed(2)} KB)</span>
                </div>
                <button type="button" 
                        onclick="removeFile(${index})"
                        class="text-red-600 hover:text-red-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            `;
            filesContainer.appendChild(fileItem);
        });
    }

    function removeFile(index) {
        // In production, this would properly handle file removal
        const filesContainer = document.getElementById('attached-files');
        if (filesContainer.children.length <= 1) {
            filesContainer.classList.add('hidden');
        }
    }

    function insertTemplate(templateId, templateBody) {
        const editor = document.querySelector('[name="body"]');
        if (editor) {
            editor.value = templateBody;
        }
    }

    function saveDraft() {
        // Implement draft saving logic
        alert('{{ __('Draft saved') }}');
    }

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            document.querySelector('form').submit();
        } else if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            saveDraft();
        }
    });
</script>
