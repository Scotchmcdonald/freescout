# Work Batch 04: Authorization Policies & Email Jobs

**Batch ID**: BATCH_04  
**Category**: Authorization & Jobs  
**Priority**: 🔴 CRITICAL  
**Estimated Effort**: 14 hours  
**Parallelizable**: Yes  
**Dependencies**: Models must exist

---

## Agent Prompt

You are implementing authorization policies and email jobs for FreeScout Laravel 11. These components control access and handle email notifications.

**Repository**: `/home/runner/work/freescout/freescout`  
**Targets**: `app/Policies/`, `app/Jobs/`  
**Reference**: `docs/CRITICAL_FEATURES_IMPLEMENTATION.md` Sections 3 & 5

---

## Part A: Authorization Policies (7 hours)

### 1. ConversationPolicy (3h)

**File**: `app/Policies/ConversationPolicy.php`

**Methods**:
- `viewAny()` - Can view conversation list
- `view()` - Can view specific conversation
- `create()` - Can create conversation
- `update()` - Can update conversation
- `delete()` - Can delete conversation
- `assign()` - Can assign to users

**Rules**:
- Admins can do everything
- Users need mailbox access (check via MailboxPolicy constants)
- VIEW level: read-only
- REPLY level: read + reply
- ADMIN level: full control

**Reference**: `docs/CRITICAL_FEATURES_IMPLEMENTATION.md` Section 3.1

---

### 2. ThreadPolicy (2h)

**File**: `app/Policies/ThreadPolicy.php`

**Methods**:
- `view()` - Can view thread
- `create()` - Can create thread
- `update()` - Can edit thread (own threads only)
- `delete()` - Can delete thread (admins only)

**Rules**:
- Users can edit their own threads
- Admins can edit any thread
- Must have conversation access

**Reference**: `docs/CRITICAL_FEATURES_IMPLEMENTATION.md` Section 3.2

---

### 3. FolderPolicy (2h)

**File**: `app/Policies/FolderPolicy.php`

**Methods**:
- `view()` - Can view folder
- `create()` - Can create personal folder
- `update()` - Can update folder
- `delete()` - Can delete folder

**Rules**:
- Personal folders: only owner
- System folders: only admins
- Must have mailbox access

**Reference**: `docs/CRITICAL_FEATURES_IMPLEMENTATION.md` Section 3.3

---

## Part B: Email Jobs (7 hours)

### 1. SendNotificationToUsers (3h)

**File**: `app/Jobs/SendNotificationToUsers.php`

**Purpose**: Send notification emails to users

**Requirements**:
- Accept notification type and data
- Query users who should receive notification
- Check user preferences/subscriptions
- Send via UserNotification mailable
- Log in SendLog
- Handle failures gracefully

**Mailable needed**: `app/Mail/UserNotification.php`

---

### 2. SendEmailReplyError (2h)

**File**: `app/Jobs/SendEmailReplyError.php`

**Purpose**: Notify user when their email reply fails

**Requirements**:
- Accept error details and thread
- Send to thread creator
- Include error message
- Provide troubleshooting tips
- Log in SendLog

**Mailable needed**: `app/Mail/UserEmailReplyError.php`

---

### 3. SendAlert (2h)

**File**: `app/Jobs/SendAlert.php`

**Purpose**: Send system alert emails

**Requirements**:
- Accept alert type and message
- Send to admins or specific users
- Support severity levels
- Include action links
- Log in SendLog

**Mailable needed**: `app/Mail/Alert.php`

---

## Implementation Guidelines

### Policy Registration

In `app/Providers/AppServiceProvider.php`:

```php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::policy(Conversation::class, ConversationPolicy::class);
    Gate::policy(Thread::class, ThreadPolicy::class);
    Gate::policy(Folder::class, FolderPolicy::class);
}
```

### Job Structure

```php
class SendNotificationToUsers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerieslySerializable;

    public function __construct(
        public string $notificationType,
        public array $data
    ) {}

    public function handle(): void
    {
        // Implementation
    }
}
```

### Testing

**Policy Tests** (`tests/Feature/Policies/`):
- Test each method with different user roles
- Test edge cases
- Verify authorization works in controllers

**Job Tests** (`tests/Feature/Jobs/`):
- Test job dispatches
- Test email sending
- Test error handling
- Verify SendLog entries

---

**Time**: 14 hours  
**Status**: Ready for implementation
