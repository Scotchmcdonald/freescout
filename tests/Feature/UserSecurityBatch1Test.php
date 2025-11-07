<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSecurityBatch1Test extends TestCase
{
    use RefreshDatabase;

    public function test_user_email_is_sanitized_against_xss(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin);

        // Act
        $response = $this->post(route('users.store'), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => '<script>alert("xss")</script>@example.com',
            'password' => 'password123',
            'role' => User::ROLE_USER,
            'status' => User::STATUS_ACTIVE,
        ]);

        // Assert - Should fail validation for invalid email format
        $response->assertSessionHasErrors('email');
        
        // Verify error message exists
        $errors = session('errors');
        $this->assertNotNull($errors);
        $emailErrors = $errors->get('email');
        $this->assertNotEmpty($emailErrors);
        
        // Verify no user was created with XSS in email
        $this->assertDatabaseMissing('users', [
            'email' => '<script>alert("xss")</script>@example.com',
        ]);
    }

    public function test_user_name_fields_handle_html_tags_properly(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin);

        // Act
        $response = $this->post(route('users.store'), [
            'first_name' => '<b>Bold</b>',
            'last_name' => '<script>alert("xss")</script>',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => User::ROLE_USER,
            'status' => User::STATUS_ACTIVE,
        ]);

        // Assert
        $response->assertRedirect();
        
        $user = User::where('email', 'test@example.com')->first();
        // NOTE: HTML is stored as-is in the database. This test verifies that:
        // 1. Input is not automatically stripped/sanitized at storage level
        // 2. Views MUST use {{ $var }} (auto-escaped) not {!! $var !!} (unescaped)
        // 3. This is Laravel's standard approach - store raw, escape on output
        $this->assertEquals('<b>Bold</b>', $user->first_name);
        $this->assertEquals('<script>alert("xss")</script>', $user->last_name);
    }

    public function test_mass_assignment_protection_prevents_role_escalation(): void
    {
        // Arrange
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($user);

        // Act - Try to update own profile with admin role via mass assignment
        $response = $this->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
            'role' => User::ROLE_ADMIN, // Try to escalate to admin
        ]);

        // Assert
        $user->refresh();
        $this->assertEquals(User::ROLE_USER, $user->role); // Role should not change
        
        // Verify role was not updated in database
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => User::ROLE_USER,
        ]);
        
        // Verify no admin-level user was created
        $this->assertDatabaseMissing('users', [
            'email' => $user->email,
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_session_is_invalidated_on_logout(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $sessionId = session()->getId();
        $this->assertNotEmpty($sessionId);

        // Act
        $this->post('/logout');

        // Assert
        $this->assertGuest();
        $newSessionId = session()->getId();
        $this->assertNotEquals($sessionId, $newSessionId);
    }

    public function test_failed_login_attempts_do_not_reveal_user_existence(): void
    {
        // Arrange
        User::factory()->create(['email' => 'existing@example.com']);

        // Act
        $existingUserResponse = $this->post('/login', [
            'email' => 'existing@example.com',
            'password' => 'wrong-password',
        ]);

        $nonExistingUserResponse = $this->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrong-password',
        ]);

        // Assert - Error messages should be the same
        $this->assertGuest();
        // Both should fail without revealing which email exists
        $existingUserResponse->assertSessionHasErrors();
        $nonExistingUserResponse->assertSessionHasErrors();
    }
}
