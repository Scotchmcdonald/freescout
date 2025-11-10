<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_notification_can_be_sent(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'test@example.com',
        ]);

        Notification::fake();

        $response = $this->actingAs($user)->post('/email/verification-notification');

        $response->assertStatus(302);
        $response->assertRedirect();
        $response->assertSessionHas('status', 'verification-link-sent');
    }

    public function test_email_verification_notification_not_sent_if_already_verified(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/email/verification-notification');

        $response->assertStatus(302);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_guest_cannot_request_verification_notification(): void
    {
        $response = $this->post('/email/verification-notification');

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }
}
