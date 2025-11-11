# Work Batch 03: Conversation UI Views

**Batch ID**: BATCH_03  
**Category**: Frontend Views  
**Priority**: 🔴 CRITICAL  
**Estimated Effort**: 30 hours  
**Parallelizable**: Yes (can work with BATCH_01, BATCH_02)  
**Dependencies**: Conversation, Thread, Customer models must exist

---

## Agent Prompt

You are implementing the conversation management UI for FreeScout Laravel 11. This is the **core interface** where users manage support tickets and customer interactions.

### Context

The modernized app has only 5 conversation views (17% coverage). You need to implement 25 critical view files that make up the conversation management interface.

**Repository Location**: `/home/runner/work/freescout/freescout`  
**Target Directory**: `resources/views/conversations/`  
**Reference**: `archive/resources/views/conversations/`

### Reference Documentation

Review before starting:
1. `docs/VIEWS_COMPARISON.md` - Section 1.2 (Conversation Views)
2. `archive/resources/views/conversations/` - Original Blade templates
3. Existing views in `resources/views/conversations/`

---

## Part A: Thread Display Partials (18 hours)

### 1. Thread Partial (3h) - CRITICAL

**File**: `resources/views/conversations/partials/thread.blade.php`

**Purpose**: Display a single message thread in a conversation

**Requirements**:
- Show thread content (message body)
- Display sender information (name, avatar)
- Show timestamp
- Display attachments
- Show thread status (draft, sent, etc.)
- Support both customer and user threads
- Include action buttons (edit, delete for authorized users)

**Data Available**:
```php
$thread // Thread model instance
$conversation // Parent conversation
$can_edit // Boolean - user can edit
```

**Reference**: See `archive/resources/views/conversations/partials/thread.blade.php`

---

### 2. Threads List Partial (2h)

**File**: `resources/views/conversations/partials/threads.blade.php`

**Purpose**: Display all threads in a conversation

**Requirements**:
- Loop through conversation threads
- Show threads in chronological order
- Separate customer vs user threads visually
- Include thread partial for each
- Handle empty state

---

### 3. Thread Attachments Partial (2h)

**File**: `resources/views/conversations/partials/thread_attachments.blade.php`

**Purpose**: Display attachments for a thread

**Requirements**:
- Show attachment list with icons
- Display file names and sizes
- Provide download links
- Show image thumbnails for images
- Handle multiple attachments

---

### 4. Customer Sidebar Partial (2h)

**File**: `resources/views/conversations/partials/customer_sidebar.blade.php`

**Purpose**: Show customer information in conversation view

**Requirements**:
- Display customer name and email
- Show customer photo/avatar
- List all conversations from this customer
- Show customer metadata
- Provide quick action buttons

---

### 5. Edit Thread Partial (2h)

**File**: `resources/views/conversations/partials/edit_thread.blade.php`

**Purpose**: Inline thread editing form

**Requirements**:
- Rich text editor for content
- Save/cancel buttons
- Preserve formatting
- Handle attachments
- AJAX submission

---

### 6. Status Badges Partial (1h)

**File**: `resources/views/conversations/partials/badges.blade.php`

**Purpose**: Display status badges (active, closed, spam, etc.)

**Requirements**:
- Color-coded badges
- Status text
- Tooltips for additional info
- Support all conversation statuses

---

### 7. Bulk Actions Toolbar (2h)

**File**: `resources/views/conversations/partials/bulk_actions.blade.php`

**Purpose**: Toolbar for bulk conversation operations

**Requirements**:
- Checkboxes for selection
- Actions: assign, close, delete, move, merge
- Action buttons with icons
- Confirmation dialogs
- AJAX handling

---

### 8. Settings Modal (2h)

**File**: `resources/views/conversations/partials/settings_modal.blade.php`

**Purpose**: Conversation settings dialog

**Requirements**:
- Modal dialog structure
- Settings form (tags, priority, etc.)
- Save/cancel actions
- AJAX form submission

---

### 9. Merge Search Result (1h)

**File**: `resources/views/conversations/partials/merge_search_result.blade.php`

**Purpose**: Display conversation search results for merging

**Requirements**:
- Show conversation preview
- Display customer and subject
- Last activity timestamp
- Selection radio button

---

### 10. Previous Conversations Short (1h)

**File**: `resources/views/conversations/partials/prev_convs_short.blade.php`

**Purpose**: Show recent conversations from same customer

**Requirements**:
- List last 5 conversations
- Show subject and date
- Link to conversation
- Handle no results

---

## Part B: AJAX HTML Views (12 hours)

### 1. Assignee Filter (2h)

**File**: `resources/views/conversations/ajax_html/assignee_filter.blade.php`

**Purpose**: AJAX-loaded assignee filter dropdown

**Requirements**:
- List all users who can be assigned
- Search/filter functionality
- Selection handling
- "Unassigned" option

---

### 2. Change Customer Dialog (2h)

**File**: `resources/views/conversations/ajax_html/change_customer.blade.php`

**Purpose**: AJAX dialog to change conversation customer

**Requirements**:
- Customer search field
- Create new customer option
- Submit button
- Validation

---

### 3. Merge Conversations Dialog (2h)

**File**: `resources/views/conversations/ajax_html/merge_conv.blade.php`

**Purpose**: Merge conversations interface

**Requirements**:
- Search for target conversation
- Display search results
- Confirm merge action
- Warning about permanence

---

### 4. Move Conversation Dialog (2h)

**File**: `resources/views/conversations/ajax_html/move_conv.blade.php`

**Purpose**: Move conversation to different mailbox

**Requirements**:
- Mailbox selector dropdown
- Current mailbox indicator
- Move button
- Confirmation

---

### 5. Send Log View (2h)

**File**: `resources/views/conversations/ajax_html/send_log.blade.php`

**Purpose**: Display email send log for debugging

**Requirements**:
- Show sent emails list
- Display timestamps
- Show recipients
- Show status (sent, failed, bounced)
- Error messages if any

---

### 6. Show Original Message (1h)

**File**: `resources/views/conversations/ajax_html/show_original.blade.php`

**Purpose**: Display original raw email message

**Requirements**:
- Show full email headers
- Display message source
- Syntax highlighting
- Download button

---

### 7. Default Redirect (1h)

**File**: `resources/views/conversations/ajax_html/default_redirect.blade.php`

**Purpose**: Handle default redirect after actions

**Requirements**:
- Redirect message
- Auto-redirect with timer
- Manual redirect link

---

## Part C: Specialized Views (8 hours - Optional)

### 1. Chats View (2h) - MEDIUM PRIORITY

**File**: `resources/views/conversations/chats.blade.php`

**Purpose**: Separate view for chat-style conversations

**Requirements**:
- Chat-focused UI
- Real-time updates
- Compact display
- Quick reply box

---

### 2. Conversations Pagination (1h)

**File**: `resources/views/conversations/conversations_pagination.blade.php`

**Purpose**: Custom pagination for conversation lists

**Requirements**:
- Page numbers
- Next/previous buttons
- Current page indicator
- Total count

---

### 3. Conversations Table (2h)

**File**: `resources/views/conversations/conversations_table.blade.php`

**Purpose**: Table view of conversations (alternative to list)

**Requirements**:
- Sortable columns
- Status indicators
- Customer info
- Last activity
- Assigned user

---

### 4. Editor Bottom Toolbar (2h)

**File**: `resources/views/conversations/editor_bottom_toolbar.blade.php`

**Purpose**: Toolbar below rich text editor

**Requirements**:
- Attachment button
- Template selector
- Send button
- Draft save button
- Shortcuts info

---

### 5. Thread Attribution (1h)

**File**: `resources/views/conversations/thread_by.blade.php`

**Purpose**: Show "Created by" / "Replied by" attribution

**Requirements**:
- User name and avatar
- Timestamp
- Tooltip with full details

---

## Implementation Guidelines

### Tailwind CSS Usage

Use Tailwind utility classes consistently:

```html
<!-- Card -->
<div class="bg-white rounded-lg shadow p-4">

<!-- Button Primary -->
<button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">

<!-- Button Secondary -->
<button class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded">

<!-- Input -->
<input class="border border-gray-300 rounded px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-500">
```

### Alpine.js for Interactivity

Use Alpine.js for simple interactions:

```html
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <div x-show="open">Content</div>
</div>
```

### AJAX Requests

Use Axios (already included via Laravel):

```javascript
axios.post('/conversations/merge', {
    source_id: sourceId,
    target_id: targetId
}).then(response => {
    // Handle success
}).catch(error => {
    // Handle error
});
```

### Blade Components

Reuse existing components when possible:

```blade
<x-primary-button>Submit</x-primary-button>
<x-danger-button>Delete</x-danger-button>
<x-input-error :messages="$errors->get('email')" />
```

### Icons

Use Heroicons (included with Breeze):

```blade
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
</svg>
```

### Testing Strategy

1. **Manual testing**:
   - View each partial in browser
   - Test all interactions
   - Verify responsive design
   - Check accessibility

2. **Browser testing**:
   - Chrome, Firefox, Safari
   - Mobile devices
   - Different screen sizes

3. **Integration testing**:
   - Test with real data
   - Test AJAX requests
   - Verify form submissions

### Success Criteria

- [ ] All partials render correctly
- [ ] AJAX views work properly
- [ ] Responsive on all devices
- [ ] Accessible (WCAG compliant)
- [ ] No JavaScript errors
- [ ] Consistent styling
- [ ] Icons load correctly
- [ ] Forms submit properly

### Time Estimate

- Thread partials: 18h
- AJAX views: 12h
- Specialized views: 8h (optional)
- Testing: Included above

**Total Critical**: 30 hours  
**Total with Optional**: 38 hours

### Dependencies

- Thread, Conversation, Customer models
- Attachment model
- User model
- Existing layout (app.blade.php)

### Notes

- Use existing Blade components
- Follow Tailwind patterns from other views
- Implement AJAX with progressive enhancement
- Test accessibility with screen readers
- Mobile-first responsive design

---

**Batch Status**: Ready for implementation  
**Next Batch**: BATCH_04 (Email Templates & Mailables)
