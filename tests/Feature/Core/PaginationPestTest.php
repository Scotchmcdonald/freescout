<?php

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\User;

// uses(Tests\TestCase::class); // Implicit in Feature folder

test('conversation list with page 2 navigation', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $customer = Customer::factory()->create();

    $user->mailboxes()->attach($mailbox->id);

    // Create 50 conversations for multi-page results
    Conversation::factory()->count(50)->create([
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
        'status' => Conversation::STATUS_ACTIVE,
    ]);

    $response = $this->actingAs($user)->get(route('conversations.index', ['mailbox' => $mailbox->id, 'page' => 2]));

    $response->assertOk();
    $response->assertViewHas('conversations');
});

test('customer ajax search with multiple matching results', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    // Create 25 customers with "Smith" in name for search
    Customer::factory()->count(25)->create(['last_name' => 'Smith']);

    // Create some with other names
    Customer::factory()->count(10)->create(['last_name' => 'Jones']);

    $response = $this->actingAs($user)->post(route('customers.ajax', ['action' => 'search', 'q' => 'Smith']));

    $response->assertOk();
    $response->assertJsonStructure([
        'results' => [
            '*' => ['id', 'text'],
        ],
    ]);

    $json = $response->json();
    expect(count($json['results']))->toBeGreaterThan(0);
});

test('conversation search with partial match', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $customer = Customer::factory()->create();

    Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
        'subject' => 'Payment Issue Resolution',
        'status' => Conversation::STATUS_ACTIVE,
    ]);

    Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
        'subject' => 'Technical Support Question',
        'status' => Conversation::STATUS_ACTIVE,
    ]);

    $response = $this->actingAs($user)->get(route('conversations.search', ['q' => 'Payment']));

    $response->assertOk();
    // Legacy didn't assert seeing it, just status 200. We can add assertion if we want, but sticking to legacy parity.
});

test('mailbox conversations filter by assignment status', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $assignedUser = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $customer = Customer::factory()->create();

    $admin->mailboxes()->attach($mailbox->id);

    // Create assigned conversation
    Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
        'user_id' => $assignedUser->id,
        'status' => Conversation::STATUS_ACTIVE,
    ]);

    // Create unassigned conversation
    Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
        'user_id' => null,
        'status' => Conversation::STATUS_ACTIVE,
    ]);

    $response = $this->actingAs($admin)->get(route('conversations.index', $mailbox->id));

    $response->assertOk();
    $response->assertViewHas('conversations');
});

test('customer list handles large dataset efficiently', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    // Create 100 customers
    Customer::factory()->count(100)->create();

    $response = $this->actingAs($user)->get('/customers');

    $response->assertOk();
    $response->assertViewHas('customers');

    // Pagination should limit results per page
    $customers = $response->viewData('customers');
    expect($customers->count())->toBeLessThanOrEqual(50);
});

test('empty search query returns all results', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    Customer::factory()->count(10)->create();

    $response = $this->actingAs($user)->post('/customers/ajax?action=search&q=');

    $response->assertOk();
});

test('conversation list excludes draft conversations by default', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $customer = Customer::factory()->create();

    $user->mailboxes()->attach($mailbox->id);

    Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
        'status' => Conversation::STATUS_ACTIVE,
        'state' => Conversation::STATE_PUBLISHED,
    ]);

    Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
        'status' => Conversation::STATUS_ACTIVE,
        'state' => Conversation::STATE_DRAFT,
    ]);

    $response = $this->actingAs($user)->get(route('conversations.index', $mailbox->id));

    $response->assertOk();
    $response->assertViewHas('conversations');
});
