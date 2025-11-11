<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementAdminBatch1Test extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_admin_user_can_create_a_new_user(): void
    {
        // Arrange
        $this->actingAs($this->admin);

        // Act
        $response = $this->post(route('users.store'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => User::ROLE_USER,
            'status' => User::STATUS_ACTIVE,
        ]);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'role' => User::ROLE_USER,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    public function test_admin_user_can_create_user_with_admin_role(): void
    {
        // Arrange
        $this->actingAs($this->admin);

        // Act
        $response = $this->post(route('users.store'), [
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_admin_user_can_update_an_existing_user(): void
    {
        // Arrange
        $this->actingAs($this->admin);
        $user = User::factory()->create([
            'first_name' => 'Original',
            'last_name' => 'Name',
        ]);

        // Act
        $response = $this->put(route('users.update', $user), [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
        ]);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ]);
    }

    public function test_admin_can_change_user_role(): void
    {
        // Arrange
        $this->actingAs($this->admin);
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        // Act
        $response = $this->put(route('users.update', $user), [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'role' => User::ROLE_ADMIN,
            'status' => $user->status,
        ]);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_admin_can_change_user_status(): void
    {
        // Arrange
        $this->actingAs($this->admin);
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);

        // Act
        $response = $this->put(route('users.update', $user), [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'role' => $user->role,
            'status' => User::STATUS_INACTIVE,
        ]);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => User::STATUS_INACTIVE,
        ]);
    }

    public function test_created_user_password_is_properly_hashed(): void
    {
        // Arrange
        $this->actingAs($this->admin);

        // Act
        $this->post(route('users.store'), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => User::ROLE_USER,
            'status' => User::STATUS_ACTIVE,
        ]);

        // Assert
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotEquals('password123', $user->password);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_non_admin_user_cannot_access_user_management_routes(): void
    {
        // Arrange
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($user);

        // Act
        $response = $this->get(route('users.index'));

        // Assert
        $response->assertStatus(403);
    }

    public function test_non_admin_user_cannot_create_users(): void
    {
        // Arrange
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($user);

        // Act
        $response = $this->post(route('users.store'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'role' => User::ROLE_USER,
            'status' => User::STATUS_ACTIVE,
        ]);

        // Assert
        $response->assertStatus(403);
        $this->assertDatabaseMissing('users', [
            'email' => 'john@example.com',
        ]);
    }

    public function test_non_admin_user_cannot_update_other_users(): void
    {
        // Arrange
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $otherUser = User::factory()->create();
        $this->actingAs($user);

        // Act
        $response = $this->put(route('users.update', $otherUser), [
            'first_name' => 'Updated',
            'last_name' => $otherUser->last_name,
            'email' => $otherUser->email,
            'role' => $otherUser->role,
            'status' => $otherUser->status,
        ]);

        // Assert
        $response->assertStatus(403);
    }

    public function test_admin_can_assign_user_to_mailboxes(): void
    {
        // Arrange
        $this->actingAs($this->admin);
        $user = User::factory()->create();
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();

        // Act
        $response = $this->put(route('users.update', $user), [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'mailboxes' => [$mailbox1->id, $mailbox2->id],
        ]);

        // Assert
        $user->refresh();
        $this->assertTrue($user->mailboxes->contains($mailbox1));
        $this->assertTrue($user->mailboxes->contains($mailbox2));
        $this->assertEquals(2, $user->mailboxes->count());
    }
}
