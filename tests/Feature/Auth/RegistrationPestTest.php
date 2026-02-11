<?php

use App\Models\User;

test('registration screen can be rendered', function () {
    $this->get('/register')
        ->assertStatus(200)
        ->assertViewIs('auth.register');
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'first_name' => 'Test',
        'last_name' => 'User',
    ]);
});
