<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $this->user = User::factory()->create(['role' => User::ROLE_USER]);

    $this->mailbox = Mailbox::factory()->create();
    $this->mailbox->users()->attach($this->user);

    Folder::factory()->create([
        'mailbox_id' => $this->mailbox->id,
        'type' => Folder::TYPE_INBOX,
    ]);

    $customer = Customer::factory()->create();

    $this->conversation = Conversation::factory()->create([
        'mailbox_id' => $this->mailbox->id,
        'customer_id' => $customer->id,
        'subject' => 'Test Conversation',
        'status' => Conversation::STATUS_ACTIVE,
        'state' => 2,
    ]);
});

test('user can reply to conversation', function () {
    $replyData = [
        'body' => 'This is a reply to the conversation',
        'status' => Conversation::STATUS_ACTIVE,
    ];

    $this->actingAs($this->user)->post(
        route('conversations.reply', $this->conversation),
        $replyData
    )->assertRedirect();

    $this->assertDatabaseHas('threads', [
        'conversation_id' => $this->conversation->id,
        'body' => '<p>This is a reply to the conversation</p>',
        'type' => 1, // User message type
    ]);
});

test('reply validates body is required', function () {
    $this->actingAs($this->user)->post(
        route('conversations.reply', $this->conversation),
        ['body' => '']
    )->assertSessionHasErrors('body');
});

test('reply can change conversation status', function () {
    $this->conversation->update(['status' => Conversation::STATUS_ACTIVE]);

    $replyData = [
        'body' => 'Closing this conversation',
        'status' => Conversation::STATUS_CLOSED,
    ];

    $this->actingAs($this->user)->post(
        route('conversations.reply', $this->conversation),
        $replyData
    )->assertRedirect();

    $this->assertDatabaseHas('conversations', [
        'id' => $this->conversation->id,
        'status' => Conversation::STATUS_CLOSED,
    ]);
});

test('reply assigns conversation to replying user', function () {
    $this->conversation->update(['user_id' => null]);

    $replyData = [
        'body' => 'I am taking this conversation',
    ];

    $this->actingAs($this->user)->post(
        route('conversations.reply', $this->conversation),
        $replyData
    )->assertRedirect();

    $this->assertDatabaseHas('conversations', [
        'id' => $this->conversation->id,
        'user_id' => $this->user->id,
    ]);
});

test('unauthorized user cannot reply to conversation', function () {
    $unauthorizedUser = User::factory()->create();

    $replyData = [
        'body' => 'Unauthorized reply',
    ];

    $this->actingAs($unauthorizedUser)->post(
        route('conversations.reply', $this->conversation),
        $replyData
    )->assertForbidden();

    $this->assertDatabaseMissing('threads', [
        'body' => 'Unauthorized reply',
    ]);
});

test('guest cannot reply to conversation', function () {
    $this->post(
        route('conversations.reply', $this->conversation),
        ['body' => 'Guest reply']
    )->assertRedirect(route('login'));
});

test('reply updates conversation last reply at timestamp', function () {
    $originalTime = $this->conversation->last_reply_at;

    sleep(1); // Ensure time difference

    $replyData = [
        'body' => 'New reply',
    ];

    $this->actingAs($this->user)->post(
        route('conversations.reply', $this->conversation),
        $replyData
    )->assertRedirect();

    $this->conversation->refresh();

    // Depending on DB precision, sometimes sleep(1) is enough.
    // However, if last_reply_at was null initially, it will definitely change.
    // If it was set, it should update.
    // The legacy test asserts NotEquals.

    expect($this->conversation->last_reply_at)->not->toBe($originalTime);
});

test('conversation update changes status', function () {
    $this->conversation->update(['status' => Conversation::STATUS_ACTIVE]);

    $updateData = [
        'status' => Conversation::STATUS_CLOSED,
    ];

    $this->actingAs($this->user)->patchJson(
        route('conversations.update', $this->conversation),
        $updateData
    )->assertOk();

    $this->assertDatabaseHas('conversations', [
        'id' => $this->conversation->id,
        'status' => Conversation::STATUS_CLOSED,
    ]);
});

test('conversation update assigns user', function () {
    $this->conversation->update(['user_id' => null]);

    $updateData = [
        'user_id' => $this->user->id,
    ];

    $this->actingAs($this->user)->patchJson(
        route('conversations.update', $this->conversation),
        $updateData
    )->assertOk();

    $this->assertDatabaseHas('conversations', [
        'id' => $this->conversation->id,
        'user_id' => $this->user->id,
    ]);
});

test('unauthorized user cannot update conversation', function () {
    $unauthorizedUser = User::factory()->create();

    $updateData = [
        'status' => Conversation::STATUS_CLOSED,
    ];

    $this->actingAs($unauthorizedUser)->patch(
        route('conversations.update', $this->conversation),
        $updateData
    )->assertForbidden();
});
