<?php

use App\Models\Customer;
use App\Models\Email;
use App\Models\User;

test('customers index requires authentication', function () {
    $this->get(route('customers.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can view customers list', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('customers.index'))
        ->assertOk()
        ->assertViewIs('customers.index')
        ->assertViewHas('customers');
});

test('customers list displays created customers', function () {
    $user = User::factory()->create();

    // Create specific customers to check for logic
    $customer1 = Customer::factory()->withoutEmail()->create(['first_name' => 'Alpha']);
    Email::factory()->create(['customer_id' => $customer1->id, 'email' => 'alpha@example.com']);

    $customer2 = Customer::factory()->withoutEmail()->create(['first_name' => 'Beta']);
    Email::factory()->create(['customer_id' => $customer2->id, 'email' => 'beta@example.com']);

    $response = $this->actingAs($user)->get(route('customers.index'));

    $customers = $response->viewData('customers')->getCollection()->keyBy('first_name');

    $response->assertOk()->assertViewHas('customers');
    expect($customers->has('Alpha'))->toBeTrue()
        ->and($customers->has('Beta'))->toBeTrue();
});

test('customers list supports search by first name', function () {
    $user = User::factory()->create();
    Customer::factory()->create(['first_name' => 'UniqueName', 'email' => 'unique@example.com']);
    Customer::factory()->create(['first_name' => 'OtherName', 'email' => 'other@example.com']);

    $response = $this->actingAs($user)->get(route('customers.index', ['search' => 'UniqueName']));

    $customers = $response->viewData('customers')->getCollection();
    $names = $customers->pluck('first_name');

    $response->assertOk()->assertViewHas('customers');
    expect($names)->toContain('UniqueName')
        ->and($names)->not->toContain('OtherName');
});

test('customers list supports search by last name', function () {
    $user = User::factory()->create();
    Customer::factory()->create(['last_name' => 'UniqueLast', 'email' => 'uniquelast@example.com']);
    Customer::factory()->create(['last_name' => 'OtherLast', 'email' => 'otherlast@example.com']);

    $response = $this->actingAs($user)->get(route('customers.index', ['search' => 'UniqueLast']));

    $customers = $response->viewData('customers')->getCollection();
    $lastNames = $customers->pluck('last_name');

    $response->assertOk()->assertViewHas('customers');
    expect($lastNames)->toContain('UniqueLast')
        ->and($lastNames)->not->toContain('OtherLast');
});

test('customers list supports search by email', function () {
    $user = User::factory()->create();
    Customer::factory()->create(['first_name' => 'PersonA', 'email' => 'findme@example.com']);
    Customer::factory()->create(['first_name' => 'PersonB', 'email' => 'hide@example.com']);

    $response = $this->actingAs($user)->get(route('customers.index', ['search' => 'findme@example.com']));

    $customers = $response->viewData('customers')->getCollection();
    $names = $customers->pluck('first_name');

    $response->assertOk()->assertViewHas('customers');
    expect($names)->toContain('PersonA')
        ->and($names)->not->toContain('PersonB');
});

test('customers list pagination works', function () {
    $user = User::factory()->create();
    Customer::factory()->count(60)->create();

    $response = $this->actingAs($user)->get(route('customers.index'));

    $customers = $response->viewData('customers');
    expect($customers)->toHaveCount(50); // Standard pagination limit
});
