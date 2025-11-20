<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\UserInvite;
use App\Models\Option;
use App\Models\User;
use Tests\UnitTestCase;

class UserInviteMailTest extends UnitTestCase
{
    public function test_envelope_sets_correct_subject_with_company_name(): void
    {
        $user = User::factory()->create(['id' => 1]);
        Option::set('company_name', 'Acme Corp');
        
        $mailable = new UserInvite($user);
        $envelope = $mailable->envelope();

        $this->assertStringContainsString('Acme Corp', $envelope->subject);
        $this->assertStringContainsString('Welcome', $envelope->subject);
    }

    public function test_envelope_uses_app_name_when_company_name_not_set(): void
    {
        config(['app.name' => 'FreeScout']);
        $user = User::factory()->create(['id' => 1]);
        
        $mailable = new UserInvite($user);
        $envelope = $mailable->envelope();

        $this->assertStringContainsString('FreeScout', $envelope->subject);
        $this->assertStringContainsString('Welcome', $envelope->subject);
    }

    public function test_content_uses_correct_view(): void
    {
        $user = User::factory()->create(['id' => 1]);
        
        $mailable = new UserInvite($user);
        $content = $mailable->content();

        $this->assertEquals('emails.user.user_invite', $content->view);
    }

    public function test_content_uses_correct_text_view(): void
    {
        $user = User::factory()->create(['id' => 1]);
        
        $mailable = new UserInvite($user);
        $content = $mailable->content();

        $this->assertEquals('emails.user.user_invite_text', $content->text);
    }

    public function test_mailable_stores_user_instance(): void
    {
        $user = User::factory()->create([
            'id' => 1,
            'first_name' => 'Jane',
            'email' => 'jane@example.com',
        ]);
        
        $mailable = new UserInvite($user);

        $this->assertInstanceOf(User::class, $mailable->user);
        $this->assertEquals('Jane', $mailable->user->first_name);
        $this->assertEquals('jane@example.com', $mailable->user->email);
    }

    public function test_mailable_accepts_optional_password(): void
    {
        $user = User::factory()->create(['id' => 1]);
        $password = 'secure-password-123';
        
        $mailable = new UserInvite($user, $password);

        $this->assertEquals($password, $mailable->password);
    }

    public function test_mailable_password_defaults_to_null(): void
    {
        $user = User::factory()->create(['id' => 1]);
        
        $mailable = new UserInvite($user);

        $this->assertNull($mailable->password);
    }

    public function test_mailable_can_be_constructed_without_password(): void
    {
        $user = User::factory()->create(['id' => 1]);
        
        $mailable = new UserInvite($user);

        $this->assertInstanceOf(UserInvite::class, $mailable);
        $this->assertNull($mailable->password);
    }

    public function test_mailable_can_be_constructed_with_password(): void
    {
        $user = User::factory()->create(['id' => 1]);
        
        $mailable = new UserInvite($user, 'temp-password');

        $this->assertInstanceOf(UserInvite::class, $mailable);
        $this->assertEquals('temp-password', $mailable->password);
    }
}
