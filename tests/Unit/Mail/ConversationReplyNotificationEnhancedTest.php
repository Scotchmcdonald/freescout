<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\ConversationReplyNotification;
use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\Thread;
use Tests\TestCase;

class ConversationReplyNotificationEnhancedTest extends TestCase
{
    public function test_conversation_reply_notification_envelope_has_subject(): void
    {
        $mailbox = new Mailbox(['id' => 1, 'email' => 'support@example.com']);
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test Conversation']);
        $conversation->setRelation('mailbox', $mailbox);
        $thread = new Thread(['id' => 1, 'body' => 'Reply message']);

        $mail = new ConversationReplyNotification($conversation, $thread);
        $envelope = $mail->envelope();

        $this->assertEquals('Re: Test Conversation', $envelope->subject);
    }

    public function test_conversation_reply_notification_envelope_has_from_address(): void
    {
        $mailbox = new Mailbox(['id' => 1, 'email' => 'support@example.com']);
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $conversation->setRelation('mailbox', $mailbox);
        $thread = new Thread(['id' => 1]);

        $mail = new ConversationReplyNotification($conversation, $thread);
        $envelope = $mail->envelope();

        // from is an Address object, not an array
        $this->assertIsObject($envelope->from);
        $this->assertEquals('support@example.com', $envelope->from->address);
    }

    public function test_conversation_reply_notification_envelope_has_reply_to_address(): void
    {
        $mailbox = new Mailbox(['id' => 1, 'email' => 'support@example.com']);
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $conversation->setRelation('mailbox', $mailbox);
        $thread = new Thread(['id' => 1]);

        $mail = new ConversationReplyNotification($conversation, $thread);
        $envelope = $mail->envelope();

        $this->assertCount(1, $envelope->replyTo);
        $this->assertEquals('support@example.com', $envelope->replyTo[0]->address);
    }

    public function test_conversation_reply_notification_content_returns_markdown_view(): void
    {
        // Create a conversation with proper attributes to avoid route generation issues
        $mailbox = new Mailbox;
        $mailbox->id = 1;
        $mailbox->email = 'support@example.com';

        $conversation = new Conversation;
        $conversation->id = 123;
        $conversation->subject = 'Test';
        $conversation->setRelation('mailbox', $mailbox);

        $thread = new Thread(['body' => 'Reply body']);
        $thread->id = 1;

        $mail = new ConversationReplyNotification($conversation, $thread);
        $content = $mail->content();

        $this->assertEquals('emails.conversation.reply', $content->markdown);
        $this->assertArrayHasKey('conversation', $content->with);
        $this->assertArrayHasKey('thread', $content->with);
        $this->assertArrayHasKey('url', $content->with);
    }

    public function test_conversation_reply_notification_content_includes_conversation(): void
    {
        $mailbox = new Mailbox;
        $mailbox->id = 1;
        $mailbox->email = 'support@example.com';

        $conversation = new Conversation;
        $conversation->id = 1;
        $conversation->subject = 'Test';
        $conversation->setRelation('mailbox', $mailbox);

        $thread = new Thread;
        $thread->id = 1;

        $mail = new ConversationReplyNotification($conversation, $thread);
        $content = $mail->content();

        $this->assertSame($conversation, $content->with['conversation']);
    }

    public function test_conversation_reply_notification_content_includes_thread(): void
    {
        $mailbox = new Mailbox;
        $mailbox->id = 1;
        $mailbox->email = 'support@example.com';

        $conversation = new Conversation;
        $conversation->id = 1;
        $conversation->subject = 'Test';
        $conversation->setRelation('mailbox', $mailbox);

        $thread = new Thread(['body' => 'Test body']);
        $thread->id = 1;

        $mail = new ConversationReplyNotification($conversation, $thread);
        $content = $mail->content();

        $this->assertSame($thread, $content->with['thread']);
    }

    public function test_conversation_reply_notification_attachments_returns_empty_array(): void
    {
        $mailbox = new Mailbox(['id' => 1, 'email' => 'support@example.com']);
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $conversation->setRelation('mailbox', $mailbox);
        $thread = new Thread(['id' => 1]);

        $mail = new ConversationReplyNotification($conversation, $thread);
        $attachments = $mail->attachments();

        $this->assertIsArray($attachments);
        $this->assertEmpty($attachments);
    }

    public function test_conversation_reply_notification_can_be_instantiated_with_required_properties(): void
    {
        $mailbox = new Mailbox(['id' => 1, 'email' => 'support@example.com']);
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $conversation->setRelation('mailbox', $mailbox);
        $thread = new Thread(['id' => 1]);

        $mail = new ConversationReplyNotification($conversation, $thread);

        $this->assertInstanceOf(ConversationReplyNotification::class, $mail);
        $this->assertSame($conversation, $mail->conversation);
        $this->assertSame($thread, $mail->thread);
    }
}
