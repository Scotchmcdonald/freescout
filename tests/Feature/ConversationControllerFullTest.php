<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Tests\FeatureTestCase;

/**
 * Comprehensive tests for ConversationController
 * Following TESTING_GUIDE.md - using test_ prefix, FeatureTestCase base class
 */
class ConversationControllerFullTest extends FeatureTestCase
{
    // ===== INDEX TESTS =====

    public function test_index_requires_authentication(): void
    {
        $response = $this->get(route('conversations.index'));
        
        $response->assertRedirect(route('login'));
    }

    public function test_index_returns_view_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        
        $response = $this->actingAs($user)->get(route('conversations.index'));
        
        $response->assertOk();
        $response->assertViewIs('conversations.index');
    }

    public function test_index_with_mailbox_filter(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        
        $response = $this->actingAs($user)->get(route('conversations.index', ['mailbox' => $mailbox->id]));
        
        $response->assertOk();
    }

    // ===== SHOW TESTS =====

    public function test_show_requires_authentication(): void
    {
        $conversation = Conversation::factory()->create();
        
        $response = $this->get(route('conversations.show', $conversation));
        
        $response->assertRedirect(route('login'));
    }

    public function test_show_returns_view_for_authorized_user(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        
        $response = $this->actingAs($user)->get(route('conversations.show', $conversation));
        
        $response->assertOk();
        $response->assertViewIs('conversations.show');
    }

    public function test_show_forbids_unauthorized_user(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $conversation = Conversation::factory()->create();
        
        $response = $this->actingAs($user)->get(route('conversations.show', $conversation));
        
        $response->assertForbidden();
    }

    public function test_show_admin_can_view_any_conversation(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $conversation = Conversation::factory()->create();
        
        $response = $this->actingAs($admin)->get(route('conversations.show', $conversation));
        
        $response->assertOk();
    }

    // ===== CREATE TESTS =====

    public function test_create_requires_authentication(): void
    {
        $response = $this->get(route('conversations.create'));
        
        $response->assertRedirect(route('login'));
    }

    public function test_create_returns_view_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        
        $response = $this->actingAs($user)->get(route('conversations.create'));
        
        $response->assertOk();
        $response->assertViewIs('conversations.create');
    }

    // ===== STORE TESTS =====

    public function test_store_requires_authentication(): void
    {
        $response = $this->post(route('conversations.store'), [
            'subject' => 'Test',
            'body' => 'Test body',
        ]);
        
        $response->assertRedirect(route('login'));
    }

    public function test_store_creates_conversation_with_valid_data(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        $customer = Customer::factory()->create();
        
        $response = $this->actingAs($user)->post(route('conversations.store'), [
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'subject' => 'Test Subject',
            'body' => 'Test body content',
        ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('conversations', [
            'subject' => 'Test Subject',
            'mailbox_id' => $mailbox->id,
        ]);
    }

    public function test_store_validates_subject_required(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->post(route('conversations.store'), [
            'body' => 'Test',
        ]);
        
        $response->assertSessionHasErrors('subject');
    }

    public function test_store_validates_body_required(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->post(route('conversations.store'), [
            'subject' => 'Test',
        ]);
        
        $response->assertSessionHasErrors('body');
    }

    // ===== UPDATE TESTS =====

    public function test_update_requires_authentication(): void
    {
        $conversation = Conversation::factory()->create();
        
        $response = $this->put(route('conversations.update', $conversation), [
            'subject' => 'Updated',
        ]);
        
        $response->assertRedirect(route('login'));
    }

    public function test_update_updates_conversation_subject(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'subject' => 'Original',
        ]);
        
        $response = $this->actingAs($user)->put(route('conversations.update', $conversation), [
            'subject' => 'Updated Subject',
        ]);
        
        $response->assertRedirect();
        $this->assertEquals('Updated Subject', $conversation->fresh()->subject);
    }

    public function test_update_forbids_unauthorized_user(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $conversation = Conversation::factory()->create();
        
        $response = $this->actingAs($user)->put(route('conversations.update', $conversation), [
            'subject' => 'Updated',
        ]);
        
        $response->assertForbidden();
    }

    // ===== REPLY TESTS =====

    public function test_reply_requires_authentication(): void
    {
        $conversation = Conversation::factory()->create();
        
        $response = $this->post(route('conversations.reply', $conversation), [
            'body' => 'Reply text',
        ]);
        
        $response->assertRedirect(route('login'));
    }

    public function test_reply_creates_thread(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        
        $response = $this->actingAs($user)->post(route('conversations.reply', $conversation), [
            'body' => 'Reply content',
            'type' => Thread::TYPE_MESSAGE,
        ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('threads', [
            'conversation_id' => $conversation->id,
            'body' => 'Reply content',
        ]);
    }

    public function test_reply_validates_body_required(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        
        $response = $this->actingAs($user)->post(route('conversations.reply', $conversation), []);
        
        $response->assertSessionHasErrors('body');
    }

    // ===== SEARCH TESTS =====

    public function test_search_requires_authentication(): void
    {
        $response = $this->get(route('conversations.search', ['q' => 'test']));
        
        $response->assertRedirect(route('login'));
    }

    public function test_search_returns_results(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        
        Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'subject' => 'Searchable Subject',
        ]);
        
        $response = $this->actingAs($user)->get(route('conversations.search', ['q' => 'Searchable']));
        
        $response->assertOk();
    }

    // ===== DESTROY TESTS =====

    public function test_destroy_requires_authentication(): void
    {
        $conversation = Conversation::factory()->create();
        
        $response = $this->delete(route('conversations.destroy', $conversation));
        
        $response->assertRedirect(route('login'));
    }

    public function test_destroy_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $conversation = Conversation::factory()->create();
        
        $response = $this->actingAs($user)->delete(route('conversations.destroy', $conversation));
        
        $response->assertForbidden();
    }

    public function test_destroy_deletes_conversation_for_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $conversation = Conversation::factory()->create();
        
        $response = $this->actingAs($admin)->delete(route('conversations.destroy', $conversation));
        
        $response->assertRedirect();
        $this->assertDatabaseMissing('conversations', ['id' => $conversation->id]);
    }

    // ===== AJAX TESTS =====

    public function test_ajax_requires_authentication(): void
    {
        $conversation = Conversation::factory()->create();
        
        $response = $this->postJson(route('conversations.ajax', $conversation), [
            'action' => 'test',
        ]);
        
        $response->assertUnauthorized();
    }

    public function test_ajax_returns_json_response(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        
        $response = $this->actingAs($user)->postJson(route('conversations.ajax', $conversation), [
            'action' => 'status',
        ]);
        
        $response->assertOk();
        $response->assertJson([]);
    }

    // ===== MOVE TESTS =====

    public function test_move_requires_authentication(): void
    {
        $conversation = Conversation::factory()->create();
        $folder = Folder::factory()->create();
        
        $response = $this->post(route('conversations.move', $conversation), [
            'folder_id' => $folder->id,
        ]);
        
        $response->assertRedirect(route('login'));
    }

    public function test_move_changes_conversation_folder(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        
        $oldFolder = Folder::factory()->create(['mailbox_id' => $mailbox->id]);
        $newFolder = Folder::factory()->create(['mailbox_id' => $mailbox->id]);
        
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'folder_id' => $oldFolder->id,
        ]);
        
        $response = $this->actingAs($user)->post(route('conversations.move', $conversation), [
            'folder_id' => $newFolder->id,
        ]);
        
        $response->assertRedirect();
        $this->assertEquals($newFolder->id, $conversation->fresh()->folder_id);
    }

    // ===== EDGE CASES =====

    public function test_show_with_nonexistent_conversation_returns_404(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get('/conversations/99999');
        
        $response->assertNotFound();
    }

    public function test_store_with_long_subject(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        $customer = Customer::factory()->create();
        
        $longSubject = str_repeat('a', 300);
        
        $response = $this->actingAs($user)->post(route('conversations.store'), [
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'subject' => $longSubject,
            'body' => 'Test body',
        ]);
        
        $response->assertSessionHasErrors('subject');
    }

    public function test_update_preserves_unchanged_fields(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'subject' => 'Original',
            'status' => 1,
        ]);
        
        $originalStatus = $conversation->status;
        
        $response = $this->actingAs($user)->put(route('conversations.update', $conversation), [
            'subject' => 'Updated',
        ]);
        
        $response->assertRedirect();
        $this->assertEquals($originalStatus, $conversation->fresh()->status);
    }

    public function test_reply_with_empty_body_fails_validation(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        
        $response = $this->actingAs($user)->post(route('conversations.reply', $conversation), [
            'body' => '',
        ]);
        
        $response->assertSessionHasErrors('body');
    }

    public function test_search_with_no_results(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get(route('conversations.search', ['q' => 'nonexistentquery123']));
        
        $response->assertOk();
    }

    public function test_admin_can_perform_all_operations(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $conversation = Conversation::factory()->create();
        
        $showResponse = $this->actingAs($admin)->get(route('conversations.show', $conversation));
        $updateResponse = $this->actingAs($admin)->put(route('conversations.update', $conversation), [
            'subject' => 'Admin Update',
        ]);
        
        $showResponse->assertOk();
        $updateResponse->assertRedirect();
    }
}
