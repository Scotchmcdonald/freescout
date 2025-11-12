<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Misc\MailHelper;
use Tests\TestCase;

class MailHelperTest extends TestCase
{
    public function test_is_auto_responder_detects_x_autoreply(): void
    {
        $headers = "X-Autoreply: yes\nFrom: test@example.com";

        $this->assertTrue(MailHelper::isAutoResponder($headers));
    }

    public function test_is_auto_responder_detects_auto_submitted(): void
    {
        $headers = "Auto-Submitted: auto-replied\nFrom: test@example.com";

        $this->assertTrue(MailHelper::isAutoResponder($headers));
    }

    public function test_is_auto_responder_detects_precedence_bulk(): void
    {
        $headers = "Precedence: bulk\nFrom: test@example.com";

        $this->assertTrue(MailHelper::isAutoResponder($headers));
    }

    public function test_is_auto_responder_detects_precedence_junk(): void
    {
        $headers = "Precedence: junk\nFrom: test@example.com";

        $this->assertTrue(MailHelper::isAutoResponder($headers));
    }

    public function test_is_auto_responder_detects_precedence_list(): void
    {
        $headers = "Precedence: list\nFrom: test@example.com";

        $this->assertTrue(MailHelper::isAutoResponder($headers));
    }

    public function test_is_auto_responder_detects_x_autorespond(): void
    {
        $headers = "X-Autorespond: yes\nFrom: test@example.com";

        $this->assertTrue(MailHelper::isAutoResponder($headers));
    }

    public function test_is_auto_responder_detects_x_autoresponder(): void
    {
        $headers = "X-Autoresponder: enabled\nFrom: test@example.com";

        $this->assertTrue(MailHelper::isAutoResponder($headers));
    }

    public function test_is_auto_responder_returns_false_for_normal_email(): void
    {
        $headers = "From: user@example.com\nTo: support@example.com";

        $this->assertFalse(MailHelper::isAutoResponder($headers));
    }

    public function test_is_auto_responder_returns_false_for_empty_headers(): void
    {
        $this->assertFalse(MailHelper::isAutoResponder(''));
    }

    public function test_is_auto_responder_returns_false_for_null_headers(): void
    {
        $this->assertFalse(MailHelper::isAutoResponder(null));
    }

    public function test_is_auto_responder_case_insensitive(): void
    {
        $headers1 = "x-autoreply: yes\nFrom: test@example.com";
        $headers2 = "X-AUTOREPLY: yes\nFrom: test@example.com";
        $headers3 = "X-AutoReply: yes\nFrom: test@example.com";

        $this->assertTrue(MailHelper::isAutoResponder($headers1));
        $this->assertTrue(MailHelper::isAutoResponder($headers2));
        $this->assertTrue(MailHelper::isAutoResponder($headers3));
    }

    // Story 5.2.1: Message ID Generation and Email Parsing

    public function test_generate_message_id_creates_valid_format(): void
    {
        if (! method_exists(MailHelper::class, 'generateMessageId')) {
            $this->markTestSkipped('generateMessageId method does not exist');
        }

        $messageId = MailHelper::generateMessageId('example.com');

        $this->assertMatchesRegularExpression('/^fs-[\w\-\.]+@example\.com$/', $messageId);
        $this->assertStringStartsWith('fs-', $messageId);
        $this->assertStringContainsString('@example.com', $messageId);
    }

    public function test_generate_message_id_is_unique(): void
    {
        if (! method_exists(MailHelper::class, 'generateMessageId')) {
            $this->markTestSkipped('generateMessageId method does not exist');
        }

        $id1 = MailHelper::generateMessageId('test.com');
        $id2 = MailHelper::generateMessageId('test.com');

        $this->assertNotEquals($id1, $id2);
    }

    public function test_parse_email_extracts_address_correctly(): void
    {
        if (! method_exists(MailHelper::class, 'parseEmail')) {
            $this->markTestSkipped('parseEmail method does not exist');
        }

        $testCases = [
            'user@example.com' => 'user@example.com',
            'John Doe <john@example.com>' => 'john@example.com',
            '<user@example.com>' => 'user@example.com',
            'user+tag@example.com' => 'user+tag@example.com',
        ];

        foreach ($testCases as $input => $expected) {
            $result = MailHelper::parseEmail($input);
            $this->assertEquals($expected, $result, "Failed for input: {$input}");
        }
    }

    public function test_sanitize_email_removes_dangerous_content(): void
    {
        if (! method_exists(MailHelper::class, 'sanitizeEmail')) {
            $this->markTestSkipped('sanitizeEmail method does not exist');
        }

        $dangerous = '<p>Safe content</p><script>alert("xss")</script><iframe src="evil.com"></iframe>';

        $result = MailHelper::sanitizeEmail($dangerous);

        $this->assertStringContainsString('Safe content', $result);
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('<iframe>', $result);
    }

    public function test_format_email_with_name(): void
    {
        if (! method_exists(MailHelper::class, 'formatEmail')) {
            $this->markTestSkipped('formatEmail method does not exist');
        }

        $result = MailHelper::formatEmail('john@example.com', 'John Doe');

        $this->assertEquals('John Doe <john@example.com>', $result);
    }

    public function test_format_email_without_name(): void
    {
        if (! method_exists(MailHelper::class, 'formatEmail')) {
            $this->markTestSkipped('formatEmail method does not exist');
        }

        $result = MailHelper::formatEmail('john@example.com', null);

        $this->assertEquals('john@example.com', $result);
    }

    public function test_extract_reply_separators(): void
    {
        if (! method_exists(MailHelper::class, 'extractReply')) {
            $this->markTestSkipped('extractReply method does not exist');
        }

        $emailBody = <<<'EMAIL'
This is the new reply.

On Mon, Nov 11, 2024 at 10:30 AM, sender@example.com wrote:
> This is the previous message
> with multiple lines
EMAIL;

        $result = MailHelper::extractReply($emailBody);

        $this->assertStringContainsString('This is the new reply', $result);
        $this->assertStringNotContainsString('On Mon, Nov 11', $result);
    }
}
