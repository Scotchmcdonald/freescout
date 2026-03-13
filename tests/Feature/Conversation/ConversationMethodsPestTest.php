<?php

namespace Tests\Feature\Conversation;

use App\Models\Conversation;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $this->mailbox = Mailbox::factory()->create([
        'name' => 'Support',
        'email' => 'support@example.com',
    ]);

    $this->mailbox->users()->attach($this->user, ['access' => 30]); // ADMIN access

    // Create inbox folder
    Folder::factory()->create([
        'mailbox_id' => $this->mailbox->id,
        'type' => Folder::TYPE_INBOX,
        'name' => 'Inbox',
    ]);
});

test('admin can view create conversation form', function () {
    $this->actingAs($this->user);

    $response = $this->get(route('conversations.create', $this->mailbox));

    $response->assertOk();
    $response->assertViewIs('conversations.create');
    $response->assertViewHas('mailbox', function ($mailbox) {
        return $mailbox->id === $this->mailbox->id;
    });
    $response->assertViewHas('folders');
});

test('non-admin with mailbox access can view create form', function () {
    $regularUser = User::factory()->create(['role' => User::ROLE_USER]);
    $this->mailbox->users()->attach($regularUser, ['access' => 10]); // VIEW access

    $this->actingAs($regularUser);

    $response = $this->get(route('conversations.create', $this->mailbox));

    $response->assertOk();
    $response->assertViewIs('conversations.create');
});

test('user without mailbox access cannot view create form', function () {
    $unauthorizedUser = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($unauthorizedUser);

    $response = $this->get(route('conversations.create', $this->mailbox));

    $response->assertForbidden();
});

test('guest cannot view create form', function () {
    $response = $this->get(route('conversations.create', $this->mailbox));

    $response->assertRedirect(route('login'));
});

test('create form displays user folders only', function () {
    $this->actingAs($this->user);

    // Create a user-specific folder
    $userFolder = Folder::factory()->create([
        'mailbox_id' => $this->mailbox->id,
        'type' => Folder::TYPE_ASSIGNED,
        'user_id' => $this->user->id,
    ]);

    // Create another user's folder
    $otherUser = User::factory()->create();
    $otherUserFolder = Folder::factory()->create([
        'mailbox_id' => $this->mailbox->id,
        'type' => Folder::TYPE_MINE,
        'user_id' => $otherUser->id,
    ]);

    $response = $this->get(route('conversations.create', $this->mailbox));

    $response->assertOk();
    $response->assertViewHas('folders', function ($folders) use ($userFolder, $otherUserFolder) {
        $folderIds = $folders->pluck('id')->toArray();

        return in_array($userFolder->id, $folderIds) && ! in_array($otherUserFolder->id, $folderIds);
    });
});

test('ajax change status updates conversation', function () {
    $this->actingAs($this->user);

    $conversation = Conversation::factory()
        ->for($this->mailbox)
        ->create(['status' => 1]);

    $response = $this->postJson(route('conversations.ajax'), [
        'action' => 'change_status',
        'conversation_id' => $conversation->id,
        'status' => 2,
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
        'status' => 2,
    ]);
});

test('ajax change user assigns conversation', function () {
    $this->actingAs($this->user);

    $assignee = User::factory()->create();
    $conversation = Conversation::factory()
        ->for($this->mailbox)
        ->create(['user_id' => null]);

    $response = $this->postJson(route('conversations.ajax'), [
        'action' => 'change_user',
        'conversation_id' => $conversation->id,
        'user_id' => $assignee->id,
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
        'user_id' => $assignee->id,
    ]);
});

test('ajax change user with null user id unassigns conversation', function () {
    $this->actingAs($this->user);

    $assignee = User::factory()->create();
    $conversation = Conversation::factory()
        ->for($this->mailbox)
        ->create(['user_id' => $assignee->id]);

    $response = $this->postJson(route('conversations.ajax'), [
        'action' => 'change_user',
        'conversation_id' => $conversation->id,
        'user_id' => null,
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
        'user_id' => null,
    ]);
});

test('ajax change folder moves conversation', function () {
    $this->actingAs($this->user);

    $newFolder = Folder::factory()->create([
        'mailbox_id' => $this->mailbox->id,
        'type' => Folder::TYPE_SENT,
    ]);

    $conversation = Conversation::factory()
        ->for($this->mailbox)
        ->create();

    $response = $this->postJson(route('conversations.ajax'), [
        'action' => 'change_folder',
        'conversation_id' => $conversation->id,
        'folder_id' => $newFolder->id,
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
        'folder_id' => $newFolder->id,
    ]);
});

test('ajax delete soft deletes conversation', function () {
    $this->actingAs($this->user);

    $conversation = Conversation::factory()
        ->for($this->mailbox)
        ->create(['state' => 2]);

    $response = $this->postJson(route('conversations.ajax'), [
        'action' => 'delete',
        'conversation_id' => $conversation->id,
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
        'state' => 3, // Deleted state
    ]);
});

test('ajax requires conversation id', function () {
    $this->actingAs($this->user);

    $response = $this->postJson(route('conversations.ajax'), [
        'action' => 'change_status',
    ]);

    $response->assertStatus(400);
    $response->assertJson([
        'success' => false,
        'message' => 'Conversation ID required',
    ]);
});

test('ajax rejects invalid action', function () {
    $this->actingAs($this->user);

    $conversation = Conversation::factory()
        ->for($this->mailbox)
        ->create();

    $response = $this->postJson(route('conversations.ajax'), [
        'action' => 'invalid_action',
        'conversation_id' => $conversation->id,
    ]);

    $response->assertStatus(400);
    $response->assertJson([
        'success' => false,
        'message' => 'Invalid action',
    ]);
});

test('ajax with missing action parameter', function () {
    $this->actingAs($this->user);

    $conversation = Conversation::factory()
        ->for($this->mailbox)
        ->create();

    $response = $this->postJson(route('conversations.ajax'), [
        'conversation_id' => $conversation->id,
        // Missing 'action' parameter
    ]);

    $response->assertStatus(400);
    $response->assertJson([
        'success' => false,
        'message' => 'Invalid action',
    ]);
});

test('ajax unauthorized user gets forbidden', function () {
    $unauthorizedUser = User::factory()->create(['role' => User::ROLE_USER]);
    $this->actingAs($unauthorizedUser);

    $conversation = Conversation::factory()
        ->for($this->mailbox)
        ->create();

    $response = $this->postJson(route('conversations.ajax'), [
        'action' => 'change_status',
        'conversation_id' => $conversation->id,
        'status' => 2,
    ]);

    $response->assertStatus(403);
    $response->assertJson([
        'success' => false,
        'message' => 'Unauthorized',
    ]);
});

test('ajax handles non existent conversation', function () {
    $this->actingAs($this->user);

    $response = $this->postJson(route('conversations.ajax'), [
        'action' => 'change_status',
        'conversation_id' => 99999, // Non-existent ID
        'status' => 2,
    ]);

    $response->assertNotFound();
});

test('ajax change folder with non existent folder', function () {
    $this->actingAs($this->user);

    $conversation = Conversation::factory()
        ->for($this->mailbox)
        ->create();

    $response = $this->postJson(route('conversations.ajax'), [
        'action' => 'change_folder',
        'conversation_id' => $conversation->id,
        'folder_id' => 99999, // Non-existent folder
    ]);

    // Controller returns error due to foreign key constraint violation
    $response->assertStatus(500);
});

test('ajax handles sql injection attempt', function () {
    $this->actingAs($this->user);

    $conversation = Conversation::factory()
        ->for($this->mailbox)
        ->create();

    $response = $this->postJson(route('conversations.ajax'), [
        'action' => 'change_status',
        'conversation_id' => '1 OR 1=1',
        'status' => 2,
    ]);

    // Laravel's Eloquent should handle this safely
    $response->assertNotFound();
});

test('guest cannot clone conversation', function () {
    $conversation = Conversation::factory()
        ->for($this->mailbox)
        ->create();

    $thread = Thread::factory()->create([
        'conversation_id' => $conversation->id,
    ]);

    $response = $this->get(route('conversations.clone', [
        'mailbox' => $this->mailbox,
        'thread' => $thread,
    ]));

    $response->assertRedirect(route('login'));
});
