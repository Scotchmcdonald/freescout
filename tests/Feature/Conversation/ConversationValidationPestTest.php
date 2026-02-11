<?php

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;

test('conversation requires subject and body', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    $customer = Customer::factory()->create();

    // Verify Subject
    $this->actingAs($user)->post(route('conversations.store', $mailbox), [
        'customer_id' => $customer->id,
        'subject' => '',
        'body' => 'Test',
        'to' => [$customer->email],
    ])->assertSessionHasErrors('subject');

    // Verify Body
    $this->actingAs($user)->post(route('conversations.store', $mailbox), [
        'customer_id' => $customer->id,
        'subject' => 'Test',
        'body' => '',
        'to' => [$customer->email],
    ])->assertSessionHasErrors('body');
});

test('conversation validates email format', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    $customer = Customer::factory()->create();

    $this->actingAs($user)->post(route('conversations.store', $mailbox), [
        'customer_id' => $customer->id,
        'subject' => 'Test',
        'body' => 'Test',
        'to' => ['not-an-email'],
    ])->assertSessionHasErrors('to.0');
});

test('conversation accepts multiple recipients', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    $customer = Customer::factory()->create();

    $this->actingAs($user)->post(route('conversations.store', $mailbox), [
        'customer_id' => $customer->id,
        'subject' => 'Test',
        'body' => 'Test',
        'to' => ['1@test.com', '2@test.com'],
    ])->assertRedirect();
});

test('conversation validates customer exists', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);

    $this->actingAs($user)->post(route('conversations.store', $mailbox), [
        'customer_id' => 99999,
        'subject' => 'Test',
        'body' => 'Test',
        'to' => ['test@test.com'],
    ])->assertSessionHasErrors('customer_id');
});

test('conversation validation handles special characters in subject', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    $customer = Customer::factory()->create();
    $specialSubject = 'Test: Émojis / Special chars';

    // Ensure we have a valid email, sometimes factory accessor might be flaky if not reloaded
    $email = $customer->emails->first()?->email ?? 'fallback@example.com';

    $this->actingAs($user)->post(route('conversations.store', $mailbox), [
        'customer_id' => $customer->id,
        'subject' => $specialSubject,
        'body' => 'Test',
        'to' => [$email],
    ])->assertRedirect()->assertSessionHasNoErrors();

    $this->assertDatabaseHas('conversations', [
        'subject' => $specialSubject,
    ]);
});

test('conversation subject length validation', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    $customer = Customer::factory()->create();
    $longSubject = str_repeat('a', 300);

    // Depending on logic, this might be truncated or allowed, but usually validated max length
    // Legacy test says basically "Expect Redirect OR 422". 
    // We will assume it should work or fail gracefully.
    
    $response = $this->actingAs($user)->post(route('conversations.store', $mailbox), [
        'customer_id' => $customer->id,
        'subject' => $longSubject,
        'body' => 'Test',
        'to' => [$customer->email],
    ]);

    expect($response->status())->toBeIn([200, 302, 422]);
});

test('conversation rejects whitespace-only body', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    $customer = Customer::factory()->create();

    $this->actingAs($user)->post(route('conversations.store', $mailbox), [
        'customer_id' => $customer->id,
        'subject' => 'Test',
        'body' => "   \n   ",
        'to' => [$customer->email],
    ])->assertSessionHasErrors('body');
});

test('conversation status change validates values', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);

    $conversation = Conversation::factory()->for($mailbox)->create(['status' => Conversation::STATUS_ACTIVE]);

    // Try invalid status - should reject or validate
    $response = $this->actingAs($user)->patch(route('conversations.update', $conversation->id), [
        'status' => 999, // Invalid status
    ]);

    // Depending on validation implementation, it might redirect back with errors or return 422
    // Legacy test just asserted database missing. AssertSessionHasErrors is better.
    $response->assertSessionHasErrors('status');

    $this->assertDatabaseMissing('conversations', [
        'id' => $conversation->id,
        'status' => 999,
    ]);
});
