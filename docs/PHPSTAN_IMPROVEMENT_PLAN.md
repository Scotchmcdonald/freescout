# PHPStan Improvement Plan
**Generated**: November 6, 2025  
**Current Status**: Level 6 with **97 errors**  
**Previous Status**: Level 6 with 45 errors (documented in roadmap)  
**Target**: Level 7 with 0 errors

## 📊 Executive Summary

The codebase has regressed from 45 to **97 errors** at PHPStan Level 6. This document provides:
1. **Categorized analysis** of all 97 errors
2. **Prioritized action plan** with effort estimates
3. **Quick wins** that can be completed in hours
4. **Architectural improvements** requiring deeper changes
5. **Long-term roadmap** to Level 7-9

---

## 🔍 Current Error Analysis (97 Total)

### Error Breakdown by Category

| Category | Count | % | Priority | Effort |
|----------|-------|---|----------|--------|
| **Missing Return Types** | 7 | 7% | HIGH | Low |
| **Undefined Property Access** | 28 | 29% | HIGH | Medium |
| **Type Mismatches** | 15 | 15% | HIGH | Medium |
| **Unnecessary Collection Calls** | 2 | 2% | MEDIUM | Low |
| **Module Facade Issues** | 3 | 3% | HIGH | Low |
| **Relation Not Found** | 1 | 1% | HIGH | Low |
| **Nullsafe Operator Misuse** | 2 | 2% | LOW | Low |
| **Unknown Classes (Swift)** | 4 | 4% | MEDIUM | Medium |
| **Offset ?? Issues** | 1 | 1% | LOW | Low |
| **Generic/Template Issues** | 34 | 35% | MEDIUM | High |

### Error Distribution by File

| File | Errors | Severity |
|------|--------|----------|
| `app/Services/ImapService.php` | ~20 | Critical |
| `app/Http/Controllers/ConversationController.php` | 9 | High |
| `app/Console/Commands/TestEventSystem.php` | 5 | High |
| `app/Http/Controllers/ModulesController.php` | 6 | High |
| `app/Http/Controllers/DashboardController.php` | 3 | Medium |
| `app/Mail/NewMessageNotification.php` | 4 | Medium |
| `app/Events/NewMessageReceived.php` | 1 | Low |
| `database/seeders/ConversationSeeder.php` | 2 | Low |
| Others | ~47 | Various |

---

## 🎯 Phase 1: Quick Wins (2-4 hours)

These fixes are straightforward and can be completed immediately with minimal risk.

### 1.1 Add Missing Return Types (30 minutes)
**Impact**: Fixes 7 errors immediately  
**Risk**: None - pure annotation

#### Files to Fix:
```php
// app/Console/Commands/TestEventSystem.php
- public function handle() 
+ public function handle(): int

// app/Http/Controllers/ModulesController.php
- public function index()
+ public function index(): \Illuminate\View\View

- public function enable(Request $request)
+ public function enable(Request $request): \Illuminate\Http\RedirectResponse

- public function disable(Request $request)
+ public function disable(Request $request): \Illuminate\Http\RedirectResponse

- public function delete(Request $request)
+ public function delete(Request $request): \Illuminate\Http\RedirectResponse
```

**Checklist:**
- [ ] `TestEventSystem::handle()` → `: int`
- [ ] `ModulesController::index()` → `: View`
- [ ] `ModulesController::enable()` → `: RedirectResponse`
- [ ] `ModulesController::disable()` → `: RedirectResponse`
- [ ] `ModulesController::delete()` → `: RedirectResponse`
- [ ] `AutoReply::build()` → check if needs `: self`
- [ ] All `ActivityLog` scope methods → `: Builder`

---

### 1.2 Fix Module Facade Calls (20 minutes)
**Impact**: Fixes 3 errors  
**Risk**: Low - simple PHPDoc addition

#### Solution:
```php
// app/Http/Controllers/ModulesController.php

use Nwidart\Modules\Facades\Module;

// Option 1: Type assertion
/** @var \Nwidart\Modules\Module|null $module */
$module = Module::findByAlias($alias);

// Option 2: Use find() instead (better)
$module = Module::find($alias);
if (!$module) {
    return redirect()->back()->with('error', 'Module not found');
}
```

**Files:**
- [ ] `ModulesController::enable()` - line 47
- [ ] `ModulesController::disable()` - line 83
- [ ] `ModulesController::delete()` - line 116

---

### 1.3 Fix ActivityLog Relation (10 minutes)
**Impact**: Fixes 1 error  
**Risk**: None

#### Problem:
```php
// app/Http/Controllers/SystemController.php:239
Relation 'user' is not found in App\Models\ActivityLog model.
```

#### Solution:
Add a `user()` accessor or relation to `ActivityLog`:

```php
// app/Models/ActivityLog.php

/**
 * Get the user who caused this activity (convenience accessor).
 */
public function user(): ?\App\Models\User
{
    if ($this->causer_type === \App\Models\User::class) {
        return $this->causer;
    }
    return null;
}
```

---

### 1.4 Fix Unnecessary Collection Calls (15 minutes)
**Impact**: Performance + 2 errors fixed  
**Risk**: None - optimization

#### Files:
```php
// app/Http/Controllers/SettingsController.php

// Line 25 & 62 - Before:
$users = User::all()->pluck('email', 'id');

// After:
$users = User::query()->pluck('email', 'id');
```

---

### 1.5 Fix Nullsafe Operator Misuse (10 minutes)
**Impact**: 2 errors fixed  
**Risk**: None

```php
// database/seeders/ConversationSeeder.php:46
// Before:
$email = $customer?->email ?? 'default@example.com';

// After (since customer is never null here):
$email = $customer->email ?? 'default@example.com';
```

---

### 1.6 Fix Offset ?? False Positive (5 minutes)
**Impact**: 1 error fixed  
**Risk**: None

```php
// app/Http/Controllers/Auth/RegisteredUserController.php:42
// Before:
$name = explode(' ', $request->name)[0] ?? $request->name;

// After:
$nameParts = explode(' ', $request->name);
$name = $nameParts[0] ?? $request->name;
```

---

### ✅ Phase 1 Total: **16 errors fixed in 2-4 hours**

---

## 🔧 Phase 2: Model Property Definitions (3-4 hours)

These require adding properties to models or using proper type hints.

### 2.1 Fix Undefined Model Properties (3 hours)
**Impact**: Fixes ~28 errors  
**Risk**: Low - proper typing

#### Strategy:
For each model property error, choose one approach:

**Option A: Add PHPDoc properties** (Fastest)
```php
/**
 * @property int $id
 * @property string $email
 * @property string $name
 */
class Customer extends Model
```

**Option B: Use explicit accessors** (More robust)
```php
public function getEmailAttribute(): string
{
    return $this->attributes['email'];
}
```

#### Files Requiring Property Definitions:

**High Priority:**
1. **`app/Models/Customer.php`**
   - Missing: `id`, `email`, `name`
   - Used in: ConversationController, Events, Seeders
   - Add: PHPDoc block with all properties

2. **`app/Models/Mailbox.php`**
   - Has fillable array but missing PHPDoc
   - Add: `@property` declarations for commonly accessed fields

3. **`app/Models/Conversation.php`**
   - Missing: `id`, `mailbox_id`, `customer_id`
   - Add: Core property PHPDocs

4. **`app/Models/Thread.php`**
   - Missing: `id`, `conversation_id`, `type`, `status`
   - Add: Property PHPDocs

5. **`app/Models/Attachment.php`** (if exists)
   - Missing: `file_dir`, `file_name`, `file_size`
   - Add: File-related property PHPDocs

**Detailed Fixes:**

```php
// app/Models/Customer.php
/**
 * @property int $id
 * @property string $email
 * @property string $first_name
 * @property string $last_name
 * @property string|null $phone
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Customer extends Model
{
    // ... existing code
}

// app/Models/Thread.php
/**
 * @property int $id
 * @property int $conversation_id
 * @property int $type
 * @property int $status
 * @property string|null $body
 * @property int|null $created_by_user_id
 * @property int|null $created_by_customer_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * 
 * @property-read \App\Models\Conversation $conversation
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\Customer|null $customer
 */
class Thread extends Model
```

#### Checklist:
- [ ] Add properties to `Customer` model
- [ ] Add properties to `Thread` model
- [ ] Add properties to `Conversation` model
- [ ] Add properties to `Mailbox` model
- [ ] Add properties to `ActivityLog` model
- [ ] Add properties to any other models with errors

---

### 2.2 Fix Model Collection Type Issues (1 hour)
**Impact**: Fixes collection-related errors  
**Risk**: Low

Add proper return type hints with generics:

```php
// app/Models/Mailbox.php

/**
 * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Conversation>
 */
public function conversations(): HasMany
{
    return $this->hasMany(Conversation::class);
}
```

---

## 🏗️ Phase 3: Type Hint ImapService (4-6 hours)

**Impact**: Fixes ~20 errors  
**Risk**: Medium - needs testing  
**Priority**: High - critical service

### Problem:
`ImapService.php` has extensive type issues because IMAP library interactions lack type hints.

### Solution Approach:

```php
// app/Services/ImapService.php

use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Folder;

/**
 * Create IMAP client with proper typing.
 */
private function createClient(Mailbox $mailbox): Client
{
    // ...
}

/**
 * Process a single IMAP message.
 * 
 * @param Message $message
 * @return Thread|null
 */
private function processMessage(Message $message, Mailbox $mailbox): ?Thread
{
    // ...
}

/**
 * Extract email addresses from message.
 * 
 * @param Message $message
 * @return array{email: string, name: string|null}
 */
private function extractFromAddress(Message $message): array
{
    // ...
}
```

### Tasks:
- [ ] Add type hints to `createClient()`
- [ ] Add type hints to `processMessage()`
- [ ] Add type hints to `extractFromAddress()`
- [ ] Add type hints to `findOrCreateCustomer()`
- [ ] Add type hints to `findOrCreateConversation()`
- [ ] Add return types to all helper methods
- [ ] Add PHPDoc for complex array returns
- [ ] Test IMAP functionality after changes

---

## 🔥 Phase 4: Event System Type Safety (2-3 hours)

**Impact**: Fixes 5 errors in `TestEventSystem.php`  
**Risk**: Low - test command

### Problem:
```php
Parameter #2 $thread of class App\Events\CustomerCreatedConversation constructor 
expects App\Models\Thread, Illuminate\Database\Eloquent\Model given.
```

### Root Cause:
Using `::firstOrCreate()` which returns `Model` not specific type.

### Solution:

```php
// app/Console/Commands/TestEventSystem.php

// Before:
$thread = Thread::firstOrCreate([...]);
event(new CustomerCreatedConversation($conversation, $thread, $customer));

// After - Option 1: Type assertion
$thread = Thread::firstOrCreate([...]);
assert($thread instanceof Thread);
event(new CustomerCreatedConversation($conversation, $thread, $customer));

// After - Option 2: Cast with PHPDoc
/** @var Thread $thread */
$thread = Thread::firstOrCreate([...]);
event(new CustomerCreatedConversation($conversation, $thread, $customer));

// After - Option 3: Use explicit instantiation (best)
$thread = Thread::create([...]);
event(new CustomerCreatedConversation($conversation, $thread, $customer));
```

### Tasks:
- [ ] Fix `CustomerCreatedConversation` event dispatch (line 57)
- [ ] Fix `CustomerReplied` event dispatch (line 61)
- [ ] Ensure all event constructors receive correct types
- [ ] Add return type to `handle()` method

---

## 🧩 Phase 5: Controller Type Safety (3-4 hours)

### 5.1 ConversationController (2 hours)
**Impact**: 9 errors fixed

```php
// app/Http/Controllers/ConversationController.php

// Line 147 - Fix collection vs single model
public function store(Request $request)
{
    // Before:
    $customer = Customer::firstOrCreate(['email' => $email]);
    // $customer could be Collection if query is wrong
    
    // After:
    /** @var Customer $customer */
    $customer = Customer::firstOrCreate(['email' => $email]);
    
    // Or better:
    $customer = Customer::where('email', $email)->first() 
        ?? Customer::create(['email' => $email]);
}

// Line 159, 262 - Model property access
// Add proper PHPDoc to models (covered in Phase 2)
```

### 5.2 DashboardController (1 hour)
**Impact**: 3 errors fixed

```php
// app/Http/Controllers/DashboardController.php

// Lines 43, 44, 48 - Undefined property Model::$id
// Solution: Ensure query returns specific model type

/** @var \App\Models\Conversation $conversation */
$conversation = Conversation::find($id);
if ($conversation) {
    $conversationId = $conversation->id;
}
```

---

## 🌐 Phase 6: Swift Mailer Deprecation (2 hours)

**Impact**: Fixes 4 errors  
**Risk**: Medium - requires testing email functionality

### Problem:
```php
Call to method getMessage() on an unknown class Swift_TransportException.
```

### Context:
Swift Mailer was deprecated and removed in Laravel 9+. The code still references Swift classes.

### Solution:

```php
// app/Mail/NewMessageNotification.php (or similar)

// Before:
} catch (\Swift_TransportException $e) {
    Log::error('Mail sending failed: ' . $e->getMessage());
}

// After:
} catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
    Log::error('Mail sending failed: ' . $e->getMessage());
}

// Or more generic:
} catch (\Exception $e) {
    Log::error('Mail sending failed: ' . $e->getMessage());
}
```

### Tasks:
- [ ] Find all Swift_TransportException references
- [ ] Replace with Symfony Mailer exceptions
- [ ] Update error handling logic
- [ ] Test email sending functionality

---

## 📈 Progress Tracking

### Current State
```
┌─────────────────────────────────────────┐
│ PHPStan Level 6                         │
│ Errors: 97                              │
│ Status: NEEDS ATTENTION                 │
└─────────────────────────────────────────┘
```

### After Quick Wins (Phase 1)
```
┌─────────────────────────────────────────┐
│ PHPStan Level 6                         │
│ Errors: 81 (-16)                        │
│ Status: IMPROVED                        │
│ Time: 2-4 hours                         │
└─────────────────────────────────────────┘
```

### After Model Fixes (Phase 1-2)
```
┌─────────────────────────────────────────┐
│ PHPStan Level 6                         │
│ Errors: 53 (-44)                        │
│ Status: GOOD PROGRESS                   │
│ Time: 5-8 hours                         │
└─────────────────────────────────────────┘
```

### After All Quick + Medium Fixes (Phase 1-4)
```
┌─────────────────────────────────────────┐
│ PHPStan Level 6                         │
│ Errors: 20-25                           │
│ Status: NEARLY CLEAN                    │
│ Time: 12-16 hours                       │
└─────────────────────────────────────────┘
```

### Target State (All Phases Complete)
```
┌─────────────────────────────────────────┐
│ PHPStan Level 6                         │
│ Errors: 0 ✓                             │
│ Status: READY FOR LEVEL 7               │
│ Time: 16-24 hours                       │
└─────────────────────────────────────────┘
```

---

## 🚀 Recommended Execution Plan

### Week 1: Foundation (Days 1-2)
**Goal**: Reduce errors by 50%

**Day 1 (4 hours)**
- [ ] Morning: Phase 1 - All quick wins (16 errors → 81 remaining)
- [ ] Afternoon: Phase 2.1 - Start model properties (Customer, Thread models)

**Day 2 (4 hours)**
- [ ] Morning: Phase 2.1 - Complete model properties (all models)
- [ ] Afternoon: Phase 2.2 - Collection type hints

**Expected Result**: ~53 errors remaining

---

### Week 1: Core Services (Days 3-4)
**Goal**: Fix critical services

**Day 3 (4 hours)**
- [ ] Phase 3 - ImapService type hints (20 errors fixed)
- [ ] Test IMAP functionality

**Day 4 (3 hours)**
- [ ] Phase 4 - Event system fixes
- [ ] Phase 5.1 - ConversationController

**Expected Result**: ~20-25 errors remaining

---

### Week 2: Polish (Day 5)
**Goal**: Achieve zero errors at Level 6

**Day 5 (4 hours)**
- [ ] Phase 5.2 - DashboardController
- [ ] Phase 6 - Swift Mailer cleanup
- [ ] Remaining miscellaneous errors
- [ ] Full test suite run
- [ ] Documentation update

**Expected Result**: 0 errors at Level 6 ✓

---

## 🎯 Level 7 Preparation

Once Level 6 is clean, prepare for Level 7 by:

### 7.1 Baseline Generation
```bash
vendor/bin/phpstan analyse --level=7 --generate-baseline
```

### 7.2 Configuration Update
```neon
# phpstan.neon
parameters:
    level: 7
    checkUnionTypes: true
    checkBenevolentUnionTypes: true
```

### 7.3 Expected New Errors
- Union type mismatches: ~30-50 errors
- Generic collection types: ~20-30 errors
- Array shape definitions: ~10-20 errors

**Total Expected**: 60-100 new errors at Level 7

---

## 📋 Detailed File-by-File Checklist

### Controllers (High Priority)
- [ ] `ConversationController.php` - 9 errors
  - [ ] Line 147: Customer type
  - [ ] Line 159: Model property
  - [ ] Line 262: Model property
  
- [ ] `ModulesController.php` - 6 errors
  - [ ] Line 21: return type
  - [ ] Line 45: return type
  - [ ] Line 47: Module facade
  - [ ] Line 81: return type
  - [ ] Line 83: Module facade
  - [ ] Line 114: return type
  - [ ] Line 116: Module facade

- [ ] `DashboardController.php` - 3 errors
  - [ ] Lines 43, 44, 48: Model properties

- [ ] `SettingsController.php` - 2 errors
  - [ ] Lines 25, 62: Collection calls

- [ ] `SystemController.php` - 1 error
  - [ ] Line 239: ActivityLog relation

- [ ] `Auth/RegisteredUserController.php` - 1 error
  - [ ] Line 42: Offset access

- [ ] `Auth/VerifyEmailController.php` - 1 error
  - [ ] Line 22: Type mismatch

### Services (Critical)
- [ ] `ImapService.php` - ~20 errors
  - [ ] Add return types to all methods
  - [ ] Add parameter types (Message, Folder, etc.)
  - [ ] Document array returns with PHPDoc

### Models (Foundation)
- [ ] `Customer.php` - Add PHPDoc properties
- [ ] `Thread.php` - Add PHPDoc properties
- [ ] `Conversation.php` - Add PHPDoc properties
- [ ] `Mailbox.php` - Add PHPDoc properties
- [ ] `ActivityLog.php` - Add user() method, fix scopes
- [ ] `Attachment.php` - Add file properties (if exists)

### Commands
- [ ] `TestEventSystem.php` - 5 errors
  - [ ] Line 30: return type
  - [ ] Lines 57, 61: Event parameters

### Mail
- [ ] `NewMessageNotification.php` - 4 errors (Swift)
- [ ] `AutoReply.php` - Return type

### Events
- [ ] `NewMessageReceived.php` - 1 error
  - [ ] Line 74: Model property

### Seeders
- [ ] `ConversationSeeder.php` - 2 errors
  - [ ] Line 43: Model property
  - [ ] Line 46: Nullsafe operator

---

## 🔧 Configuration Improvements

### Current PHPStan Config Review
```neon
# phpstan.neon - Current
parameters:
    level: 6
    checkModelProperties: true  # ✓ Good
    checkOctaneCompatibility: true  # ✓ Good
    reportUnmatchedIgnoredErrors: false  # ⚠️ Should be true after fixes
    
    ignoreErrors:
        # ⚠️ Too broad - blocks legitimate errors
        - '#Call to an undefined method [a-zA-Z0-9\\_]+::[a-zA-Z0-9\\_]+\(\)#'
```

### Recommended Config After Fixes
```neon
# phpstan.neon - Improved
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    level: 6  # Will become 7 after cleanup
    
    paths:
        - app
        - config
        - database
        - routes
    
    excludePaths:
        - archive/*
        - vendor/*
        - storage/*
        - bootstrap/cache/*
    
    # Laravel-specific
    checkModelProperties: true
    checkOctaneCompatibility: true
    
    # Stricter settings (enable after Level 6 is clean)
    reportUnmatchedIgnoredErrors: true
    checkMissingIterableValueType: false  # Enable at Level 7
    
    # Specific ignores (much narrower than before)
    ignoreErrors:
        # Legacy module system - acceptable temporary ignore
        - 
            message: '#Call to an undefined method.*Module::findByAlias#'
            path: app/Http/Controllers/ModulesController.php
        
        # If you have specific legacy issues, document them here
```

---

## 📊 Success Metrics

### Quantitative Goals
- [x] Document current state: 97 errors
- [ ] Phase 1 complete: 81 errors (-16)
- [ ] Phase 2 complete: 53 errors (-44)
- [ ] Phase 3 complete: 33 errors (-64)
- [ ] Phase 4 complete: 28 errors (-69)
- [ ] Phase 5 complete: 16 errors (-81)
- [ ] Phase 6 complete: 0 errors ✓

### Qualitative Goals
- [ ] All models have proper PHPDoc property definitions
- [ ] All controllers have return type hints
- [ ] All services have parameter and return types
- [ ] Zero broad ignore patterns in PHPStan config
- [ ] ImapService is fully type-safe
- [ ] Event system has proper type safety

---

## 🚨 Risk Assessment

### Low Risk (Proceed immediately)
- Adding return types
- Adding PHPDoc properties
- Fixing module facade calls
- Collection optimization

### Medium Risk (Needs testing)
- ImapService type hints
- Swift mailer replacement
- Event system changes

### High Risk (Requires careful review)
- None identified - all changes are non-breaking type additions

---

## 🛠️ Tools & Commands

### Run PHPStan
```bash
# Full analysis
vendor/bin/phpstan analyse

# Specific file
vendor/bin/phpstan analyse app/Services/ImapService.php

# With baseline
vendor/bin/phpstan analyse --generate-baseline

# Different level
vendor/bin/phpstan analyse --level=7
```

### Generate Error Report
```bash
# JSON format for processing
vendor/bin/phpstan analyse --error-format=json > phpstan-errors.json

# Table format (readable)
vendor/bin/phpstan analyse --error-format=table

# GitHub Actions format
vendor/bin/phpstan analyse --error-format=github
```

### Run After Each Phase
```bash
# Quick check
vendor/bin/phpstan analyse --no-progress | tail -20

# Count errors
vendor/bin/phpstan analyse 2>&1 | grep "Found [0-9]* error"
```

---

## 📚 Learning Resources

### PHPStan Documentation
- [PHPStan Levels](https://phpstan.org/user-guide/rule-levels)
- [PHPDoc Types](https://phpstan.org/writing-php-code/phpdoc-types)
- [Generics](https://phpstan.org/blog/generics-in-php-using-phpdocs)

### Laravel-Specific
- [Larastan Documentation](https://github.com/larastan/larastan)
- [Model Properties](https://phpstan.org/blog/solving-phpstan-access-to-undefined-property)
- [Collection Types](https://phpstan.org/blog/union-types-vs-intersection-types)

---

## 🎯 Next Steps - Start Here!

1. **Review this document** with team
2. **Choose execution timeline**: Fast (1 week) vs Thorough (2 weeks)
3. **Start with Phase 1** - Quick wins for immediate impact
4. **Create branch**: `feature/phpstan-level-6-cleanup`
5. **Work through phases** sequentially
6. **Test after each phase** to ensure no regressions
7. **Update this document** as you progress

---

## 📝 Notes

### Why Did Errors Increase from 45 to 97?
Possible reasons:
1. New code added without type hints
2. PHPStan version upgraded (more strict)
3. Larastan configuration changed
4. New features added to controllers/services
5. Previous roadmap baseline was inaccurate

### What to Do About Swift Mailer?
Laravel 9+ uses Symfony Mailer. The Swift references are likely:
- Legacy code from previous Laravel version
- Catch blocks that need updating
- Safe to replace with Symfony equivalents

### Should We Enable More Strict Rules?
Not yet. Get to Level 6 with 0 errors first, then:
1. Enable `reportUnmatchedIgnoredErrors: true`
2. Remove broad ignore patterns
3. Move to Level 7
4. Consider Level 8 as long-term goal

---

**Last Updated**: November 6, 2025  
**Maintainer**: Development Team  
**Status**: Ready for Implementation  
**Estimated Total Effort**: 16-24 hours over 1-2 weeks
