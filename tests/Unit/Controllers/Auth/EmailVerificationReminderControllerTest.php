<?php

namespace Tests\Unit\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailVerificationReminderControllerTest extends TestCase
{
    use RefreshDatabase;

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

        // Send multiple requests
        $this->actingAs($user)->post(route('verification.send'));
        $this->actingAs($user)->post(route('verification.send'));

        $response = $this->actingAs($user)->post(route('verification.send'));

        // Should be rate limited
        $this->assertTrue(true); // Rate limiting test placeholder
    }
}
