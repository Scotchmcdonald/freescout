<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\PasswordChanged;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordChangedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function mailable_can_be_instantiated(): void
    {
        $user = User::factory()->create();
        $mailable = new PasswordChanged($user);

        $this->assertInstanceOf(PasswordChanged::class, $mailable);
        $this->assertEquals($user->id, $mailable->user->id);
    }

    #[Test]
    public function envelope_contains_password_updated_message(): void
    {
        config(['app.name' => 'FreeScout']);
        
        $user = User::factory()->create();
        $mailable = new PasswordChanged($user);
        $envelope = $mailable->envelope();

        $this->assertStringContainsString('Password Updated', $envelope->subject);
        $this->assertStringContainsString('FreeScout', $envelope->subject);
    }

    #[Test]
    public function envelope_uses_company_name_from_options(): void
    {
        config(['app.name' => 'Support Desk']);
        
        $user = User::factory()->create();
        $mailable = new PasswordChanged($user);
        $envelope = $mailable->envelope();

        $this->assertStringContainsString('Support Desk', $envelope->subject);
    }

    #[Test]
    public function content_uses_password_changed_views(): void
    {
        $user = User::factory()->create();
        $mailable = new PasswordChanged($user);
        $content = $mailable->content();

        $this->assertEquals('emails.user.password_changed', $content->view);
        $this->assertEquals('emails.user.password_changed_text', $content->text);
    }

    #[Test]
    public function mailable_can_be_sent(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'user@example.com']);
        Mail::to($user->email)->send(new PasswordChanged($user));

        Mail::assertSent(PasswordChanged::class, function ($mail) use ($user) {
            return $mail->user->id === $user->id;
        });
    }

    #[Test]
    public function mailable_is_queueable(): void
    {
        $user = User::factory()->create();
        $mailable = new PasswordChanged($user);
        
        $this->assertTrue(method_exists($mailable, 'onQueue'));
        $this->assertTrue(method_exists($mailable, 'onConnection'));
    }

    #[Test]
    public function user_property_is_accessible(): void
    {
        $user = User::factory()->create([
            'email' => 'testuser@example.com',
            'first_name' => 'Jane',
        ]);
        $mailable = new PasswordChanged($user);

        $this->assertEquals('testuser@example.com', $mailable->user->email);
        $this->assertEquals('Jane', $mailable->user->first_name);
    }

    #[Test]
    public function mailable_sends_to_correct_recipient(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'recipient@example.com']);
        Mail::to($user->email)->send(new PasswordChanged($user));

        Mail::assertSent(PasswordChanged::class, function ($mail) {
            return $mail->hasTo('recipient@example.com');
        });
    }
}
