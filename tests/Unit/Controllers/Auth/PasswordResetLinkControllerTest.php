<?php

namespace Tests\Unit\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetLinkControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_shows_request_form()
    {
        $response = $this->get(route('password.request'));
        $response->assertStatus(200);
        $response->assertViewIs('auth.forgot-password');
    }

    public function test_store_sends_reset_link_with_valid_email()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $response = $this->post(route('password.email'), [
            'email' => 'test@example.com',
        ]);

        $response->assertSessionHas('status');
    }

    public function test_store_fails_with_non_existent_email()
    {
        $response = $this->post(route('password.email'), [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_store_validates_email_required()
    {
        $response = $this->post(route('password.email'), []);

        $response->assertSessionHasErrors('email');
    }

    public function test_store_validates_email_format()
    {
        $response = $this->post(route('password.email'), [
            'email' => 'invalid-email',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_store_generates_token()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $this->post(route('password.email'), [
            'email' => 'test@example.com',
        ]);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_guest_can_request_password_reset()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $response = $this->post(route('password.email'), [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(302);
    }

    public function test_authenticated_user_can_request_password_reset()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $this->actingAs($user);

        $response = $this->post(route('password.email'), [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(302);
    }
}
