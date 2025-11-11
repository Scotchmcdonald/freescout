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
}
