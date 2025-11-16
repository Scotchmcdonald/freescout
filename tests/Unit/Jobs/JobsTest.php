<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SendAutoReply;
use App\Jobs\SendConversationReply;
use App\Mail\AutoReplyNotification;
use App\Mail\ConversationReplyNotification;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Test Job classes
 * 
 * Focus: Job construction, email sending
 */
class JobsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function send_conversation_reply_job_can_be_constructed(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $job = new SendConversationReply($conversation, $thread, 'test@example.com');

        $this->assertInstanceOf(SendConversationReply::class, $job);
        $this->assertEquals($conversation->id, $job->conversation->id);
        $this->assertEquals($thread->id, $job->thread->id);
        $this->assertEquals('test@example.com', $job->recipientEmail);
    }

    /** @test */
    public function send_conversation_reply_job_sends_email(): void
    {
        Mail::fake();

        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $job = new SendConversationReply($conversation, $thread, 'customer@example.com');
        $job->handle();

        Mail::assertSent(ConversationReplyNotification::class, function ($mail) {
            return $mail->hasTo('customer@example.com');
        });
    }

    /** @test */
    public function send_conversation_reply_job_uses_correct_mailable(): void
    {
        Mail::fake();

        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $job = new SendConversationReply($conversation, $thread, 'test@example.com');
        $job->handle();

        Mail::assertSent(ConversationReplyNotification::class);
    }

    /** @test */
    public function send_auto_reply_job_can_be_constructed(): void
    {
        $conversation = Conversation::factory()->create();
        $customer = Customer::factory()->create();

        $job = new SendAutoReply($conversation, $customer);

        $this->assertInstanceOf(SendAutoReply::class, $job);
        $this->assertEquals($conversation->id, $job->conversation->id);
        $this->assertEquals($customer->id, $job->customer->id);
    }

    /** @test */
    public function send_auto_reply_job_sends_email(): void
    {
        Mail::fake();

        $conversation = Conversation::factory()->create();
        $customer = Customer::factory()->create();
        
        // Create email for customer
        \App\Models\Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'customer@example.com',
            'type' => 1,
        ]);

        $job = new SendAutoReply($conversation, $customer);
        $job->handle();

        Mail::assertSent(AutoReplyNotification::class);
    }

    /** @test */
    public function send_conversation_reply_job_handles_unicode_email(): void
    {
        Mail::fake();

        $conversation = Conversation::factory()->withUnicodeSubject()->create();
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'body' => '日本語のメッセージ',
        ]);

        $job = new SendConversationReply($conversation, $thread, 'test@example.com');
        $job->handle();

        Mail::assertSent(ConversationReplyNotification::class);
    }

    /** @test */
    public function send_conversation_reply_job_handles_long_email_body(): void
    {
        Mail::fake();

        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->withLargeBody()->create([
            'conversation_id' => $conversation->id,
        ]);

        $job = new SendConversationReply($conversation, $thread, 'test@example.com');
        $job->handle();

        Mail::assertSent(ConversationReplyNotification::class);
    }

    /** @test */
    public function send_conversation_reply_job_is_queueable(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $job = new SendConversationReply($conversation, $thread, 'test@example.com');

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }

    /** @test */
    public function send_auto_reply_job_is_queueable(): void
    {
        $conversation = Conversation::factory()->create();
        $customer = Customer::factory()->create();

        $job = new SendAutoReply($conversation, $customer);

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }
}
