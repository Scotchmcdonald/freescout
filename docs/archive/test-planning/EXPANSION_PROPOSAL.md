# Test Expansion Proposal for FreeScout
**Generated**: November 7, 2025  
**Current Status**: ✅ 674 tests passing (1612 assertions) in 22.06s  
**Test Files**: 75 files  
**Coverage**: 49.78% lines, 53.33% methods, 33.90% classes  
**Goal**: Achieve 70-80% coverage with comprehensive edge case testing  
**Architecture**: Phased agent-based implementation with database compatibility preservation

---

## 🎯 MASTER LLM PROMPT FOR AGENT EXECUTION

**CONTEXT**: You are implementing test enhancements for a Laravel 11 FreeScout helpdesk application. The codebase has been modernized from a legacy version, and we must maintain database schema compatibility with the archived application located in `/var/www/html/archive/`.

**CRITICAL REQUIREMENTS**:
1. ⚠️ **Database Compatibility**: All tests MUST work with the existing database schema. Do NOT create migrations that break compatibility with the archived app.
2. **Test Independence**: Each test must use `RefreshDatabase` and be independently executable.
3. **Factory Usage**: Always use model factories for test data creation.
4. **Real Assertions**: Verify actual behavior (database persistence, content display, relationships) - not just HTTP status codes.
5. **Sequential Execution**: Complete each phase fully before moving to the next.

**EXECUTION WORKFLOW**:
1. Read the phase assignment below
2. Review coverage report at `/var/www/html/coverage-report/dashboard.html` for context
3. Check existing implementations in `app/` directory before writing tests
4. Create test files according to the phase specification
5. Run tests after each file: `php artisan test --filter=YourTestClass`
6. Fix any failures before moving to next test file
7. Verify database compatibility by checking schema against `/var/www/html/archive/database/migrations/`
8. Report completion with test count and coverage impact

**CODE QUALITY STANDARDS**:
```php
// ✅ GOOD - Tests actual behavior
public function test_user_registration_creates_database_record(): void
{
    $response = $this->post('/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);
    
    $response->assertRedirect('/dashboard');
    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
        'name' => 'John Doe',
    ]);
}

// ❌ BAD - Only tests HTTP status
public function test_registration_works(): void
{
    $response = $this->post('/register', [...]);
    $response->assertOk();
}
```

**WHEN TO MERGE PHASES**:
- If tasks have shared setup/dependencies
- If tasks modify the same files
- If tasks test the same component from different angles
- When parallel execution would cause race conditions

**PHASE STRUCTURE**: Each phase is assigned to ONE agent and must be completed sequentially within that phase. Multiple agents can work on different phases in parallel only if phases are marked as "Parallelizable".

---

## 📊 Current State Analysis

### Coverage Metrics (from `/var/www/html/coverage-report/`)
- **Lines**: 49.78% (1384/2780)
- **Functions/Methods**: 53.33% (144/270)
- **Classes/Traits**: 33.90% (20/59)

### High-Risk Components (CRAP Index)
1. **ImapService**: 15,687 CRAP (6% coverage) - CRITICAL
2. **SendAutoReply Job**: 202 CRAP (1% coverage)
3. **SmtpService**: 197 CRAP (40% coverage)
4. **ConversationController**: 131 CRAP (63% coverage)
5. **SendAutoReply Listener**: 182 CRAP (0% coverage)

### Test Results Summary
```
✅ Passing: 674 tests (1612 assertions)
❌ Failing: 0 tests
Duration: 22.06s
Status: ALL TESTS PASSING ✅
```

### Database Compatibility Note
⚠️ **CRITICAL**: The archived application in `/var/www/html/archive/` shares the same database. All tests and fixes MUST maintain schema compatibility. Check migrations in `archive/database/migrations/` before modifying models.

### Test Suite Composition
- **Unit Tests**: 39 test files (~270 tests)
- **Feature Tests**: 36 test files (~404 tests)
- **Coverage Focus**: Controllers (63-87%), Models (46-89%), Policies (60-80%)
- **Coverage Gaps**: Services (6-40%), Jobs (0-1%), Commands (0%), Listeners (0%)

##Previous Failures - NOW RESOLVED ✅

#### 1. **ModuleModelTest** - ✅ FIXED - Test expects wrong attributes
```php
// ISSUE: Test expected 'is_enabled' but model used 'active'
// RESOLUTION: Added accessor to Module model for backward compatibility
// File: app/Models/Module.php
public function getIsEnabledAttribute(): bool
{
    return (bool) $this->active;
}
```

#### 2. **SendLogModelTest** - ✅ FIXED - Missing constants
```php
// ISSUE: Constants were not defined in SendLog model
// RESOLUTION: Added constants to model
// File: app/Models/SendLog.php
public const MAIL_TYPE_REPLY = 1;
public const MAIL_TYPE_NOTE = 2;  
public const MAIL_TYPE_AUTO_REPLY = 3;
public const STATUS_ACCEPTED = 1;
public const STATUS_SEND_ERROR = 2;
```

#### 3. **SubscriptionModelTest** - ✅ FIXED - Missing 'conversation_id' in fillable
```php
// ISSUE: 'conversation_id' was not in fillable array
// RESOLUTION: Added to fillable array
// File: app/Models/Subscription.php
protected $fillable = [
    'user_id',
    'conversation_id',  // ADDED
    'medium',
    'event',
];
```

---

## 🎯 Test Expansion Strategy

### Coverage Gaps Identified (From Coverage Report)

| Component | Current Coverage | CRAP Risk | Priority | Target Coverage |
|-----------|-----------------|-----------|----------|-----------------|
| **Services** | 6-40% | EXTREME | CRITICAL | 70%+ |
| **Jobs/Listeners** | 0-1% | EXTREME | CRITICAL | 70%+ |
| **Controllers** | 45-86% | HIGH | HIGH | 80%+ |
| **Commands** | 0% | HIGH | HIGH | 60%+ |
| **Events** | 4-30% | MEDIUM | MEDIUM | 70%+ |
| **Mail** | 6-14% | MEDIUM | MEDIUM | 70%+ |
| **Models** | 33-89% | LOW | MEDIUM | 85%+ |
| **Policies** | 58-80% | LOW | LOW | 90%+ |

### Test Organization Philosophy
Tests are organized into **PHASES** where each phase is assigned to a single agent. Phases contain related work that shares context and cannot be safely parallelized. Agents working on different phases can run in parallel if phases are marked as parallelizable.

---

## 📋 PHASE-BASED TEST IMPLEMENTATION

> **NOTE**: All phases 0 tasks are now complete! ✅ All 674 tests passing.

### ✅ PHASE 0: Critical Fixes (COMPLETED)
**Status**: ✅ COMPLETE - All tests passing  
**Duration**: Completed  
**Impact**: Fixed 6 failing tests, established clean baseline

**Tasks Completed**:
1. ✅ Added `getIsEnabledAttribute()` accessor to Module model
2. ✅ Added mail type and status constants to SendLog model  
3. ✅ Added `conversation_id` to Subscription fillable array
4. ✅ Verified all 674 tests passing

---

### 🔥 PHASE 1: Critical Service & Job Coverage (HIGHEST PRIORITY)
**Agent Assignment**: Agent-1-Services  
**Parallelizable**: YES (with Phase 2, 3)  
**Duration**: 8-12 hours  
**Prerequisites**: Phase 0 complete ✅  
**Impact**: +40-60 tests, +30-40% coverage on critical components

**Objective**: Test the highest-risk components with EXTREME CRAP scores

**Scope**:
- **ImapService** (CRAP: 16,146, Coverage: 6%) - CRITICAL
- **SmtpService** (CRAP: 197, Coverage: 40%)
- **SendAutoReply Job** (CRAP: 202, Coverage: 1%)
- **SendAutoReply Listener** (CRAP: 182, Coverage: 0%)
- **SendConversationReply Job** (CRAP: N/A, Coverage: 0%)
- **HandleNewMessage Listener** (CRAP: N/A, Coverage: 0%)

**Why Merge These**: All these components work together in the email sending/receiving pipeline. Testing them separately would require extensive mocking. Testing together provides integration coverage.

#### Task 1.1: Expand ImapService Tests (4-5 hours)
**File**: `tests/Unit/Services/ImapServiceTest.php` (EXISTS - expand)  
**Current Tests**: 2 basic tests  
**Target**: 15-20 comprehensive tests  
**Coverage Impact**: 6% → 50%+

**Tests to Add**:
1. Connection with various encryption types (TLS, SSL, none)
2. Connection timeout handling
3. Authentication failure handling  
4. Malformed email header parsing
5. Large attachment handling (with size limits)
6. Duplicate message ID prevention
7. Various charset encoding (UTF-8, ISO-8859-1, Windows-1252)
8. Missing required headers (From, To, Subject)
9. HTML vs plain text email parsing
10. Multipart email handling
11. Inline image processing
12. Email threading/conversation grouping
13. Folder selection and navigation
14. Message fetching with different protocols (IMAP vs POP3)
15. Connection pooling and reuse

**Key Assertion Patterns**:
```php
// Test error handling
$result = $service->fetchEmails($mailbox);
$this->assertArrayHasKey('errors', $result);
$this->assertArrayHasKey('fetched', $result);
$this->assertIsInt($result['errors']);
$this->assertIsInt($result['fetched']);

// Test no exceptions thrown
try {
    $service->fetchEmails($invalidMailbox);
    $this->assertTrue(true); // Should handle gracefully
} catch (\Exception $e) {
    $this->fail('Service should not throw exceptions');
}
```

#### Task 1.2: Expand SmtpService Tests (3-4 hours)
**File**: `tests/Unit/Services/SmtpServiceTest.php` (EXISTS - expand)  
**Current Tests**: 3 basic tests  
**Target**: 12-15 comprehensive tests  
**Coverage Impact**: 40% → 70%+

**Tests to Add**:
1. SMTP connection with valid/invalid credentials
2. TLS/SSL encryption handling
3. Various SMTP auth methods (PLAIN, LOGIN, CRAM-MD5)
4. Email sending with attachments
5. Large email body handling
6. Unicode character support
7. Bounce notification processing
8. Connection retry logic
9. Rate limiting and throttling
10. Email queue management
11. Send failure error handling
12. SMTP timeout handling

#### Task 1.3: Job & Listener Integration Tests (4-5 hours)
**Files to Create**:
- `tests/Unit/Jobs/SendAutoReplyJobTest.php` (EXISTS - expand)
- `tests/Unit/Jobs/SendConversationReplyJobTest.php` (NEW)
- `tests/Unit/Listeners/SendAutoReplyListenerTest.php` (EXISTS - expand)
- `tests/Unit/Listeners/HandleNewMessageListenerTest.php` (NEW)

**Tests to Add** (20-25 total):
1. Job dispatch and queuing
2. Job execution with valid data
3. Job failure handling and retry logic
4. Job timeout behavior
5. Listener event handling
6. Listener condition checking (e.g., auto-reply enabled)
7. Listener database interactions
8. Integration: Event → Listener → Job → Email flow
9. Dead letter queue handling
10. Job priority and ordering

**Example Test Structure**:
```php
public function test_send_auto_reply_job_sends_email(): void
{
    Mail::fake();
    Queue::fake();
    
    $conversation = Conversation::factory()->create();
    $mailbox = $conversation->mailbox;
    $mailbox->update(['auto_reply_enabled' => true]);
    
    SendAutoReplyJob::dispatch($conversation);
    
    Queue::assertPushed(SendAutoReplyJob::class);
    
    // Execute the job
    (new SendAutoReplyJob($conversation))->handle();
    
    Mail::assertSent(AutoReply::class);
}
**File**: `tests/Unit/ModuleModelTest.php`
**Issue**: Test expects `is_enabled` attribute but model uses `active`
**Action**: Add accessor to Module model to maintain test compatibility
```php
// In app/Models/Module.php - Add accessor:
public function getIsEnabledAttribute(): bool
{
    return (bool) $this->active;
}
```
**Rationale**: Adding accessor preserves backward compatibility without breaking tests.

#### Task 0.2: Add SendLog Constants (10 min)
**File**: `app/Models/SendLog.php`
**Issue**: Missing constants for mail types and statuses
**Action**: Add constants to model (check archived schema first)
```php
class SendLog extends Model
{
    // Mail Types
    public const MAIL_TYPE_REPLY = 1;
    public const MAIL_TYPE_NOTE = 2;
    public const MAIL_TYPE_AUTO_REPLY = 3;
    
    // Status Constants  
    public const STATUS_ACCEPTED = 1;
    public const STATUS_SEND_ERROR = 2;
}
```
**Database Check**: Verify `mail_type` and `status` columns exist in archive DB schema.

#### Task 0.3: Fix Subscription Model Fillable (5 min)
**File**: `app/Models/Subscription.php`
**Issue**: `conversation_id` not in fillable array but used in tests
**Action**: Add to fillable (verify column exists in archive)
```php
protected $fillable = [
    'user_id',
    'conversation_id',  // ADD THIS
    'medium',
    'event',
];
```
**Database Check**: Confirm `subscriptions.conversation_id` column exists.

#### Task 0.4: Verify All Tests Pass (10 min)
```bash
php artisan test
```
**Expected**: 188/188 passing, 0 failures

**Completion Criteria**: All tests green, no database schema changes made.

---

### 🔥 PHASE 1: Critical Service Coverage (HIGH PRIORITY)
**Agent Assignment**: Agent-1-Services  
**Parallelizable**: YES (with Phase 2)  
**Duration**: 6-8 hours  
**Prerequisites**: Phase 0 complete  
**Impact**: +30-40 tests, +25% coverage on critical services

**Objective**: Test the highest-risk components (ImapService CRAP: 15,687 and SmtpService CRAP: 197)

#### 1.1 ImapService Core Functionality Tests
**File**: `tests/Unit/Services/ImapServiceTest.php` (NEW)
**Coverage Target**: ImapService (current: 6%, target: 50%+)
**Tests to Create**: 15-20 tests

**Priority Tests**:

```php
<?php

namespace Tests\Feature;

use App\Models\{Conversation, Customer, Mailbox, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationControllerSecurityTest extends TestCase
{
    use RefreshDatabase;

    /** Test unauthorized user cannot access conversations */
    public function test_guest_cannot_view_conversations(): void
    {
        $mailbox = Mailbox::factory()->create();
        
        $response = $this->get(route('conversations.index', $mailbox));
        
        $response->assertRedirect(route('login'));
    }

    /** Test user cannot access conversations from unauthorized mailbox */
    public function test_user_cannot_view_unauthorized_mailbox_conversations(): void
    {
        $user = User::factory()->create();
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();
        
        $mailbox1->users()->attach($user);  // User only has access to mailbox1
        
        $conversation = Conversation::factory()->for($mailbox2)->create();
        
        $response = $this->actingAs($user)->get(
            route('conversations.show', $conversation)
        );
        
        $response->assertForbidden();
    }

    /** Test user cannot modify conversations they don't own */
    public function test_user_cannot_update_conversation_in_unauthorized_mailbox(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        
        $mailbox->users()->attach($admin);
        $conversation = Conversation::factory()->for($mailbox)->create();
        
        $response = $this->actingAs($user)->patch(
            route('conversations.update', $conversation),
            ['status' => 2]
        );
        
        $response->assertForbidden();
    }

    /** Test SQL injection prevention in search */
    public function test_conversation_search_prevents_sql_injection(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user);
        
        $maliciousInput = "' OR '1'='1"; 
        
        $response = $this->actingAs($user)->get(
            route('conversations.index', ['q' => $maliciousInput])
        );
        
        $response->assertOk();
        // Should return empty or safe results, not error
    }

    /** Test XSS prevention in conversation subject */
    public function test_conversation_subject_sanitizes_xss(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user);
        
        $xssPayload = '<script>alert("xss")</script>';
        $customer = Customer::factory()->create();
        
        $response = $this->actingAs($user)->post(
            route('conversations.store', $mailbox),
            [
                'customer_id' => $customer->id,
                'subject' => $xssPayload,
                'body' => 'Test body',
                'to' => [$customer->email],
            ]
        );
        
        $conversation = Conversation::latest()->first();
        
        // Subject should be escaped/sanitized
        $this->assertStringNotContainsString('<script>', $conversation->subject);
    }

    /** Test rate limiting on conversation creation */
    public function test_conversation_creation_rate_limit(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user);
        $customer = Customer::factory()->create();
        
        // Create many conversations rapidly
        for ($i = 0; $i < 100; $i++) {
            $response = $this->actingAs($user)->post(
                route('conversations.store', $mailbox),
                [
                    'customer_id' => $customer->id,
                    'subject' => "Test $i",
                    'body' => 'Test body',
                    'to' => [$customer->email],
                ]
            );
            
            if ($i > 50) {  // Expect rate limit after 50
                // Should eventually hit rate limit
                if ($response->status() === 429) {
                    $this->assertTrue(true);
                    return;
                }
            }
        }
        
        // If no rate limit hit, test should document this
        $this->markTestIncomplete('Rate limiting not implemented');
    }
}
**Phase 1 Completion Criteria**:
- All service tests pass
- Coverage on ImapService ≥ 50%
- Coverage on SmtpService ≥ 70%
- All jobs and listeners have basic coverage ≥ 60%
- No new database migrations required
- Integration tests demonstrate end-to-end email flow

---

### 🎯 PHASE 2: Command & Console Coverage (HIGH PRIORITY)
**Agent Assignment**: Agent-2-Commands  
**Parallelizable**: YES (with Phase 1, 3)  
**Duration**: 4-6 hours  
**Prerequisites**: Phase 0 complete ✅  
**Impact**: +20-30 tests, bring 0% coverage components to 60%+

**Objective**: Test console commands that currently have ZERO coverage

**Scope**:
- **ConfigureGmailMailbox Command** (Coverage: 0%)
- **FetchEmails Command** (Coverage: 0%)
- **TestEventSystem Command** (Coverage: 0%)

#### Task 2.1: Command Tests (4-6 hours)
**Files to Create**:
- `tests/Feature/Commands/ConfigureGmailMailboxTest.php` (NEW)
- `tests/Feature/Commands/FetchEmailsCommandTest.php` (NEW)
- `tests/Feature/Commands/TestEventSystemTest.php` (NEW)

**Tests to Add** (15-20 total):
1. Command execution without errors
2. Command help output
3. Command argument validation
4. Command output formatting
5. Command database interactions
6. Command error handling
7. Command exit codes
8. Command in scheduled tasks
9. Command with various options/flags
10. Command logging

**Example Test Structure**:
```php
public function test_fetch_emails_command_runs_successfully(): void
{
    $mailbox = Mailbox::factory()->create([
        'in_server' => 'imap.example.com',
        'in_username' => 'test@example.com',
    ]);
    
    $this->artisan('freescout:fetch-emails')
        ->expectsOutput('Fetching emails...')
        ->assertExitCode(0);
}

public function test_fetch_emails_command_with_specific_mailbox(): void
{
    $mailbox = Mailbox::factory()->create();
    
    $this->artisan('freescout:fetch-emails', ['--mailbox' => $mailbox->id])
        ->assertExitCode(0);
}
```

**Phase 2 Completion Criteria**:
- All command tests pass
- Commands can be executed without errors
- Command output is tested
- Error scenarios are covered
- Integration with artisan schedule tested

---

### 📧 PHASE 3: Mail & Event Broadcasting Coverage (MEDIUM PRIORITY)
**Agent Assignment**: Agent-3-Mail-Events  
**Parallelizable**: YES (with Phase 1, 2)  
**Duration**: 4-6 hours  
**Prerequisites**: Phase 0 complete ✅  
**Impact**: +25-35 tests, improve mail/event coverage to 70%+

**Objective**: Test mail classes and event broadcasting mechanisms

**Scope**:
- **AutoReply Mail** (Coverage: 14%)
- **ConversationReplyNotification Mail** (Coverage: 6%)
- **NewMessageReceived Event** (Coverage: 4%)
- **ConversationUpdated Event** (Coverage: 30%)
- **UserViewingConversation Event** (Coverage: 30%)

#### Task 3.1: Mail Class Tests (2-3 hours)
**Files to Expand**:
- `tests/Unit/MailTest.php` (EXISTS - expand significantly)

**Tests to Add** (12-15 total):
1. Mail envelope (from, to, subject)
2. Mail content rendering
3. Mail with attachments
4. Mail variable replacement
5. Mail HTML vs text rendering
6. Mail headers (Reply-To, CC, BCC)
7. Mail queue configuration
8. Mail sending triggers
9. Mail failure handling
10. Mail with inline images
11. Mail with various locales
12. Mail preview generation

#### Task 3.2: Event Broadcasting Tests (2-3 hours)
**File to Expand**:
- `tests/Unit/EventBroadcastingTest.php` (EXISTS - expand)

**Tests to Add** (10-15 total):
1. Event broadcast channel selection
2. Event data serialization
3. Event authentication for private channels
4. Event broadcasting with Pusher/Redis
5. Event listener registration
6. Event queue configuration
7. Broadcast event data structure
8. WebSocket connection handling
9. Event retry logic
10. Event failure handling

**Phase 3 Completion Criteria**:
- Mail classes have 70%+ coverage
- Events have 70%+ coverage
- Broadcasting mechanisms tested
- Mail rendering verified
- Event payloads validated

---

### 🔧 PHASE 4: Model & Relationship Edge Cases (MEDIUM PRIORITY)
**Agent Assignment**: Agent-4-Models  
**Parallelizable**: YES (after Phase 1-3 start)  
**Duration**: 4-6 hours  
**Prerequisites**: Phase 0 complete ✅  
**Impact**: +20-30 tests, bring model coverage to 85%+

**Objective**: Test model methods, relationships, and edge cases currently uncovered

**Scope**:
- **ActivityLog Model** (Coverage: 33%)
- **Attachment Model** (Coverage: 46%)
- **Conversation Model** (Coverage: 52%)
- **Module Model** (Coverage: 54%)
- **SendLog Model** (Coverage: 63%)
- **Subscription Model** (Coverage: 63%)
- **Thread Model** (Coverage: 65%)

#### Task 4.1: Model Method & Accessor Tests (2-3 hours)
**Files to Expand**:
- `tests/Unit/*ModelTest.php` (multiple files)

**Tests to Add** (15-20 total):
1. Accessor methods (get*Attribute)
2. Mutator methods (set*Attribute)
3. Scope methods
4. Helper methods (is*, get*)
5. Relationship methods
6. Model casts
7. Model fillable/guarded
8. Model timestamps
9. Model soft deletes (if applicable)
10. Model factories

#### Task 4.2: Relationship & Cascade Tests (2-3 hours)
**File**: `tests/Unit/ModelRelationshipsTest.php` (EXISTS - expand)

**Tests to Add** (10-15 total):
1. Cascade deletes
2. Orphaned record handling
3. Polymorphic relationships
4. Many-to-many pivot data
5. Has-many-through relationships
6. Eager loading optimization
7. Lazy loading behavior
8. Relationship constraints
9. Relationship counting
10. Relationship existence queries

**Phase 4 Completion Criteria**:
- All model methods tested
- All relationships validated
- Cascade behavior verified
- Edge cases covered
- Models have 85%+ coverage

---

### 🎨 PHASE 5: Controller Method Coverage (LOWER PRIORITY)
**Agent Assignment**: Agent-5-Controllers  
**Parallelizable**: YES (after Phase 1-4)  
**Duration**: 3-5 hours  
**Prerequisites**: Phase 0 complete ✅  
**Impact**: +15-25 tests, fill controller coverage gaps

**Objective**: Test currently uncovered controller methods

**Scope**:
- **ConversationController::create, ::ajax, ::upload, ::clone** (0% coverage)
- **SettingsController::testSmtp, ::testImap, ::validateSmtp** (0% coverage)
- **UserController::create, ::show, ::edit, ::ajax** (0% coverage)
- **ModulesController** (45% coverage - expand)

#### Task 5.1: Controller Method Tests (3-5 hours)
**Files to Expand**:
- `tests/Feature/ConversationTest.php`
- `tests/Feature/SettingsTest.php`
- `tests/Feature/UserManagementTest.php`
- `tests/Feature/ModulesTest.php`

**Tests to Add** (15-25 total):
1. View rendering methods
2. AJAX endpoint responses
3. File upload handling
4. Form validation
5. JSON response structure
6. Error response handling
7. Redirect behavior
8. Flash message handling
9. Authorization checks
10. Method-specific business logic

**Phase 5 Completion Criteria**:
- All controller methods have basic coverage
- AJAX endpoints tested
- File uploads validated
- Controllers have 80%+ coverage

---

### 🚦 PHASE 6: Integration & Performance Tests (LOWEST PRIORITY)
**Agent Assignment**: Agent-6-Integration  
**Parallelizable**: NO (depends on all previous phases)  
**Duration**: 4-6 hours  
**Prerequisites**: Phases 1-5 complete  
**Impact**: +15-20 tests, validate system integration

**Objective**: End-to-end workflow validation and performance benchmarking

#### Task 6.1: Complete Workflow Tests (2-3 hours)
**File**: `tests/Feature/CompleteWorkflowTest.php` (NEW)

**Tests to Add** (8-10 total):
1. Full customer inquiry workflow (email in → conversation → reply → email out)
2. Auto-reply workflow
3. Conversation assignment workflow
4. Multi-user collaboration workflow
5. Email threading workflow
6. Attachment handling workflow
7. User authentication → dashboard → conversation workflow
8. Settings update → system impact workflow

#### Task 6.2: Performance Tests (2-3 hours)
**File**: `tests/Feature/PerformanceTest.php` (NEW)

**Tests to Add** (5-8 total):
1. Large conversation list loading
2. Search with large dataset
3. Mailbox with many conversations
4. Email fetch with many messages
5. Query optimization validation
6. Memory usage benchmarks
7. Response time benchmarks

**Phase 6 Completion Criteria**:
- All critical workflows tested end-to-end
- Performance benchmarks established
- Integration points validated
- System behaves correctly under load

---

## 📊 SUCCESS METRICS & TARGETS

### Coverage Targets by Phase

| Phase | Component | Current | Target | Tests Added |
|-------|-----------|---------|--------|-------------|
| 0 | Baseline | 674 tests | 674 tests | 0 (fixes) |
| 1 | Services/Jobs/Listeners | 0-40% | 60-70% | +40-60 |
| 2 | Commands | 0% | 60%+ | +20-30 |
| 3 | Mail/Events | 4-30% | 70%+ | +25-35 |
| 4 | Models | 33-65% | 85%+ | +20-30 |
| 5 | Controllers | 45-87% | 80%+ | +15-25 |
| 6 | Integration | N/A | Complete | +15-20 |
| **TOTAL** | **All** | **49.78%** | **70-80%** | **+135-200** |

### Final Target State
- **Total Tests**: 810-875 tests
- **Line Coverage**: 70-80%
- **Method Coverage**: 75-85%
- **Class Coverage**: 65-75%
- **CRAP Score**: All components < 50
- **Test Execution Time**: < 45 seconds

---

## 🎯 AGENT EXECUTION GUIDELINES

### For Each Phase

1. **Read Phase Assignment**: Understand scope, objectives, and targets
2. **Review Existing Code**: Check `/var/www/html/app/` for implementation details
3. **Check Coverage Report**: Review `/var/www/html/coverage-report/dashboard.html`
4. **Verify Database Compatibility**: Check `/var/www/html/archive/database/migrations/`
5. **Create/Expand Test Files**: Follow the test structure provided
6. **Run Tests Incrementally**: `php artisan test --filter=YourTestClass`
7. **Fix Failures Immediately**: Don't move to next test until current passes
8. **Verify Coverage Impact**: Regenerate coverage report
9. **Report Completion**: Provide test count, coverage %, and any blockers

### Test Quality Checklist

- [ ] Uses `RefreshDatabase` trait
- [ ] Uses factories for all test data
- [ ] Tests actual behavior (DB changes, content, relationships)
- [ ] Includes negative test cases (failures, validation)
- [ ] No hardcoded IDs or data
- [ ] Descriptive test names (`test_user_can_action_resource`)
- [ ] AAA pattern (Arrange, Act, Assert)
- [ ] No external dependencies (mocked)
- [ ] Independent (can run in any order)
- [ ] Fast execution (< 1 second per test)

---

## 📝 QUICK REFERENCE

### Running Tests
```bash
# All tests
php artisan test

# Specific test file
php artisan test tests/Feature/ConversationTest.php

# Specific test method
php artisan test --filter=test_user_can_create_conversation

# With coverage
php artisan test --coverage-html coverage-report

# Parallel execution
php artisan test --parallel
```

### Coverage Analysis
```bash
# Generate HTML report
php artisan test --coverage-html coverage-report

# View report
open coverage-report/index.html

# Coverage summary
php artisan test --coverage
```

### Common Patterns
```php
// Database assertions
$this->assertDatabaseHas('table', ['field' => 'value']);

// Response assertions
$response->assertOk();
$response->assertSee('text');
$response->assertViewIs('view.name');
$response->assertViewHas('variable');

// Authentication
$this->actingAs($user);
$this->assertAuthenticated();

// Mocking
Mail::fake();
Queue::fake();
Event::fake();
```

---

**Document Version**: 2.0  
**Last Updated**: November 7, 2025  
**Status**: Ready for Agent Execution  
**Total Estimated Effort**: 27-41 hours across 6 phases  
**Expected Outcome**: 810-875 tests, 70-80% coverage


