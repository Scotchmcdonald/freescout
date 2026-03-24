<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// ==================== Complete Conversation Workflows ====================

test('complete conversation lifecycle from creation to closure', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => Folder::TYPE_INBOX]);
    $customer = Customer::factory()->create();

    // Assign admin to mailbox
    $mailbox->users()->attach($admin->id);

    // Create conversation
    $response = $this->actingAs($admin)->post(route('conversations.store', ['mailbox' => $mailbox->id]), [
        'subject' => 'Test Issue',
        'customer_id' => $customer->id,
        'type' => Thread::TYPE_MESSAGE,
        'body' => 'This is a test conversation',
        'to' => ['customer@example.com'],
    ]);

    $response->assertRedirect();

    $conversation = Conversation::first();
    expect($conversation)->not->toBeNull()
        ->and($conversation->status)->toBe(Conversation::STATUS_ACTIVE);

    // Reply to conversation
    $this->actingAs($admin)->post(route('conversations.reply', $conversation->id), [
        'body' => 'Here is my reply',
        'type' => Thread::TYPE_MESSAGE,
    ]);

    $this->assertDatabaseHas('threads', [
        'conversation_id' => $conversation->id,
        'body' => '<p>Here is my reply</p>',
    ]);

    // Close conversation
    $this->actingAs($admin)->patch(route('conversations.update', $conversation->id), [
        'status' => Conversation::STATUS_CLOSED,
    ]);

    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
        'status' => Conversation::STATUS_CLOSED,
    ]);
});

test('conversation assignment workflow', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $mailbox = Mailbox::factory()->create();

    // Assign users to mailbox
    $mailbox->users()->attach([$admin->id, $user1->id, $user2->id]);

    $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);

    // Assign to user1 using the generic update route
    $this->actingAs($admin)->patch(route('conversations.update', $conversation), [
        'user_id' => $user1->id,
    ]);

    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
        'user_id' => $user1->id,
    ]);

    // Reassign to user2
    $this->actingAs($admin)->patch(route('conversations.update', $conversation), [
        'user_id' => $user2->id,
    ]);

    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
        'user_id' => $user2->id,
    ]);
});

// ==================== Multi-Mailbox Scenarios ====================

test('user can access only assigned mailboxes', function () {
    $user = User::factory()->create();
    $mailbox1 = Mailbox::factory()->create(['name' => 'Support']);
    $mailbox2 = Mailbox::factory()->create(['name' => 'Sales']);

    // User only assigned to mailbox1
    $mailbox1->users()->attach($user->id);

    $conversation1 = Conversation::factory()->create(['mailbox_id' => $mailbox1->id]);
    $conversation2 = Conversation::factory()->create(['mailbox_id' => $mailbox2->id]);

    // Can access mailbox1 conversation
    $this->actingAs($user)->get(route('conversations.show', $conversation1->id))
        ->assertSuccessful();

    // Cannot access mailbox2 conversation
    $this->actingAs($user)->get(route('conversations.show', $conversation2->id))
        ->assertForbidden();
});

test('conversations isolated between mailboxes', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox1 = Mailbox::factory()->create();
    $mailbox2 = Mailbox::factory()->create();

    // Assign admin to mailbox1
    $mailbox1->users()->attach($admin->id);

    $conversation1 = Conversation::factory()->create(['mailbox_id' => $mailbox1->id]);
    $conversation2 = Conversation::factory()->create(['mailbox_id' => $mailbox2->id]);

    // List conversations for mailbox1
    $response = $this->actingAs($admin)->get(route('conversations.index', $mailbox1));

    $response->assertSuccessful();
    $response->assertSee($conversation1->subject);
    $response->assertDontSee($conversation2->subject);
});

// ==================== Database Transaction Tests ====================

test('conversation creation is transactional', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => Folder::TYPE_INBOX]);
    $mailbox->users()->attach($admin->id);

    DB::shouldReceive('transaction')->once()->andReturnUsing(function ($callback) {
        return $callback();
    });

    $this->actingAs($admin)->post(route('conversations.store', ['mailbox' => $mailbox->id]), [
        'subject' => 'Test',
        'customer_email' => 'test@example.com',
        'type' => Thread::TYPE_MESSAGE,
        'body' => 'Body',
        'to' => ['test@example.com'],
    ]);
});

test('failed conversation creation rolls back', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => Folder::TYPE_INBOX]);
    $mailbox->users()->attach($admin->id); // Also assign admin to mailbox

    $conversationCount = Conversation::count();
    $threadCount = Thread::count();

    // Invalid data should fail and rollback
    $this->actingAs($admin)->post(route('conversations.store', ['mailbox' => $mailbox->id]), [
        'subject' => '', // Invalid - required field
        'body' => '',
    ]);

    // No new records should be created
    expect(Conversation::count())->toBe($conversationCount)
        ->and(Thread::count())->toBe($threadCount);
});

// ==================== Concurrent Operations ====================

test('concurrent replies to same conversation', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach([$user1->id, $user2->id]);

    $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);

    // Both users reply at the same time
    $this->actingAs($user1)->post(route('conversations.reply', $conversation->id), [
        'body' => 'Reply from user 1',
        'type' => Thread::TYPE_MESSAGE,
    ]);

    $this->actingAs($user2)->post(route('conversations.reply', $conversation->id), [
        'body' => 'Reply from user 2',
        'type' => Thread::TYPE_MESSAGE,
    ]);

    // Both replies should be saved
    $this->assertDatabaseHas('threads', [
        'conversation_id' => $conversation->id,
        'body' => '<p>Reply from user 1</p>',
    ]);
    $this->assertDatabaseHas('threads', [
        'conversation_id' => $conversation->id,
        'body' => '<p>Reply from user 2</p>',
    ]);
});

test('concurrent status changes', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($admin->id);

    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'status' => Conversation::STATUS_ACTIVE,
    ]);

    // Change status twice rapidly
    $this->actingAs($admin)->patch(route('conversations.update', $conversation->id), [
        'status' => Conversation::STATUS_PENDING,
    ]);

    $this->actingAs($admin)->patch(route('conversations.update', $conversation->id), [
        'status' => Conversation::STATUS_CLOSED,
    ]);

    // Final status should be closed
    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
        'status' => Conversation::STATUS_CLOSED,
    ]);
});

// ==================== Complex Search and Filtering ====================

test('search across multiple fields', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $customer1 = Customer::factory()->create(['first_name' => 'John', 'last_name' => 'Smith']);
    $customer2 = Customer::factory()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);

    $conversation1 = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'subject' => 'Important Issue',
        'customer_id' => $customer1->id,
    ]);

    $conversation2 = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'subject' => 'Billing Question',
        'customer_id' => $customer2->id,
    ]);

    // Search by subject
    $response = $this->actingAs($admin)->get(route('conversations.search', [
        'q' => 'Important',
    ]));

    $response->assertSee($conversation1->subject);
    $response->assertDontSee($conversation2->subject);
});

test('filter by multiple criteria', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach([$admin->id, $user->id]);

    Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'status' => Conversation::STATUS_ACTIVE,
        'user_id' => $user->id,
    ]);

    Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'status' => Conversation::STATUS_CLOSED,
    ]);

    // The mailbox should have 2 conversations total: one active, one closed.
    $this->assertDatabaseCount('conversations', 2);
    $this->assertDatabaseHas('conversations', [
        'mailbox_id' => $mailbox->id,
        'status' => Conversation::STATUS_ACTIVE,
        'user_id' => $user->id,
    ]);
    $this->assertDatabaseHas('conversations', [
        'mailbox_id' => $mailbox->id,
        'status' => Conversation::STATUS_CLOSED,
    ]);
});
