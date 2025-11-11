<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Mail\AutoReply;
use App\Mail\ConversationReplyNotification;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use Tests\TestCase;

class MailTest extends TestCase
{
    public function test_auto_reply_has_properties(): void
    {
        $conversation = new Conversation(['id' => 1]);
        $mailbox = new Mailbox(['id' => 1, 'name' => 'Support']);
        $customer = new Customer(['id' => 1, 'first_name' => 'John']);

        $mail = new AutoReply($conversation, $mailbox, $customer);

        $this->assertSame($conversation, $mail->conversation);
        $this->assertSame($mailbox, $mail->mailbox);
        $this->assertSame($customer, $mail->customer);
    }

    public function test_auto_reply_envelope_has_subject(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test Subject']);
        $mailbox = new Mailbox(['id' => 1, 'name' => 'Support', 'auto_reply_subject' => null]);
        $customer = new Customer(['id' => 1]);

        $mail = new AutoReply($conversation, $mailbox, $customer);
        $envelope = $mail->envelope();

        $this->assertEquals('Re: Test Subject', $envelope->subject);
    }

    public function test_conversation_reply_notification_has_properties(): void
    {
        $conversation = new Conversation(['id' => 1]);
        $thread = new Thread(['id' => 1, 'body' => 'Reply message']);

        $mail = new ConversationReplyNotification($conversation, $thread);

        $this->assertSame($thread, $mail->thread);
        $this->assertSame($conversation, $mail->conversation);
    }
}
