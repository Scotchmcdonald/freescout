# Work Batch 09: Event Listeners & Audit Logging

**Batch ID**: BATCH_09  
**Category**: Backend Events  
**Priority**: 🟡 MEDIUM  
**Estimated Effort**: 18 hours  
**Parallelizable**: Yes  
**Dependencies**: ActivityLog model

---

## Agent Prompt

Implement event listeners for audit logging and system events in FreeScout.

**Repository**: `/home/runner/work/freescout/freescout`  
**Target**: `app/Listeners/`  
**Reference**: `archive/app/Listeners/`

---

## Audit Logging Listeners (10 hours)

### 1. Login/Logout Tracking (4h)

**Files**:
- `app/Listeners/LogSuccessfulLogin.php`
- `app/Listeners/LogSuccessfulLogout.php`
- `app/Listeners/LogFailedLogin.php`

**Purpose**: Track authentication events

**Requirements**:
- Log user login with IP address
- Log user logout
- Log failed login attempts (security)
- Store in activity_logs table

**Events**:
- `Illuminate\Auth\Events\Login`
- `Illuminate\Auth\Events\Logout`
- `Illuminate\Auth\Events\Failed`

---

### 2. Password Events (2h)

**File**: `app/Listeners/LogPasswordReset.php`

**Purpose**: Track password resets

**Requirements**:
- Log password reset requests
- Store IP and timestamp

**Event**: `Illuminate\Auth\Events\PasswordReset`

---

### 3. User Events (4h)

**Files**:
- `app/Listeners/LogRegisteredUser.php`
- `app/Listeners/LogUserDeletion.php`
- `app/Listeners/LogLockout.php`

**Purpose**: Track user lifecycle and security

**Requirements**:
- Log new user registrations
- Log user deletions (admin action)
- Log account lockouts (brute force protection)

---

## Email Processing Listeners (6 hours)

### 1. SendReplyToCustomer (3h)

**File**: `app/Listeners/SendReplyToCustomer.php`

**Purpose**: Send reply emails to customers

**Requirements**:
- Listen to UserReplied event
- Queue SendConversationReply job
- Update conversation status
- Log in SendLog

---

### 2. SendNotificationToUsers (2h)

**File**: `app/Listeners/SendNotificationToUsers.php`

**Purpose**: Notify users of conversation changes

**Requirements**:
- Check user subscriptions
- Queue SendNotificationToUsers job
- Respect user preferences

---

### 3. UpdateMailboxCounters (1h)

**File**: `app/Listeners/UpdateMailboxCounters.php`

**Purpose**: Update mailbox statistics

**Requirements**:
- Update conversation counts
- Update unread counts
- Cache for performance

---

## User Management Listeners (2 hours)

### 1. RememberUserLocale (1h)

**File**: `app/Listeners/RememberUserLocale.php`

**Purpose**: Set user's preferred language

**Requirements**:
- Set app locale from user preference
- Handle missing locales gracefully

---

### 2. SendPasswordChanged (1h)

**File**: `app/Listeners/SendPasswordChanged.php`

**Purpose**: Email user when password changes

**Requirements**:
- Send PasswordChanged mailable
- Include security advice
- Don't send if user initiated

---

## Implementation Guidelines

### Listener Structure

```php
namespace App\Listeners;

use App\Events\UserReplied;
use App\Models\ActivityLog;

class LogUserAction
{
    public function handle(UserReplied $event): void
    {
        activity()
            ->causedBy($event->user)
            ->performedOn($event->thread)
            ->withProperties(['ip' => request()->ip()])
            ->useLog(ActivityLog::NAME_USER)
            ->log(ActivityLog::DESCRIPTION_USER_REPLIED);
    }
}
```

### Register in EventServiceProvider

```php
protected $listen = [
    \Illuminate\Auth\Events\Login::class => [
        \App\Listeners\LogSuccessfulLogin::class,
    ],
    // More mappings
];
```

---

**Time**: 18 hours  
**Status**: Ready for implementation
