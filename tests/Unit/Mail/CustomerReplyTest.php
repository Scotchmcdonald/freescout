<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\CustomerReply;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CustomerReplyTest extends TestCase
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
            'email' => 'support@example.com',
            'name' => 'Support',
        ]);

        $this->customer = Customer::factory()->create([
            'email' => 'customer@example.com',
        ]);

        $this->conversation = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'customer_id' => $this->customer->id,
            'subject' => 'Test Subject',
        ]);

        $this->thread = Thread::factory()->create([
            'conversation_id' => $this->conversation->id,
            'body' => '<p>This is the reply body</p>',
        ]);
    }

    public function test_customer_reply_can_be_instantiated(): void
    {
        $mailable = new CustomerReply($this->conversation, $this->thread);

        $this->assertInstanceOf(CustomerReply::class, $mailable);
    }

    public function test_customer_reply_has_correct_subject(): void
    {
        $mailable = new CustomerReply($this->conversation, $this->thread);
        $envelope = $mailable->envelope();

        $this->assertEquals('Re: Test Subject', $envelope->subject);
    }

    public function test_customer_reply_has_correct_from_address(): void
    {
        $mailable = new CustomerReply($this->conversation, $this->thread);
        $envelope = $mailable->envelope();

        $this->assertEquals($this->mailbox->email, $envelope->from->address);
    }

    public function test_customer_reply_has_reply_to_address(): void
    {
        $mailable = new CustomerReply($this->conversation, $this->thread);
        $envelope = $mailable->envelope();

        $this->assertNotEmpty($envelope->replyTo);
        $this->assertEquals($this->mailbox->email, $envelope->replyTo[0]->address ?? $envelope->replyTo[0]);
    }

    public function test_customer_reply_content_has_body(): void
    {
        $mailable = new CustomerReply($this->conversation, $this->thread);
        $content = $mailable->content();

        $this->assertEquals('emails.customer.reply', $content->view);
        $this->assertArrayHasKey('body', $content->with);
    }

    public function test_customer_reply_content_has_tracking_url(): void
    {
        $mailable = new CustomerReply($this->conversation, $this->thread);
        $content = $mailable->content();

        $this->assertArrayHasKey('trackingUrl', $content->with);
        $this->assertNotEmpty($content->with['trackingUrl']);
    }

    public function test_customer_reply_attachments_returns_array(): void
    {
        $mailable = new CustomerReply($this->conversation, $this->thread);
        $attachments = $mailable->attachments();

        $this->assertIsArray($attachments);
    }

    public function test_customer_reply_attachments_empty_when_no_files(): void
    {
        $mailable = new CustomerReply($this->conversation, $this->thread);
        $attachments = $mailable->attachments();

        $this->assertEmpty($attachments);
    }

    public function test_customer_reply_processes_body_with_attachment_links(): void
    {
        $this->thread->update([
            'body' => '<p>Check out this <img src="/attachments/123/download"></p>',
        ]);

        $mailable = new CustomerReply($this->conversation, $this->thread);
        $content = $mailable->content();

        // Body should be processed
        $this->assertNotNull($content->with['body']);
    }

    public function test_customer_reply_preserves_body_without_attachment_links(): void
    {
        $originalBody = '<p>Simple text without attachments</p>';
        $this->thread->update(['body' => $originalBody]);

        $mailable = new CustomerReply($this->conversation, $this->thread);
        $content = $mailable->content();

        $this->assertEquals($originalBody, $content->with['body']);
    }

    public function test_customer_reply_handles_null_body(): void
    {
        $this->thread->update(['body' => null]);

        $mailable = new CustomerReply($this->conversation, $this->thread);
        $content = $mailable->content();

        $this->assertNull($content->with['body']);
    }

    public function test_customer_reply_uses_conversation_and_thread(): void
    {
        $mailable = new CustomerReply($this->conversation, $this->thread);

        $this->assertEquals($this->conversation->id, $mailable->conversation->id);
        $this->assertEquals($this->thread->id, $mailable->thread->id);
    }

    public function test_customer_reply_generates_signed_tracking_url(): void
    {
        $mailable = new CustomerReply($this->conversation, $this->thread);
        $content = $mailable->content();

        $trackingUrl = $content->with['trackingUrl'];

        // Should be a signed URL
        $this->assertStringContainsString('signature=', $trackingUrl);
    }
}
