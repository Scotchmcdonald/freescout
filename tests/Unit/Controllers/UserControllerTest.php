<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Http\Controllers\UserController;
use App\Models\User;
use Tests\UnitTestCase;

class UserControllerTest extends UnitTestCase
{

    public function test_controller_can_be_instantiated(): void
    {
        $controller = new UserController;

        $this->assertInstanceOf(UserController::class, $controller);
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->get(route('users.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_index_accessible_by_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertStatus(200);
    }

    public function test_index_forbidden_for_regular_users(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($user)->get(route('users.index'));

        $response->assertStatus(403);
    }

    public function test_create_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($user)->get(route('users.create'));

        $response->assertStatus(403);
    }

    public function test_create_accessible_by_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('users.create'));

        $response->assertStatus(200);
    }

    public function test_store_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($user)->post(route('users.store'), [
            'first_name' => 'New',
            'last_name' => 'User',
            'email' => 'newuser@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(403);
    }

    public function test_store_creates_user_with_valid_data(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'testuser@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'role' => User::ROLE_USER,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseHas('users', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'testuser@example.com',
        ]);
    }

    public function test_show_requires_authentication(): void
    {
        $user = User::factory()->create();

        $response = $this->get(route('users.show', $user));

        $response->assertRedirect(route('login'));
    }

    public function test_show_accessible_by_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->get(route('users.show', $user));

        $response->assertStatus(200);
    }

    public function test_edit_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $targetUser = User::factory()->create();

        $response = $this->actingAs($user)->get(route('users.edit', $targetUser));

        $response->assertStatus(403);
    }

    public function test_update_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $targetUser = User::factory()->create();

        $response = $this->actingAs($user)->put(route('users.update', $targetUser), [
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ]);

        $response->assertStatus(403);
    }

    public function test_update_modifies_user_data(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['first_name' => 'Original', 'last_name' => 'Name']);

        $response = $this->actingAs($admin)->put(route('users.update', $user), [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ]);
    }

    public function test_destroy_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $targetUser = User::factory()->create();

        $response = $this->actingAs($user)->delete(route('users.destroy', $targetUser));

        $response->assertStatus(403);
    }

    public function test_destroy_deletes_user(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->delete(route('users.destroy', $user));

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
