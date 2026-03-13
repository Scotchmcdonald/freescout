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

    $response->assertSee('Alpha')
        ->assertSee('alpha@example.com')
        ->assertSee('Beta')
        ->assertSee('beta@example.com');
});

test('customers list supports search by first name', function () {
    $user = User::factory()->create();
    Customer::factory()->create(['first_name' => 'UniqueName', 'email' => 'unique@example.com']);
    Customer::factory()->create(['first_name' => 'OtherName', 'email' => 'other@example.com']);

    $this->actingAs($user)->get(route('customers.index', ['search' => 'UniqueName']))
        ->assertOk()
        ->assertSee('UniqueName')
        ->assertSee('unique@example.com')
        ->assertDontSee('OtherName');
});

test('customers list supports search by last name', function () {
    $user = User::factory()->create();
    Customer::factory()->create(['last_name' => 'UniqueLast', 'email' => 'uniquelast@example.com']);
    Customer::factory()->create(['last_name' => 'OtherLast', 'email' => 'otherlast@example.com']);

    $this->actingAs($user)->get(route('customers.index', ['search' => 'UniqueLast']))
        ->assertOk()
        ->assertSee('UniqueLast')
        ->assertSee('uniquelast@example.com')
        ->assertDontSee('OtherLast');
});

test('customers list supports search by email', function () {
    $user = User::factory()->create();
    Customer::factory()->create(['first_name' => 'PersonA', 'email' => 'findme@example.com']);
    Customer::factory()->create(['first_name' => 'PersonB', 'email' => 'hide@example.com']);

    $this->actingAs($user)->get(route('customers.index', ['search' => 'findme@example.com']))
        ->assertOk()
        ->assertSee('PersonA')
        ->assertSee('findme@example.com')
        ->assertDontSee('PersonB'); // Assuming PersonB is not visible because email doesn't match
});

test('customers list pagination works', function () {
    $user = User::factory()->create();
    Customer::factory()->count(60)->create();

    $response = $this->actingAs($user)->get(route('customers.index'));

    $customers = $response->viewData('customers');
    expect($customers)->toHaveCount(50); // Standard pagination limit
});
