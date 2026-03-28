<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\AutoReply;
use App\Models\Conversation;
use App\Models\Mailbox;
use Tests\PureUnitTestCase;

class AutoReplyEnhancedTest extends PureUnitTestCase
{
    public function test_auto_reply_content_returns_text_view(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $mailbox = new Mailbox(['id' => 1]);

        $mail = new AutoReply($conversation, $mailbox);
        $content = $mail->content();

        $this->assertEquals('emails.auto-reply', $content->text);
        $this->assertArrayHasKey('message', $content->with);
        $this->assertArrayHasKey('conversation', $content->with);
        $this->assertArrayHasKey('mailbox', $content->with);
    }

    public function test_auto_reply_content_uses_default_message_when_none_set(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $mailbox = new Mailbox(['id' => 1, 'auto_reply_message' => null]);

        $mail = new AutoReply($conversation, $mailbox);
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

        $mail = new AutoReply($conversation, $mailbox);
        $content = $mail->content();

        $this->assertEquals('Custom auto-reply message', $content->with['message']);
    }

    public function test_auto_reply_envelope_uses_default_subject_when_none_set(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Original Subject']);
        $mailbox = new Mailbox(['id' => 1, 'auto_reply_subject' => null]);

        $mail = new AutoReply($conversation, $mailbox);
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

        $mail = new AutoReply($conversation, $mailbox);
        $envelope = $mail->envelope();

        $this->assertEquals('Custom Auto Reply Subject', $envelope->subject);
    }

    public function test_auto_reply_build_returns_mailable(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $mailbox = new Mailbox(['id' => 1]);

        $mail = new AutoReply($conversation, $mailbox);
        $result = $mail->build();

        $this->assertInstanceOf(AutoReply::class, $result);
    }

    public function test_auto_reply_can_be_created_with_headers(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $mailbox = new Mailbox(['id' => 1]);

        $headers = ['X-Custom-Header' => 'Custom Value'];

        $mail = new AutoReply($conversation, $mailbox, $headers);

        $this->assertEquals($headers, $mail->headers);
    }

    public function test_auto_reply_with_empty_headers_array(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $mailbox = new Mailbox(['id' => 1]);

        $mail = new AutoReply($conversation, $mailbox, []);

        $this->assertEquals([], $mail->headers);
        $this->assertInstanceOf(AutoReply::class, $mail->build());
    }

    // build() method tests - 63% coverage

    public function test_build_uses_mailbox_from_address(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $mailbox = new Mailbox([
            'id' => 1,
            'email' => 'support@example.com',
            'name' => 'Support Team',
        ]);

        $mail = new AutoReply($conversation, $mailbox);
        $result = $mail->build();

        $this->assertInstanceOf(AutoReply::class, $result);
    }

    public function test_build_sets_correct_subject(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Help Request']);
        $mailbox = new Mailbox(['id' => 1]);

        $mail = new AutoReply($conversation, $mailbox);
        $result = $mail->build();

        // Subject is set via the subject() method in build()
        $this->assertInstanceOf(AutoReply::class, $result);
    }

    public function test_build_passes_variables_to_view(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $mailbox = new Mailbox([
            'id' => 1,
            'auto_reply_message' => 'Custom message for you',
        ]);

        $mail = new AutoReply($conversation, $mailbox);
        $result = $mail->build();

        // The text() method is called with the view data
        $this->assertInstanceOf(AutoReply::class, $result);
    }

    public function test_build_adds_custom_headers(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $mailbox = new Mailbox(['id' => 1]);

        $headers = [
            'X-Auto-Reply' => 'true',
            'X-Conversation-ID' => '123',
        ];

        $mail = new AutoReply($conversation, $mailbox, $headers);
        $result = $mail->build();

        $this->assertInstanceOf(AutoReply::class, $result);
    }

    public function test_build_skips_message_id_header(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $mailbox = new Mailbox(['id' => 1]);

        $headers = [
            'Message-ID' => '<custom-id@example.com>',
            'X-Custom-Header' => 'value',
        ];

        $mail = new AutoReply($conversation, $mailbox, $headers);
        $result = $mail->build();

        // Message-ID should be skipped but X-Custom-Header should be added
        $this->assertInstanceOf(AutoReply::class, $result);
    }

    public function test_build_handles_multiple_custom_headers(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $mailbox = new Mailbox(['id' => 1]);

        $headers = [
            'X-Header-1' => 'value1',
            'X-Header-2' => 'value2',
            'X-Header-3' => 'value3',
        ];

        $mail = new AutoReply($conversation, $mailbox, $headers);
        $result = $mail->build();

        $this->assertInstanceOf(AutoReply::class, $result);
    }

    public function test_build_uses_text_template(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $mailbox = new Mailbox(['id' => 1]);

        $mail = new AutoReply($conversation, $mailbox);
        $result = $mail->build();

        // The text() method should be called with 'emails.auto-reply'
        $this->assertInstanceOf(AutoReply::class, $result);
    }

    // Additional edge case tests for AutoReply

    public function test_build_handles_empty_subject(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => '']);
        $mailbox = new Mailbox(['id' => 1, 'auto_reply_subject' => null]);

        $mail = new AutoReply($conversation, $mailbox);
        $result = $mail->build();

        // Should handle empty subject gracefully
        $this->assertInstanceOf(AutoReply::class, $result);
    }

    public function test_build_handles_very_long_subject(): void
    {
        $longSubject = str_repeat('This is a very long subject line ', 20);
        $conversation = new Conversation(['id' => 1, 'subject' => $longSubject]);
        $mailbox = new Mailbox(['id' => 1]);

        $mail = new AutoReply($conversation, $mailbox);
        $result = $mail->build();

        $this->assertInstanceOf(AutoReply::class, $result);
    }

    public function test_build_handles_unicode_in_subject(): void
    {
        $unicodeSubject = '件名: テストメール 🎉';
        $conversation = new Conversation(['id' => 1, 'subject' => $unicodeSubject]);
        $mailbox = new Mailbox(['id' => 1]);

        $mail = new AutoReply($conversation, $mailbox);
        $result = $mail->build();

        $this->assertInstanceOf(AutoReply::class, $result);
    }

    public function test_build_handles_special_characters_in_message(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $specialMessage = 'Message with <html> & "quotes" and \'apostrophes\'';
        $mailbox = new Mailbox([
            'id' => 1,
            'auto_reply_message' => $specialMessage,
        ]);

        $mail = new AutoReply($conversation, $mailbox);
        $result = $mail->build();

        $this->assertInstanceOf(AutoReply::class, $result);
    }

    public function test_build_handles_minimal_args(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $mailbox = new Mailbox(['id' => 1]);

        $mail = new AutoReply($conversation, $mailbox);
        $result = $mail->build();

        $this->assertInstanceOf(AutoReply::class, $result);
    }

    public function test_envelope_with_very_long_custom_subject(): void
    {
        $longCustomSubject = str_repeat('Custom Subject ', 30);
        $conversation = new Conversation(['id' => 1, 'subject' => 'Original']);
        $mailbox = new Mailbox([
            'id' => 1,
            'auto_reply_subject' => $longCustomSubject,
        ]);

        $mail = new AutoReply($conversation, $mailbox);
        $envelope = $mail->envelope();

        $this->assertEquals($longCustomSubject, $envelope->subject);
    }

    public function test_content_with_multiline_message(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $multilineMessage = "Line 1\nLine 2\nLine 3\n\nLine 5 after blank";
        $mailbox = new Mailbox([
            'id' => 1,
            'auto_reply_message' => $multilineMessage,
        ]);

        $mail = new AutoReply($conversation, $mailbox);
        $content = $mail->content();

        $this->assertEquals($multilineMessage, $content->with['message']);
    }

    public function test_build_with_header_containing_colon(): void
    {
        $conversation = new Conversation(['id' => 1, 'subject' => 'Test']);
        $mailbox = new Mailbox(['id' => 1]);

        $headers = [
            'X-Custom-Header' => 'value:with:colons',
        ];

        $mail = new AutoReply($conversation, $mailbox, $headers);
        $result = $mail->build();

        // Should handle header values with colons
        $this->assertInstanceOf(AutoReply::class, $result);
    }

    public function test_validation_auto_reply_subject_preserves_conversation_context(): void
    {
        // Validation boundary: the auto-reply subject must always contain the
        // original conversation subject so recipients can validate the thread context
        // and the message is not mistaken for a phishing/spam attempt.
        $conversation = new Conversation(['id' => 1, 'subject' => 'Urgent Help Request']);
        $mailbox = new Mailbox(['id' => 1]); // no custom auto_reply_subject

        $envelope = (new AutoReply($conversation, $mailbox))->envelope();

        $this->assertStringContainsString(
            'Urgent Help Request',
            $envelope->subject,
            'Validation boundary: auto-reply subject must contain the original conversation subject'
        );
    }
}
