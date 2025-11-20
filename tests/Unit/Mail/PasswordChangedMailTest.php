<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\PasswordChanged;
use App\Models\Option;
use App\Models\User;
use Tests\UnitTestCase;

class PasswordChangedMailTest extends UnitTestCase
{
    public function test_envelope_sets_correct_subject_with_company_name(): void
    {
        $user = User::factory()->create(['id' => 1]);
        Option::set('company_name', 'Acme Corp');
        
        $mailable = new PasswordChanged($user);
        $envelope = $mailable->envelope();

        $this->assertStringContainsString('Acme Corp', $envelope->subject);
        $this->assertStringContainsString('Password Updated', $envelope->subject);
    }

    public function test_envelope_uses_app_name_when_company_name_not_set(): void
    {
        config(['app.name' => 'FreeScout']);
        $user = User::factory()->create(['id' => 1]);
        
        $mailable = new PasswordChanged($user);
        $envelope = $mailable->envelope();

        $this->assertStringContainsString('FreeScout', $envelope->subject);
        $this->assertStringContainsString('Password Updated', $envelope->subject);
    }

    public function test_content_uses_correct_view(): void
    {
        $user = User::factory()->create(['id' => 1]);
        
        $mailable = new PasswordChanged($user);
        $content = $mailable->content();

        $this->assertEquals('emails.user.password_changed', $content->view);
    }

    public function test_content_uses_correct_text_view(): void
    {
        $user = User::factory()->create(['id' => 1]);
        
        $mailable = new PasswordChanged($user);
        $content = $mailable->content();

        $this->assertEquals('emails.user.password_changed_text', $content->text);
    }

    public function test_mailable_stores_user_instance(): void
    {
        $user = User::factory()->create([
            'id' => 1,
            'first_name' => 'John',
            'email' => 'john@example.com',
        ]);
        
        $mailable = new PasswordChanged($user);

        $this->assertInstanceOf(User::class, $mailable->user);
        $this->assertEquals('John', $mailable->user->first_name);
        $this->assertEquals('john@example.com', $mailable->user->email);
    }

    public function test_mailable_can_be_constructed(): void
    {
        $user = User::factory()->create(['id' => 1]);
        
        $mailable = new PasswordChanged($user);

        $this->assertInstanceOf(PasswordChanged::class, $mailable);
    }
}
