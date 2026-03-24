<?php

declare(strict_types=1);

use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

// uses(\Tests\TestCase::class, RefreshDatabase::class);

// ====================
// INDEX TESTS
// ====================

test('admin can view users list', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    User::factory()->create(['first_name' => 'John', 'email' => 'john@example.com']);
    User::factory()->create(['first_name' => 'Jane', 'email' => 'jane@example.com']);

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertViewIs('users.index')
        ->assertSee(['John', 'john@example.com', 'Jane', 'jane@example.com']);
});

test('non-admin cannot view users list', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($user)
        ->get(route('users.index'))
        ->assertForbidden();
});

test('unauthenticated user cannot view users list', function () {
    $this->get(route('users.index'))
        ->assertRedirect(route('login'));
});

// ====================
// CREATE & STORE TESTS
// ====================

test('admin can view create user page', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get(route('users.create'))
        ->assertOk()
        ->assertViewIs('users.create');
});

test('non-admin cannot view create user page', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($user)
        ->get(route('users.create'))
        ->assertForbidden();
});

test('admin can create user with valid data', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $email = 'newuser'.time().'@example.com';
    $password = 'password123';

    $response = $this->actingAs($admin)
        ->post(route('users.store'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
            'role' => User::ROLE_USER,
            'status' => User::STATUS_ACTIVE,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('users', [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => $email,
    ]);

    $user = User::where('email', $email)->first();
    expect(Hash::check($password, $user->password))->toBeTrue();
});

test('create user validation fails', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'first_name' => '', // Required
            'email' => 'invalid-email', // Invalid format
        ])
        ->assertSessionHasErrors(['first_name', 'email', 'password']);
});

test('create user email must be unique', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    User::factory()->create(['email' => 'existing@example.com']);

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'first_name' => 'Test',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertSessionHasErrors('email');
});

// ====================
// SHOW & EDIT TESTS
// ====================

test('admin can view any user profile', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('users.show', $user))
        ->assertOk()
        ->assertViewIs('users.show');
});

test('user can view own profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('users.show', $user))
        ->assertOk();
});

test('user cannot view other user profile', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $other = User::factory()->create();

    $this->actingAs($user)
        ->get(route('users.show', $other))
        ->assertForbidden();
});

test('admin can view edit user page', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('users.edit', $user))
        ->assertOk()
        ->assertViewIs('users.edit');
});

// ====================
// UPDATE TESTS
// ====================

test('admin can update user', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->put(route('users.update', $user), [
            'first_name' => 'Updated Name',
            'last_name' => $user->last_name,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
        ])
        ->assertRedirect();

    expect($user->fresh()->first_name)->toBe('Updated Name');
});

test('admin can deactivate user', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);

    $this->actingAs($admin)
        ->put(route('users.update', $user), [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'role' => $user->role,
            'status' => User::STATUS_INACTIVE,
        ])
        ->assertRedirect();

    expect($user->fresh()->status)->toBe(User::STATUS_INACTIVE);
});

test('update user with mailboxes', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($admin)
        ->put(route('users.update', $user), [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'mailboxes' => [$mailbox->id],
        ]);

    expect($user->mailboxes->contains($mailbox))->toBeTrue();
});

// ====================
// DELETE TESTS
// ====================

test('admin can delete other user', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->delete(route('users.destroy', $user))
        ->assertRedirect();

    expect($user->fresh()->status)->toBe(User::STATUS_DELETED);
});

test('admin cannot delete themselves', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $response = $this->actingAs($admin)
        ->delete(route('users.destroy', $admin));

    $response->assertForbidden();
});

// ====================
// EDGE CASES & ADDITIONAL TESTS
// ====================

test('show returns 404 for nonexistent user', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get(route('users.show', 99999))
        ->assertNotFound();
});

test('create user with admin role', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $email = 'admin2@example.com';

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'first_name' => 'Admin2',
            'last_name' => 'User2',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ])
        ->assertRedirect();

    expect(User::where('email', $email)->first()->role)->toBe(User::ROLE_ADMIN);
});

test('update cannot change email to existing', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $user1 = User::factory()->create(['email' => 'user1@example.com']);
    $user2 = User::factory()->create(['email' => 'user2@example.com']);

    $this->actingAs($admin)
        ->put(route('users.update', $user1), [
            'first_name' => $user1->first_name,
            'email' => 'user2@example.com', // Exists
            'role' => $user1->role,
            'status' => $user1->status,
        ])
        ->assertSessionHasErrors('email');
});

test('store user with special characters in name', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'first_name' => "O'Brien",
            'last_name' => 'José-María',
            'email' => 'special@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => User::ROLE_USER,
            'status' => User::STATUS_ACTIVE,
        ])
        ->assertRedirect();

    $user = User::where('email', 'special@example.com')->first();
    expect($user->first_name)->toBe("O'Brien");
    expect($user->last_name)->toBe('José-María');
});
