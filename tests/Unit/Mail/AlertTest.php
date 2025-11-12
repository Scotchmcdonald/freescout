<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\Alert;
use App\Mail\PasswordChanged;
use App\Mail\UserEmailReplyError;
use App\Mail\UserInvite;
use App\Mail\UserNotification;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AlertTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function mailable_can_be_instantiated_with_text(): void
    {
        $mailable = new Alert('Test alert message');

        $this->assertInstanceOf(Alert::class, $mailable);
        $this->assertEquals('Test alert message', $mailable->text);
        $this->assertEquals('', $mailable->title);
    }

    #[Test]
    public function mailable_can_be_instantiated_with_title(): void
    {
        $mailable = new Alert('Test message', 'Important Alert');

        $this->assertEquals('Test message', $mailable->text);
        $this->assertEquals('Important Alert', $mailable->title);
    }

    #[Test]
    public function envelope_contains_correct_subject_with_title(): void
    {
        config(['app.name' => 'FreeScout', 'app.url' => 'https://example.com']);
        
        $mailable = new Alert('Test message', 'Security Alert');
        $envelope = $mailable->envelope();

        $this->assertStringContainsString('[FreeScout]', $envelope->subject);
        $this->assertStringContainsString('Security Alert', $envelope->subject);
        $this->assertStringContainsString('example.com', $envelope->subject);
    }

    #[Test]
    public function envelope_uses_default_title_when_empty(): void
    {
        config(['app.name' => 'FreeScout', 'app.url' => 'https://example.com']);
        
        $mailable = new Alert('Test message');
        $envelope = $mailable->envelope();

        $this->assertStringContainsString('Alert', $envelope->subject);
        $this->assertStringContainsString('[FreeScout]', $envelope->subject);
    }

    #[Test]
    public function content_uses_alert_view(): void
    {
        $mailable = new Alert('Test message');
        $content = $mailable->content();

        $this->assertEquals('emails.user.alert', $content->view);
    }

    #[Test]
    public function mailable_can_be_sent(): void
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

    #[Test]
    public function envelope_includes_domain_from_url(): void
    {
        config(['app.url' => 'https://helpdesk.example.org']);
        
        $mailable = new Alert('Test');
        $envelope = $mailable->envelope();

        $this->assertStringContainsString('helpdesk.example.org', $envelope->subject);
    }

    #[Test]
    public function mailable_is_queueable(): void
    {
        $mailable = new Alert('Test');
        
        $this->assertTrue(method_exists($mailable, 'onQueue'));
        $this->assertTrue(method_exists($mailable, 'onConnection'));
    }
}
