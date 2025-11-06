# Frontend Quick Reference

## Quick Start

### 1. Install Dependencies
```bash
npm install
```

### 2. Start Development Server
```bash
npm run dev
```

### 3. Build for Production
```bash
npm run build
```

## Common Tasks

### Initialize Rich Text Editor
```javascript
import { RichTextEditor } from './editor.js';

const editor = new RichTextEditor({
    selector: '#editor-container',
    placeholder: 'Type your message...',
    onSave: (content) => {
        console.log('Content saved:', content);
    }
});

// Get content
const html = editor.getContent();

// Set content
editor.setContent('<p>Hello World</p>');

// Insert variable
editor.insertVariable('{{user.name}}');

// Clear editor
editor.clear();

// Destroy editor
editor.destroy();
```

### Setup File Uploader
```javascript
import { FileUploader } from './uploader.js';

const uploader = new FileUploader({
    selector: '#dropzone-area',
    url: '/api/attachments/upload',
    maxFiles: 5,
    maxFilesize: 10, // MB
    acceptedFiles: 'image/*,application/pdf',
    onSuccess: (file, response) => {
        console.log('File uploaded:', response);
    },
    onError: (file, message) => {
        console.error('Upload failed:', message);
    }
});

// Add files programmatically
uploader.addFile(file);

// Get all files
const files = uploader.getFiles();

// Remove file
uploader.removeFile(file);

// Clear all files
uploader.removeAll();
```

### Show Notifications
```javascript
import { UIHelpers } from './ui-helpers.js';

// Toast notification
UIHelpers.toast('Message sent!', 'success'); // success, error, warning, info

// Alert dialog
UIHelpers.showAlert('Success!', 'Your changes were saved', 'success');

// Confirm dialog
await UIHelpers.confirmDialog({
    title: 'Are you sure?',
    text: 'This action cannot be undone',
    confirmButtonText: 'Yes, delete it!',
    onConfirm: () => {
        console.log('User confirmed');
    }
});

// Loading spinner
UIHelpers.showLoader('Saving...');
UIHelpers.hideLoader();
```

### Real-time Notifications
```javascript
import { RealtimeNotifications } from './notifications.js';

// Initialize
const notifications = new RealtimeNotifications();

// Subscribe to mailbox
notifications.subscribeToMailbox(mailboxId);

// Subscribe to conversation
notifications.subscribeToConversation(conversationId);

// Join presence channel
notifications.joinPresence(mailboxId);

// Update unread count
notifications.updateUnreadCount(10);
```

### Manage Conversation
```javascript
import { ConversationManager } from './conversation.js';

// Initialize
const manager = new ConversationManager({
    conversationId: 123,
    mailboxId: 1,
    editorSelector: '#editor-container',
    uploaderSelector: '#dropzone-area'
});

// Submit reply
manager.submitReply(formEvent);

// Create note
manager.createNote();

// Change status
manager.changeStatus(2); // 1=Active, 2=Pending, 3=Closed

// Change assignee
manager.changeAssignee(userId);

// Update subject
manager.updateSubject('New Subject');

// Toggle star
manager.toggleStar();

// Save draft
manager.saveDraft();

// Accept chat
manager.acceptChat();

// End chat
manager.endChat();
```

## Alpine.js Components

### Dropdown Menu
```html
<div x-data="{ open: false }">
    <button @click="open = !open">
        Toggle Menu
    </button>
    <div x-show="open" @click.away="open = false">
        <a href="#">Option 1</a>
        <a href="#">Option 2</a>
    </div>
</div>
```

### Tabs
```html
<div x-data="{ tab: 'tab1' }">
    <button @click="tab = 'tab1'" :class="{ 'active': tab === 'tab1' }">
        Tab 1
    </button>
    <button @click="tab = 'tab2'" :class="{ 'active': tab === 'tab2' }">
        Tab 2
    </button>
    
    <div x-show="tab === 'tab1'">
        Content 1
    </div>
    <div x-show="tab === 'tab2'">
        Content 2
    </div>
</div>
```

### Form Validation
```html
<form x-data="{ 
    email: '', 
    isValid() { return this.email.includes('@'); } 
}">
    <input type="email" x-model="email" />
    <button type="submit" :disabled="!isValid()">
        Submit
    </button>
    <span x-show="!isValid()" class="form-error">
        Invalid email
    </span>
</form>
```

### Toggle
```html
<div x-data="{ enabled: false }">
    <button @click="enabled = !enabled" 
            :class="enabled ? 'bg-blue-600' : 'bg-gray-300'">
        <span x-show="enabled">On</span>
        <span x-show="!enabled">Off</span>
    </button>
</div>
```

## CSS Classes

### Layout
```html
<div class="flex gap-4">               <!-- Flexbox with gap -->
<div class="grid grid-cols-3">         <!-- 3-column grid -->
<div class="container mx-auto">        <!-- Centered container -->
```

### Buttons
```html
<button class="editor-btn">            <!-- Toolbar button -->
<button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
    Primary Button
</button>
<button class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
    Secondary Button
</button>
```

### Status Badges
```html
<span class="status-badge status-badge-active">Active</span>
<span class="status-badge status-badge-pending">Pending</span>
<span class="status-badge status-badge-closed">Closed</span>
<span class="status-badge status-badge-spam">Spam</span>
```

### Forms
```html
<input type="text" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500" />
<textarea class="w-full px-3 py-2 border border-gray-300 rounded"></textarea>
<select class="w-full px-3 py-2 border border-gray-300 rounded"></select>
```

### Alerts
```html
<div class="p-4 bg-blue-50 border border-blue-200 rounded">Info</div>
<div class="p-4 bg-green-50 border border-green-200 rounded">Success</div>
<div class="p-4 bg-yellow-50 border border-yellow-200 rounded">Warning</div>
<div class="p-4 bg-red-50 border border-red-200 rounded">Error</div>
```

### Cards
```html
<div class="bg-white shadow-sm rounded-lg p-4">
    Card content
</div>
```

## API Routes

### Conversations
```
POST   /conversations/{id}/reply        - Submit reply
POST   /conversations/{id}/note         - Add internal note
PATCH  /conversations/{id}/status       - Update status
PATCH  /conversations/{id}/assignee     - Change assignee
PATCH  /conversations/{id}/subject      - Update subject
POST   /conversations/{id}/star         - Toggle star
```

### Attachments
```
POST   /attachments/upload              - Upload file
DELETE /attachments/{id}                - Delete file
GET    /attachments/{id}/download       - Download file
```

### Drafts
```
POST   /drafts                          - Save draft
GET    /drafts/{id}                     - Get draft
DELETE /drafts/{id}                     - Delete draft
```

## WebSocket Events

### Listen for Events
```javascript
window.Echo.private(`mailbox.${mailboxId}`)
    .listen('ConversationUpdated', (e) => {
        console.log('Conversation updated:', e.conversation);
    })
    .listen('NewMessageReceived', (e) => {
        console.log('New message:', e.message);
    });

window.Echo.join(`mailbox.${mailboxId}.users`)
    .here((users) => {
        console.log('Online users:', users);
    })
    .joining((user) => {
        console.log('User joined:', user);
    })
    .leaving((user) => {
        console.log('User left:', user);
    });
```

### Broadcast Events
```php
// From Laravel
broadcast(new ConversationUpdated($conversation));
broadcast(new NewMessageReceived($thread));
broadcast(new UserViewingConversation($user, $conversation));
```

## Debugging

### Check if Editor is Loaded
```javascript
if (typeof RichTextEditor !== 'undefined') {
    console.log('✅ Editor loaded');
} else {
    console.error('❌ Editor not loaded');
}
```

### Check Echo Connection
```javascript
console.log('Echo connected:', window.Echo.connector.pusher.connection.state);
// Should be: "connected"
```

### View Compiled Assets
```bash
# Check manifest
cat public/build/manifest.json

# List compiled files
ls -lh public/build/assets/
```

### Clear Cache
```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
npm run build
```

## Performance Tips

### 1. Lazy Load Components
```javascript
// Instead of importing everything
import { RichTextEditor } from './editor.js';

// Use dynamic imports
const loadEditor = async () => {
    const { RichTextEditor } = await import('./editor.js');
    return new RichTextEditor({ selector: '#editor' });
};
```

### 2. Debounce Events
```javascript
// Debounce search input
let timeout;
input.addEventListener('input', (e) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        performSearch(e.target.value);
    }, 300);
});
```

### 3. Throttle Scroll
```javascript
// Throttle scroll events
let ticking = false;
window.addEventListener('scroll', () => {
    if (!ticking) {
        window.requestAnimationFrame(() => {
            handleScroll();
            ticking = false;
        });
        ticking = true;
    }
});
```

### 4. Use Event Delegation
```javascript
// Instead of multiple listeners
document.querySelectorAll('.btn').forEach(btn => {
    btn.addEventListener('click', handleClick);
});

// Use delegation
document.addEventListener('click', (e) => {
    if (e.target.matches('.btn')) {
        handleClick(e);
    }
});
```

## Accessibility

### ARIA Labels
```html
<button aria-label="Close dialog">
    <i class="glyphicon glyphicon-remove"></i>
</button>

<input type="text" 
       aria-describedby="email-help" 
       aria-invalid="true" />
<span id="email-help">Enter a valid email</span>
```

### Keyboard Navigation
```javascript
// Handle keyboard events
element.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        handleAction();
    }
    if (e.key === 'Escape') {
        closeDialog();
    }
});
```

### Focus Management
```javascript
// Trap focus in modal
const focusableElements = modal.querySelectorAll(
    'a, button, input, textarea, select, [tabindex]:not([tabindex="-1"])'
);
const firstElement = focusableElements[0];
const lastElement = focusableElements[focusableElements.length - 1];

modal.addEventListener('keydown', (e) => {
    if (e.key === 'Tab') {
        if (e.shiftKey && document.activeElement === firstElement) {
            e.preventDefault();
            lastElement.focus();
        } else if (!e.shiftKey && document.activeElement === lastElement) {
            e.preventDefault();
            firstElement.focus();
        }
    }
});
```

## Environment Variables

```env
# Broadcasting
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

# Pusher (if using Pusher instead of Reverb)
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1

# Vite
VITE_APP_NAME="${APP_NAME}"
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
```

## Troubleshooting Checklist

- [ ] Node.js version >= 18
- [ ] Dependencies installed (`npm install`)
- [ ] Assets compiled (`npm run build`)
- [ ] Reverb server running (`php artisan reverb:start`)
- [ ] Queue worker running (`php artisan queue:work`)
- [ ] Cache cleared (`php artisan cache:clear`)
- [ ] Browser cache cleared
- [ ] Console errors checked (F12)
- [ ] Network tab checked for 404s
- [ ] WebSocket connection established

## Resources

- 📚 [Full Documentation](./FRONTEND_MODERNIZATION.md)
- 🎨 [Tailwind CSS Docs](https://tailwindcss.com/docs)
- ⚡ [Alpine.js Docs](https://alpinejs.dev/)
- ✍️ [Tiptap Docs](https://tiptap.dev/docs)
- 📡 [Laravel Echo Docs](https://laravel.com/docs/11.x/broadcasting)
- 🚀 [Vite Docs](https://vite.dev/guide/)
