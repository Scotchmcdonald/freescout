# Work Batch 06: Customer & User Management Views

**Batch ID**: BATCH_06  
**Category**: Frontend Views  
**Priority**: 🟡 MEDIUM  
**Estimated Effort**: 24 hours  
**Parallelizable**: Yes  
**Dependencies**: Customer, User models

---

## Agent Prompt

Implement customer and user management UI views for FreeScout.

**Repository**: `/home/runner/work/freescout/freescout`  
**Reference**: `docs/VIEWS_COMPARISON.md` Sections 1.3, 1.6

---

## Customer Views (12 hours)

### 1. Customer Merging (3h)

**File**: `resources/views/customers/merge.blade.php`

**Purpose**: UI to merge duplicate customers

**Requirements**:
- Search for target customer
- Show comparison of both customers
- Select which data to keep
- Confirm merge (permanent action)
- Show affected conversations count

---

### 2. Profile Components (6h)

**Files**:
- `customers/profile_menu.blade.php` - Navigation tabs
- `customers/profile_tabs.blade.php` - Tab panels
- `customers/profile_snippet.blade.php` - Quick info widget
- `customers/conversations.blade.php` - Customer conversation history

**Requirements**:
- Tabbed interface for customer profile
- Conversation history with filtering
- Quick actions (email, merge, delete)
- Custom fields display

---

### 3. Partials (3h)

**Files**:
- `customers/partials/customers_table.blade.php` - Reusable customer table

**Requirements**:
- Sortable columns
- Search/filter
- Pagination
- Bulk actions

---

## User Views (12 hours)

### 1. Notification Management (6h)

**Files**:
- `users/notifications.blade.php` - Notification preferences
- `users/is_subscribed.blade.php` - Subscription status widget
- `users/subscriptions_table.blade.php` - Subscription list
- `users/partials/web_notifications.blade.php` - Browser notifications

**Requirements**:
- Per-event notification toggles
- Email vs web notification preferences
- Browser notification permission handling
- Subscription management

---

### 2. Permissions (3h)

**File**: `users/permissions.blade.php`

**Purpose**: Manage user's mailbox permissions

**Requirements**:
- List all mailboxes
- Access level per mailbox (VIEW/REPLY/ADMIN)
- Save changes with validation

---

### 3. Navigation (3h)

**File**: `users/sidebar_menu.blade.php`

**Purpose**: User management sidebar

**Requirements**:
- Links to profile, permissions, notifications
- Active state highlighting
- Admin-only sections

---

## Implementation Guidelines

Use Tailwind CSS and Alpine.js as in previous batches. Follow existing patterns from auth and profile views.

**Time**: 24 hours  
**Status**: Ready for implementation
