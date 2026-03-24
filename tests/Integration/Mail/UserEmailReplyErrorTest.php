<?php

declare(strict_types=1);

namespace Tests\Integration\Mail;

use App\Mail\UserEmailReplyError;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Tests\IntegrationTestCase;

class UserEmailReplyErrorTest extends IntegrationTestCase
{
    public function test_mailable_can_be_instantiated(): void
    {
        $user = \App\Models\User::factory()->create();
        $mailable = new UserEmailReplyError($user);

        $this->assertInstanceOf(UserEmailReplyError::class, $mailable);
    }

    public function test_envelope_contains_error_message(): void
    {
        $user = \App\Models\User::factory()->create();
        $mailable = new UserEmailReplyError($user);
        $envelope = $mailable->envelope();

        $this->assertStringContainsString('Unable to process', $envelope->subject);
    }

    public function test_envelope_subject_is_translated(): void
    {
        $user = \App\Models\User::factory()->create();
        $mailable = new UserEmailReplyError($user);
        $envelope = $mailable->envelope();

        // Subject should be translatable
        $this->assertNotEmpty($envelope->subject);
        $this->assertIsString($envelope->subject);
    }

    public function test_user_email_reply_error_content(): void
    {
        $user = User::factory()->create();
        $mailable = new UserEmailReplyError($user);

        $this->assertStringContainsString('emails.user.email_reply_error', $mailable->content()->view);
    }

    public function test_user_email_reply_error_sending(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $recipient = 'user@example.com';

        Mail::to($recipient)->send(new UserEmailReplyError($user));

        Mail::assertSent(UserEmailReplyError::class, function ($mail) use ($recipient) {
            return $mail->hasTo($recipient);
        });
    }

    public function test_mailable_is_queueable(): void
    {
        $user = \App\Models\User::factory()->create();
        $mailable = new UserEmailReplyError($user);

        $this->assertTrue(method_exists($mailable, 'onQueue'));
        $this->assertTrue(method_exists($mailable, 'onConnection'));
    }

    public function test_mailable_requires_user_parameter(): void
    {
        $user = \App\Models\User::factory()->create();
        $mailable = new UserEmailReplyError($user);

        $this->assertInstanceOf(UserEmailReplyError::class, $mailable);
        $this->assertEquals($user->id, $mailable->user->id);
    }

    public function test_user_email_reply_error_can_be_queued(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        Mail::to('user@example.com')->queue(new UserEmailReplyError($user));

        Mail::assertQueued(UserEmailReplyError::class);
    }

    public function test_content_has_view_defined(): void
    {
        $user = \App\Models\User::factory()->create();
        $mailable = new UserEmailReplyError($user);
        $content = $mailable->content();

        $this->assertNotNull($content->view);
        $this->assertEquals('emails.user.email_reply_error', $content->view);
    }
}
