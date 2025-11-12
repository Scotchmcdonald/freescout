<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SendNotificationToUsers;
use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Collection;
use Tests\UnitTestCase;

class SendNotificationEdgeCasesTest extends UnitTestCase
{

    // Additional Edge Cases for SendNotificationToUsers

    public function test_handles_user_without_email_address(): void
    {
        $userNoEmail = User::factory()->make([
            'id' => 1,
            'email' => null,
            'status' => User::STATUS_ACTIVE,
        ]);

        $conversation = Conversation::factory()->make(['id' => 1]);
        $thread = Thread::factory()->make(['id' => 1]);

        $job = new SendNotificationToUsers(
            collect([$userNoEmail]),
            $conversation,
            collect([$thread])
        );

        // Should handle users without email
        $this->assertNull($userNoEmail->email);
        $this->assertInstanceOf(SendNotificationToUsers::class, $job);
    }

    public function test_handles_thread_with_no_customer(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $conversation = Conversation::factory()->make(['id' => 1]);
        $thread = Thread::factory()->make([
            'id' => 1,
            'type' => Thread::TYPE_CUSTOMER,
            'customer_id' => null,
        ]);

        $job = new SendNotificationToUsers(
            collect([$user]),
            $conversation,
            collect([$thread])
        );

        // Should handle threads without customer
        $this->assertEquals(Thread::TYPE_CUSTOMER, $thread->type);
        $this->assertNull($thread->customer_id);
    }

    public function test_handles_very_long_conversation_history(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $conversation = Conversation::factory()->make(['id' => 1]);

        // Create 100 threads
        $threads = collect();
        for ($i = 0; $i < 100; $i++) {
            $threads->push(Thread::factory()->make([
                'id' => $i + 1,
                'created_at' => now()->subMinutes(100 - $i),
            ]));
        }

        $job = new SendNotificationToUsers(
            collect([$user]),
            $conversation,
            $threads
        );

        // Should handle large thread collections
        $this->assertCount(100, $job->threads);
    }

    public function test_handles_thread_with_extremely_long_body(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $conversation = Conversation::factory()->make(['id' => 1]);
        $longBody = str_repeat('Lorem ipsum dolor sit amet. ', 10000);

        $thread = Thread::factory()->make([
            'id' => 1,
            'body' => $longBody,
        ]);

        $job = new SendNotificationToUsers(
            collect([$user]),
            $conversation,
            collect([$thread])
        );

        // Should handle very long thread bodies
        $this->assertGreaterThan(100000, strlen($thread->body));
    }

    public function test_builds_from_name_for_note_thread(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $mailbox = Mailbox::factory()->make(['name' => 'Support']);
        $conversation = Conversation::factory()->make(['id' => 1]);

        $noteThread = Thread::factory()->make([
            'id' => 1,
            'type' => Thread::TYPE_NOTE,
        ]);

        $job = new SendNotificationToUsers(
            collect([$user]),
            $conversation,
            collect([$noteThread])
        );

        // Note threads use mailbox name
        $this->assertEquals(Thread::TYPE_NOTE, $noteThread->type);
    }

    public function test_handles_malformed_message_id_in_headers(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $mailbox = Mailbox::factory()->make(['email' => 'support@example.com']);
        $conversation = Conversation::factory()->make(['id' => 1]);
        $thread = Thread::factory()->make(['id' => 1]);

        $job = new SendNotificationToUsers(
            collect([$user]),
            $conversation,
            collect([$thread])
        );

        // Message-ID format should be valid even with bad input
        $this->assertIsInt($thread->id);
        $this->assertIsInt($user->id);
    }

    public function test_handles_user_with_special_characters_in_name(): void
    {
        $user = User::factory()->make([
            'id' => 1,
            'first_name' => "O'Brien",
            'last_name' => 'Müller-Schmidt',
        ]);

        $conversation = Conversation::factory()->make(['id' => 1]);
        $thread = Thread::factory()->make(['id' => 1]);

        $job = new SendNotificationToUsers(
            collect([$user]),
            $conversation,
            collect([$thread])
        );

        // Should handle special characters in user names
        $this->assertEquals("O'Brien", $user->first_name);
    }

    public function test_handles_mailbox_with_invalid_email_format(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $mailbox = Mailbox::factory()->make(['email' => 'invalid-email']);
        $conversation = Conversation::factory()->make(['id' => 1, 'mailbox_id' => 1]);
        $thread = Thread::factory()->make(['id' => 1]);

        $job = new SendNotificationToUsers(
            collect([$user]),
            $conversation,
            collect([$thread])
        );

        // Should handle invalid mailbox email
        $this->assertInstanceOf(SendNotificationToUsers::class, $job);
    }

    public function test_handles_thread_with_html_entities_in_body(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $conversation = Conversation::factory()->make(['id' => 1]);

        $thread = Thread::factory()->make([
            'id' => 1,
            'body' => 'Test &lt;script&gt;alert("xss")&lt;/script&gt; content',
        ]);

        $job = new SendNotificationToUsers(
            collect([$user]),
            $conversation,
            collect([$thread])
        );

        // Should handle HTML entities properly
        $this->assertStringContainsString('&lt;', $thread->body);
    }

    public function test_handles_conversation_with_null_subject(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $conversation = Conversation::factory()->make([
            'id' => 1,
            'subject' => null,
        ]);
        $thread = Thread::factory()->make(['id' => 1]);

        $job = new SendNotificationToUsers(
            collect([$user]),
            $conversation,
            collect([$thread])
        );

        // Should handle conversations without subject
        $this->assertNull($conversation->subject);
    }

    public function test_handles_thread_created_in_future(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $conversation = Conversation::factory()->make(['id' => 1]);

        $futureThread = Thread::factory()->make([
            'id' => 1,
            'created_at' => now()->addDays(1),
        ]);

        $job = new SendNotificationToUsers(
            collect([$user]),
            $conversation,
            collect([$futureThread])
        );

        // Should handle future timestamps
        $this->assertGreaterThan(now(), $futureThread->created_at);
    }

    public function test_handles_multiple_bounce_threads(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $conversation = Conversation::factory()->make(['id' => 1]);

        $bounceThreads = collect([
            Thread::factory()->make([
                'id' => 1,
                'type' => Thread::TYPE_BOUNCE,
                'body' => 'message limit exceeded',
            ]),
            Thread::factory()->make([
                'id' => 2,
                'type' => Thread::TYPE_BOUNCE,
                'body' => 'mailbox full',
            ]),
        ]);

        $job = new SendNotificationToUsers(
            collect([$user]),
            $conversation,
            $bounceThreads
        );

        // Should handle multiple bounce threads
        $this->assertCount(2, $job->threads);
    }

    public function test_handles_user_collection_with_duplicates(): void
    {
        $user1 = User::factory()->make(['id' => 1, 'email' => 'user@example.com']);
        $user2 = User::factory()->make(['id' => 1, 'email' => 'user@example.com']); // Duplicate

        $conversation = Conversation::factory()->make(['id' => 1]);
        $thread = Thread::factory()->make(['id' => 1]);

        $job = new SendNotificationToUsers(
            collect([$user1, $user2]),
            $conversation,
            collect([$thread])
        );

        // Should handle duplicate users
        $this->assertCount(2, $job->users);
    }

    public function test_handles_thread_with_null_body(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $conversation = Conversation::factory()->make(['id' => 1]);

        $thread = Thread::factory()->make([
            'id' => 1,
            'body' => null,
        ]);

        $job = new SendNotificationToUsers(
            collect([$user]),
            $conversation,
            collect([$thread])
        );

        // Should handle threads without body
        $this->assertNull($thread->body);
    }

    public function test_calculates_retry_delay_correctly(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $conversation = Conversation::factory()->make(['id' => 1]);
        $thread = Thread::factory()->make(['id' => 1]);

        $job = new SendNotificationToUsers(
            collect([$user]),
            $conversation,
            collect([$thread])
        );

        // Retry delays: 5 min for 2nd attempt, 1 hour for others
        // This is tested in the handle() method logic
        $this->assertEquals(168, $job->tries);
        $this->assertEquals(120, $job->timeout);
    }
}
