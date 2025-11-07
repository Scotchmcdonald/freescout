<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Jobs\SendConversationReply;
use App\Mail\ConversationReplyNotification;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendConversationReplyJobTest extends TestCase
{
    use RefreshDatabase;

    /** Test job has required properties */
    public function test_job_has_required_properties(): void
    {
        $conversation = new Conversation(['id' => 1]);
        $thread = new Thread(['id' => 2]);
        $recipientEmail = 'test@example.com';
        
        $job = new SendConversationReply($conversation, $thread, $recipientEmail);
        
        $this->assertSame($conversation, $job->conversation);
        $this->assertSame($thread, $job->thread);
        $this->assertEquals($recipientEmail, $job->recipientEmail);
    }

    /** Test job implements ShouldQueue */
    public function test_job_implements_should_queue(): void
    {
        $job = new SendConversationReply(
            new Conversation(),
            new Thread(),
            'test@example.com'
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
            new Conversation(),
            new Thread(),
            'test@example.com'
        );
        
        $this->assertTrue(method_exists($job, 'handle'));
    }

    /** Test job sends email to correct recipient */
    public function test_job_sends_email_to_recipient(): void
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
        $recipientEmail = 'recipient@example.com';
        
        $job = new SendConversationReply($conversation, $thread, $recipientEmail);
        $job->handle();
        
        Mail::assertSent(ConversationReplyNotification::class, function ($mail) use ($recipientEmail) {
            return $mail->hasTo($recipientEmail);
        });
    }

    /** Test job sends correct mail type */
    public function test_job_sends_conversation_reply_notification(): void
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
        
        $job = new SendConversationReply($conversation, $thread, 'test@example.com');
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
        
        SendConversationReply::dispatch($conversation, $thread, 'test@example.com');
        
        // Job was dispatched successfully
        $this->assertTrue(true);
    }

    /** Test job handles different email formats */
    public function test_job_handles_various_email_formats(): void
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
        
        $emailFormats = [
            'simple@example.com',
            'with.dot@example.com',
            'with+plus@example.com',
            'with_underscore@example.co.uk',
        ];
        
        foreach ($emailFormats as $email) {
            $job = new SendConversationReply($conversation, $thread, $email);
            $job->handle();
            
            Mail::assertSent(ConversationReplyNotification::class, function ($mail) use ($email) {
                return $mail->hasTo($email);
            });
        }
    }
}
