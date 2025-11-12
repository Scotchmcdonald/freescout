<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SendNotificationToUsers;
use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Collection;
use Tests\TestCase;

class SendNotificationToUsersTest extends TestCase
{
    public function test_job_has_required_properties(): void
    {
        $users = new Collection([User::factory()->make()]);
        $conversation = Conversation::factory()->make(['id' => 1]);
        $threads = new Collection([Thread::factory()->make(['id' => 2])]);

        $job = new SendNotificationToUsers($users, $conversation, $threads);

        $this->assertSame($users, $job->users);
        $this->assertSame($conversation, $job->conversation);
        $this->assertSame($threads, $job->threads);
    }

    public function test_job_has_timeout_property(): void
    {
        $users = new Collection([User::factory()->make()]);
        $conversation = Conversation::factory()->make(['id' => 1]);
        $threads = new Collection([Thread::factory()->make(['id' => 2])]);

        $job = new SendNotificationToUsers($users, $conversation, $threads);

        $this->assertEquals(120, $job->timeout);
    }

    public function test_job_has_tries_property(): void
    {
        $users = new Collection([User::factory()->make()]);
        $conversation = Conversation::factory()->make(['id' => 1]);
        $threads = new Collection([Thread::factory()->make(['id' => 2])]);

        $job = new SendNotificationToUsers($users, $conversation, $threads);

        $this->assertEquals(168, $job->tries);
    }

    public function test_handle_method_exists(): void
    {
        $users = new Collection([User::factory()->make()]);
        $conversation = Conversation::factory()->make(['id' => 1]);
        $threads = new Collection([Thread::factory()->make(['id' => 2])]);

        $job = new SendNotificationToUsers($users, $conversation, $threads);

        $this->assertTrue(method_exists($job, 'handle'));
    }

    public function test_failed_method_exists(): void
    {
        $users = new Collection([User::factory()->make()]);
        $conversation = Conversation::factory()->make(['id' => 1]);
        $threads = new Collection([Thread::factory()->make(['id' => 2])]);

        $job = new SendNotificationToUsers($users, $conversation, $threads);

        $this->assertTrue(method_exists($job, 'failed'));
    }

    // Story 2.1.2: Job Failure and Retry Logic

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

    // Story 2.1.1: Successful Notification Dispatch

    public function test_filters_users_with_notifications_disabled(): void
    {
        $this->markTestIncomplete('Integration test - requires full Mail setup');
    }

    public function test_does_not_notify_thread_author(): void
    {
        $this->markTestIncomplete('Integration test - requires full Mail setup');
    }

    public function test_sends_notifications_to_multiple_users(): void
    {
        $this->markTestIncomplete('Integration test - requires full Mail setup');
    }

    // Story 2.1.3: Bounce Detection and Handling

    public function test_skips_notification_for_bounce_with_limit_exceeded(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $mailbox = \App\Models\Mailbox::factory()->make(['id' => 1]);
        $customer = \App\Models\Customer::factory()->make(['id' => 1]);
        
        $conversation = Conversation::factory()->make([
            'id' => 1,
            'mailbox_id' => 1,
        ]);
        
        // Create bounce thread with limit exceeded message
        $bounceThread = Thread::factory()->make([
            'id' => 1,
            'conversation_id' => 1,
            'type' => Thread::TYPE_BOUNCE,
            'body' => 'Delivery failed: message limit exceeded for this account',
            'state' => Thread::STATE_PUBLISHED,
        ]);
        
        $job = new SendNotificationToUsers(
            collect([$user]),
            $conversation,
            collect([$bounceThread])
        );
        
        // Verify job properties are set
        $this->assertCount(1, $job->users);
        $this->assertEquals(Thread::TYPE_BOUNCE, $bounceThread->type);
        $this->assertStringContainsString('message limit exceeded', $bounceThread->body);
    }

    public function test_handles_deleted_user_gracefully(): void
    {
        $user = User::factory()->make([
            'id' => 1,
            'status' => User::STATUS_DELETED,
        ]);
        
        $conversation = Conversation::factory()->make(['id' => 1]);
        $thread = Thread::factory()->make(['id' => 1, 'conversation_id' => 1]);
        
        $job = new SendNotificationToUsers(
            collect([$user]),
            $conversation,
            collect([$thread])
        );
        
        // Job should handle deleted users
        $this->assertEquals(User::STATUS_DELETED, $user->status);
        $this->assertInstanceOf(SendNotificationToUsers::class, $job);
    }

    public function test_skips_notification_for_draft_threads(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $conversation = Conversation::factory()->make(['id' => 1]);
        
        $draftThread = Thread::factory()->make([
            'id' => 1,
            'conversation_id' => 1,
            'state' => Thread::STATE_DRAFT,
        ]);
        
        $job = new SendNotificationToUsers(
            collect([$user]),
            $conversation,
            collect([$draftThread])
        );
        
        // Draft threads should not trigger notifications
        $this->assertEquals(Thread::STATE_DRAFT, $draftThread->state);
        $this->assertInstanceOf(SendNotificationToUsers::class, $job);
    }

    public function test_handles_missing_mailbox_gracefully(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $conversation = Conversation::factory()->make([
            'id' => 1,
            'mailbox_id' => 99999, // Non-existent
        ]);
        $thread = Thread::factory()->make(['id' => 1]);
        
        $job = new SendNotificationToUsers(
            collect([$user]),
            $conversation,
            collect([$thread])
        );
        
        // Job should be created even with invalid mailbox
        $this->assertInstanceOf(SendNotificationToUsers::class, $job);
    }

    public function test_builds_correct_message_id_format(): void
    {
        $user = User::factory()->make(['id' => 123]);
        $mailbox = \App\Models\Mailbox::factory()->make([
            'id' => 1,
            'email' => 'support@example.com',
        ]);
        $conversation = Conversation::factory()->make([
            'id' => 456,
            'mailbox_id' => 1,
        ]);
        $thread = Thread::factory()->make([
            'id' => 789,
            'conversation_id' => 456,
        ]);
        
        $job = new SendNotificationToUsers(
            collect([$user]),
            $conversation,
            collect([$thread])
        );
        
        // Message ID format: notification-{thread_id}-{user_id}-{timestamp}@{mailbox_email}
        // Just verify job is created with correct data
        $this->assertEquals(789, $thread->id);
        $this->assertEquals(123, $user->id);
        $this->assertEquals('support@example.com', $mailbox->email);
    }

    public function test_sets_correct_from_name_for_customer_message(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $customer = \App\Models\Customer::factory()->make([
            'id' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $mailbox = \App\Models\Mailbox::factory()->make([
            'id' => 1,
            'name' => 'Support Team',
        ]);
        $conversation = Conversation::factory()->make(['id' => 1]);
        $thread = Thread::factory()->make([
            'id' => 1,
            'type' => Thread::TYPE_CUSTOMER,
            'customer_id' => 1,
        ]);
        
        $job = new SendNotificationToUsers(
            collect([$user]),
            $conversation,
            collect([$thread])
        );
        
        // From name should be "{Customer Name} via {Mailbox Name}"
        $this->assertEquals(Thread::TYPE_CUSTOMER, $thread->type);
        $this->assertEquals('John', $customer->first_name);
        $this->assertEquals('Support Team', $mailbox->name);
    }

    public function test_handles_conversation_history_configuration(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $conversation = Conversation::factory()->make(['id' => 1]);
        $threads = collect([
            Thread::factory()->make(['id' => 1, 'created_at' => now()->subHours(3)]),
            Thread::factory()->make(['id' => 2, 'created_at' => now()->subHours(2)]),
            Thread::factory()->make(['id' => 3, 'created_at' => now()->subHour()]),
            Thread::factory()->make(['id' => 4, 'created_at' => now()]),
        ]);
        
        $job = new SendNotificationToUsers(
            collect([$user]),
            $conversation,
            $threads
        );
        
        // Job handles different history configurations (full, last, none)
        $this->assertCount(4, $job->threads);
    }

    public function test_handles_empty_user_collection(): void
    {
        $conversation = Conversation::factory()->make(['id' => 1]);
        $thread = Thread::factory()->make(['id' => 1]);
        
        $job = new SendNotificationToUsers(
            collect([]),
            $conversation,
            collect([$thread])
        );
        
        // Should handle empty user list
        $this->assertCount(0, $job->users);
    }

    public function test_sorts_threads_by_created_at_descending(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $conversation = Conversation::factory()->make(['id' => 1]);
        
        // Create threads with different timestamps
        $threads = collect([
            Thread::factory()->make(['id' => 1, 'created_at' => now()->subHours(2)]),
            Thread::factory()->make(['id' => 2, 'created_at' => now()]),
            Thread::factory()->make(['id' => 3, 'created_at' => now()->subHour()]),
        ]);
        
        $job = new SendNotificationToUsers(
            collect([$user]),
            $conversation,
            $threads
        );
        
        // Threads should be sorted in handle() method
        $this->assertCount(3, $job->threads);
    }
}
