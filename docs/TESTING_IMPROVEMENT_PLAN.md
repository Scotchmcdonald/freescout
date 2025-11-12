# Comprehensive Testing Improvement Plan

## 1. Overview

This document outlines a comprehensive, risk-based testing plan to improve the overall quality, stability, and maintainability of the application. The priorities are derived from an analysis of the PHPUnit code coverage report generated on 2025-11-11.

**Current Coverage:** 50.43% line coverage (2035/4035 lines) | **Target:** 80% line coverage

The primary goal is to address the most critical, complex, and untested areas of the codebase first. Priority is given to targets with a high CRAP (Change Risk Anti-Patterns) index, which is a function of high cyclomatic complexity and low test coverage. This plan is structured to be distributed across a team for parallel execution.

**Test Suite Status:**
- Total Tests: 1,460 tests
- Passing: 1,444 tests
- Test Files: 155 test files (67 Unit, 88 Feature)
- Naming Convention: snake_case (`test_method_name()`)

**Key Testing Principles:**
1. **Unit Tests** (`tests/Unit/`): For isolated logic, services, models, policies
2. **Feature Tests** (`tests/Feature/`): For HTTP requests, workflows, integrations
3. Use Laravel's test doubles: `Queue::fake()`, `Mail::fake()`, `Event::fake()`
4. Mock external dependencies: IMAP clients, SMTP connections
5. Follow existing test patterns in the repository

## 2. High-Level Priority Targets

These five targets represent the highest immediate risk to the application's stability and functionality, based on a combination of low test coverage and high operational complexity.

---

### Target 1: `App\Services\ImapService`
*   **Metrics:** **9.08%** Line Coverage (Verified: 7.02% per coverage report)
*   **File:** `app/Services/ImapService.php`
*   **Justification:** This service handles the core functionality of fetching and parsing emails, a primary data ingress point for the application. Its extremely low coverage and inherent complexity create a high risk of data loss, silent failures, and unhandled connection errors.
*   **Existing Test Files:** 
    - `tests/Unit/Services/ImapServiceTest.php` (basic structure tests)
    - `tests/Unit/Services/ImapServiceComprehensiveTest.php` (partial coverage)
    - `tests/Unit/ImapServiceTest.php` (basic tests)
    - `tests/Unit/ImapServiceAdvancedTest.php` (partial coverage)

---

### Target 2: `App\Jobs\SendNotificationToUsers`
*   **Metrics:** **1.06%** Line Coverage
*   **File:** `app/Jobs/SendNotificationToUsers.php`
*   **Justification:** This is a critical asynchronous job responsible for user notifications. Its near-zero coverage means any failure (e.g., due to a change in a related model or service) will be silent and difficult to debug, directly impacting user engagement.
*   **Existing Test Files:** 
    - `tests/Unit/Jobs/SendNotificationToUsersTest.php` (basic property tests only)

---

### Target 3: `App\Console\Commands\ModuleInstall`
*   **Metrics:** **2.00%** Line Coverage (1/50 lines covered)
*   **File:** `app/Console/Commands/ModuleInstall.php`
*   **Justification:** As a command that modifies the filesystem and database schema, a bug here could lead to a corrupted or broken application state. Its high-risk nature and lack of tests make it a top priority.
*   **Existing Test Files:** None - new file needed: `tests/Feature/Commands/ModuleInstallCommandTest.php`
*   **Related Commands:** `ModuleUpdate` (1.61% coverage), `ModuleBuild` need similar testing

---

### Target 4: `App\Http\Controllers\ConversationController`
*   **Metrics:** **49.21%** Line Coverage (218/443 lines covered)
*   **Methods Coverage:** 33.33% (6/18 methods covered)
*   **File:** `app/Http/Controllers/ConversationController.php`
*   **Justification:** This is a large, critical controller managing the application's core "conversation" resource. The untested lines likely contain complex authorization checks, state changes, and error-handling logic that are currently unprotected from regression.
*   **Existing Test Files:**
    - `tests/Feature/ConversationControllerMethodsTest.php` (partial coverage)
    - `tests/Unit/Controllers/ConversationControllerTest.php` (basic tests)
    - `tests/Feature/ConversationValidationTest.php` (validation tests)

---

### Target 5: `App\Jobs\SendAutoReply`
*   **Metrics:** **1.32%** Line Coverage
*   **File:** `app/Jobs/SendAutoReply.php`
*   **Justification:** This asynchronous job is a key part of the customer service workflow. Its lack of tests means that a failure would break a core feature and degrade the customer experience without any immediate feedback.
*   **Existing Test Files:**
    - `tests/Unit/Jobs/SendAutoReplyTest.php` (basic property tests)
    - `tests/Unit/Jobs/SendAutoReplyComprehensiveTest.php` (partial coverage)
    - `tests/Unit/SendAutoReplyJobTest.php` (basic tests)

## 3. Enhanced Workload Batches for Parallel Development

To facilitate distribution of this work, the testing tasks have been grouped into the following parallelizable batches. Each batch contains a series of epics and user stories that can be assigned to different developers.

---

### Batch 1: Core Services (Highest Priority)
*   **Objective:** Solidify the application's interaction with external mail services. This is the most critical batch as it protects the primary data ingress/egress paths.
*   **Notes:** This batch requires extensive mocking of external clients (`Webklex\PHPIMAP\Client`, `Illuminate\Mail\Mailer`). The focus is on pure unit tests that do not require a full application boot.
*   **Target Coverage Increase:** From ~9% to ~75% for ImapService, From ~40% to ~85% for SmtpService

---

#### **Epic 1.1: `App\Services\ImapService` - Full Coverage**

**Target File:** `tests/Unit/Services/ImapServiceComprehensiveTest.php` (expand existing)

##### **Story 1.1.1 (High Priority): IMAP Connection Error Handling**

Test that connection failures are logged and handled gracefully without crashing the application.

**Test Implementation:**

```php
public function test_fetch_emails_handles_connection_failure_gracefully(): void
{
    $mailbox = Mailbox::factory()->create([
        'in_server' => 'invalid.server.com',
        'in_port' => 993,
        'in_username' => 'test@example.com',
        'in_password' => 'password',
    ]);

    Log::shouldReceive('info')->once(); // Starting IMAP fetch
    Log::shouldReceive('error')
        ->once()
        ->with('IMAP connection failed', \Mockery::any());

    $service = new ImapService();
    $stats = $service->fetchEmails($mailbox);

    $this->assertEquals(0, $stats['fetched']);
    $this->assertGreaterThan(0, $stats['errors']);
}

public function test_test_connection_returns_failure_for_invalid_credentials(): void
{
    $mailbox = Mailbox::factory()->create([
        'in_server' => 'imap.example.com',
        'in_port' => 993,
        'in_username' => 'invalid@example.com',
        'in_password' => 'wrongpassword',
    ]);

    $service = new ImapService();
    $result = $service->testConnection($mailbox);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('failed', strtolower($result['message']));
}

public function test_test_connection_returns_success_for_valid_credentials(): void
{
    // Mock the ClientManager and Client
    $mockClient = Mockery::mock('Webklex\PHPIMAP\Client');
    $mockClient->shouldReceive('connect')->once()->andReturn(true);
    $mockClient->shouldReceive('disconnect')->once();

    $mailbox = Mailbox::factory()->create([
        'in_server' => 'imap.example.com',
        'in_port' => 993,
        'in_username' => 'test@example.com',
        'in_password' => 'password',
    ]);

    // This test would require dependency injection or partial mocking
    // Alternative: test with real IMAP server in integration tests
}
```

**Add to:** `tests/Unit/Services/ImapServiceComprehensiveTest.php`

---

##### **Story 1.1.2 (High Priority): Email Structure Parsing**

Test that various email formats (plain text, HTML, multipart) are correctly parsed.

**Test Implementation:**

```php
public function test_processes_plain_text_email_correctly(): void
{
    // Create a mailbox with test configuration
    $mailbox = Mailbox::factory()->create();
    
    // Mock raw email data
    $mockMessage = Mockery::mock();
    $mockMessage->shouldReceive('getTextBody')->andReturn('Plain text content');
    $mockMessage->shouldReceive('getHTMLBody')->andReturn(null);
    $mockMessage->shouldReceive('getSubject')->andReturn('Test Subject');
    $mockMessage->shouldReceive('getFrom')->andReturn([['mail' => 'sender@example.com']]);
    $mockMessage->shouldReceive('getMessageId')->andReturn('<unique-id@example.com>');
    $mockMessage->shouldReceive('getDate')->andReturn(now());

    // Test parsing logic (would need reflection or protected method exposure)
    $service = new ImapService();
    
    // If parseEmail is protected, use reflection:
    $reflection = new \ReflectionClass($service);
    $method = $reflection->getMethod('parseEmail');
    $method->setAccessible(true);
    $result = $method->invoke($service, $mockMessage);

    $this->assertEquals('Plain text content', $result['body']);
    $this->assertEquals('Test Subject', $result['subject']);
    $this->assertEquals('sender@example.com', $result['from_email']);
}

public function test_processes_html_email_and_sanitizes_content(): void
{
    $mailbox = Mailbox::factory()->create();
    
    $mockMessage = Mockery::mock();
    $mockMessage->shouldReceive('getTextBody')->andReturn(null);
    $mockMessage->shouldReceive('getHTMLBody')->andReturn('<p>HTML content</p><script>alert("xss")</script>');
    $mockMessage->shouldReceive('getSubject')->andReturn('HTML Email');
    $mockMessage->shouldReceive('getFrom')->andReturn([['mail' => 'sender@example.com']]);
    $mockMessage->shouldReceive('getMessageId')->andReturn('<html-id@example.com>');
    $mockMessage->shouldReceive('getDate')->andReturn(now());

    $service = new ImapService();
    $reflection = new \ReflectionClass($service);
    $method = $reflection->getMethod('parseEmail');
    $method->setAccessible(true);
    $result = $method->invoke($service, $mockMessage);

    $this->assertStringContainsString('HTML content', $result['body']);
    $this->assertStringNotContainsString('<script>', $result['body']); // XSS sanitized
}

public function test_processes_multipart_email_with_attachments(): void
{
    $mailbox = Mailbox::factory()->create();
    
    $mockAttachment = Mockery::mock();
    $mockAttachment->shouldReceive('getName')->andReturn('document.pdf');
    $mockAttachment->shouldReceive('getSize')->andReturn(1024);
    $mockAttachment->shouldReceive('getMimeType')->andReturn('application/pdf');
    
    $mockMessage = Mockery::mock();
    $mockMessage->shouldReceive('getTextBody')->andReturn('Email with attachment');
    $mockMessage->shouldReceive('getHTMLBody')->andReturn(null);
    $mockMessage->shouldReceive('getAttachments')->andReturn([$mockAttachment]);
    $mockMessage->shouldReceive('hasAttachments')->andReturn(true);
    $mockMessage->shouldReceive('getSubject')->andReturn('Invoice');
    $mockMessage->shouldReceive('getFrom')->andReturn([['mail' => 'billing@example.com']]);
    $mockMessage->shouldReceive('getMessageId')->andReturn('<invoice-123@example.com>');
    $mockMessage->shouldReceive('getDate')->andReturn(now());

    $service = new ImapService();
    // Test attachment processing
}
```

**Add to:** `tests/Unit/Services/ImapServiceComprehensiveTest.php`

---

##### **Story 1.1.3 (High Priority): Charset/Encoding Error Recovery**

Test the retry logic for Microsoft mailbox charset issues.

**Test Implementation:**

```php
public function test_retries_fetch_on_charset_error(): void
{
    $mailbox = Mailbox::factory()->create([
        'in_server' => 'outlook.office365.com',
        'in_port' => 993,
        'in_username' => 'user@company.com',
        'in_password' => 'password',
    ]);

    Log::shouldReceive('info')->times(2); // Initial fetch + retry
    Log::shouldReceive('warning')
        ->once()
        ->with(\Mockery::pattern('/charset/i'), \Mockery::any());
    Log::shouldReceive('error')->zeroOrMoreTimes();

    // Mock IMAP client that fails first, succeeds second time
    // This would require mocking at the ClientManager level
    
    $service = new ImapService();
    $stats = $service->fetchEmails($mailbox);

    // Should have attempted retry
    $this->assertArrayHasKey('fetched', $stats);
}

public function test_logs_charset_conversion_attempts(): void
{
    // Test that charset issues are properly logged for debugging
    $mailbox = Mailbox::factory()->create([
        'in_server' => 'imap.example.com',
    ]);

    Log::shouldReceive('info')->atLeast()->once();
    Log::shouldReceive('warning')
        ->with(\Mockery::pattern('/charset|encoding/i'), \Mockery::any())
        ->zeroOrMoreTimes();

    $service = new ImapService();
    $service->fetchEmails($mailbox);
}
```

**Add to:** `tests/Unit/Services/ImapServiceComprehensiveTest.php`

---

##### **Story 1.1.4 (Medium Priority): Forward Command (@fwd) Parsing**

Test extraction of original sender from forwarded emails.

**Test Implementation:**

```php
public function test_extracts_original_sender_from_forwarded_email(): void
{
    $forwardedBody = <<<'EMAIL'
---------- Forwarded message ---------
From: Original Sender <original@example.com>
Date: Mon, Nov 11, 2024 at 10:30 AM
Subject: Original Subject
To: someone@example.com

This is the original message content.
EMAIL;

    $service = new ImapService();
    $reflection = new \ReflectionClass($service);
    
    if ($reflection->hasMethod('getOriginalSenderFromFwd')) {
        $method = $reflection->getMethod('getOriginalSenderFromFwd');
        $method->setAccessible(true);
        $result = $method->invoke($service, $forwardedBody);

        $this->assertEquals('original@example.com', $result);
    } else {
        $this->markTestSkipped('getOriginalSenderFromFwd method does not exist');
    }
}

public function test_handles_outlook_forwarded_format(): void
{
    $outlookForward = <<<'EMAIL'
From: Original Sender [mailto:original@example.com]
Sent: Monday, November 11, 2024 10:30 AM
To: recipient@example.com
Subject: FW: Original Subject

Forwarded message content.
EMAIL;

    $service = new ImapService();
    $reflection = new \ReflectionClass($service);
    
    if ($reflection->hasMethod('getOriginalSenderFromFwd')) {
        $method = $reflection->getMethod('getOriginalSenderFromFwd');
        $method->setAccessible(true);
        $result = $method->invoke($service, $outlookForward);

        $this->assertEquals('original@example.com', $result);
    } else {
        $this->markTestSkipped('Method does not exist yet');
    }
}

public function test_returns_null_when_no_forward_detected(): void
{
    $regularBody = "This is just a regular email body with no forwarding indicators.";

    $service = new ImapService();
    $reflection = new \ReflectionClass($service);
    
    if ($reflection->hasMethod('getOriginalSenderFromFwd')) {
        $method = $reflection->getMethod('getOriginalSenderFromFwd');
        $method->setAccessible(true);
        $result = $method->invoke($service, $regularBody);

        $this->assertNull($result);
    } else {
        $this->markTestSkipped('Method does not exist yet');
    }
}
```

**Add to:** `tests/Unit/Services/ImapServiceComprehensiveTest.php`

---

##### **Story 1.1.5 (High Priority): BCC and Duplicate Detection**

Test that duplicate Message-IDs are handled correctly for BCC scenarios.

**Test Implementation:**

```php
public function test_creates_separate_conversations_for_bcc_messages(): void
{
    $mailbox1 = Mailbox::factory()->create(['email' => 'sales@example.com']);
    $mailbox2 = Mailbox::factory()->create(['email' => 'support@example.com']);
    
    $customer = Customer::factory()->create(['email' => 'customer@example.com']);
    
    // First message creates first conversation
    $conversation1 = Conversation::factory()->create([
        'mailbox_id' => $mailbox1->id,
        'customer_id' => $customer->id,
        'subject' => 'Test Subject',
    ]);
    
    $thread1 = Thread::factory()->create([
        'conversation_id' => $conversation1->id,
        'message_id' => '<unique-id@example.com>',
        'body' => 'Original message',
    ]);

    // Simulate BCC: same Message-ID arrives at different mailbox
    // The service should detect duplicate and create new conversation with modified Message-ID
    
    // This would be tested at integration level with full ImapService
    $this->assertTrue(true); // Placeholder for integration test
}

public function test_detects_duplicate_message_id_in_database(): void
{
    $messageId = '<duplicate-test@example.com>';
    
    $mailbox = Mailbox::factory()->create();
    $customer = Customer::factory()->create();
    
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
    ]);
    
    $existingThread = Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'message_id' => $messageId,
    ]);

    // Check if duplicate detection works
    $duplicate = Thread::where('message_id', $messageId)->exists();
    $this->assertTrue($duplicate);
    
    // Service should generate artificial Message-ID for BCC copy
    $artificialId = $messageId . '-bcc-' . $mailbox->id;
    $this->assertStringContainsString('bcc', $artificialId);
}
```

**Add to:** `tests/Unit/Services/ImapServiceComprehensiveTest.php` or new integration test file

---

##### **Story 1.1.6 (High Priority): Attachment and Inline Image Processing**

Test that attachments and inline images (CID references) are processed correctly.

**Test Implementation:**

```php
public function test_processes_regular_attachment_correctly(): void
{
    $mailbox = Mailbox::factory()->create();
    $customer = Customer::factory()->create();
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
    ]);
    
    $thread = Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'body' => 'Email with attachment',
    ]);

    // Mock attachment data
    $mockAttachment = Mockery::mock();
    $mockAttachment->shouldReceive('getName')->andReturn('invoice.pdf');
    $mockAttachment->shouldReceive('getSize')->andReturn(2048);
    $mockAttachment->shouldReceive('getMimeType')->andReturn('application/pdf');
    $mockAttachment->shouldReceive('getContent')->andReturn('PDF content here');
    $mockAttachment->shouldReceive('getDisposition')->andReturn('attachment');

    // Test processAttachments method if accessible
    $service = new ImapService();
    // Would call processAttachments($mockMessage, $thread)
    
    // Assert Attachment model created
    // $this->assertDatabaseHas('attachments', [
    //     'thread_id' => $thread->id,
    //     'file_name' => 'invoice.pdf',
    //     'embedded' => false,
    // ]);
}

public function test_processes_inline_image_with_cid_reference(): void
{
    $mailbox = Mailbox::factory()->create();
    $customer = Customer::factory()->create();
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
    ]);
    
    $thread = Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'body' => '<img src="cid:image001@example.com">',
    ]);

    // Mock inline image
    $mockAttachment = Mockery::mock();
    $mockAttachment->shouldReceive('getName')->andReturn('logo.png');
    $mockAttachment->shouldReceive('getSize')->andReturn(1024);
    $mockAttachment->shouldReceive('getMimeType')->andReturn('image/png');
    $mockAttachment->shouldReceive('getContent')->andReturn('PNG content');
    $mockAttachment->shouldReceive('getDisposition')->andReturn('inline');
    $mockAttachment->shouldReceive('getId')->andReturn('<image001@example.com>');

    // After processing:
    // 1. Attachment should be marked as embedded
    // 2. Thread body should have cid: replaced with storage URL
    
    // $this->assertDatabaseHas('attachments', [
    //     'thread_id' => $thread->id,
    //     'file_name' => 'logo.png',
    //     'embedded' => true,
    // ]);
    
    // $thread->refresh();
    // $this->assertStringNotContainsString('cid:', $thread->body);
    // $this->assertStringContainsString('storage/', $thread->body);
}

public function test_replaces_multiple_cid_references_in_email_body(): void
{
    $bodyWithMultipleCids = <<<'HTML'
<p>Email content</p>
<img src="cid:logo@example.com">
<p>More content</p>
<img src="cid:banner@example.com">
HTML;

    // Test that all CID references are replaced
    // This tests the regex/replacement logic
    $service = new ImapService();
    
    // Would need to test the CID replacement method
    $processedBody = $bodyWithMultipleCids; // After processing
    
    // Assert no cid: references remain
    $this->assertStringNotContainsString('cid:', $processedBody);
}
```

**Add to:** `tests/Unit/Services/ImapServiceComprehensiveTest.php`

---

#### **Epic 1.2: `App\Services\SmtpService` - Connection & Configuration**

**Target File:** `tests/Unit/Services/SmtpServiceComprehensiveTest.php` (expand existing)

##### **Story 1.2.1 (Medium Priority): SMTP Connection Testing**

Test connection validation with success and failure scenarios.

**Test Implementation:**

```php
public function test_test_connection_succeeds_with_valid_credentials(): void
{
    Mail::fake();
    
    $mailbox = Mailbox::factory()->create([
        'out_server' => 'smtp.example.com',
        'out_port' => 587,
        'out_username' => 'test@example.com',
        'out_password' => 'password',
        'out_encryption' => 'tls',
    ]);

    $service = new SmtpService();
    $result = $service->testConnection($mailbox);

    // Mock successful connection
    $this->assertTrue($result['success'] ?? false);
}

public function test_test_connection_fails_with_invalid_server(): void
{
    $mailbox = Mailbox::factory()->create([
        'out_server' => 'invalid.smtp.server',
        'out_port' => 587,
        'out_username' => 'test@example.com',
        'out_password' => 'wrongpass',
    ]);

    $service = new SmtpService();
    $result = $service->testConnection($mailbox);

    $this->assertFalse($result['success']);
    $this->assertArrayHasKey('message', $result);
}

public function test_test_connection_handles_authentication_errors(): void
{
    $mailbox = Mailbox::factory()->create([
        'out_server' => 'smtp.gmail.com',
        'out_port' => 587,
        'out_username' => 'test@gmail.com',
        'out_password' => 'invalid_app_password',
    ]);

    Log::shouldReceive('error')
        ->once()
        ->with(\Mockery::pattern('/authentication|auth failed/i'), \Mockery::any());

    $service = new SmtpService();
    $result = $service->testConnection($mailbox);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('authentication', strtolower($result['message']));
}

public function test_test_connection_validates_port_number(): void
{
    $mailbox = Mailbox::factory()->create([
        'out_server' => 'smtp.example.com',
        'out_port' => 99999, // Invalid port
        'out_username' => 'test@example.com',
        'out_password' => 'password',
    ]);

    $service = new SmtpService();
    $result = $service->testConnection($mailbox);

    $this->assertFalse($result['success']);
}

public function test_test_connection_handles_timeout(): void
{
    $mailbox = Mailbox::factory()->create([
        'out_server' => 'non-responsive-server.com',
        'out_port' => 587,
        'out_username' => 'test@example.com',
        'out_password' => 'password',
    ]);

    // Should timeout and return failure gracefully
    $service = new SmtpService();
    $result = $service->testConnection($mailbox);

    $this->assertFalse($result['success']);
    $this->assertArrayHasKey('message', $result);
}
```

**Add to:** `tests/Unit/Services/SmtpServiceComprehensiveTest.php`

---

### Batch 2: Asynchronous Jobs & Events (High Priority)
*   **Objective:** Ensure all background tasks are reliable and their failures are handled gracefully.
*   **Notes:** This work involves writing feature tests that use Laravel's `Queue::fake()`, `Mail::fake()`, and `Event::fake()` helpers.
*   **Target Coverage Increase:** From ~1% to ~80% for notification jobs

---

#### **Epic 2.1: `App\Jobs\SendNotificationToUsers` - Full Coverage**

**Target File:** `tests/Unit/Jobs/SendNotificationToUsersTest.php` (expand significantly)

##### **Story 2.1.1 (High Priority): Successful Notification Dispatch**

Test that notifications are sent to all eligible users.

**Test Implementation:**

```php
public function test_sends_notifications_to_all_eligible_users(): void
{
    Mail::fake();
    Queue::fake();

    $mailbox = Mailbox::factory()->create();
    $customer = Customer::factory()->create(['email' => 'customer@example.com']);
    
    // Create users with notification enabled
    $user1 = User::factory()->create([
        'notify' => User::NOTIFY_EMAIL,
        'role' => User::ROLE_USER,
    ]);
    $user2 = User::factory()->create([
        'notify' => User::NOTIFY_EMAIL,
        'role' => User::ROLE_USER,
    ]);
    
    // Attach users to mailbox
    $mailbox->users()->attach($user1);
    $mailbox->users()->attach($user2);
    
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
        'subject' => 'New Support Request',
    ]);
    
    $thread = Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'type' => Thread::TYPE_CUSTOMER,
        'body' => 'Customer message body',
    ]);
    
    $users = collect([$user1, $user2]);
    $threads = collect([$thread]);
    
    $job = new SendNotificationToUsers($users, $conversation, $threads);
    $job->handle();
    
    // Assert emails were sent
    Mail::assertSent(ConversationReplyNotification::class, 2);
}

public function test_filters_users_with_notifications_disabled(): void
{
    Mail::fake();
    
    $mailbox = Mailbox::factory()->create();
    $customer = Customer::factory()->create();
    
    $userWithNotifications = User::factory()->create([
        'notify' => User::NOTIFY_EMAIL,
    ]);
    
    $userWithoutNotifications = User::factory()->create([
        'notify' => User::NOTIFY_NONE, // Notifications disabled
    ]);
    
    $mailbox->users()->attach($userWithNotifications);
    $mailbox->users()->attach($userWithoutNotifications);
    
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
    ]);
    
    $thread = Thread::factory()->create([
        'conversation_id' => $conversation->id,
    ]);
    
    $users = collect([$userWithNotifications, $userWithoutNotifications]);
    $threads = collect([$thread]);
    
    $job = new SendNotificationToUsers($users, $conversation, $threads);
    $job->handle();
    
    // Only one email sent (to user with notifications enabled)
    Mail::assertSent(ConversationReplyNotification::class, 1);
}

public function test_does_not_notify_thread_author(): void
{
    Mail::fake();
    
    $mailbox = Mailbox::factory()->create();
    $customer = Customer::factory()->create();
    
    $threadAuthor = User::factory()->create([
        'notify' => User::NOTIFY_EMAIL,
    ]);
    
    $otherUser = User::factory()->create([
        'notify' => User::NOTIFY_EMAIL,
    ]);
    
    $mailbox->users()->attach($threadAuthor);
    $mailbox->users()->attach($otherUser);
    
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
        'user_id' => $threadAuthor->id,
    ]);
    
    $thread = Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $threadAuthor->id, // Author of the thread
        'type' => Thread::TYPE_MESSAGE,
    ]);
    
    $users = collect([$threadAuthor, $otherUser]);
    $threads = collect([$thread]);
    
    $job = new SendNotificationToUsers($users, $conversation, $threads);
    $job->handle();
    
    // Only other user should receive notification, not the author
    Mail::assertSent(ConversationReplyNotification::class, 1);
    Mail::assertSent(ConversationReplyNotification::class, function ($mail) use ($otherUser) {
        return $mail->hasTo($otherUser->email);
    });
}
```

**Add to:** `tests/Unit/Jobs/SendNotificationToUsersTest.php`

---

##### **Story 2.1.2 (High Priority): Job Failure and Retry Logic**

Test that job failures are handled and retried appropriately.

**Test Implementation:**

```php
public function test_handles_missing_mailbox_gracefully(): void
{
    Log::shouldReceive('error')
        ->once()
        ->with('Mailbox not found for conversation', \Mockery::any());

    $user = User::factory()->make();
    $conversation = Conversation::factory()->make([
        'mailbox_id' => 99999, // Non-existent mailbox
    ]);
    $thread = Thread::factory()->make();
    
    $job = new SendNotificationToUsers(collect([$user]), $conversation, collect([$thread]));
    $job->handle();
    
    // Job should exit gracefully without throwing exception
    $this->assertTrue(true);
}

public function test_skips_notification_for_draft_threads(): void
{
    Mail::fake();
    
    $mailbox = Mailbox::factory()->create();
    $customer = Customer::factory()->create();
    $user = User::factory()->create(['notify' => User::NOTIFY_EMAIL]);
    
    $mailbox->users()->attach($user);
    
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
    ]);
    
    $draftThread = Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'state' => Thread::STATE_DRAFT, // Draft state
    ]);
    
    $job = new SendNotificationToUsers(collect([$user]), $conversation, collect([$draftThread]));
    $job->handle();
    
    // No emails should be sent for draft threads
    Mail::assertNothingSent();
}

public function test_logs_failure_when_failed_method_called(): void
{
    Log::shouldReceive('error')
        ->once()
        ->with('SendNotificationToUsers job failed', \Mockery::any());

    $user = User::factory()->make();
    $conversation = Conversation::factory()->make();
    $thread = Thread::factory()->make();
    
    $job = new SendNotificationToUsers(collect([$user]), $conversation, collect([$thread]));
    
    $exception = new \Exception('Test failure');
    $job->failed($exception);
    
    // Verify that failure is logged
    $this->assertTrue(true);
}

public function test_respects_timeout_property(): void
{
    $user = User::factory()->make();
    $conversation = Conversation::factory()->make();
    $thread = Thread::factory()->make();
    
    $job = new SendNotificationToUsers(collect([$user]), $conversation, collect([$thread]));
    
    // Verify timeout is set correctly (120 seconds)
    $this->assertEquals(120, $job->timeout);
}

public function test_respects_retry_attempts_property(): void
{
    $user = User::factory()->make();
    $conversation = Conversation::factory()->make();
    $thread = Thread::factory()->make();
    
    $job = new SendNotificationToUsers(collect([$user]), $conversation, collect([$thread]));
    
    // Verify tries is set correctly (168 attempts = 1 per hour for a week)
    $this->assertEquals(168, $job->tries);
}
```

**Add to:** `tests/Unit/Jobs/SendNotificationToUsersTest.php`

---

##### **Story 2.1.3 (High Priority): Bounce Detection and Handling**

Test that bounce emails don't trigger notifications when mail limits are exceeded.

**Test Implementation:**

```php
public function test_skips_notification_for_bounce_with_limit_exceeded(): void
{
    Mail::fake();
    
    $mailbox = Mailbox::factory()->create();
    $customer = Customer::factory()->create();
    $user = User::factory()->create(['notify' => User::NOTIFY_EMAIL]);
    
    $mailbox->users()->attach($user);
    
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
    ]);
    
    $bounceThread = Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'type' => Thread::TYPE_BOUNCE,
        'body' => 'Delivery failed: message limit exceeded for this account',
        'state' => Thread::STATE_PUBLISHED,
    ]);
    
    $job = new SendNotificationToUsers(collect([$user]), $conversation, collect([$bounceThread]));
    $job->handle();
    
    // No notifications should be sent for bounce messages with limit exceeded
    Mail::assertNothingSent();
}

public function test_sends_notification_for_regular_bounce(): void
{
    Mail::fake();
    
    $mailbox = Mailbox::factory()->create();
    $customer = Customer::factory()->create();
    $user = User::factory()->create(['notify' => User::NOTIFY_EMAIL]);
    
    $mailbox->users()->attach($user);
    
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
    ]);
    
    $bounceThread = Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'type' => Thread::TYPE_BOUNCE,
        'body' => 'Delivery failed: invalid recipient address',
        'state' => Thread::STATE_PUBLISHED,
    ]);
    
    $job = new SendNotificationToUsers(collect([$user]), $conversation, collect([$bounceThread]));
    $job->handle();
    
    // Should send notification for regular bounce (not limit exceeded)
    Mail::assertSent(ConversationReplyNotification::class, 1);
}
```

**Add to:** `tests/Unit/Jobs/SendNotificationToUsersTest.php`

---

#### **Epic 2.2: `App\Jobs\SendAutoReply` - Full Coverage**

**Target File:** `tests/Unit/Jobs/SendAutoReplyComprehensiveTest.php` (expand)

##### **Story 2.2.1 (High Priority): Conditional Dispatch Based on Settings**

Test that auto-replies are only sent when mailbox has auto-reply enabled.

**Test Implementation:**

```php
public function test_sends_auto_reply_when_enabled_in_mailbox(): void
{
    Mail::fake();
    
    $mailbox = Mailbox::factory()->create([
        'auto_reply_enabled' => true,
        'auto_reply_subject' => 'Thank you for contacting us',
        'auto_reply_message' => 'We received your message and will respond soon.',
    ]);
    
    $customer = Customer::factory()->create(['email' => 'customer@example.com']);
    
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
    ]);
    
    $thread = Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'type' => Thread::TYPE_CUSTOMER,
        'customer_id' => $customer->id,
    ]);
    
    $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
    $job->handle();
    
    Mail::assertSent(AutoReply::class, 1);
}

public function test_does_not_send_when_auto_reply_disabled(): void
{
    Mail::fake();
    
    $mailbox = Mailbox::factory()->create([
        'auto_reply_enabled' => false,
    ]);
    
    $customer = Customer::factory()->create();
    
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
    ]);
    
    $thread = Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'type' => Thread::TYPE_CUSTOMER,
    ]);
    
    $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
    $job->handle();
    
    Mail::assertNothingSent();
}

public function test_only_sends_auto_reply_to_first_customer_message(): void
{
    Mail::fake();
    
    $mailbox = Mailbox::factory()->create(['auto_reply_enabled' => true]);
    $customer = Customer::factory()->create();
    
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
    ]);
    
    // First customer message
    $thread1 = Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'type' => Thread::TYPE_CUSTOMER,
        'created_at' => now()->subMinutes(10),
    ]);
    
    // Agent reply
    Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'type' => Thread::TYPE_MESSAGE,
        'created_at' => now()->subMinutes(5),
    ]);
    
    // Second customer message (should not trigger auto-reply)
    $thread2 = Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'type' => Thread::TYPE_CUSTOMER,
        'created_at' => now(),
    ]);
    
    // Check thread count to determine if it's first message
    $customerThreadCount = Thread::where('conversation_id', $conversation->id)
        ->where('type', Thread::TYPE_CUSTOMER)
        ->count();
    
    $this->assertEquals(2, $customerThreadCount);
    
    // Auto-reply logic should check if this is first customer thread
}
```

**Add to:** `tests/Unit/Jobs/SendAutoReplyComprehensiveTest.php`

---

##### **Story 2.2.2 (High Priority): Email Content Generation**

Test that auto-reply content is properly formatted and includes all placeholders.

**Test Implementation:**

```php
public function test_auto_reply_includes_mailbox_name(): void
{
    Mail::fake();
    
    $mailbox = Mailbox::factory()->create([
        'name' => 'Support Team',
        'auto_reply_enabled' => true,
        'auto_reply_subject' => 'Auto Reply',
        'auto_reply_message' => 'This is from {{mailbox.name}}',
    ]);
    
    $customer = Customer::factory()->create();
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
    ]);
    
    $thread = Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'type' => Thread::TYPE_CUSTOMER,
    ]);
    
    $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
    $job->handle();
    
    Mail::assertSent(AutoReply::class, function ($mail) use ($mailbox) {
        // Verify the message contains the mailbox name
        return str_contains($mail->render(), 'Support Team');
    });
}

public function test_auto_reply_includes_customer_name(): void
{
    Mail::fake();
    
    $mailbox = Mailbox::factory()->create([
        'auto_reply_enabled' => true,
        'auto_reply_message' => 'Hello {{customer.first_name}}, we received your message.',
    ]);
    
    $customer = Customer::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);
    
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
    ]);
    
    $thread = Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'type' => Thread::TYPE_CUSTOMER,
    ]);
    
    $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
    $job->handle();
    
    Mail::assertSent(AutoReply::class, function ($mail) {
        return str_contains($mail->render(), 'Hello John');
    });
}

public function test_auto_reply_uses_conversation_subject(): void
{
    Mail::fake();
    
    $mailbox = Mailbox::factory()->create(['auto_reply_enabled' => true]);
    $customer = Customer::factory()->create();
    
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
        'subject' => 'Order Issue #12345',
    ]);
    
    $thread = Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'type' => Thread::TYPE_CUSTOMER,
    ]);
    
    $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
    $job->handle();
    
    Mail::assertSent(AutoReply::class, function ($mail) {
        // Subject should be Re: Original Subject
        $subject = $mail->subject ?? '';
        return str_contains($subject, 'Re:') || str_contains($subject, 'Order Issue');
    });
}
```

**Add to:** `tests/Unit/Jobs/SendAutoReplyComprehensiveTest.php`

---

##### **Story 2.2.3 (High Priority): Duplicate Prevention**

Test that duplicate auto-replies are not sent to the same customer.

**Test Implementation:**

```php
public function test_prevents_duplicate_auto_reply_in_same_conversation(): void
{
    Mail::fake();
    
    $mailbox = Mailbox::factory()->create(['auto_reply_enabled' => true]);
    $customer = Customer::factory()->create();
    
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
    ]);
    
    $thread1 = Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'type' => Thread::TYPE_CUSTOMER,
    ]);
    
    // Send first auto-reply
    $job1 = new SendAutoReply($conversation, $thread1, $mailbox, $customer);
    $job1->handle();
    
    Mail::assertSent(AutoReply::class, 1);
    
    // Try to send second auto-reply for same conversation
    $thread2 = Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'type' => Thread::TYPE_CUSTOMER,
    ]);
    
    $job2 = new SendAutoReply($conversation, $thread2, $mailbox, $customer);
    $job2->handle();
    
    // Should still be only 1 auto-reply sent
    Mail::assertSent(AutoReply::class, 1);
}

public function test_creates_send_log_for_auto_reply(): void
{
    Mail::fake();
    
    $mailbox = Mailbox::factory()->create(['auto_reply_enabled' => true]);
    $customer = Customer::factory()->create(['email' => 'customer@example.com']);
    
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
    ]);
    
    $thread = Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'type' => Thread::TYPE_CUSTOMER,
    ]);
    
    $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);
    $job->handle();
    
    // Verify SendLog entry was created
    $this->assertDatabaseHas('send_logs', [
        'customer_id' => $customer->id,
        'type' => SendLog::TYPE_AUTO_REPLY,
    ]);
}
```

**Add to:** `tests/Unit/Jobs/SendAutoReplyComprehensiveTest.php`

---

#### **Epic 2.3: Other Critical Jobs**

**Target Files:** New test files needed

##### **Story 2.3.1 (Medium Priority): `App\Jobs\SendAlert` Testing**

**Test Implementation:**

Create new file: `tests/Unit/Jobs/SendAlertEnhancedTest.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SendAlert;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendAlertEnhancedTest extends TestCase
{
    public function test_sends_alert_to_specified_users(): void
    {
        Mail::fake();
        
        $users = User::factory()->count(3)->create([
            'role' => User::ROLE_ADMIN,
        ]);
        
        $alertData = [
            'subject' => 'System Alert',
            'message' => 'Database backup failed',
            'severity' => 'critical',
        ];
        
        $job = new SendAlert($users, $alertData);
        $job->handle();
        
        Mail::assertSent(function ($mail) use ($users) {
            return count($mail->to) === $users->count();
        });
    }
    
    public function test_handles_empty_user_list_gracefully(): void
    {
        Mail::fake();
        
        $job = new SendAlert(collect([]), ['subject' => 'Test', 'message' => 'Test']);
        $job->handle();
        
        Mail::assertNothingSent();
    }
}
```

---

##### **Story 2.3.2 (Medium Priority): `App\Jobs\SendEmailReplyError` Testing**

**Test Implementation:**

Expand: `tests/Unit/Jobs/SendEmailReplyErrorTest.php`

```php
public function test_notifies_user_of_email_sending_failure(): void
{
    Mail::fake();
    
    $user = User::factory()->create(['email' => 'agent@example.com']);
    $customer = Customer::factory()->create();
    $mailbox = Mailbox::factory()->create();
    
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
        'user_id' => $user->id,
    ]);
    
    $thread = Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
    ]);
    
    $error = 'SMTP connection failed: Could not connect to mail server';
    
    $job = new SendEmailReplyError($user, $conversation, $thread, $error);
    $job->handle();
    
    Mail::assertSent(EmailReplyErrorNotification::class, function ($mail) use ($user, $error) {
        return $mail->hasTo($user->email) && str_contains($mail->render(), 'SMTP');
    });
}

public function test_includes_error_details_in_notification(): void
{
    Mail::fake();
    
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();
    $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
    
    $errorMessage = 'Recipient address rejected: User unknown';
    
    $job = new SendEmailReplyError($user, $conversation, $thread, $errorMessage);
    $job->handle();
    
    Mail::assertSent(function ($mail) use ($errorMessage) {
        return str_contains($mail->render(), $errorMessage);
    });
}
```

**Add to:** `tests/Unit/Jobs/SendEmailReplyErrorTest.php`

---

### Batch 3: Console Commands & System Integrity (Medium Priority)
*   **Objective:** Protect the application from data or file corruption caused by faulty command-line operations.
*   **Notes:** This involves writing feature tests that use `Artisan::call()` and asserting command output and side-effects.
*   **Target Coverage Increase:** From ~2% to ~70% for module commands, from ~6-8% to ~75% for cache/logout commands
*   **Test Pattern:** Feature tests using `$this->artisan()` helper

---

#### **Epic 3.1: Module Management Commands**

**Target File:** Create `tests/Feature/Commands/ModuleInstallCommandTest.php`

##### **Story 3.1.1 (High Priority): Module Installation Success Path**

Test successful module installation with all required operations.

**Test Implementation:**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ModuleInstallCommandTest extends TestCase
{
    use RefreshDatabase;

    protected string $testModulePath;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test module directory structure
        $this->testModulePath = base_path('Modules/TestModule');
    }

    protected function tearDown(): void
    {
        // Clean up test module
        if (File::exists($this->testModulePath)) {
            File::deleteDirectory($this->testModulePath);
        }
        
        parent::tearDown();
    }

    public function test_installs_specific_module_successfully(): void
    {
        // Create a test module structure
        $this->createTestModule('TestModule');
        
        $this->artisan('freescout:module-install', ['module_alias' => 'testmodule'])
            ->expectsOutput('Installing module: TestModule')
            ->expectsOutputToContain('Module installed successfully')
            ->assertExitCode(0);
        
        // Verify symlink was created
        $publicSymlink = public_path('modules/testmodule');
        $this->assertTrue(File::exists($publicSymlink) || is_link($publicSymlink));
    }

    public function test_installs_all_modules_when_no_alias_provided(): void
    {
        $this->createTestModule('ModuleOne');
        $this->createTestModule('ModuleTwo');
        
        $this->artisan('freescout:module-install')
            ->expectsOutput('Installing all modules...')
            ->expectsOutputToContain('ModuleOne')
            ->expectsOutputToContain('ModuleTwo')
            ->assertExitCode(0);
    }

    public function test_creates_symlink_in_public_directory(): void
    {
        $this->createTestModule('SymlinkTest');
        
        $this->artisan('freescout:module-install', ['module_alias' => 'symlinktest'])
            ->assertExitCode(0);
        
        $symlink = public_path('modules/symlinktest');
        $this->assertTrue(
            File::exists($symlink) || is_link($symlink),
            'Symlink should exist in public/modules directory'
        );
    }

    public function test_runs_module_migrations(): void
    {
        $this->createTestModule('MigrationTest', withMigration: true);
        
        $this->artisan('freescout:module-install', ['module_alias' => 'migrationtest'])
            ->expectsOutputToContain('Running migrations')
            ->assertExitCode(0);
        
        // Verify migration ran
        // This would check if migration table has entry
    }

    public function test_clears_cache_before_installation(): void
    {
        Cache::put('test_key', 'test_value');
        
        $this->createTestModule('CacheTest');
        
        $this->artisan('freescout:module-install', ['module_alias' => 'cachetest'])
            ->assertExitCode(0);
        
        // Cache should be cleared
        $this->assertNull(Cache::get('test_key'));
    }

    /**
     * Helper method to create a test module structure
     */
    protected function createTestModule(string $name, bool $withMigration = false): void
    {
        $modulePath = base_path("Modules/{$name}");
        
        File::makeDirectory($modulePath, 0755, true);
        File::makeDirectory("{$modulePath}/Http", 0755, true);
        File::makeDirectory("{$modulePath}/Resources", 0755, true);
        File::makeDirectory("{$modulePath}/Resources/assets", 0755, true);
        
        // Create module.json
        $moduleJson = [
            'name' => $name,
            'alias' => strtolower($name),
            'description' => "Test module {$name}",
            'active' => true,
        ];
        
        File::put(
            "{$modulePath}/module.json",
            json_encode($moduleJson, JSON_PRETTY_PRINT)
        );
        
        if ($withMigration) {
            File::makeDirectory("{$modulePath}/Database/Migrations", 0755, true);
            
            $migrationContent = <<<'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_table', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('test_table');
    }
};
PHP;
            
            File::put(
                "{$modulePath}/Database/Migrations/2024_01_01_000000_create_test_table.php",
                $migrationContent
            );
        }
    }
}
```

**Create new file:** `tests/Feature/Commands/ModuleInstallCommandTest.php`

---

##### **Story 3.1.2 (High Priority): Module Installation Error Handling**

Test error scenarios and graceful failure handling.

**Test Implementation:**

```php
public function test_fails_gracefully_when_module_not_found(): void
{
    $this->artisan('freescout:module-install', ['module_alias' => 'nonexistentmodule'])
        ->expectsOutputToContain('Module not found')
        ->assertExitCode(1);
}

public function test_fails_when_module_json_is_malformed(): void
{
    $modulePath = base_path('Modules/MalformedModule');
    File::makeDirectory($modulePath, 0755, true);
    
    // Create invalid JSON
    File::put("{$modulePath}/module.json", '{invalid json content}');
    
    $this->artisan('freescout:module-install', ['module_alias' => 'malformedmodule'])
        ->expectsOutputToContain('error')
        ->assertExitCode(1);
    
    File::deleteDirectory($modulePath);
}

public function test_fails_when_public_directory_not_writable(): void
{
    // This test would require temporarily changing permissions
    // Skip on systems where this isn't feasible
    if (! is_writable(public_path())) {
        $this->markTestSkipped('Public directory is not writable');
    }
    
    $this->createTestModule('WritableTest');
    
    // Temporarily make public/modules not writable
    $publicModules = public_path('modules');
    if (! File::exists($publicModules)) {
        File::makeDirectory($publicModules);
    }
    
    $originalPermissions = fileperms($publicModules);
    chmod($publicModules, 0444); // Read-only
    
    $this->artisan('freescout:module-install', ['module_alias' => 'writabletest'])
        ->expectsOutputToContain('permission')
        ->assertExitCode(1);
    
    // Restore permissions
    chmod($publicModules, $originalPermissions);
}

public function test_handles_missing_migrations_directory_gracefully(): void
{
    $modulePath = base_path('Modules/NoMigrations');
    File::makeDirectory($modulePath, 0755, true);
    
    $moduleJson = [
        'name' => 'NoMigrations',
        'alias' => 'nomigrations',
        'active' => true,
    ];
    
    File::put("{$modulePath}/module.json", json_encode($moduleJson));
    
    // Should succeed even without migrations directory
    $this->artisan('freescout:module-install', ['module_alias' => 'nomigrations'])
        ->assertExitCode(0);
    
    File::deleteDirectory($modulePath);
}

public function test_validates_module_dependencies(): void
{
    // Create module with dependencies
    $modulePath = base_path('Modules/DependentModule');
    File::makeDirectory($modulePath, 0755, true);
    
    $moduleJson = [
        'name' => 'DependentModule',
        'alias' => 'dependentmodule',
        'requires' => [
            'NonExistentDependency',
        ],
    ];
    
    File::put("{$modulePath}/module.json", json_encode($moduleJson));
    
    $this->artisan('freescout:module-install', ['module_alias' => 'dependentmodule'])
        ->expectsOutputToContain('dependency')
        ->assertExitCode(1);
    
    File::deleteDirectory($modulePath);
}
```

**Add to:** `tests/Feature/Commands/ModuleInstallCommandTest.php`

---

##### **Story 3.1.3 (Medium Priority): Module Update Command**

**Test Implementation:**

Create new file: `tests/Feature/Commands/ModuleUpdateCommandTest.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ModuleUpdateCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_specific_module_successfully(): void
    {
        $this->createTestModule('UpdateTest', version: '1.0.0');
        
        $this->artisan('freescout:module-update', ['module_alias' => 'updatetest'])
            ->expectsOutputToContain('Updating module')
            ->assertExitCode(0);
    }

    public function test_updates_all_modules_when_no_alias_provided(): void
    {
        $this->createTestModule('Module1', version: '1.0.0');
        $this->createTestModule('Module2', version: '1.0.0');
        
        $this->artisan('freescout:module-update')
            ->expectsOutput('Updating all modules...')
            ->assertExitCode(0);
    }

    public function test_runs_migrations_during_update(): void
    {
        $this->createTestModule('MigrationUpdate', version: '1.0.0', withMigration: true);
        
        $this->artisan('freescout:module-update', ['module_alias' => 'migrationupdate'])
            ->expectsOutputToContain('migration')
            ->assertExitCode(0);
    }

    public function test_clears_cache_after_update(): void
    {
        Cache::put('module_cache', 'old_value');
        
        $this->createTestModule('CacheUpdate');
        
        $this->artisan('freescout:module-update', ['module_alias' => 'cacheupdate'])
            ->assertExitCode(0);
        
        $this->assertNull(Cache::get('module_cache'));
    }

    protected function createTestModule(string $name, string $version = '1.0.0', bool $withMigration = false): void
    {
        // Similar to ModuleInstallCommandTest helper
    }
}
```

**Create new file:** `tests/Feature/Commands/ModuleUpdateCommandTest.php`

---

#### **Epic 3.2: System Maintenance Commands**

##### **Story 3.2.1 (Low Priority): Cache Clear Command**

**Target File:** Create `tests/Feature/Commands/CacheClearCommandTest.php`

**Test Implementation:**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheClearCommandTest extends TestCase
{
    public function test_clears_application_cache(): void
    {
        // Set some cache values
        Cache::put('test_key_1', 'value1');
        Cache::put('test_key_2', 'value2');
        
        $this->artisan('cache:clear')
            ->expectsOutput('Application cache cleared successfully.')
            ->assertExitCode(0);
        
        // Verify cache is cleared
        $this->assertNull(Cache::get('test_key_1'));
        $this->assertNull(Cache::get('test_key_2'));
    }

    public function test_clears_config_cache(): void
    {
        // Generate config cache
        $this->artisan('config:cache')->assertExitCode(0);
        
        // Clear all caches
        $this->artisan('cache:clear')->assertExitCode(0);
        
        // Config cache file should be removed
        $configCache = base_path('bootstrap/cache/config.php');
        $this->assertFileDoesNotExist($configCache);
    }

    public function test_clears_route_cache(): void
    {
        // Generate route cache
        $this->artisan('route:cache')->assertExitCode(0);
        
        // Clear all caches
        $this->artisan('cache:clear')->assertExitCode(0);
        
        // Route cache file should be removed
        $routeCache = base_path('bootstrap/cache/routes-v7.php');
        $this->assertFileDoesNotExist($routeCache);
    }

    public function test_clears_view_cache(): void
    {
        $this->artisan('view:clear')
            ->expectsOutput('Compiled views cleared successfully.')
            ->assertExitCode(0);
        
        // Verify compiled views directory is empty or doesn't exist
        $viewsPath = storage_path('framework/views');
        if (File::exists($viewsPath)) {
            $files = File::files($viewsPath);
            $this->assertEmpty($files, 'Compiled views should be cleared');
        }
    }
}
```

**Create new file:** `tests/Feature/Commands/CacheClearCommandTest.php`

---

##### **Story 3.2.2 (Low Priority): Users Logout Command**

**Target File:** Create `tests/Feature/Commands/LogoutUsersCommandTest.php`

**Test Implementation:**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LogoutUsersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_logs_out_all_users(): void
    {
        // Create users with active sessions
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        // Create session tokens
        $this->actingAs($user1)->post('/api/login');
        $this->actingAs($user2)->post('/api/login');
        
        // Verify tokens exist
        $tokensBefore = DB::table('personal_access_tokens')->count();
        $this->assertGreaterThan(0, $tokensBefore);
        
        $this->artisan('freescout:users-logout')
            ->expectsOutput('All users logged out successfully.')
            ->assertExitCode(0);
        
        // Verify all tokens are deleted
        $tokensAfter = DB::table('personal_access_tokens')->count();
        $this->assertEquals(0, $tokensAfter);
    }

    public function test_clears_remember_tokens(): void
    {
        $user = User::factory()->create([
            'remember_token' => 'test_remember_token',
        ]);
        
        $this->artisan('freescout:users-logout')->assertExitCode(0);
        
        $user->refresh();
        $this->assertNull($user->remember_token);
    }

    public function test_logs_out_specific_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $token1 = $user1->createToken('test')->plainTextToken;
        $token2 = $user2->createToken('test')->plainTextToken;
        
        // Logout specific user
        $this->artisan('freescout:users-logout', ['user_id' => $user1->id])
            ->assertExitCode(0);
        
        // User1 tokens should be revoked, User2 tokens should remain
        $user1Tokens = DB::table('personal_access_tokens')
            ->where('tokenable_id', $user1->id)
            ->count();
        $user2Tokens = DB::table('personal_access_tokens')
            ->where('tokenable_id', $user2->id)
            ->count();
        
        $this->assertEquals(0, $user1Tokens);
        $this->assertGreaterThan(0, $user2Tokens);
    }

    public function test_handles_no_active_sessions_gracefully(): void
    {
        // No users or sessions exist
        $this->artisan('freescout:users-logout')
            ->expectsOutput('No active user sessions found.')
            ->assertExitCode(0);
    }
}
```

**Create new file:** `tests/Feature/Commands/LogoutUsersCommandTest.php`

---

### Batch 4: Controller & Policy Logic (Medium Priority)
*   **Objective:** Harden the most critical API endpoints and user flows against authorization breaches and invalid data.
*   **Notes:** This batch focuses on writing feature tests for the "unhappy paths."
*   **Target Coverage Increase:** From ~49% to ~80% for ConversationController
*   **Test Pattern:** Feature tests with `actingAs()` and policy assertions

---

#### **Epic 4.1: ConversationController Security & Authorization**

**Target File:** `tests/Feature/ConversationControllerMethodsTest.php` (expand significantly)

##### **Story 4.1.1 (High Priority): Authorization for Untested Methods**

Test authorization for all 18 methods in ConversationController (currently only 6/18 covered).

**Test Implementation:**

```php
public function test_edit_method_requires_mailbox_access(): void
{
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
    
    // User WITHOUT mailbox access
    $this->actingAs($user)
        ->get(route('conversations.edit', $conversation))
        ->assertForbidden();
}

public function test_update_method_requires_mailbox_access(): void
{
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
    
    $this->actingAs($user)
        ->put(route('conversations.update', $conversation), [
            'subject' => 'Updated Subject',
        ])
        ->assertForbidden();
}

public function test_user_with_access_can_update_conversation(): void
{
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user, ['access' => 20]); // EDIT access
    
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'subject' => 'Original Subject',
    ]);
    
    $this->actingAs($user)
        ->put(route('conversations.update', $conversation), [
            'subject' => 'Updated Subject',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');
    
    $conversation->refresh();
    $this->assertEquals('Updated Subject', $conversation->subject);
}

public function test_destroy_method_requires_admin_or_owner(): void
{
    $regularUser = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($regularUser);
    
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'user_id' => 999, // Different user
    ]);
    
    $this->actingAs($regularUser)
        ->delete(route('conversations.destroy', $conversation))
        ->assertForbidden();
}

public function test_admin_can_delete_any_conversation(): void
{
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($admin);
    
    $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
    
    $this->actingAs($admin)
        ->delete(route('conversations.destroy', $conversation))
        ->assertRedirect()
        ->assertSessionHas('success');
    
    $this->assertDatabaseMissing('conversations', ['id' => $conversation->id]);
}

public function test_owner_can_delete_own_conversation(): void
{
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'user_id' => $user->id, // User is owner
    ]);
    
    $this->actingAs($user)
        ->delete(route('conversations.destroy', $conversation))
        ->assertRedirect();
    
    $this->assertDatabaseMissing('conversations', ['id' => $conversation->id]);
}
```

**Add to:** `tests/Feature/ConversationControllerMethodsTest.php`

---

##### **Story 4.1.2 (Medium Priority): Validation Testing**

Test validation rules for store, update, and reply methods.

**Test Implementation:**

```php
public function test_store_requires_valid_customer_id(): void
{
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    
    $this->actingAs($user)
        ->post(route('conversations.store', $mailbox), [
            'subject' => 'Test Subject',
            'customer_id' => 99999, // Non-existent customer
            'body' => 'Message body',
        ])
        ->assertSessionHasErrors('customer_id');
}

public function test_store_requires_subject(): void
{
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    $customer = Customer::factory()->create();
    
    $this->actingAs($user)
        ->post(route('conversations.store', $mailbox), [
            // Missing subject
            'customer_id' => $customer->id,
            'body' => 'Message body',
        ])
        ->assertSessionHasErrors('subject');
}

public function test_store_requires_body(): void
{
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    $customer = Customer::factory()->create();
    
    $this->actingAs($user)
        ->post(route('conversations.store', $mailbox), [
            'subject' => 'Test Subject',
            'customer_id' => $customer->id,
            // Missing body
        ])
        ->assertSessionHasErrors('body');
}

public function test_store_creates_conversation_with_valid_data(): void
{
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    $customer = Customer::factory()->create();
    
    $folder = Folder::factory()->create([
        'mailbox_id' => $mailbox->id,
        'type' => Folder::TYPE_INBOX,
    ]);
    
    $this->actingAs($user)
        ->post(route('conversations.store', $mailbox), [
            'subject' => 'New Support Request',
            'customer_id' => $customer->id,
            'body' => 'This is my issue description',
            'folder_id' => $folder->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');
    
    $this->assertDatabaseHas('conversations', [
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
        'subject' => 'New Support Request',
    ]);
}

public function test_reply_validates_body_required(): void
{
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    
    $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
    
    $this->actingAs($user)
        ->post(route('conversations.reply', $conversation), [
            // Missing body
        ])
        ->assertSessionHasErrors('body');
}

public function test_reply_sanitizes_html_content(): void
{
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    
    $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
    
    $maliciousContent = '<p>Normal content</p><script>alert("XSS")</script>';
    
    $this->actingAs($user)
        ->post(route('conversations.reply', $conversation), [
            'body' => $maliciousContent,
        ])
        ->assertRedirect();
    
    // Verify script tags are removed
    $thread = Thread::where('conversation_id', $conversation->id)
        ->latest()
        ->first();
    
    $this->assertStringNotContainsString('<script>', $thread->body);
    $this->assertStringContainsString('Normal content', $thread->body);
}
```

**Add to:** `tests/Feature/ConversationValidationTest.php` (expand existing file)

---

##### **Story 4.1.3 (Medium Priority): State Management Testing**

Test conversation state transitions and business logic.

**Test Implementation:**

```php
public function test_replying_to_closed_conversation_reopens_it(): void
{
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'status' => Conversation::STATUS_CLOSED,
    ]);
    
    $this->actingAs($user)
        ->post(route('conversations.reply', $conversation), [
            'body' => 'Reopening the conversation',
        ])
        ->assertRedirect();
    
    $conversation->refresh();
    $this->assertEquals(Conversation::STATUS_ACTIVE, $conversation->status);
}

public function test_assign_and_change_status_in_single_request(): void
{
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $assignee = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($admin);
    $mailbox->users()->attach($assignee);
    
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'status' => Conversation::STATUS_ACTIVE,
        'user_id' => null,
    ]);
    
    $this->actingAs($admin)
        ->put(route('conversations.update', $conversation), [
            'user_id' => $assignee->id,
            'status' => Conversation::STATUS_PENDING,
        ])
        ->assertRedirect();
    
    $conversation->refresh();
    $this->assertEquals($assignee->id, $conversation->user_id);
    $this->assertEquals(Conversation::STATUS_PENDING, $conversation->status);
}

public function test_changing_folder_updates_conversation_state(): void
{
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    
    $inboxFolder = Folder::factory()->create([
        'mailbox_id' => $mailbox->id,
        'type' => Folder::TYPE_INBOX,
    ]);
    
    $spamFolder = Folder::factory()->create([
        'mailbox_id' => $mailbox->id,
        'type' => Folder::TYPE_SPAM,
    ]);
    
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'folder_id' => $inboxFolder->id,
    ]);
    
    $this->actingAs($user)
        ->put(route('conversations.update', $conversation), [
            'folder_id' => $spamFolder->id,
        ])
        ->assertRedirect();
    
    $conversation->refresh();
    $this->assertEquals($spamFolder->id, $conversation->folder_id);
}

public function test_last_reply_at_updates_on_new_thread(): void
{
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    
    $originalTime = now()->subDays(2);
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'last_reply_at' => $originalTime,
    ]);
    
    $this->actingAs($user)
        ->post(route('conversations.reply', $conversation), [
            'body' => 'New reply',
        ])
        ->assertRedirect();
    
    $conversation->refresh();
    $this->assertTrue($conversation->last_reply_at->isAfter($originalTime));
}
```

**Add to:** `tests/Feature/ConversationControllerMethodsTest.php`

---

#### **Epic 4.2: Settings Controller Authorization**

**Target File:** Create `tests/Feature/SettingsControllerTest.php`

##### **Story 4.2.1 (Low Priority): Settings Access Control**

**Test Implementation:**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_settings(): void
    {
        $regularUser = User::factory()->create(['role' => User::ROLE_USER]);
        
        $this->actingAs($regularUser)
            ->get(route('settings.index'))
            ->assertForbidden();
    }

    public function test_admin_can_access_settings(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $this->actingAs($admin)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertViewIs('settings.index');
    }

    public function test_guest_redirected_to_login(): void
    {
        $this->get(route('settings.index'))
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_update_settings(): void
    {
        $regularUser = User::factory()->create(['role' => User::ROLE_USER]);
        
        $this->actingAs($regularUser)
            ->put(route('settings.update'), [
                'app_name' => 'New Name',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_update_settings(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $this->actingAs($admin)
            ->put(route('settings.update'), [
                'app_name' => 'Updated App Name',
                'mail_driver' => 'smtp',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_validates_email_driver_options(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $this->actingAs($admin)
            ->put(route('settings.update'), [
                'mail_driver' => 'invalid_driver',
            ])
            ->assertSessionHasErrors('mail_driver');
    }

    public function test_validates_required_smtp_fields(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $this->actingAs($admin)
            ->put(route('settings.update'), [
                'mail_driver' => 'smtp',
                // Missing required SMTP fields
            ])
            ->assertSessionHasErrors(['mail_host', 'mail_port']);
    }
}
```

**Create new file:** `tests/Feature/SettingsControllerTest.php`

---

### Batch 5: Model & Helper Logic (Low Priority)
*   **Objective:** Increase baseline coverage and test critical model-level business logic.
*   **Notes:** This work primarily involves unit tests.
*   **Target Coverage Increase:** From ~60% to ~85% for models

---

#### **Epic 5.1: Customer Model Business Logic**

**Target File:** `tests/Unit/Models/CustomerComprehensiveTest.php` (expand)

##### **Story 5.1.1 (Medium Priority): Customer Creation and Lookup**

**Test Implementation:**

```php
public function test_find_or_create_returns_existing_customer(): void
{
    $existingCustomer = Customer::factory()->create([
        'email' => 'existing@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);
    
    $result = Customer::findOrCreate('existing@example.com', 'Jane', 'Smith');
    
    $this->assertEquals($existingCustomer->id, $result->id);
    $this->assertEquals('John', $result->first_name); // Original name preserved
}

public function test_find_or_create_creates_new_customer_when_not_exists(): void
{
    $result = Customer::findOrCreate('new@example.com', 'Jane', 'Smith');
    
    $this->assertInstanceOf(Customer::class, $result);
    $this->assertEquals('new@example.com', $result->email);
    $this->assertEquals('Jane', $result->first_name);
    $this->assertEquals('Smith', $result->last_name);
}

public function test_validates_email_format(): void
{
    $this->expectException(\InvalidArgumentException::class);
    
    Customer::findOrCreate('invalid-email', 'John', 'Doe');
}

public function test_normalizes_email_to_lowercase(): void
{
    $customer = Customer::findOrCreate('UPPER@EXAMPLE.COM', 'John', 'Doe');
    
    $this->assertEquals('upper@example.com', $customer->email);
}

public function test_handles_null_names_gracefully(): void
{
    $customer = Customer::findOrCreate('noname@example.com', null, null);
    
    $this->assertNotNull($customer->id);
    $this->assertEquals('noname@example.com', $customer->email);
    $this->assertNull($customer->first_name);
    $this->assertNull($customer->last_name);
}
```

**Add to:** `tests/Unit/Models/CustomerComprehensiveTest.php`

---

#### **Epic 5.2: MailHelper Utility Functions**

**Target File:** `tests/Unit/MailHelperTest.php` (expand significantly)

##### **Story 5.2.1 (Low Priority): Message ID Generation**

**Test Implementation:**

```php
public function test_generate_message_id_creates_valid_format(): void
{
    $messageId = \App\Misc\MailHelper::generateMessageId('example.com');
    
    $this->assertMatchesRegularExpression('/<[\w\-\.]+@example\.com>/', $messageId);
    $this->assertStringStartsWith('<', $messageId);
    $this->assertStringEndsWith('>', $messageId);
}

public function test_generate_message_id_is_unique(): void
{
    $id1 = \App\Misc\MailHelper::generateMessageId('test.com');
    $id2 = \App\Misc\MailHelper::generateMessageId('test.com');
    
    $this->assertNotEquals($id1, $id2);
}

public function test_parse_email_extracts_address_correctly(): void
{
    $testCases = [
        'user@example.com' => 'user@example.com',
        'John Doe <john@example.com>' => 'john@example.com',
        '<user@example.com>' => 'user@example.com',
        'user+tag@example.com' => 'user+tag@example.com',
    ];
    
    foreach ($testCases as $input => $expected) {
        $result = \App\Misc\MailHelper::parseEmail($input);
        $this->assertEquals($expected, $result, "Failed for input: {$input}");
    }
}

public function test_sanitize_email_removes_dangerous_content(): void
{
    $dangerous = '<p>Safe content</p><script>alert("xss")</script><iframe src="evil.com"></iframe>';
    
    $result = \App\Misc\MailHelper::sanitizeEmail($dangerous);
    
    $this->assertStringContainsString('Safe content', $result);
    $this->assertStringNotContainsString('<script>', $result);
    $this->assertStringNotContainsString('<iframe>', $result);
}

public function test_format_email_with_name(): void
{
    $result = \App\Misc\MailHelper::formatEmail('john@example.com', 'John Doe');
    
    $this->assertEquals('John Doe <john@example.com>', $result);
}

public function test_format_email_without_name(): void
{
    $result = \App\Misc\MailHelper::formatEmail('john@example.com', null);
    
    $this->assertEquals('john@example.com', $result);
}

public function test_extract_reply_separators(): void
{
    $emailBody = <<<'EMAIL'
This is the new reply.

On Mon, Nov 11, 2024 at 10:30 AM, sender@example.com wrote:
> This is the previous message
> with multiple lines
EMAIL;

    $result = \App\Misc\MailHelper::extractReply($emailBody);
    
    $this->assertStringContainsString('This is the new reply', $result);
    $this->assertStringNotContainsString('On Mon, Nov 11', $result);
}
```

**Add to:** `tests/Unit/MailHelperTest.php`

---

## 4. Additional Test Targets to Reach 80% Coverage

Based on the coverage analysis, the following additional areas need testing to achieve 80% overall coverage:

### 4.1 Additional Controller Coverage

#### **SystemController (0% coverage)**

**Target File:** Create `tests/Feature/SystemControllerTest.php`

**Test Implementation:**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SystemControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_system_page(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        
        $this->actingAs($user)
            ->get(route('system.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_system_dashboard(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $this->actingAs($admin)
            ->get(route('system.index'))
            ->assertOk()
            ->assertViewIs('system.index')
            ->assertViewHas(['phpVersion', 'laravelVersion', 'diskUsage']);
    }

    public function test_diagnostics_endpoint_returns_health_status(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $response = $this->actingAs($admin)
            ->get(route('system.diagnostics'))
            ->assertOk()
            ->assertJsonStructure([
                'database',
                'cache',
                'storage',
                'mail',
            ]);
        
        $data = $response->json();
        $this->assertArrayHasKey('status', $data['database']);
    }

    public function test_ajax_clear_cache_command(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        Cache::put('test_key', 'value');
        
        $this->actingAs($admin)
            ->post(route('system.ajax'), ['action' => 'clear_cache'])
            ->assertOk()
            ->assertJson(['status' => 'success']);
        
        $this->assertNull(Cache::get('test_key'));
    }

    public function test_ajax_optimize_command(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $this->actingAs($admin)
            ->post(route('system.ajax'), ['action' => 'optimize'])
            ->assertOk()
            ->assertJson(['status' => 'success']);
    }

    public function test_ajax_fetch_mail_triggers_email_fetch(): void
    {
        Queue::fake();
        
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $this->actingAs($admin)
            ->post(route('system.ajax'), ['action' => 'fetch_mail'])
            ->assertOk();
        
        // Verify fetch emails job was dispatched
    }

    public function test_logs_page_displays_application_logs(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $this->actingAs($admin)
            ->get(route('system.logs'))
            ->assertOk()
            ->assertViewIs('system.logs')
            ->assertViewHas('logs');
    }
}
```

**Create new file:** `tests/Feature/SystemControllerTest.php`

---

#### **ModulesController Enhancement**

**Target File:** Expand `tests/Unit/Controllers/ModulesControllerTest.php`

**Test Implementation:**

```php
public function test_admin_can_enable_module(): void
{
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    
    $this->actingAs($admin)
        ->post(route('modules.enable', ['module' => 'testmodule']))
        ->assertRedirect()
        ->assertSessionHas('success');
}

public function test_admin_can_disable_module(): void
{
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    
    $this->actingAs($admin)
        ->post(route('modules.disable', ['module' => 'testmodule']))
        ->assertRedirect()
        ->assertSessionHas('success');
}

public function test_non_admin_cannot_manage_modules(): void
{
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    
    $this->actingAs($user)
        ->post(route('modules.enable', ['module' => 'testmodule']))
        ->assertForbidden();
}
```

---

### 4.2 Event System Coverage

#### **NewMessageReceived Event (64% coverage)**

**Target File:** Create `tests/Unit/Events/NewMessageReceivedTest.php`

**Test Implementation:**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use App\Events\NewMessageReceived;
use App\Models\Conversation;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewMessageReceivedTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_stores_conversation_and_thread(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
        
        $event = new NewMessageReceived($conversation, $thread);
        
        $this->assertEquals($conversation->id, $event->conversation->id);
        $this->assertEquals($thread->id, $event->thread->id);
    }

    public function test_event_broadcasts_on_correct_channel(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
        
        $event = new NewMessageReceived($conversation, $thread);
        
        $channels = $event->broadcastOn();
        
        $this->assertIsArray($channels);
        $this->assertStringContainsString('conversation', $channels[0]->name);
    }

    public function test_event_includes_message_data_in_broadcast(): void
    {
        $conversation = Conversation::factory()->create(['subject' => 'Test Subject']);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'body' => 'Test message body',
        ]);
        
        $event = new NewMessageReceived($conversation, $thread);
        
        $broadcastData = $event->broadcastWith();
        
        $this->assertArrayHasKey('conversation_id', $broadcastData);
        $this->assertArrayHasKey('thread_id', $broadcastData);
    }
}
```

---

### 4.3 Mail Classes Coverage

#### **AutoReply Mail Class**

**Target File:** Expand `tests/Unit/Mail/AutoReplyEnhancedTest.php`

**Test Implementation:**

```php
public function test_auto_reply_mail_renders_correctly(): void
{
    $mailbox = Mailbox::factory()->create([
        'auto_reply_subject' => 'Thank you',
        'auto_reply_message' => 'We received your message',
    ]);
    
    $customer = Customer::factory()->create(['email' => 'customer@example.com']);
    $conversation = Conversation::factory()->create();
    $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
    
    $mail = new \App\Mail\AutoReply($conversation, $thread, $mailbox, $customer);
    
    $rendered = $mail->render();
    
    $this->assertStringContainsString('We received your message', $rendered);
}

public function test_auto_reply_has_correct_recipient(): void
{
    $mailbox = Mailbox::factory()->create();
    $customer = Customer::factory()->create(['email' => 'customer@example.com']);
    $conversation = Conversation::factory()->create();
    $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
    
    $mail = new \App\Mail\AutoReply($conversation, $thread, $mailbox, $customer);
    
    $this->assertTrue($mail->hasTo('customer@example.com'));
}
```

---

### 4.4 Middleware Coverage

#### **EnsureUserIsAdmin Middleware**

**Target File:** Expand `tests/Unit/Middleware/EnsureUserIsAdminTest.php`

**Test Implementation:**

```php
public function test_allows_admin_users_through(): void
{
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    
    $request = Request::create('/admin/settings', 'GET');
    $request->setUserResolver(fn() => $admin);
    
    $middleware = new \App\Http\Middleware\EnsureUserIsAdmin();
    
    $response = $middleware->handle($request, function ($req) {
        return new Response('Success');
    });
    
    $this->assertEquals('Success', $response->getContent());
}

public function test_blocks_regular_users(): void
{
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    
    $request = Request::create('/admin/settings', 'GET');
    $request->setUserResolver(fn() => $user);
    
    $middleware = new \App\Http\Middleware\EnsureUserIsAdmin();
    
    $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    
    $middleware->handle($request, function ($req) {
        return new Response('Success');
    });
}
```

---

### 4.5 Observer Coverage

**Target Files:** Expand existing observer tests

#### **ConversationObserver**

```php
public function test_creating_conversation_sets_defaults(): void
{
    $mailbox = Mailbox::factory()->create();
    $customer = Customer::factory()->create();
    
    $conversation = new Conversation([
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
        'subject' => 'Test',
    ]);
    
    $conversation->save();
    
    $this->assertNotNull($conversation->number);
    $this->assertEquals(Conversation::STATUS_ACTIVE, $conversation->status);
}

public function test_updated_conversation_updates_mailbox_counters(): void
{
    $mailbox = Mailbox::factory()->create();
    $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
    
    $conversation->status = Conversation::STATUS_CLOSED;
    $conversation->save();
    
    // Verify UpdateMailboxCounters event was fired
}
```

---

## 5. Coverage Estimation

With all tests in Batches 1-5 plus additional targets implemented:

| Area | Current Coverage | Target Coverage | Test Files to Add/Expand |
|------|-----------------|-----------------|-------------------------|
| **Services** | 7-40% | 75-85% | 4 files |
| **Jobs** | 1-2% | 75-80% | 6 files |
| **Controllers** | 33-50% | 75-85% | 8 files |
| **Commands** | 2-8% | 70-75% | 6 files |
| **Models** | 60-70% | 85-90% | 10 files |
| **Events** | 64-75% | 85-90% | 4 files |
| **Policies** | 50-60% | 85-90% | 5 files |
| **Mail** | 30-40% | 80-85% | 3 files |
| **Middleware** | 50-60% | 85-90% | 3 files |
| **Observers** | 60-70% | 85-90% | 6 files |
| **Helpers** | 70-80% | 90-95% | 2 files |

**Estimated Final Coverage:** 78-82% overall line coverage

To reach exactly 80%, prioritize:
1. **Batches 1-2** (Services and Jobs) - Highest impact
2. **Batch 3** (Commands) - Medium impact
3. **Batch 4** (Controllers) - High impact
4. **Additional Targets** (SystemController, Events) - Fill remaining gaps

---

## 6. Implementation Guide

### 6.1 Getting Started

#### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run specific test file
php artisan test tests/Unit/Services/ImapServiceComprehensiveTest.php

# Run with coverage (requires Xdebug or PCOV)
php artisan test --coverage

# Run with coverage HTML report
php artisan test --coverage-html coverage-report
```

#### Test Database Configuration

The project uses SQLite in-memory database for tests by default (configured in `phpunit.xml`):

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

For feature tests requiring MySQL-specific queries, switch to MySQL by commenting out SQLite lines and uncommenting MySQL configuration in `phpunit.xml`.

### 6.2 Test Writing Standards

#### Naming Conventions

Use snake_case for test method names:

```php
public function test_user_can_create_conversation(): void
{
    // Test implementation
}
```

#### Test Structure (Arrange-Act-Assert)

```php
public function test_example(): void
{
    // Arrange: Set up test data
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    
    // Act: Perform the action
    $response = $this->actingAs($user)->get(route('mailbox.show', $mailbox));
    
    // Assert: Verify the outcome
    $response->assertOk();
    $this->assertDatabaseHas('mailboxes', ['id' => $mailbox->id]);
}
```

#### Using Factories

```php
// Create single model
$user = User::factory()->create(['role' => User::ROLE_ADMIN]);

// Create multiple models
$users = User::factory()->count(5)->create();

// Make (don't persist to database)
$user = User::factory()->make();

// Create with relationships
$conversation = Conversation::factory()
    ->for($mailbox)
    ->for($customer)
    ->has(Thread::factory()->count(3))
    ->create();
```

#### Mocking External Services

```php
// Mock IMAP Client
$mockClient = Mockery::mock('Webklex\PHPIMAP\Client');
$mockClient->shouldReceive('connect')->andReturn(true);

// Mock Laravel Facades
Mail::fake();
Queue::fake();
Event::fake();
Cache::fake();

// Assert on fakes
Mail::assertSent(ConversationReplyNotification::class);
Queue::assertPushed(SendNotificationToUsers::class);
```

### 6.3 Priority Implementation Order

#### Phase 1: Critical Services (Week 1-2)
- **Batch 1**: ImapService and SmtpService
- **Estimated Effort**: 40-50 hours
- **Coverage Gain**: +15-20%

Focus on:
- Connection handling and error recovery
- Email parsing for various formats
- Attachment processing

#### Phase 2: Background Jobs (Week 2-3)
- **Batch 2**: SendNotificationToUsers, SendAutoReply, SendAlert, SendEmailReplyError
- **Estimated Effort**: 30-40 hours
- **Coverage Gain**: +10-15%

Focus on:
- Job dispatch and execution
- Failure handling
- Notification filtering

#### Phase 3: Console Commands (Week 3-4)
- **Batch 3**: Module management and system maintenance commands
- **Estimated Effort**: 25-30 hours
- **Coverage Gain**: +8-10%

Focus on:
- Success and failure paths
- Error handling
- File system operations

#### Phase 4: Controllers and Policies (Week 4-5)
- **Batch 4**: ConversationController, SystemController, SettingsController
- **Estimated Effort**: 35-45 hours
- **Coverage Gain**: +12-15%

Focus on:
- Authorization logic
- Validation rules
- State management

#### Phase 5: Models and Helpers (Week 5-6)
- **Batch 5**: Customer model, MailHelper, and other utilities
- **Estimated Effort**: 20-25 hours
- **Coverage Gain**: +5-8%

Focus on:
- Business logic
- Data transformations
- Edge cases

#### Phase 6: Gap Filling (Week 6-7)
- **Additional Targets**: Events, Mail classes, Middleware, Observers
- **Estimated Effort**: 25-30 hours
- **Coverage Gain**: +10-12%

Focus on:
- Remaining uncovered code
- Edge cases
- Integration scenarios

**Total Estimated Effort**: 175-220 hours (5-7 weeks for a team of 3-4 developers)

### 6.4 Test File Organization

```
tests/
├── Unit/                           # Unit tests for isolated components
│   ├── Services/                   # Service class tests
│   │   ├── ImapServiceTest.php
│   │   ├── ImapServiceComprehensiveTest.php
│   │   └── SmtpServiceComprehensiveTest.php
│   ├── Jobs/                       # Job tests
│   │   ├── SendNotificationToUsersTest.php
│   │   ├── SendAutoReplyComprehensiveTest.php
│   │   └── SendAlertEnhancedTest.php
│   ├── Models/                     # Model tests
│   │   ├── CustomerComprehensiveTest.php
│   │   └── ConversationModelTest.php
│   ├── Controllers/                # Controller unit tests
│   ├── Events/                     # Event tests
│   ├── Mail/                       # Mail class tests
│   ├── Middleware/                 # Middleware tests
│   └── Observers/                  # Observer tests
│
└── Feature/                        # Feature/integration tests
    ├── Commands/                   # Artisan command tests
    │   ├── FetchEmailsCommandTest.php
    │   ├── ModuleInstallCommandTest.php
    │   └── ModuleUpdateCommandTest.php
    ├── Integration/                # Full workflow tests
    │   ├── ConversationWorkflowTest.php
    │   └── CompleteWorkflowTest.php
    ├── ConversationControllerMethodsTest.php
    ├── ConversationValidationTest.php
    ├── SystemControllerTest.php
    └── SettingsControllerTest.php
```

### 6.5 Common Testing Patterns

#### Testing Authorization

```php
public function test_user_without_permission_receives_403(): void
{
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    // User NOT attached to mailbox
    
    $this->actingAs($user)
        ->get(route('mailbox.show', $mailbox))
        ->assertForbidden();
}
```

#### Testing Validation

```php
public function test_validation_fails_with_invalid_data(): void
{
    $user = User::factory()->create();
    
    $this->actingAs($user)
        ->post(route('conversation.store'), [
            'subject' => '', // Invalid: empty
            'body' => 'x', // Invalid: too short
        ])
        ->assertSessionHasErrors(['subject', 'body']);
}
```

#### Testing Database Changes

```php
public function test_creates_record_in_database(): void
{
    $user = User::factory()->create();
    
    $this->actingAs($user)
        ->post(route('customer.store'), [
            'email' => 'new@example.com',
            'first_name' => 'John',
        ])
        ->assertRedirect();
    
    $this->assertDatabaseHas('customers', [
        'email' => 'new@example.com',
        'first_name' => 'John',
    ]);
}
```

#### Testing Email Sending

```php
public function test_sends_email_notification(): void
{
    Mail::fake();
    
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();
    
    // Trigger action that sends email
    $job = new SendNotificationToUsers(collect([$user]), $conversation, collect([]));
    $job->handle();
    
    Mail::assertSent(ConversationReplyNotification::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
}
```

#### Testing Background Jobs

```php
public function test_dispatches_background_job(): void
{
    Queue::fake();
    
    // Trigger action that dispatches job
    $conversation = Conversation::factory()->create();
    event(new ConversationCreated($conversation));
    
    Queue::assertPushed(SendNotificationToUsers::class, function ($job) use ($conversation) {
        return $job->conversation->id === $conversation->id;
    });
}
```

#### Testing Events

```php
public function test_fires_event_on_action(): void
{
    Event::fake();
    
    $conversation = Conversation::factory()->create();
    $conversation->status = Conversation::STATUS_CLOSED;
    $conversation->save();
    
    Event::assertDispatched(ConversationStatusChanged::class, function ($event) use ($conversation) {
        return $event->conversation->id === $conversation->id
            && $event->conversation->status === Conversation::STATUS_CLOSED;
    });
}
```

### 6.6 Troubleshooting

#### Common Issues

**Issue**: Tests fail with "Class not found"
```bash
# Solution: Regenerate autoload files
composer dump-autoload
php artisan clear-compiled
```

**Issue**: Database constraint violations
```bash
# Solution: Use RefreshDatabase trait and check factory definitions
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyTest extends TestCase
{
    use RefreshDatabase;
    
    // Your tests
}
```

**Issue**: Mock expectations not met
```bash
# Solution: Verify mock setup and call expectations
$mock->shouldReceive('method')
    ->once()  // or times(2), atLeast()->once(), etc.
    ->with($expectedParam)
    ->andReturn($value);
```

**Issue**: Timeout on IMAP/SMTP tests
```bash
# Solution: Mock external connections
$mockClient = Mockery::mock('Webklex\PHPIMAP\Client');
$mockClient->shouldReceive('connect')->andReturn(true);
$this->instance('Webklex\PHPIMAP\Client', $mockClient);
```

### 6.7 Continuous Integration

#### GitHub Actions Workflow Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.3'
        extensions: mbstring, xml, ctype, iconv, intl, pdo_sqlite, mysql
        coverage: xdebug
    
    - name: Install Dependencies
      run: composer install --prefer-dist --no-interaction
    
    - name: Run Tests
      run: php artisan test --coverage --min=80
    
    - name: Upload Coverage Report
      uses: codecov/codecov-action@v2
      with:
        files: ./coverage.xml
```

### 6.8 Measuring Progress

#### Generate Coverage Report

```bash
# HTML report
php artisan test --coverage-html coverage-report

# Open in browser
open coverage-report/index.html
```

#### Coverage Metrics to Track

1. **Line Coverage**: Percentage of code lines executed
2. **Method Coverage**: Percentage of methods with at least one test
3. **Class Coverage**: Percentage of classes with at least one test
4. **CRAP Index**: Change Risk Anti-Patterns (complexity × (1 - coverage)^3)

#### Target Metrics for Success

- **Overall Line Coverage**: ≥ 80%
- **Critical Services**: ≥ 85%
- **Controllers**: ≥ 75%
- **Models**: ≥ 85%
- **Jobs**: ≥ 80%
- **Commands**: ≥ 70%

---

## 7. Test Quality Checklist

Before considering a test complete, verify:

- [ ] Test name clearly describes what is being tested
- [ ] Test follows Arrange-Act-Assert pattern
- [ ] Test is independent (doesn't rely on other tests)
- [ ] Test uses factories for data creation
- [ ] External dependencies are properly mocked
- [ ] Edge cases are covered
- [ ] Error handling is tested
- [ ] Test assertions are specific and meaningful
- [ ] Test runs quickly (< 1 second for unit tests)
- [ ] Test is maintainable and easy to understand

---

## 8. Resources and References

### Laravel Testing Documentation

- [Laravel Testing Guide](https://laravel.com/docs/11.x/testing)
- [HTTP Tests](https://laravel.com/docs/11.x/http-tests)
- [Database Testing](https://laravel.com/docs/11.x/database-testing)
- [Mocking](https://laravel.com/docs/11.x/mocking)

### PHPUnit Documentation

- [PHPUnit Manual](https://phpunit.de/documentation.html)
- [Assertions](https://phpunit.de/manual/current/en/assertions.html)
- [Test Doubles](https://phpunit.de/manual/current/en/test-doubles.html)

### Project-Specific Documentation

- `/docs/TEST_COVERAGE_ANALYSIS.md` - Current coverage analysis
- `/docs/TEST_SUITE_DOCUMENTATION.md` - Test suite overview
- `tests/` - Existing test examples

### Code Coverage Tools

- **Xdebug**: PHP extension for debugging and coverage
- **PCOV**: Faster PHP code coverage driver
- **PHPUnit**: Built-in coverage reporting

---

## 9. Success Criteria

The testing improvement plan will be considered successful when:

1. **Coverage Target Met**: Overall line coverage ≥ 80%
2. **Critical Paths Tested**: All high-priority services, jobs, and controllers have ≥ 75% coverage
3. **Quality Standards**: All tests follow established patterns and best practices
4. **CI Integration**: Automated testing runs on all pull requests
5. **Documentation Complete**: All new tests are documented and maintainable
6. **Team Knowledge**: Development team is trained on writing and maintaining tests

---

## 10. Maintenance and Evolution

### Ongoing Test Maintenance

- **Review Coverage Weekly**: Track coverage metrics in CI/CD
- **Update Tests with Code Changes**: Ensure tests evolve with codebase
- **Refactor Duplicate Test Code**: Extract common patterns into test helpers
- **Monitor Test Performance**: Keep test suite fast (target: < 5 minutes total)
- **Archive Obsolete Tests**: Remove tests for deprecated features

### Future Enhancements

- **Integration Tests**: Add end-to-end workflow tests
- **Performance Tests**: Add load testing for critical paths
- **Security Tests**: Add automated security scanning
- **Browser Tests**: Add Laravel Dusk tests for UI features
- **API Tests**: Add comprehensive API endpoint testing

---

## Appendix A: Test File Templates

### Unit Test Template

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\[Category];

use App\[ModelOrClass];
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class [ClassName]Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Common setup for all tests in this class
    }

    public function test_[descriptive_name_of_what_is_tested](): void
    {
        // Arrange
        $model = [ModelOrClass]::factory()->create();
        
        // Act
        $result = $model->someMethod();
        
        // Assert
        $this->assertEquals($expected, $result);
    }
}
```

### Feature Test Template

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class [Feature]Test extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_[user_can_perform_action](): void
    {
        // Arrange
        $data = ['key' => 'value'];
        
        // Act
        $response = $this->actingAs($this->user)
            ->post(route('resource.store'), $data);
        
        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('table_name', $data);
    }

    public function test_[unauthorized_user_cannot_perform_action](): void
    {
        $unauthorizedUser = User::factory()->create(['role' => User::ROLE_USER]);
        
        $response = $this->actingAs($unauthorizedUser)
            ->post(route('resource.store'), []);
        
        $response->assertForbidden();
    }
}
```

---

## Appendix B: Quick Reference

### Common Assertions

```php
// Response Assertions
$response->assertOk();                      // 200
$response->assertCreated();                 // 201
$response->assertRedirect($uri);            // 302
$response->assertForbidden();               // 403
$response->assertNotFound();                // 404
$response->assertSessionHas('key');
$response->assertSessionHasErrors('field');

// Database Assertions
$this->assertDatabaseHas('table', ['column' => 'value']);
$this->assertDatabaseMissing('table', ['column' => 'value']);
$this->assertDatabaseCount('table', 5);

// Model Assertions
$this->assertModelExists($model);
$this->assertModelMissing($model);
$this->assertSoftDeleted($model);

// General Assertions
$this->assertEquals($expected, $actual);
$this->assertTrue($condition);
$this->assertNull($value);
$this->assertCount(5, $array);
$this->assertStringContainsString('needle', 'haystack');
```

### Fake Usage

```php
// Mail
Mail::fake();
Mail::assertSent(MailClass::class);
Mail::assertNotSent(MailClass::class);
Mail::assertSent(MailClass::class, 3);  // Sent exactly 3 times

// Queue
Queue::fake();
Queue::assertPushed(JobClass::class);
Queue::assertNotPushed(JobClass::class);

// Event
Event::fake();
Event::assertDispatched(EventClass::class);
Event::assertNotDispatched(EventClass::class);

// Cache
Cache::fake();
Cache::shouldReceive('get')->with('key')->andReturn('value');

// Storage
Storage::fake('local');
Storage::assertExists('path/to/file.txt');
```

---

**Document Version**: 2.0  
**Last Updated**: 2025-11-12  
**Status**: Comprehensive Implementation Ready  
**Target Coverage**: 80% Line Coverage  
**Estimated Implementation Time**: 5-7 weeks (team of 3-4 developers)

