<?php

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;

test('user cannot view conversation in unauthorized mailbox', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    // User NOT attached to mailbox

    $conversation = Conversation::factory()->for($mailbox)->create();

    $this->actingAs($user)
        ->get(route('conversations.show', $conversation))
        ->assertForbidden();
});

test('user cannot reply to unauthorized conversation', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    
    $conversation = Conversation::factory()->for($mailbox)->create();

    $this->actingAs($user)
        ->post(route('conversations.reply', $conversation), [
            'body' => 'This is a reply',
        ])
        ->assertForbidden();
});

test('reporter cannot close conversation', function () {
    $user = User::factory()->create(['role' => User::ROLE_REPORTER]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    $conversation = Conversation::factory()->for($mailbox)->create(['status' => Conversation::STATUS_ACTIVE]);

    $this->actingAs($user)
        ->postJson(route('conversations.ajax'), [
            'action' => 'change_status',
            'conversation_id' => $conversation->id,
            'status' => Conversation::STATUS_CLOSED
        ])
        ->assertForbidden()
        ->assertJsonFragment(['message' => 'Reporters cannot close tickets']);
});

test('user cannot update unauthorized conversation', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    // User not attached to mailbox

    $conversation = Conversation::factory()->for($mailbox)->create();

    $this->actingAs($user)
        ->patch(route('conversations.update', $conversation), [
            'status' => Conversation::STATUS_CLOSED
        ])
        ->assertForbidden();
});

test('conversation search prevents sql injection', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => Folder::TYPE_INBOX]);

    // Create a legitimate conversation
    $conversation = Conversation::factory()->for($mailbox)->create([
        'subject' => 'Legitimate Conversation',
        'state' => Conversation::STATE_PUBLISHED,
    ]);

    $maliciousInput = "' OR '1'='1";

    $response = $this->actingAs($user)->get(
        route('conversations.index', $mailbox).'?q='.urlencode($maliciousInput)
    );

    $response->assertOk();
    $response->assertDontSee($maliciousInput);
    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
        'subject' => 'Legitimate Conversation',
    ]);
});

test('conversation subject handles xss payload', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => Folder::TYPE_INBOX]);
    $customer = Customer::factory()->create();

    $xssPayload = '<script>alert("xss")</script>';
    
    // Create customer email to avoid validation error
    $email = \App\Models\Email::factory()->create(['customer_id' => $customer->id]);

    $this->actingAs($user)->post(route('conversations.store', $mailbox), [
        'customer_id' => $customer->id,
        'subject' => $xssPayload,
        'body' => 'Test body',
        'to' => [$email->email],
    ])->assertRedirect();
    
    $conversation = Conversation::latest('id')->first();
    // We expect it to be stored (DB usually stores raw), but typically we'd test view escaping.
    // Legacy test just asserted it exists and contains script.
    expect($conversation->subject)->toBe('alert("xss")');
});
