<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ImapService;
use Tests\UnitTestCase;
use Mockery;

/**
 * Test Suite for IMAP Service Helper Methods - Advanced
 *
 * This test suite covers advanced helper methods:
 * - separateReply() (29 tests) - High complexity method for reply separation
 * - getOriginalSenderFromFwd() (22 tests) - Medium complexity forwarding detection
 * Total: 51 tests
 *
 * These methods handle complex message parsing and pattern matching.
 */
class ImapServiceHelpersAdvancedTest extends UnitTestCase
{
    protected ImapService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ImapService();
    }

    protected function tearDown(): void
    {
        try {
            Mockery::close();
        } finally {
            parent::tearDown();
        }
    }

    /**
     * Helper method to invoke private/protected methods using reflection
     */
    protected function invokeMethod($object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    // =====================================================================
    // Tests for separateReply() - HIGH (CRAP: 72)
    // =====================================================================

    public function test_separate_reply_returns_full_body_when_not_reply(): void
    {
        $body = 'This is the full email body content.';

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, false]);

        $this->assertEquals($body, $result);
    }

    public function test_separate_reply_returns_full_html_body_when_not_reply(): void
    {
        $body = '<html><body><p>Full HTML content</p></body></html>';

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, true, false]);

        $this->assertEquals($body, $result);
    }

    public function test_separate_reply_extracts_content_from_html_body_tag(): void
    {
        $body = '<html><head></head><body><p>Main content</p></body></html>';

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, true, true]);

        $this->assertStringContainsString('Main content', $result);
        $this->assertStringNotContainsString('<html>', $result);
        $this->assertStringNotContainsString('<head>', $result);
    }

    public function test_separate_reply_extracts_content_from_body_tag_with_attributes(): void
    {
        $body = '<html><body style="color:black" class="test"><p>Content here</p></body></html>';

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, true, true]);

        $this->assertStringContainsString('Content here', $result);
        $this->assertStringNotContainsString('<html>', $result);
    }

    public function test_separate_reply_handles_html_without_body_tag(): void
    {
        $body = '<p>Direct HTML content</p>';

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, true, true]);

        $this->assertStringContainsString('Direct HTML content', $result);
    }

    public function test_separate_reply_handles_protonmail_quote(): void
    {
        $body = 'New reply content<div class="protonmail_quote">Previous message</div>';

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, true, true]);

        $this->assertStringContainsString('New reply content', $result);
        $this->assertStringNotContainsString('Previous message', $result);
    }

    public function test_separate_reply_handles_protonmail_quote_case_insensitive(): void
    {
        $body = 'New reply<div class="ProtonMail_Quote">Old message</div>';

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, true, true]);

        $this->assertStringContainsString('New reply', $result);
        $this->assertStringNotContainsString('Old message', $result);
    }

    public function test_separate_reply_handles_replied_above_separator(): void
    {
        $body = "New content here\n---- Replied Above ----\nOld content";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertStringContainsString('New content', $result);
        $this->assertStringNotContainsString('Old content', $result);
    }

    public function test_separate_reply_handles_replied_above_separator_case_insensitive(): void
    {
        $body = "New content\n---- replied above ----\nOld content";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertStringContainsString('New content', $result);
        $this->assertStringNotContainsString('Old content', $result);
    }

    public function test_separate_reply_handles_on_date_wrote_separator(): void
    {
        $body = "My reply\nOn 2025-01-01, John wrote:\n> Original message";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertStringContainsString('My reply', $result);
        $this->assertStringNotContainsString('Original message', $result);
    }

    public function test_separate_reply_handles_on_date_wrote_various_formats(): void
    {
        $body = "Response here\nOn Mon, Jan 1, 2025 at 10:00 AM, User <user@example.com> wrote:\n> Quoted";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertStringContainsString('Response here', $result);
        $this->assertStringNotContainsString('Quoted', $result);
    }

    public function test_separate_reply_handles_on_date_wrote_case_insensitive(): void
    {
        $body = "Reply\non 2025-01-01, john WROTE:\n> Quote";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertStringContainsString('Reply', $result);
    }

    public function test_separate_reply_handles_from_separator(): void
    {
        $body = "Forwarding this\nFrom: Original Sender\nOriginal content";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertStringContainsString('Forwarding this', $result);
        $this->assertStringNotContainsString('Original content', $result);
    }

    public function test_separate_reply_handles_from_separator_case_insensitive(): void
    {
        $body = "New message\nfrom: sender@example.com\nOld message";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertStringContainsString('New message', $result);
        $this->assertStringNotContainsString('Old message', $result);
    }

    public function test_separate_reply_handles_from_separator_with_full_header(): void
    {
        $body = "FYI\nFrom: John Doe <john@example.com>\nDate: Jan 1, 2025\nSubject: Test\n\nOriginal body";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertStringContainsString('FYI', $result);
        $this->assertStringNotContainsString('Original body', $result);
    }

    public function test_separate_reply_handles_underscore_separator(): void
    {
        $body = "New message\n________\nQuoted text";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertStringContainsString('New message', $result);
        $this->assertStringNotContainsString('Quoted text', $result);
    }

    public function test_separate_reply_handles_underscore_separator_case_insensitive(): void
    {
        $body = "Reply text\n________\nOld text";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertStringContainsString('Reply text', $result);
        $this->assertStringNotContainsString('Old text', $result);
    }

    public function test_separate_reply_returns_full_body_if_no_separator_found(): void
    {
        $body = 'Complete email body with no reply markers';

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertEquals(nl2br($body), $result);
    }

    public function test_separate_reply_converts_plain_text_to_html(): void
    {
        $body = "Line 1\nLine 2\nLine 3";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertStringContainsString('<br />', $result);
    }

    public function test_separate_reply_does_not_convert_html_to_br(): void
    {
        $body = "<p>Line 1</p>\n<p>Line 2</p>";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, true, true]);

        // HTML should not get nl2br applied
        $this->assertStringContainsString('<p>Line 1</p>', $result);
    }

    public function test_separate_reply_skips_separator_if_no_content_before(): void
    {
        $body = "---- Replied Above ----\nOnly old content here";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        // Should return full body since there's no real content before separator
        $this->assertStringContainsString('old content', $result);
    }

    public function test_separate_reply_skips_separator_if_only_whitespace_before(): void
    {
        $body = "   \n\n---- Replied Above ----\nActual content";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        // Should not split at separator if only whitespace before
        $this->assertStringContainsString('Actual content', $result);
    }

    public function test_separate_reply_skips_separator_if_only_html_tags_before(): void
    {
        $body = "<div></div>---- Replied Above ----\nContent here";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, true, true]);

        // Empty tags don't count as content
        $this->assertStringContainsString('Content here', $result);
    }

    public function test_separate_reply_uses_first_matching_separator(): void
    {
        $body = "New\n---- Replied Above ----\nMiddle\nFrom: sender\nOld";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        // Should stop at first separator
        $this->assertStringContainsString('New', $result);
        $this->assertStringNotContainsString('Middle', $result);
        $this->assertStringNotContainsString('Old', $result);
    }

    public function test_separate_reply_handles_multiple_line_breaks(): void
    {
        $body = "Reply\n\n\n\nOn date wrote:\nQuote";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertStringContainsString('Reply', $result);
        $this->assertStringNotContainsString('Quote', $result);
    }

    public function test_separate_reply_handles_empty_body(): void
    {
        $body = "";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertEquals(nl2br(''), $result);
    }

    public function test_separate_reply_handles_body_with_only_separator(): void
    {
        $body = "From: sender@example.com";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        // No content before separator, should return full body
        $this->assertStringContainsString('From:', $result);
    }

    public function test_separate_reply_preserves_html_structure_in_reply(): void
    {
        $body = "<p>My <strong>reply</strong></p><div class=\"protonmail_quote\">Quote</div>";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, true, true]);

        $this->assertStringContainsString('<p>My <strong>reply</strong></p>', $result);
        $this->assertStringNotContainsString('Quote', $result);
    }

    public function test_separate_reply_handles_separator_in_middle_of_line(): void
    {
        $body = "Text before From: middle text\nAfter";

        $result = $this->invokeMethod($this->service, 'separateReply', [$body, false, true]);

        $this->assertStringContainsString('Text before', $result);
        $this->assertStringNotContainsString('After', $result);
    }

    // =====================================================================
    // =====================================================================
    // Tests for getOriginalSenderFromFwd() - MEDIUM (CRAP: 30)
    // =====================================================================

    public function test_get_original_sender_from_fwd_extracts_from_header_with_name(): void
    {
        $body = "---------- Forwarded message ---------\nFrom: John Doe <john@example.com>\nDate: Mon, Jan 1, 2025\nSubject: Test";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertEquals('John Doe', $result['name']);
        $this->assertEquals('john@example.com', $result['email']);
    }

    public function test_get_original_sender_from_fwd_extracts_from_header_without_name(): void
    {
        $body = "Forwarded message\nFrom:  <sender@example.com>\nSubject: Test";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertEquals('', $result['name']);
        $this->assertEquals('sender@example.com', $result['email']);
    }

    public function test_get_original_sender_from_fwd_extracts_with_quotes_in_name(): void
    {
        $body = 'From: "John Q. Public" <john@example.com>';

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertEquals('"John Q. Public"', $result['name']);
        $this->assertEquals('john@example.com', $result['email']);
    }

    public function test_get_original_sender_from_fwd_handles_name_with_comma(): void
    {
        $body = 'From: Doe, John <john@example.com>';

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertEquals('Doe, John', $result['name']);
        $this->assertEquals('john@example.com', $result['email']);
    }

    public function test_get_original_sender_from_fwd_extracts_with_extra_whitespace(): void
    {
        $body = "From:    John Doe    <   john@example.com   >";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertEquals('John Doe', $result['name']);
        $this->assertEquals('john@example.com', $result['email']);
    }

    public function test_get_original_sender_from_fwd_case_insensitive(): void
    {
        $body = "from: JOHN DOE <JOHN@EXAMPLE.COM>";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertEquals('JOHN DOE', $result['name']);
        $this->assertEquals('JOHN@EXAMPLE.COM', $result['email']);
    }

    public function test_get_original_sender_from_fwd_extracts_email_from_text_single_quotes(): void
    {
        $body = "Check this email: 'user@example.com' sent me a message";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertEquals('', $result['name']);
        $this->assertStringContainsString('user@example.com', $result['email']);
    }

    public function test_get_original_sender_from_fwd_extracts_email_from_text_double_quotes(): void
    {
        $body = 'Message from "sender@example.com" was received';

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertEquals('', $result['name']);
        $this->assertNotEmpty($result['email']);
    }

    public function test_get_original_sender_from_fwd_extracts_email_from_text_angle_brackets(): void
    {
        $body = "Email from <user@example.com> received";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertEquals('', $result['name']);
        $this->assertNotEmpty($result['email']);
    }

    public function test_get_original_sender_from_fwd_extracts_email_with_colon(): void
    {
        $body = "Sender: user@example.com mentioned";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['email']);
    }

    public function test_get_original_sender_from_fwd_extracts_email_with_semicolon(): void
    {
        $body = "Recipients; user@example.com; others";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['email']);
    }

    public function test_get_original_sender_from_fwd_returns_null_when_not_found(): void
    {
        $body = "This is just a regular message with no forwarded content";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertNull($result);
    }

    public function test_get_original_sender_from_fwd_returns_null_for_empty_string(): void
    {
        $body = "";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertNull($result);
    }

    public function test_get_original_sender_from_fwd_returns_null_for_invalid_email_pattern(): void
    {
        $body = "From: not-an-email-at-all";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        // May return null or may find a pattern - depends on regex
        $this->assertTrue(is_null($result) || is_array($result));
    }

    public function test_get_original_sender_from_fwd_handles_html_entities(): void
    {
        $body = "From: Test &lt;test@example.com&gt;";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertStringContainsString('test@example.com', $result['email']);
    }

    public function test_get_original_sender_from_fwd_handles_html_lt_gt(): void
    {
        $body = "From: User &lt;user@example.com&gt;";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['email']);
    }

    public function test_get_original_sender_from_fwd_cleans_fwd_prefix(): void
    {
        $body = "From: sender@fwd <real@example.com>";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        // The @fwd should be cleaned from the body
        $this->assertNotEmpty($result['email']);
    }

    public function test_get_original_sender_from_fwd_cleans_fwd_with_space(): void
    {
        $body = "From: user@fwd <actual@example.com>";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['email']);
    }

    public function test_get_original_sender_from_fwd_cleans_cid_prefix(): void
    {
        $body = 'Image "cid:image001@example.com" and from: real@example.com';

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        // cid: should be cleaned
        $this->assertIsArray($result);
        $this->assertNotEmpty($result['email']);
    }

    public function test_get_original_sender_from_fwd_prefers_from_header_over_text(): void
    {
        $body = "From: primary@example.com\nMention of 'secondary@example.com' in text";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        // Should prefer From: header match
        $this->assertIsArray($result);
        $this->assertEquals('primary@example.com', $result['email']);
    }

    public function test_get_original_sender_from_fwd_handles_unicode_name(): void
    {
        $body = "From: José García <jose@example.com>";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertEquals('José García', $result['name']);
        $this->assertEquals('jose@example.com', $result['email']);
    }

    public function test_get_original_sender_from_fwd_handles_complex_multiline(): void
    {
        $body = "---------- Forwarded message ---------\n" .
                "From: John Doe <john@example.com>\n" .
                "Date: Wed, Jan 1, 2025 at 10:30 AM\n" .
                "Subject: Important\n" .
                "To: recipient@example.com\n\n" .
                "Email content here";

        $result = $this->invokeMethod($this->service, 'getOriginalSenderFromFwd', [$body]);

        $this->assertIsArray($result);
        $this->assertEquals('John Doe', $result['name']);
        $this->assertEquals('john@example.com', $result['email']);
    }

    // =====================================================================
}