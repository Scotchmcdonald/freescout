# Work Batch 07: Shared Partials & Components

**Batch ID**: BATCH_07  
**Category**: Frontend Components  
**Priority**: 🟡 MEDIUM  
**Estimated Effort**: 14 hours  
**Parallelizable**: Yes  
**Dependencies**: None (standalone components)

---

## Agent Prompt

Implement reusable UI components and partials for FreeScout.

**Repository**: `/home/runner/work/freescout/freescout`  
**Target**: `resources/views/partials/`  
**Reference**: `archive/resources/views/partials/`

---

## Components to Implement

### 1. Rich Text Editor (4h) - CRITICAL

**File**: `resources/views/partials/editor.blade.php`

**Purpose**: Reusable rich text editor component

**Requirements**:
- Integrate Tiptap 2.x (already in package.json)
- Toolbar with formatting options
- Variable insertion support
- Attachment handling
- Draft autosave
- Mention support (@user)

---

### 2. Form Helpers (4h)

**Files**:
- `partials/calendar.blade.php` - Date picker component
- `partials/locale_options.blade.php` - Language selector dropdown
- `partials/timezone_options.blade.php` - Timezone selector

**Requirements**:
- Calendar: Integrate flatpickr or similar
- Locale: Loop through available languages
- Timezone: PHP timezone list formatted

---

### 3. UI Components (4h)

**Files**:
- `partials/flash_messages.blade.php` - Flash message display
- `partials/person_photo.blade.php` - Avatar/photo component
- `partials/empty.blade.php` - Empty state component

**Requirements**:
- Flash messages: Bootstrap alert style with Tailwind
- Avatar: Show initials if no photo, support gravatar
- Empty state: Icon, message, call-to-action

---

### 4. Navigation (2h)

**File**: `partials/sidebar_menu_toggle.blade.php`

**Purpose**: Sidebar toggle button for mobile

**Requirements**:
- Hamburger icon
- Alpine.js toggle
- Responsive behavior

---

## Implementation Guidelines

- Use Alpine.js for interactivity
- Reusable with props/slots where possible
- Consistent Tailwind styling
- Accessible (ARIA labels, keyboard nav)

**Time**: 14 hours  
**Status**: Ready for implementation
