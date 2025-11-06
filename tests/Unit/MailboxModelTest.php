<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Mailbox;
use Tests\TestCase;

class MailboxModelTest extends TestCase
{
    public function test_mailbox_has_name(): void
    {
        $mailbox = new Mailbox(['name' => 'Support']);
        $this->assertEquals('Support', $mailbox->name);
    }

    public function test_mailbox_has_email(): void
    {
        $mailbox = new Mailbox(['email' => 'support@example.com']);
        $this->assertEquals('support@example.com', $mailbox->email);
    }

    public function test_mailbox_has_from_name(): void
    {
        $mailbox = new Mailbox(['from_name' => 'Support Team']);
        $this->assertEquals('Support Team', $mailbox->from_name);
    }

    public function test_mailbox_has_signature(): void
    {
        $mailbox = new Mailbox(['signature' => 'Best regards, Support Team']);
        $this->assertEquals('Best regards, Support Team', $mailbox->signature);
    }

    public function test_mailbox_has_auto_reply_enabled(): void
    {
        $mailbox = new Mailbox(['auto_reply_enabled' => true]);
        $this->assertTrue($mailbox->auto_reply_enabled);
    }

    public function test_mailbox_has_auto_reply_subject(): void
    {
        $mailbox = new Mailbox(['auto_reply_subject' => 'Thanks for contacting us']);
        $this->assertEquals('Thanks for contacting us', $mailbox->auto_reply_subject);
    }

    public function test_mailbox_has_auto_reply_message(): void
    {
        $mailbox = new Mailbox(['auto_reply_message' => 'We will respond shortly']);
        $this->assertEquals('We will respond shortly', $mailbox->auto_reply_message);
    }

    public function test_mailbox_has_in_server(): void
    {
        $mailbox = new Mailbox(['in_server' => 'imap.example.com']);
        $this->assertEquals('imap.example.com', $mailbox->in_server);
    }

    public function test_mailbox_has_in_port(): void
    {
        $mailbox = new Mailbox(['in_port' => 993]);
        $this->assertEquals(993, $mailbox->in_port);
    }

    public function test_mailbox_has_out_server(): void
    {
        $mailbox = new Mailbox(['out_server' => 'smtp.example.com']);
        $this->assertEquals('smtp.example.com', $mailbox->out_server);
    }

    public function test_mailbox_has_out_port(): void
    {
        $mailbox = new Mailbox(['out_port' => 587]);
        $this->assertEquals(587, $mailbox->out_port);
    }

    public function test_mailbox_has_status(): void
    {
        $mailbox = new Mailbox(['status' => 1]);
        $this->assertEquals(1, $mailbox->status);
    }
}
