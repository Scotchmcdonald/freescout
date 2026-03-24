<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;

test('complete conversation workflow from creation to closure', function () {
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    $customer = Customer::factory()->create();

    // Create conversation
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'customer_id' => $customer->id,
        'user_id' => null, // Unassigned initially
        'status' => Conversation::STATUS_ACTIVE,
    ]);

    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
        'status' => Conversation::STATUS_ACTIVE,
        'user_id' => null,
    ]);

    // Assign to user
    $conversation->update(['user_id' => $user->id]);

    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
        'user_id' => $user->id,
    ]);

    // Add reply thread
    $thread = Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'type' => Thread::TYPE_MESSAGE,
    ]);

    $this->assertDatabaseHas('threads', [
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
    ]);

    // Close conversation
    $conversation->update(['status' => Conversation::STATUS_CLOSED]);

    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
        'status' => Conversation::STATUS_CLOSED,
    ]);
});

test('conversation reassignment workflow', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user1->id]);

    // Reassign to different user
    $conversation->update(['user_id' => $user2->id]);

    expect($conversation->fresh()->user_id)->toBe($user2->id);
});

test('conversation with multiple threads', function () {
    $conversation = Conversation::factory()->create();
    $user = User::factory()->create();

    // Customer initial message
    Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'type' => Thread::TYPE_CUSTOMER,
    ]);

    // User replies
    Thread::factory()->count(2)->create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'type' => Thread::TYPE_MESSAGE,
    ]);

    expect($conversation->fresh()->threads)->toHaveCount(3);
});

test('conversation status transitions', function () {
    $conversation = Conversation::factory()->create(['status' => Conversation::STATUS_ACTIVE]);

    // Active -> Pending
    $conversation->update(['status' => Conversation::STATUS_PENDING]);
    expect($conversation->fresh()->status)->toBe(Conversation::STATUS_PENDING);

    // Pending -> Closed
    $conversation->update(['status' => Conversation::STATUS_CLOSED]);
    expect($conversation->fresh()->status)->toBe(Conversation::STATUS_CLOSED);

    // Closed -> Active (reopen)
    $conversation->update(['status' => Conversation::STATUS_ACTIVE]);
    expect($conversation->fresh()->status)->toBe(Conversation::STATUS_ACTIVE);
});
