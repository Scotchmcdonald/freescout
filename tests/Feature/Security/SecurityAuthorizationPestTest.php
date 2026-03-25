<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Email;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    // Create 'user' with type 0 so they are not considered 'internal' by the admin middleware
    $this->user = User::factory()->create(['role' => User::ROLE_USER, 'type' => 0]);
    $this->unauthorizedUser = User::factory()->create(['role' => User::ROLE_USER, 'type' => 0]);

    $this->mailbox = Mailbox::factory()->create();
    $this->mailbox->users()->attach([$this->admin->id, $this->user->id]);
    // Note: unauthorizedUser is NOT attached to mailbox

    $this->folder = Folder::factory()->create([
        'mailbox_id' => $this->mailbox->id,
        'type' => Folder::TYPE_INBOX,
    ]);

    $this->customer = Customer::factory()->create();
    Email::factory()->create([
        'customer_id' => $this->customer->id,
        'email' => 'test@example.com',
    ]);

    $this->conversation = Conversation::factory()->for($this->mailbox)->create([
        'customer_id' => $this->customer->id,
        'folder_id' => $this->folder->id,
        'status' => Conversation::STATUS_ACTIVE,
    ]);
});

test('unauthorized user cannot access conversation ajax', function () {
    $this->actingAs($this->unauthorizedUser);

    $response = $this->postJson(route('conversations.ajax'), [
        'action' => 'follow',
        'conversation_id' => $this->conversation->id,
    ]);

    // Should be denied
    $isDenied = $response->status() === 403 || $response->json('success') === false;
    expect($isDenied)->toBeTrue();
});

test('unauthorized user cannot bulk modify conversations', function () {
    $this->actingAs($this->unauthorizedUser);

    $response = $this->postJson(route('conversations.ajax'), [
        'action' => 'bulk_change_status',
        'conversation_ids' => [$this->conversation->id],
        'status' => Conversation::STATUS_CLOSED,
    ]);

    $isDenied = $response->status() === 403 || $response->json('success') === false;
    expect($isDenied)->toBeTrue();

    // Verify conversation was not modified
    $this->conversation->refresh();
    expect($this->conversation->status)->toBe(Conversation::STATUS_ACTIVE);
});

test('non admin cannot access system tools', function () {
    $this->actingAs($this->user);

    $response = $this->get(route('system.tools'));

    // Should require admin
    $isDenied = $response->status() === 403 || $response->isRedirect();
    expect($isDenied)->toBeTrue();
});

test('non admin cannot execute system tools', function () {
    $this->actingAs($this->user);

    $response = $this->postJson(route('system.tools.execute'), [
        'action' => 'clear_cache',
    ]);

    $isDenied = $response->status() === 403 || $response->json('success') === false;
    expect($isDenied)->toBeTrue();
});

test('non admin cannot clear logs', function () {
    $this->actingAs($this->user);

    $response = $this->postJson(route('system.logs.clear'), [
        'log_name' => 'system',
    ]);

    expect($response->status())->toBe(403);
});

test('module license operations require admin', function () {
    $this->actingAs($this->user);

    $response = $this->postJson(route('modules.ajax'), [
        'action' => 'activate_license',
        'module' => 'TestModule',
        'license' => 'test-license-key',
    ]);

    $isDenied = $response->status() === 403 || $response->json('success') === false;
    expect($isDenied)->toBeTrue();
});

test('module update operations require admin', function () {
    $this->actingAs($this->user);

    $response = $this->postJson(route('modules.ajax'), [
        'action' => 'update_module',
        'module' => 'TestModule',
    ]);

    $isDenied = $response->status() === 403 || $response->json('success') === false;
    expect($isDenied)->toBeTrue();
});

test('user cannot delete admin', function () {
    $this->actingAs($this->user);

    $response = $this->delete(route('users.destroy', $this->admin));

    $isDenied = $response->status() === 403 || $response->isRedirect();
    expect($isDenied)->toBeTrue();

    // Admin should still exist
    $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
});

test('user cannot escalate own privileges', function () {
    $this->actingAs($this->user);

    $response = $this->put(route('users.update', $this->user), [
        'role' => User::ROLE_ADMIN,
    ]);

    // Should deny or ignore role change
    $this->user->refresh();
    expect($this->user->role)->toBe(User::ROLE_USER);
});

test('user cannot access other users saved searches', function () {
    if (! class_exists(\App\Models\SavedSearch::class)) {
        $this->markTestSkipped('SavedSearch model not available');
    }

    // Create search for other user
    $otherUser = User::factory()->create();
    $search = \App\Models\SavedSearch::create([
        'user_id' => $otherUser->id,
        'name' => 'Private Search',
        'query' => 'test',
        'filters' => [],
    ]);

    $this->actingAs($this->user);

    $response = $this->postJson(route('conversations.ajax'), [
        'action' => 'delete_search',
        'search_id' => $search->id,
    ]);

    // Should deny
    $isDenied = $response->status() === 403 || $response->json('success') === false;
    expect($isDenied)->toBeTrue();

    // Verify search still exists
    $this->assertDatabaseHas('saved_searches', ['id' => $search->id]);
});

test('user cannot modify other users drafts', function () {
    // Create a draft for another user
    $otherUser = User::factory()->create();
    $this->mailbox->users()->attach($otherUser->id);

    $this->actingAs($otherUser);
    $this->postJson(route('conversations.ajax'), [
        'action' => 'save_draft',
        'conversation_id' => $this->conversation->id,
        'body' => 'Other user draft',
    ]);

    // Now try to discard as different user
    $this->actingAs($this->user);
    $response = $this->postJson(route('conversations.ajax'), [
        'action' => 'discard_draft',
        'conversation_id' => $this->conversation->id,
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('threads', [
        'conversation_id' => $this->conversation->id,
        'user_id' => $otherUser->id,
        'type' => \App\Models\Thread::TYPE_DRAFT,
        'state' => \App\Models\Thread::STATE_DRAFT,
        'body' => 'Other user draft',
    ]);
    $this->assertDatabaseMissing('threads', [
        'conversation_id' => $this->conversation->id,
        'user_id' => $this->user->id,
        'type' => \App\Models\Thread::TYPE_DRAFT,
        'state' => \App\Models\Thread::STATE_DRAFT,
    ]);
});

test('guest cannot access settings routes', function () {
    $response = $this->get(route('settings'));
    $response->assertRedirect(route('login'));

    // Verify no settings data is leaked
    $this->assertStringNotContainsString('company_name', $response->getContent());
    $this->assertStringNotContainsString('option', $response->getContent());
});

test('guest cannot access system routes', function () {
    $response = $this->get(route('system'));
    $response->assertRedirect(route('login'));
});

test('non admin cannot update settings', function () {
    $response = $this->actingAs($this->user)->post(route('settings.update'), [
        'company_name' => 'Hacked Company',
    ]);

    $response->assertForbidden();

    // Verify the setting was not updated
    $this->assertDatabaseMissing('options', [
        'name' => 'company_name',
        'value' => 'Hacked Company',
    ]);

    // Verify no sensitive data is leaked in the forbidden response
    $content = $response->getContent();
    $this->assertStringNotContainsString('Hacked Company', $content);
});

test('non admin cannot access email settings', function () {
    $response = $this->actingAs($this->user)->get(route('settings.email'));
    $response->assertForbidden();
});

test('non admin cannot update email settings', function () {
    $response = $this->actingAs($this->user)->post(route('settings.email.update'), [
        'mail_driver' => 'smtp',
        'mail_from_address' => 'hacker@example.com',
        'mail_from_name' => 'Hacker',
    ]);

    $response->assertForbidden();
});

test('non admin cannot clear cache', function () {
    $response = $this->actingAs($this->user)->post(route('settings.cache.clear'));
    $response->assertForbidden();
});

test('non admin cannot run migrations', function () {
    $response = $this->actingAs($this->user)->post(route('settings.migrate'));
    $response->assertForbidden();
});

test('non admin cannot access system diagnostics', function () {
    $response = $this->actingAs($this->user)->get(route('system.diagnostics'));
    $response->assertForbidden();
});

test('system ajax requires admin', function () {
    $response = $this->actingAs($this->user)->post(route('system.ajax'), [
        'action' => 'system_info',
    ]);

    $response->assertForbidden();
});
