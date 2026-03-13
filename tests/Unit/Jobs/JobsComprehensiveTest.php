<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SendAlertJob as SendAlert;
use App\Jobs\SendAutoReplyJob as SendAutoReply;
use App\Jobs\SendNotificationToUsersJob as SendNotificationToUsers;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\SendLog;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\UnitTestCase;

class JobsComprehensiveTest extends UnitTestCase
{
    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);
        $this->user = User::factory()->create([
            'role' => User::ROLE_USER,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    // SendNotificationToUsers Tests
    // ========================================

    public function test_send_notification_handles_empty_users_list(): void
    {
        Mail::fake();
        Log::spy();

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'state' => Thread::STATE_PUBLISHED,
            'type' => Thread::TYPE_CUSTOMER,
        ]);
        $threads = new Collection([$thread]);

        $job = new SendNotificationToUsers(
            new Collection([]), // Empty users
            $conversation,
            $threads
        );

        $job->handle();

        // Should not send any emails
        Mail::assertNothingSent();

        // Should not create any send logs
        $this->assertEquals(0, SendLog::where('thread_id', $thread->id)->count());
    }

    public function test_send_notification_handles_deleted_users(): void
    {
        Mail::fake();
        Log::spy();

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'state' => Thread::STATE_PUBLISHED,
            'type' => Thread::TYPE_CUSTOMER,
        ]);
        $threads = new Collection([$thread]);

        // Create a deleted user
        $deletedUser = User::factory()->create([
            'status' => User::STATUS_DELETED,
        ]);

        $job = new SendNotificationToUsers(
            new Collection([$deletedUser]),
            $conversation,
            $threads
        );

        $job->handle();

        // Should not send any emails to deleted users
        Mail::assertNothingSent();
    }

    public function test_send_notification_skips_draft_threads(): void
    {
        Mail::fake();
        Log::spy();

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);
        $draftThread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'state' => Thread::STATE_DRAFT, // Draft state
            'type' => Thread::TYPE_CUSTOMER,
        ]);
        $threads = new Collection([$draftThread]);

        $job = new SendNotificationToUsers(
            new Collection([$this->user]),
            $conversation,
            $threads
        );

        $job->handle();

        // Should not send emails for draft threads
        Mail::assertNothingSent();
    }

    public function test_send_notification_skips_bounce_with_limit_exceeded(): void
    {
        Mail::fake();
        Log::spy();

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);
        $bounceThread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'state' => Thread::STATE_PUBLISHED,
            'type' => Thread::TYPE_BOUNCE,
            'body' => 'Delivery failed: message limit exceeded for this account',
        ]);
        $threads = new Collection([$bounceThread]);

        $job = new SendNotificationToUsers(
            new Collection([$this->user]),
            $conversation,
            $threads
        );

        $job->handle();

        // Should not send notifications for bounce with limit exceeded
        Mail::assertNothingSent();
    }

    public function test_send_notification_logs_send_success(): void
    {
        Mail::fake();
        Log::spy();

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'state' => Thread::STATE_PUBLISHED,
            'type' => Thread::TYPE_CUSTOMER,
        ]);
        $threads = new Collection([$thread]);

        $job = new SendNotificationToUsers(
            new Collection([$this->user]),
            $conversation,
            $threads
        );

        $job->handle();

        // Should create a send log
        $this->assertDatabaseHas('send_logs', [
            'thread_id' => $thread->id,
            'email' => $this->user->email,
            'mail_type' => SendLog::MAIL_TYPE_USER_NOTIFICATION,
            'status' => SendLog::STATUS_ACCEPTED,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_send_notification_handles_missing_mailbox(): void
    {
        Log::spy();

        // Create conversation with null mailbox relationship
        $conversation = new Conversation([
            'id' => 999,
            'mailbox_id' => 99999, // Non-existent
        ]);
        $thread = Thread::factory()->make([
            'id' => 1,
            'state' => Thread::STATE_PUBLISHED,
        ]);
        $threads = new Collection([$thread]);

        $job = new SendNotificationToUsers(
            new Collection([$this->user]),
            $conversation,
            $threads
        );

        $job->handle();

        // Should log error and return early
        Log::shouldHaveReceived('error')
            ->with('Mailbox not found for conversation', \Mockery::type('array'))
            ->once();
    }

    public function test_send_notification_handles_empty_threads(): void
    {
        Mail::fake();

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        $job = new SendNotificationToUsers(
            new Collection([$this->user]),
            $conversation,
            new Collection([]) // Empty threads
        );

        $job->handle();

        // Should not send any emails
        Mail::assertNothingSent();
    }

    public function test_send_notification_sorts_threads_by_created_at(): void
    {
        Mail::fake();
        Log::spy();

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        // Create threads with different timestamps
        $oldThread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'state' => Thread::STATE_PUBLISHED,
            'created_at' => now()->subHours(2),
        ]);
        $newerThread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'state' => Thread::STATE_PUBLISHED,
            'created_at' => now()->subHour(),
        ]);
        $newestThread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'state' => Thread::STATE_PUBLISHED,
            'created_at' => now(),
        ]);

        $threads = new Collection([$oldThread, $newestThread, $newerThread]);

        $job = new SendNotificationToUsers(
            new Collection([$this->user]),
            $conversation,
            $threads
        );

        $job->handle();

        // Should use the newest thread (sorted descending)
        $sendLog = SendLog::where('thread_id', $newestThread->id)->first();
        $this->assertNotNull($sendLog);
    }

    public function test_send_notification_failed_method_logs_error(): void
    {
        Log::spy();

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);
        $threads = new Collection([Thread::factory()->make()]);

        $job = new SendNotificationToUsers(
            new Collection([$this->user]),
            $conversation,
            $threads
        );

        $exception = new \Exception('Test failure message');
        $job->failed($exception);

        Log::shouldHaveReceived('error')
            ->with('SendNotificationToUsers job failed', \Mockery::type('array'))
            ->once();
    }

    public function test_send_notification_handles_user_without_id(): void
    {
        Mail::fake();
        Log::spy();

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'state' => Thread::STATE_PUBLISHED,
        ]);

        // Create user object without ID
        $userWithoutId = new User;
        $userWithoutId->email = 'test@example.com';
        $userWithoutId->status = User::STATUS_ACTIVE;

        $job = new SendNotificationToUsers(
            new Collection([$userWithoutId]),
            $conversation,
            new Collection([$thread])
        );

        $job->handle();

        // Should skip user without ID
        Mail::assertNothingSent();
    }

    public function test_send_notification_processes_multiple_users(): void
    {
        Mail::fake();
        Log::spy();

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'state' => Thread::STATE_PUBLISHED,
        ]);

        $user2 = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
        ]);

        $job = new SendNotificationToUsers(
            new Collection([$this->user, $user2]),
            $conversation,
            new Collection([$thread])
        );

        $job->handle();

        // Should create send logs for both users
        $this->assertDatabaseHas('send_logs', [
            'thread_id' => $thread->id,
            'user_id' => $this->user->id,
        ]);
        $this->assertDatabaseHas('send_logs', [
            'thread_id' => $thread->id,
            'user_id' => $user2->id,
        ]);
    }

    // ========================================
    // SendAutoReply Tests
    // ========================================

    public function test_send_auto_reply_handles_job_failure(): void
    {
        Log::spy();

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
        ]);
        $customer = Customer::factory()->create();

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);

        // Test the failed() method
        $exception = new \Exception('Test failure');
        $job->failed($exception);

        // Should have failed() method
        $this->assertTrue(method_exists($job, 'failed'));
    }

    public function test_send_auto_reply_skips_when_disabled_via_meta(): void
    {
        Mail::fake();
        Log::spy();

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'meta' => ['ar_off' => true], // Auto-reply disabled
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
        ]);
        $customer = Customer::factory()->create();

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);

        // Mock SmtpService
        $smtpService = \Mockery::mock(\App\Services\SmtpService::class);
        $job->handle($smtpService);

        // Should not send when disabled
        Mail::assertNothingSent();
    }

    public function test_send_auto_reply_aborts_without_customer_email(): void
    {
        Mail::fake();
        Log::spy();

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_email' => null, // No customer email
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
        ]);
        $customer = Customer::factory()->create();

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);

        // Mock SmtpService
        $smtpService = \Mockery::mock(\App\Services\SmtpService::class);
        $job->handle($smtpService);

        // Should not send without customer email
        Mail::assertNothingSent();
    }

    public function test_send_auto_reply_creates_send_log_on_success(): void
    {
        Mail::fake();
        Log::spy();

        $mailbox = Mailbox::factory()->create([
            'email' => 'support@example.com',
        ]);
        $customer = Customer::factory()->create([
            'email' => 'customer@example.com',
        ]);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_email' => $customer->emails->first()->email,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'message_id' => 'original-message-id@example.com',
        ]);

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);

        // Mock SmtpService
        $smtpService = \Mockery::mock(\App\Services\SmtpService::class);
        $smtpService->shouldReceive('configureSmtp')->once();

        $job->handle($smtpService);

        // Should create send log
        $this->assertDatabaseHas('send_logs', [
            'thread_id' => $thread->id,
            'email' => $customer->emails->first()->email,
            'mail_type' => SendLog::MAIL_TYPE_AUTO_REPLY,
        ]);
    }

    public function test_send_auto_reply_has_timeout_property(): void
    {
        $conversation = Conversation::factory()->make();
        $thread = Thread::factory()->make();
        $mailbox = Mailbox::factory()->make();
        $customer = Customer::factory()->make();

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);

        $this->assertEquals(120, $job->timeout);
    }

    public function test_send_auto_reply_logs_info_when_executing(): void
    {
        Mail::fake();
        Log::spy();

        $mailbox = Mailbox::factory()->create([
            'email' => 'support@example.com',
        ]);
        $customer = Customer::factory()->create([
            'email' => 'customer@example.com',
        ]);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_email' => $customer->emails->first()->email,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'message_id' => 'test-message-id',
        ]);

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);

        // Mock SmtpService
        $smtpService = \Mockery::mock(\App\Services\SmtpService::class);
        $smtpService->shouldReceive('configureSmtp')->once();

        $job->handle($smtpService);

        Log::shouldHaveReceived('info')
            ->with('Executing SendAutoReply job', \Mockery::type('array'))
            ->once();
    }

    // ========================================
    // SendAlert Tests
    // ========================================

    public function test_send_alert_handles_no_recipients(): void
    {
        Mail::fake();
        Log::spy();

        // Clear all admin users
        User::where('role', User::ROLE_ADMIN)->delete();

        $job = new SendAlert('Test error message', 'Test Alert');

        // Should handle gracefully with no recipients
        $job->handle();

        // No emails should be sent
        Mail::assertNothingSent();
    }

    public function test_send_alert_dispatches_to_admin_users(): void
    {
        Mail::fake();
        Log::spy();

        // Create activated admin users
        $admin1 = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'invite_state' => User::INVITE_STATE_ACTIVATED ?? 1,
            'email' => 'admin1@example.com',
        ]);

        $admin2 = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'invite_state' => User::INVITE_STATE_ACTIVATED ?? 1,
            'email' => 'admin2@example.com',
        ]);

        $job = new SendAlert('IMAP connection failed', 'fetch_error');
        $job->handle();

        // Should create send logs for each admin
        $sendLogs = SendLog::where('mail_type', SendLog::MAIL_TYPE_ALERT)->get();
        $this->assertGreaterThanOrEqual(2, $sendLogs->count());
        $this->assertTrue($sendLogs->contains('email', 'admin1@example.com'));
        $this->assertTrue($sendLogs->contains('email', 'admin2@example.com'));
    }

    public function test_send_alert_skips_inactive_admins(): void
    {
        Mail::fake();
        Log::spy();

        // Clear existing admins
        User::where('role', User::ROLE_ADMIN)->delete();

        // Create inactive admin
        $inactiveAdmin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_INACTIVE,
            'email' => 'inactive@example.com',
        ]);

        // Create active admin
        $activeAdmin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'invite_state' => User::INVITE_STATE_ACTIVATED ?? 1,
            'email' => 'active@example.com',
        ]);

        $job = new SendAlert('Test alert', 'Test Title');
        $job->handle();

        // Should only send to active admin
        $this->assertDatabaseHas('send_logs', [
            'email' => 'active@example.com',
            'mail_type' => SendLog::MAIL_TYPE_ALERT,
        ]);

        // Should not send to inactive admin
        $this->assertDatabaseMissing('send_logs', [
            'email' => 'inactive@example.com',
        ]);
    }

    public function test_send_alert_skips_non_activated_admins(): void
    {
        Mail::fake();
        Log::spy();

        // Clear existing admins
        User::where('role', User::ROLE_ADMIN)->delete();

        // Create non-activated admin (invited but not activated)
        $nonActivatedAdmin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'invite_state' => User::INVITE_STATE_SENT,
            'email' => 'notactivated@example.com',
        ]);

        $job = new SendAlert('Test alert', 'Test Title');
        $job->handle();

        // Should not send to non-activated admin
        $this->assertEquals(0, SendLog::where('mail_type', SendLog::MAIL_TYPE_ALERT)->count());
    }

    public function test_send_alert_has_timeout_property(): void
    {
        $job = new SendAlert('Test message', 'Test title');

        $this->assertEquals(120, $job->timeout);
    }

    public function test_send_alert_logs_send_attempt(): void
    {
        Mail::fake();
        Log::spy();

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'invite_state' => User::INVITE_STATE_ACTIVATED ?? 1,
            'email' => 'admin@example.com',
        ]);

        $job = new SendAlert('Database connection error', 'Critical Alert');
        $job->handle();

        Log::shouldHaveReceived('info')
            ->with('Sending alert email', \Mockery::type('array'))
            ->atLeast()
            ->once();
    }

    public function test_send_alert_creates_send_log_with_null_thread_id(): void
    {
        Mail::fake();
        Log::spy();

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'invite_state' => User::INVITE_STATE_ACTIVATED ?? 1,
        ]);

        $job = new SendAlert('System error', 'Alert');
        $job->handle();

        // Send logs for alerts should have null thread_id
        $sendLog = SendLog::where('mail_type', SendLog::MAIL_TYPE_ALERT)->first();
        $this->assertNotNull($sendLog);
        $this->assertNull($sendLog->thread_id);
        $this->assertNull($sendLog->message_id);
        $this->assertNull($sendLog->user_id);
    }

    // ========================================

    // Additional Edge Case Tests for Jobs
    // ========================================

    public function test_send_notification_with_customer_thread_sets_from_name(): void
    {
        Mail::fake();
        Log::spy();

        $mailbox = Mailbox::factory()->create([
            'name' => 'Support Team',
            'email' => 'support@example.com',
        ]);
        $customer = Customer::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'state' => Thread::STATE_PUBLISHED,
            'type' => Thread::TYPE_CUSTOMER,
            'customer_id' => $customer->id,
        ]);

        $job = new SendNotificationToUsers(
            new Collection([$this->user]),
            $conversation,
            new Collection([$thread])
        );

        $job->handle();

        // From name should be "{Customer Name} via {Mailbox Name}"
        // Verify send log was created
        $this->assertDatabaseHas('send_logs', [
            'thread_id' => $thread->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_send_notification_creates_proper_message_id(): void
    {
        Mail::fake();
        Log::spy();

        $mailbox = Mailbox::factory()->create([
            'email' => 'support@example.com',
        ]);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'state' => Thread::STATE_PUBLISHED,
        ]);

        $job = new SendNotificationToUsers(
            new Collection([$this->user]),
            $conversation,
            new Collection([$thread])
        );

        $job->handle();

        // Message ID format: notification-{thread_id}-{user_id}-{timestamp}@{mailbox_email}
        $sendLog = SendLog::where('thread_id', $thread->id)->first();
        $this->assertNotNull($sendLog);
        $this->assertStringContainsString('notification-'.$thread->id.'-'.$this->user->id, $sendLog->message_id);
        $this->assertStringContainsString('@support@example.com', $sendLog->message_id);
    }

    public function test_send_auto_reply_creates_proper_message_id(): void
    {
        Mail::fake();
        Log::spy();

        $mailbox = Mailbox::factory()->create([
            'email' => 'support@example.com',
        ]);
        $customer = Customer::factory()->create([
            'email' => 'customer@example.com',
        ]);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_email' => $customer->emails->first()->email,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'message_id' => 'original@example.com',
        ]);

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);

        // Mock SmtpService
        $smtpService = \Mockery::mock(\App\Services\SmtpService::class);
        $smtpService->shouldReceive('configureSmtp')->once();

        $job->handle($smtpService);

        // Message ID format: auto-reply-{thread_id}-{hash}@{domain}
        $sendLog = SendLog::where('thread_id', $thread->id)->first();
        $this->assertNotNull($sendLog);
        $this->assertStringContainsString('auto-reply-'.$thread->id, $sendLog->message_id);
    }

    public function test_send_notification_job_properties(): void
    {
        $users = new Collection([$this->user]);
        $conversation = Conversation::factory()->make();
        $threads = new Collection([Thread::factory()->make()]);

        $job = new SendNotificationToUsers($users, $conversation, $threads);

        $this->assertEquals(168, $job->tries);
        $this->assertEquals(120, $job->timeout);
        $this->assertSame($users, $job->users);
        $this->assertSame($conversation, $job->conversation);
        $this->assertSame($threads, $job->threads);
    }

    public function test_send_auto_reply_job_properties(): void
    {
        $conversation = Conversation::factory()->make();
        $thread = Thread::factory()->make();
        $mailbox = Mailbox::factory()->make();
        $customer = Customer::factory()->make();

        $job = new SendAutoReply($conversation, $thread, $mailbox, $customer);

        $this->assertEquals(120, $job->timeout);
        $this->assertSame($conversation, $job->conversation);
        $this->assertSame($thread, $job->thread);
        $this->assertSame($mailbox, $job->mailbox);
        $this->assertSame($customer, $job->customer);
    }

    public function test_send_alert_job_properties(): void
    {
        $text = 'Alert message';
        $title = 'Alert title';

        $job = new SendAlert($text, $title);

        $this->assertEquals(120, $job->timeout);
        $this->assertEquals($text, $job->text);
        $this->assertEquals($title, $job->title);
    }

    public function test_send_alert_with_empty_title(): void
    {
        Mail::fake();
        Log::spy();

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'invite_state' => User::INVITE_STATE_ACTIVATED ?? 1,
        ]);

        $job = new SendAlert('Message without title');
        $job->handle();

        // Should still send with empty title
        $this->assertDatabaseHas('send_logs', [
            'mail_type' => SendLog::MAIL_TYPE_ALERT,
        ]);
    }

    public function test_send_notification_handles_inactive_users(): void
    {
        Mail::fake();
        Log::spy();

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'state' => Thread::STATE_PUBLISHED,
        ]);

        // Create inactive user
        $inactiveUser = User::factory()->create([
            'status' => User::STATUS_INACTIVE,
        ]);

        $job = new SendNotificationToUsers(
            new Collection([$inactiveUser]),
            $conversation,
            new Collection([$thread])
        );

        $job->handle();

        // Inactive users should still receive notifications (only DELETED status is skipped)
        $this->assertDatabaseHas('send_logs', [
            'thread_id' => $thread->id,
            'user_id' => $inactiveUser->id,
        ]);
    }
}
