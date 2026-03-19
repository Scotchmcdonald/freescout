<?php

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
