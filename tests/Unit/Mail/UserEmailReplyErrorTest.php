<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\UserEmailReplyError;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\UnitTestCase;

class UserEmailReplyErrorTest extends UnitTestCase
{

    #[Test]
    public function mailable_can_be_instantiated(): void
    {
        $mailable = new UserEmailReplyError();

        $this->assertInstanceOf(UserEmailReplyError::class, $mailable);
    }

    #[Test]
    public function envelope_contains_error_message(): void
    {
        $mailable = new UserEmailReplyError();
        $envelope = $mailable->envelope();

        $this->assertStringContainsString('Unable to process', $envelope->subject);
    }

    #[Test]
    public function envelope_subject_is_translated(): void
    {
        $mailable = new UserEmailReplyError();
        $envelope = $mailable->envelope();

        // Subject should be translatable
        $this->assertNotEmpty($envelope->subject);
        $this->assertIsString($envelope->subject);
    }

    #[Test]
    public function content_uses_email_reply_error_view(): void
    {
        $mailable = new UserEmailReplyError();
        $content = $mailable->content();

        $this->assertEquals('emails.user.email_reply_error', $content->view);
    }

    #[Test]
    public function mailable_can_be_sent(): void
    {
        Mail::fake();

        $recipient = 'user@example.com';
        Mail::to($recipient)->send(new UserEmailReplyError());

        Mail::assertSent(UserEmailReplyError::class, function ($mail) use ($recipient) {
            return $mail->hasTo($recipient);
        });
    }

    #[Test]
    public function mailable_is_queueable(): void
    {
        $mailable = new UserEmailReplyError();
        
        $this->assertTrue(method_exists($mailable, 'onQueue'));
        $this->assertTrue(method_exists($mailable, 'onConnection'));
    }

    #[Test]
    public function mailable_has_no_required_parameters(): void
    {
        // Should be able to instantiate without any parameters
        $mailable = new UserEmailReplyError();
        
        $this->assertInstanceOf(UserEmailReplyError::class, $mailable);
    }

    #[Test]
    public function mailable_can_be_queued(): void
    {
        Mail::fake();

        Mail::to('user@example.com')->queue(new UserEmailReplyError());

        Mail::assertQueued(UserEmailReplyError::class);
    }

    #[Test]
    public function content_has_view_defined(): void
    {
        $mailable = new UserEmailReplyError();
        $content = $mailable->content();

        $this->assertNotNull($content->view);
        $this->assertEquals('emails.user.email_reply_error', $content->view);
    }
}
