<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\FeatureTestCase;

/**
 * Comprehensive tests for UserController
 * Following TESTING_GUIDE.md - using test_ prefix, FeatureTestCase base class
 */
class UserControllerFullTest extends FeatureTestCase
{
    // ===== INDEX TESTS =====

    public function test_index_requires_authentication(): void
    {
        $response = $this->get(route('users.index'));
        
        $response->assertRedirect(route('login'));
    }

    public function test_index_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        
        $response = $this->actingAs($user)->get(route('users.index'));
        
        $response->assertForbidden();
    }

    public function test_index_returns_view_for_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $response = $this->actingAs($admin)->get(route('users.index'));
        
        $response->assertOk();
        $response->assertViewIs('users.index');
        $response->assertViewHas('users');
    }

    public function test_index_displays_all_users(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        User::factory()->count(5)->create();
        
        $response = $this->actingAs($admin)->get(route('users.index'));
        
        $users = $response->viewData('users');
        $this->assertGreaterThanOrEqual(5, $users->count());
    }

    // ===== CREATE TESTS =====

    public function test_create_requires_authentication(): void
    {
        $response = $this->get(route('users.create'));
        
        $response->assertRedirect(route('login'));
    }

    public function test_create_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        
        $response = $this->actingAs($user)->get(route('users.create'));
        
        $response->assertForbidden();
    }

    public function test_create_returns_view_for_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $response = $this->actingAs($admin)->get(route('users.create'));
        
        $response->assertOk();
        $response->assertViewIs('users.create');
    }

    // ===== STORE TESTS =====

    public function test_store_requires_authentication(): void
    {
        $response = $this->post(route('users.store'), [
            'first_name' => 'John',
            'email' => 'john@example.com',
        ]);
        
        $response->assertRedirect(route('login'));
    }

    public function test_store_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        
        $response = $this->actingAs($user)->post(route('users.store'), [
            'first_name' => 'John',
            'email' => 'john@example.com',
        ]);
        
        $response->assertForbidden();
    }

    public function test_store_creates_user_with_valid_data(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $email = 'newuser' . time() . '@example.com';
        
        $response = $this->actingAs($admin)->post(route('users.store'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => $email,
            'password' => 'password123',
            'role' => User::ROLE_USER,
        ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => $email,
        ]);
    }

    public function test_store_validates_first_name_required(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $response = $this->actingAs($admin)->post(route('users.store'), [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        
        $response->assertSessionHasErrors('first_name');
    }

    public function test_store_validates_email_required(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $response = $this->actingAs($admin)->post(route('users.store'), [
            'first_name' => 'John',
            'password' => 'password',
        ]);
        
        $response->assertSessionHasErrors('email');
    }

    public function test_store_validates_email_format(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $response = $this->actingAs($admin)->post(route('users.store'), [
            'first_name' => 'John',
            'email' => 'invalid-email',
            'password' => 'password',
        ]);
        
        $response->assertSessionHasErrors('email');
    }

    public function test_store_validates_email_unique(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);
        
        $response = $this->actingAs($admin)->post(route('users.store'), [
            'first_name' => 'John',
            'email' => 'existing@example.com',
            'password' => 'password',
        ]);
        
        $response->assertSessionHasErrors('email');
    }

    public function test_store_validates_password_required(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $response = $this->actingAs($admin)->post(route('users.store'), [
            'first_name' => 'John',
            'email' => 'test@example.com',
        ]);
        
        $response->assertSessionHasErrors('password');
    }

    public function test_store_hashes_password(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $email = 'hashtest' . time() . '@example.com';
        $password = 'plaintextpassword';
        
        $response = $this->actingAs($admin)->post(route('users.store'), [
            'first_name' => 'Test',
            'email' => $email,
            'password' => $password,
            'role' => User::ROLE_USER,
        ]);
        
        $user = User::where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check($password, $user->password));
    }

    // ===== SHOW TESTS =====

    public function test_show_requires_authentication(): void
    {
        $user = User::factory()->create();
        
        $response = $this->get(route('users.show', $user));
        
        $response->assertRedirect(route('login'));
    }

    public function test_show_admin_can_view_any_user(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $otherUser = User::factory()->create();
        
        $response = $this->actingAs($admin)->get(route('users.show', $otherUser));
        
        $response->assertOk();
        $response->assertViewIs('users.show');
    }

    public function test_show_user_can_view_own_profile(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get(route('users.show', $user));
        
        $response->assertOk();
    }

    public function test_show_user_cannot_view_other_users(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $otherUser = User::factory()->create();
        
        $response = $this->actingAs($user)->get(route('users.show', $otherUser));
        
        $response->assertForbidden();
    }

    // ===== EDIT TESTS =====

    public function test_edit_requires_authentication(): void
    {
        $user = User::factory()->create();
        
        $response = $this->get(route('users.edit', $user));
        
        $response->assertRedirect(route('login'));
    }

    public function test_edit_admin_can_edit_any_user(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $otherUser = User::factory()->create();
        
        $response = $this->actingAs($admin)->get(route('users.edit', $otherUser));
        
        $response->assertOk();
        $response->assertViewIs('users.edit');
    }

    public function test_edit_user_can_edit_own_profile(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get(route('users.edit', $user));
        
        $response->assertOk();
    }

    // ===== UPDATE TESTS =====

    public function test_update_requires_authentication(): void
    {
        $user = User::factory()->create();
        
        $response = $this->put(route('users.update', $user), [
            'first_name' => 'Updated',
        ]);
        
        $response->assertRedirect(route('login'));
    }

    public function test_update_updates_user_with_valid_data(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['first_name' => 'Original']);
        
        $response = $this->actingAs($admin)->put(route('users.update', $user), [
            'first_name' => 'Updated',
            'last_name' => $user->last_name,
            'email' => $user->email,
        ]);
        
        $response->assertRedirect();
        $this->assertEquals('Updated', $user->fresh()->first_name);
    }

    public function test_update_user_can_update_own_profile(): void
    {
        $user = User::factory()->create(['first_name' => 'Original']);
        
        $response = $this->actingAs($user)->put(route('users.update', $user), [
            'first_name' => 'Updated',
            'last_name' => $user->last_name,
            'email' => $user->email,
        ]);
        
        $response->assertRedirect();
        $this->assertEquals('Updated', $user->fresh()->first_name);
    }

    public function test_update_validates_first_name_required(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create();
        
        $response = $this->actingAs($admin)->put(route('users.update', $user), [
            'first_name' => '',
            'email' => $user->email,
        ]);
        
        $response->assertSessionHasErrors('first_name');
    }

    // ===== DESTROY TESTS =====

    public function test_destroy_requires_authentication(): void
    {
        $user = User::factory()->create();
        
        $response = $this->delete(route('users.destroy', $user));
        
        $response->assertRedirect(route('login'));
    }

    public function test_destroy_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $otherUser = User::factory()->create();
        
        $response = $this->actingAs($user)->delete(route('users.destroy', $otherUser));
        
        $response->assertForbidden();
    }

    public function test_destroy_deletes_user_for_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create();
        
        $response = $this->actingAs($admin)->delete(route('users.destroy', $user));
        
        $response->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_destroy_admin_cannot_delete_self(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $response = $this->actingAs($admin)->delete(route('users.destroy', $admin));
        
        $response->assertForbidden();
    }

    // ===== EDGE CASES =====

    public function test_store_with_admin_role(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $email = 'adminuser' . time() . '@example.com';
        
        $response = $this->actingAs($admin)->post(route('users.store'), [
            'first_name' => 'Admin',
            'email' => $email,
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);
        
        $response->assertRedirect();
        $user = User::where('email', $email)->first();
        $this->assertEquals(User::ROLE_ADMIN, $user->role);
    }

    public function test_update_cannot_change_email_to_existing(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        
        $response = $this->actingAs($admin)->put(route('users.update', $user1), [
            'first_name' => $user1->first_name,
            'email' => 'user2@example.com',
        ]);
        
        $response->assertSessionHasErrors('email');
    }

    public function test_show_with_nonexistent_user_returns_404(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $response = $this->actingAs($admin)->get('/users/99999');
        
        $response->assertNotFound();
    }

    public function test_store_with_special_characters_in_name(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $email = 'special' . time() . '@example.com';
        
        $response = $this->actingAs($admin)->post(route('users.store'), [
            'first_name' => "O'Brien",
            'last_name' => 'José-María',
            'email' => $email,
            'password' => 'password',
            'role' => User::ROLE_USER,
        ]);
        
        $response->assertRedirect();
    }

    public function test_update_password_updates_hash(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create();
        $oldPassword = $user->password;
        
        $response = $this->actingAs($admin)->put(route('users.update', $user), [
            'first_name' => $user->first_name,
            'email' => $user->email,
            'password' => 'newpassword123',
        ]);
        
        $response->assertRedirect();
        $this->assertNotEquals($oldPassword, $user->fresh()->password);
    }
}
