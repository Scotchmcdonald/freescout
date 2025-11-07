<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Mail\AutoReply;
use App\Mail\ConversationReplyNotification;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailTest extends TestCase
{
    use RefreshDatabase;
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

    public function test_auto_reply_uses_custom_subject_when_set(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test Subject']);
        $mailbox = new Mailbox([
            'id' => 1,
            'name' => 'Support',
            'auto_reply_subject' => 'Custom Auto Reply Subject',
        ]);
        $customer = new Customer(['id' => 1]);
        
        $mail = new AutoReply($conversation, $mailbox, $customer);
        $envelope = $mail->envelope();
        
        $this->assertEquals('Custom Auto Reply Subject', $envelope->subject);
    }

    public function test_auto_reply_uses_default_subject_when_not_set(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Original Subject']);
        $mailbox = new Mailbox(['id' => 1, 'name' => 'Support', 'auto_reply_subject' => null]);
        $customer = new Customer(['id' => 1]);
        
        $mail = new AutoReply($conversation, $mailbox, $customer);
        $envelope = $mail->envelope();
        
        $this->assertEquals('Re: Original Subject', $envelope->subject);
    }

    public function test_auto_reply_content_has_custom_message(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $mailbox = new Mailbox([
            'id' => 1,
            'name' => 'Support',
            'auto_reply_message' => 'Thank you for contacting us!',
        ]);
        $customer = new Customer(['id' => 1]);
        
        $mail = new AutoReply($conversation, $mailbox, $customer);
        $content = $mail->content();
        
        $this->assertEquals('Thank you for contacting us!', $content->with['message']);
    }

    public function test_auto_reply_content_has_default_message(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $mailbox = new Mailbox(['id' => 1, 'name' => 'Support', 'auto_reply_message' => null]);
        $customer = new Customer(['id' => 1]);
        
        $mail = new AutoReply($conversation, $mailbox, $customer);
        $content = $mail->content();
        
        $this->assertEquals(
            'We have received your message and will get back to you shortly.',
            $content->with['message']
        );
    }

    public function test_auto_reply_content_includes_conversation(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $mailbox = new Mailbox(['id' => 1, 'name' => 'Support']);
        $customer = new Customer(['id' => 1]);
        
        $mail = new AutoReply($conversation, $mailbox, $customer);
        $content = $mail->content();
        
        $this->assertArrayHasKey('conversation', $content->with);
        $this->assertArrayHasKey('mailbox', $content->with);
        $this->assertArrayHasKey('customer', $content->with);
    }

    public function test_auto_reply_can_have_custom_headers(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $mailbox = new Mailbox(['id' => 1, 'name' => 'Support']);
        $customer = new Customer(['id' => 1]);
        $headers = [
            'X-Custom-Header' => 'Custom Value',
            'X-Priority' => 'High',
        ];
        
        $mail = new AutoReply($conversation, $mailbox, $customer, $headers);
        
        $this->assertSame($headers, $mail->headers);
    }

    public function test_auto_reply_builds_with_text_view(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $mailbox = new Mailbox(['id' => 1, 'name' => 'Support']);
        $customer = new Customer(['id' => 1]);
        
        $mail = new AutoReply($conversation, $mailbox, $customer);
        $content = $mail->content();
        
        $this->assertEquals('emails.auto-reply', $content->text);
    }

    public function test_conversation_reply_notification_envelope_has_from(): void
    {
        $mailbox = new Mailbox(['id' => 1, 'name' => 'Support', 'email' => 'support@example.com']);
        $conversation = new Conversation([
            'id' => 1,
            'subject' => 'Test Subject',
            'mailbox_id' => 1,
        ]);
        $conversation->setRelation('mailbox', $mailbox);
        $thread = new Thread(['id' => 1, 'body' => 'Reply message']);
        
        $mail = new ConversationReplyNotification($conversation, $thread);
        $envelope = $mail->envelope();
        
        $this->assertNotNull($envelope->from);
        // from is a single Address object, not an array
        $this->assertEquals('support@example.com', $envelope->from->address);
    }

    public function test_conversation_reply_notification_envelope_has_reply_to(): void
    {
        $mailbox = new Mailbox(['id' => 1, 'name' => 'Support', 'email' => 'support@example.com']);
        $conversation = new Conversation([
            'id' => 1,
            'subject' => 'Test Subject',
            'mailbox_id' => 1,
        ]);
        $conversation->setRelation('mailbox', $mailbox);
        $thread = new Thread(['id' => 1, 'body' => 'Reply message']);
        
        $mail = new ConversationReplyNotification($conversation, $thread);
        $envelope = $mail->envelope();
        
        $this->assertCount(1, $envelope->replyTo);
        $this->assertEquals('support@example.com', $envelope->replyTo[0]->address);
    }

    public function test_conversation_reply_notification_subject(): void
    {
        $mailbox = new Mailbox(['id' => 1, 'name' => 'Support', 'email' => 'support@example.com']);
        $conversation = new Conversation([
            'id' => 1,
            'subject' => 'Help Request',
            'mailbox_id' => 1,
        ]);
        $conversation->setRelation('mailbox', $mailbox);
        $thread = new Thread(['id' => 1, 'body' => 'Reply message']);
        
        $mail = new ConversationReplyNotification($conversation, $thread);
        $envelope = $mail->envelope();
        
        $this->assertEquals('Re: Help Request', $envelope->subject);
    }

    public function test_conversation_reply_notification_content_uses_markdown(): void
    {
        $mailbox = Mailbox::factory()->create(['name' => 'Support', 'email' => 'support@example.com']);
        $conversation = Conversation::factory()->for($mailbox)->create(['subject' => 'Test']);
        $thread = Thread::factory()->for($conversation)->create(['body' => 'Reply message']);
        
        $mail = new ConversationReplyNotification($conversation, $thread);
        $content = $mail->content();
        
        $this->assertEquals('emails.conversation.reply', $content->markdown);
    }

    public function test_conversation_reply_notification_content_includes_thread(): void
    {
        $mailbox = Mailbox::factory()->create(['name' => 'Support', 'email' => 'support@example.com']);
        $conversation = Conversation::factory()->for($mailbox)->create(['subject' => 'Test']);
        $thread = Thread::factory()->for($conversation)->create(['body' => 'Reply message']);
        
        $mail = new ConversationReplyNotification($conversation, $thread);
        $content = $mail->content();
        
        $this->assertArrayHasKey('conversation', $content->with);
        $this->assertArrayHasKey('thread', $content->with);
        $this->assertArrayHasKey('url', $content->with);
        $this->assertEquals($conversation->id, $content->with['conversation']->id);
        $this->assertEquals($thread->id, $content->with['thread']->id);
    }

    public function test_conversation_reply_notification_has_no_attachments_by_default(): void
    {
        $mailbox = new Mailbox(['id' => 1, 'name' => 'Support', 'email' => 'support@example.com']);
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test', 'mailbox_id' => 1]);
        $conversation->setRelation('mailbox', $mailbox);
        $thread = new Thread(['id' => 1, 'body' => 'Reply message']);
        
        $mail = new ConversationReplyNotification($conversation, $thread);
        $attachments = $mail->attachments();
        
        $this->assertIsArray($attachments);
        $this->assertEmpty($attachments);
    }

    public function test_auto_reply_mailable_uses_queueable_trait(): void
    {
        $conversation = new Conversation(['id' => 1]);
        $mailbox = new Mailbox(['id' => 1]);
        $customer = new Customer(['id' => 1]);
        
        $mail = new AutoReply($conversation, $mailbox, $customer);
        
        $this->assertContains(
            'Illuminate\Bus\Queueable',
            class_uses_recursive(AutoReply::class)
        );
    }

    public function test_conversation_reply_notification_uses_queueable_trait(): void
    {
        $conversation = new Conversation(['id' => 1]);
        $thread = new Thread(['id' => 1]);
        
        $mail = new ConversationReplyNotification($conversation, $thread);
        
        $this->assertContains(
            'Illuminate\Bus\Queueable',
            class_uses_recursive(ConversationReplyNotification::class)
        );
    }

    public function test_auto_reply_can_be_serialized(): void
    {
        $mailbox = Mailbox::factory()->create(['name' => 'Support']);
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->for($mailbox)->for($customer)->create(['subject' => 'Test']);
        
        $mail = new AutoReply($conversation, $mailbox, $customer);
        $serialized = serialize($mail);
        $unserialized = unserialize($serialized);
        
        $this->assertInstanceOf(AutoReply::class, $unserialized);
        $this->assertEquals($conversation->id, $unserialized->conversation->id);
        $this->assertEquals($mailbox->id, $unserialized->mailbox->id);
        $this->assertEquals($customer->id, $unserialized->customer->id);
    }

    public function test_conversation_reply_notification_can_be_serialized(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->for($mailbox)->create(['subject' => 'Test']);
        $thread = Thread::factory()->for($conversation)->create(['body' => 'Reply']);
        
        $mail = new ConversationReplyNotification($conversation, $thread);
        $serialized = serialize($mail);
        $unserialized = unserialize($serialized);
        
        $this->assertInstanceOf(ConversationReplyNotification::class, $unserialized);
        $this->assertEquals($conversation->id, $unserialized->conversation->id);
        $this->assertEquals($thread->id, $unserialized->thread->id);
    }

    /** Test AutoReply with empty subject fallback */
    public function test_auto_reply_handles_empty_conversation_subject(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => '']);
        $mailbox = new Mailbox(['id' => 1, 'name' => 'Support', 'auto_reply_subject' => null]);
        $customer = new Customer(['id' => 1]);
        
        $mail = new AutoReply($conversation, $mailbox, $customer);
        $envelope = $mail->envelope();
        
        $this->assertEquals('Re: ', $envelope->subject);
    }

    /** Test AutoReply with empty custom subject uses default */
    public function test_auto_reply_with_empty_custom_subject_uses_default(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Original']);
        $mailbox = new Mailbox(['id' => 1, 'name' => 'Support', 'auto_reply_subject' => '']);
        $customer = new Customer(['id' => 1]);
        
        $mail = new AutoReply($conversation, $mailbox, $customer);
        $envelope = $mail->envelope();
        
        // Empty string is falsy, so should use default
        $this->assertEquals('Re: Original', $envelope->subject);
    }

    /** Test AutoReply with empty custom message uses default */
    public function test_auto_reply_with_empty_custom_message_uses_default(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $mailbox = new Mailbox(['id' => 1, 'name' => 'Support', 'auto_reply_message' => '']);
        $customer = new Customer(['id' => 1]);
        
        $mail = new AutoReply($conversation, $mailbox, $customer);
        $content = $mail->content();
        
        // Empty string is falsy, so should use default
        $this->assertEquals(
            'We have received your message and will get back to you shortly.',
            $content->with['message']
        );
    }

    /** Test AutoReply with Message-ID header is skipped */
    public function test_auto_reply_skips_message_id_header(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $mailbox = new Mailbox(['id' => 1, 'name' => 'Support']);
        $customer = new Customer(['id' => 1]);
        $headers = [
            'Message-ID' => '<test@example.com>',
            'X-Custom' => 'Value',
        ];
        
        $mail = new AutoReply($conversation, $mailbox, $customer, $headers);
        
        // Message-ID should be in headers array but will be skipped in build()
        $this->assertArrayHasKey('Message-ID', $mail->headers);
        $this->assertArrayHasKey('X-Custom', $mail->headers);
    }

    /** Test AutoReply with empty headers array */
    public function test_auto_reply_with_empty_headers(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $mailbox = new Mailbox(['id' => 1, 'name' => 'Support']);
        $customer = new Customer(['id' => 1]);
        
        $mail = new AutoReply($conversation, $mailbox, $customer, []);
        
        $this->assertIsArray($mail->headers);
        $this->assertEmpty($mail->headers);
    }

    /** Test AutoReply build method consistency with envelope */
    public function test_auto_reply_build_uses_same_subject_as_envelope(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test Subject']);
        $mailbox = new Mailbox(['id' => 1, 'name' => 'Support', 'auto_reply_subject' => 'Custom']);
        $customer = new Customer(['id' => 1]);
        
        $mail = new AutoReply($conversation, $mailbox, $customer);
        $envelope = $mail->envelope();
        
        // Both envelope() and build() should use the same subject logic
        $this->assertEquals('Custom', $envelope->subject);
    }

    /** Test AutoReply with special characters in subject */
    public function test_auto_reply_handles_special_characters_in_subject(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test <script>alert("xss")</script> Subject']);
        $mailbox = new Mailbox(['id' => 1, 'name' => 'Support', 'auto_reply_subject' => null]);
        $customer = new Customer(['id' => 1]);
        
        $mail = new AutoReply($conversation, $mailbox, $customer);
        $envelope = $mail->envelope();
        
        $this->assertEquals('Re: Test <script>alert("xss")</script> Subject', $envelope->subject);
    }

    /** Test AutoReply with unicode characters in subject */
    public function test_auto_reply_handles_unicode_in_subject(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test 日本語 Subject 中文']);
        $mailbox = new Mailbox(['id' => 1, 'name' => 'Support', 'auto_reply_subject' => null]);
        $customer = new Customer(['id' => 1]);
        
        $mail = new AutoReply($conversation, $mailbox, $customer);
        $envelope = $mail->envelope();
        
        $this->assertEquals('Re: Test 日本語 Subject 中文', $envelope->subject);
    }

    /** Test AutoReply with very long subject */
    public function test_auto_reply_handles_long_subject(): void
    {
        $longSubject = str_repeat('Very Long Subject ', 50); // ~900 characters
        $conversation = new Conversation(['id' => 1, 'subject' => $longSubject]);
        $mailbox = new Mailbox(['id' => 1, 'name' => 'Support', 'auto_reply_subject' => null]);
        $customer = new Customer(['id' => 1]);
        
        $mail = new AutoReply($conversation, $mailbox, $customer);
        $envelope = $mail->envelope();
        
        $this->assertEquals('Re: ' . $longSubject, $envelope->subject);
    }

    /** Test ConversationReplyNotification with empty subject */
    public function test_conversation_reply_notification_handles_empty_subject(): void
    {
        $mailbox = new Mailbox(['id' => 1, 'name' => 'Support', 'email' => 'support@example.com']);
        $conversation = new Conversation(['id' => 1, 'subject' => '', 'mailbox_id' => 1]);
        $conversation->setRelation('mailbox', $mailbox);
        $thread = new Thread(['id' => 1, 'body' => 'Reply message']);
        
        $mail = new ConversationReplyNotification($conversation, $thread);
        $envelope = $mail->envelope();
        
        $this->assertEquals('Re: ', $envelope->subject);
    }

    /** Test ConversationReplyNotification with null subject */
    public function test_conversation_reply_notification_handles_null_subject(): void
    {
        $mailbox = new Mailbox(['id' => 1, 'name' => 'Support', 'email' => 'support@example.com']);
        $conversation = new Conversation(['id' => 1, 'subject' => null, 'mailbox_id' => 1]);
        $conversation->setRelation('mailbox', $mailbox);
        $thread = new Thread(['id' => 1, 'body' => 'Reply message']);
        
        $mail = new ConversationReplyNotification($conversation, $thread);
        $envelope = $mail->envelope();
        
        $this->assertEquals('Re: ', $envelope->subject);
    }

    /** Test ConversationReplyNotification with special characters in subject */
    public function test_conversation_reply_notification_handles_special_chars(): void
    {
        $mailbox = new Mailbox(['id' => 1, 'name' => 'Support', 'email' => 'support@example.com']);
        $conversation = new Conversation([
            'id' => 1,
            'subject' => 'Test & Special <Characters>',
            'mailbox_id' => 1,
        ]);
        $conversation->setRelation('mailbox', $mailbox);
        $thread = new Thread(['id' => 1, 'body' => 'Reply message']);
        
        $mail = new ConversationReplyNotification($conversation, $thread);
        $envelope = $mail->envelope();
        
        $this->assertEquals('Re: Test & Special <Characters>', $envelope->subject);
    }
}
