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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class JobsPoliciesTest extends TestCase
{
    use RefreshDatabase;

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
}
