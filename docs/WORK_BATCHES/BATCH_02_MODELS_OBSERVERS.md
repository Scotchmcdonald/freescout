# Work Batch 02: Models & Observers Implementation

**Batch ID**: BATCH_02  
**Category**: Data Layer  
**Priority**: 🔴 CRITICAL  
**Estimated Effort**: 19 hours  
**Parallelizable**: Yes (can work independently from BATCH_01)  
**Dependencies**: Database migrations must exist

---

## Agent Prompt

You are implementing missing models and model observers for the FreeScout Laravel 11 application. These components ensure data integrity and proper lifecycle management.

### Context

The modernized FreeScout app is missing:
- 5 model classes
- 9 of 10 model observers

Your task is to implement missing models and the 5 critical observers that ensure data consistency.

**Repository Location**: `/home/runner/work/freescout/freescout`  
**Target Directories**: 
- `app/Models/` (for models)
- `app/Observers/` (for observers)
**Reference**: `archive/app/` and `archive/app/Observers/`

### Reference Documentation

Before starting, review:
1. `docs/CRITICAL_FEATURES_IMPLEMENTATION.md` - Sections 2 & 4
2. `docs/ARCHIVE_COMPARISON_ROADMAP.md` - Sections 2 & 3
3. Database schema in `database/migrations/`

---

## Part A: Missing Models (8 hours)

### 1. Follower Model (2h)

**File**: `app/Models/Follower.php`

**Purpose**: Track users following conversations for notifications

**Requirements**:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Follower extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

**Migration** (create if missing):
```php
Schema::create('followers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->timestamps();
    
    $table->unique(['conversation_id', 'user_id']);
});
```

**Update Related Models**:

In `app/Models/Conversation.php`, add:
```php
public function followers(): BelongsToMany
{
    return $this->belongsToMany(User::class, 'followers');
}
```

In `app/Models/User.php`, add:
```php
public function followedConversations(): BelongsToMany
{
    return $this->belongsToMany(Conversation::class, 'followers');
}
```

---

### 2. MailboxUser Model (1h)

**File**: `app/Models/MailboxUser.php`

**Purpose**: Pivot model for mailbox user permissions

**Requirements**:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class MailboxUser extends Pivot
{
    protected $table = 'mailbox_user';
    
    protected $fillable = [
        'mailbox_id',
        'user_id',
        'access_level',
    ];

    public $timestamps = true;

    // Access levels from MailboxPolicy
    const ACCESS_VIEW = 10;
    const ACCESS_REPLY = 20;
    const ACCESS_ADMIN = 30;
}
```

**Migration** (create if missing):
```php
Schema::create('mailbox_user', function (Blueprint $table) {
    $table->id();
    $table->foreignId('mailbox_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->integer('access_level')->default(20); // REPLY
    $table->timestamps();
    
    $table->unique(['mailbox_id', 'user_id']);
});
```

**Update Related Models**:

In `app/Models/User.php`, add:
```php
public function mailboxes(): BelongsToMany
{
    return $this->belongsToMany(Mailbox::class, 'mailbox_user')
        ->using(MailboxUser::class)
        ->withPivot('access_level')
        ->withTimestamps();
}

public function hasAccessToMailbox(int $mailboxId, int $minLevel = MailboxUser::ACCESS_VIEW): bool
{
    if ($this->isAdmin()) {
        return true;
    }
    
    $pivot = $this->mailboxes()->where('mailbox_id', $mailboxId)->first()?->pivot;
    return $pivot && $pivot->access_level >= $minLevel;
}
```

---

### 3. ConversationFolder Model (1h)

**File**: `app/Models/ConversationFolder.php`

**Purpose**: Pivot for many-to-many conversation-folder relationship (if needed)

**Note**: Only implement if conversations can be in multiple folders. Otherwise, use direct `folder_id` on conversations table.

**Requirements**:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ConversationFolder extends Pivot
{
    protected $table = 'conversation_folder';
    
    protected $fillable = [
        'conversation_id',
        'folder_id',
    ];

    public $timestamps = true;
}
```

---

### 4. CustomerChannel Model (2h)

**File**: `app/Models/CustomerChannel.php`

**Purpose**: Track customer communication channels (email, phone, etc.)

**Requirements**:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerChannel extends Model
{
    protected $fillable = [
        'customer_id',
        'channel',
        'channel_id',
    ];

    const CHANNEL_EMAIL = 1;
    const CHANNEL_PHONE = 2;
    const CHANNEL_CHAT = 3;

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
```

---

### 5. Sendmail Model (2h)

**File**: `app/Models/Sendmail.php`

**Purpose**: Track sendmail configuration (if needed)

**Requirements**:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sendmail extends Model
{
    protected $fillable = [
        'mailbox_id',
        'from_name',
        'from_email',
    ];

    public function mailbox()
    {
        return $this->belongsTo(Mailbox::class);
    }
}
```

---

## Part B: Model Observers (11 hours)

### 1. ConversationObserver (3h) - CRITICAL

**File**: `app/Observers/ConversationObserver.php`

**Purpose**: Handle conversation lifecycle events

**Requirements**:
```php
<?php

namespace App\Observers;

use App\Models\Conversation;
use App\Events\ConversationUpdated;

class ConversationObserver
{
    public function creating(Conversation $conversation): void
    {
        // Mark as read if created by user
        if ($conversation->source_via === Conversation::PERSON_USER) {
            $conversation->read_by_user = true;
        }

        // Set default status
        if (!$conversation->status) {
            $conversation->status = Conversation::STATUS_ACTIVE;
        }
    }

    public function created(Conversation $conversation): void
    {
        // Update folder counters
        $conversation->folder?->updateCounters();
        
        // Fire event
        event(new ConversationUpdated($conversation));
    }

    public function updated(Conversation $conversation): void
    {
        // Update folder counters if status changed
        if ($conversation->wasChanged('status')) {
            $conversation->folder?->updateCounters();
        }
        
        // Fire event
        event(new ConversationUpdated($conversation));
    }

    public function deleting(Conversation $conversation): void
    {
        // Delete related records
        $conversation->threads()->delete();
        $conversation->attachments()->delete();
        
        // Update folder counters
        $conversation->folder?->updateCounters();
    }
}
```

**Constants needed in Conversation model**:
```php
const PERSON_USER = 2;
const PERSON_CUSTOMER = 1;
const STATUS_ACTIVE = 1;
```

---

### 2. UserObserver (2h) - CRITICAL

**File**: `app/Observers/UserObserver.php`

**Purpose**: Handle user lifecycle events

**Requirements**:
```php
<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Folder;
use App\Models\Subscription;
use App\Models\Mailbox;

class UserObserver
{
    public function created(User $user): void
    {
        // Create admin personal folders for all mailboxes
        if ($user->isAdmin()) {
            $this->createAdminPersonalFolders($user);
        }

        // Add default subscriptions
        $this->addDefaultSubscriptions($user);
    }

    public function deleting(User $user): void
    {
        // Delete user's personal folders
        $user->folders()->delete();
        
        // Remove from conversation followers
        $user->followedConversations()->detach();
        
        // Unassign from conversations
        $user->conversations()->update(['user_id' => null]);
    }

    private function createAdminPersonalFolders(User $user): void
    {
        $mailboxes = Mailbox::all();
        
        foreach ($mailboxes as $mailbox) {
            Folder::firstOrCreate([
                'mailbox_id' => $mailbox->id,
                'user_id' => $user->id,
                'type' => Folder::TYPE_MINE,
            ], [
                'name' => 'My Conversations',
            ]);
        }
    }

    private function addDefaultSubscriptions(User $user): void
    {
        // Subscribe to assigned conversations
        Subscription::create([
            'user_id' => $user->id,
            'medium' => Subscription::MEDIUM_EMAIL,
            'event' => Subscription::EVENT_USER_ASSIGNED,
        ]);

        // Subscribe to followed conversations
        Subscription::create([
            'user_id' => $user->id,
            'medium' => Subscription::MEDIUM_EMAIL,
            'event' => Subscription::EVENT_NEW_REPLY,
        ]);
    }
}
```

---

### 3. CustomerObserver (2h) - CRITICAL

**File**: `app/Observers/CustomerObserver.php`

**Purpose**: Handle customer lifecycle events

**Requirements**:
```php
<?php

namespace App\Observers;

use App\Models\Customer;

class CustomerObserver
{
    public function creating(Customer $customer): void
    {
        // Normalize email
        $customer->email = strtolower(trim($customer->email));
        
        // Generate initials if name provided
        if ($customer->first_name || $customer->last_name) {
            $customer->initials = $this->generateInitials($customer);
        }
    }

    public function deleting(Customer $customer): void
    {
        // Delete conversations
        $customer->conversations()->delete();
    }

    private function generateInitials(Customer $customer): string
    {
        $initials = '';
        
        if ($customer->first_name) {
            $initials .= strtoupper(substr($customer->first_name, 0, 1));
        }
        
        if ($customer->last_name) {
            $initials .= strtoupper(substr($customer->last_name, 0, 1));
        }
        
        return $initials ?: substr(strtoupper($customer->email), 0, 2);
    }
}
```

---

### 4. MailboxObserver (2h) - CRITICAL

**File**: `app/Observers/MailboxObserver.php`

**Purpose**: Handle mailbox lifecycle events

**Requirements**:
```php
<?php

namespace App\Observers;

use App\Models\Mailbox;
use App\Models\Folder;

class MailboxObserver
{
    public function created(Mailbox $mailbox): void
    {
        // Create default folders
        $this->createDefaultFolders($mailbox);
    }

    public function deleting(Mailbox $mailbox): void
    {
        // Delete all conversations
        $mailbox->conversations()->delete();
        
        // Delete all folders
        $mailbox->folders()->delete();
    }

    private function createDefaultFolders(Mailbox $mailbox): void
    {
        $folders = [
            ['type' => Folder::TYPE_INBOX, 'name' => 'Inbox'],
            ['type' => Folder::TYPE_ASSIGNED, 'name' => 'Assigned'],
            ['type' => Folder::TYPE_CLOSED, 'name' => 'Closed'],
            ['type' => Folder::TYPE_SPAM, 'name' => 'Spam'],
            ['type' => Folder::TYPE_DRAFTS, 'name' => 'Drafts'],
        ];

        foreach ($folders as $folderData) {
            Folder::create([
                'mailbox_id' => $mailbox->id,
                'type' => $folderData['type'],
                'name' => $folderData['name'],
            ]);
        }
    }
}
```

**Constants needed in Folder model**:
```php
const TYPE_INBOX = 1;
const TYPE_ASSIGNED = 2;
const TYPE_CLOSED = 3;
const TYPE_SPAM = 4;
const TYPE_DRAFTS = 5;
const TYPE_MINE = 20;
```

---

### 5. AttachmentObserver (2h) - CRITICAL

**File**: `app/Observers/AttachmentObserver.php`

**Purpose**: Handle attachment lifecycle (delete files)

**Requirements**:
```php
<?php

namespace App\Observers;

use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;

class AttachmentObserver
{
    public function deleting(Attachment $attachment): void
    {
        // Delete file from storage
        if ($attachment->file_path && Storage::exists($attachment->file_path)) {
            Storage::delete($attachment->file_path);
        }
    }
}
```

---

## Implementation Guidelines

### Register Observers

In `app/Providers/AppServiceProvider.php`:

```php
use App\Models\Conversation;
use App\Models\User;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Attachment;
use App\Observers\ConversationObserver;
use App\Observers\UserObserver;
use App\Observers\CustomerObserver;
use App\Observers\MailboxObserver;
use App\Observers/AttachmentObserver;

public function boot(): void
{
    Conversation::observe(ConversationObserver::class);
    User::observe(UserObserver::class);
    Customer::observe(CustomerObserver::class);
    Mailbox::observe(MailboxObserver::class);
    Attachment::observe(AttachmentObserver::class);
}
```

### Testing Strategy

1. **Model tests** (`tests/Unit/Models/`):
   - Test relationships
   - Test methods
   - Test constants

2. **Observer tests** (`tests/Unit/Observers/`):
   - Test creating/created events
   - Test updating/updated events
   - Test deleting/deleted events
   - Verify side effects (counters, files, relationships)

3. **Integration tests**:
   - Create full object graphs
   - Delete and verify cascades
   - Update and verify triggers

### Success Criteria

- [ ] All 5 models implemented
- [ ] All 5 observers implemented
- [ ] Observers registered in AppServiceProvider
- [ ] All relationships defined
- [ ] Constants defined in models
- [ ] Migrations created if needed
- [ ] All tests passing
- [ ] No orphaned records on deletion

### Time Estimate

**Models**: 8 hours
**Observers**: 11 hours
**Testing**: 4 hours (included in above)

**Total**: 19 hours

---

**Batch Status**: Ready for implementation  
**Next Batch**: BATCH_03 (Authorization Policies)
