<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\UserInvite;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\UnitTestCase;

class UserInviteTest extends UnitTestCase
{

    #[Test]
    public function mailable_can_be_instantiated(): void
    {
        $user = User::factory()->create();
        $mailable = new UserInvite($user);

        $this->assertInstanceOf(UserInvite::class, $mailable);
        $this->assertEquals($user->id, $mailable->user->id);
    }

    #[Test]
    public function envelope_contains_welcome_message(): void
    {
        config(['app.name' => 'FreeScout']);
        
        $user = User::factory()->create();
        $mailable = new UserInvite($user);
        $envelope = $mailable->envelope();

        $this->assertStringContainsString('Welcome', $envelope->subject);
    }

    #[Test]
    public function envelope_uses_company_name_from_options(): void
    {
        // This would test Option::get('company_name') if available
        config(['app.name' => 'Test Company']);
        
        $user = User::factory()->create();
        $mailable = new UserInvite($user);
        $envelope = $mailable->envelope();

        $this->assertStringContainsString('Test Company', $envelope->subject);
    }

    #[Test]
    public function content_uses_user_invite_views(): void
    {
        $user = User::factory()->create();
        $mailable = new UserInvite($user);
        $content = $mailable->content();

        $this->assertEquals('emails.user.user_invite', $content->view);
        $this->assertEquals('emails.user.user_invite_text', $content->text);
    }

    #[Test]
    public function mailable_can_be_sent(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'newuser@example.com']);
        Mail::to($user->email)->send(new UserInvite($user));

        Mail::assertSent(UserInvite::class, function ($mail) {
            return $mail->user instanceof User;
        });
    }

    #[Test]
    public function mailable_is_queueable(): void
    {
        $user = User::factory()->create();
        $mailable = new UserInvite($user);
        
        $this->assertTrue(method_exists($mailable, 'onQueue'));
        $this->assertTrue(method_exists($mailable, 'onConnection'));
    }

    #[Test]
    public function user_property_is_accessible(): void
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $mailable = new UserInvite($user);

        $this->assertEquals('John', $mailable->user->first_name);
        $this->assertEquals('Doe', $mailable->user->last_name);
    }
}
