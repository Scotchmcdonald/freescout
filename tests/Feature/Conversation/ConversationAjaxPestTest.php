<?php

use App\Models\Conversation;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $this->user = User::factory()->create(['role' => User::ROLE_USER]);

    $this->mailbox = Mailbox::factory()->create();
    $this->mailbox->users()->attach($this->admin->id);
    $this->mailbox->users()->attach($this->user->id);

    $this->inboxFolder = Folder::factory()->create([
        'mailbox_id' => $this->mailbox->id,
        'type' => Folder::TYPE_INBOX,
    ]);

    $this->conversation = Conversation::factory()->for($this->mailbox)->create([
        'folder_id' => $this->inboxFolder->id,
        'status' => Conversation::STATUS_ACTIVE,
    ]);
});

test('star action stars conversation', function () {
    $this->actingAs($this->user);

    $this->postJson(route('conversations.ajax'), [
        'action' => 'star',
        'conversation_id' => $this->conversation->id,
    ])->assertOk();

    expect($this->conversation->isStarredBy($this->user))->toBeTrue();
});

test('unstar action unstars conversation', function () {
    $this->actingAs($this->user);
    $this->conversation->star($this->user);

    $this->postJson(route('conversations.ajax'), [
        'action' => 'unstar',
        'conversation_id' => $this->conversation->id,
    ])->assertOk();

    expect($this->conversation->isStarredBy($this->user))->toBeFalse();
});

// ===== Viewer Tests =====

test('set viewer action updates cache', function () {
    $this->actingAs($this->user);
    Cache::flush();

    $this->postJson(route('conversations.ajax'), [
        'action' => 'set_viewer',
        'conversation_id' => $this->conversation->id,
        'replying' => false,
    ])->assertOk();

    $cache = Cache::get(Conversation::VIEWER_CACHE_KEY, []);
    expect($cache)->toHaveKey($this->conversation->id);
    expect($cache[$this->conversation->id])->toHaveKey($this->user->id);
    expect($cache[$this->conversation->id][$this->user->id]['r'])->toBeFalse();
});

test('set viewer with replying flag', function () {
    $this->actingAs($this->user);
    Cache::flush();

    $this->postJson(route('conversations.ajax'), [
        'action' => 'set_viewer',
        'conversation_id' => $this->conversation->id,
        'replying' => true,
    ])->assertOk();

    $cache = Cache::get(Conversation::VIEWER_CACHE_KEY, []);
    expect($cache)->toHaveKey($this->conversation->id);
    expect($cache[$this->conversation->id])->toHaveKey($this->user->id);
    expect($cache[$this->conversation->id][$this->user->id]['r'])->toBeTrue();
});

test('remove viewer action clears cache', function () {
    $this->actingAs($this->user);
    Conversation::setViewer($this->conversation->id, $this->user->id, false);

    $this->postJson(route('conversations.ajax'), [
        'action' => 'remove_viewer',
        'conversation_id' => $this->conversation->id,
    ])->assertOk();

    $cache = Cache::get(Conversation::VIEWER_CACHE_KEY, []);
    // Cache for this conversation might not be empty if other users are viewing,
    // strictly we check if THIS user is gone from THIS conversation's viewers
    if (isset($cache[$this->conversation->id])) {
        expect($cache[$this->conversation->id])->not->toHaveKey($this->user->id);
    } else {
        expect($cache)->not->toHaveKey($this->conversation->id);
    }
});

test('get viewers returns correct data', function () {
    $this->actingAs($this->user);
    Cache::flush();
    Conversation::setViewer($this->conversation->id, $this->admin->id, true);

    $response = $this->postJson(route('conversations.ajax'), [
        'action' => 'get_viewers',
        'conversation_ids' => [$this->conversation->id],
    ])->assertOk();

    $viewers = $response->json('viewers');
    // Structure might be complex depending on implementation,
    // usually keyed by conversation ID or just an array

    // Based on ConversationController logic: $viewers = Conversation::getViewersInfo(...)
    // getViewersInfo returns [convId => ['user' => ..., 'user_id' => ..., 'replying' => ...]]

    expect($viewers)->toHaveKey($this->conversation->id);
    expect($viewers[$this->conversation->id]['user_id'])->toBe($this->admin->id);
    expect($viewers[$this->conversation->id]['replying'])->toBeTrue();
});

// ===== Saved Search Tests =====

test('save search creates record', function () {
    $this->actingAs($this->user);

    $this->postJson(route('conversations.ajax'), [
        'action' => 'save_search',
        'name' => 'My Saved Search',
        'query' => 'test query',
        'filters' => ['status' => 1, 'mailbox' => $this->mailbox->id],
    ])->assertOk();

    $this->assertDatabaseHas('saved_searches', [
        'user_id' => $this->user->id,
        'name' => 'My Saved Search',
        'query' => 'test query',
    ]);
});

test('list saved searches', function () {
    $this->actingAs($this->user);
    SavedSearch::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Existing Search',
    ]);

    $response = $this->postJson(route('conversations.ajax'), [
        'action' => 'list_saved_searches',
    ])->assertOk();

    $response->assertJsonFragment(['name' => 'Existing Search']);
});

test('set default search', function () {
    $this->actingAs($this->user);
    $search = SavedSearch::factory()->create([
        'user_id' => $this->user->id,
        'is_default' => false,
    ]);

    $this->postJson(route('conversations.ajax'), [
        'action' => 'set_default_search',
        'search_id' => $search->id,
    ])->assertOk();

    expect($search->fresh()->is_default)->toBeTrue();
});

test('delete saved search', function () {
    $this->actingAs($this->user);
    $search = SavedSearch::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $this->postJson(route('conversations.ajax'), [
        'action' => 'delete_search',
        'search_id' => $search->id,
    ])->assertOk();

    $this->assertDatabaseMissing('saved_searches', ['id' => $search->id]);
});

test('reply returns json for ajax requests', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    $conversation = Conversation::factory()->for($mailbox)->create();

    $response = $this->actingAs($user)->postJson(route('conversations.reply', $conversation), [
        'body' => 'AJAX reply test',
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);
    $response->assertJsonStructure([
        'success',
        'thread' => ['id', 'body', 'user'],
    ]);

    // Verify the response data
    $data = $response->json();
    expect($data['success'])->toBeTrue();
    expect($data['thread']['body'])->toBe('<p>AJAX reply test</p>');
});

test('save draft via ajax', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    $conversation = Conversation::factory()->for($mailbox)->create();

    $response = $this->actingAs($user)->postJson(route('conversations.ajax'), [
        'action' => 'save_draft',
        'conversation_id' => $conversation->id,
        'body' => 'Draft message content',
        'to' => 'customer@example.com',
    ]);

    $response->assertOk();
    // Assuming controller returns 'status' => 'success'
});

test('discard draft via ajax', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    $conversation = Conversation::factory()->for($mailbox)->create();

    $response = $this->actingAs($user)->postJson(route('conversations.ajax'), [
        'action' => 'discard_draft',
        'conversation_id' => $conversation->id,
    ]);

    $response->assertOk();
});

test('invalid ajax action', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    $conversation = Conversation::factory()->for($mailbox)->create();

    $response = $this->actingAs($user)->postJson(route('conversations.ajax'), [
        'action' => 'invalid_action_xyz',
        'conversation_id' => $conversation->id,
    ]);

    expect($response->status() >= 400 || $response->json('error') !== null)->toBeTrue();
});

test('missing ajax action', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    // Auth required
    $response = $this->actingAs($user)->postJson(route('conversations.ajax'), []);

    expect($response->status() >= 400 || $response->json('error') !== null)->toBeTrue();
});

test('guest cannot access ajax actions', function () {
    $mailbox = Mailbox::factory()->create();
    $conversation = Conversation::factory()->for($mailbox)->create();

    $response = $this->postJson(route('conversations.ajax'), [
        'action' => 'follow',
        'conversation_id' => $conversation->id,
    ]);

    $response->assertUnauthorized();
});

test('unauthorized user cannot access ajax actions', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    // User NOT attached
    $conversation = Conversation::factory()->for($mailbox)->create();

    $response = $this->actingAs($user)->postJson(route('conversations.ajax'), [
        'action' => 'follow',
        'conversation_id' => $conversation->id,
    ]);

    $response->assertForbidden();
});
