<?php

use App\Models\User;

test('confirm password screen can be rendered', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/confirm-password')
        ->assertStatus(200)
        ->assertViewIs('auth.confirm-password')
        ->assertSee('This is a secure area');
});

test('password can be confirmed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/confirm-password', [
        'password' => 'password',
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $user->refresh();
});

test('password is not confirmed with invalid password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/confirm-password', [
        'password' => 'wrong-password',
    ])
        ->assertSessionHasErrors();
});
