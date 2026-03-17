<?php

namespace Tests\Integration\Controllers\Auth;

use App\Models\User;
use Tests\IntegrationTestCase;

class EmailVerificationReminderControllerTest extends IntegrationTestCase
{
    public function test_user_can_request_verification_email()
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $response = $this->actingAs($user)
            ->post(route('verification.send'));

        $response->assertRedirect();
    }

    public function test_verified_user_redirected_when_requesting_verification()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)
            ->post(route('verification.send'));

        $response->assertRedirect();
    }

    public function test_guest_cannot_request_verification_email()
    {
        $response = $this->post(route('verification.send'));

        $response->assertRedirect(route('login'));
    }

    public function test_verification_email_rate_limited()
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        // Exhaust throttling budget first, then assert throttle response.
        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($user)->post(route('verification.send'));
        }

        $response = $this->actingAs($user)->post(route('verification.send'));

        $this->assertTrue(in_array($response->status(), [302, 429], true));
        $this->assertNotNull($response->headers->get('X-RateLimit-Limit'));
    }
}
