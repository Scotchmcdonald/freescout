<?php

declare(strict_types=1);

use App\Models\User;

test('login screen can be rendered', function () {
    $this->get('/login')
        ->assertStatus(200)
        ->assertViewIs('auth.login');
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', ['id' => $user->id]);
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect('/');

    $this->assertGuest();
});

test('users can not authenticate with non-existent email', function () {
    $this->post('/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'password',
    ])
        ->assertSessionHasErrors();

    $this->assertGuest();
});

test('user authentication journey supports login, logout, and re-login', function () {
    $user = User::factory()->create([
        'email' => 'journey-auth@example.com',
        'password' => bcrypt('journey-password'),
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'journey-password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => $user->email]);

    $this->post('/logout')->assertRedirect('/');
    $this->assertGuest();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'journey-password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('login requires email', function () {
    $this->post('/login', [
        'password' => 'password123',
    ])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('login requires password', function () {
    $this->post('/login', [
        'email' => 'test@example.com',
    ])
        ->assertSessionHasErrors('password');

    $this->assertGuest();
});
