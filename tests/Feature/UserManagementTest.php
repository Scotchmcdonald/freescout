<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
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

    public function test_admin_can_view_users_list(): void
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create();

        $response = $this->get(route('users.index'));

        $response->assertOk();
        $response->assertSee($user->email);
    }

    public function test_admin_can_create_user(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('users.store'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => User::ROLE_USER,
            'status' => User::STATUS_ACTIVE,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }

    public function test_admin_can_update_user(): void
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create();

        $response = $this->put(route('users.update', $user), [
            'first_name' => 'Updated',
            'last_name' => $user->last_name,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Updated',
        ]);
    }

    public function test_admin_can_deactivate_user(): void
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);

        $response = $this->put(route('users.update', $user), [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'role' => $user->role,
            'status' => User::STATUS_INACTIVE,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => User::STATUS_INACTIVE,
        ]);
    }

    public function test_non_admin_cannot_access_users_list(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($user);

        $response = $this->get(route('users.index'));

        $response->assertForbidden();
    }

    public function test_user_email_must_be_unique(): void
    {
        $this->actingAs($this->admin);

        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this->post(route('users.store'), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => User::ROLE_USER,
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_user_password_is_hashed(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('users.store'), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => User::ROLE_USER,
            'status' => User::STATUS_ACTIVE,
        ]);

        $user = User::where('email', 'test@example.com')->first();

        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_user_can_be_assigned_to_mailboxes(): void
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create();
        $mailbox = \App\Models\Mailbox::factory()->create();

        $response = $this->put(route('users.update', $user), [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'mailboxes' => [$mailbox->id],
        ]);

        $user->refresh();

        $this->assertTrue($user->mailboxes->contains($mailbox));
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $this->actingAs($this->admin);

        $response = $this->delete(route('users.destroy', $this->admin));

        $response->assertForbidden();
    }

    public function test_admin_can_delete_other_users(): void
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create();

        $response = $this->delete(route('users.destroy', $user));

        $response->assertRedirect();

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }
}
