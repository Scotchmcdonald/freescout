<?php

declare(strict_types=1);

namespace Tests\Unit\Misc;

use App\Misc\MailHelper;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Tests\PureUnitTestCase;

class MailHelperTest extends PureUnitTestCase
{
    private ?Container $previousContainer = null;

    private mixed $previousFacadeApplication = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousContainer = Container::getInstance();
        $this->previousFacadeApplication = Facade::getFacadeApplication();

        $app = new Application(getcwd());
        $app->instance('config', new Repository([
            'app' => ['key' => 'base64:dGVzdGtleXRlc3RrZXl0ZXN0a2V5dGVzdGtleXQ='],
        ]));

        Container::setInstance($app);
        Facade::setFacadeApplication($app);
    }

    protected function tearDown(): void
    {
        Facade::setFacadeApplication($this->previousFacadeApplication);
        Container::setInstance($this->previousContainer);

        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // isAutoResponder — cover remaining delivered-to branch
    // -------------------------------------------------------------------------

    public function test_is_auto_responder_returns_true_for_delivered_to_autoresponder(): void
    {
        $this->assertTrue(MailHelper::isAutoResponder("Delivered-To: autoresponder"));
    }

    public function test_is_auto_responder_returns_true_for_x_autorespond_header(): void
    {
        $this->assertTrue(MailHelper::isAutoResponder("X-Autorespond: yes"));
    }

    public function test_is_auto_responder_returns_true_for_x_autoresponder_header(): void
    {
        $this->assertTrue(MailHelper::isAutoResponder("X-Autoresponder: yes"));
    }

    public function test_is_auto_responder_returns_true_for_precedence_junk(): void
    {
        $this->assertTrue(MailHelper::isAutoResponder("Precedence: junk"));
    }

    public function test_is_auto_responder_returns_true_for_precedence_list(): void
    {
        $this->assertTrue(MailHelper::isAutoResponder("Precedence: list"));
    }

    public function test_is_auto_responder_returns_true_for_x_precedence_bulk(): void
    {
        $this->assertTrue(MailHelper::isAutoResponder("X-Precedence: bulk"));
    }

    public function test_is_auto_responder_skips_malformed_header_without_colon(): void
    {
        // No colon means explode(':') gives only 1 part → count($parts) !== 2 → skipped
        $this->assertFalse(MailHelper::isAutoResponder("AutoSubmitted yes"));
    }

    public function test_is_auto_responder_returns_false_for_unmatched_delivered_to_value(): void
    {
        // delivered-to header exists but value is not 'autoresponder'
        $this->assertFalse(MailHelper::isAutoResponder("Delivered-To: user@example.com"));
    }

    // -------------------------------------------------------------------------
    // generateMessageId
    // -------------------------------------------------------------------------

    public function test_generate_message_id_contains_at_sign_and_domain(): void
    {
        $msgId = MailHelper::generateMessageId('user@example.com');

        $this->assertStringStartsWith('fs-', $msgId);
        $this->assertStringContainsString('@example.com', $msgId);
    }

    public function test_generate_message_id_uses_md5_of_body_when_provided(): void
    {
        $body = 'Hello world';
        $msgId = MailHelper::generateMessageId('user@example.com', $body);

        $this->assertStringContainsString(md5($body), $msgId);
    }

    // -------------------------------------------------------------------------
    // getMessageIdHash — deterministic given same threadId + app.key
    // -------------------------------------------------------------------------

    public function test_get_message_id_hash_returns_32_char_md5_hex_string(): void
    {
        $hash = MailHelper::getMessageIdHash(42);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $hash);
    }

    public function test_get_message_id_hash_is_deterministic_for_same_thread_id(): void
    {
        $this->assertSame(MailHelper::getMessageIdHash(7), MailHelper::getMessageIdHash(7));
    }

    public function test_get_message_id_hash_differs_for_different_thread_ids(): void
    {
        $this->assertNotSame(MailHelper::getMessageIdHash(1), MailHelper::getMessageIdHash(2));
    }

    // -------------------------------------------------------------------------
    // hasVars
    // -------------------------------------------------------------------------

    public function test_has_vars_returns_true_when_text_contains_merge_code(): void
    {
        $this->assertTrue(MailHelper::hasVars('Hello {%customer.fullName%}'));
    }

    public function test_has_vars_returns_false_when_text_has_no_merge_code(): void
    {
        $this->assertFalse(MailHelper::hasVars('Hello world'));
    }

    public function test_has_vars_returns_false_for_null(): void
    {
        $this->assertFalse(MailHelper::hasVars(null));
    }

    public function test_has_vars_returns_false_for_empty_string(): void
    {
        $this->assertFalse(MailHelper::hasVars(''));
    }

    // -------------------------------------------------------------------------
    // parseEmail
    // -------------------------------------------------------------------------

    public function test_parse_email_extracts_from_angle_bracket_format(): void
    {
        $this->assertSame('john@example.com', MailHelper::parseEmail('John Doe <john@example.com>'));
    }

    public function test_parse_email_handles_angle_bracket_without_name(): void
    {
        $this->assertSame('john@example.com', MailHelper::parseEmail('<john@example.com>'));
    }

    public function test_parse_email_returns_trimmed_plain_email_as_is(): void
    {
        $this->assertSame('john@example.com', MailHelper::parseEmail('  john@example.com  '));
    }

    // -------------------------------------------------------------------------
    // sanitizeEmail (HTML sanitizer)
    // -------------------------------------------------------------------------

    public function test_sanitize_email_html_removes_script_tags(): void
    {
        $result = MailHelper::sanitizeEmail('<p>Hello</p><script>alert(1)</script>');

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('alert(1)', $result);
        $this->assertStringContainsString('<p>Hello</p>', $result);
    }

    public function test_sanitize_email_html_removes_iframe_tags(): void
    {
        $result = MailHelper::sanitizeEmail('<iframe src="evil.com"></iframe><p>Clean</p>');

        $this->assertStringNotContainsString('<iframe', $result);
        $this->assertStringContainsString('<p>Clean</p>', $result);
    }

    public function test_sanitize_email_html_removes_on_event_handlers(): void
    {
        $result = MailHelper::sanitizeEmail('<a href="x" onclick="evil()">click</a>');

        $this->assertStringNotContainsString('onclick', $result);
    }

    public function test_sanitize_email_html_leaves_safe_content_intact(): void
    {
        $html = '<p>Hello <strong>world</strong></p>';
        $this->assertSame($html, MailHelper::sanitizeEmail($html));
    }

    // -------------------------------------------------------------------------
    // formatEmail
    // -------------------------------------------------------------------------

    public function test_format_email_with_name_produces_rfc_format(): void
    {
        $this->assertSame('Jane Doe <jane@example.com>', MailHelper::formatEmail('jane@example.com', 'Jane Doe'));
    }

    public function test_format_email_without_name_returns_email_only(): void
    {
        $this->assertSame('jane@example.com', MailHelper::formatEmail('jane@example.com'));
    }

    public function test_format_email_with_empty_name_returns_email_only(): void
    {
        $this->assertSame('jane@example.com', MailHelper::formatEmail('jane@example.com', ''));
    }

    // -------------------------------------------------------------------------
    // extractReply
    // -------------------------------------------------------------------------

    public function test_extract_reply_returns_full_text_when_no_separator(): void
    {
        $body = "Hello, thank you for your message.";
        $this->assertSame($body, MailHelper::extractReply($body));
    }

    public function test_extract_reply_strips_content_after_forwarded_message_header(): void
    {
        $body = "My reply\n\n---- Original Message ----\nSent: Mon, 1 Jan";
        $result = MailHelper::extractReply($body);

        $this->assertStringContainsString('My reply', $result);
        $this->assertStringNotContainsString('Original Message', $result);
    }

    public function test_extract_reply_handles_empty_body(): void
    {
        $this->assertSame('', MailHelper::extractReply(''));
    }
}
