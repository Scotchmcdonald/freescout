<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\CustomerCreatedConversation;
use App\Jobs\SendAutoReplyJob;
use App\Listeners\SendAutoReply;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Email;
use App\Models\Mailbox;
use App\Models\SendLog;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendAutoReplyListenerTest extends TestCase
{
    use RefreshDatabase;

    protected Mailbox $mailbox;
    protected Customer $customer;
    protected Conversation $conversation;
    protected Thread $thread;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mailbox = Mailbox::factory()->create([
            'auto_reply_enabled' => true,
            'auto_reply_subject' => 'Thanks for contacting us',
            'auto_reply_message' => 'We have received your message.',
        ]);

        $this->customer = Customer::factory()->withoutEmail()->create();
        Email::factory()->create([
            'customer_id' => $this->customer->id,
            'email' => 'customer@example.com',
        ]);

        $this->conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create([
                'customer_id' => $this->customer->id,
                'customer_email' => 'customer@example.com',
                'imported' => false,
            ]);

        $this->thread = Thread::factory()->create([
            'conversation_id' => $this->conversation->id,
            'type' => Thread::TYPE_CUSTOMER,
        ]);
    }

    public function test_sends_auto_reply_when_enabled(): void
    {
        Queue::fake();

        $listener = new SendAutoReply();
        $event = new CustomerCreatedConversation($this->conversation, $this->thread, $this->customer);

        $listener->handle($event);

        Queue::assertPushed(SendAutoReplyJob::class, function ($job) {
            return $job->conversation->id === $this->conversation->id;
        });
    }

    public function test_skips_auto_reply_when_disabled(): void
    {
        Queue::fake();

        $this->mailbox->update(['auto_reply_enabled' => false]);
        $this->conversation->refresh();

        $listener = new SendAutoReply();
        $event = new CustomerCreatedConversation($this->conversation, $this->thread, $this->customer);

        $listener->handle($event);

        Queue::assertNotPushed(SendAutoReplyJob::class);
    }

    public function test_skips_auto_reply_for_imported_conversation(): void
    {
        Queue::fake();

        $this->conversation->update(['imported' => true]);

        $listener = new SendAutoReply();
        $event = new CustomerCreatedConversation($this->conversation, $this->thread, $this->customer);

        $listener->handle($event);

        Queue::assertNotPushed(SendAutoReplyJob::class);
    }

    public function test_skips_auto_reply_for_spam(): void
    {
        Queue::fake();

        $this->conversation->update(['status' => 3]); // STATUS_SPAM

        $listener = new SendAutoReply();
        $event = new CustomerCreatedConversation($this->conversation, $this->thread, $this->customer);

        $listener->handle($event);

        Queue::assertNotPushed(SendAutoReplyJob::class);
    }

    public function test_skips_auto_reply_when_rate_limit_exceeded(): void
    {
        Queue::fake();

        // Create 10 recent auto-reply send logs
        for ($i = 0; $i < 10; $i++) {
            SendLog::create([
                'customer_id' => $this->customer->id,
                'email' => 'customer@example.com',
                'mail_type' => 3, // MAIL_TYPE_AUTO_REPLY
                'status' => SendLog::STATUS_SENT,
                'created_at' => now()->subMinutes(30),
            ]);
        }

        $listener = new SendAutoReply();
        $event = new CustomerCreatedConversation($this->conversation, $this->thread, $this->customer);

        $listener->handle($event);

        Queue::assertNotPushed(SendAutoReplyJob::class);
    }

    public function test_skips_auto_reply_to_internal_mailbox(): void
    {
        Queue::fake();

        // Create another mailbox with same email as customer
        Mailbox::factory()->create(['email' => 'customer@example.com']);
        $this->conversation->update(['customer_email' => 'customer@example.com']);

        $listener = new SendAutoReply();
        $event = new CustomerCreatedConversation($this->conversation, $this->thread, $this->customer);

        $listener->handle($event);

        Queue::assertNotPushed(SendAutoReplyJob::class);
    }

    public function test_returns_early_when_mailbox_is_null(): void
    {
        Queue::fake();

        // Mock conversation to simulate missing mailbox
        $conversation = \Mockery::mock(Conversation::class)->makePartial();
        $conversation->shouldReceive('getAttribute')->with('mailbox')->andReturn(null);
        $conversation->id = 1;

        $thread = Thread::factory()->make();
        
        $listener = new SendAutoReply();
        $event = new CustomerCreatedConversation($conversation, $thread, $this->customer);

        $listener->handle($event);

        Queue::assertNotPushed(SendAutoReplyJob::class);
    }

    public function test_skips_duplicate_subject_auto_reply(): void
    {
        Queue::fake();

        // Create previous auto-reply logs
        for ($i = 0; $i < 3; $i++) {
            SendLog::create([
                'customer_id' => $this->customer->id,
                'email' => 'customer@example.com',
                'mail_type' => 3, // MAIL_TYPE_AUTO_REPLY
                'status' => SendLog::STATUS_SENT,
                'created_at' => now()->subMinutes(30),
            ]);
        }

        // Create another conversation with same subject
        Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'customer_id' => $this->customer->id,
            'customer_email' => 'customer@example.com',
            'subject' => $this->conversation->subject,
            'created_at' => now()->subMinutes(30),
        ]);

        $listener = new SendAutoReply();
        $event = new CustomerCreatedConversation($this->conversation, $this->thread, $this->customer);

        $listener->handle($event);

        Queue::assertNotPushed(SendAutoReplyJob::class);
    }
}
