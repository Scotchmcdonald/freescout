<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\CustomerCreatedConversation;
use App\Jobs\SendAutoReply as SendAutoReplyJob;
use App\Listeners\SendAutoReply;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\SendLog;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendAutoReplyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function listener_dispatches_job_for_valid_conversation(): void
    {
        Queue::fake();
        Log::spy();

        $mailbox = Mailbox::factory()->create(['auto_reply_enabled' => true]);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'customer_email' => 'customer@example.com',
            'imported' => false,
            'status' => 1, // Active
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'type' => Thread::TYPE_CUSTOMER,
        ]);

        $event = new CustomerCreatedConversation($conversation, $thread, $customer);
        $listener = new SendAutoReply();
        $listener->handle($event);

        Queue::assertPushed(SendAutoReplyJob::class);
        Log::shouldHaveReceived('info')
            ->with('SendAutoReply listener triggered', \Mockery::type('array'))
            ->once();
    }

    #[Test]
    public function listener_skips_imported_conversations(): void
    {
        Queue::fake();
        Log::spy();

        $mailbox = Mailbox::factory()->create(['auto_reply_enabled' => true]);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'imported' => true,
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $event = new CustomerCreatedConversation($conversation, $thread, $customer);
        $listener = new SendAutoReply();
        $listener->handle($event);

        Queue::assertNotPushed(SendAutoReplyJob::class);
        Log::shouldHaveReceived('debug')
            ->with('Skipping auto-reply for imported conversation', \Mockery::type('array'))
            ->once();
    }

    #[Test]
    public function listener_skips_when_auto_reply_disabled(): void
    {
        Queue::fake();
        Log::spy();

        $mailbox = Mailbox::factory()->create(['auto_reply_enabled' => false]);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'imported' => false,
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $event = new CustomerCreatedConversation($conversation, $thread, $customer);
        $listener = new SendAutoReply();
        $listener->handle($event);

        Queue::assertNotPushed(SendAutoReplyJob::class);
        Log::shouldHaveReceived('debug')
            ->with('Auto-reply disabled for mailbox', \Mockery::type('array'))
            ->once();
    }

    #[Test]
    public function listener_skips_spam_conversations(): void
    {
        Queue::fake();
        Log::spy();

        $mailbox = Mailbox::factory()->create(['auto_reply_enabled' => true]);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'status' => 3, // STATUS_SPAM
            'imported' => false,
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $event = new CustomerCreatedConversation($conversation, $thread, $customer);
        $listener = new SendAutoReply();
        $listener->handle($event);

        Queue::assertNotPushed(SendAutoReplyJob::class);
        Log::shouldHaveReceived('debug')
            ->with('Skipping auto-reply for spam conversation', \Mockery::type('array'))
            ->once();
    }

    #[Test]
    public function listener_enforces_rate_limit(): void
    {
        Queue::fake();
        Log::spy();

        $mailbox = Mailbox::factory()->create(['auto_reply_enabled' => true]);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'imported' => false,
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        // Create 10 recent auto-reply send logs
        for ($i = 0; $i < 10; $i++) {
            SendLog::factory()->create([
                'customer_id' => $customer->id,
                'mail_type' => 3, // SendLog::MAIL_TYPE_AUTO_REPLY
                'created_at' => now()->subMinutes(30),
            ]);
        }

        $event = new CustomerCreatedConversation($conversation, $thread, $customer);
        $listener = new SendAutoReply();
        $listener->handle($event);

        Queue::assertNotPushed(SendAutoReplyJob::class);
        Log::shouldHaveReceived('warning')
            ->with('Auto-reply rate limit exceeded (10)', \Mockery::type('array'))
            ->once();
    }

    #[Test]
    public function listener_skips_duplicate_subjects(): void
    {
        Queue::fake();
        Log::spy();

        $mailbox = Mailbox::factory()->create(['auto_reply_enabled' => true]);
        $customer = Customer::factory()->create();
        
        // Create previous conversation with same subject
        $prevConversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'subject' => 'Duplicate Subject',
            'created_at' => now()->subMinutes(30),
        ]);

        // Create 2 recent auto-reply logs to trigger duplicate check
        SendLog::factory()->create([
            'customer_id' => $customer->id,
            'mail_type' => 3,
            'created_at' => now()->subMinutes(30),
        ]);
        SendLog::factory()->create([
            'customer_id' => $customer->id,
            'mail_type' => 3,
            'created_at' => now()->subMinutes(25),
        ]);

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'subject' => 'Duplicate Subject',
            'imported' => false,
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $event = new CustomerCreatedConversation($conversation, $thread, $customer);
        $listener = new SendAutoReply();
        $listener->handle($event);

        Queue::assertNotPushed(SendAutoReplyJob::class);
        Log::shouldHaveReceived('debug')
            ->with('Skipping auto-reply - duplicate subject detected', \Mockery::type('array'))
            ->once();
    }

    #[Test]
    public function listener_skips_internal_mailbox_emails(): void
    {
        Queue::fake();
        Log::spy();

        $internalMailbox = Mailbox::factory()->create(['email' => 'internal@example.com']);
        $customerMailbox = Mailbox::factory()->create([
            'email' => 'customer@example.com',
            'auto_reply_enabled' => true,
        ]);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $customerMailbox->id,
            'customer_id' => $customer->id,
            'customer_email' => 'internal@example.com', // Same as internal mailbox
            'imported' => false,
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $event = new CustomerCreatedConversation($conversation, $thread, $customer);
        $listener = new SendAutoReply();
        $listener->handle($event);

        Queue::assertNotPushed(SendAutoReplyJob::class);
        Log::shouldHaveReceived('debug')
            ->with('Skipping auto-reply to internal mailbox', \Mockery::type('array'))
            ->once();
    }

    #[Test]
    public function listener_logs_job_dispatch(): void
    {
        Queue::fake();
        Log::spy();

        $mailbox = Mailbox::factory()->create(['auto_reply_enabled' => true]);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'customer_email' => 'customer@example.com',
            'imported' => false,
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $event = new CustomerCreatedConversation($conversation, $thread, $customer);
        $listener = new SendAutoReply();
        $listener->handle($event);

        Log::shouldHaveReceived('info')
            ->with('SendAutoReply job dispatched', \Mockery::type('array'))
            ->once();
    }

    #[Test]
    public function listener_dispatches_job_to_emails_queue(): void
    {
        Queue::fake();

        $mailbox = Mailbox::factory()->create(['auto_reply_enabled' => true]);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'imported' => false,
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $event = new CustomerCreatedConversation($conversation, $thread, $customer);
        $listener = new SendAutoReply();
        $listener->handle($event);

        Queue::assertPushed(SendAutoReplyJob::class, function ($job) {
            return $job->queue === 'emails';
        });
    }

    #[Test]
    public function check_period_constant_is_180_minutes(): void
    {
        $this->assertEquals(180, SendAutoReply::CHECK_PERIOD);
    }
}
