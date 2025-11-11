{{-- Rich Text Editor Component --}}
{{-- Tiptap 2.x WYSIWYG editor with toolbar and advanced features --}}
{{-- Usage: @include('partials.editor', ['name' => 'content', 'value' => $content, 'id' => 'editor']) --}}

@php
    $editorId = $id ?? 'editor-' . uniqid();
    $editorName = $name ?? 'content';
    $editorValue = $value ?? '';
    $editorPlaceholder = $placeholder ?? __('Write your message here...');
    $editorHeight = $height ?? '300px';
    $editorClass = $class ?? '';
    $showToolbar = $showToolbar ?? true;
    $enableMentions = $enableMentions ?? true;
    $enableVariables = $enableVariables ?? true;
    $enableAttachments = $enableAttachments ?? false;
@endphp

<div class="editor-wrapper {{ $editorClass }}" x-data="richTextEditor{{ $editorId }}()">
    {{-- Toolbar --}}
    @if ($showToolbar)
    <div class="editor-toolbar border border-gray-300 rounded-t-md bg-gray-50 px-2 py-1 flex flex-wrap gap-1 items-center">
        {{-- Text formatting --}}
        <div class="flex gap-1 border-r border-gray-300 pr-2">
            <button type="button" @click="editor?.chain().focus().toggleBold().run()" 
                    :class="{'bg-gray-200': editor?.isActive('bold')}"
                    class="p-1.5 rounded hover:bg-gray-200 transition-colors" 
                    title="Bold (Ctrl+B)">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6z M6 12h9a4 4 0 014 4 4 4 0 01-4 4H6z" />
                </svg>
            </button>
            <button type="button" @click="editor?.chain().focus().toggleItalic().run()"
                    :class="{'bg-gray-200': editor?.isActive('italic')}"
                    class="p-1.5 rounded hover:bg-gray-200 transition-colors" 
                    title="Italic (Ctrl+I)">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 4h4M6 20h4M11 4L7 20" />
                </svg>
            </button>
            <button type="button" @click="editor?.chain().focus().toggleUnderline().run()"
                    :class="{'bg-gray-200': editor?.isActive('underline')}"
                    class="p-1.5 rounded hover:bg-gray-200 transition-colors" 
                    title="Underline (Ctrl+U)">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 20h14M7 4v7a5 5 0 0010 0V4" />
                </svg>
            </button>
            <button type="button" @click="editor?.chain().focus().toggleStrike().run()"
                    :class="{'bg-gray-200': editor?.isActive('strike')}"
                    class="p-1.5 rounded hover:bg-gray-200 transition-colors" 
                    title="Strikethrough">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12h18M6 7.5c0-2 1.5-3.5 4-3.5 2.5 0 4 1.5 4 3.5M10 16.5c0 2 1.5 3.5 4 3.5s4-1.5 4-3.5" />
                </svg>
            </button>
        </div>
        
        {{-- Lists --}}
        <div class="flex gap-1 border-r border-gray-300 pr-2">
            <button type="button" @click="editor?.chain().focus().toggleBulletList().run()"
                    :class="{'bg-gray-200': editor?.isActive('bulletList')}"
                    class="p-1.5 rounded hover:bg-gray-200 transition-colors" 
                    title="Bullet List">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <button type="button" @click="editor?.chain().focus().toggleOrderedList().run()"
                    :class="{'bg-gray-200': editor?.isActive('orderedList')}"
                    class="p-1.5 rounded hover:bg-gray-200 transition-colors" 
                    title="Numbered List">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 6h14M7 12h14M7 18h14M3 6h.01M3 12h.01M3 18h.01" />
                </svg>
            </button>
        </div>
        
        {{-- Headings --}}
        <div class="flex gap-1 border-r border-gray-300 pr-2">
            <button type="button" @click="editor?.chain().focus().toggleHeading({ level: 2 }).run()"
                    :class="{'bg-gray-200': editor?.isActive('heading', { level: 2 })}"
                    class="p-1.5 rounded hover:bg-gray-200 transition-colors text-sm font-semibold" 
                    title="Heading 2">
                H2
            </button>
            <button type="button" @click="editor?.chain().focus().toggleHeading({ level: 3 }).run()"
                    :class="{'bg-gray-200': editor?.isActive('heading', { level: 3 })}"
                    class="p-1.5 rounded hover:bg-gray-200 transition-colors text-sm font-semibold" 
                    title="Heading 3">
                H3
            </button>
        </div>
        
        {{-- Link --}}
        <div class="flex gap-1 border-r border-gray-300 pr-2">
            <button type="button" @click="setLink()"
                    :class="{'bg-gray-200': editor?.isActive('link')}"
                    class="p-1.5 rounded hover:bg-gray-200 transition-colors" 
                    title="Insert Link">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                </svg>
            </button>
        </div>
        
        {{-- Blockquote & Code --}}
        <div class="flex gap-1 border-r border-gray-300 pr-2">
            <button type="button" @click="editor?.chain().focus().toggleBlockquote().run()"
                    :class="{'bg-gray-200': editor?.isActive('blockquote')}"
                    class="p-1.5 rounded hover:bg-gray-200 transition-colors" 
                    title="Quote">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
            </button>
            <button type="button" @click="editor?.chain().focus().toggleCode().run()"
                    :class="{'bg-gray-200': editor?.isActive('code')}"
                    class="p-1.5 rounded hover:bg-gray-200 transition-colors" 
                    title="Inline Code">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                </svg>
            </button>
        </div>
        
        {{-- Alignment & Clear --}}
        <div class="flex gap-1">
            <button type="button" @click="editor?.chain().focus().clearNodes().unsetAllMarks().run()"
                    class="p-1.5 rounded hover:bg-gray-200 transition-colors" 
                    title="Clear Formatting">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
    @endif
    
    {{-- Editor Content Area --}}
    <div 
        x-ref="editorElement"
        class="editor-content prose prose-sm max-w-none border @if($showToolbar) border-t-0 @endif border-gray-300 rounded-b-md p-3 focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-indigo-500 bg-white"
        style="min-height: {{ $editorHeight }};"
    ></div>
    
    {{-- Hidden textarea to store content --}}
    <textarea 
        name="{{ $editorName }}" 
        id="{{ $editorId }}-input"
        class="hidden"
        x-model="content"
    >{{ $editorValue }}</textarea>
</div>

@once
    @push('scripts')
    <script type="module">
        import { Editor } from 'https://cdn.jsdelivr.net/npm/@tiptap/core@latest/dist/index.js';
        import StarterKit from 'https://cdn.jsdelivr.net/npm/@tiptap/starter-kit@latest/dist/index.js';
        import Link from 'https://cdn.jsdelivr.net/npm/@tiptap/extension-link@latest/dist/index.js';
        import Placeholder from 'https://cdn.jsdelivr.net/npm/@tiptap/extension-placeholder@latest/dist/index.js';
        import Underline from 'https://cdn.jsdelivr.net/npm/@tiptap/extension-underline@latest/dist/index.js';
        
        // Store Editor constructor globally for Alpine components
        window.TiptapEditor = Editor;
        window.TiptapExtensions = {
            StarterKit,
            Link,
            Placeholder,
            Underline
        };
    </script>
    @endpush
@endonce

@push('scripts')
<script>
    function richTextEditor{{ $editorId }}() {
        return {
            editor: null,
            content: @json($editorValue),
            
            init() {
                this.$nextTick(() => {
                    if (typeof window.TiptapEditor === 'undefined') {
                        console.error('Tiptap Editor not loaded');
                        return;
                    }
                    
                    this.editor = new window.TiptapEditor({
                        element: this.$refs.editorElement,
                        extensions: [
                            window.TiptapExtensions.StarterKit,
                            window.TiptapExtensions.Link.configure({
                                openOnClick: false,
                                HTMLAttributes: {
                                    class: 'text-indigo-600 hover:text-indigo-500 underline',
                                },
                            }),
                            window.TiptapExtensions.Placeholder.configure({
                                placeholder: '{{ $editorPlaceholder }}',
                            }),
                            window.TiptapExtensions.Underline,
                        ],
                        content: this.content,
                        onUpdate: ({ editor }) => {
                            this.content = editor.getHTML();
                        },
                        editorProps: {
                            attributes: {
                                class: 'prose prose-sm max-w-none focus:outline-none',
                            },
                        },
                    });
                });
            },
            
            setLink() {
                const previousUrl = this.editor.getAttributes('link').href;
                const url = window.prompt('Enter URL:', previousUrl);
                
                if (url === null) {
                    return;
                }
                
                if (url === '') {
                    this.editor.chain().focus().extendMarkRange('link').unsetLink().run();
                    return;
                }
                
                this.editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
            },
            
            destroy() {
                if (this.editor) {
                    this.editor.destroy();
                }
            }
        }
    }
</script>
@endpush

<style>
    .editor-content .tiptap {
        outline: none;
    }
    
    .editor-content .tiptap p.is-editor-empty:first-child::before {
        color: #adb5bd;
        content: attr(data-placeholder);
        float: left;
        height: 0;
        pointer-events: none;
    }
    
    .editor-content .tiptap ul,
    .editor-content .tiptap ol {
        padding-left: 1.5rem;
    }
    
    .editor-content .tiptap blockquote {
        padding-left: 1rem;
        border-left: 3px solid #d1d5db;
    }
    
    .editor-content .tiptap code {
        background-color: #f3f4f6;
        padding: 0.125rem 0.25rem;
        border-radius: 0.25rem;
        font-size: 0.875em;
    }
</style>
