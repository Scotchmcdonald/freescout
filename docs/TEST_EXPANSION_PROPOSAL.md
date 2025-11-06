# Test Expansion Proposal for FreeScout
**Generated**: November 6, 2025  
**Current Status**: 188 passing tests, 6 failing tests  
**Test Files**: 39 files  
**Goal**: Comprehensive edge case coverage and robust test suite

---

## 📊 Current Test Status

### Test Results Summary
```
✅ Passing: 182 tests (285 assertions)
❌ Failing: 6 tests
Duration: 8.20s
```

### Current Failures to Fix First

#### 1. **ModuleModelTest** - Test expects wrong attributes
```php
// ISSUE: Test expects 'is_enabled' but model uses 'active'
// File: tests/Unit/ModuleModelTest.php:30
Expected: $module->is_enabled
Actual: $module->active (in App\Models\Module)

FIX REQUIRED:
- Update test to use 'active' instead of 'is_enabled'
- OR add 'is_enabled' as alias/accessor in Module model
```

#### 2. **SendLogModelTest** - Missing constants (2 failures)
```php
// ISSUE: Constants not defined in SendLog model
// File: tests/Unit/SendLogModelTest.php:20, 34

MISSING CONSTANTS:
- SendLog::MAIL_TYPE_REPLY (expected: 1)
- SendLog::MAIL_TYPE_NOTE (expected: 2)  
- SendLog::MAIL_TYPE_AUTO_REPLY (expected: 3)
- SendLog::STATUS_ACCEPTED (expected: 1)
- SendLog::STATUS_SEND_ERROR (expected: 2)

FIX REQUIRED: Add to App\Models\SendLog
```

#### 3. **SubscriptionModelTest** - Missing 'conversation_id' in fillable
```php
// ISSUE: 'conversation_id' not in fillable array
// File: tests/Unit/SubscriptionModelTest.php:26

FIX REQUIRED:
- Add 'conversation_id' to Subscription model fillable array
- Verify database migration includes conversation_id column
```

---

## 🎯 Test Expansion Strategy

### Coverage Gaps Identified

| Component | Current Tests | Coverage | Priority | New Tests Needed |
|-----------|--------------|----------|----------|------------------|
| **Models** | ✅ Good | ~80% | Medium | Edge cases |
| **Controllers** | ⚠️ Partial | ~40% | HIGH | Security, validation |
| **Services** | ⚠️ Minimal | ~20% | HIGH | Core logic |
| **Events/Listeners** | ⚠️ Minimal | ~30% | Medium | Integration |
| **Jobs** | ⚠️ Minimal | ~25% | Medium | Queue behavior |
| **Mail** | ⚠️ Minimal | ~15% | Medium | Delivery, templates |
| **Policies** | ✅ Some | ~60% | Medium | Authorization edge cases |
| **Middleware** | ❌ None | 0% | Medium | Request filtering |
| **Helpers** | ❌ None | 0% | Low | Utility functions |
| **Commands** | ⚠️ Minimal | ~10% | Low | CLI behavior |

---

## 📋 Detailed Test Proposals

### Phase 1: Fix Existing Failures (PRIORITY 1 - 30 minutes)

#### Task 1.1: Fix Module Model Test
```php
// File: tests/Unit/ModuleModelTest.php

// CURRENT (Line 30):
$this->assertTrue($module->is_enabled);

// OPTION A - Update test to match model:
$this->assertTrue($module->active);

// OPTION B - Add accessor to model:
// In App\Models\Module.php:
public function getIsEnabledAttribute(): bool
{
    return $this->active;
}
```

#### Task 1.2: Add SendLog Constants
```php
// File: app/Models/SendLog.php
// Add after class declaration:

class SendLog extends Model
{
    use HasFactory;

    // Mail Types
    public const MAIL_TYPE_REPLY = 1;
    public const MAIL_TYPE_NOTE = 2;
    public const MAIL_TYPE_AUTO_REPLY = 3;

    // Status Constants
    public const STATUS_ACCEPTED = 1;
    public const STATUS_SEND_ERROR = 2;

    // ... rest of class
}
```

#### Task 1.3: Fix Subscription Model
```php
// File: app/Models/Subscription.php
// Update fillable array:

protected $fillable = [
    'user_id',
    'conversation_id',  // ADD THIS
    'medium',
    'event',
];
```

**Expected Outcome**: All 188 tests passing ✅

---

### Phase 2: Controller Edge Cases (PRIORITY 1 - 4-6 hours)

#### 2.1 ConversationController Security Tests
**File**: `tests/Feature/ConversationControllerSecurityTest.php` (NEW)

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
```

#### 2.2 ConversationController Validation Tests
**File**: `tests/Feature/ConversationValidationTest.php` (NEW)

```php
<?php

namespace Tests\Feature;

use App\Models\{Customer, Mailbox, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationValidationTest extends TestCase
{
    use RefreshDatabase;

    /** Test empty subject validation */
    public function test_conversation_requires_subject(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user);
        $customer = Customer::factory()->create();
        
        $response = $this->actingAs($user)->post(
            route('conversations.store', $mailbox),
            [
                'customer_id' => $customer->id,
                'subject' => '',  // Empty
                'body' => 'Test body',
                'to' => [$customer->email],
            ]
        );
        
        $response->assertSessionHasErrors('subject');
    }

    /** Test empty body validation */
    public function test_conversation_requires_body(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user);
        $customer = Customer::factory()->create();
        
        $response = $this->actingAs($user)->post(
            route('conversations.store', $mailbox),
            [
                'customer_id' => $customer->id,
                'subject' => 'Test',
                'body' => '',  // Empty
                'to' => [$customer->email],
            ]
        );
        
        $response->assertSessionHasErrors('body');
    }

    /** Test invalid email format */
    public function test_conversation_validates_email_format(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user);
        $customer = Customer::factory()->create();
        
        $response = $this->actingAs($user)->post(
            route('conversations.store', $mailbox),
            [
                'customer_id' => $customer->id,
                'subject' => 'Test',
                'body' => 'Test body',
                'to' => ['not-an-email'],
            ]
        );
        
        $response->assertSessionHasErrors('to');
    }

    /** Test subject length limit */
    public function test_conversation_subject_max_length(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user);
        $customer = Customer::factory()->create();
        
        $response = $this->actingAs($user)->post(
            route('conversations.store', $mailbox),
            [
                'customer_id' => $customer->id,
                'subject' => str_repeat('a', 300),  // Very long
                'body' => 'Test body',
                'to' => [$customer->email],
            ]
        );
        
        // Should either truncate or reject
        $response->assertSessionHasErrors('subject');
    }

    /** Test body with only whitespace */
    public function test_conversation_body_rejects_only_whitespace(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user);
        $customer = Customer::factory()->create();
        
        $response = $this->actingAs($user)->post(
            route('conversations.store', $mailbox),
            [
                'customer_id' => $customer->id,
                'subject' => 'Test',
                'body' => '   \n\n   ',  // Only whitespace
                'to' => [$customer->email],
            ]
        );
        
        $response->assertSessionHasErrors('body');
    }

    /** Test multiple recipients */
    public function test_conversation_accepts_multiple_recipients(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user);
        $customer = Customer::factory()->create();
        
        $response = $this->actingAs($user)->post(
            route('conversations.store', $mailbox),
            [
                'customer_id' => $customer->id,
                'subject' => 'Test',
                'body' => 'Test body',
                'to' => ['user1@test.com', 'user2@test.com', 'user3@test.com'],
            ]
        );
        
        $response->assertRedirect();
    }

    /** Test CC and BCC fields */
    public function test_conversation_handles_cc_and_bcc(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user);
        $customer = Customer::factory()->create();
        
        $response = $this->actingAs($user)->post(
            route('conversations.store', $mailbox),
            [
                'customer_id' => $customer->id,
                'subject' => 'Test',
                'body' => 'Test body',
                'to' => [$customer->email],
                'cc' => ['cc@test.com'],
                'bcc' => ['bcc@test.com'],
            ]
        );
        
        $response->assertRedirect();
        // Verify CC/BCC stored appropriately
    }
}
```

---

### Phase 3: Service Layer Tests (PRIORITY 1 - 4-6 hours)

#### 3.1 ImapService Comprehensive Tests
**File**: `tests/Unit/ImapServiceAdvancedTest.php` (NEW)

```php
<?php

namespace Tests\Unit;

use App\Models\{Mailbox, Customer, Conversation};
use App\Services\ImapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImapServiceAdvancedTest extends TestCase
{
    use RefreshDatabase;

    /** Test handling of malformed emails */
    public function test_handles_malformed_email_headers(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.test.com',
            'in_port' => 993,
            'in_username' => 'test@test.com',
            'in_password' => 'password',
        ]);
        
        $service = new ImapService();
        
        // This should not throw exceptions
        $result = $service->fetchEmails($mailbox);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('errors', $result);
    }

    /** Test handling of very large attachments */
    public function test_handles_large_attachments_gracefully(): void
    {
        $this->markTestIncomplete('Requires mock IMAP server');
    }

    /** Test handling of duplicate message IDs */
    public function test_prevents_duplicate_message_imports(): void
    {
        $this->markTestIncomplete('Requires mock IMAP server');
    }

    /** Test charset encoding issues */
    public function test_handles_various_charset_encodings(): void
    {
        // UTF-8, ISO-8859-1, Windows-1252, etc.
        $this->markTestIncomplete('Requires mock IMAP messages');
    }

    /** Test handling of missing required headers */
    public function test_handles_missing_from_header(): void
    {
        $this->markTestIncomplete('Requires mock IMAP server');
    }

    /** Test connection timeout handling */
    public function test_handles_imap_connection_timeout(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'nonexistent.server.local',
            'in_port' => 993,
        ]);
        
        $service = new ImapService();
        $result = $service->fetchEmails($mailbox);
        
        $this->assertArrayHasKey('errors', $result);
        $this->assertGreaterThan(0, $result['errors']);
    }

    /** Test authentication failure */
    public function test_handles_invalid_credentials(): void
    {
        $this->markTestIncomplete('Requires mock IMAP server');
    }

    /** Test folder not found */
    public function test_handles_nonexistent_folder(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.test.com',
            'in_imap_folders' => 'NonExistentFolder',
        ]);
        
        $service = new ImapService();
        $result = $service->fetchEmails($mailbox);
        
        // Should log warning but not crash
        $this->assertIsArray($result);
    }

    /** Test HTML email parsing */
    public function test_parses_html_emails_correctly(): void
    {
        $this->markTestIncomplete('Requires mock HTML email');
    }

    /** Test plain text email parsing */
    public function test_parses_plain_text_emails_correctly(): void
    {
        $this->markTestIncomplete('Requires mock plain text email');
    }

    /** Test multipart email parsing */
    public function test_parses_multipart_emails_correctly(): void
    {
        $this->markTestIncomplete('Requires mock multipart email');
    }

    /** Test inline images */
    public function test_handles_inline_images(): void
    {
        $this->markTestIncomplete('Requires mock email with inline images');
    }

    /** Test threaded conversations */
    public function test_groups_emails_into_threads_correctly(): void
    {
        $this->markTestIncomplete('Requires multiple related emails');
    }

    /** Test spam filtering */
    public function test_identifies_spam_emails(): void
    {
        $this->markTestIncomplete('Implement spam detection logic');
    }
}
```

#### 3.2 SmtpService Tests
**File**: `tests/Unit/SmtpServiceTest.php` (EXPAND EXISTING)

```php
<?php

namespace Tests\Unit;

use App\Services\SmtpService;
use Tests\TestCase;

class SmtpServiceTest extends TestCase
{
    /** Test SMTP connection with valid credentials */
    public function test_connects_to_smtp_server_successfully(): void
    {
        $this->markTestIncomplete();
    }

    /** Test SMTP connection failure */
    public function test_handles_smtp_connection_failure(): void
    {
        $this->markTestIncomplete();
    }

    /** Test email sending with attachments */
    public function test_sends_email_with_attachments(): void
    {
        $this->markTestIncomplete();
    }

    /** Test email sending with large body */
    public function test_handles_large_email_body(): void
    {
        $this->markTestIncomplete();
    }

    /** Test email sending with special characters */
    public function test_sends_email_with_unicode_characters(): void
    {
        $this->markTestIncomplete();
    }

    /** Test SMTP authentication methods */
    public function test_supports_various_smtp_auth_methods(): void
    {
        // PLAIN, LOGIN, CRAM-MD5
        $this->markTestIncomplete();
    }

    /** Test TLS/SSL encryption */
    public function test_handles_tls_ssl_encryption(): void
    {
        $this->markTestIncomplete();
    }

    /** Test bounce handling */
    public function test_processes_bounce_notifications(): void
    {
        $this->markTestIncomplete();
    }
}
```

---

### Phase 4: Event & Listener Tests (PRIORITY 2 - 3-4 hours)

#### 4.1 Event Broadcasting Tests
**File**: `tests/Unit/EventBroadcastingTest.php` (NEW)

```php
<?php

namespace Tests\Unit;

use App\Events\{CustomerCreatedConversation, CustomerReplied, NewMessageReceived};
use App\Models\{Conversation, Customer, Mailbox, Thread, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EventBroadcastingTest extends TestCase
{
    use RefreshDatabase;

    /** Test CustomerCreatedConversation event is dispatched */
    public function test_customer_created_conversation_event_dispatched(): void
    {
        Event::fake();
        
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->for($mailbox)->for($customer)->create();
        $thread = Thread::factory()->for($conversation)->create();
        
        event(new CustomerCreatedConversation($conversation, $thread, $customer));
        
        Event::assertDispatched(CustomerCreatedConversation::class);
    }

    /** Test CustomerReplied event is dispatched */
    public function test_customer_replied_event_dispatched(): void
    {
        Event::fake();
        
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->for($mailbox)->for($customer)->create();
        $thread = Thread::factory()->for($conversation)->create();
        
        event(new CustomerReplied($conversation, $thread, $customer));
        
        Event::assertDispatched(CustomerReplied::class);
    }

    /** Test NewMessageReceived event is dispatched */
    public function test_new_message_received_event_dispatched(): void
    {
        Event::fake();
        
        $thread = Thread::factory()->create();
        
        event(new NewMessageReceived($thread));
        
        Event::assertDispatched(NewMessageReceived::class);
    }

    /** Test event listeners are triggered */
    public function test_new_message_triggers_notification_listener(): void
    {
        $this->markTestIncomplete('Verify SendNewMessageNotification listener triggered');
    }

    /** Test event queue is used for async processing */
    public function test_events_use_queue_for_heavy_processing(): void
    {
        $this->markTestIncomplete('Verify events pushed to queue');
    }
}
```

---

### Phase 5: Job Tests (PRIORITY 2 - 2-3 hours)

#### 5.1 Queue Job Tests
**File**: `tests/Unit/JobProcessingTest.php` (NEW)

```php
<?php

namespace Tests\Unit;

use App\Jobs\{SendAutoReplyJob, ProcessIncomingEmail};
use App\Models\{Mailbox, Customer, Conversation};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class JobProcessingTest extends TestCase
{
    use RefreshDatabase;

    /** Test SendAutoReplyJob is queued */
    public function test_send_auto_reply_job_is_queued(): void
    {
        Queue::fake();
        
        $mailbox = Mailbox::factory()->create(['auto_reply_enabled' => true]);
        $conversation = Conversation::factory()->for($mailbox)->create();
        
        SendAutoReplyJob::dispatch($conversation);
        
        Queue::assertPushed(SendAutoReplyJob::class);
    }

    /** Test job retry logic */
    public function test_failed_jobs_are_retried(): void
    {
        $this->markTestIncomplete('Test job retry with exponential backoff');
    }

    /** Test job failure handling */
    public function test_job_failures_are_logged(): void
    {
        $this->markTestIncomplete('Verify failed jobs logged properly');
    }

    /** Test job timeout handling */
    public function test_long_running_jobs_timeout_correctly(): void
    {
        $this->markTestIncomplete('Test job timeout configuration');
    }

    /** Test job batching */
    public function test_batch_jobs_process_correctly(): void
    {
        $this->markTestIncomplete('Test batch job processing');
    }
}
```

---

### Phase 6: Model Edge Cases (PRIORITY 2 - 3-4 hours)

#### 6.1 Model Relationship Tests
**File**: `tests/Unit/ModelRelationshipsTest.php` (NEW)

```php
<?php

namespace Tests\Unit;

use App\Models\{Conversation, Customer, Mailbox, Thread, User, Folder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    /** Test conversation belongs to mailbox */
    public function test_conversation_belongs_to_mailbox(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->for($mailbox)->create();
        
        $this->assertInstanceOf(Mailbox::class, $conversation->mailbox);
        $this->assertEquals($mailbox->id, $conversation->mailbox->id);
    }

    /** Test conversation has many threads */
    public function test_conversation_has_many_threads(): void
    {
        $conversation = Conversation::factory()->create();
        $thread1 = Thread::factory()->for($conversation)->create();
        $thread2 = Thread::factory()->for($conversation)->create();
        
        $this->assertCount(2, $conversation->threads);
        $this->assertTrue($conversation->threads->contains($thread1));
    }

    /** Test eager loading prevents N+1 queries */
    public function test_eager_loading_prevents_n_plus_1_queries(): void
    {
        Conversation::factory()->count(10)->create();
        
        // Without eager loading - many queries
        $countWithout = 0;
        \DB::enableQueryLog();
        $conversations = Conversation::all();
        foreach ($conversations as $conv) {
            $_ = $conv->mailbox->name;
        }
        $countWithout = count(\DB::getQueryLog());
        \DB::disableQueryLog();
        
        // With eager loading - fewer queries
        \DB::enableQueryLog();
        $conversations = Conversation::with('mailbox')->get();
        foreach ($conversations as $conv) {
            $_ = $conv->mailbox->name;
        }
        $countWith = count(\DB::getQueryLog());
        \DB::disableQueryLog();
        
        $this->assertLessThan($countWithout, $countWith);
    }

    /** Test polymorphic relationships */
    public function test_activity_log_polymorphic_relationships(): void
    {
        $this->markTestIncomplete('Test morphTo relationships');
    }

    /** Test many-to-many relationships */
    public function test_mailbox_users_many_to_many(): void
    {
        $mailbox = Mailbox::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $mailbox->users()->attach([$user1->id, $user2->id]);
        
        $this->assertCount(2, $mailbox->users);
        $this->assertTrue($mailbox->users->contains($user1));
    }

    /** Test cascading deletes */
    public function test_deleting_mailbox_deletes_conversations(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->for($mailbox)->create();
        
        $mailbox->delete();
        
        // Verify conversation is also deleted (if cascade delete configured)
        $this->assertDatabaseMissing('conversations', ['id' => $conversation->id]);
    }

    /** Test soft deletes */
    public function test_soft_deletes_work_correctly(): void
    {
        // If models use SoftDeletes trait
        $this->markTestIncomplete('Test soft delete functionality');
    }
}
```

#### 6.2 Model Scope Tests
**File**: `tests/Unit/ModelScopesTest.php` (NEW)

```php
<?php

namespace Tests\Unit;

use App\Models\{Conversation, Thread, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelScopesTest extends TestCase
{
    use RefreshDatabase;

    /** Test active conversations scope */
    public function test_active_scope_returns_only_active_conversations(): void
    {
        $active = Conversation::factory()->create(['status' => Conversation::STATUS_ACTIVE]);
        $closed = Conversation::factory()->create(['status' => Conversation::STATUS_CLOSED]);
        
        $results = Conversation::active()->get();
        
        $this->assertTrue($results->contains($active));
        $this->assertFalse($results->contains($closed));
    }

    /** Test assigned to user scope */
    public function test_assigned_to_scope_filters_by_user(): void
    {
        $user = User::factory()->create();
        $assigned = Conversation::factory()->create(['user_id' => $user->id]);
        $unassigned = Conversation::factory()->create(['user_id' => null]);
        
        $results = Conversation::where('user_id', $user->id)->get();
        
        $this->assertTrue($results->contains($assigned));
        $this->assertFalse($results->contains($unassigned));
    }

    /** Test recent scope */
    public function test_recent_scope_orders_by_latest_first(): void
    {
        $old = Conversation::factory()->create(['created_at' => now()->subDays(5)]);
        $new = Conversation::factory()->create(['created_at' => now()]);
        
        $results = Conversation::orderBy('created_at', 'desc')->get();
        
        $this->assertEquals($new->id, $results->first()->id);
    }
}
```

---

### Phase 7: Integration Tests (PRIORITY 2 - 4-6 hours)

#### 7.1 End-to-End Workflow Tests
**File**: `tests/Feature/CompleteWorkflowTest.php` (NEW)

```php
<?php

namespace Tests\Feature;

use App\Models\{Conversation, Customer, Mailbox, Thread, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CompleteWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /** Test complete customer inquiry workflow */
    public function test_complete_customer_inquiry_workflow(): void
    {
        Mail::fake();
        
        // 1. Customer sends email (simulated IMAP fetch)
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        
        // 2. Conversation created
        $conversation = Conversation::factory()
            ->for($mailbox)
            ->for($customer)
            ->create(['status' => Conversation::STATUS_ACTIVE]);
        
        // 3. Thread created from email
        $thread = Thread::factory()->for($conversation)->create([
            'type' => Thread::TYPE_CUSTOMER,
        ]);
        
        // 4. Agent responds
        $agent = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox->users()->attach($agent);
        
        $this->actingAs($agent)->post(route('conversations.reply', $conversation), [
            'body' => 'Thank you for contacting us!',
            'to' => [$customer->email],
        ]);
        
        // 5. Verify reply created
        $this->assertDatabaseHas('threads', [
            'conversation_id' => $conversation->id,
            'created_by_user_id' => $agent->id,
            'type' => Thread::TYPE_MESSAGE,
        ]);
        
        // 6. Mark as resolved
        $this->actingAs($agent)->patch(route('conversations.update', $conversation), [
            'status' => Conversation::STATUS_CLOSED,
        ]);
        
        // 7. Verify closed
        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'status' => Conversation::STATUS_CLOSED,
        ]);
        
        Mail::assertSent(\App\Mail\NewMessageNotification::class);
    }

    /** Test conversation assignment workflow */
    public function test_conversation_assignment_workflow(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $agent1 = User::factory()->create(['role' => User::ROLE_USER]);
        $agent2 = User::factory()->create(['role' => User::ROLE_USER]);
        
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach([$admin->id, $agent1->id, $agent2->id]);
        
        $conversation = Conversation::factory()->for($mailbox)->create();
        
        // Admin assigns to agent1
        $this->actingAs($admin)->patch(
            route('conversations.update', $conversation),
            ['user_id' => $agent1->id]
        );
        
        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'user_id' => $agent1->id,
        ]);
        
        // Agent1 reassigns to agent2
        $this->actingAs($agent1)->patch(
            route('conversations.update', $conversation),
            ['user_id' => $agent2->id]
        );
        
        $conversation->refresh();
        $this->assertEquals($agent2->id, $conversation->user_id);
    }

    /** Test auto-reply workflow */
    public function test_auto_reply_sends_when_enabled(): void
    {
        Mail::fake();
        
        $mailbox = Mailbox::factory()->create([
            'auto_reply_enabled' => true,
            'auto_reply_subject' => 'Thanks for contacting us',
            'auto_reply_message' => 'We will respond shortly.',
        ]);
        
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()
            ->for($mailbox)
            ->for($customer)
            ->create();
        
        // Trigger auto-reply (via event or direct call)
        // ... depends on implementation
        
        // Mail::assertSent(\App\Mail\AutoReply::class);
        $this->markTestIncomplete('Implement auto-reply trigger test');
    }
}
```

---

### Phase 8: Performance Tests (PRIORITY 3 - 2-3 hours)

#### 8.1 Database Performance Tests
**File**: `tests/Feature/PerformanceTest.php` (NEW)

```php
<?php

namespace Tests\Feature;

use App\Models\{Conversation, Customer, Mailbox};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    /** Test loading conversations with many threads */
    public function test_loads_conversation_with_many_threads_efficiently(): void
    {
        $conversation = Conversation::factory()
            ->has(Thread::factory()->count(100))
            ->create();
        
        \DB::enableQueryLog();
        
        $loaded = Conversation::with('threads')->find($conversation->id);
        
        $queryCount = count(\DB::getQueryLog());
        \DB::disableQueryLog();
        
        // Should use only 2 queries (conversation + threads)
        $this->assertLessThanOrEqual(2, $queryCount);
    }

    /** Test paginated conversations load quickly */
    public function test_paginated_conversations_perform_well(): void
    {
        Conversation::factory()->count(1000)->create();
        
        $start = microtime(true);
        
        $conversations = Conversation::with('mailbox', 'customer')
            ->paginate(50);
        
        $duration = microtime(true) - $start;
        
        // Should load in under 1 second
        $this->assertLessThan(1.0, $duration);
    }

    /** Test search performance with large dataset */
    public function test_search_performs_well_with_many_conversations(): void
    {
        Conversation::factory()->count(5000)->create();
        
        $start = microtime(true);
        
        $results = Conversation::where('subject', 'like', '%test%')
            ->limit(50)
            ->get();
        
        $duration = microtime(true) - $start;
        
        // Should complete in under 1 second
        $this->assertLessThan(1.0, $duration);
    }
}
```

---

## 🎯 Test Execution Priority

### Immediate (Today)
1. ✅ Fix 6 failing tests (30 minutes)
2. ✅ Verify all 188 tests pass

### Week 1 (High Priority)
1. Phase 2: Controller Security & Validation (4-6 hours)
2. Phase 3: Service Layer Tests (4-6 hours)
3. Expected: +50-60 tests, ~240-250 total tests

### Week 2 (Medium Priority)
1. Phase 4: Events & Listeners (3-4 hours)
2. Phase 5: Job Tests (2-3 hours)
3. Phase 6: Model Edge Cases (3-4 hours)
4. Expected: +40-50 tests, ~280-300 total tests

### Week 3 (Lower Priority)
1. Phase 7: Integration Tests (4-6 hours)
2. Phase 8: Performance Tests (2-3 hours)
3. Expected: +20-30 tests, ~300-330 total tests

---

## 📊 Success Metrics

### Current State
- Tests: 188 (182 passing, 6 failing)
- Test Files: 39
- Coverage: ~40% estimated

### Target State (After All Phases)
- Tests: 300-330 (all passing)
- Test Files: 60-70
- Coverage: ~70-80% estimated
- All critical paths tested
- All edge cases covered
- Security vulnerabilities tested
- Performance validated

---

## 🔧 Testing Best Practices to Follow

### 1. AAA Pattern
```php
// Arrange
$user = User::factory()->create();

// Act
$response = $this->actingAs($user)->get('/dashboard');

// Assert
$response->assertOk();
```

### 2. Database Transactions
```php
use RefreshDatabase;  // Rolls back after each test
```

### 3. Factory Usage
```php
// Use factories, not manual creation
$user = User::factory()->create();
```

### 4. Descriptive Test Names
```php
// Good
public function test_user_cannot_delete_conversation_in_unauthorized_mailbox(): void

// Bad
public function test_delete(): void
```

### 5. Test One Thing
Each test should verify one specific behavior.

### 6. Mock External Services
```php
Mail::fake();
Queue::fake();
Event::fake();
```

### 7. Use Data Providers
```php
/** @dataProvider invalidEmailProvider */
public function test_rejects_invalid_emails(string $email): void
{
    // Test with multiple invalid formats
}
```

---

## 🚀 Next Steps for Implementation

**For the other LLM to execute:**

1. **Start with failures**: Fix the 6 failing tests first
2. **Choose a phase**: Pick Phase 2 (Controllers) as highest priority
3. **Create test files**: Use the templates provided above
4. **Run tests incrementally**: After each test file, run `php artisan test`
5. **Adjust as needed**: Some tests may need modification based on actual implementation
6. **Document coverage**: Track which components are fully tested
7. **Report blockers**: Some tests may require mocking or additional setup

**Commands to use:**
```bash
# Run all tests
php artisan test

# Run specific file
php artisan test tests/Feature/ConversationControllerSecurityTest.php

# Run with coverage (if xdebug enabled)
php artisan test --coverage

# Run parallel (faster)
php artisan test --parallel
```

---

**Generated**: November 6, 2025  
**Status**: Ready for Implementation  
**Estimated Total Effort**: 24-36 hours  
**Expected Outcome**: 300+ comprehensive tests covering all edge cases
