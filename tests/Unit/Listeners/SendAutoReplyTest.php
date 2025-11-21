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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\UnitTestCase;

class SendAutoReplyTest extends UnitTestCase
{

    public function test_listener_dispatches_job_for_valid_conversation(): void
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

    public function test_listener_skips_imported_conversations(): void
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

    public function test_listener_skips_when_auto_reply_disabled(): void
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

    public function test_listener_skips_spam_conversations(): void
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

    public function test_listener_enforces_rate_limit(): void
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

    public function test_listener_skips_duplicate_subjects(): void
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

    public function test_listener_skips_internal_mailbox_emails(): void
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

    public function test_listener_logs_job_dispatch(): void
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

    public function test_listener_dispatches_job_to_emails_queue(): void
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

    public function test_check_period_constant_is_180_minutes(): void
    {
        $this->assertEquals(180, SendAutoReply::CHECK_PERIOD);
    }

    // ===== BASIC TESTS (Merged from SendAutoReplyListenerTest.php) =====

    public function test_send_auto_reply_listener_handle_method_exists(): void
    {
        $conversation = new Conversation(['id' => 1, 'imported' => true]);
        $thread = new Thread(['id' => 2]);
        $customer = new Customer(['id' => 3]);

        $event = new CustomerCreatedConversation($conversation, $thread, $customer);
        $listener = new SendAutoReply;

        $this->assertTrue(method_exists($listener, 'handle'));
    }

    public function test_send_auto_reply_listener_has_correct_check_period_constant(): void
    {
        $this->assertEquals(180, SendAutoReply::CHECK_PERIOD);
    }

    public function test_listener_skips_when_rate_limit_exceeded_10_replies(): void
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

        // Create 10 auto-reply send logs within the check period
        for ($i = 0; $i < 10; $i++) {
            SendLog::create([
                'customer_id' => $customer->id,
                'mail_type' => 3, // MAIL_TYPE_AUTO_REPLY
                'email' => 'test@example.com',
                'status' => 1,
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

    public function test_listener_skips_duplicate_subject_with_2_plus_replies(): void
    {
        Queue::fake();
        Log::spy();

        $mailbox = Mailbox::factory()->create(['auto_reply_enabled' => true]);
        $customer = Customer::factory()->create();
        
        // Create previous conversation with same subject
        $prevConversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'subject' => 'Test Subject',
            'created_at' => now()->subMinutes(30),
        ]);
        
        // Create 2 auto-reply logs to trigger duplicate check
        for ($i = 0; $i < 2; $i++) {
            SendLog::create([
                'customer_id' => $customer->id,
                'mail_type' => 3,
                'email' => 'test@example.com',
                'status' => 1,
                'created_at' => now()->subMinutes(60),
            ]);
        }

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'subject' => 'Test Subject', // Same subject
            'imported' => false,
            'created_at' => now(),
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

    public function test_listener_allows_different_subject_with_2_plus_replies(): void
    {
        Queue::fake();

        $mailbox = Mailbox::factory()->create(['auto_reply_enabled' => true]);
        $customer = Customer::factory()->create();
        
        // Create previous conversation with different subject
        $prevConversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'subject' => 'Different Subject',
            'created_at' => now()->subMinutes(30),
        ]);
        
        // Create 2 auto-reply logs
        for ($i = 0; $i < 2; $i++) {
            SendLog::create([
                'customer_id' => $customer->id,
                'mail_type' => 3,
                'email' => 'test@example.com',
                'status' => 1,
                'created_at' => now()->subMinutes(60),
            ]);
        }

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'subject' => 'Test Subject', // Different subject
            'imported' => false,
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $event = new CustomerCreatedConversation($conversation, $thread, $customer);
        $listener = new SendAutoReply();
        $listener->handle($event);

        // Should dispatch since subject is different
        Queue::assertPushed(SendAutoReplyJob::class);
    }

    public function test_listener_handles_null_customer_email(): void
    {
        Queue::fake();

        $mailbox = Mailbox::factory()->create(['auto_reply_enabled' => true]);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'customer_email' => null, // No email
            'imported' => false,
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $event = new CustomerCreatedConversation($conversation, $thread, $customer);
        $listener = new SendAutoReply();
        $listener->handle($event);

        // Should still dispatch - internal mailbox check is skipped
        Queue::assertPushed(SendAutoReplyJob::class);
    }

    public function test_listener_allows_non_internal_customer_email(): void
    {
        Queue::fake();

        $internalMailbox = Mailbox::factory()->create(['email' => 'internal@example.com']);
        $customerMailbox = Mailbox::factory()->create([
            'email' => 'support@example.com',
            'auto_reply_enabled' => true,
        ]);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $customerMailbox->id,
            'customer_id' => $customer->id,
            'customer_email' => 'external@customer.com', // Not internal
            'imported' => false,
        ]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $event = new CustomerCreatedConversation($conversation, $thread, $customer);
        $listener = new SendAutoReply();
        $listener->handle($event);

        // Should dispatch since email is not internal
        Queue::assertPushed(SendAutoReplyJob::class);
    }
}
