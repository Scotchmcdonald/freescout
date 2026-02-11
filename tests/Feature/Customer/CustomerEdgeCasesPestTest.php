<?php

use App\Models\Customer;
use App\Models\User;

test('customer with very long name is handled', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create([
        'first_name' => str_repeat('a', 50),
    ]);

    $this->actingAs($user)->get(route('customers.show', $customer->id))
        ->assertOk();
});

test('customer list displays with many records', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    Customer::factory()->count(30)->create();

    $this->actingAs($user)->get(route('customers.index'))
        ->assertOk()
        ->assertViewHas('customers');
});

test('customer with special characters in name is escaped', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create([
        'first_name' => '<script>alert("xss")</script>',
    ]);

    $response = $this->actingAs($user)->get(route('customers.show', $customer->id));
    
    $response->assertOk()
        ->assertSee(htmlspecialchars('<script>alert("xss")</script>'), false)
        ->assertDontSee('<script>alert("xss")</script>', false);
});

test('customer with null optional fields displays correctly', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create([
        'first_name' => 'Test',
        'last_name' => null,
        'company' => null,
    ]);

    $this->actingAs($user)->get(route('customers.show', $customer->id))
        ->assertOk();
});

test('guest cannot access customer pages', function () {
    $this->get(route('customers.index'))->assertRedirect(route('login'));
    
    $customer = Customer::factory()->create();
    $this->get(route('customers.show', $customer->id))->assertRedirect(route('login'));
});

test('non existent customer returns 404', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/customers/999999')
        ->assertNotFound();
});
