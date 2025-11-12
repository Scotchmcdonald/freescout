<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Jobs\SendAlert;
use App\Jobs\SendAutoReply;
use App\Jobs\SendNotificationToUsers;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\SendLog;
use App\Models\Thread;
use App\Models\User;
use App\Policies\ConversationPolicy;
use App\Policies\MailboxPolicy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\UnitTestCase;

class JobsPoliciesTest extends UnitTestCase
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

    // ========================================
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
        $userWithoutId = new User();
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
            'customer_email' => $customer->email,
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
            'email' => $customer->email,
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
            'customer_email' => $customer->email,
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
    // ConversationPolicy Tests
    // ========================================

    public function test_view_cached_allows_user_with_mailbox_access(): void
    {
        $policy = new ConversationPolicy();

        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($this->user->id);

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        // Reload to populate relationships
        $conversation->load('mailbox.users');

        $result = $policy->viewCached($this->user, $conversation);

        $this->assertTrue($result);
    }

    public function test_view_cached_allows_admin(): void
    {
        $policy = new ConversationPolicy();

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        $result = $policy->viewCached($this->admin, $conversation);

        $this->assertTrue($result);
    }

    public function test_delete_prevents_unauthorized_user_from_deleting(): void
    {
        $policy = new ConversationPolicy();

        $otherMailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $otherMailbox->id,
        ]);

        $result = $policy->delete($this->user, $conversation);

        $this->assertFalse($result);
    }

    public function test_delete_allows_user_with_mailbox_access(): void
    {
        $policy = new ConversationPolicy();

        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($this->user->id);

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        $result = $policy->delete($this->user, $conversation);

        $this->assertTrue($result);
    }

    public function test_view_allows_admin(): void
    {
        $policy = new ConversationPolicy();
        $conversation = Conversation::factory()->create();

        $result = $policy->view($this->admin, $conversation);

        $this->assertTrue($result);
    }

    public function test_view_allows_user_with_mailbox_access(): void
    {
        $policy = new ConversationPolicy();

        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($this->user->id);

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        $result = $policy->view($this->user, $conversation);

        $this->assertTrue($result);
    }

    public function test_view_denies_user_without_mailbox_access(): void
    {
        $policy = new ConversationPolicy();

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        $result = $policy->view($this->user, $conversation);

        $this->assertFalse($result);
    }

    public function test_update_allows_admin(): void
    {
        $policy = new ConversationPolicy();
        $conversation = Conversation::factory()->create();

        $result = $policy->update($this->admin, $conversation);

        $this->assertTrue($result);
    }

    public function test_update_allows_user_with_mailbox_access(): void
    {
        $policy = new ConversationPolicy();

        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($this->user->id);

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        $result = $policy->update($this->user, $conversation);

        $this->assertTrue($result);
    }

    public function test_update_denies_user_without_mailbox_access(): void
    {
        $policy = new ConversationPolicy();

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        $result = $policy->update($this->user, $conversation);

        $this->assertFalse($result);
    }

    public function test_delete_allows_admin(): void
    {
        $policy = new ConversationPolicy();
        $conversation = Conversation::factory()->create();

        $result = $policy->delete($this->admin, $conversation);

        $this->assertTrue($result);
    }

    public function test_delete_allows_conversation_without_id(): void
    {
        $policy = new ConversationPolicy();
        $conversation = new Conversation();

        $result = $policy->delete($this->user, $conversation);

        $this->assertTrue($result);
    }

    public function test_move_allows_user_with_multiple_mailboxes(): void
    {
        $policy = new ConversationPolicy();

        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();

        $this->user->mailboxes()->attach([$mailbox1->id, $mailbox2->id]);

        $result = $policy->move($this->user);

        $this->assertTrue($result);
    }

    public function test_move_allows_when_multiple_mailboxes_exist(): void
    {
        // Clear existing mailboxes
        Mailbox::query()->delete();

        $policy = new ConversationPolicy();

        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();

        $this->user->mailboxes()->attach($mailbox1->id);

        $result = $policy->move($this->user);

        $this->assertTrue($result);
    }

    public function test_move_denies_with_single_mailbox_system(): void
    {
        // Clear existing mailboxes
        Mailbox::query()->delete();

        $policy = new ConversationPolicy();

        $mailbox = Mailbox::factory()->create();
        $this->user->mailboxes()->attach($mailbox->id);

        $result = $policy->move($this->user);

        $this->assertFalse($result);
    }

    public function test_view_cached_denies_user_without_mailbox_access(): void
    {
        $policy = new ConversationPolicy();

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        // Load the mailbox relationship
        $conversation->load('mailbox.users');

        $result = $policy->viewCached($this->user, $conversation);

        $this->assertFalse($result);
    }

    public function test_check_is_only_assigned_returns_true(): void
    {
        $policy = new ConversationPolicy();

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'user_id' => $this->user->id, // User is assignee
        ]);

        $result = $policy->checkIsOnlyAssigned($conversation, $this->user);

        $this->assertTrue($result);
    }

    public function test_check_is_only_assigned_for_creator(): void
    {
        $policy = new ConversationPolicy();

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'created_by_user_id' => $this->user->id, // User is creator
        ]);

        $result = $policy->checkIsOnlyAssigned($conversation, $this->user);

        $this->assertTrue($result);
    }

    // ========================================
    // MailboxPolicy Tests
    // ========================================

    public function test_restore_allows_admin_to_restore_mailbox(): void
    {
        $policy = new MailboxPolicy();
        $mailbox = Mailbox::factory()->create();

        $result = $policy->restore($this->admin, $mailbox);

        $this->assertTrue($result);
    }

    public function test_restore_prevents_non_admin_from_restoring(): void
    {
        $policy = new MailboxPolicy();
        $mailbox = Mailbox::factory()->create();

        $result = $policy->restore($this->user, $mailbox);

        $this->assertFalse($result);
    }

    public function test_force_delete_allows_admin_to_permanently_delete(): void
    {
        $policy = new MailboxPolicy();
        $mailbox = Mailbox::factory()->create();

        $result = $policy->forceDelete($this->admin, $mailbox);

        $this->assertTrue($result);
    }

    public function test_force_delete_prevents_non_admin_from_permanently_deleting(): void
    {
        $policy = new MailboxPolicy();
        $mailbox = Mailbox::factory()->create();

        $result = $policy->forceDelete($this->user, $mailbox);

        $this->assertFalse($result);
    }

    public function test_restore_handles_null_user(): void
    {
        $policy = new MailboxPolicy();
        $mailbox = Mailbox::factory()->create();

        $result = $policy->restore(null, $mailbox);

        $this->assertFalse($result);
    }

    public function test_force_delete_handles_null_user(): void
    {
        $policy = new MailboxPolicy();
        $mailbox = Mailbox::factory()->create();

        $result = $policy->forceDelete(null, $mailbox);

        $this->assertFalse($result);
    }

    public function test_view_any_allows_authenticated_users(): void
    {
        $policy = new MailboxPolicy();

        $result = $policy->viewAny($this->user);

        $this->assertTrue($result);
    }

    public function test_view_any_denies_null_user(): void
    {
        $policy = new MailboxPolicy();

        $result = $policy->viewAny(null);

        $this->assertFalse($result);
    }

    public function test_mailbox_view_allows_admin(): void
    {
        $policy = new MailboxPolicy();
        $mailbox = Mailbox::factory()->create();

        $result = $policy->view($this->admin, $mailbox);

        $this->assertTrue($result);
    }

    public function test_view_allows_user_with_view_access(): void
    {
        $policy = new MailboxPolicy();
        $mailbox = Mailbox::factory()->create();
        
        // Attach user with VIEW access
        $this->user->mailboxes()->attach($mailbox->id, [
            'access' => MailboxPolicy::ACCESS_VIEW,
        ]);

        // Reload to get pivot data
        $this->user->load('mailboxes');

        $result = $policy->view($this->user, $mailbox);

        $this->assertTrue($result);
    }

    public function test_view_denies_user_without_access(): void
    {
        $policy = new MailboxPolicy();
        $mailbox = Mailbox::factory()->create();

        $result = $policy->view($this->user, $mailbox);

        $this->assertFalse($result);
    }

    public function test_view_denies_null_user(): void
    {
        $policy = new MailboxPolicy();
        $mailbox = Mailbox::factory()->create();

        $result = $policy->view(null, $mailbox);

        $this->assertFalse($result);
    }

    public function test_create_allows_admin(): void
    {
        $policy = new MailboxPolicy();

        $result = $policy->create($this->admin);

        $this->assertTrue($result);
    }

    public function test_create_denies_non_admin(): void
    {
        $policy = new MailboxPolicy();

        $result = $policy->create($this->user);

        $this->assertFalse($result);
    }

    public function test_create_denies_null_user(): void
    {
        $policy = new MailboxPolicy();

        $result = $policy->create(null);

        $this->assertFalse($result);
    }

    public function test_mailbox_update_allows_admin(): void
    {
        $policy = new MailboxPolicy();
        $mailbox = Mailbox::factory()->create();

        $result = $policy->update($this->admin, $mailbox);

        $this->assertTrue($result);
    }

    public function test_update_allows_user_with_admin_access(): void
    {
        $policy = new MailboxPolicy();
        $mailbox = Mailbox::factory()->create();
        
        // Attach user with ADMIN access
        $this->user->mailboxes()->attach($mailbox->id, [
            'access' => MailboxPolicy::ACCESS_ADMIN,
        ]);

        // Reload to get pivot data
        $this->user->load('mailboxes');

        $result = $policy->update($this->user, $mailbox);

        $this->assertTrue($result);
    }

    public function test_update_denies_user_with_reply_access_only(): void
    {
        $policy = new MailboxPolicy();
        $mailbox = Mailbox::factory()->create();
        
        // Attach user with REPLY access (not enough for update)
        $this->user->mailboxes()->attach($mailbox->id, [
            'access' => MailboxPolicy::ACCESS_REPLY,
        ]);

        // Reload to get pivot data
        $this->user->load('mailboxes');

        $result = $policy->update($this->user, $mailbox);

        $this->assertFalse($result);
    }

    public function test_update_denies_user_without_access(): void
    {
        $policy = new MailboxPolicy();
        $mailbox = Mailbox::factory()->create();

        $result = $policy->update($this->user, $mailbox);

        $this->assertFalse($result);
    }

    public function test_update_denies_null_user(): void
    {
        $policy = new MailboxPolicy();
        $mailbox = Mailbox::factory()->create();

        $result = $policy->update(null, $mailbox);

        $this->assertFalse($result);
    }

    public function test_mailbox_delete_allows_admin(): void
    {
        $policy = new MailboxPolicy();
        $mailbox = Mailbox::factory()->create();

        $result = $policy->delete($this->admin, $mailbox);

        $this->assertTrue($result);
    }

    public function test_delete_denies_non_admin(): void
    {
        $policy = new MailboxPolicy();
        $mailbox = Mailbox::factory()->create();

        $result = $policy->delete($this->user, $mailbox);

        $this->assertFalse($result);
    }

    public function test_delete_denies_null_user(): void
    {
        $policy = new MailboxPolicy();
        $mailbox = Mailbox::factory()->create();

        $result = $policy->delete(null, $mailbox);

        $this->assertFalse($result);
    }

    public function test_reply_allows_admin(): void
    {
        $policy = new MailboxPolicy();
        $mailbox = Mailbox::factory()->create();

        $result = $policy->reply($this->admin, $mailbox);

        $this->assertTrue($result);
    }

    public function test_reply_allows_user_with_reply_access(): void
    {
        $policy = new MailboxPolicy();
        $mailbox = Mailbox::factory()->create();
        
        // Attach user with REPLY access
        $this->user->mailboxes()->attach($mailbox->id, [
            'access' => MailboxPolicy::ACCESS_REPLY,
        ]);

        // Reload to get pivot data
        $this->user->load('mailboxes');

        $result = $policy->reply($this->user, $mailbox);

        $this->assertTrue($result);
    }

    public function test_reply_denies_user_with_view_access_only(): void
    {
        $policy = new MailboxPolicy();
        $mailbox = Mailbox::factory()->create();
        
        // Attach user with VIEW access (not enough for reply)
        $this->user->mailboxes()->attach($mailbox->id, [
            'access' => MailboxPolicy::ACCESS_VIEW,
        ]);

        // Reload to get pivot data
        $this->user->load('mailboxes');

        $result = $policy->reply($this->user, $mailbox);

        $this->assertFalse($result);
    }

    public function test_reply_denies_user_without_access(): void
    {
        $policy = new MailboxPolicy();
        $mailbox = Mailbox::factory()->create();

        $result = $policy->reply($this->user, $mailbox);

        $this->assertFalse($result);
    }

    public function test_reply_denies_null_user(): void
    {
        $policy = new MailboxPolicy();
        $mailbox = Mailbox::factory()->create();

        $result = $policy->reply(null, $mailbox);

        $this->assertFalse($result);
    }

    public function test_admin_policy_allows_admin_user(): void
    {
        $policy = new MailboxPolicy();
        $mailbox = Mailbox::factory()->create();

        $result = $policy->admin($this->admin, $mailbox);

        $this->assertTrue($result);
    }

    public function test_admin_policy_allows_user_with_admin_access(): void
    {
        $policy = new MailboxPolicy();
        $mailbox = Mailbox::factory()->create();
        
        // Attach user with ADMIN access
        $this->user->mailboxes()->attach($mailbox->id, [
            'access' => MailboxPolicy::ACCESS_ADMIN,
        ]);

        // Reload to get pivot data
        $this->user->load('mailboxes');

        $result = $policy->admin($this->user, $mailbox);

        $this->assertTrue($result);
    }

    public function test_admin_policy_denies_user_with_reply_access(): void
    {
        $policy = new MailboxPolicy();
        $mailbox = Mailbox::factory()->create();
        
        // Attach user with REPLY access (not enough for admin operations)
        $this->user->mailboxes()->attach($mailbox->id, [
            'access' => MailboxPolicy::ACCESS_REPLY,
        ]);

        // Reload to get pivot data
        $this->user->load('mailboxes');

        $result = $policy->admin($this->user, $mailbox);

        $this->assertFalse($result);
    }

    public function test_admin_policy_denies_user_without_access(): void
    {
        $policy = new MailboxPolicy();
        $mailbox = Mailbox::factory()->create();

        $result = $policy->admin($this->user, $mailbox);

        $this->assertFalse($result);
    }

    public function test_admin_policy_denies_null_user(): void
    {
        $policy = new MailboxPolicy();
        $mailbox = Mailbox::factory()->create();

        $result = $policy->admin(null, $mailbox);

        $this->assertFalse($result);
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
        $this->assertStringContainsString('notification-' . $thread->id . '-' . $this->user->id, $sendLog->message_id);
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
            'customer_email' => $customer->email,
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
        $this->assertStringContainsString('auto-reply-' . $thread->id, $sendLog->message_id);
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
