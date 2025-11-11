# Work Batch 08: Mailbox Management Views

**Batch ID**: BATCH_08  
**Category**: Frontend Views  
**Priority**: 🔴 HIGH  
**Estimated Effort**: 8 hours  
**Parallelizable**: Yes  
**Dependencies**: Mailbox model, MailboxPolicy

---

## Agent Prompt

Implement mailbox management UI views for FreeScout.

**Repository**: `/home/runner/work/freescout/freescout`  
**Target**: `resources/views/mailboxes/`  
**Reference**: `archive/resources/views/mailboxes/`, `docs/VIEWS_COMPARISON.md` Section 1.5

---

## Views to Implement

### 1. Mailbox Creation (3h) - CRITICAL

**File**: `resources/views/mailboxes/create.blade.php`

**Purpose**: Create new mailbox form

**Requirements**:
- Mailbox name field
- Email address field
- Default settings
- Submit/cancel buttons
- Validation messages
- Redirect to connection settings after creation

**Form Fields**:
- Name (required)
- Email (required, unique, valid email)
- From name (optional)
- Timezone (dropdown)
- Active status (checkbox)

---

### 2. Sidebar Menu Components (3h)

**Files**:
- `mailboxes/sidebar_menu.blade.php` - Main mailbox sidebar
- `mailboxes/sidebar_menu_view.blade.php` - Mailbox view sidebar

**Purpose**: Navigation sidebars for mailbox sections

**Requirements**:
- List of folders with counts
- Active folder highlighting
- Collapsed/expanded states
- Icons for folder types
- User personal folders section

---

### 3. Partials (2h)

**Files**:
- `mailboxes/partials/chat_list.blade.php` - Chat conversation list
- `mailboxes/partials/folders.blade.php` - Folder list component
- `mailboxes/partials/mute_icon.blade.php` - Mute indicator icon

**Purpose**: Reusable mailbox components

**Requirements**:
- Chat list: Compact conversation display
- Folders: Draggable folder list
- Mute icon: Visual indicator with tooltip

---

## Implementation Guidelines

### Mailbox Create Form

```blade
<form method="POST" action="{{ route('mailboxes.store') }}">
    @csrf
    
    <x-input-label for="name" value="Mailbox Name" />
    <x-text-input id="name" name="name" :value="old('name')" required />
    <x-input-error :messages="$errors->get('name')" />
    
    <!-- More fields -->
    
    <x-primary-button>Create Mailbox</x-primary-button>
</form>
```

### Sidebar Structure

```blade
<nav class="space-y-1">
    <a href="{{ route('mailboxes.conversations', ['folder' => 'inbox']) }}"
       class="flex items-center px-3 py-2 text-sm font-medium rounded-md
              @if($currentFolder === 'inbox') bg-gray-200 @endif">
        <span>Inbox</span>
        <span class="ml-auto">{{ $inboxCount }}</span>
    </a>
    <!-- More folders -->
</nav>
```

---

**Time**: 8 hours  
**Status**: Ready for implementation
