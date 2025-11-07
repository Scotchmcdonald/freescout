<?php

declare(strict_types=1);

namespace Tests\Unit;

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
use Tests\TestCase;

class SendAutoReplyListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_handle_method_exists(): void
    {
        $conversation = new Conversation(['id' => 1, 'imported' => true]);
        $thread = new Thread(['id' => 2]);
        $customer = new Customer(['id' => 3]);
        
        $event = new CustomerCreatedConversation($conversation, $thread, $customer);
        $listener = new SendAutoReply();
        
        $this->assertTrue(method_exists($listener, 'handle'));
    }

    public function test_listener_has_correct_check_period_constant(): void
    {
        $this->assertEquals(180, SendAutoReply::CHECK_PERIOD);
    }

    /** Test listener skips auto-reply for imported conversations */
    public function test_listener_skips_imported_conversations(): void
    {
        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('debug')->atLeast()->once();
        
        Queue::fake();
        
        $mailbox = Mailbox::factory()->create(['auto_reply_enabled' => true]);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'imported' => true,
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
        
        $event = new CustomerCreatedConversation($conversation, $thread, $customer);
        $listener = new SendAutoReply();
        $listener->handle($event);
        
        Queue::assertNotPushed(SendAutoReplyJob::class);
    }

    /** Test listener skips when mailbox auto-reply is disabled */
    public function test_listener_skips_when_auto_reply_disabled(): void
    {
        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('debug')->atLeast()->once();
        
        Queue::fake();
        
        $mailbox = Mailbox::factory()->create(['auto_reply_enabled' => false]);
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
        
        Queue::assertNotPushed(SendAutoReplyJob::class);
    }

    /** Test listener skips spam conversations */
    public function test_listener_skips_spam_conversations(): void
    {
        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('debug')->atLeast()->once();
        
        Queue::fake();
        
        $mailbox = Mailbox::factory()->create(['auto_reply_enabled' => true]);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'imported' => false,
            'status' => 3, // STATUS_SPAM
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
        
        $event = new CustomerCreatedConversation($conversation, $thread, $customer);
        $listener = new SendAutoReply();
        $listener->handle($event);
        
        Queue::assertNotPushed(SendAutoReplyJob::class);
    }

    /** Test listener dispatches job for valid conversation */
    public function test_listener_dispatches_job_for_valid_conversation(): void
    {
        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        
        Queue::fake();
        
        $mailbox = Mailbox::factory()->create(['auto_reply_enabled' => true]);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'imported' => false,
            'status' => 1, // ACTIVE
            'customer_email' => $customer->email,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
        ]);
        
        $event = new CustomerCreatedConversation($conversation, $thread, $customer);
        $listener = new SendAutoReply();
        $listener->handle($event);
        
        Queue::assertPushed(SendAutoReplyJob::class, function ($job) use ($conversation) {
            return $job->conversation->id === $conversation->id;
        });
    }

    /** Test listener rate limits auto-replies */
    public function test_listener_rate_limits_auto_replies(): void
    {
        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('warning')->atLeast()->once();
        
        Queue::fake();
        
        $mailbox = Mailbox::factory()->create(['auto_reply_enabled' => true]);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'imported' => false,
            'customer_email' => 'customer@test.com',
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
        ]);
        
        // Create 10 existing send logs to trigger rate limit
        for ($i = 0; $i < 10; $i++) {
            SendLog::create([
                'thread_id' => $thread->id,
                'message_id' => 'test-' . $i . '@test.com',
                'email' => 'customer@test.com',
                'mail_type' => SendLog::MAIL_TYPE_AUTO_REPLY,
                'status' => SendLog::STATUS_ACCEPTED,
                'customer_id' => $customer->id,
                'created_at' => now()->subMinutes(30),
                'updated_at' => now()->subMinutes(30),
            ]);
        }
        
        $event = new CustomerCreatedConversation($conversation, $thread, $customer);
        $listener = new SendAutoReply();
        $listener->handle($event);
        
        Queue::assertNotPushed(SendAutoReplyJob::class);
    }

    /** Test listener skips auto-replies to internal mailboxes */
    public function test_listener_skips_internal_mailbox_emails(): void
    {
        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('debug')->atLeast()->once();
        
        Queue::fake();
        
        $internalMailbox = Mailbox::factory()->create([
            'email' => 'internal@test.com',
            'auto_reply_enabled' => true,
        ]);
        $mailbox = Mailbox::factory()->create(['auto_reply_enabled' => true]);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'customer_email' => 'internal@test.com',
            'imported' => false,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
        ]);
        
        $event = new CustomerCreatedConversation($conversation, $thread, $customer);
        $listener = new SendAutoReply();
        $listener->handle($event);
        
        Queue::assertNotPushed(SendAutoReplyJob::class);
    }
}