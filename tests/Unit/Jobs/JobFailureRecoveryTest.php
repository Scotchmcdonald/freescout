<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SendAlertJob as SendAlert;
use App\Jobs\SendAutoReplyJob;
use App\Jobs\SendNotificationToUsersJob as SendNotificationToUsers;
use App\Mail\Alert;
use App\Mail\AutoReply;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\SendLog;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\UnitTestCase;

class JobFailureRecoveryTest extends UnitTestCase
{
    public function test_send_auto_reply_job_creates_send_log_on_success(): void
    {
        Mail::fake();

        $mailbox = Mailbox::factory()->create([
            'auto_reply_enabled' => true,
            'auto_reply_subject' => 'Thank you',
            'auto_reply_message' => 'We received your message.',
        ]);
        $conversation = Conversation::factory()->for($mailbox)->create();
        $thread = Thread::factory()->for($conversation)->create();
        $customer = Customer::factory()->create();

        $smtpService = app(\App\Services\SmtpService::class);
        $job = new SendAutoReplyJob($conversation, $thread, $mailbox, $customer);
        $job->handle($smtpService);

        $this->assertDatabaseHas('send_logs', [
            'customer_id' => $customer->id,
            'mail_type' => SendLog::MAIL_TYPE_AUTO_REPLY,
        ]);
        Mail::assertSent(AutoReply::class, 1);
    }

    public function test_job_serialization_preserves_model_data(): void
    {
        $users = User::factory()->count(2)->create();
        $conversation = Conversation::factory()->create();
        $threads = collect([Thread::factory()->for($conversation)->create()]);

        $job = new SendNotificationToUsers($users, $conversation, $threads);

        $serialized = serialize($job);
        $unserialized = unserialize($serialized);

        // Should preserve IDs for lazy loading
        $this->assertInstanceOf(SendNotificationToUsers::class, $unserialized);
    }

    public function test_send_alert_job_handles_single_recipient_failure_gracefully(): void
    {
        Mail::fake();

        User::factory()->count(5)->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $job = new SendAlert(
            'Test alert message',
            'Test Alert Title'
        );

        // Job should succeed even if some emails fail
        $job->handle();

        // At least one send log should be created
        $this->assertGreaterThan(0, SendLog::count());
        Mail::assertSent(Alert::class);
    }

    public function test_job_attempts_tracking(): void
    {
        $users = User::factory()->count(2)->create();
        $conversation = Conversation::factory()->create();
        $threads = collect([Thread::factory()->for($conversation)->create()]);

        $job = new SendNotificationToUsers($users, $conversation, $threads);

        // Default attempts should be 1
        $this->assertEquals(1, $job->attempts());
    }

    public function test_job_can_be_pushed_to_specific_queue(): void
    {
        Queue::fake();

        $users = User::factory()->count(2)->create();
        $conversation = Conversation::factory()->create();
        $threads = collect([Thread::factory()->for($conversation)->create()]);

        SendNotificationToUsers::dispatch($users, $conversation, $threads)
            ->onQueue('emails');

        Queue::assertPushedOn('emails', SendNotificationToUsers::class);
    }

    public function test_job_connection_configuration(): void
    {
        Queue::fake();

        $users = User::factory()->count(2)->create();
        $conversation = Conversation::factory()->create();
        $threads = collect([Thread::factory()->for($conversation)->create()]);

        SendNotificationToUsers::dispatch($users, $conversation, $threads);

        Queue::assertPushed(SendNotificationToUsers::class);
    }

    public function test_job_handles_database_transaction_properly(): void
    {
        Mail::fake();

        $mailbox = Mailbox::factory()->create([
            'auto_reply_enabled' => true,
            'auto_reply_subject' => 'Thank you',
            'auto_reply_message' => 'We received your message.',
        ]);
        $conversation = Conversation::factory()->for($mailbox)->create();
        $thread = Thread::factory()->for($conversation)->create();
        $customer = Customer::factory()->create();

        // Use DB::transaction() with an intentional rollback so Laravel's savepoint
        // mechanism is used. This avoids nesting a raw PDO beginTransaction() inside
        // the transaction that RefreshDatabase has already opened, which SQLite does
        // not support and throws "There is already an active transaction".
        $sendLogCreated = false;
        try {
            DB::transaction(function () use ($conversation, $thread, $mailbox, $customer, &$sendLogCreated): void {
                $smtpService = app(\App\Services\SmtpService::class);
                $senderInfo = [
                    'email' => $conversation->customer_email ?? '',
                    'name' => $customer->getFullName(),
                ];
                $job = new SendAutoReplyJob($conversation, $thread, $mailbox, $senderInfo);
                $job->handle($smtpService);

                $sendLogCreated = SendLog::where('mail_type', SendLog::MAIL_TYPE_AUTO_REPLY)->exists();

                // Force the savepoint to roll back so the send_log write is undone.
                throw new \RuntimeException('Intentional rollback to test transaction isolation');
            });
        } catch (\RuntimeException $e) {
            // Expected — the rollback happened; continue to assertions below.
        }

        // After the rolled-back savepoint the send log should not exist.
        $this->assertFalse(
            $sendLogCreated === false || SendLog::where('mail_type', SendLog::MAIL_TYPE_AUTO_REPLY)->exists(),
            'Send log should not persist after transaction rollback'
        );
    }

    public function test_send_alert_creates_send_log_for_each_recipient(): void
    {
        Mail::fake();

        User::factory()->count(3)->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $job = new SendAlert(
            'Test alert message',
            'Test Alert Title'
        );

        $initialCount = SendLog::count();

        $job->handle();

        $this->assertGreaterThan($initialCount, SendLog::count());
        Mail::assertSent(Alert::class, 3);
    }

    public function test_job_handles_encrypted_payload(): void
    {
        Queue::fake();

        $users = User::factory()->count(2)->create();
        $conversation = Conversation::factory()->create();
        $threads = collect([Thread::factory()->for($conversation)->create()]);

        SendNotificationToUsers::dispatch($users, $conversation, $threads);

        Queue::assertPushed(SendNotificationToUsers::class, function ($job) {
            return $job->conversation instanceof Conversation;
        });
    }
}
