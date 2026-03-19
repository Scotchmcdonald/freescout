<?php

use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Subscription;
use App\Models\User;

test('displays customer conversations page', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER, 'status' => 1]);
    $customer = Customer::factory()->create();

    $this->actingAs($user)->get(route('customers.conversations', $customer))
        ->assertOk()
        ->assertViewIs('customers.conversations');
});

test('displays customer merge form', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER, 'status' => 1]);
    $customer = Customer::factory()->create();

    $this->actingAs($user)->get(route('customers.merge.form', $customer))
        ->assertOk()
        ->assertViewIs('customers.merge');
});

test('displays user notifications page', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER, 'status' => 1]);

    $this->actingAs($user)->get(route('users.notifications', $user))
        ->assertOk()
        ->assertViewIs('users.notifications');
});

test('displays user permissions page', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'status' => 1]);
    $user = User::factory()->create(['role' => User::ROLE_USER, 'status' => 1]);
    Mailbox::factory()->create(['name' => 'Support']);

    $this->actingAs($admin)->get(route('users.permissions', $user))
        ->assertOk()
        ->assertViewIs('users.permissions');
});

test('updates user notifications', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER, 'status' => 1]);

    $this->actingAs($user)->post(route('users.notifications.update', $user), [
        'subscriptions' => [
            Subscription::MEDIUM_EMAIL => [
                Subscription::EVENT_NEW_CONVERSATION,
            ],
        ],
    ])->assertRedirect()->assertSessionHas('success');

    expect($user->fresh()->subscriptions)->toHaveCount(1);
});

test('updates user mailbox permissions', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'status' => 1]);
    $user = User::factory()->create(['role' => User::ROLE_USER, 'status' => 1]);
    $m1 = Mailbox::factory()->create();
    $m2 = Mailbox::factory()->create();

    $this->actingAs($admin)->post(route('users.permissions.update', $user), [
        'mailboxes' => [$m1->id, $m2->id],
    ])->assertRedirect()->assertSessionHas('success');

    expect($user->mailboxes()->count())->toBe(2);
});

test('prevents non-admin from viewing other users permissions', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER, 'status' => 1]);
    $other = User::factory()->create(['role' => User::ROLE_USER, 'status' => 1]);

    $this->actingAs($user)->get(route('users.permissions', $other))
        ->assertForbidden();
});

test('allows users to view their own notifications', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER, 'status' => 1]);

    $this->actingAs($user)->get(route('users.notifications', $user))
        ->assertOk();
});

test('deletes customer without conversations', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'status' => 1]);
    $customer = Customer::factory()->create();

    $this->actingAs($admin)->delete(route('customers.destroy', $customer))
        ->assertRedirect(route('customers.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
});

test('prevents deleting customer with conversations', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'status' => 1]);
    $customer = Customer::factory()->hasConversations(1)->create();

    $this->actingAs($admin)->delete(route('customers.destroy', $customer))
        ->assertRedirect()
        ->assertSessionHasErrors();

    $this->assertDatabaseHas('customers', ['id' => $customer->id]);
});

test('customers table partial displays customers', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER, 'status' => 1]);
    $c1 = Customer::factory()->create(['first_name' => 'Charlie']);

    $this->actingAs($user)->get(route('customers.index'))
        ->assertSee('Charlie');
});
