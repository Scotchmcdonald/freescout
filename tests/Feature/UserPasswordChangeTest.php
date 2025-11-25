<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Tests for user password change functionality.
 *
 * @covers \App\Http\Controllers\UserController::passwordForm
 * @covers \App\Http\Controllers\UserController::updatePassword
 */
class UserPasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'password' => 'old_password_123',
        ]);
        $this->regularUser = User::factory()->create([
            'role' => User::ROLE_USER,
            'password' => 'user_password_123',
        ]);
    }

    // ====================
    // VIEW ACCESS TESTS
    // ====================

    public function test_user_can_access_own_password_form(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('users.password', $this->regularUser));

        $response->assertStatus(200);
        $response->assertViewIs('users.password');
    }

    public function test_admin_can_access_other_user_password_form(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('users.password', $this->regularUser));

        $response->assertStatus(200);
    }

    public function test_regular_user_cannot_access_other_user_password_form(): void
    {
        $otherUser = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($this->regularUser)
            ->get(route('users.password', $otherUser));

        $response->assertForbidden();
    }

    // ====================
    // OWN PASSWORD CHANGE TESTS
    // ====================

    public function test_user_can_change_own_password_with_correct_current_password(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->post(route('users.password.update', $this->regularUser), [
                'current_password' => 'user_password_123',
                'password' => 'new_secure_password_456',
                'password_confirmation' => 'new_secure_password_456',
            ]);

        $response->assertSessionHas('success');
        
        // Verify password was updated
        $this->regularUser->refresh();
        $this->assertTrue(Hash::check('new_secure_password_456', $this->regularUser->password));
    }

    public function test_user_cannot_change_password_with_incorrect_current_password(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->post(route('users.password.update', $this->regularUser), [
                'current_password' => 'wrong_password',
                'password' => 'new_secure_password_456',
                'password_confirmation' => 'new_secure_password_456',
            ]);

        $response->assertSessionHasErrors('current_password');
    }

    public function test_password_confirmation_must_match(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->post(route('users.password.update', $this->regularUser), [
                'current_password' => 'user_password_123',
                'password' => 'new_secure_password_456',
                'password_confirmation' => 'different_password',
            ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_password_must_be_minimum_8_characters(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->post(route('users.password.update', $this->regularUser), [
                'current_password' => 'user_password_123',
                'password' => 'short',
                'password_confirmation' => 'short',
            ]);

        $response->assertSessionHasErrors('password');
    }

    // ====================
    // ADMIN PASSWORD CHANGE TESTS
    // ====================

    public function test_admin_can_change_other_user_password_without_current(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('users.password.update', $this->regularUser), [
                'password' => 'admin_set_password_789',
                'password_confirmation' => 'admin_set_password_789',
            ]);

        $response->assertSessionHas('success');
        
        // Verify password was updated
        $this->regularUser->refresh();
        $this->assertTrue(Hash::check('admin_set_password_789', $this->regularUser->password));
    }

    public function test_admin_must_provide_current_password_for_own_account(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('users.password.update', $this->adminUser), [
                'password' => 'new_admin_password',
                'password_confirmation' => 'new_admin_password',
            ]);

        // Should fail validation for current_password
        $response->assertSessionHasErrors('current_password');
    }

    // ====================
    // AUTHORIZATION TESTS
    // ====================

    public function test_unauthenticated_user_is_redirected(): void
    {
        $response = $this->get(route('users.password', $this->regularUser));

        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_cannot_change_other_user_password(): void
    {
        $otherUser = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($this->regularUser)
            ->post(route('users.password.update', $otherUser), [
                'password' => 'hacked_password',
                'password_confirmation' => 'hacked_password',
            ]);

        $response->assertForbidden();
    }
}
