<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\Alert;
use Illuminate\Support\Facades\Mail;
use Tests\UnitTestCase;

class AlertTest extends UnitTestCase
{
    public function test_mailable_can_be_instantiated_with_text(): void
    {
        $mailable = new Alert('Test alert message');

        $this->assertInstanceOf(Alert::class, $mailable);
        $this->assertEquals('Test alert message', $mailable->text);
        $this->assertEquals('', $mailable->title);
    }

    public function test_mailable_can_be_instantiated_with_title(): void
    {
        $mailable = new Alert('Test message', 'Important Alert');

        $this->assertEquals('Test message', $mailable->text);
        $this->assertEquals('Important Alert', $mailable->title);
    }

    public function test_envelope_contains_correct_subject_with_title(): void
    {
        config(['app.name' => 'FreeScout', 'app.url' => 'https://example.com']);

        $mailable = new Alert('Test message', 'Security Alert');
        $envelope = $mailable->envelope();

        $this->assertStringContainsString('[FreeScout]', $envelope->subject);
        $this->assertStringContainsString('Security Alert', $envelope->subject);
        $this->assertStringContainsString('example.com', $envelope->subject);
    }

    public function test_envelope_uses_default_title_when_empty(): void
    {
        config(['app.name' => 'FreeScout', 'app.url' => 'https://example.com']);

        $mailable = new Alert('Test message');
        $envelope = $mailable->envelope();

        $this->assertStringContainsString('Alert', $envelope->subject);
        $this->assertStringContainsString('[FreeScout]', $envelope->subject);
    }

    public function test_content_uses_alert_view(): void
    {
        $mailable = new Alert('Test message');
        $content = $mailable->content();

        $this->assertEquals('emails.user.alert', $content->view);
    }

    public function test_mailable_can_be_sent(): void
    {
        Mail::fake();

        $recipient = 'admin@example.com';
        Mail::to($recipient)->send(new Alert('System alert', 'Warning'));

        Mail::assertSent(Alert::class, function ($mail) use ($recipient) {
            return $mail->hasTo($recipient) &&
                   $mail->text === 'System alert' &&
                   $mail->title === 'Warning';
        });
    }

    public function test_envelope_includes_domain_from_url(): void
    {
        config(['app.url' => 'https://helpdesk.example.org']);

        $mailable = new Alert('Test');
        $envelope = $mailable->envelope();

        $this->assertStringContainsString('helpdesk.example.org', $envelope->subject);
    }

    public function test_mailable_is_queueable(): void
    {
        $mailable = new Alert('Test');

        $this->assertTrue(method_exists($mailable, 'onQueue'));
        $this->assertTrue(method_exists($mailable, 'onConnection'));
    }
}
