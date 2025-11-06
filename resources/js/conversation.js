/**
 * Conversation Page Functionality
 * Handles conversation view, replies, notes, and real-time updates
 */

import { UIHelpers } from './ui-helpers';
import { RichTextEditor } from './editor';

export class ConversationManager {
    constructor() {
        this.conversationId = document.querySelector('meta[name="conversation-id"]')?.content;
        this.editor = null;
        this.autoSaveTimer = null;
        this.autoSaveInterval = 12000; // 12 seconds
        this.init();
    }

    init() {
        if (!this.conversationId) return;

        this.initEditor();
        this.initReplyForm();
        this.initAutoSave();
        this.initConversationActions();
        this.loadDraft();
        this.initCollisionDetection();
    }

    initEditor() {
        const editorElement = document.querySelector('#reply-body');
        if (editorElement) {
            this.editor = new RichTextEditor(editorElement, {
                placeholder: 'Type your reply...',
                minHeight: '200px',
                onUpdate: (content) => {
                    this.onEditorChange(content);
                },
                onSaveDraft: () => this.saveDraft(true),
                onDiscard: () => this.discardDraft(),
                onAttachment: () => this.showAttachmentDialog()
            });
        }
    }

    initReplyForm() {
        const form = document.querySelector('#conversation-reply-form');
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.submitReply();
            });
        }

        // Handle reply type switcher (reply vs note)
        document.querySelectorAll('[name="reply_type"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.switchReplyType(e.target.value);
            });
        });
    }

    initAutoSave() {
        // Auto-save draft every 12 seconds when content changes
        let contentChanged = false;

        if (this.editor) {
            this.editor.editor.on('update', () => {
                contentChanged = true;
            });
        }

        this.autoSaveTimer = setInterval(() => {
            if (contentChanged) {
                this.saveDraft(false);
                contentChanged = false;
            }
        }, this.autoSaveInterval);
    }

    initConversationActions() {
        // Status change
        document.querySelectorAll('[data-action="change-status"]').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const status = btn.dataset.status;
                await this.changeStatus(status);
            });
        });

        // Assign user
        document.querySelectorAll('[data-action="assign-user"]').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const userId = btn.dataset.userId;
                await this.assignUser(userId);
            });
        });

        // Follow/unfollow
        document.querySelector('[data-action="toggle-follow"]')?.addEventListener('click', async (e) => {
            e.preventDefault();
            await this.toggleFollow();
        });

        // Star/unstar
        document.querySelector('[data-action="toggle-star"]')?.addEventListener('click', async (e) => {
            e.preventDefault();
            await this.toggleStar();
        });
    }

    onEditorChange(content) {
        // Mark that content has changed
        window.fsReplyChanged = true;
        
        // Clear auto-save indicator
        const indicator = document.querySelector('#autosave-indicator');
        if (indicator) {
            indicator.textContent = '';
        }
    }

    async saveDraft(showNotification = false) {
        const content = this.editor?.getHTML();
        if (!content || content === '<p></p>') return;

        try {
            const response = await fetch(`/conversations/${this.conversationId}/draft`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ content })
            });

            if (response.ok) {
                const indicator = document.querySelector('#autosave-indicator');
                if (indicator) {
                    indicator.textContent = 'Draft saved at ' + new Date().toLocaleTimeString();
                    indicator.className = 'text-sm text-green-600';
                }

                if (showNotification) {
                    UIHelpers.showToast('Draft saved', 'success');
                }
            }
        } catch (error) {
            console.error('Failed to save draft:', error);
            if (showNotification) {
                UIHelpers.showToast('Failed to save draft', 'error');
            }
        }
    }

    async loadDraft() {
        try {
            const response = await fetch(`/conversations/${this.conversationId}/draft`);
            if (response.ok) {
                const data = await response.json();
                if (data.content && this.editor) {
                    this.editor.setContent(data.content);
                }
            }
        } catch (error) {
            console.error('Failed to load draft:', error);
        }
    }

    async discardDraft() {
        const confirmed = await UIHelpers.confirm(
            'Discard Draft',
            'Are you sure you want to discard this draft?',
            'Discard',
            'Cancel'
        );

        if (confirmed) {
            this.editor?.setContent('');
            
            try {
                await fetch(`/conversations/${this.conversationId}/draft`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                UIHelpers.showToast('Draft discarded', 'success');
            } catch (error) {
                console.error('Failed to discard draft:', error);
            }
        }
    }

    async submitReply() {
        const form = document.querySelector('#conversation-reply-form');
        const content = this.editor?.getHTML();

        if (!content || content === '<p></p>') {
            UIHelpers.showToast('Please enter a message', 'warning');
            return;
        }

        UIHelpers.showLoading('Sending...');

        try {
            const formData = new FormData(form);
            formData.set('body', content);

            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            });

            UIHelpers.hideLoading();

            if (response.ok) {
                const data = await response.json();
                UIHelpers.showToast('Reply sent successfully', 'success');
                
                // Clear editor
                this.editor?.setContent('');
                
                // Reload conversation or append new thread
                window.location.reload();
            } else {
                const error = await response.json();
                UIHelpers.showToast(error.message || 'Failed to send reply', 'error');
            }
        } catch (error) {
            UIHelpers.hideLoading();
            console.error('Failed to submit reply:', error);
            UIHelpers.showToast('Failed to send reply', 'error');
        }
    }

    switchReplyType(type) {
        const form = document.querySelector('#conversation-reply-form');
        if (form) {
            form.dataset.replyType = type;
            
            // Update UI to show note vs reply
            const noteIndicator = document.querySelector('#note-indicator');
            if (noteIndicator) {
                noteIndicator.classList.toggle('hidden', type !== 'note');
            }
        }
    }

    async changeStatus(status) {
        try {
            const response = await fetch(`/conversations/${this.conversationId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ status })
            });

            if (response.ok) {
                UIHelpers.showToast('Status updated', 'success');
                // Update status badge in UI
                const badge = document.querySelector('#conversation-status');
                if (badge) {
                    badge.textContent = this.getStatusLabel(status);
                    badge.className = this.getStatusClass(status);
                }
            }
        } catch (error) {
            console.error('Failed to change status:', error);
            UIHelpers.showToast('Failed to update status', 'error');
        }
    }

    async assignUser(userId) {
        try {
            const response = await fetch(`/conversations/${this.conversationId}/assign`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ user_id: userId })
            });

            if (response.ok) {
                UIHelpers.showToast('Assignee updated', 'success');
                window.location.reload();
            }
        } catch (error) {
            console.error('Failed to assign user:', error);
            UIHelpers.showToast('Failed to update assignee', 'error');
        }
    }

    async toggleFollow() {
        try {
            const response = await fetch(`/conversations/${this.conversationId}/follow`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (response.ok) {
                const data = await response.json();
                UIHelpers.showToast(data.following ? 'Following conversation' : 'Unfollowed conversation', 'success');
                
                const btn = document.querySelector('[data-action="toggle-follow"]');
                if (btn) {
                    btn.textContent = data.following ? 'Unfollow' : 'Follow';
                }
            }
        } catch (error) {
            console.error('Failed to toggle follow:', error);
        }
    }

    async toggleStar() {
        try {
            const response = await fetch(`/conversations/${this.conversationId}/star`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (response.ok) {
                const data = await response.json();
                const icon = document.querySelector('[data-action="toggle-star"] svg');
                if (icon) {
                    icon.classList.toggle('fill-yellow-400', data.starred);
                }
            }
        } catch (error) {
            console.error('Failed to toggle star:', error);
        }
    }

    initCollisionDetection() {
        // Notify when someone else is viewing/editing
        if (window.Echo) {
            window.Echo.join(`conversation.${this.conversationId}`)
                .listen('.user.viewing', (data) => {
                    if (data.user_id !== document.querySelector('meta[name="user-id"]')?.content) {
                        const message = data.is_replying 
                            ? `${data.user_name} is replying...`
                            : `${data.user_name} is viewing this conversation`;
                        
                        this.showCollisionWarning(message);
                    }
                });
        }

        // Send our own presence
        setInterval(() => {
            const isReplying = document.querySelector('#reply-body')?.value?.length > 0;
            if (window.fsReplyChanged || isReplying) {
                this.broadcastPresence(isReplying);
            }
        }, 5000);
    }

    showCollisionWarning(message) {
        let warning = document.querySelector('#collision-warning');
        if (!warning) {
            warning = document.createElement('div');
            warning.id = 'collision-warning';
            warning.className = 'fixed top-20 right-4 bg-yellow-100 border-l-4 border-yellow-500 p-4 rounded shadow-lg z-50';
            document.body.appendChild(warning);
        }
        warning.textContent = message;
        
        // Auto-hide after 10 seconds
        setTimeout(() => warning.remove(), 10000);
    }

    async broadcastPresence(isReplying) {
        try {
            await fetch(`/conversations/${this.conversationId}/presence`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ is_replying: isReplying })
            });
        } catch (error) {
            // Silently fail
        }
    }

    showAttachmentDialog() {
        // Trigger file input
        const fileInput = document.querySelector('#attachment-input');
        if (fileInput) {
            fileInput.click();
        }
    }

    getStatusLabel(status) {
        const labels = { 1: 'Active', 2: 'Pending', 3: 'Closed', 4: 'Spam' };
        return labels[status] || 'Unknown';
    }

    getStatusClass(status) {
        const classes = {
            1: 'px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-800',
            2: 'px-2 py-1 text-xs font-semibold rounded bg-yellow-100 text-yellow-800',
            3: 'px-2 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-800',
            4: 'px-2 py-1 text-xs font-semibold rounded bg-red-100 text-red-800'
        };
        return classes[status] || classes[1];
    }

    destroy() {
        if (this.autoSaveTimer) {
            clearInterval(this.autoSaveTimer);
        }
        if (this.editor) {
            this.editor.destroy();
        }
    }
}

// Auto-initialize
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new ConversationManager();
    });
} else {
    new ConversationManager();
}

export default ConversationManager;
