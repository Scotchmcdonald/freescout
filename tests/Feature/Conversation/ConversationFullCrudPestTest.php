<?php

namespace Tests\Feature\Conversation;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->mailbox = Mailbox::factory()->create();
    $this->mailbox->users()->attach($this->user->id);
});

// ===== INDEX TESTS =====

test('index requires authentication', function () {
    $response = $this->get(route('conversations.index', ['mailbox' => 1]));

    $response->assertRedirect(route('login'));
});

test('index returns view for authenticated user', function () {
    $response = $this->actingAs($this->user)->get(route('conversations.index', ['mailbox' => $this->mailbox->id]));

    $response->assertOk();
    $response->assertViewIs('conversations.index');
});

test('index with mailbox filter', function () {
    $response = $this->actingAs($this->user)->get(route('conversations.index', ['mailbox' => $this->mailbox->id]));

    $response->assertOk();
});

// ===== SHOW TESTS =====

test('show requires authentication', function () {
    $conversation = Conversation::factory()->create();

    $response = $this->get(route('conversations.show', $conversation));

    $response->assertRedirect(route('login'));
});

test('show returns view for authorized user', function () {
    $conversation = Conversation::factory()->create(['mailbox_id' => $this->mailbox->id]);

    $response = $this->actingAs($this->user)->get(route('conversations.show', $conversation));

    $response->assertOk();
    $response->assertViewIs('conversations.show');
});

test('show forbids unauthorized user', function () {
    $unauthorizedUser = User::factory()->create(['role' => User::ROLE_USER]);
    $conversation = Conversation::factory()->create();

    $response = $this->actingAs($unauthorizedUser)->get(route('conversations.show', $conversation));

    $response->assertForbidden();
});

test('show admin can view any conversation', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $conversation = Conversation::factory()->create();

    $response = $this->actingAs($admin)->get(route('conversations.show', $conversation));

    $response->assertOk();
});

test('show with nonexistent conversation returns 404', function () {
    $response = $this->actingAs($this->user)->get('/conversations/99999');

    $response->assertNotFound();
});

// ===== CREATE TESTS =====

test('create requires authentication', function () {
    $response = $this->get(route('conversations.create', ['mailbox_id' => 1]));

    $response->assertRedirect(route('login'));
});

test('create returns view for authenticated user', function () {
    $response = $this->actingAs($this->user)->get(route('conversations.create', ['mailbox_id' => $this->mailbox->id]));

    $response->assertOk();
    $response->assertViewIs('conversations.create');
});

// ===== STORE TESTS =====

test('store requires authentication', function () {
    $response = $this->post(route('conversations.store', ['mailbox' => 1]), [
        'subject' => 'Test',
        'body' => 'Test body',
    ]);

    $response->assertRedirect(route('login'));
});

test('store creates conversation with valid data', function () {
    $customer = Customer::factory()->create();

    $response = $this->actingAs($this->user)->post(route('conversations.store', ['mailbox' => $this->mailbox->id]), [
        'mailbox_id' => $this->mailbox->id,
        'customer_id' => $customer->id,
        'subject' => 'Test Subject',
        'body' => 'Test body content',
        'to' => ['test@example.com'],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('conversations', [
        'subject' => 'Test Subject',
        'mailbox_id' => $this->mailbox->id,
    ]);
});

test('store validates subject required', function () {
    $response = $this->actingAs($this->user)->post(route('conversations.store', ['mailbox' => $this->mailbox->id]), [
        'body' => 'Test',
        'to' => ['test@example.com'],
    ]);

    $response->assertSessionHasErrors('subject');
});

test('store validates body required', function () {
    $response = $this->actingAs($this->user)->post(route('conversations.store', ['mailbox' => $this->mailbox->id]), [
        'subject' => 'Test',
        'to' => ['test@example.com'],
    ]);

    $response->assertSessionHasErrors('body');
});

test('store with long subject', function () {
    $customer = Customer::factory()->create();
    $longSubject = str_repeat('a', 1000);

    $response = $this->actingAs($this->user)->post(route('conversations.store', ['mailbox' => $this->mailbox->id]), [
        'mailbox_id' => $this->mailbox->id,
        'customer_id' => $customer->id,
        'subject' => $longSubject,
        'body' => 'Test body',
        'to' => ['test@example.com'],
    ]);

    $response->assertSessionHasErrors('subject');
});

// ===== UPDATE TESTS =====

test('update requires authentication', function () {
    $conversation = Conversation::factory()->create();

    $response = $this->patch(route('conversations.update', $conversation), [
        'subject' => 'Updated',
    ]);

    $response->assertRedirect(route('login'));
});

test('update updates conversation status', function () {
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $this->mailbox->id,
        'status' => 1,
    ]);

    $response = $this->actingAs($this->user)->patch(route('conversations.update', $conversation), [
        'status' => 2,
    ]);

    $response->assertRedirect();
    $this->assertEquals(2, $conversation->fresh()->status);
});

test('update forbids unauthorized user', function () {
    $unauthorizedUser = User::factory()->create(['role' => User::ROLE_USER]);
    $conversation = Conversation::factory()->create();

    $response = $this->actingAs($unauthorizedUser)->patch(route('conversations.update', $conversation), [
        'subject' => 'Updated',
    ]);

    $response->assertForbidden();
});

test('update preserves unchanged fields', function () {
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $this->mailbox->id,
        'subject' => 'Original',
        'status' => 1,
    ]);

    $originalStatus = $conversation->status;
    $folder = Folder::factory()->create(['mailbox_id' => $this->mailbox->id]);

    $response = $this->actingAs($this->user)->patch(route('conversations.update', $conversation), [
        'folder_id' => $folder->id,
    ]);

    $response->assertRedirect();
    $this->assertEquals($originalStatus, $conversation->fresh()->status);
});

// ===== REPLY TESTS =====

test('reply requires authentication', function () {
    $conversation = Conversation::factory()->create();

    $response = $this->post(route('conversations.reply', $conversation), [
        'body' => 'Reply text',
    ]);

    $response->assertRedirect(route('login'));
});

test('reply creates thread', function () {
    $conversation = Conversation::factory()->create(['mailbox_id' => $this->mailbox->id]);

    $response = $this->actingAs($this->user)->post(route('conversations.reply', $conversation), [
        'body' => 'Reply content',
        'type' => Thread::TYPE_MESSAGE,
    ]);

    $response->assertRedirect();

    // Retrieve the created thread to check body content flexibly
    $thread = Thread::where('conversation_id', $conversation->id)->first();

    expect($thread)->not->toBeNull();
    expect($thread->body)->toContain('Reply content');
});

test('reply validates body required', function () {
    $conversation = Conversation::factory()->create(['mailbox_id' => $this->mailbox->id]);

    $response = $this->actingAs($this->user)->post(route('conversations.reply', $conversation), []);

    $response->assertSessionHasErrors('body');
});

test('reply with empty body fails validation', function () {
    $conversation = Conversation::factory()->create(['mailbox_id' => $this->mailbox->id]);

    $response = $this->actingAs($this->user)->post(route('conversations.reply', $conversation), [
        'body' => '',
    ]);

    $response->assertSessionHasErrors('body');
});

// ===== SEARCH TESTS =====

test('search requires authentication', function () {
    $response = $this->get(route('conversations.search', ['q' => 'test']));

    $response->assertRedirect(route('login'));
});

test('search returns results', function () {
    Conversation::factory()->create([
        'mailbox_id' => $this->mailbox->id,
        'subject' => 'Searchable Subject',
    ]);

    $response = $this->actingAs($this->user)->get(route('conversations.search', ['q' => 'Searchable']));

    $response->assertOk();
});

test('search with no results', function () {
    $response = $this->actingAs($this->user)->get(route('conversations.search', ['q' => 'nonexistentquery123']));

    $response->assertOk();
});

// ===== DESTROY TESTS =====

test('destroy requires authentication', function () {
    $conversation = Conversation::factory()->create();

    $response = $this->delete(route('conversations.destroy', $conversation));

    $response->assertRedirect(route('login'));
});

test('destroy requires admin', function () {
    $regularUser = User::factory()->create(['role' => User::ROLE_USER]);
    $conversation = Conversation::factory()->create();

    $response = $this->actingAs($regularUser)->delete(route('conversations.destroy', $conversation));

    $response->assertForbidden();
});

test('destroy deletes conversation for admin', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $conversation = Conversation::factory()->create();

    $response = $this->actingAs($admin)->delete(route('conversations.destroy', $conversation));

    $response->assertRedirect();
    $this->assertSoftDeleted('conversations', ['id' => $conversation->id]);
});

// ===== MOVE TESTS =====

test('move requires authentication', function () {
    $conversation = Conversation::factory()->create();
    $folder = Folder::factory()->create();

    // The legacy test used update() alias or route, but distinct 'move' endpoint is tested here
    // Checking route('conversations.move') specifically
    $response = $this->post(route('conversations.move', $conversation), [
        'mailbox_id' => 1,
    ]);

    $response->assertRedirect(route('login'));
});

test('move changes conversation mailbox', function () {
    $targetMailbox = Mailbox::factory()->create();
    $targetMailbox->users()->attach($this->user->id);

    $conversation = Conversation::factory()->create([
        'mailbox_id' => $this->mailbox->id,
    ]);

    $response = $this->actingAs($this->user)->post(route('conversations.move', $conversation), [
        'mailbox_id' => $targetMailbox->id,
    ]);

    $response->assertRedirect();
    $this->assertEquals($targetMailbox->id, $conversation->fresh()->mailbox_id);
});

test('admin can perform all operations', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $conversation = Conversation::factory()->create();

    $showResponse = $this->actingAs($admin)->get(route('conversations.show', $conversation));
    $updateResponse = $this->actingAs($admin)->patch(route('conversations.update', $conversation), [
        'subject' => 'Admin Update',
    ]);

    $showResponse->assertOk();
    $updateResponse->assertRedirect();
});

// ===== AJAX TESTS (Brief check, fully covered in MethodsTest) =====

test('ajax requires authentication', function () {
    $conversation = Conversation::factory()->create();

    $response = $this->postJson(route('conversations.ajax', $conversation), [
        'action' => 'test',
    ]);

    $response->assertUnauthorized();
});

test('ajax returns json response', function () {
    $conversation = Conversation::factory()->create(['mailbox_id' => $this->mailbox->id]);

    $response = $this->actingAs($this->user)->postJson(route('conversations.ajax'), [
        'action' => 'change_status',
        'status' => 2,
        'conversation_id' => $conversation->id,
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);
});
