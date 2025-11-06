/**
 * Modern Rich Text Editor using Tiptap (replaces Summernote)
 * Provides similar functionality with modern architecture
 */

import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Placeholder from '@tiptap/extension-placeholder';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';

export class RichTextEditor {
    constructor(element, options = {}) {
        this.element = element;
        this.options = {
            placeholder: options.placeholder || 'Type your message...',
            minHeight: options.minHeight || '200px',
            editable: options.editable !== false,
            onUpdate: options.onUpdate || (() => {}),
            onBlur: options.onBlur || (() => {}),
            onFocus: options.onFocus || (() => {}),
            ...options
        };
        
        this.editor = null;
        this.init();
    }

    init() {
        // Create editor container
        const editorContainer = document.createElement('div');
        editorContainer.className = 'rich-text-editor';
        
        // Create toolbar
        const toolbar = this.createToolbar();
        editorContainer.appendChild(toolbar);
        
        // Create editor element
        const editorElement = document.createElement('div');
        editorElement.className = 'editor-content';
        editorElement.style.minHeight = this.options.minHeight;
        editorContainer.appendChild(editorElement);
        
        // Replace original element with our editor
        this.element.style.display = 'none';
        this.element.parentNode.insertBefore(editorContainer, this.element.nextSibling);
        
        // Initialize Tiptap editor
        this.editor = new Editor({
            element: editorElement,
            extensions: [
                StarterKit.configure({
                    heading: {
                        levels: [1, 2, 3]
                    }
                }),
                Placeholder.configure({
                    placeholder: this.options.placeholder
                }),
                Link.configure({
                    openOnClick: false,
                    HTMLAttributes: {
                        class: 'text-blue-600 hover:underline'
                    }
                }),
                Image.configure({
                    inline: true,
                    allowBase64: true
                })
            ],
            content: this.element.value,
            editable: this.options.editable,
            onUpdate: ({ editor }) => {
                this.element.value = editor.getHTML();
                this.options.onUpdate(editor.getHTML());
            },
            onBlur: ({ editor }) => {
                this.options.onBlur(editor);
            },
            onFocus: ({ editor }) => {
                this.options.onFocus(editor);
            }
        });
    }

    createToolbar() {
        const toolbar = document.createElement('div');
        toolbar.className = 'editor-toolbar flex items-center gap-1 p-2 bg-gray-50 border border-gray-300 rounded-t';
        
        const buttons = [
            { 
                icon: 'B', 
                title: 'Bold', 
                action: () => this.editor.chain().focus().toggleBold().run(),
                isActive: () => this.editor.isActive('bold')
            },
            { 
                icon: 'I', 
                title: 'Italic', 
                action: () => this.editor.chain().focus().toggleItalic().run(),
                isActive: () => this.editor.isActive('italic')
            },
            { 
                icon: 'U', 
                title: 'Underline', 
                action: () => this.editor.chain().focus().toggleUnderline().run(),
                isActive: () => this.editor.isActive('underline')
            },
            { type: 'separator' },
            { 
                icon: '●', 
                title: 'Bullet List', 
                action: () => this.editor.chain().focus().toggleBulletList().run(),
                isActive: () => this.editor.isActive('bulletList')
            },
            { 
                icon: '1.', 
                title: 'Numbered List', 
                action: () => this.editor.chain().focus().toggleOrderedList().run(),
                isActive: () => this.editor.isActive('orderedList')
            },
            { type: 'separator' },
            { 
                icon: '🔗', 
                title: 'Insert Link', 
                action: () => this.insertLink()
            },
            { 
                icon: '🖼', 
                title: 'Insert Image', 
                action: () => this.insertImage()
            },
            { 
                icon: '📎', 
                title: 'Attach File', 
                action: () => this.options.onAttachment?.()
            },
            { type: 'separator' },
            { 
                icon: '✓', 
                title: 'Save Draft', 
                action: () => this.options.onSaveDraft?.(),
                className: 'text-green-600'
            },
            { 
                icon: '🗑', 
                title: 'Discard', 
                action: () => this.options.onDiscard?.(),
                className: 'text-red-600'
            }
        ];
        
        buttons.forEach(btn => {
            if (btn.type === 'separator') {
                const sep = document.createElement('div');
                sep.className = 'w-px h-6 bg-gray-300 mx-1';
                toolbar.appendChild(sep);
            } else {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = `editor-btn px-3 py-1 rounded hover:bg-gray-200 ${btn.className || ''}`;
                button.innerHTML = btn.icon;
                button.title = btn.title;
                
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    btn.action();
                    if (btn.isActive) {
                        button.classList.toggle('active', btn.isActive());
                    }
                });
                
                toolbar.appendChild(button);
            }
        });
        
        return toolbar;
    }

    insertLink() {
        const url = prompt('Enter URL:');
        if (url) {
            this.editor.chain().focus().setLink({ href: url }).run();
        }
    }

    insertImage() {
        const url = prompt('Enter image URL:');
        if (url) {
            this.editor.chain().focus().setImage({ src: url }).run();
        }
    }

    getHTML() {
        return this.editor.getHTML();
    }

    setContent(content) {
        this.editor.commands.setContent(content);
    }

    destroy() {
        if (this.editor) {
            this.editor.destroy();
        }
    }

    focus() {
        this.editor.commands.focus();
    }
}

// Auto-initialize editors
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.rich-text-editor-init').forEach(element => {
        new RichTextEditor(element);
    });
});

export default RichTextEditor;
