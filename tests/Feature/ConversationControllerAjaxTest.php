<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Tests\FeatureTestCase;

/**
 * Feature tests for ConversationController AJAX actions added during migration.
 */
class ConversationControllerAjaxTest extends FeatureTestCase
{
    protected User $admin;
    protected User $user;
    protected Mailbox $mailbox;
    protected Folder $inboxFolder;
    protected Folder $deletedFolder;
    protected Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->mailbox = Mailbox::factory()->create();
        $this->mailbox->users()->attach($this->admin->id);
        $this->mailbox->users()->attach($this->user->id);

        $this->inboxFolder = Folder::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'type' => Folder::TYPE_INBOX,
        ]);

        $this->deletedFolder = Folder::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'type' => Folder::TYPE_DELETED,
        ]);

        $this->conversation = Conversation::factory()->for($this->mailbox)->create([
            'folder_id' => $this->inboxFolder->id,
            'status' => Conversation::STATUS_ACTIVE,
        ]);
    }

    // ===== follow/unfollow tests =====

    public function test_follow_action_adds_user_as_follower(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'follow',
            'conversation_id' => $this->conversation->id,
        ]);

        $response->assertOk();
        $this->assertTrue($this->conversation->isUserFollowing($this->user));
    }

    public function test_unfollow_action_removes_user_as_follower(): void
    {
        $this->actingAs($this->user);
        $this->conversation->followers()->attach($this->user->id);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'unfollow',
            'conversation_id' => $this->conversation->id,
        ]);

        $response->assertOk();
        $this->assertFalse($this->conversation->isUserFollowing($this->user));
    }

    // ===== star/unstar tests =====

    public function test_star_action_stars_conversation(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'star',
            'conversation_id' => $this->conversation->id,
        ]);

        $response->assertOk();
        $this->assertTrue($this->conversation->isStarredBy($this->user));
    }

    public function test_unstar_action_unstars_conversation(): void
    {
        $this->actingAs($this->user);
        $this->conversation->star($this->user);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'unstar',
            'conversation_id' => $this->conversation->id,
        ]);

        $response->assertOk();
        $this->assertFalse($this->conversation->isStarredBy($this->user));
    }

    // ===== Bulk action tests =====

    public function test_bulk_change_status_changes_multiple_conversations(): void
    {
        $this->actingAs($this->admin);

        $conv2 = Conversation::factory()->for($this->mailbox)->create([
            'folder_id' => $this->inboxFolder->id,
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'bulk_change_status',
            'conversation_ids' => [$this->conversation->id, $conv2->id],
            'status' => Conversation::STATUS_CLOSED,
        ]);

        $response->assertOk();

        $this->conversation->refresh();
        $conv2->refresh();

        $this->assertEquals(Conversation::STATUS_CLOSED, $this->conversation->status);
        $this->assertEquals(Conversation::STATUS_CLOSED, $conv2->status);
    }

    public function test_bulk_change_user_assigns_conversations(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'bulk_change_user',
            'conversation_ids' => [$this->conversation->id],
            'user_id' => $this->user->id,
        ]);

        $response->assertOk();

        $this->conversation->refresh();
        $this->assertEquals($this->user->id, $this->conversation->user_id);
    }

    public function test_bulk_delete_moves_to_deleted_folder(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'bulk_delete',
            'conversation_ids' => [$this->conversation->id],
        ]);

        $response->assertOk();

        $this->conversation->refresh();
        $this->assertEquals(Conversation::STATE_DELETED, $this->conversation->state);
    }

    public function test_bulk_restore_restores_deleted_conversations(): void
    {
        $this->actingAs($this->admin);

        $this->conversation->state = Conversation::STATE_DELETED;
        $this->conversation->folder_id = $this->deletedFolder->id;
        $this->conversation->save();

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'bulk_restore',
            'conversation_ids' => [$this->conversation->id],
        ]);

        $response->assertOk();

        $this->conversation->refresh();
        $this->assertEquals(Conversation::STATE_PUBLISHED, $this->conversation->state);
    }

    public function test_bulk_move_moves_to_new_mailbox(): void
    {
        $this->actingAs($this->admin);

        $newMailbox = Mailbox::factory()->create();
        $newInbox = Folder::factory()->create([
            'mailbox_id' => $newMailbox->id,
            'type' => Folder::TYPE_INBOX,
        ]);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'bulk_move',
            'conversation_ids' => [$this->conversation->id],
            'mailbox_id' => $newMailbox->id,
        ]);

        $response->assertOk();

        $this->conversation->refresh();
        $this->assertEquals($newMailbox->id, $this->conversation->mailbox_id);
    }

    // ===== Viewer actions tests =====

    public function test_set_viewer_action(): void
    {
        $this->actingAs($this->user);
        Cache::flush();

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'set_viewer',
            'conversation_id' => $this->conversation->id,
            'replying' => false,
        ]);

        $response->assertOk();

        $cache = Cache::get(Conversation::VIEWER_CACHE_KEY, []);
        $this->assertArrayHasKey($this->conversation->id, $cache);
    }

    public function test_set_viewer_with_replying_flag(): void
    {
        $this->actingAs($this->user);
        Cache::flush();

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'set_viewer',
            'conversation_id' => $this->conversation->id,
            'replying' => true,
        ]);

        $response->assertOk();

        $cache = Cache::get(Conversation::VIEWER_CACHE_KEY, []);
        
        // Ensure the conversation ID exists in the cache
        $this->assertArrayHasKey($this->conversation->id, $cache);
        
        // Ensure the user ID exists in the conversation cache
        $this->assertArrayHasKey($this->user->id, $cache[$this->conversation->id]);
        
        // Check the replying flag
        $this->assertTrue($cache[$this->conversation->id][$this->user->id]['r']);
    }

    public function test_remove_viewer_action(): void
    {
        $this->actingAs($this->user);
        Conversation::setViewer($this->conversation->id, $this->user->id, false);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'remove_viewer',
            'conversation_id' => $this->conversation->id,
        ]);

        $response->assertOk();

        $cache = Cache::get(Conversation::VIEWER_CACHE_KEY, []);
        $this->assertArrayNotHasKey($this->conversation->id, $cache);
    }

    public function test_get_viewers_action(): void
    {
        $this->actingAs($this->user);
        Cache::flush();
        Conversation::setViewer($this->conversation->id, $this->admin->id, true);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'get_viewers',
            'conversation_ids' => [$this->conversation->id],
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['viewers']);
    }

    // ===== Save search tests =====

    public function test_save_search_creates_saved_search(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'save_search',
            'name' => 'My Saved Search',
            'query' => 'test query',
            'filters' => ['status' => 1, 'mailbox' => $this->mailbox->id],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('saved_searches', [
            'user_id' => $this->user->id,
            'name' => 'My Saved Search',
        ]);
    }

    public function test_delete_search_removes_saved_search(): void
    {
        $this->actingAs($this->user);

        $savedSearch = SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => 'Test Search',
            'query' => 'test',
        ]);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'delete_search',
            'search_id' => $savedSearch->id,
        ]);

        $response->assertOk();

        $this->assertDatabaseMissing('saved_searches', [
            'id' => $savedSearch->id,
        ]);
    }

    public function test_delete_search_prevents_deleting_other_users_search(): void
    {
        $this->actingAs($this->user);

        $otherUserSearch = SavedSearch::create([
            'user_id' => $this->admin->id,
            'name' => 'Admin Search',
            'query' => 'test',
        ]);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'delete_search',
            'search_id' => $otherUserSearch->id,
        ]);

        // Should not delete
        $this->assertDatabaseHas('saved_searches', [
            'id' => $otherUserSearch->id,
        ]);
    }

    public function test_list_saved_searches_returns_user_searches(): void
    {
        $this->actingAs($this->user);

        SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => 'Search 1',
            'query' => 'q1',
        ]);
        SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => 'Search 2',
            'query' => 'q2',
        ]);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'list_saved_searches',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['searches']);
    }

    public function test_set_default_search_sets_default(): void
    {
        $this->actingAs($this->user);

        $savedSearch = SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => 'Test Search',
            'query' => 'test',
            'is_default' => false,
        ]);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'set_default_search',
            'search_id' => $savedSearch->id,
        ]);

        $response->assertOk();

        $savedSearch->refresh();
        $this->assertTrue($savedSearch->is_default);
    }

    // ===== Draft tests =====

    public function test_save_draft_creates_draft(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'save_draft',
            'conversation_id' => $this->conversation->id,
            'body' => 'Draft message content',
            'to' => 'customer@example.com',
        ]);

        $response->assertOk();
    }

    public function test_discard_draft_removes_draft(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'discard_draft',
            'conversation_id' => $this->conversation->id,
        ]);

        $response->assertOk();
    }

    // ===== Authorization tests =====

    public function test_guest_cannot_access_ajax_actions(): void
    {
        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'follow',
            'conversation_id' => $this->conversation->id,
        ]);

        $response->assertUnauthorized();
    }

    public function test_user_cannot_access_conversation_in_unauthorized_mailbox(): void
    {
        $unauthorizedUser = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($unauthorizedUser);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'follow',
            'conversation_id' => $this->conversation->id,
        ]);

        $response->assertForbidden();
    }

    // ===== Invalid action tests =====

    public function test_invalid_action_returns_error(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'invalid_action_xyz',
            'conversation_id' => $this->conversation->id,
        ]);

        // Should return error or handle gracefully
        $this->assertTrue($response->status() >= 400 || $response->json('error') !== null);
    }

    public function test_missing_action_returns_error(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('conversations.ajax'), []);

        // Should return error
        $this->assertTrue($response->status() >= 400 || $response->json('error') !== null);
    }
}
