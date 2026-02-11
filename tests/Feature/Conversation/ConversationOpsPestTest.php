<?php

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Email;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $this->user = User::factory()->create(['role' => User::ROLE_USER]);
    $this->mailbox = Mailbox::factory()->create();
    $this->mailbox->users()->attach([$this->admin->id, $this->user->id]);

    $this->inboxFolder = Folder::factory()->create([
        'mailbox_id' => $this->mailbox->id,
        'type' => Folder::TYPE_INBOX,
    ]);

    $this->deletedFolder = Folder::factory()->create([
        'mailbox_id' => $this->mailbox->id,
        'type' => Folder::TYPE_DELETED,
    ]);

    $this->customer = Customer::factory()->create();
    Email::factory()->create(['customer_id' => $this->customer->id, 'email' => 'customer@example.com']);

    $this->conversation = Conversation::factory()->for($this->mailbox)->create([
        'customer_id' => $this->customer->id,
        'folder_id' => $this->inboxFolder->id,
        'status' => Conversation::STATUS_ACTIVE,
        'state' => Conversation::STATE_PUBLISHED,
    ]);
});

test('bulk change status validates status value', function () {
    $this->actingAs($this->admin)->postJson(route('conversations.ajax'), [
        'action' => 'bulk_change_status',
        'conversation_ids' => [$this->conversation->id],
        'status' => 999,
    ])->assertStatus(400); // Or expect loose check like the original test? Originally >= 400
});

test('bulk change user validates user exists', function () {
    $this->actingAs($this->admin)->postJson(route('conversations.ajax'), [
        'action' => 'bulk_change_user',
        'conversation_ids' => [$this->conversation->id],
        'user_id' => 99999,
    ])->assertStatus(400);
});

test('bulk delete with empty array', function () {
    $this->actingAs($this->admin)->postJson(route('conversations.ajax'), [
        'action' => 'bulk_delete',
        'conversation_ids' => [],
    ])->assertStatus(400);
});

test('bulk restore only affects deleted conversations', function () {
    $activeConv = Conversation::factory()->for($this->mailbox)->create([
        'folder_id' => $this->inboxFolder->id,
        'state' => Conversation::STATE_PUBLISHED,
    ]);
    
    $deletedConv = Conversation::factory()->for($this->mailbox)->create([
        'folder_id' => $this->deletedFolder->id,
        'state' => Conversation::STATE_DELETED,
    ]);

    $this->actingAs($this->admin)->postJson(route('conversations.ajax'), [
        'action' => 'bulk_restore',
        'conversation_ids' => [$activeConv->id, $deletedConv->id],
    ])->assertOk();

    expect($activeConv->refresh()->state)->toBe(Conversation::STATE_PUBLISHED);
    expect($deletedConv->refresh()->state)->toBe(Conversation::STATE_PUBLISHED); // Should match published?
    // Note: Restoration logic typically moves it to inbox/published.
});

test('bulk move validates target mailbox', function () {
    $this->actingAs($this->admin)->postJson(route('conversations.ajax'), [
        'action' => 'bulk_move',
        'conversation_ids' => [$this->conversation->id],
        'mailbox_id' => 99999,
    ])->assertStatus(400);
});

test('follow same conversation twice is idempotent', function () {
    $this->actingAs($this->user);

    $this->postJson(route('conversations.ajax'), [
        'action' => 'follow',
        'conversation_id' => $this->conversation->id,
    ]);

    $this->postJson(route('conversations.ajax'), [
        'action' => 'follow',
        'conversation_id' => $this->conversation->id,
    ])->assertOk();

    expect($this->conversation->followers()->where('user_id', $this->user->id)->count())->toBe(1);
});

test('unfollow conversation not following is graceful', function () {
    $this->actingAs($this->user);
    $this->postJson(route('conversations.ajax'), [
        'action' => 'unfollow',
        'conversation_id' => $this->conversation->id,
    ])->assertOk();
});

test('follow requires conversation access', function () {
    $other = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($other)->postJson(route('conversations.ajax'), [
        'action' => 'follow',
        'conversation_id' => $this->conversation->id,
    ])->assertForbidden(); // Or unauthorized? Original comment said "Should deny access"
});

test('create phone conversation validates required fields', function () {
    $this->actingAs($this->user)->postJson(route('conversations.ajax'), [
        'action' => 'create_phone_conversation',
        'mailbox_id' => $this->mailbox->id,
        // Missing customer info
    ])->assertStatus(422);
});

test('create phone conversation with new customer', function () {
    $response = $this->actingAs($this->user)->postJson(route('conversations.ajax'), [
        'action' => 'create_phone_conversation',
        'mailbox_id' => $this->mailbox->id,
        'customer_email' => 'phonecust@example.com',
        'customer_name' => 'Phone Customer',
        'subject' => 'Phone conversation',
        'body' => 'Call notes',
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]); 
});

test('merge search with empty query', function () {
    $response = $this->actingAs($this->user)->postJson(route('conversations.ajax'), [
        'action' => 'merge_search',
        'conversation_id' => $this->conversation->id,
        'q' => '',
    ]);

    $response->assertOk();
    expect($response->json('results') ?? [])->toBeArray();
});

test('merge search excludes self', function () {
    // Create another conversation with similar subject
    $otherConv = Conversation::factory()->for($this->mailbox)->create([
        'subject' => $this->conversation->subject,
        'folder_id' => $this->inboxFolder->id,
    ]);

    $response = $this->actingAs($this->user)->postJson(route('conversations.ajax'), [
        'action' => 'merge_search',
        'conversation_id' => $this->conversation->id,
        'q' => $this->conversation->subject,
    ]);

    $response->assertOk();
    
    $resultIds = collect($response->json('results'))->pluck('id')->toArray();
    expect($resultIds)->not->toContain($this->conversation->id);
    // Should ideally contain otherConv if search works, but we test exclusion logic mainly
});

test('merge validates target conversation', function () {
    $response = $this->actingAs($this->user)->postJson(route('conversations.ajax'), [
        'action' => 'merge',
        'conversation_id' => $this->conversation->id,
        'target_id' => 99999, // Non-existent
    ]);

    expect($response->status() >= 400 || $response->json('status') === 'error')->toBeTrue();
});

test('change customer with new email', function () {
    $response = $this->actingAs($this->admin)->postJson(route('conversations.ajax'), [
        'action' => 'change_customer',
        'conversation_id' => $this->conversation->id,
        'customer_email' => 'brandnew@example.com',
    ]);

    $response->assertOk();
});

test('change customer validates email format', function () {
    $response = $this->actingAs($this->admin)->postJson(route('conversations.ajax'), [
        'action' => 'change_customer',
        'conversation_id' => $this->conversation->id,
        'customer_email' => 'invalid-email',
    ]);

    expect($response->status() >= 400 || $response->json('status') === 'error')->toBeTrue();
});

test('empty folder only works on deletable folders', function () {
    // Try to empty inbox (should be prevented)
    $response = $this->actingAs($this->admin)->postJson(route('folders.empty', $this->inboxFolder));

    expect($response->status() === 403 || 
           $response->json('status') === 'error' ||
           $response->json('success') === false ||
           $response->json('count') === 0)->toBeTrue();
});

test('empty folder on spam folder', function () {
    $spamFolder = Folder::factory()->create(['mailbox_id' => $this->mailbox->id, 'type' => Folder::TYPE_SPAM]);
    Conversation::factory()->for($this->mailbox)->create(['folder_id' => $spamFolder->id]);

    $response = $this->actingAs($this->admin)->postJson(route('folders.empty', $spamFolder));

    $response->assertOk();
    // Verify empty
    expect(Conversation::where('folder_id', $spamFolder->id)->count())->toBe(0);
});

test('empty folder on already empty folder', function () {
    $spamFolder = Folder::factory()->create(['mailbox_id' => $this->mailbox->id, 'type' => Folder::TYPE_SPAM]);
    // No conversations

    $response = $this->actingAs($this->admin)->postJson(route('folders.empty', $spamFolder));

    $response->assertOk();
});
