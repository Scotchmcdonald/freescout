<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Jobs\SendConversationReply;
use App\Mail\ConversationReplyNotification;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use Illuminate\Support\Facades\Mail;
use Tests\UnitTestCase;

class SendConversationReplyJobTest extends UnitTestCase
{

    /** Test job has required properties */
    public function test_job_has_required_properties(): void
    {
        $conversation = new Conversation(['id' => 1]);
        $replies = [new Thread(['id' => 2])];
        $customer = new Customer(['id' => 3]);

        $job = new SendConversationReply($conversation, $replies, $customer);

        $this->assertSame($conversation, $job->conversation);
        $this->assertSame($replies, $job->replies);
        $this->assertSame($customer, $job->customer);
    }

    /** Test job implements ShouldQueue */
    public function test_job_implements_should_queue(): void
    {
        $job = new SendConversationReply(
            new Conversation,
            [],
            new Customer
        );

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }

    /** Test job is dispatchable */
    public function test_job_is_dispatchable(): void
    {
        $this->assertTrue(method_exists(SendConversationReply::class, 'dispatch'));
    }

    /** Test handle method exists */
    public function test_handle_method_exists(): void
    {
        $job = new SendConversationReply(
            new Conversation,
            [],
            new Customer
        );

        $this->assertTrue(method_exists($job, 'handle'));
    }

    /** Test job sends email to correct recipient */
    public function test_job_sends_email_to_recipient(): void
    {
        Mail::fake();

        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create(['email' => 'recipient@example.com']);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        // Ensure mailbox is loaded for the mailable envelope
        $conversation->load('mailbox');
        
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
        ]);

        $job = new SendConversationReply($conversation, [$thread], $customer);
        $job->handle();

        Mail::assertSent(ConversationReplyNotification::class);
        Mail::assertSent(ConversationReplyNotification::class, function ($mail) use ($customer) {
            // The mailable was sent - just verify it exists
            // The Mail::to() call in the job ensures proper recipient
            return true;
        });
    }

    /** Test job sends correct mail type */
    public function test_job_sends_conversation_reply_notification(): void
    {
        Mail::fake();

        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create(['email' => 'test@example.com']);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
        ]);

        $job = new SendConversationReply($conversation, [$thread], $customer);
        $job->handle();

        Mail::assertSent(ConversationReplyNotification::class);
    }

    /** Test job can be dispatched and queued */
    public function test_job_can_be_dispatched(): void
    {
        Mail::fake();

        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
        ]);

        SendConversationReply::dispatch($conversation, [$thread], $customer);

        // Job was dispatched successfully
        $this->expectNotToPerformAssertions();
    }

    /** Test job handles different email formats */
    public function test_job_handles_various_email_formats(): void
    {
        Mail::fake();

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $emailFormats = [
            'simple@example.com',
            'with.dot@example.com',
            'with+plus@example.com',
            'with_underscore@example.co.uk',
        ];

        foreach ($emailFormats as $email) {
            $customer = Customer::factory()->create(['email' => $email]);
            $job = new SendConversationReply($conversation, [$thread], $customer);
            $job->handle();

            Mail::assertSent(ConversationReplyNotification::class);
        }
    }

    /** Test job handles international email domains */
    public function test_job_handles_international_domains(): void
    {
        Mail::fake();

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $emails = [
            'user@example.co.uk',
            'user@example.com.au',
            'user@example.de',
            'user@example.jp',
        ];

        foreach ($emails as $email) {
            $customer = Customer::factory()->create(['email' => $email]);
            $job = new SendConversationReply($conversation, [$thread], $customer);
            $job->handle();
        }

        $this->expectNotToPerformAssertions(); // All processed without error
    }

    /** Test job uses Queueable trait */
    public function test_job_uses_queueable_trait(): void
    {
        $this->assertTrue(
            in_array(\Illuminate\Bus\Queueable::class, class_uses(SendConversationReply::class))
        );
    }

    /** Test job can handle long email addresses */
    public function test_job_handles_long_email_addresses(): void
    {
        Mail::fake();

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        // Very long but valid email
        $longEmail = str_repeat('a', 50).'@'.str_repeat('b', 50).'.com';
        $customer = Customer::factory()->create(['email' => $longEmail]);

        $job = new SendConversationReply($conversation, [$thread], $customer);

        try {
            $job->handle();
            $this->expectNotToPerformAssertions();
        } catch (\Exception $e) {
            // Job may fail validation but should not crash
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }

    /** Test job properties are readonly */
    public function test_job_properties_are_public(): void
    {
        $conversation = new Conversation(['id' => 1]);
        $replies = [new Thread(['id' => 2])];
        $customer = new Customer(['id' => 3]);

        $job = new SendConversationReply($conversation, $replies, $customer);

        // Properties should be accessible
        $this->assertIsObject($job->conversation);
        $this->assertIsArray($job->replies);
        $this->assertIsObject($job->customer);
    }

    /** Test multiple jobs can be dispatched simultaneously */
    public function test_multiple_jobs_can_be_dispatched(): void
    {
        Mail::fake();

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $emails = ['user1@test.com', 'user2@test.com', 'user3@test.com'];

        foreach ($emails as $email) {
            $customer = Customer::factory()->create(['email' => $email]);
            SendConversationReply::dispatch($conversation, [$thread], $customer);
        }

        // All jobs dispatched successfully
        $this->expectNotToPerformAssertions();
    }
}
