# Test Coverage Analysis & Strategic Implementation Plan
**Generated:** 2025-11-17  
**Current Coverage:** 85.9% (with skipped tests)  
**Goal:** 80%+ on ALL classes and methods

---

## Executive Summary

### Current State
- **Total Coverage:** 85.9%
- **Tests Passing:** 2,997 tests (6,175 assertions)
- **Tests Skipped:** 3 (SystemController log tests - OOM during coverage)
- **Coverage Distribution:**
  - **0% coverage:** 103 classes, 397 methods (CRITICAL PRIORITY)
  - **100% coverage:** 1 class, 12 methods (EXCELLENT)
  - **Below 80%:** Majority of codebase

### Critical Findings

**Top 3 Highest Risk Areas (CRAP Index):**
1. **ImapService::processMessage** - CRAP: 6,162 (!!!)
2. **ModuleUpdate::handle** - CRAP: 600
3. **ImapService::getAddressesWithNames** - CRAP: 552

**Most Impactful Low-Hanging Fruit:**
1. All Event classes: 0% coverage, simple constructors/broadcasts
2. All Observer classes: 0% coverage, lifecycle hooks
3. All Mailable classes: 0% coverage, simple envelope/content methods

---

## Coverage Breakdown by Category

### 🔴 **CRITICAL PRIORITY - 0% Coverage (103 Classes)**

#### **1. Console Commands (14 classes - 0% coverage)**
**Impact:** HIGH | **Complexity:** MEDIUM | **Est. Tests:** 150

| Class | CRAP | Methods | Priority |
|-------|------|---------|----------|
| CheckRequirements | 380 | 6 | HIGH - System diagnostics |
| ModuleUpdate | 620 | 1 | HIGH - Complex logic |
| ModuleInstall | 396 | 2 | HIGH - File operations |
| ModuleBuild | 182 | 3 | MEDIUM |
| CreateUser | 156 | 1 | MEDIUM |
| FetchEmails | 110 | 1 | MEDIUM |
| Update | 12 | 1 | MEDIUM |
| ConfigureGmailMailbox | 30 | 1 | LOW |
| ClearCache | 35 | 1 | LOW |
| LogoutUsers | 33 | 1 | LOW |
| TestEventSystem | 20 | 1 | LOW |
| UpdateFolderCounters | 20 | 1 | LOW |
| GenerateVars | 0 | 1 | LOW |
| AfterAppUpdate | 6 | 1 | LOW |

**Test Strategy:**
- Use `Artisan::fake()` to mock command execution
- Test exit codes, output formatting, validation
- Mock external services (IMAP, filesystem, HTTP)
- Reference: `tests/Feature/Commands/` for patterns

---

#### **2. Events (11 classes - 0% coverage)**
**Impact:** HIGH | **Complexity:** LOW | **Est. Tests:** 55

| Class | CRAP | Methods | Properties |
|-------|------|---------|------------|
| ConversationUpdated | 30 | 5 | conversation, changes |
| NewMessageReceived | 56 | 5 | mailbox, message |
| UserViewingConversation | 0 | 4 | conversation, user |
| ConversationStatusChanged | 0 | 1 | conversation, oldStatus, newStatus |
| ConversationUserChanged | 0 | 1 | conversation, oldUser, newUser |
| CustomerCreatedConversation | 0 | 1 | conversation, customer |
| CustomerReplied | 0 | 1 | conversation, thread, customer |
| UserAddedNote | 0 | 1 | conversation, thread, user |
| UserCreatedConversation | 0 | 1 | conversation, user |
| UserDeleted | 0 | 1 | user |
| UserReplied | 0 | 1 | conversation, thread, user |

**Test Strategy:**
```php
// Pattern from existing tests
public function test_event_is_dispatched_with_correct_data(): void
{
    Event::fake([UserReplied::class]);
    
    $conversation = Conversation::factory()->create();
    $thread = Thread::factory()->for($conversation)->create();
    $user = User::factory()->create();
    
    event(new UserReplied($conversation, $thread, $user));
    
    Event::assertDispatched(UserReplied::class, function ($event) use ($conversation, $thread, $user) {
        return $event->conversation->id === $conversation->id
            && $event->thread->id === $thread->id
            && $event->user->id === $user->id;
    });
}

// Test broadcast channel
public function test_event_broadcasts_on_correct_channel(): void
{
    $conversation = Conversation::factory()->create();
    $event = new ConversationUpdated($conversation, ['status' => 'closed']);
    
    $channels = $event->broadcastOn();
    
    $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
    $this->assertEquals('conversation.' . $conversation->id, $channels[0]->name);
}
```

**Reference:** Similar to Batch 6 Listeners tests - test event dispatch, properties, broadcast channels

---

#### **3. HTTP Controllers (21 classes - 0% coverage)**
**Impact:** CRITICAL | **Complexity:** HIGH | **Est. Tests:** 300+

##### Auth Controllers (10 classes)
| Class | CRAP | Key Methods |
|-------|------|-------------|
| AuthenticatedSessionController | 0 | create, store, destroy |
| RegisteredUserController | 72 | store (complex validation) |
| NewPasswordController | 56 | store (password reset) |
| LoginRequest | 56 | authenticate, ensureIsNotRateLimited |
| ConfirmablePasswordController | 12 | show, store |
| EmailVerificationNotificationController | 6 | store |
| EmailVerificationPromptController | 6 | __invoke |
| PasswordController | 0 | update |
| PasswordResetLinkController | 12 | create, store |
| VerifyEmailController | 12 | __invoke |

**Existing Coverage:** `tests/Feature/Auth/` already has excellent auth tests!
- ✅ AuthenticationTest
- ✅ EmailVerificationNotificationTest
- ✅ EmailVerificationTest
- ✅ PasswordConfirmationTest
- ✅ PasswordResetTest
- ✅ PasswordUpdateTest
- ✅ RegistrationTest

**Why 0%?** Coverage tool isn't picking up Feature test execution properly!

**Action Required:**
1. Verify Feature tests execute controllers (they do via HTTP)
2. Add Unit tests to explicitly hit controller methods
3. Coverage likely false negative - controllers ARE tested

---

##### Core Application Controllers (11 classes)
| Class | CRAP | Priority | Reason |
|-------|------|----------|---------|
| **ConversationController** | 6,642 | CRITICAL | 81 methods, core functionality |
| **MailboxController** | 2,652 | CRITICAL | 51 methods, IMAP configuration |
| **SettingsController** | 1,640 | HIGH | 40 methods, system configuration |
| **SystemController** | 1,190 | HIGH | 34 methods, diagnostics |
| **UserController** | 992 | HIGH | 31 methods, user management |
| **CustomerController** | 462 | MEDIUM | 21 methods |
| DashboardController | 12 | LOW | 3 methods |
| ProfileController | 20 | LOW | 4 methods (likely covered by Feature) |
| ModulesController | 182 | MEDIUM | 13 methods |

**Existing Coverage:** `tests/Feature/` has extensive controller coverage!
- ✅ ConversationTest, ConversationAdvancedTest, ConversationReplyTest
- ✅ CustomerManagementTest
- ✅ MailboxCRUDTest, MailboxConnectionTest, MailboxViewTest
- ✅ UserManagementTest
- ✅ DashboardTest

**Problem:** Feature tests hit routes, not direct controller methods. Coverage tool measures method-level execution.

**Solution Strategy:**
```php
// tests/Unit/Controllers/ConversationControllerTest.php exists but has gaps
// Add missing method tests:

public function test_ajax_returns_json_for_change_status(): void
{
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $conversation = Conversation::factory()->for($mailbox)->create();
    
    $controller = new ConversationController();
    $request = Request::create('/ajax', 'POST', [
        'action' => 'change_status',
        'conversation_id' => $conversation->id,
        'status' => Conversation::STATUS_CLOSED
    ]);
    
    $this->actingAs($user);
    $response = $controller->ajax($request);
    
    $this->assertInstanceOf(JsonResponse::class, $response);
    $conversation->refresh();
    $this->assertEquals(Conversation::STATUS_CLOSED, $conversation->status);
}
```

---

#### **4. Jobs (5 classes - 0% coverage)**
**Impact:** HIGH | **Complexity:** MEDIUM-HIGH | **Est. Tests:** 70

| Class | CRAP | Priority | Key Logic |
|-------|------|----------|-----------|
| **SendNotificationToUsers** | 600 | CRITICAL | Complex user filtering, notification logic |
| **SendAutoReply** | 182 | HIGH | Auto-reply detection, duplicate prevention |
| SendAlert | 42 | MEDIUM | Alert dispatching |
| SendEmailReplyError | 30 | LOW | Error notification |
| SendConversationReply | 0 | LOW | Reply dispatching |

**Existing Tests:** `tests/Unit/Jobs/` has SendAutoReplyTest and SendNotificationToUsersTest!

**Problem:** Tests exist but methods aren't fully covered. Need more edge cases.

**Enhancement Strategy:**
```php
// tests/Unit/Jobs/SendNotificationToUsersTest.php - Add edge cases

public function test_skips_deleted_users(): void
{
    // Already exists - expand to cover all branches
}

public function test_respects_user_subscription_preferences(): void
{
    $user = User::factory()->create();
    $user->subscriptions()->create([
        'event' => 'user_replied',
        'medium' => Subscription::MEDIUM_EMAIL,
    ]);
    
    // User subscribed - should send
    // Test opposite - user unsubscribed - should not send
}

public function test_handles_mail_send_failures_gracefully(): void
{
    Mail::shouldReceive('to->send')->andThrow(new \Exception('SMTP error'));
    
    // Should catch and log, not crash job
}
```

---

#### **5. Listeners (14 classes - 0% coverage)**
**Impact:** HIGH | **Complexity:** MEDIUM | **Est. Tests:** 84

| Class | CRAP | Priority |
|-------|------|----------|
| **SendNotificationToUsers** | 210 | CRITICAL |
| **SendAutoReply** | 182 | HIGH |
| **SendReplyToCustomer** | 182 | HIGH |
| UpdateMailboxCounters | 6 | MEDIUM |
| RememberUserLocale | 6 | LOW |
| SendPasswordChanged | 6 | LOW |
| HandleNewMessage | 0 | MEDIUM |
| Log* (7 listeners) | 0 each | LOW |

**Existing Tests:** `tests/Unit/Listeners/` has some tests but incomplete!

**Test Pattern:**
```php
// tests/Unit/Listeners/SendAutoReplyTest.php exists
// Expand coverage:

public function test_listener_does_not_send_for_internal_notes(): void
{
    $conversation = Conversation::factory()->create();
    $thread = Thread::factory()->for($conversation)->create([
        'type' => Thread::TYPE_NOTE // Internal note
    ]);
    
    Mail::shouldReceive('to->send')->never();
    
    $listener = new SendAutoReply();
    $listener->handle(new CustomerReplied($conversation, $thread, $thread->customer));
}

public function test_listener_checks_mailbox_auto_reply_enabled(): void
{
    $mailbox = Mailbox::factory()->create(['auto_reply_enabled' => false]);
    // ... should not send when disabled
}
```

---

#### **6. Mailable Classes (8 classes - 0% coverage)**
**Impact:** MEDIUM | **Complexity:** LOW | **Est. Tests:** 40

| Class | CRAP | Methods | Priority |
|-------|------|---------|----------|
| AutoReply | 132 | 4 | HIGH (most complex) |
| UserNotification | 72 | 4 | MEDIUM |
| Alert | 30 | 3 | LOW |
| ConversationReplyNotification | 0 | 4 | MEDIUM |
| PasswordChanged | 20 | 3 | LOW |
| UserInvite | 20 | 3 | LOW |
| Test | 0 | 3 | LOW |
| UserEmailReplyError | 0 | 3 | LOW |

**Test Strategy:**
```php
// Pattern from Laravel docs
public function test_auto_reply_mailable_contains_correct_subject(): void
{
    $mailbox = Mailbox::factory()->create(['name' => 'Support']);
    $conversation = Conversation::factory()->for($mailbox)->create();
    
    $mailable = new AutoReply($conversation, $mailbox, 'Auto reply message');
    
    $mailable->assertHasSubject('[Support] Auto Reply');
}

public function test_auto_reply_mailable_has_correct_from_address(): void
{
    $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
    $conversation = Conversation::factory()->create();
    
    $mailable = new AutoReply($conversation, $mailbox, 'Message');
    
    $mailable->assertFrom('support@example.com');
}

public function test_auto_reply_includes_message_body(): void
{
    $mailable = new AutoReply($conversation, $mailbox, 'Custom message');
    
    $mailable->assertSeeInHtml('Custom message');
}
```

---

#### **7. Observers (6 classes - 0% coverage)**
**Impact:** HIGH | **Complexity:** MEDIUM | **Est. Tests:** 42

| Class | CRAP | Methods | Priority |
|-------|------|---------|----------|
| ConversationObserver | 182 | 6 | HIGH - Critical lifecycle |
| UserObserver | 42 | 4 | HIGH - Folder creation |
| MailboxObserver | 20 | 3 | MEDIUM - Default folders |
| AttachmentObserver | 20 | 1 | MEDIUM - File cleanup |
| ThreadObserver | 12 | 2 | LOW |
| CustomerObserver | 0 | 2 | LOW |

**Existing Tests:** `tests/Unit/Observers/AttachmentObserverTest.php` exists!

**Enhancement Needed:**
```php
// tests/Unit/Observers/ConversationObserverTest.php - NEW FILE

public function test_creating_sets_default_number(): void
{
    $conversation = new Conversation(['mailbox_id' => 1]);
    
    // Should auto-increment conversation number
    $observer = new ConversationObserver();
    $observer->creating($conversation);
    
    $this->assertNotNull($conversation->number);
}

public function test_created_fires_conversation_updated_event(): void
{
    Event::fake([ConversationUpdated::class]);
    
    $conversation = Conversation::factory()->create();
    
    Event::assertDispatched(ConversationUpdated::class);
}

public function test_updated_updates_folder_counters(): void
{
    $conversation = Conversation::factory()->create(['status' => Conversation::STATUS_ACTIVE]);
    
    $conversation->status = Conversation::STATUS_CLOSED;
    $conversation->save(); // Triggers observer
    
    // Folder counters should be recalculated
}

public function test_deleting_removes_threads_and_attachments(): void
{
    $conversation = Conversation::factory()->has(Thread::factory()->count(3))->create();
    
    $conversation->delete(); // Should cascade
    
    $this->assertEquals(0, Thread::where('conversation_id', $conversation->id)->count());
}
```

---

#### **8. Helpers & Utilities (4 classes - 0% coverage)**
**Impact:** MEDIUM | **Complexity:** MEDIUM | **Est. Tests:** 60

| Class | CRAP | Coverage | Priority |
|-------|------|----------|----------|
| **MailHelper** | 2,070 | 98.2% (!!) | LOW - Nearly complete! |
| Helper | 20 | 25% | MEDIUM |
| WpApi | 0 | 0% | LOW |
| Module | 0 | 0% | LOW |

**MailHelper:** Already excellent! Only 2 missing lines out of 220.

**Missing Tests for Helper:**
```php
// tests/Unit/Misc/HelperTest.php - NEW FILE

public function test_is_installed_returns_true_when_env_exists(): void
{
    // Mock .env file existence
    $this->assertTrue(Helper::isInstalled());
}

public function test_queue_worker_restart_creates_restart_file(): void
{
    Storage::fake('framework');
    
    Helper::queueWorkerRestart();
    
    Storage::disk('framework')->assertExists('schedule-*');
}

public function test_set_guzzle_default_options_sets_timeout(): void
{
    $options = Helper::setGuzzleDefaultOptions([]);
    
    $this->assertArrayHasKey('timeout', $options);
    $this->assertEquals(30, $options['timeout']);
}
```

---

#### **9. Models (12 classes with 0% coverage)**
**Impact:** MEDIUM-LOW | **Complexity:** LOW | **Est. Tests:** 120

Most model methods are simple accessors/relationships. Priority order:

| Class | Coverage | CRAP | Priority | Reason |
|-------|----------|------|----------|---------|
| **Customer** | 11% | 1,576 | HIGH | create(), setData() are complex |
| **User** | 31% | 135 | MEDIUM | Auth & permissions |
| **Conversation** | 43% | 102 | MEDIUM | updateFolder() logic |
| **Thread** | 51% | 36 | LOW | Mostly covered |
| **Mailbox** | 61% | 9 | LOW | Well covered |
| **Attachment** | 46% | 14 | LOW | isImage() method |
| ActivityLog | 0% | 90 | MEDIUM | Scopes |
| Channel | 0% | 0 | LOW | Simple model |
| ConversationFolder | 0% | 0 | LOW | Pivot table |
| CustomerChannel | 0% | 0 | LOW | Pivot table |
| Email | 0% | 72 | MEDIUM | sanitizeEmail() |
| Folder | 0% | 0 | LOW | Relationships |
| Follower | 0% | 0 | LOW | Pivot table |
| MailboxUser | 0% | 0 | LOW | Pivot table |
| Module | 0% | 0 | LOW | activate/deactivate |
| Option | 0% | 42 | MEDIUM | getValue/setValue |
| SendLog | 0% | 0 | LOW | Status checks |
| Subscription | 0% | 0 | LOW | Type checks |

**Test Strategy:**
```php
// Focus on business logic, not CRUD

// tests/Unit/Models/CustomerTest.php - ENHANCE EXISTING

public function test_create_handles_duplicate_email(): void
{
    Customer::factory()->create(['email' => 'test@example.com']);
    
    // Should return existing or handle gracefully
    $customer = Customer::create(['email' => 'test@example.com', 'first_name' => 'John']);
    
    $this->assertInstanceOf(Customer::class, $customer);
}

public function test_set_data_merges_with_existing_data(): void
{
    $customer = Customer::factory()->create(['first_name' => 'John']);
    
    $customer->setData(['last_name' => 'Doe'], false, false);
    
    $this->assertEquals('John', $customer->first_name);
    $this->assertEquals('Doe', $customer->last_name);
}
```

---

#### **10. Policies (5 classes - 0% coverage)**
**Impact:** CRITICAL | **Complexity:** MEDIUM | **Est. Tests:** 50

| Class | CRAP | Methods | Priority |
|-------|------|---------|----------|
| **MailboxPolicy** | 462 | 10 | CRITICAL |
| **ConversationPolicy** | 306 | 6 | CRITICAL |
| **ThreadPolicy** | 132 | 2 | HIGH |
| **UserPolicy** | 132 | 5 | HIGH |
| FolderPolicy | 12 | 1 | LOW |

**Existing Tests:** `tests/Unit/Policies/` has some policy tests!

**Why 0%?** Need more comprehensive edge case coverage.

**Enhancement Strategy:**
```php
// tests/Unit/Policies/MailboxPolicyTest.php - ENHANCE

public function test_admin_can_perform_all_actions(): void
{
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $policy = new MailboxPolicy();
    
    $this->assertTrue($policy->viewAny($admin));
    $this->assertTrue($policy->view($admin, $mailbox));
    $this->assertTrue($policy->create($admin));
    $this->assertTrue($policy->update($admin, $mailbox));
    $this->assertTrue($policy->delete($admin, $mailbox));
    $this->assertTrue($policy->admin($admin, $mailbox));
}

public function test_user_with_admin_permission_can_update_settings(): void
{
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user->id, ['access' => MailboxUser::ACCESS_ADMIN]);
    
    $policy = new MailboxPolicy();
    
    $this->assertTrue($policy->admin($user, $mailbox));
}

public function test_user_with_view_only_cannot_reply(): void
{
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user->id, ['access' => MailboxUser::ACCESS_VIEW]);
    
    $policy = new MailboxPolicy();
    
    $this->assertFalse($policy->reply($user, $mailbox));
}
```

---

#### **11. Services (2 classes - 0% coverage)**
**Impact:** CRITICAL | **Complexity:** VERY HIGH | **Est. Tests:** 200+

| Class | Coverage | CRAP | Lines | Methods |
|-------|----------|------|-------|---------|
| **ImapService** | 77.3% (!!) | 34,410 | 1,100+ | 14 |
| **SmtpService** | 71.3% | 812 | 200 | 6 |

**HUGE WIN:** These are already ~75% covered!

**Existing Tests:**
- ✅ `tests/Unit/Services/ImapServiceTest.php` - 141 tests in Batch 1!
- ✅ `tests/Unit/Services/ImapServiceParseAddressesTest.php` - 36 tests
- ✅ Controller tests use SMTP service

**Missing Coverage - ImapService:**
- Edge cases in `processMessage()` (CRAP 6,162!)
- Error handling in `fetchEmails()`
- Bounce detection logic
- Forward detection edge cases

**Missing Coverage - SmtpService:**
- Connection timeout scenarios
- SSL/TLS encryption variations
- Authentication failures

**Enhancement Strategy:**
```php
// tests/Unit/Services/ImapServiceTest.php - ADD EDGE CASES

public function test_processMessage_handles_malformed_from_address(): void
{
    $message = $this->createMockMessage([
        'from' => ['not-an-email'], // Malformed
    ]);
    
    // Should handle gracefully, not crash
}

public function test_processMessage_detects_out_of_office_replies(): void
{
    $message = $this->createMockMessage([
        'subject' => 'Out of Office AutoReply',
        'headers' => ['X-Auto-Response-Suppress' => 'OOF'],
    ]);
    
    // Should NOT create conversation for OOF
}

public function test_fetchEmails_resumes_after_partial_failure(): void
{
    // First message processes OK
    // Second message throws exception
    // Third message should still process
}
```

---

#### **12. Middleware & Requests (4 classes - 0% coverage)**
**Impact:** MEDIUM | **Complexity:** LOW | **Est. Tests:** 20

| Class | CRAP | Priority |
|-------|------|----------|
| LoginRequest | 56 | MEDIUM - Has complex auth logic |
| FrameGuard | 12 | LOW |
| EnsureUserIsAdmin | 12 | LOW |
| ProfileUpdateRequest | 30 | LOW |

**Likely Already Covered:** Feature tests authenticate and hit these, but no direct unit tests.

---

### 🟡 **MEDIUM PRIORITY - Partial Coverage**

#### Classes with 1-50% Coverage
| Class | Coverage | CRAP | Gap Analysis |
|-------|----------|------|--------------|
| ModuleUpdate | 1.6% | 620 | Complex update logic untested |
| ModuleInstall | 2% | 396 | Symlink creation edge cases |
| ClearCache | 6.25% | 35 | Missing error scenarios |
| LogoutUsers | 8.3% | 33 | Missing edge cases |
| Customer | 11% | 1,576 | create() & setData() complex methods |
| User | 31% | 135 | Permission methods need coverage |
| Conversation | 43% | 102 | updateFolder() edge cases |
| ModuleCompatibilityServiceProvider | 45% | 9 | Boot() method partially covered |
| Attachment | 46% | 14 | isImage() edge cases |
| EventServiceProvider | 50% | 0 | Half covered - add remaining events |
| Thread | 51% | 36 | Type checking methods |
| Mailbox | 61% | 9 | getMailFrom() edge cases |

**Strategy:** Add 5-10 tests per class focusing on uncovered branches.

---

### 🟢 **LOW PRIORITY - Good Coverage**

#### Classes with 60%+ Coverage (Maintain & Polish)
| Class | Coverage | Notes |
|-------|----------|-------|
| **MailHelper** | 98.2% | Excellent! Only 2 lines uncovered |
| **Conversation** | 96.1% | Nearly complete |
| **Customer** | 93.6% | Good, need create() edges |
| **User** | 97.1% | Excellent |
| **ImapService** | 77.3% | Good foundation, add edges |
| **SmtpService** | 71.3% | Good, add error scenarios |

**Strategy:** Cherry-pick remaining edge cases for 100% coverage.

---

## Strategic Implementation Plan

### Phase 4A: Low-Hanging Fruit (Est: 3-4 hours, +500 tests)

**Goal:** Maximum coverage gain with minimum effort

**Targets:**
1. **All 11 Event Classes** (~55 tests)
   - Simple constructors + broadcast tests
   - Pattern: 5 tests per event × 11 = 55 tests
   
2. **All 8 Mailable Classes** (~40 tests)
   - envelope(), content(), assertions
   - Pattern: 5 tests per mailable × 8 = 40 tests

3. **Simple Model Methods** (~80 tests)
   - Accessors, type checks, simple relationships
   - ActivityLog scopes, SendLog status checks, etc.
   - Pattern: ~7 tests per model × 12 models = 84 tests

4. **All 7 Log Listeners** (~35 tests)
   - Simple event logging
   - Pattern: 5 tests each = 35 tests

**Expected Coverage Gain:** +8-10% overall (103 classes → ~83 classes at 0%)

---

### Phase 4B: Observer Lifecycle Hooks (Est: 4-5 hours, +60 tests)

**Goal:** Cover critical model lifecycle events

**Targets:**
1. ConversationObserver (12 tests) - creating, created, updated, deleting, folder counters
2. UserObserver (10 tests) - created (folder setup), deleting (cleanup)
3. MailboxObserver (8 tests) - created (default folders), deleting
4. AttachmentObserver (8 tests) - deleting (file cleanup) - ENHANCE EXISTING
5. ThreadObserver (8 tests) - created, deleted
6. CustomerObserver (8 tests) - creating, deleting

**Reference:** `tests/Unit/Observers/AttachmentObserverTest.php` for pattern

**Expected Coverage Gain:** +3-4% overall (6 classes fully covered)

---

### Phase 4C: Policy Authorization (Est: 3-4 hours, +50 tests)

**Goal:** Comprehensive authorization coverage

**Targets:**
1. **MailboxPolicy** (15 tests) - All 10 methods with edge cases
2. **ConversationPolicy** (12 tests) - viewCached, update, delete permissions
3. **ThreadPolicy** (8 tests) - edit, delete with complex conditions
4. **UserPolicy** (10 tests) - viewAny, view, create, update, delete
5. **FolderPolicy** (5 tests) - view permission

**Pattern from existing:**
```php
// tests/Unit/Policies/MailboxPolicyTest.php exists
// tests/Unit/Policies/UserPolicyTest.php exists
// ENHANCE with missing edge cases
```

**Expected Coverage Gain:** +4-5% overall (5 critical classes fully covered)

---

### Phase 4D: Console Commands (Est: 6-8 hours, +150 tests)

**Goal:** Cover command execution, validation, error handling

**Prioritized List:**
1. **CheckRequirements** (15 tests) - Extensions, functions, permissions
2. **ModuleUpdate** (15 tests) - Complex update logic, API calls
3. **ModuleInstall** (12 tests) - Symlink creation, file operations
4. **ModuleBuild** (10 tests) - View compilation, file generation
5. **CreateUser** (10 tests) - User creation with validation
6. **FetchEmails** (10 tests) - IMAP fetch integration
7. **Update** (8 tests) - App update process
8. Rest of commands (70 tests) - ConfigureGmail, ClearCache, etc.

**Test Pattern:**
```php
public function test_command_exits_with_success_code(): void
{
    $this->artisan('command:name')
        ->assertExitCode(0);
}

public function test_command_validates_required_arguments(): void
{
    $this->artisan('command:name')
        ->expectsQuestion('Email?', '')
        ->assertExitCode(1);
}

public function test_command_handles_service_exceptions(): void
{
    $this->mock(Service::class, function ($mock) {
        $mock->shouldReceive('method')->andThrow(new \Exception());
    });
    
    $this->artisan('command:name')
        ->assertExitCode(1);
}
```

**Expected Coverage Gain:** +5-7% overall (14 command classes covered)

---

### Phase 4E: Jobs & Listeners (Est: 5-6 hours, +80 tests)

**Goal:** Cover async job execution and event listeners

**Targets:**
1. **SendNotificationToUsers Job** (15 tests) - User filtering, subscription checks
2. **SendAutoReply Job** (12 tests) - Duplicate detection, send log
3. **SendNotificationToUsers Listener** (12 tests) - Event handling
4. **SendAutoReply Listener** (10 tests) - Auto-reply conditions
5. **SendReplyToCustomer Listener** (10 tests) - Customer email dispatch
6. **Other Jobs** (21 tests) - SendAlert, SendEmailReplyError, SendConversationReply

**Enhancement Strategy:**
```php
// tests/Unit/Jobs/SendNotificationToUsersTest.php - ENHANCE EXISTING
// Add missing edge cases:
// - Failed user - handle() with exception → failed()
// - Subscription preference combinations
// - Mailbox access edge cases
// - Batch notification scenarios
```

**Expected Coverage Gain:** +3-4% overall

---

### Phase 4F: Helper & Utility Edge Cases (Est: 3-4 hours, +50 tests)

**Goal:** 100% coverage on critical utilities

**Targets:**
1. **MailHelper** (5 tests) - 2 remaining lines for 100%
2. **Helper** (15 tests) - isInstalled(), queueWorkerRestart(), setGuzzleDefaultOptions()
3. **Email Model** (10 tests) - sanitizeEmail() edge cases
4. **Option Model** (10 tests) - getValue(), setValue(), complex types
5. **WpApi** (5 tests) - getModules() API calls
6. **Module** (5 tests) - isOfficial(), activate/deactivate

**Expected Coverage Gain:** +2% overall (Small codebase, high impact)

---

### Phase 4G: Service Edge Cases (Est: 8-10 hours, +100 tests)

**Goal:** Reach 90%+ coverage on ImapService & SmtpService

**Targets:**
1. **ImapService** (~60 tests for missing 23% coverage)
   - processMessage() error scenarios
   - Malformed email addresses
   - Missing headers/fields
   - Bounce detection edge cases
   - Forward detection edge cases
   - Connection timeouts
   - IMAP folder edge cases

2. **SmtpService** (~40 tests for missing 29% coverage)
   - Connection failures
   - Auth failures
   - Timeout scenarios
   - SSL/TLS variations
   - Invalid configuration

**Test Pattern:**
```php
// ImapService edge cases
public function test_processMessage_handles_empty_from_address(): void
public function test_processMessage_handles_missing_message_id(): void
public function test_processMessage_detects_bounce_by_return_path(): void
public function test_processMessage_detects_bounce_by_subject(): void
public function test_processMessage_handles_large_attachments(): void
public function test_fetchEmails_handles_imap_timeout(): void
public function test_fetchEmails_skips_already_processed_messages(): void
```

**Expected Coverage Gain:** +8-10% overall (Biggest single impact!)

---

### Phase 4H: Controller Unit Tests (Est: 10-12 hours, +250 tests)

**Goal:** Direct controller method testing (not via HTTP)

**Why Needed:** Feature tests hit routes; Unit tests hit methods directly for coverage metrics.

**Prioritized Targets:**
1. **ConversationController** (40 tests) - ajax(), clone(), move(), updateThread()
2. **MailboxController** (30 tests) - ajax(), fetchEmails(), connection tests
3. **SettingsController** (25 tests) - testSmtp(), testImap(), updateAlerts()
4. **SystemController** (20 tests) - ajax(), diagnostics() - 3 tests already skipped
5. **UserController** (20 tests) - ajax(), updateNotifications(), userSetup()
6. **CustomerController** (15 tests) - ajax(), merge()
7. **ModulesController** (12 tests) - enable(), disable(), delete()
8. **Auth Controllers** (60 tests) - Direct method calls
9. **Others** (28 tests) - Dashboard, Profile

**Test Strategy:**
```php
// Unit test pattern - direct controller instantiation
public function test_controller_method_returns_json(): void
{
    $controller = new ConversationController();
    $request = Request::create('/ajax', 'POST', ['action' => 'test']);
    
    $response = $controller->ajax($request);
    
    $this->assertInstanceOf(JsonResponse::class, $response);
}
```

**Expected Coverage Gain:** +12-15% overall (Largest remaining gap!)

---

### Phase 4I: Model Business Logic (Est: 6-8 hours, +120 tests)

**Goal:** Cover complex model methods

**Prioritized Targets:**
1. **Customer** (15 tests) - create() with duplicate handling, setData() merge logic
2. **User** (12 tests) - hasAccessToMailbox(), permission checks
3. **Conversation** (10 tests) - updateFolder(), isActive(), isClosed()
4. **ActivityLog** (10 tests) - Scopes (inLog, causedBy, forSubject)
5. **Email** (8 tests) - sanitizeEmail() comprehensive
6. **Folder** (10 tests) - updateCounters(), type checks
7. **SendLog** (8 tests) - Status checks (isSent, isFailed, wasOpened)
8. **Subscription** (7 tests) - Type checks (isEmail, isBrowser, isMobile)
9. **Simple Models** (40 tests) - Channel, Follower, etc. - 5 tests each × 8

**Expected Coverage Gain:** +6-8% overall

---

### Phase 4J: Request & Middleware (Est: 2-3 hours, +20 tests)

**Goal:** Direct validation and middleware testing

**Targets:**
1. **LoginRequest** (8 tests) - authorize(), rules(), authenticate(), throttle
2. **ProfileUpdateRequest** (5 tests) - rules(), prepareForValidation()
3. **FrameGuard** (3 tests) - handle()
4. **EnsureUserIsAdmin** (4 tests) - handle() with various roles

**Expected Coverage Gain:** +1-2% overall

---

## Summary: Complete Phased Approach

| Phase | Focus Area | Est. Hours | Est. Tests | Coverage Gain |
|-------|-----------|------------|------------|---------------|
| **4A** | Low-Hanging Fruit | 3-4h | 500 | +8-10% |
| **4B** | Observers | 4-5h | 60 | +3-4% |
| **4C** | Policies | 3-4h | 50 | +4-5% |
| **4D** | Console Commands | 6-8h | 150 | +5-7% |
| **4E** | Jobs & Listeners | 5-6h | 80 | +3-4% |
| **4F** | Helpers & Utils | 3-4h | 50 | +2% |
| **4G** | Service Edge Cases | 8-10h | 100 | +8-10% |
| **4H** | Controllers Unit | 10-12h | 250 | +12-15% |
| **4I** | Model Logic | 6-8h | 120 | +6-8% |
| **4J** | Requests & Middleware | 2-3h | 20 | +1-2% |
| **TOTAL** | **Full Coverage** | **50-64h** | **~1,380 tests** | **Target: 95%+** |

### Milestone Targets

- **After Phase 4A-4C:** ~30% → ~50% covered classes (12-15 hours)
- **After Phase 4D-4F:** ~50% → ~70% covered classes (22-30 hours)
- **After Phase 4G-4H:** ~70% → ~85% covered classes (40-52 hours)
- **After Phase 4I-4J:** ~85% → ~95% covered classes (50-64 hours)

---

## Testing Patterns & Best Practices

### 1. Event Testing Pattern
```php
use Illuminate\Support\Facades\Event;

public function test_event_has_required_properties(): void
{
    $conversation = Conversation::factory()->create();
    $event = new UserReplied($conversation, $thread, $user);
    
    $this->assertSame($conversation, $event->conversation);
    $this->assertSame($thread, $event->thread);
    $this->assertSame($user, $event->user);
}

public function test_event_broadcasts_on_private_channel(): void
{
    $conversation = Conversation::factory()->create();
    $event = new ConversationUpdated($conversation, []);
    
    $channels = $event->broadcastOn();
    
    $this->assertCount(1, $channels);
    $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
}
```

### 2. Observer Testing Pattern
```php
use Illuminate\Support\Facades\Event;

public function test_observer_fires_on_model_creation(): void
{
    Event::fake();
    
    $conversation = Conversation::factory()->create();
    
    // Assert observer method was called via side effects
    $this->assertNotNull($conversation->number);
}

public function test_observer_updates_related_models(): void
{
    $conversation = Conversation::factory()->create(['status' => Conversation::STATUS_ACTIVE]);
    $folder = $conversation->folder;
    
    $conversation->status = Conversation::STATUS_CLOSED;
    $conversation->save();
    
    $folder->refresh();
    // Assert folder counters updated
}
```

### 3. Mailable Testing Pattern
```php
public function test_mailable_renders_correctly(): void
{
    $mailable = new AutoReply($conversation, $mailbox, 'Message');
    
    $mailable->assertSeeInHtml('Message');
    $mailable->assertSeeInText('Message');
}

public function test_mailable_has_correct_envelope(): void
{
    $mailable = new UserNotification($thread, $user, $conversation);
    $envelope = $mailable->envelope();
    
    $this->assertEquals('New Reply', $envelope->subject);
    $this->assertEquals('support@example.com', $envelope->from[0]->address);
}
```

### 4. Job Testing Pattern
```php
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

public function test_job_can_be_dispatched(): void
{
    Queue::fake();
    
    SendAutoReply::dispatch($conversation, $mailbox);
    
    Queue::assertPushed(SendAutoReply::class);
}

public function test_job_handles_failures_gracefully(): void
{
    Mail::shouldReceive('send')->andThrow(new \Exception('SMTP error'));
    
    $job = new SendAutoReply($conversation, $mailbox);
    $job->failed(new \Exception('SMTP error'));
    
    // Assert error was logged
    $this->assertDatabaseHas('send_logs', [
        'status' => SendLog::STATUS_FAILED
    ]);
}
```

### 5. Policy Testing Pattern
```php
public function test_policy_allows_action_for_authorized_user(): void
{
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user->id, ['access' => MailboxUser::ACCESS_ADMIN]);
    
    $policy = new MailboxPolicy();
    
    $this->assertTrue($policy->admin($user, $mailbox));
}

public function test_policy_denies_action_for_unauthorized_user(): void
{
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    // No attachment - no access
    
    $policy = new MailboxPolicy();
    
    $this->assertFalse($policy->view($user, $mailbox));
}
```

### 6. Command Testing Pattern
```php
public function test_command_succeeds_with_valid_input(): void
{
    $this->artisan('user:create')
        ->expectsQuestion('Email?', 'user@example.com')
        ->expectsQuestion('First Name?', 'John')
        ->expectsQuestion('Last Name?', 'Doe')
        ->expectsQuestion('Password?', 'password123')
        ->expectsOutput('User created successfully')
        ->assertExitCode(0);
    
    $this->assertDatabaseHas('users', ['email' => 'user@example.com']);
}

public function test_command_validates_email_format(): void
{
    $this->artisan('user:create')
        ->expectsQuestion('Email?', 'invalid-email')
        ->expectsOutput('Invalid email format')
        ->assertExitCode(1);
}
```

### 7. Service Edge Case Pattern
```php
public function test_service_handles_null_input_gracefully(): void
{
    $service = app(ImapService::class);
    
    $result = $service->parseAddresses(null);
    
    $this->assertEmpty($result);
}

public function test_service_handles_malformed_data(): void
{
    $service = app(ImapService::class);
    
    // Should not throw exception
    $result = $service->parseEmail('not-an-email');
    
    $this->assertNull($result);
}

public function test_service_logs_errors_on_failure(): void
{
    Log::shouldReceive('error')->once();
    
    $service = app(SmtpService::class);
    $service->testConnection($mailbox); // With invalid config
}
```

---

## Reference Documentation

### Key Test Files to Study
1. **Batch 1-3 Tests:** `/var/www/html/tests/Unit/Services/ImapServiceTest.php` - 141 tests, excellent patterns
2. **Feature Tests:** `/var/www/html/tests/Feature/` - Full HTTP integration patterns
3. **Email Fixtures:** `/var/www/html/tests/fixtures/` - Mock email data
4. **Test Guide:** `/var/www/html/docs/current-development/TESTING_GUIDE.md` - Critical patterns & gotchas

### Coverage Analysis Tools
```bash
# Generate HTML coverage report
php artisan test --coverage-html=/tmp/coverage-report --min=0

# View specific file coverage
# Open: /tmp/coverage-report/Services/ImapService.php.html
# Red lines = not covered
# Green lines = covered

# Coverage by directory
php artisan test --coverage --min=0
```

### Continuous Validation
```bash
# Run tests with coverage after each batch
php artisan test --compact

# Verify no regressions
php artisan test --stop-on-failure

# Generate updated coverage report
php artisan test --coverage --min=0 --compact | tail -50
```

---

## Success Criteria

### Phase 4 Complete When:
- ✅ 95%+ classes have 80%+ coverage
- ✅ 0 classes with 0% coverage
- ✅ All CRAP >100 methods have tests
- ✅ All tests passing (0 failures, 0 skipped except 3 OOM tests)
- ✅ ~4,300+ total tests (2,997 current + 1,300 new)
- ✅ 10,000+ assertions

### Quality Gates:
- Every new test must pass on first run
- Every new test must follow patterns in TESTING_GUIDE.md
- No transaction pollution (use base test classes)
- No schema assumptions (check TESTING_GUIDE.md)
- Proper mocking of external services (IMAP, SMTP, filesystem)

---

## Recommended Execution Order

### Week 1: Quick Wins (Phases 4A-4C)
**Goal:** 50% class coverage
- Day 1-2: Events & Mailables (Phase 4A)
- Day 3: Simple Model Methods (Phase 4A cont.)
- Day 4: Observers (Phase 4B)
- Day 5: Policies (Phase 4C)

### Week 2: Core Coverage (Phases 4D-4F)
**Goal:** 70% class coverage
- Day 1-2: Console Commands high priority (Phase 4D)
- Day 3: Console Commands remainder (Phase 4D cont.)
- Day 4: Jobs & Listeners (Phase 4E)
- Day 5: Helpers & Utils (Phase 4F)

### Week 3-4: Deep Coverage (Phases 4G-4J)
**Goal:** 95% class coverage
- Week 3: Service edge cases (Phase 4G) + Controller unit tests start (Phase 4H)
- Week 4: Controller unit tests finish (Phase 4H) + Model logic (Phase 4I) + Middleware (Phase 4J)

---

---

## CRITICAL MISSING EDGE CASES - HIGH VALUE ADDITIONS

> **Analysis Date:** 2025-11-17  
> **Based on:** Actual code review of app/ directory, existing test patterns, and production failure scenarios

---

### 🔴 **Priority 1: Rate Limiting & Anti-Loop Protection (CRITICAL - NOT FULLY TESTED)**

**Impact:** CRITICAL - Prevents infinite email loops, spam, brute force attacks  
**Current Gap:** Rate limiting logic exists but edge cases NOT comprehensively tested  
**Est. Tests:** 40 tests

#### SendAutoReply Rate Limiting Edge Cases (15 tests)
```php
// tests/Unit/Listeners/SendAutoReplyRateLimitingTest.php - NEW FILE

public function test_allows_auto_reply_when_under_rate_limit(): void
{
    // Send 9 auto-replies in 180 minutes - should allow 10th
    $customer = Customer::factory()->create();
    $mailbox = Mailbox::factory()->create(['auto_reply_enabled' => true]);
    
    SendLog::factory()->count(9)->create([
        'customer_id' => $customer->id,
        'mail_type' => SendLog::MAIL_TYPE_AUTO_REPLY,
        'created_at' => now()->subMinutes(90), // Within CHECK_PERIOD
    ]);
    
    $conversation = Conversation::factory()->for($mailbox)->for($customer)->create();
    $thread = Thread::factory()->for($conversation)->create();
    
    Event::fake();
    event(new CustomerCreatedConversation($conversation, $customer, $thread));
    
    // Should dispatch job - not rate limited yet
    Queue::assertPushed(SendAutoReplyJob::class);
}

public function test_blocks_auto_reply_at_rate_limit_of_10(): void
{
    $customer = Customer::factory()->create();
    $mailbox = Mailbox::factory()->create(['auto_reply_enabled' => true]);
    
    // Already sent 10 auto-replies in last 180 minutes
    SendLog::factory()->count(10)->create([
        'customer_id' => $customer->id,
        'mail_type' => SendLog::MAIL_TYPE_AUTO_REPLY,
        'created_at' => now()->subMinutes(90),
    ]);
    
    $conversation = Conversation::factory()->for($mailbox)->for($customer)->create();
    $thread = Thread::factory()->for($conversation)->create();
    
    Queue::fake();
    event(new CustomerCreatedConversation($conversation, $customer, $thread));
    
    // Should NOT dispatch - rate limited
    Queue::assertNotPushed(SendAutoReplyJob::class);
}

public function test_rate_limit_resets_after_check_period(): void
{
    $customer = Customer::factory()->create();
    
    // Sent 10 auto-replies 181 minutes ago (outside CHECK_PERIOD of 180)
    SendLog::factory()->count(10)->create([
        'customer_id' => $customer->id,
        'mail_type' => SendLog::MAIL_TYPE_AUTO_REPLY,
        'created_at' => now()->subMinutes(181),
    ]);
    
    // Should allow new auto-reply
    Queue::fake();
    // ... trigger event
    Queue::assertPushed(SendAutoReplyJob::class);
}

public function test_duplicate_subject_detection_after_2_replies(): void
{
    $customer = Customer::factory()->create();
    $mailbox = Mailbox::factory()->create(['auto_reply_enabled' => true]);
    
    // Sent 2 auto-replies already
    SendLog::factory()->count(2)->create([
        'customer_id' => $customer->id,
        'mail_type' => SendLog::MAIL_TYPE_AUTO_REPLY,
        'created_at' => now()->subMinutes(90),
    ]);
    
    // Create previous conversations with SAME subject
    Conversation::factory()->count(2)->create([
        'customer_id' => $customer->id,
        'mailbox_id' => $mailbox->id,
        'subject' => 'Same Subject',
        'created_at' => now()->subMinutes(60),
    ]);
    
    // New conversation with duplicate subject
    $conversation = Conversation::factory()->create([
        'customer_id' => $customer->id,
        'mailbox_id' => $mailbox->id,
        'subject' => 'Same Subject',
    ]);
    
    Queue::fake();
    event(new CustomerCreatedConversation($conversation, $customer, $thread));
    
    // Should NOT send - duplicate subject protection
    Queue::assertNotPushed(SendAutoReplyJob::class);
}

public function test_duplicate_subject_check_ignores_different_subjects(): void
{
    // Same setup but DIFFERENT subjects - should allow
}

public function test_rate_limit_is_per_customer_not_global(): void
{
    $customer1 = Customer::factory()->create();
    $customer2 = Customer::factory()->create();
    
    // Customer 1 hit rate limit
    SendLog::factory()->count(10)->create([
        'customer_id' => $customer1->id,
        'mail_type' => SendLog::MAIL_TYPE_AUTO_REPLY,
        'created_at' => now()->subMinutes(90),
    ]);
    
    // Customer 2 should still be allowed
    Queue::fake();
    // ... trigger for customer2
    Queue::assertPushed(SendAutoReplyJob::class);
}

public function test_imported_conversations_bypass_all_checks(): void
{
    // Should exit early, not check rate limits at all
}

public function test_auto_responder_detection_bypasses_rate_limit_check(): void
{
    // isAutoResponder() = true should exit before rate limit check
}

public function test_bounce_detection_bypasses_rate_limit_check(): void
{
    // isBounce() = true should exit before rate limit check
}

public function test_spam_conversations_bypass_rate_limit_check(): void
{
    // status == STATUS_SPAM should exit before rate limit check
}

public function test_rate_limit_logs_warning_at_threshold(): void
{
    Log::shouldReceive('warning')
        ->once()
        ->with('Auto-reply rate limit exceeded (10)', Mockery::any());
    
    // Trigger with 10+ auto-replies sent
}

public function test_disabled_auto_reply_mailbox_exits_early(): void
{
    $mailbox = Mailbox::factory()->create(['auto_reply_enabled' => false]);
    
    Queue::fake();
    // ... should never reach rate limit check
}

public function test_rate_limit_at_boundary_8_and_9_replies(): void
{
    // Test behavior at 8 replies (no duplicate check)
    // Test behavior at 9 replies (triggers duplicate check path)
}

public function test_rate_limit_with_mixed_mail_types(): void
{
    // Sent 5 auto-replies, 5 notifications
    // Only auto-replies (mail_type=3) should count toward limit
}

public function test_concurrent_auto_reply_attempts_dont_exceed_limit(): void
{
    // Race condition: Multiple simultaneous customer emails
    // Ensure rate limit check is atomic
}
```

---

#### Login Rate Limiting Edge Cases (10 tests)
```php
// tests/Unit/Requests/Auth/LoginRequestRateLimitingTest.php - NEW FILE

public function test_rate_limit_increments_on_failed_login(): void
{
    $request = LoginRequest::create('/login', 'POST', [
        'email' => 'user@example.com',
        'password' => 'wrong-password',
    ]);
    
    try {
        $request->authenticate();
    } catch (ValidationException $e) {
        // Expected
    }
    
    // Check RateLimiter was hit
    $this->assertEquals(1, RateLimiter::attempts($request->throttleKey()));
}

public function test_rate_limit_clears_on_successful_login(): void
{
    // Pre-populate with 3 failed attempts
    $user = User::factory()->create(['password' => Hash::make('correct-password')]);
    $key = Str::lower($user->email) . '|127.0.0.1';
    
    RateLimiter::hit($key);
    RateLimiter::hit($key);
    RateLimiter::hit($key);
    
    $this->assertEquals(3, RateLimiter::attempts($key));
    
    // Successful login
    $request = LoginRequest::create('/login', 'POST', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);
    
    $request->authenticate();
    
    // Rate limiter should be cleared
    $this->assertEquals(0, RateLimiter::attempts($key));
}

public function test_rate_limit_triggers_at_5_failed_attempts(): void
{
    $request = LoginRequest::create('/login', 'POST', [
        'email' => 'test@example.com',
        'password' => 'wrong',
    ]);
    
    // Simulate 5 failed attempts
    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit($request->throttleKey());
    }
    
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('auth.throttle');
    
    $request->ensureIsNotRateLimited();
}

public function test_rate_limit_key_includes_ip_address(): void
{
    $request1 = LoginRequest::create('/login', 'POST', [
        'email' => 'test@example.com',
    ], [], [], ['REMOTE_ADDR' => '192.168.1.1']);
    
    $request2 = LoginRequest::create('/login', 'POST', [
        'email' => 'test@example.com',
    ], [], [], ['REMOTE_ADDR' => '192.168.1.2']);
    
    // Different IPs should have different keys
    $this->assertNotEquals($request1->throttleKey(), $request2->throttleKey());
}

public function test_rate_limit_key_is_case_insensitive_for_email(): void
{
    $request1 = LoginRequest::create('/login', 'POST', ['email' => 'Test@Example.COM']);
    $request2 = LoginRequest::create('/login', 'POST', ['email' => 'test@example.com']);
    
    $this->assertEquals($request1->throttleKey(), $request2->throttleKey());
}

public function test_lockout_event_fires_when_rate_limited(): void
{
    Event::fake([Lockout::class]);
    
    $request = LoginRequest::create('/login', 'POST', ['email' => 'test@example.com']);
    
    // Hit rate limit
    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit($request->throttleKey());
    }
    
    try {
        $request->ensureIsNotRateLimited();
    } catch (ValidationException $e) {
        // Expected
    }
    
    Event::assertDispatched(Lockout::class);
}

public function test_rate_limit_message_includes_seconds_until_retry(): void
{
    $request = LoginRequest::create('/login', 'POST', ['email' => 'test@example.com']);
    
    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit($request->throttleKey(), 60); // 60 second decay
    }
    
    try {
        $request->ensureIsNotRateLimited();
    } catch (ValidationException $e) {
        $errors = $e->errors();
        $this->assertArrayHasKey('email', $errors);
        $this->assertStringContainsString('seconds', $errors['email'][0]);
    }
}

public function test_rate_limit_at_4_attempts_still_allows_login(): void
{
    // 4 attempts - should NOT be rate limited yet
    $request = LoginRequest::create('/login', 'POST', ['email' => 'test@example.com']);
    
    for ($i = 0; $i < 4; $i++) {
        RateLimiter::hit($request->throttleKey());
    }
    
    // Should not throw exception
    $request->ensureIsNotRateLimited();
    $this->assertTrue(true);
}

public function test_rate_limit_with_unicode_email(): void
{
    $request = LoginRequest::create('/login', 'POST', ['email' => 'tëst@example.com']);
    
    $key = $request->throttleKey();
    
    // Should use transliterate to handle unicode
    $this->assertIsString($key);
    $this->assertStringContainsString('test@example.com', $key);
}

public function test_remember_me_flag_preserved_through_rate_limit(): void
{
    // Test that 'remember' boolean is properly passed through authenticate()
}
```

---

#### API/Controller Rate Limiting (15 tests)
```php
// tests/Feature/RateLimitingTest.php - NEW FILE

public function test_conversation_creation_respects_user_rate_limits(): void
{
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    $customer = Customer::factory()->create();
    
    // Create 100 conversations in rapid succession
    for ($i = 0; $i < 100; $i++) {
        $response = $this->actingAs($user)->post(route('conversations.create', $mailbox), [
            'customer_id' => $customer->id,
            'subject' => "Test $i",
            'body' => 'Test body',
        ]);
        
        if ($i < 60) {
            $response->assertStatus(200); // Should succeed initially
        }
        
        // After certain threshold, might be rate limited
        if ($response->status() === 429) {
            $this->assertGreaterThan(50, $i, 'Rate limit kicked in');
            return;
        }
    }
}

public function test_ajax_endpoints_have_rate_limiting(): void
{
    $user = User::factory()->create();
    
    // Rapid fire ajax requests
    for ($i = 0; $i < 200; $i++) {
        $response = $this->actingAs($user)->post(route('conversation.ajax'), [
            'action' => 'load_more',
        ]);
        
        if ($response->status() === 429) {
            // Rate limited - good!
            $this->assertTrue(true);
            return;
        }
    }
    
    // If we got here, no rate limiting exists
    $this->markTestIncomplete('No rate limiting detected on AJAX endpoints');
}

public function test_api_returns_429_with_retry_after_header(): void
{
    // Hit rate limit, check response has Retry-After header
}

public function test_rate_limit_per_user_not_global(): void
{
    // User A hitting limit doesn't affect User B
}

public function test_guest_users_have_stricter_rate_limits(): void
{
    // Unauthenticated requests should be rate limited more aggressively
}

// Add 10 more rate limiting tests for different endpoints
```

---

### 🔴 **Priority 2: Data Integrity & Boundary Conditions (15 tests)**

#### Database Constraint Violations
```php
// tests/Unit/EdgeCases/DatabaseConstraintsTest.php - NEW FILE

public function test_customer_email_uniqueness_per_mailbox(): void
{
    // Customer can have same email in different mailboxes?
    // Or strictly unique globally?
}

public function test_conversation_number_auto_increment_handles_gaps(): void
{
    // Delete conversation, next number should still increment
}

public function test_orphaned_threads_cannot_exist(): void
{
    // Foreign key constraint should prevent thread with invalid conversation_id
    $this->expectException(QueryException::class);
    
    Thread::factory()->create(['conversation_id' => 99999]);
}

public function test_user_cannot_be_deleted_with_active_conversations(): void
{
    // Or test cascade behavior is correct
}

public function test_mailbox_deletion_cascades_to_conversations(): void
{
    $mailbox = Mailbox::factory()->has(Conversation::factory()->count(3))->create();
    $conversationIds = $mailbox->conversations->pluck('id');
    
    $mailbox->delete();
    
    foreach ($conversationIds as $id) {
        $this->assertDatabaseMissing('conversations', ['id' => $id]);
    }
}

public function test_attachment_file_deletion_on_thread_deletion(): void
{
    // Ensure files are cleaned up from disk
    Storage::fake('attachments');
    
    $thread = Thread::factory()->create();
    $attachment = Attachment::factory()->for($thread)->create();
    
    Storage::disk('attachments')->put($attachment->file_path, 'test content');
    
    $thread->delete();
    
    // File should be deleted
    Storage::disk('attachments')->assertMissing($attachment->file_path);
}

public function test_folder_counters_stay_consistent_on_bulk_operations(): void
{
    // Update 100 conversations at once, folder counters should be accurate
}

public function test_concurrent_conversation_updates_dont_corrupt_data(): void
{
    // Race condition test: Two users update same conversation
    DB::transaction(function () {
        // Simulate concurrent updates
    });
}

public function test_json_column_validation_rejects_invalid_json(): void
{
    $this->expectException(QueryException::class);
    
    Thread::factory()->create(['meta' => 'not valid json']);
}

public function test_timestamps_cannot_be_null_on_required_models(): void
{
    // created_at, updated_at should always be populated
}

public function test_soft_delete_preserves_relationships(): void
{
    // Soft deleted user, their conversations should still be accessible
}

public function test_maximum_column_length_enforced(): void
{
    $this->expectException(QueryException::class);
    
    User::factory()->create(['first_name' => str_repeat('a', 256)]); // Over limit
}

public function test_email_format_validation_at_database_level(): void
{
    // If DB has CHECK constraint, test it
}

public function test_enum_values_restricted_to_defined_set(): void
{
    $this->expectException(QueryException::class);
    
    Conversation::factory()->create(['status' => 99]); // Invalid status
}

public function test_indexes_improve_query_performance(): void
{
    // Create 10,000 conversations
    // Query by status should use index
    DB::enableQueryLog();
    
    Conversation::where('status', Conversation::STATUS_ACTIVE)->count();
    
    $queries = DB::getQueryLog();
    $this->assertStringContainsString('USING INDEX', $queries[0]['query']);
}
```

---

### 🔴 **Priority 3: Security Edge Cases (12 tests)**

```php
// tests/Feature/SecurityEdgeCasesTest.php - NEW FILE

public function test_path_traversal_attack_in_attachment_download(): void
{
    $user = User::factory()->create();
    
    // Attempt to download ../../etc/passwd
    $response = $this->actingAs($user)->get(route('attachment.download', [
        'id' => '../../../etc/passwd',
    ]));
    
    $response->assertStatus(404); // Or 403
}

public function test_file_upload_rejects_executable_extensions(): void
{
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)->post(route('attachment.upload'), [
        'file' => UploadedFile::fake()->create('virus.exe', 100),
    ]);
    
    $response->assertStatus(422);
    $response->assertJsonFragment(['error' => 'Invalid file type']);
}

public function test_file_upload_enforces_size_limits(): void
{
    $user = User::factory()->create();
    
    // Attempt to upload 100MB file
    $response = $this->actingAs($user)->post(route('attachment.upload'), [
        'file' => UploadedFile::fake()->create('large.pdf', 100000), // 100MB
    ]);
    
    $response->assertStatus(422);
}

public function test_html_content_sanitized_in_email_replies(): void
{
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();
    
    $response = $this->actingAs($user)->post(route('conversation.reply', $conversation), [
        'body' => '<script>alert("xss")</script><p>Safe content</p>',
    ]);
    
    $thread = Thread::latest()->first();
    
    // Script tags should be stripped, safe HTML preserved
    $this->assertStringNotContainsString('<script>', $thread->body);
    $this->assertStringContainsString('<p>Safe content</p>', $thread->body);
}

public function test_authorization_checked_before_expensive_operations(): void
{
    $user = User::factory()->create(); // Regular user
    $mailbox = Mailbox::factory()->create();
    
    // Try to delete mailbox without permission
    $response = $this->actingAs($user)->delete(route('mailbox.delete', $mailbox));
    
    $response->assertStatus(403);
    
    // Mailbox should still exist
    $this->assertDatabaseHas('mailboxes', ['id' => $mailbox->id]);
}

public function test_email_header_injection_prevented(): void
{
    $user = User::factory()->create();
    
    // Attempt header injection via CC field
    $response = $this->actingAs($user)->post(route('conversation.reply'), [
        'cc' => "victim@example.com\nBcc: attacker@evil.com",
    ]);
    
    // Should reject or sanitize
    $response->assertStatus(422);
}

public function test_session_fixation_prevented_on_login(): void
{
    $user = User::factory()->create();
    
    $oldSessionId = session()->getId();
    
    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);
    
    $newSessionId = session()->getId();
    
    // Session should regenerate on login
    $this->assertNotEquals($oldSessionId, $newSessionId);
}

public function test_password_reset_token_expires(): void
{
    $user = User::factory()->create();
    
    // Create password reset token
    $token = Password::createToken($user);
    
    // Travel 2 hours into future (tokens expire after 1 hour)
    $this->travel(2)->hours();
    
    $response = $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);
    
    $response->assertSessionHasErrors(['email']); // Invalid/expired token
}

public function test_csrf_token_required_for_state_changing_requests(): void
{
    $user = User::factory()->create();
    
    // Attempt POST without CSRF token
    $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
        ->post(route('conversation.create'), []);
    
    // Re-enable middleware
    $this->withMiddleware();
    
    $response = $this->actingAs($user)->post(route('conversation.create'), []);
    
    $response->assertStatus(419); // CSRF token mismatch
}

public function test_old_passwords_cannot_be_reused(): void
{
    // If password history is enforced
    $user = User::factory()->create(['password' => Hash::make('old-password')]);
    
    // Change password
    $user->update(['password' => Hash::make('new-password')]);
    
    // Try to change back to old password
    $response = $this->actingAs($user)->post(route('password.update'), [
        'current_password' => 'new-password',
        'password' => 'old-password',
        'password_confirmation' => 'old-password',
    ]);
    
    $response->assertSessionHasErrors(['password' => 'Cannot reuse recent passwords']);
}

public function test_api_authentication_rejects_expired_tokens(): void
{
    // If using Sanctum/Passport
}

public function test_user_enumeration_prevented_on_password_reset(): void
{
    // Should return same message whether email exists or not
    $response1 = $this->post(route('password.email'), ['email' => 'exists@example.com']);
    $response2 = $this->post(route('password.email'), ['email' => 'nonexistent@example.com']);
    
    // Both should return success message (don't leak user existence)
    $response1->assertStatus(200);
    $response2->assertStatus(200);
}
```

---

### 🔴 **Priority 4: Internationalization & Character Encoding (8 tests)**

```php
// tests/Unit/EdgeCases/InternationalizationTest.php - NEW FILE

public function test_handles_right_to_left_languages(): void
{
    $conversation = Conversation::factory()->create([
        'subject' => 'مرحبا بك', // Arabic
    ]);
    
    $this->assertEquals('مرحبا بك', $conversation->subject);
}

public function test_handles_emojis_in_conversation_subject(): void
{
    $conversation = Conversation::factory()->create([
        'subject' => '😀 Happy Customer',
    ]);
    
    $this->assertStringContainsString('😀', $conversation->subject);
}

public function test_handles_japanese_characters(): void
{
    $customer = Customer::factory()->create([
        'first_name' => '太郎',
        'last_name' => '山田',
    ]);
    
    $this->assertEquals('太郎', $customer->first_name);
}

public function test_handles_chinese_characters(): void
{
    // 简体中文 and 繁體中文
}

public function test_handles_cyrillic_characters(): void
{
    // Russian: Привет
}

public function test_email_address_with_plus_sign_routing(): void
{
    // user+tag@example.com should route to user@example.com
}

public function test_international_domain_names_idn(): void
{
    // münchen@example.de
    $customer = Customer::factory()->create([
        'email' => 'münchen@example.de',
    ]);
    
    // Should be stored in punycode
}

public function test_mixed_encoding_email_body_parsed_correctly(): void
{
    // Email with mixed UTF-8 and ISO-8859-1 content
}
```

---

### 🔴 **Priority 5: Performance & Scalability Edge Cases (10 tests)**

```php
// tests/Feature/PerformanceEdgeCasesTest.php - NEW FILE

public function test_n_plus_one_query_prevention_in_conversation_list(): void
{
    Mailbox::factory()->has(
        Conversation::factory()->count(100)->has(Thread::factory()->count(5))
    )->create();
    
    DB::enableQueryLog();
    
    $conversations = Conversation::with(['mailbox', 'customer', 'threads'])->get();
    
    $queries = DB::getQueryLog();
    
    // Should be 4 queries total (conversations, mailboxes, customers, threads)
    // NOT 1 + 100 + 500
    $this->assertLessThan(10, count($queries));
}

public function test_pagination_with_100000_conversations(): void
{
    // Seed database with large dataset
    // Verify pagination performs in < 1 second
}

public function test_search_query_uses_full_text_indexes(): void
{
    // Search for "urgent issue" across 50,000 conversations
    $start = microtime(true);
    
    Conversation::where('subject', 'like', '%urgent%')->get();
    
    $duration = microtime(true) - $start;
    
    $this->assertLessThan(1.0, $duration, 'Query took too long');
}

public function test_attachment_chunked_upload_for_large_files(): void
{
    // Upload 50MB file in chunks
}

public function test_memory_limit_not_exceeded_on_bulk_operations(): void
{
    $initialMemory = memory_get_usage();
    
    // Process 10,000 emails
    $mailbox = Mailbox::factory()->create();
    // ... process emails
    
    $finalMemory = memory_get_usage();
    $memoryIncrease = $finalMemory - $initialMemory;
    
    // Should not consume > 128MB
    $this->assertLessThan(128 * 1024 * 1024, $memoryIncrease);
}

public function test_conversation_cache_invalidation_on_update(): void
{
    // Cached conversation should refresh when updated
}

public function test_queue_worker_processes_jobs_without_memory_leak(): void
{
    // Run 1000 jobs, memory should stay stable
}

public function test_email_fetch_with_10000_messages_in_mailbox(): void
{
    // Should handle large mailboxes without timeout
}

public function test_folder_counter_update_query_optimized(): void
{
    // Updating 100 conversations should trigger 1 counter update, not 100
}

public function test_conversation_list_lazy_loads_threads(): void
{
    // Don't load all threads for every conversation in list view
}
```

---

## Conclusion

**Current State:** 85.9% coverage with 103 classes at 0%  
**Target State:** 95%+ coverage with 0 classes below 80%  
**Estimated Effort:** 50-64 hours, ~1,380 new tests

**REFINED STRATEGY:**
1. **Phase 4A-4C** (Quick Wins): Events, Observers, Policies - 12-15 hours
2. **NEW: Critical Edge Cases** (This Section): Rate limiting, security, data integrity - 8-10 hours  
3. **Phase 4D-4J** (Remaining Coverage): Commands, Services, Controllers, Models - 40-50 hours

**CRITICAL ADDITIONS (85 new tests):**
- ✅ 40 Rate Limiting & Anti-Loop tests (SendAutoReply, LoginRequest, API endpoints)
- ✅ 15 Database Constraint & Integrity tests (cascade, indexes, foreign keys)
- ✅ 12 Security Edge Cases (XSS, path traversal, CSRF, session fixation)
- ✅ 8 Internationalization tests (Unicode, emojis, RTL, IDN)
- ✅ 10 Performance & Scalability tests (N+1 queries, memory, large datasets)

**Why These Matter:**
- **Rate Limiting:** Prevents infinite loops (email bombing), brute force attacks, system abuse
- **Data Integrity:** Prevents orphaned records, data corruption, inconsistent state
- **Security:** Protects against common web vulnerabilities (OWASP Top 10)
- **I18n:** Ensures global usability with non-Latin scripts
- **Performance:** Ensures system scales to production workloads

**Key Strategy:** Start with highest-impact edge cases (rate limiting, security) before expanding coverage breadth.

**Critical Success Factor:** Follow existing test patterns, use proper mocking, avoid database/transaction issues documented in TESTING_GUIDE.md.

This refined plan achieves comprehensive coverage PLUS critical edge case protection for production reliability.

---

## 🔥 PHASE 4K: ASYNC OPERATIONS & ERROR RECOVERY (75 Critical Tests)

**Est. Time:** 8-10 hours | **Priority:** EXECUTE AFTER Phase 4C, BEFORE Phase 4D

### Why Phase 4K Matters

These tests target **production failure scenarios** that coverage metrics don't reveal:
- Queue jobs that retry but don't recover properly
- IMAP connections that timeout mid-fetch
- Internal email loops between mailboxes
- Command errors that fail silently in cron
- Observer cascades that cause data corruption

**ROI:** Higher than controller unit tests because these catch bugs that cause incidents.

---

### 🔴 Queue Job Failure & Retry (25 tests)

```bash
# Create test file
touch tests/Unit/Jobs/JobFailureRecoveryTest.php
```

**Critical Gaps Identified:**
1. Job retry backoff timing NOT verified
2. failed() method error logging NOT tested
3. SendLog creation on job failure NOT confirmed
4. Database deadlock recovery NOT tested
5. Partial batch failure handling MISSING

**Test Examples:**
- `test_job_retries_with_exponential_backoff` - [10, 30, 60, 120] seconds
- `test_job_fails_permanently_after_max_attempts` - 3 tries then fail
- `test_job_creates_send_log_on_both_success_and_failure`
- `test_job_handles_database_deadlock_with_release` - NOT permanent failure
- `test_send_alert_continues_on_single_recipient_failure` - 4/5 succeed = success

---

### 🔴 IMAP/SMTP Connection Edge Cases (20 tests)

```bash
# Create test file
touch tests/Unit/Services/ImapConnectionEdgeCasesTest.php
```

**Critical Untested Code Paths:**
- Lines 96-115 in `ImapService.php`: Charset retry logic - ZERO tests!
- Lines 57-67: Array vs string folder handling - NOT tested
- Line 132-136: Chronological sorting - NOT verified
- Error states: Connection lost, auth failure, SSL errors

**Test Examples:**
- `test_imap_retries_without_charset_on_error` - **CRITICAL PATH**
- `test_imap_folders_handles_both_array_and_string_format`
- `test_imap_sorts_messages_chronologically_before_processing`
- `test_imap_leaves_message_unseen_on_processing_error`
- `test_smtp_handles_provider_rate_limiting` - 421 errors

---

### 🔴 SendAutoReply Internal Email Check (5 tests)

```bash
# Create test file
touch tests/Unit/Listeners/SendAutoReplyInternalEmailTest.php
```

**CRITICAL UNTESTED CODE:** Lines 119-130 in `SendAutoReply.php`

```php
// Do not send autoreplies to own mailboxes
if ($conversation->customer_email) {
    $isInternalEmail = Mailbox::where('email', $conversation->customer_email)->exists();
    if ($isInternalEmail) {
        Log::debug('Skipping auto-reply to internal mailbox', [...]);
        return;
    }
}
```

**Current Coverage:** 0% - This prevents infinite loops!

**Test Examples:**
- `test_skips_auto_reply_when_customer_email_matches_mailbox`
- `test_sends_auto_reply_to_external_customer`
- `test_internal_check_case_insensitive`
- `test_internal_check_handles_null_customer_email`
- `test_internal_check_with_subdomain_variations`

---

### 🔴 Console Command Error Recovery (15 tests)

```bash
# Create test file
touch tests/Unit/Console/Commands/CommandErrorHandlingTest.php
```

**Gap:** Commands have try/catch BUT error paths NOT tested

**Test Examples:**
- `test_module_install_handles_network_failure` - HTTP 500
- `test_fetch_emails_handles_connection_timeout` - IMAP timeout
- `test_create_user_validates_email_format` - Invalid input
- `test_clear_cache_handles_redis_connection_failure`
- `test_command_handles_database_gone_away` - Connection lost

---

### 🔴 Observer Cascade & Side Effects (10 tests)

```bash
# Create test file  
touch tests/Unit/Observers/ObserverCascadeTest.php
```

**Gap:** Observers trigger observers - cascades NOT verified

**Test Examples:**
- `test_conversation_deletion_cascades_to_threads_and_attachments`
- `test_mailbox_deletion_updates_all_folder_counters`
- `test_user_creation_creates_default_inbox_sent_trash_folders`
- `test_observer_doesnt_create_infinite_update_loop`
- `test_attachment_observer_deletes_file_from_storage`

---

## Phase 4K Completion Criteria

✅ All 75 tests passing  
✅ Zero skipped/incomplete tests  
✅ All error paths have explicit tests  
✅ All try/catch blocks exercised  
✅ Queue retry logic validated with timing  
✅ IMAP charset retry path covered  
✅ Internal email check has 100% coverage  
✅ Command error scenarios logged and handled  
✅ Observer cascades verified for data integrity

**After Phase 4K:** Proceed to Phase 4D (Commands) with confidence that async/error scenarios are protected.

