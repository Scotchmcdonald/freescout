# Work Batch 05: Email Templates & Mailables

**Batch ID**: BATCH_05  
**Category**: Email System  
**Priority**: 🟡 MEDIUM  
**Estimated Effort**: 14 hours  
**Parallelizable**: Yes  
**Dependencies**: Mail jobs from BATCH_04

---

## Agent Prompt

You are implementing email templates and mailable classes for FreeScout Laravel 11 notification system.

**Repository**: `/home/runner/work/freescout/freescout`  
**Targets**: `app/Mail/`, `resources/views/emails/`  
**Reference**: `archive/app/Mail/`, `archive/resources/views/emails/`

---

## Mailables (6 hours)

### 1. UserNotification (2h)

**Files**:
- `app/Mail/UserNotification.php`
- `resources/views/emails/user/notification.blade.php`
- `resources/views/emails/user/notification_text.blade.php` (plain text)

**Purpose**: General user notifications

**Data**:
- Notification type
- Subject
- Message
- Action button/link
- User name

---

### 2. UserInvite (2h)

**Files**:
- `app/Mail/UserInvite.php`
- `resources/views/emails/user/user_invite.blade.php`
- `resources/views/emails/user/user_invite_text.blade.php`

**Purpose**: Invite new users

**Data**:
- Setup link with token
- Expiration time
- Organization name
- Role assigned

---

### 3. Alert (1h)

**Files**:
- `app/Mail/Alert.php` (reuse existing or create)
- `resources/views/emails/user/alert.blade.php`

**Purpose**: System alerts

**Data**:
- Alert level (info, warning, critical)
- Alert message
- Action required

---

### 4. UserEmailReplyError (1h)

**Files**:
- `app/Mail/UserEmailReplyError.php`
- `resources/views/emails/user/email_reply_error.blade.php`

**Purpose**: Notify about email failures

**Data**:
- Error message
- Thread details
- Troubleshooting steps

---

## Email Views (8 hours)

### Layout (1h)

**File**: `resources/views/emails/user/layouts/system.blade.php`

**Purpose**: Email layout wrapper

**Requirements**:
- Responsive HTML email structure
- Header with logo
- Footer with unsubscribe link
- Consistent styling
- Plain text version support

---

### Additional Templates (7h)

**Files needed**:
1. `emails/user/password_changed.blade.php` (1h)
2. `emails/user/test.blade.php` (1h)
3. `emails/customer/auto_reply_text.blade.php` (1h)
4. `emails/conversation/reply_text.blade.php` (1h)
5. Plain text versions for all (3h)

---

## Implementation Guidelines

### Mailable Structure

```php
class UserNotification extends Mailable
{
    use Queueable, SerieslySerializable;

    public function __construct(
        public string $notificationType,
        public array $data
    ) {}

    public function build(): self
    {
        return $this
            ->subject($this->data['subject'])
            ->view('emails.user.notification')
            ->text('emails.user.notification_text')
            ->with('data', $this->data);
    }
}
```

### Email Styling

- Use inline CSS (email clients requirement)
- Mobile-responsive tables
- Consistent color scheme
- Clear call-to-action buttons
- Plain text alternative

---

**Time**: 14 hours  
**Status**: Ready for implementation
