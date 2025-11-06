/**
 * Real-time notifications using Laravel Echo
 */

export class RealtimeNotifications {
    constructor() {
        this.userId = document.querySelector('meta[name="user-id"]')?.content;
        this.conversationId = document.querySelector('meta[name="conversation-id"]')?.content;
        this.notificationContainer = null;
        this.setupContainer();
    }

    setupContainer() {
        // Create notification container if it doesn't exist
        if (!document.querySelector('#notification-container')) {
            const container = document.createElement('div');
            container.id = 'notification-container';
            container.className = 'fixed top-4 right-4 z-50 space-y-2';
            document.body.appendChild(container);
            this.notificationContainer = container;
        } else {
            this.notificationContainer = document.querySelector('#notification-container');
        }
    }

    init() {
        if (!this.userId || !window.Echo) {
            console.warn('Echo or user ID not available');
            return;
        }

        // Subscribe to user's private channel
        this.subscribeToUserChannel();

        // Subscribe to conversation channel if viewing a conversation
        if (this.conversationId) {
            this.subscribeToConversationChannel();
        }
    }

    subscribeToUserChannel() {
        window.Echo.private(`user.${this.userId}`)
            .listen('.message.new', (data) => {
                this.handleNewMessage(data);
            })
            .listen('.conversation.updated', (data) => {
                this.handleConversationUpdate(data);
            })
            .notification((notification) => {
                this.showNotification(notification);
            });
    }

    subscribeToConversationChannel() {
        window.Echo.join(`conversation.${this.conversationId}`)
            .here((users) => {
                this.updateViewers(users);
            })
            .joining((user) => {
                this.addViewer(user);
            })
            .leaving((user) => {
                this.removeViewer(user);
            })
            .listen('.user.viewing', (data) => {
                this.handleUserViewing(data);
            });
    }

    handleNewMessage(data) {
        // Skip if viewing the conversation where the message was created
        if (this.conversationId && this.conversationId == data.conversation_id) {
            return;
        }

        // Show browser notification
        this.showBrowserNotification(
            `New message in #${data.conversation_number}`,
            data.preview,
            `/conversations/${data.conversation_id}`
        );

        // Show in-app notification
        this.showInAppNotification({
            title: `New message in #${data.conversation_number}`,
            message: data.preview,
            from: data.customer_name || data.user_name,
            url: `/conversations/${data.conversation_id}`,
            type: 'message'
        });

        // Update conversation list if on mailbox view
        this.updateConversationList(data.conversation_id);
    }

    handleConversationUpdate(data) {
        // Update conversation in list
        this.updateConversationList(data.id);

        // If viewing this conversation, update the UI
        if (this.conversationId && this.conversationId == data.id) {
            this.updateConversationView(data);
        }
    }

    handleUserViewing(data) {
        // Skip own notifications
        if (data.user_id == this.userId) {
            return;
        }

        const title = data.is_replying
            ? `${data.user_name} is replying...`
            : `${data.user_name} is viewing`;

        this.showUserActivity(data.user_id, data.user_name, title);
    }

    showInAppNotification({ title, message, from, url, type = 'info' }) {
        const notification = document.createElement('div');
        notification.className = `
            bg-white border-l-4 border-blue-500 rounded-lg shadow-lg p-4 
            transform transition-all duration-300 ease-in-out
            hover:shadow-xl cursor-pointer max-w-sm
        `;
        
        notification.innerHTML = `
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-gray-900">${this.escapeHtml(title)}</p>
                    ${from ? `<p class="mt-1 text-xs text-gray-500">${this.escapeHtml(from)}</p>` : ''}
                    ${message ? `<p class="mt-1 text-sm text-gray-600">${this.escapeHtml(message)}</p>` : ''}
                </div>
                <button class="ml-4 flex-shrink-0 text-gray-400 hover:text-gray-500" onclick="this.parentElement.parentElement.remove()">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        `;

        if (url) {
            notification.addEventListener('click', (e) => {
                if (!e.target.closest('button')) {
                    window.location.href = url;
                }
            });
        }

        this.notificationContainer.appendChild(notification);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }

    showBrowserNotification(title, body, url) {
        if ('Notification' in window && Notification.permission === 'granted') {
            const notification = new Notification(title, {
                body: body,
                icon: '/favicon.png',
                tag: url
            });

            notification.onclick = () => {
                window.focus();
                if (url) {
                    window.location.href = url;
                }
                notification.close();
            };
        }
    }

    requestNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }

    showUserActivity(userId, userName, title) {
        const viewersContainer = document.querySelector('#conv-viewers');
        if (!viewersContainer) return;

        let viewer = viewersContainer.querySelector(`.viewer-${userId}`);
        
        if (!viewer) {
            viewer = document.createElement('div');
            viewer.className = `viewer-${userId} flex items-center space-x-2 text-sm text-gray-600 p-2 bg-blue-50 rounded`;
            viewersContainer.appendChild(viewer);
        }

        viewer.innerHTML = `
            <div class="flex-shrink-0">
                <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-xs">
                    ${userName.charAt(0).toUpperCase()}
                </div>
            </div>
            <span>${this.escapeHtml(title)}</span>
        `;

        // Remove after 30 seconds
        setTimeout(() => viewer.remove(), 30000);
    }

    updateViewers(users) {
        const viewersContainer = document.querySelector('#conv-viewers');
        if (!viewersContainer) return;

        viewersContainer.innerHTML = '';
        users.forEach(user => {
            if (user.id != this.userId) {
                this.showUserActivity(user.id, user.name, `${user.name} is viewing`);
            }
        });
    }

    addViewer(user) {
        if (user.id != this.userId) {
            this.showUserActivity(user.id, user.name, `${user.name} joined`);
        }
    }

    removeViewer(user) {
        const viewer = document.querySelector(`.viewer-${user.id}`);
        if (viewer) {
            viewer.remove();
        }
    }

    updateConversationList(conversationId) {
        // Fetch and update the conversation row in the list
        const row = document.querySelector(`[data-conversation-id="${conversationId}"]`);
        if (row) {
            // Trigger a refresh of this conversation row
            fetch(`/conversations/${conversationId}/preview`)
                .then(response => response.text())
                .then(html => {
                    row.outerHTML = html;
                })
                .catch(error => console.error('Error updating conversation:', error));
        }
    }

    updateConversationView(data) {
        // Update conversation status badge
        const statusBadge = document.querySelector('#conversation-status');
        if (statusBadge && data.status) {
            statusBadge.textContent = this.getStatusLabel(data.status);
            statusBadge.className = this.getStatusClass(data.status);
        }

        // Update assignee
        if (data.user_id) {
            const assignee = document.querySelector('#conversation-assignee');
            if (assignee) {
                // Fetch and update assignee info
                fetch(`/users/${data.user_id}/name`)
                    .then(response => response.text())
                    .then(name => assignee.textContent = name)
                    .catch(error => console.error('Error fetching user:', error));
            }
        }
    }

    getStatusLabel(status) {
        const labels = {
            1: 'Active',
            2: 'Pending',
            3: 'Closed',
            4: 'Spam'
        };
        return labels[status] || 'Unknown';
    }

    getStatusClass(status) {
        const classes = {
            1: 'px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-800',
            2: 'px-2 py-1 text-xs font-semibold rounded bg-yellow-100 text-yellow-800',
            3: 'px-2 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-800',
            4: 'px-2 py-1 text-xs font-semibold rounded bg-red-100 text-red-800'
        };
        return classes[status] || 'px-2 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-800';
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Auto-initialize if DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        const notifications = new RealtimeNotifications();
        notifications.init();
        notifications.requestNotificationPermission();
    });
} else {
    const notifications = new RealtimeNotifications();
    notifications.init();
    notifications.requestNotificationPermission();
}
