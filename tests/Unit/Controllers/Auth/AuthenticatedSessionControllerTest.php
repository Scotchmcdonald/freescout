<?php

namespace Tests\Unit\Controllers\Auth;

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticatedSessionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_shows_login_form()
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    public function test_store_authenticates_user_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    public function test_store_fails_with_invalid_credentials()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors();
    }

    public function test_store_validates_required_fields()
    {
        $response = $this->post(route('login'), []);

        $response->assertSessionHasErrors(['email', 'password']);
    }

    public function test_destroy_logs_out_user()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_destroy_clears_session()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        session(['test_key' => 'test_value']);

        $this->post(route('logout'));

        $this->assertNull(session('test_key'));
    }

    public function test_destroy_invalidates_remember_token()
    {
        $user = User::factory()->create(['remember_token' => 'old_token']);
        $this->actingAs($user);

        $this->post(route('logout'));

        $user->refresh();
        $this->assertNotEquals('old_token', $user->remember_token);
    }

    public function test_authenticated_users_redirected_from_login()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('login'));
        $response->assertRedirect(route('dashboard'));
    }

    public function test_login_with_remember_me()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
            'remember' => true,
        ]);

        $user->refresh();
        $this->assertNotNull($user->remember_token);
    }

    public function test_failed_login_preserves_email()
    {
        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasInput('email', 'test@example.com');
    }

    public function test_post_logout_redirect()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('logout'));
        $response->assertRedirect('/');
    }
}
