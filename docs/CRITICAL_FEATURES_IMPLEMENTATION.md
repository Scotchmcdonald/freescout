# Critical Features Implementation Guide

**Generated**: November 10, 2025  
**Purpose**: Detailed implementation guide for critical missing features from archived app

---

## Table of Contents

1. [Console Commands](#1-console-commands)
2. [Model Observers](#2-model-observers)
3. [Authorization Policies](#3-authorization-policies)
4. [Missing Models](#4-missing-models)
5. [Event Listeners](#5-event-listeners)
6. [Implementation Priority](#6-implementation-priority)

---

## 1. Console Commands

### 1.1 CreateUser Command (CRITICAL)

**Purpose**: Create users via CLI for initial setup and automation

**File**: `app/Console/Commands/CreateUser.php`

**Implementation**:
```php
<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateUser extends Command
{
    protected $signature = 'freescout:create-user 
                            {--role= : User role (admin/user)} 
                            {--firstName= : First name} 
                            {--lastName= : Last name} 
                            {--email= : Email address} 
                            {--password= : Password}';

    protected $description = 'Create a new user';

    public function handle(): int
    {
        // Get role
        $role = $this->option('role') ?: $this->ask('User role (admin/user)', 'admin');
        if (!in_array($role, ['admin', 'user'])) {
            $this->error('Invalid role. Must be admin or user');
            return 1;
        }
        $roleValue = $role === 'admin' ? User::ROLE_ADMIN : User::ROLE_USER;

        // Get user details
        $firstName = $this->option('firstName') ?: $this->ask('First name');
        $lastName = $this->option('lastName') ?: $this->ask('Last name');
        
        // Get and validate email
        $email = $this->option('email') ?: $this->ask('Email address');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address');
            return 1;
        }

        // Check if user exists
        if (User::where('email', $email)->exists()) {
            $this->error('User with this email already exists');
            return 1;
        }

        // Get password
        $password = $this->option('password') ?: $this->secret('Password');

        // Confirm
        if (!$this->confirm('Create user?', true)) {
            return 0;
        }

        // Create user
        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => $roleValue,
            'status' => User::STATUS_ACTIVE,
            'invite_state' => User::INVITE_STATE_ACTIVATED,
        ]);

        $this->info("User created successfully! ID: {$user->id}");
        return 0;
    }
}
```

**Required User Model Constants**:
```php
// In app/Models/User.php
const ROLE_ADMIN = 1;
const ROLE_USER = 2;
const STATUS_ACTIVE = 1;
const INVITE_STATE_ACTIVATED = 1;
```

---

### 1.2 CheckRequirements Command (CRITICAL)

**Purpose**: Validate system requirements before installation/upgrade

**File**: `app/Console/Commands/CheckRequirements.php`

**Implementation**:
```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckRequirements extends Command
{
    protected $signature = 'freescout:check-requirements';
    protected $description = 'Check system requirements (PHP version, extensions, functions)';

    public function handle(): int
    {
        $this->info('FreeScout System Requirements Check');
        $this->newLine();

        // Check PHP version
        $this->checkPhpVersion();
        
        // Check PHP extensions
        $this->checkPhpExtensions();
        
        // Check required functions
        $this->checkRequiredFunctions();
        
        // Check directory permissions
        $this->checkDirectoryPermissions();
        
        $this->newLine();
        return 0;
    }

    private function checkPhpVersion(): void
    {
        $this->info('PHP Version:');
        $minVersion = '8.2.0';
        $currentVersion = PHP_VERSION;
        $status = version_compare($currentVersion, $minVersion, '>=');
        
        $this->line(sprintf(
            '  %s %s',
            str_pad($currentVersion, 30, '.'),
            $status ? '<fg=green>OK</>' : '<fg=red>FAILED</>'
        ));
        
        if (!$status) {
            $this->error("  PHP {$minVersion} or higher is required");
        }
        $this->newLine();
    }

    private function checkPhpExtensions(): void
    {
        $this->info('PHP Extensions:');
        
        $required = [
            'openssl',
            'pdo',
            'mbstring',
            'tokenizer',
            'xml',
            'ctype',
            'json',
            'bcmath',
            'imap',
            'zip',
            'gd',
            'curl',
            'intl',
        ];

        foreach ($required as $extension) {
            $loaded = extension_loaded($extension);
            $this->line(sprintf(
                '  %s %s',
                str_pad($extension, 30, '.'),
                $loaded ? '<fg=green>OK</>' : '<fg=red>NOT FOUND</>'
            ));
        }
        $this->newLine();
    }

    private function checkRequiredFunctions(): void
    {
        $this->info('Required Functions:');
        
        $required = [
            'proc_open',
            'proc_close',
            'fsockopen',
            'symlink',
        ];

        foreach ($required as $function) {
            $exists = function_exists($function);
            $this->line(sprintf(
                '  %s %s',
                str_pad($function, 30, '.'),
                $exists ? '<fg=green>OK</>' : '<fg=red>DISABLED</>'
            ));
        }
        $this->newLine();
    }

    private function checkDirectoryPermissions(): void
    {
        $this->info('Directory Permissions:');
        
        $directories = [
            'storage',
            'storage/framework',
            'storage/logs',
            'bootstrap/cache',
            'public/storage',
        ];

        foreach ($directories as $dir) {
            $path = base_path($dir);
            $writable = is_dir($path) && is_writable($path);
            $this->line(sprintf(
                '  %s %s',
                str_pad($dir, 30, '.'),
                $writable ? '<fg=green>WRITABLE</>' : '<fg=red>NOT WRITABLE</>'
            ));
        }
        $this->newLine();
    }
}
```

---

### 1.3 UpdateFolderCounters Command

**Purpose**: Recalculate conversation counters for all folders

**File**: `app/Console/Commands/UpdateFolderCounters.php`

**Implementation**:
```php
<?php

namespace App\Console\Commands;

use App\Models\Folder;
use Illuminate\Console\Command;

class UpdateFolderCounters extends Command
{
    protected $signature = 'freescout:update-folder-counters';
    protected $description = 'Update counters for all folders';

    public function handle(): int
    {
        $folders = Folder::all();
        $bar = $this->output->createProgressBar($folders->count());
        $bar->start();

        foreach ($folders as $folder) {
            $folder->updateCounters();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Folder counters updated successfully');
        
        return 0;
    }
}
```

**Required Folder Model Method**:
```php
// In app/Models/Folder.php
public function updateCounters(): void
{
    // Count total conversations
    $this->total_count = $this->conversations()->count();
    
    // Count active conversations
    $this->active_count = $this->conversations()
        ->where('status', Conversation::STATUS_ACTIVE)
        ->count();
    
    $this->save();
}
```

---

### 1.4 ModuleInstall Command (CRITICAL)

**Purpose**: Install FreeScout modules (run migrations, create symlinks)

**File**: `app/Console/Commands/ModuleInstall.php`

**Implementation**:
```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Nwidart\Modules\Facades\Module;

class ModuleInstall extends Command
{
    protected $signature = 'freescout:module-install {module_alias?}';
    protected $description = 'Install module: run migrations and create symlink';

    public function handle(): int
    {
        $moduleAlias = $this->argument('module_alias');

        // Clear cache first
        $this->call('cache:clear');

        if (!$moduleAlias) {
            return $this->installAllModules();
        }

        return $this->installModule($moduleAlias);
    }

    private function installAllModules(): int
    {
        $modules = Module::all();
        
        if (empty($modules)) {
            $this->error('No modules found');
            return 1;
        }

        $aliases = array_map(fn($m) => $m->getName(), $modules);
        
        if (!$this->confirm(
            'Install all modules (' . implode(', ', $aliases) . ')?',
            false
        )) {
            return 0;
        }

        foreach ($modules as $module) {
            $this->info("Installing: {$module->getName()}");
            $this->installModuleFiles($module);
        }

        $this->info('All modules installed successfully');
        return 0;
    }

    private function installModule(string $alias): int
    {
        $module = Module::findByAlias($alias);
        
        if (!$module) {
            $this->error("Module not found: {$alias}");
            return 1;
        }

        $this->info("Installing: {$module->getName()}");
        $this->installModuleFiles($module);
        
        return 0;
    }

    private function installModuleFiles($module): void
    {
        // Run migrations
        $this->call('module:migrate', [
            'module' => $module->getName(),
            '--force' => true,
        ]);

        // Create public symlink
        $this->createModulePublicSymlink($module);

        // Clear cache
        $this->call('cache:clear');
    }

    private function createModulePublicSymlink($module): void
    {
        $from = public_path('modules/' . $module->getLowerName());
        $to = $module->getPath() . '/Resources/assets';

        // Remove existing symlink
        if (is_link($from)) {
            unlink($from);
        }

        // Create target directory if needed
        if (!is_dir($to)) {
            mkdir($to, 0755, true);
        }

        // Create symlink
        try {
            symlink($to, $from);
            $this->line("  Created symlink: {$from} → {$to}");
        } catch (\Exception $e) {
            $this->error("  Failed to create symlink: {$e->getMessage()}");
        }
    }
}
```

---

## 2. Model Observers

### 2.1 ConversationObserver (CRITICAL)

**Purpose**: Handle conversation lifecycle events

**File**: `app/Observers/ConversationObserver.php`

**Implementation**:
```php
<?php

namespace App\Observers;

use App\Models\Conversation;

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
        event(new \App\Events\ConversationUpdated($conversation));
    }

    public function updated(Conversation $conversation): void
    {
        // Update folder counters if status changed
        if ($conversation->wasChanged('status')) {
            $conversation->folder?->updateCounters();
        }
        
        // Fire event
        event(new \App\Events\ConversationUpdated($conversation));
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

**Register in AppServiceProvider**:
```php
// In app/Providers/AppServiceProvider.php boot()
Conversation::observe(ConversationObserver::class);
```

---

### 2.2 UserObserver (CRITICAL)

**Purpose**: Handle user lifecycle events

**File**: `app/Observers/UserObserver.php`

**Implementation**:
```php
<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Folder;
use App\Models\Subscription;

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
        $mailboxes = \App\Models\Mailbox::all();
        
        foreach ($mailboxes as $mailbox) {
            // Create "My" folder for admin
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

### 2.3 CustomerObserver

**Purpose**: Handle customer lifecycle events

**File**: `app/Observers/CustomerObserver.php`

**Implementation**:
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
        // Option 1: Delete conversations
        $customer->conversations()->delete();
        
        // Option 2: Or reassign to a "deleted customer" placeholder
        // $customer->conversations()->update(['customer_id' => null]);
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

## 3. Authorization Policies

### 3.1 ConversationPolicy (CRITICAL)

**Purpose**: Control access to conversations

**File**: `app/Policies/ConversationPolicy.php`

**Implementation**:
```php
<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        // All authenticated users can view conversation list
        return true;
    }

    public function view(User $user, Conversation $conversation): bool
    {
        // Admins can view all
        if ($user->isAdmin()) {
            return true;
        }

        // Check if user has access to mailbox
        return $user->hasAccessToMailbox($conversation->mailbox_id);
    }

    public function create(User $user): bool
    {
        // All users can create conversations
        return true;
    }

    public function update(User $user, Conversation $conversation): bool
    {
        // Admins can update all
        if ($user->isAdmin()) {
            return true;
        }

        // Users need at least REPLY access to mailbox
        return $user->hasAccessToMailbox(
            $conversation->mailbox_id,
            \App\Policies\MailboxPolicy::ACCESS_REPLY
        );
    }

    public function delete(User $user, Conversation $conversation): bool
    {
        // Only admins can delete
        return $user->isAdmin();
    }

    public function assign(User $user, Conversation $conversation): bool
    {
        // Admins and mailbox managers can assign
        if ($user->isAdmin()) {
            return true;
        }

        return $user->hasAccessToMailbox(
            $conversation->mailbox_id,
            \App\Policies\MailboxPolicy::ACCESS_ADMIN
        );
    }
}
```

---

### 3.2 ThreadPolicy

**Purpose**: Control access to conversation threads

**File**: `app/Policies/ThreadPolicy.php`

**Implementation**:
```php
<?php

namespace App\Policies;

use App\Models\Thread;
use App\Models\User;

class ThreadPolicy
{
    public function view(User $user, Thread $thread): bool
    {
        // Check conversation policy
        return $user->can('view', $thread->conversation);
    }

    public function create(User $user): bool
    {
        // All users can create threads
        return true;
    }

    public function update(User $user, Thread $thread): bool
    {
        // Admins can edit all threads
        if ($user->isAdmin()) {
            return true;
        }

        // Users can only edit their own threads
        return $thread->created_by_user_id === $user->id;
    }

    public function delete(User $user, Thread $thread): bool
    {
        // Only admins can delete threads
        return $user->isAdmin();
    }
}
```

---

### 3.3 FolderPolicy

**Purpose**: Control access to folders

**File**: `app/Policies/FolderPolicy.php`

**Implementation**:
```php
<?php

namespace App\Policies;

use App\Models\Folder;
use App\Models\User;

class FolderPolicy
{
    public function view(User $user, Folder $folder): bool
    {
        // Admins can view all folders
        if ($user->isAdmin()) {
            return true;
        }

        // Personal folder - only owner can view
        if ($folder->user_id) {
            return $folder->user_id === $user->id;
        }

        // Check mailbox access
        return $user->hasAccessToMailbox($folder->mailbox_id);
    }

    public function create(User $user): bool
    {
        // All users can create personal folders
        return true;
    }

    public function update(User $user, Folder $folder): bool
    {
        // Personal folder - only owner can update
        if ($folder->user_id) {
            return $folder->user_id === $user->id;
        }

        // System folders - only admins
        return $user->isAdmin();
    }

    public function delete(User $user, Folder $folder): bool
    {
        // Personal folder - only owner can delete
        if ($folder->user_id) {
            return $folder->user_id === $user->id;
        }

        // System folders - only admins
        return $user->isAdmin();
    }
}
```

---

## 4. Missing Models

### 4.1 Follower Model

**Purpose**: Track users following conversations

**File**: `app/Models/Follower.php`

**Implementation**:
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

**Migration**:
```php
Schema::create('followers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->timestamps();
    
    $table->unique(['conversation_id', 'user_id']);
});
```

**Add to Conversation Model**:
```php
public function followers(): BelongsToMany
{
    return $this->belongsToMany(User::class, 'followers');
}
```

---

### 4.2 ConversationFolder Model (Pivot)

**Purpose**: Many-to-many relationship between conversations and folders

**Note**: This might be unnecessary if using a direct `folder_id` on conversations

**File**: `app/Models/ConversationFolder.php`

**Implementation**:
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

### 4.3 MailboxUser Model (Pivot)

**Purpose**: Store user permissions for mailboxes

**File**: `app/Models/MailboxUser.php`

**Implementation**:
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

    // Access levels
    const ACCESS_VIEW = 10;
    const ACCESS_REPLY = 20;
    const ACCESS_ADMIN = 30;
}
```

**Migration**:
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

---

## 5. Event Listeners

### 5.1 Audit Logging Listeners

**Purpose**: Track security and user events

#### LogSuccessfulLogin

**File**: `app/Listeners/LogSuccessfulLogin.php`

```php
<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use App\Models\ActivityLog;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        activity()
            ->causedBy($event->user)
            ->withProperties(['ip' => request()->ip()])
            ->useLog(ActivityLog::NAME_USER)
            ->log(ActivityLog::DESCRIPTION_USER_LOGIN);
    }
}
```

**Register in EventServiceProvider**:
```php
protected $listen = [
    \Illuminate\Auth\Events\Login::class => [
        \App\Listeners\LogSuccessfulLogin::class,
    ],
];
```

#### LogSuccessfulLogout

**File**: `app/Listeners/LogSuccessfulLogout.php`

```php
<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use App\Models\ActivityLog;

class LogSuccessfulLogout
{
    public function handle(Logout $event): void
    {
        if ($event->user) {
            activity()
                ->causedBy($event->user)
                ->withProperties(['ip' => request()->ip()])
                ->useLog(ActivityLog::NAME_USER)
                ->log(ActivityLog::DESCRIPTION_USER_LOGOUT);
        }
    }
}
```

#### LogFailedLogin

**File**: `app/Listeners/LogFailedLogin.php`

```php
<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use App\Models\ActivityLog;

class LogFailedLogin
{
    public function handle(Failed $event): void
    {
        activity()
            ->withProperties([
                'email' => $event->credentials['email'] ?? 'unknown',
                'ip' => request()->ip(),
            ])
            ->useLog(ActivityLog::NAME_SECURITY)
            ->log(ActivityLog::DESCRIPTION_LOGIN_FAILED);
    }
}
```

---

## 6. Implementation Priority

### Phase 1: Foundation (Week 1)

**Must Have**:
1. ✅ CreateUser command (CLI administration)
2. ✅ CheckRequirements command (system validation)
3. ✅ ConversationObserver (data integrity)
4. ✅ UserObserver (user lifecycle)
5. ✅ ConversationPolicy (authorization)

**Estimated**: 16 hours

---

### Phase 2: Module System (Week 2)

**Must Have**:
1. ✅ ModuleInstall command
2. ✅ ModuleBuild command
3. ✅ ModuleUpdate command
4. ✅ Module helper methods

**Estimated**: 12 hours

---

### Phase 3: Data Integrity (Week 2)

**Must Have**:
1. ✅ UpdateFolderCounters command
2. ✅ CustomerObserver
3. ✅ Follower model
4. ✅ MailboxUser model
5. ✅ ThreadPolicy
6. ✅ FolderPolicy

**Estimated**: 12 hours

---

### Phase 4: Audit & Security (Week 3)

**Important**:
1. ✅ LogSuccessfulLogin listener
2. ✅ LogSuccessfulLogout listener
3. ✅ LogFailedLogin listener
4. ✅ LogPasswordReset listener
5. ✅ ActivityLog constants

**Estimated**: 8 hours

---

## 7. Testing Strategy

### Unit Tests

Create tests for:
- Console commands (success/failure cases)
- Observer methods (lifecycle hooks)
- Policy methods (authorization rules)

### Integration Tests

Test:
- Complete user lifecycle (create, login, logout, delete)
- Conversation lifecycle (create, update, delete)
- Module installation process

### Manual Testing

Verify:
- CLI commands work as expected
- Authorization rules are enforced
- Audit logs are created properly

---

## 8. Next Steps

1. **Review this document** with the team
2. **Create GitHub issues** for each component
3. **Implement Phase 1** (foundation)
4. **Write tests** for implemented features
5. **Deploy to staging** for validation
6. **Iterate** based on feedback

---

## Appendix: Required Model Constants

### User Model

```php
// app/Models/User.php
const ROLE_ADMIN = 1;
const ROLE_USER = 2;

const STATUS_ACTIVE = 1;
const STATUS_INACTIVE = 2;

const INVITE_STATE_ACTIVATED = 1;
const INVITE_STATE_NOT_INVITED = 2;
```

### Conversation Model

```php
// app/Models/Conversation.php
const STATUS_ACTIVE = 1;
const STATUS_PENDING = 2;
const STATUS_CLOSED = 3;

const PERSON_CUSTOMER = 1;
const PERSON_USER = 2;
```

### Folder Model

```php
// app/Models/Folder.php
const TYPE_INBOX = 1;
const TYPE_MINE = 2;
const TYPE_ASSIGNED = 3;
const TYPE_CLOSED = 4;
const TYPE_SPAM = 5;
const TYPE_DRAFTS = 6;
```

### ActivityLog Model

```php
// app/Models/ActivityLog.php
const NAME_USER = 'user';
const NAME_SECURITY = 'security';
const NAME_SYSTEM = 'system';

const DESCRIPTION_USER_LOGIN = 'User logged in';
const DESCRIPTION_USER_LOGOUT = 'User logged out';
const DESCRIPTION_LOGIN_FAILED = 'Login attempt failed';
```

---

**End of Document**
