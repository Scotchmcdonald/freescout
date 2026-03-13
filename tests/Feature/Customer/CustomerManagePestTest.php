<?php

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Email;
use App\Models\User;

test('create customer requires authentication', function () {
    $this->post(route('customers.store'), [
        'first_name' => 'John',
        'email' => 'john@example.com',
    ])->assertRedirect(route('login'));
});

test('can create customer with valid data', function () {
    $user = User::factory()->create();
    $email = 'newcustomerUser'.time().'@example.com';

    $response = $this->actingAs($user)->post(route('customers.store'), [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => $email,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('customers', ['first_name' => 'John', 'last_name' => 'Doe']);
    // App lowercases emails
    $this->assertDatabaseHas('emails', ['email' => strtolower($email)]);
});

test('customer creation validation rules', function () {
    $user = User::factory()->create();

    // Required first name
    $this->actingAs($user)->post(route('customers.store'), ['email' => 'test@example.com'])
        ->assertSessionHasErrors('first_name');

    // Max length first name
    $this->actingAs($user)->post(route('customers.store'), [
        'first_name' => str_repeat('a', 51),
        'email' => 'test@example.com',
    ])->assertSessionHasErrors('first_name');

    // Required email
    $this->actingAs($user)->post(route('customers.store'), ['first_name' => 'John'])
        ->assertSessionHasErrors('email');

    // Invalid email format
    $this->actingAs($user)->post(route('customers.store'), [
        'first_name' => 'John',
        'email' => 'not-an-email',
    ])->assertSessionHasErrors('email');
});

test('cannot create customer with existing email', function () {
    $user = User::factory()->create();
    $existing = Customer::factory()->create(['email' => 'existing@example.com']);

    $this->actingAs($user)->post(route('customers.store'), [
        'first_name' => 'John',
        'email' => 'existing@example.com',
    ])->assertSessionHasErrors('email');
});

test('can view single customer details', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith']);
    Email::factory()->create(['customer_id' => $customer->id, 'email' => 'jane@example.com']);

    // Setup some conversations
    Conversation::factory()->create(['customer_id' => $customer->id, 'subject' => 'Issue #1']);

    $response = $this->actingAs($user)->get(route('customers.show', $customer->id));

    $response->assertOk()
        ->assertViewIs('customers.show')
        ->assertSee('Jane Smith')
        ->assertSee('jane@example.com')
        ->assertSee('Issue #1');
});

test('can update customer details', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create(['first_name' => 'OldName']);

    $response = $this->actingAs($user)->patchJson(route('customers.update', $customer->id), [
        'first_name' => 'NewName',
        'last_name' => 'Updated',
        'company' => 'Acme Corp',
        'job_title' => 'Developer',
        'city' => 'New York',
    ]);

    $response->assertOk()
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'first_name' => 'NewName',
        'company' => 'Acme Corp',
    ]);
});

test('can merge two customers', function () {
    $user = User::factory()->create();

    $source = Customer::factory()->create(['first_name' => 'Source']);
    $target = Customer::factory()->create(['first_name' => 'Target']);

    $conv = Conversation::factory()->create(['customer_id' => $source->id]);

    $response = $this->actingAs($user)->post('/customers/merge', [
        'source_id' => $source->id,
        'target_id' => $target->id,
    ]);

    $response->assertOk()
        ->assertJson(['success' => true]);

    // Conversation should now belong to target
    $this->assertDatabaseHas('conversations', [
        'id' => $conv->id,
        'customer_id' => $target->id,
    ]);
});
