<?php

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\User;

test('admin can complete full ticket lifecycle', function () {
    // 1. Create admin user
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    // 2. Create mailbox
    $this->actingAs($admin)
        ->post(route('mailboxes.store'), [
            'name' => 'Support',
            'email' => 'support@example.com',
        ])
        ->assertRedirect();

    $mailbox = Mailbox::first();
    expect($mailbox)->not->toBeNull()
        ->and($mailbox->name)->toBe('Support');

    // 3. Create customer
    $customer = Customer::factory()->create();

    // 4. Create conversation
    $customerEmail = $customer->getMainEmail();
    expect($customerEmail)->not->toBeNull('Customer should have email');

    $response = $this->actingAs($admin)
        ->post(route('conversations.store', $mailbox), [
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'subject' => 'Test Ticket',
            'body' => 'This is a test message',
            'to' => [$customerEmail],
        ]);

    $response->assertRedirect();

    $conversation = Conversation::first();
    expect($conversation)->not->toBeNull()
        ->and($conversation->subject)->toBe('Test Ticket');

    // 5. Reply to conversation
    $this->actingAs($admin)
        ->post(route('conversations.reply', $conversation), [
            'body' => 'Thank you for contacting us.',
            'type' => 1, // Message type
        ])
        ->assertRedirect();

    // 6. Verify thread created
    expect($conversation->fresh()->threads()->count())->toBeGreaterThanOrEqual(1);

    // 7. Close conversation
    $this->actingAs($admin)
        ->patch(route('conversations.update', $conversation), [
            'status' => Conversation::STATUS_CLOSED,
        ])
        ->assertRedirect();

    // 8. Verify conversation closed
    expect($conversation->fresh()->status)->toBe(Conversation::STATUS_CLOSED);
});

test('regular user workflow respects permissions', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    // User cannot create mailbox (admin only)
    $this->actingAs($user)
        ->post(route('mailboxes.store'), [
            'name' => 'Test',
            'email' => 'test@example.com',
        ])
        ->assertForbidden();

    // Grant mailbox access
    $mailbox->users()->attach($user->id);

    // User CAN create conversation in assigned mailbox
    $customer = Customer::factory()->create();
    $this->actingAs($user)
        ->post(route('conversations.store', $mailbox), [
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'subject' => 'Test',
            'body' => 'Message',
            'to' => [$customer->getMainEmail()],
        ])
        ->assertRedirect();

    expect(Conversation::all())->toHaveCount(1);
});

test('customer management workflow', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    // Create customer
    $this->actingAs($admin)
        ->post(route('customers.store'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ])
        ->assertRedirect();

    $customer = Customer::whereHas('emails', function ($query) {
        $query->where('email', 'john@example.com');
    })->first();

    expect($customer)->not->toBeNull()
        ->and($customer->first_name)->toBe('John');

    // Update customer
    $this->actingAs($admin)
        ->patchJson(route('customers.update', $customer), [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ])
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($customer->fresh()->first_name)->toBe('Jane');

    // View customer profile
    $this->actingAs($admin)
        ->get(route('customers.show', $customer))
        ->assertOk()
        ->assertSee('Jane Doe');
});

test('user management workflow', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    // Create new user
    $this->actingAs($admin)
        ->post(route('users.store'), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'testuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => User::ROLE_USER,
            'status' => User::STATUS_ACTIVE,
        ])
        ->assertRedirect();

    $newUser = User::where('email', 'testuser@example.com')->first();
    expect($newUser)->not->toBeNull()
        ->and($newUser->role)->toBe(User::ROLE_USER);

    // Update user
    $this->actingAs($admin)
        ->put(route('users.update', $newUser), [
            'first_name' => 'Updated',
            'last_name' => 'User',
            'email' => 'testuser@example.com',
            'role' => User::ROLE_USER,
            'status' => User::STATUS_ACTIVE,
        ])
        ->assertRedirect();

    expect($newUser->fresh()->first_name)->toBe('Updated');
});

test('mailbox settings workflow', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    // Update mailbox settings
    $this->actingAs($admin)
        ->patch(route('mailboxes.update', $mailbox), [
            'name' => 'Updated Mailbox',
            'email' => $mailbox->email, // Keep original email
        ])
        ->assertRedirect();

    expect($mailbox->fresh()->name)->toBe('Updated Mailbox');

    // Access mailbox settings page
    $this->actingAs($admin)
        ->get(route('mailboxes.settings', $mailbox))
        ->assertOk();
});

test('conversation search workflow', function () {
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    $customer = Customer::factory()->create();

    // Create conversations with searchable content
    Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
        'subject' => 'Unique Search Term ABC123',
    ]);

    Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
        'subject' => 'Different Subject',
    ]);

    // Search for specific conversation
    $this->actingAs($user)
        ->get(route('conversations.search', ['q' => 'ABC123']))
        ->assertOk();
});

test('authentication required for protected routes', function () {
    $conversation = Conversation::factory()->create();

    // Test that all main routes require authentication
    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->get(route('mailboxes.index'))->assertRedirect(route('login'));
    $this->get(route('conversations.show', $conversation))->assertRedirect(route('login'));
    $this->get(route('customers.index'))->assertRedirect(route('login'));
    $this->get(route('settings'))->assertRedirect(route('login'));
});

test('error pages are accessible', function () {
    // Test error pages don't crash
    $response = $this->get('/nonexistent-page');
    expect(in_array($response->status(), [404]))->toBeTrue();
});
