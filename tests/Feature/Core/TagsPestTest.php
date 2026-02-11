<?php

use App\Models\User;

test('tags ajax search requires authentication', function () {
    $this->get(route('tags.ajax_search'))
        ->assertRedirect(route('login'));
});

test('tags ajax search returns json', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('tags.ajax_search', ['q' => 'test']))
        ->assertOk()
        ->assertJson([]);
});
