<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\AutoReply;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use Tests\TestCase;

class AutoReplyEnhancedTest extends TestCase
{
    public function test_auto_reply_content_returns_text_view(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $mailbox = new Mailbox(['id' => 1]);
        $customer = new Customer(['id' => 1]);

        $mail = new AutoReply($conversation, $mailbox, $customer);
        $content = $mail->content();

        $this->assertEquals('emails.auto-reply', $content->text);
        $this->assertArrayHasKey('message', $content->with);
        $this->assertArrayHasKey('conversation', $content->with);
        $this->assertArrayHasKey('mailbox', $content->with);
        $this->assertArrayHasKey('customer', $content->with);
    }

    public function test_auto_reply_content_uses_default_message_when_none_set(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $mailbox = new Mailbox(['id' => 1, 'auto_reply_message' => null]);
        $customer = new Customer(['id' => 1]);

        $mail = new AutoReply($conversation, $mailbox, $customer);
        $content = $mail->content();

        $this->assertEquals('We have received your message and will get back to you shortly.', $content->with['message']);
    }

    public function test_auto_reply_content_uses_custom_message_when_set(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $mailbox = new Mailbox([
            'id' => 1,
            'auto_reply_message' => 'Custom auto-reply message',
        ]);
        $customer = new Customer(['id' => 1]);

        $mail = new AutoReply($conversation, $mailbox, $customer);
        $content = $mail->content();

        $this->assertEquals('Custom auto-reply message', $content->with['message']);
    }

    public function test_auto_reply_envelope_uses_default_subject_when_none_set(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Original Subject']);
        $mailbox = new Mailbox(['id' => 1, 'auto_reply_subject' => null]);
        $customer = new Customer(['id' => 1]);

        $mail = new AutoReply($conversation, $mailbox, $customer);
        $envelope = $mail->envelope();

        $this->assertEquals('Re: Original Subject', $envelope->subject);
    }

    public function test_auto_reply_envelope_uses_custom_subject_when_set(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Original Subject']);
        $mailbox = new Mailbox([
            'id' => 1,
            'auto_reply_subject' => 'Custom Auto Reply Subject',
        ]);
        $customer = new Customer(['id' => 1]);

        $mail = new AutoReply($conversation, $mailbox, $customer);
        $envelope = $mail->envelope();

        $this->assertEquals('Custom Auto Reply Subject', $envelope->subject);
    }

    public function test_auto_reply_build_returns_mailable(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $mailbox = new Mailbox(['id' => 1]);
        $customer = new Customer(['id' => 1]);

        $mail = new AutoReply($conversation, $mailbox, $customer);
        $result = $mail->build();

        $this->assertInstanceOf(AutoReply::class, $result);
    }

    public function test_auto_reply_can_be_created_with_headers(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $mailbox = new Mailbox(['id' => 1]);
        $customer = new Customer(['id' => 1]);
        $headers = ['X-Custom-Header' => 'Custom Value'];

        $mail = new AutoReply($conversation, $mailbox, $customer, $headers);

        $this->assertEquals($headers, $mail->headers);
    }

    public function test_auto_reply_with_empty_headers_array(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $mailbox = new Mailbox(['id' => 1]);
        $customer = new Customer(['id' => 1]);

        $mail = new AutoReply($conversation, $mailbox, $customer, []);

        $this->assertEquals([], $mail->headers);
        $this->assertInstanceOf(AutoReply::class, $mail->build());
    }
}
