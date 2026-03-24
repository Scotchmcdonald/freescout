<?php

declare(strict_types=1);

namespace Tests\Unit\Misc;

use App\Misc\MailHelper;
use Tests\Support\EmailFixtures;
use Tests\PureUnitTestCase;

/**
 * Test MailHelper::isAutoResponder() method
 *
 * CRAP Score: 110 (High Priority)
 * Target Coverage: 90%+
 */
class MailHelperIsAutoResponderTest extends PureUnitTestCase
{
    public function test_is_auto_responder_with_null_returns_false(): void
    {
        $result = MailHelper::isAutoResponder(null);

        $this->assertFalse($result);
    }

    public function test_is_auto_responder_with_empty_string_returns_false(): void
    {
        $result = MailHelper::isAutoResponder('');

        $this->assertFalse($result);
    }

    public function test_is_auto_responder_with_no_headers_returns_false(): void
    {
        $headers = "Content-Type: text/plain\nFrom: user@example.com";

        $result = MailHelper::isAutoResponder($headers);

        $this->assertFalse($result);
    }

    public function test_is_auto_responder_detects_x_autoreply_header(): void
    {
        $headers = "From: user@example.com\nX-Autoreply: yes\nSubject: Out of Office";

        $result = MailHelper::isAutoResponder($headers);

        $this->assertTrue($result);
    }

    public function test_is_auto_responder_detects_x_autorespond_header(): void
    {
        $headers = "From: user@example.com\nX-Autorespond: true\nSubject: Auto Reply";

        $result = MailHelper::isAutoResponder($headers);

        $this->assertTrue($result);
    }

    public function test_is_auto_responder_detects_x_autoresponder_header(): void
    {
        $headers = "From: user@example.com\nX-Autoresponder: enabled\nSubject: Vacation";

        $result = MailHelper::isAutoResponder($headers);

        $this->assertTrue($result);
    }

    public function test_is_auto_responder_detects_auto_submitted_header(): void
    {
        $headers = "From: user@example.com\nAuto-Submitted: auto-replied\nSubject: Auto Response";

        $result = MailHelper::isAutoResponder($headers);

        $this->assertTrue($result);
    }

    public function test_is_auto_responder_detects_delivered_to_autoresponder(): void
    {
        $headers = "From: user@example.com\nDelivered-To: autoresponder\nSubject: Auto";

        $result = MailHelper::isAutoResponder($headers);

        $this->assertTrue($result);
    }

    public function test_is_auto_responder_detects_precedence_auto_reply(): void
    {
        $headers = "From: user@example.com\nPrecedence: auto_reply\nSubject: Out";

        $result = MailHelper::isAutoResponder($headers);

        $this->assertTrue($result);
    }

    public function test_is_auto_responder_detects_precedence_bulk(): void
    {
        $headers = "From: user@example.com\nPrecedence: bulk\nSubject: Newsletter";

        $result = MailHelper::isAutoResponder($headers);

        $this->assertTrue($result);
    }

    public function test_is_auto_responder_detects_precedence_junk(): void
    {
        $headers = "From: user@example.com\nPrecedence: junk\nSubject: Spam";

        $result = MailHelper::isAutoResponder($headers);

        $this->assertTrue($result);
    }

    public function test_is_auto_responder_detects_precedence_list(): void
    {
        $headers = "From: user@example.com\nPrecedence: list\nSubject: Mailing List";

        $result = MailHelper::isAutoResponder($headers);

        $this->assertTrue($result);
    }

    public function test_is_auto_responder_detects_x_precedence_auto_reply(): void
    {
        $headers = "From: user@example.com\nX-Precedence: auto_reply\nSubject: Auto";

        $result = MailHelper::isAutoResponder($headers);

        $this->assertTrue($result);
    }

    public function test_is_auto_responder_detects_x_precedence_bulk(): void
    {
        $headers = "From: user@example.com\nX-Precedence: bulk\nSubject: Bulk";

        $result = MailHelper::isAutoResponder($headers);

        $this->assertTrue($result);
    }

    public function test_is_auto_responder_case_insensitive_header_names(): void
    {
        $headers = "From: user@example.com\nx-AuToRePlY: yes\nSubject: Test";

        $result = MailHelper::isAutoResponder($headers);

        $this->assertTrue($result);
    }

    public function test_is_auto_responder_with_whitespace_in_headers(): void
    {
        $headers = "From: user@example.com\n  X-Autoreply:   yes  \nSubject: Test";

        $result = MailHelper::isAutoResponder($headers);

        $this->assertTrue($result);
    }

    public function test_is_auto_responder_ignores_wrong_precedence_value(): void
    {
        $headers = "From: user@example.com\nPrecedence: normal\nSubject: Regular";

        $result = MailHelper::isAutoResponder($headers);

        $this->assertFalse($result);
    }

    public function test_is_auto_responder_ignores_wrong_delivered_to_value(): void
    {
        $headers = "From: user@example.com\nDelivered-To: user@example.com\nSubject: Normal";

        $result = MailHelper::isAutoResponder($headers);

        $this->assertFalse($result);
    }

    public function test_is_auto_responder_with_multiple_headers_finds_auto_one(): void
    {
        $headers = "From: user@example.com\nContent-Type: text/plain\nTo: support@example.com\nX-Autoreply: yes\nDate: Mon, 1 Jan 2024";

        $result = MailHelper::isAutoResponder($headers);

        $this->assertTrue($result);
    }

    public function test_is_auto_responder_with_multiline_headers(): void
    {
        $headers = "From: user@example.com\nReceived: from mail.example.com\n by smtp.example.com\nX-Autorespond: true\nSubject: Test";

        $result = MailHelper::isAutoResponder($headers);

        $this->assertTrue($result);
    }

    public function test_is_auto_responder_handles_headers_without_colon(): void
    {
        // Malformed header lines without colons should be skipped
        $headers = "From: user@example.com\nInvalidHeaderLine\nX-Autoreply: yes";

        $result = MailHelper::isAutoResponder($headers);

        $this->assertTrue($result);
    }

    public function test_is_auto_responder_with_real_auto_responder_email_fixture(): void
    {
        $emailContent = EmailFixtures::load('auto_responder_email');

        // Extract headers (everything before first blank line)
        $parts = explode("\n\n", $emailContent, 2);
        $headers = $parts[0] ?? '';

        $result = MailHelper::isAutoResponder($headers);

        $this->assertTrue($result);
    }

    public function test_is_auto_responder_with_real_valid_email_fixture_returns_false(): void
    {
        $emailContent = EmailFixtures::load('valid_email');

        // Extract headers
        $parts = explode("\n\n", $emailContent, 2);
        $headers = $parts[0] ?? '';

        $result = MailHelper::isAutoResponder($headers);

        $this->assertFalse($result);
    }

    public function test_is_auto_responder_detects_multiple_auto_headers(): void
    {
        $headers = "X-Autoreply: yes\nAuto-Submitted: auto-replied\nPrecedence: bulk";

        // Should return true on first match (X-Autoreply)
        $result = MailHelper::isAutoResponder($headers);

        $this->assertTrue($result);
    }

    public function test_is_auto_responder_with_empty_header_value(): void
    {
        // X-Autoreply with any value (even empty) should trigger
        $headers = "From: user@example.com\nX-Autoreply:\nSubject: Test";

        $result = MailHelper::isAutoResponder($headers);

        $this->assertTrue($result);
    }

    public function test_is_auto_responder_with_unix_line_endings(): void
    {
        $headers = "From: user@example.com\nX-Autoreply: yes\nSubject: Test";

        $result = MailHelper::isAutoResponder($headers);

        $this->assertTrue($result);
    }

    public function test_is_auto_responder_with_windows_line_endings(): void
    {
        $headers = "From: user@example.com\r\nX-Autoreply: yes\r\nSubject: Test";

        // Should still work because explode uses \n
        $result = MailHelper::isAutoResponder($headers);

        $this->assertTrue($result);
    }

    public function test_is_auto_responder_performance_with_large_headers(): void
    {
        // Create headers with 1000 lines
        $headerLines = ['From: user@example.com'];
        for ($i = 0; $i < 998; $i++) {
            $headerLines[] = "Custom-Header-{$i}: value-{$i}";
        }
        $headerLines[] = 'X-Autoreply: yes';

        $headers = implode("\n", $headerLines);

        $result = MailHelper::isAutoResponder($headers);

        $this->assertTrue($result);
    }

    public function test_is_auto_responder_with_colon_in_header_value(): void
    {
        // Header value contains colon - only first colon is separator
        $headers = "From: user@example.com\nReceived: from mail.example.com by smtp.example.com:25\nX-Autoreply: yes";

        $result = MailHelper::isAutoResponder($headers);

        $this->assertTrue($result);
    }
}
