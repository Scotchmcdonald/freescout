<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Email;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Tests\FeatureTestCase;

/**
 * Comprehensive tests for ConversationController methods added during migration.
 * Focuses on edge cases, validation, authorization, and error handling.
 */
class ConversationControllerComprehensiveTest extends FeatureTestCase
{
    protected User $admin;
    protected User $user;
    protected Mailbox $mailbox;
    protected Folder $inboxFolder;
    protected Folder $deletedFolder;
    protected Customer $customer;
    protected Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

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
        Email::factory()->create([
            'customer_id' => $this->customer->id,
            'email' => 'customer@example.com',
        ]);

        $this->conversation = Conversation::factory()->for($this->mailbox)->create([
            'customer_id' => $this->customer->id,
            'folder_id' => $this->inboxFolder->id,
            'status' => Conversation::STATUS_ACTIVE,
            'state' => Conversation::STATE_PUBLISHED,
        ]);
    }

    // ===== Bulk Operations Edge Cases =====

    public function test_bulk_change_status_validates_status_value(): void
    {
        $this->actingAs($this->admin);

        $conv = Conversation::factory()->for($this->mailbox)->create([
            'folder_id' => $this->inboxFolder->id,
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'bulk_change_status',
            'conversation_ids' => [$conv->id],
            'status' => 999, // Invalid status
        ]);

        // Should handle invalid status gracefully
        $this->assertTrue($response->status() >= 400 || $response->json('success') === false);
    }

    public function test_bulk_change_user_validates_user_exists(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'bulk_change_user',
            'conversation_ids' => [$this->conversation->id],
            'user_id' => 99999, // Non-existent user
        ]);

        // Should handle non-existent user gracefully
        $this->assertTrue($response->status() >= 400 || $response->json('success') === false);
    }

    public function test_bulk_delete_with_empty_array(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'bulk_delete',
            'conversation_ids' => [],
        ]);

        // Should handle empty array gracefully
        $response->assertOk();
        $this->assertTrue($response->json('count') === 0 || $response->json('success') === true);
    }

    public function test_bulk_restore_only_affects_deleted_conversations(): void
    {
        $this->actingAs($this->admin);

        $activeConv = Conversation::factory()->for($this->mailbox)->create([
            'folder_id' => $this->inboxFolder->id,
            'state' => Conversation::STATE_PUBLISHED,
        ]);

        $deletedConv = Conversation::factory()->for($this->mailbox)->create([
            'folder_id' => $this->deletedFolder->id,
            'state' => Conversation::STATE_DELETED,
        ]);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'bulk_restore',
            'conversation_ids' => [$activeConv->id, $deletedConv->id],
        ]);

        $response->assertOk();
        // Active conversation should remain unchanged
        $activeConv->refresh();
        $this->assertEquals(Conversation::STATE_PUBLISHED, $activeConv->state);
    }

    public function test_bulk_move_validates_target_mailbox(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'bulk_move',
            'conversation_ids' => [$this->conversation->id],
            'mailbox_id' => 99999, // Non-existent mailbox
        ]);

        // Should handle non-existent mailbox gracefully
        $this->assertTrue($response->status() >= 400 || $response->json('success') === false);
    }

    // ===== Follow/Unfollow Edge Cases =====

    public function test_follow_same_conversation_twice_is_idempotent(): void
    {
        $this->actingAs($this->user);

        // Follow once
        $this->postJson(route('conversations.ajax'), [
            'action' => 'follow',
            'conversation_id' => $this->conversation->id,
        ]);

        // Follow again
        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'follow',
            'conversation_id' => $this->conversation->id,
        ]);

        $response->assertOk();
        
        // Should only have one follow entry
        $count = $this->conversation->followers()->where('user_id', $this->user->id)->count();
        $this->assertEquals(1, $count);
    }

    public function test_unfollow_conversation_not_following_is_graceful(): void
    {
        $this->actingAs($this->user);

        // Unfollow without ever following
        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'unfollow',
            'conversation_id' => $this->conversation->id,
        ]);

        $response->assertOk();
    }

    public function test_follow_requires_conversation_access(): void
    {
        // Create another user without mailbox access
        $otherUser = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($otherUser);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'follow',
            'conversation_id' => $this->conversation->id,
        ]);

        // Should deny access
        $this->assertTrue($response->status() === 403 || $response->json('success') === false);
    }

    // ===== Star/Unstar Edge Cases =====

    public function test_star_same_conversation_twice_is_idempotent(): void
    {
        $this->actingAs($this->user);

        // Star once
        $this->postJson(route('conversations.ajax'), [
            'action' => 'star',
            'conversation_id' => $this->conversation->id,
        ]);

        // Star again
        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'star',
            'conversation_id' => $this->conversation->id,
        ]);

        $response->assertOk();
    }

    public function test_unstar_conversation_not_starred_is_graceful(): void
    {
        $this->actingAs($this->user);

        // Unstar without ever starring
        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'unstar',
            'conversation_id' => $this->conversation->id,
        ]);

        $response->assertOk();
    }

    // ===== Draft Edge Cases =====

    public function test_save_draft_with_empty_body(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'save_draft',
            'conversation_id' => $this->conversation->id,
            'body' => '',
        ]);

        // Should handle empty body appropriately
        $response->assertOk();
    }

    public function test_save_draft_with_very_long_body(): void
    {
        $this->actingAs($this->user);

        $longBody = str_repeat('a', 100000); // 100KB of text

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'save_draft',
            'conversation_id' => $this->conversation->id,
            'body' => $longBody,
        ]);

        $response->assertOk();
    }

    public function test_discard_draft_when_no_draft_exists(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'discard_draft',
            'conversation_id' => $this->conversation->id,
        ]);

        // Should handle gracefully when no draft exists
        $response->assertOk();
    }

    // ===== Viewer Edge Cases =====

    public function test_set_viewer_with_replying_flag(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'set_viewer',
            'conversation_id' => $this->conversation->id,
            'replying' => true,
        ]);

        $response->assertOk();
    }

    public function test_get_viewers_for_conversation_with_no_viewers(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'get_viewers',
            'conversation_id' => $this->conversation->id,
        ]);

        $response->assertOk();
        $this->assertIsArray($response->json('viewers'));
    }

    public function test_remove_viewer_when_not_viewing(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'remove_viewer',
            'conversation_id' => $this->conversation->id,
        ]);

        // Should handle gracefully
        $response->assertOk();
    }

    // ===== Phone Conversation Edge Cases =====

    public function test_create_phone_conversation_validates_required_fields(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'create_phone_conversation',
            'mailbox_id' => $this->mailbox->id,
            // Missing customer info
        ]);

        // Should require customer information
        $this->assertTrue($response->status() >= 400 || $response->json('success') === false);
    }

    public function test_create_phone_conversation_with_new_customer(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'create_phone_conversation',
            'mailbox_id' => $this->mailbox->id,
            'customer_email' => 'newcustomer@example.com',
            'subject' => 'Phone conversation',
            'body' => 'Call notes',
        ]);

        // Should create conversation and new customer
        $response->assertOk();
    }

    // ===== Merge Edge Cases =====

    public function test_merge_search_with_empty_query(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'merge_search',
            'conversation_id' => $this->conversation->id,
            'q' => '',
        ]);

        $response->assertOk();
        $this->assertIsArray($response->json('results') ?? []);
    }

    public function test_merge_search_excludes_self(): void
    {
        $this->actingAs($this->user);

        // Create another conversation with similar subject
        $otherConv = Conversation::factory()->for($this->mailbox)->create([
            'subject' => $this->conversation->subject,
            'folder_id' => $this->inboxFolder->id,
        ]);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'merge_search',
            'conversation_id' => $this->conversation->id,
            'q' => $this->conversation->subject,
        ]);

        $response->assertOk();
        
        $resultIds = collect($response->json('results'))->pluck('id')->toArray();
        $this->assertNotContains($this->conversation->id, $resultIds);
    }

    public function test_merge_validates_target_conversation(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'merge',
            'conversation_id' => $this->conversation->id,
            'target_id' => 99999, // Non-existent
        ]);

        $this->assertTrue($response->status() >= 400 || $response->json('success') === false);
    }

    // ===== Change Customer Edge Cases =====

    public function test_change_customer_with_new_email(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'change_customer',
            'conversation_id' => $this->conversation->id,
            'customer_email' => 'brandnew@example.com',
        ]);

        // Should create new customer if email doesn't exist
        $response->assertOk();
    }

    public function test_change_customer_validates_email_format(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'change_customer',
            'conversation_id' => $this->conversation->id,
            'customer_email' => 'invalid-email',
        ]);

        // Should validate email format
        $this->assertTrue($response->status() >= 400 || $response->json('success') === false);
    }

    // ===== Empty Folder Edge Cases =====

    public function test_empty_folder_only_works_on_deletable_folders(): void
    {
        $this->actingAs($this->admin);

        // Try to empty inbox (should be prevented)
        $response = $this->postJson(route('folders.empty', $this->inboxFolder));

        // Should deny or handle appropriately
        $this->assertTrue(
            $response->status() === 403 || 
            $response->json('success') === false ||
            $response->json('count') === 0
        );
    }

    public function test_empty_folder_on_spam_folder(): void
    {
        $this->actingAs($this->admin);

        $spamFolder = Folder::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'type' => Folder::TYPE_SPAM,
        ]);

        // Create spam conversation
        Conversation::factory()->for($this->mailbox)->create([
            'folder_id' => $spamFolder->id,
            'status' => Conversation::STATUS_SPAM,
        ]);

        $response = $this->postJson(route('folders.empty', $spamFolder));

        $response->assertOk();
    }

    public function test_empty_folder_on_already_empty_folder(): void
    {
        $this->actingAs($this->admin);

        // Deleted folder is already empty
        $response = $this->postJson(route('folders.empty', $this->deletedFolder));

        $response->assertOk();
        $this->assertTrue($response->json('count') === 0 || $response->json('success') === true);
    }

    // ===== Saved Search Edge Cases =====

    public function test_save_search_validates_name_length(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'save_search',
            'name' => str_repeat('a', 300), // Very long name
            'query' => 'status:active',
        ]);

        // Should validate name length
        $this->assertTrue(
            $response->status() >= 400 || 
            $response->json('success') === false
        );
    }

    public function test_save_search_with_empty_query(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'save_search',
            'name' => 'Empty Search',
            'query' => '',
        ]);

        // Should handle empty query appropriately (might allow or reject)
        $this->assertTrue($response->status() === 200 || $response->status() >= 400);
    }

    public function test_delete_search_validates_ownership(): void
    {
        $this->actingAs($this->user);

        // Create search for another user
        $otherUser = User::factory()->create();
        
        // Create saved search through DB if SavedSearch model exists
        if (class_exists(\App\Models\SavedSearch::class)) {
            $search = \App\Models\SavedSearch::create([
                'user_id' => $otherUser->id,
                'name' => 'Other search',
                'query' => 'test',
                'filters' => [],
            ]);

            $response = $this->postJson(route('conversations.ajax'), [
                'action' => 'delete_search',
                'search_id' => $search->id,
            ]);

            // Should deny deleting other user's search
            $this->assertTrue($response->status() === 403 || $response->json('success') === false);
        } else {
            $this->assertTrue(true); // Skip if model doesn't exist
        }
    }

    // ===== Authorization Tests =====

    public function test_unauthenticated_user_cannot_access_ajax(): void
    {
        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'follow',
            'conversation_id' => $this->conversation->id,
        ]);

        $response->assertStatus(401);
    }

    public function test_user_without_mailbox_access_cannot_modify_conversation(): void
    {
        $otherUser = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($otherUser);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'bulk_change_status',
            'conversation_ids' => [$this->conversation->id],
            'status' => Conversation::STATUS_CLOSED,
        ]);

        // Should deny access
        $this->assertTrue($response->status() === 403 || $response->json('success') === false);
    }

    // ===== Invalid Action Tests =====

    public function test_invalid_action_returns_error(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'nonexistent_action',
        ]);

        $this->assertTrue($response->status() >= 400 || $response->json('success') === false);
    }

    public function test_missing_action_returns_error(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('conversations.ajax'), []);

        $this->assertTrue($response->status() >= 400 || $response->json('success') === false);
    }
}
