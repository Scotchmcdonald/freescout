<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationBatch1Test extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_login_page(): void
    {
        // Act
        $response = $this->get('/login');

        // Assert
        $response->assertStatus(200);
    }

    public function test_user_can_successfully_log_in_with_valid_credentials(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Act
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));
    }

    public function test_authenticated_user_can_log_out(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)->post('/logout');

        // Assert
        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_cannot_log_in_with_invalid_credentials(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'correct-password',
        ]);

        // Act
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        // Assert
        $this->assertGuest();
        $response->assertSessionHasErrors();
    }

    public function test_cannot_log_in_with_nonexistent_email(): void
    {
        // Act
        $response = $this->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password',
        ]);

        // Assert
        $this->assertGuest();
        $response->assertSessionHasErrors();
    }

    public function test_login_requires_email(): void
    {
        // Act
        $response = $this->post('/login', [
            'password' => 'password123',
        ]);

        // Assert
        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_login_requires_password(): void
    {
        // Act
        $response = $this->post('/login', [
            'email' => 'test@example.com',
        ]);

        // Assert
        $this->assertGuest();
        $response->assertSessionHasErrors('password');
    }
}
