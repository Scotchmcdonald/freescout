<?php

use App\Models\Customer;
use App\Models\User;

test('add social profile (twitter)', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $customer = Customer::factory()->create();

    $this->actingAs($user)->postJson(route('customers.ajax'), [
        'action' => 'add_social_profile',
        'customer_id' => $customer->id,
        'type' => 'twitter',
        'value' => '@username',
    ])->assertJson(['success' => true]);

    $customer->refresh();
    expect($customer->social_profiles['twitter'])->toBe('@username');
});

test('add social profile (facebook)', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $customer = Customer::factory()->create();

    $this->actingAs($user)->postJson(route('customers.ajax'), [
        'action' => 'add_social_profile',
        'customer_id' => $customer->id,
        'type' => 'facebook',
        'value' => 'facebook.com/user',
    ])->assertJson(['success' => true]);

    $customer->refresh();
    expect($customer->social_profiles['facebook'])->toBe('facebook.com/user');
});

test('add social profile (linkedin)', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $customer = Customer::factory()->create();

    $this->actingAs($user)->postJson(route('customers.ajax'), [
        'action' => 'add_social_profile',
        'customer_id' => $customer->id,
        'type' => 'linkedin',
        'value' => 'linkedin.com/in/user',
    ])->assertJson(['success' => true]);

    $customer->refresh();
    expect($customer->social_profiles['linkedin'])->toBe('linkedin.com/in/user');
});

test('update existing social profile', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $customer = Customer::factory()->create([
        'social_profiles' => ['twitter' => '@old']
    ]);

    $this->actingAs($user)->postJson(route('customers.ajax'), [
        'action' => 'add_social_profile',
        'customer_id' => $customer->id,
        'type' => 'twitter',
        'value' => '@new',
    ])->assertJson(['success' => true]);

    $customer->refresh();
    expect($customer->social_profiles['twitter'])->toBe('@new');
});

test('delete social profile', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $customer = Customer::factory()->create([
        'social_profiles' => ['twitter' => '@user', 'facebook' => 'fb/user']
    ]);

    $this->actingAs($user)->postJson(route('customers.ajax'), [
        'action' => 'delete_social_profile',
        'customer_id' => $customer->id,
        'type' => 'twitter',
    ])->assertJson(['success' => true]);

    $customer->refresh();
    expect($customer->social_profiles)
        ->not->toHaveKey('twitter')
        ->toHaveKey('facebook');
});

test('invalid social profile type rejected', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $customer = Customer::factory()->create();

    $this->actingAs($user)->postJson(route('customers.ajax'), [
        'action' => 'add_social_profile',
        'customer_id' => $customer->id,
        'type' => 'invalid_type',
        'value' => 'val',
    ])->assertStatus(422);
});

test('add website', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $customer = Customer::factory()->create();

    $this->actingAs($user)->postJson(route('customers.ajax'), [
        'action' => 'add_website',
        'customer_id' => $customer->id,
        'url' => 'https://example.com',
    ])->assertJson(['success' => true]);

    $customer->refresh();
    expect($customer->websites)->toContain('https://example.com');
});

test('add multiple websites', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $customer = Customer::factory()->create();

    $this->actingAs($user)->postJson(route('customers.ajax'), [
        'action' => 'add_website',
        'customer_id' => $customer->id,
        'url' => 'https://example.com',
    ]);

    $this->actingAs($user)->postJson(route('customers.ajax'), [
        'action' => 'add_website',
        'customer_id' => $customer->id,
        'url' => 'https://another.com',
    ]);

    $customer->refresh();
    expect($customer->websites)
        ->toHaveCount(2)
        ->toContain('https://example.com')
        ->toContain('https://another.com');
});

test('duplicate website not added', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $customer = Customer::factory()->create(['websites' => ['https://example.com']]);

    $this->actingAs($user)->postJson(route('customers.ajax'), [
        'action' => 'add_website',
        'customer_id' => $customer->id,
        'url' => 'https://example.com',
    ])->assertJson(['success' => true]);

    $customer->refresh();
    expect($customer->websites)->toHaveCount(1);
});

test('delete website', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $customer = Customer::factory()->create([
        'websites' => ['https://one.com', 'https://two.com', 'https://three.com']
    ]);

    // Delete index 1 (second.com)
    $this->actingAs($user)->postJson(route('customers.ajax'), [
        'action' => 'delete_website',
        'customer_id' => $customer->id,
        'website_index' => 1,
    ])->assertJson(['success' => true]);

    $customer->refresh();
    expect($customer->websites)
        ->toHaveCount(2)
        ->toContain('https://one.com')
        ->toContain('https://three.com')
        ->not->toContain('https://two.com');
});

test('invalid url rejected', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $customer = Customer::factory()->create();

    $this->actingAs($user)->postJson(route('customers.ajax'), [
        'action' => 'add_website',
        'customer_id' => $customer->id,
        'url' => 'not-a-url',
    ])->assertStatus(422);
});

test('unauthenticated user cannot modify social or websites', function () {
    $customer = Customer::factory()->create();

    $this->postJson(route('customers.ajax'), [
        'action' => 'add_social_profile',
        'customer_id' => $customer->id,
        'type' => 'twitter',
        'value' => '@user',
    ])->assertStatus(401);

    $this->postJson(route('customers.ajax'), [
        'action' => 'add_website',
        'customer_id' => $customer->id,
        'url' => 'https://example.com',
    ])->assertStatus(401);
});
