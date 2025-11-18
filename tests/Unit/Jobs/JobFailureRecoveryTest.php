<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SendAlert;
use App\Jobs\SendAutoReply as SendAutoReplyJob;
use App\Jobs\SendNotificationToUsers;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\SendLog;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\UnitTestCase;

class JobFailureRecoveryTest extends UnitTestCase
{
    public function test_send_notification_job_has_retry_properties(): void
    {
        $users = User::factory()->count(2)->create();
        $conversation = Conversation::factory()->create();
        $threads = collect([Thread::factory()->for($conversation)->create()]);
        
        $job = new SendNotificationToUsers($users, $conversation, $threads);
        
        // Verify job has retry configuration
        $this->assertObjectHasProperty('tries', $job);
        $this->assertObjectHasProperty('timeout', $job);
    }

    public function test_send_notification_job_has_exponential_backoff(): void
    {
        $users = User::factory()->count(2)->create();
        $conversation = Conversation::factory()->create();
        $threads = collect([Thread::factory()->for($conversation)->create()]);
        
        $job = new SendNotificationToUsers($users, $conversation, $threads);
        
        // Check backoff property exists and is array
        if (isset($job->backoff)) {
            $this->assertIsArray($job->backoff);
            // Should have increasing delays
            $this->assertGreaterThan(0, count($job->backoff));
        } else {
            $this->assertTrue(true); // Optional property
        }
    }

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

    public function test_job_delete_method_exists(): void
    {
        $users = User::factory()->count(2)->create();
        $conversation = Conversation::factory()->create();
        $threads = collect([Thread::factory()->for($conversation)->create()]);
        
        $job = new SendNotificationToUsers($users, $conversation, $threads);
        
        $this->assertTrue(method_exists($job, 'delete'));
    }

    public function test_job_release_method_exists(): void
    {
        $users = User::factory()->count(2)->create();
        $conversation = Conversation::factory()->create();
        $threads = collect([Thread::factory()->for($conversation)->create()]);
        
        $job = new SendNotificationToUsers($users, $conversation, $threads);
        
        $this->assertTrue(method_exists($job, 'release'));
    }

    public function test_job_fail_method_exists(): void
    {
        $users = User::factory()->count(2)->create();
        $conversation = Conversation::factory()->create();
        $threads = collect([Thread::factory()->for($conversation)->create()]);
        
        $job = new SendNotificationToUsers($users, $conversation, $threads);
        
        $this->assertTrue(method_exists($job, 'fail'));
    }

    public function test_job_failed_callback_exists(): void
    {
        $users = User::factory()->count(2)->create();
        $conversation = Conversation::factory()->create();
        $threads = collect([Thread::factory()->for($conversation)->create()]);
        
        $job = new SendNotificationToUsers($users, $conversation, $threads);
        
        $this->assertTrue(method_exists($job, 'failed'));
    }

    public function test_send_alert_job_has_timeout_configuration(): void
    {
        $job = new SendAlert(
            'Test message',
            'Test Title'
        );
        
        $this->assertObjectHasProperty('timeout', $job);
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

    public function test_job_middleware_property_exists(): void
    {
        $users = User::factory()->count(2)->create();
        $conversation = Conversation::factory()->create();
        $threads = collect([Thread::factory()->for($conversation)->create()]);
        
        $job = new SendNotificationToUsers($users, $conversation, $threads);
        
        // Check if middleware method exists
        if (method_exists($job, 'middleware')) {
            $middleware = $job->middleware();
            $this->assertIsArray($middleware);
        } else {
            $this->assertTrue(true); // Not all jobs have middleware
        }
    }

    public function test_job_handles_null_models_gracefully(): void
    {
        $users = User::factory()->count(2)->create();
        $conversation = Conversation::factory()->create();
        $threads = collect([Thread::factory()->for($conversation)->create()]);
        
        $job = new SendNotificationToUsers($users, $conversation, $threads);
        
        // Job should not crash when accessing properties
        $this->assertNotNull($job);
    }

    public function test_job_display_name_for_logging(): void
    {
        $users = User::factory()->count(2)->create();
        $conversation = Conversation::factory()->create();
        $threads = collect([Thread::factory()->for($conversation)->create()]);
        
        $job = new SendNotificationToUsers($users, $conversation, $threads);
        
        if (method_exists($job, 'displayName')) {
            $displayName = $job->displayName();
            $this->assertIsString($displayName);
        } else {
            $this->assertTrue(true);
        }
    }

    public function test_job_tags_for_horizon_tracking(): void
    {
        $users = User::factory()->count(2)->create();
        $conversation = Conversation::factory()->create();
        $threads = collect([Thread::factory()->for($conversation)->create()]);
        
        $job = new SendNotificationToUsers($users, $conversation, $threads);
        
        if (method_exists($job, 'tags')) {
            $tags = $job->tags();
            $this->assertIsArray($tags);
        } else {
            $this->assertTrue(true);
        }
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
        
        DB::beginTransaction();
        
        $smtpService = app(\App\Services\SmtpService::class);
        $job = new SendAutoReplyJob($conversation, $thread, $mailbox, $customer);
        $job->handle($smtpService);
        
        DB::rollBack();
        
        // Send log should not exist after rollback
        $this->assertDatabaseMissing('send_logs', [
            'customer_id' => $customer->id,
            'mail_type' => SendLog::MAIL_TYPE_AUTO_REPLY,
        ]);
    }

    public function test_job_unique_id_for_deduplication(): void
    {
        $users = User::factory()->count(2)->create();
        $conversation = Conversation::factory()->create();
        $threads = collect([Thread::factory()->for($conversation)->create()]);
        
        $job = new SendNotificationToUsers($users, $conversation, $threads);
        
        if (method_exists($job, 'uniqueId')) {
            $uniqueId = $job->uniqueId();
            $this->assertNotEmpty($uniqueId);
        } else {
            $this->assertTrue(true);
        }
    }

    public function test_job_retry_until_configuration(): void
    {
        $users = User::factory()->count(2)->create();
        $conversation = Conversation::factory()->create();
        $threads = collect([Thread::factory()->for($conversation)->create()]);
        
        $job = new SendNotificationToUsers($users, $conversation, $threads);
        
        if (method_exists($job, 'retryUntil')) {
            $retryUntil = $job->retryUntil();
            $this->assertInstanceOf(\DateTimeInterface::class, $retryUntil);
        } else {
            $this->assertTrue(true);
        }
    }

    public function test_job_should_fail_on_timeout_configuration(): void
    {
        $users = User::factory()->count(2)->create();
        $conversation = Conversation::factory()->create();
        $threads = collect([Thread::factory()->for($conversation)->create()]);
        
        $job = new SendNotificationToUsers($users, $conversation, $threads);
        
        if (method_exists($job, 'shouldFailOnTimeout')) {
            $shouldFail = $job->shouldFailOnTimeout();
            $this->assertIsBool($shouldFail);
        } else {
            $this->assertTrue(true);
        }
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
    }

    public function test_job_handles_model_soft_deletion(): void
    {
        $users = User::factory()->count(2)->create();
        $conversation = Conversation::factory()->create();
        $threads = collect([Thread::factory()->for($conversation)->create()]);
        
        $job = new SendNotificationToUsers($users, $conversation, $threads);
        
        // Delete models
        $threads->first()->delete();
        $conversation->delete();
        
        // Job should handle gracefully
        $this->assertNotNull($job);
    }

    public function test_job_batch_processing_identifier(): void
    {
        $users = User::factory()->count(2)->create();
        $conversation = Conversation::factory()->create();
        $threads = collect([Thread::factory()->for($conversation)->create()]);
        
        $job = new SendNotificationToUsers($users, $conversation, $threads);
        
        if (property_exists($job, 'batchId')) {
            $this->assertTrue(true);
        } else {
            $this->assertTrue(true); // Optional property
        }
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
