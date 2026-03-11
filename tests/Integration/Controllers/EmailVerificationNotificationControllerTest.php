<?php

declare(strict_types=1);

namespace Tests\Integration\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Tests\IntegrationTestCase;

class EmailVerificationNotificationControllerTest extends IntegrationTestCase
{
    public function test_store_sends_verification_email_to_unverified_user(): void
    {
        Notification::fake();
        
        $user = User::factory()->create(['email_verified_at' => null]);

        $response = $this->actingAs($user)->post('/email/verification-notification');

        $response->assertStatus(302);
        $response->assertSessionHas('status', 'verification-link-sent');
    }

    public function test_store_redirects_to_dashboard_if_already_verified(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->post('/email/verification-notification');

        $response->assertRedirect(route('dashboard'));
    }

    public function test_store_redirects_back_with_status_message(): void
    {
        Notification::fake();
        
        $user = User::factory()->create(['email_verified_at' => null]);

        $response = $this->actingAs($user)
            ->from('/email/verify')
            ->post('/email/verification-notification');

        $response->assertRedirect('/email/verify');
        $response->assertSessionHas('status', 'verification-link-sent');
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->post('/email/verification-notification');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function test_store_calls_send_email_verification_notification_method(): void
    {
        $user = \Mockery::mock(User::class)->makePartial();
        $user->email_verified_at = null;
        $user->shouldReceive('hasVerifiedEmail')->andReturn(false);
        $user->shouldReceive('sendEmailVerificationNotification')->once();
        
        $this->be($user);

        $response = $this->post('/email/verification-notification');

        $response->assertSessionHas('status', 'verification-link-sent');
    }

    protected function tearDown(): void
    {
        try {
            \Mockery::close();
        } finally {
            parent::tearDown();
        }
    }
}
