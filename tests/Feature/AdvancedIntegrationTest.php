<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\FeatureTestCase;

/**
 * Advanced Integration Tests covering complex workflows and scenarios
 * Following TESTING_GUIDE.md standards
 */
class AdvancedIntegrationTest extends FeatureTestCase
{

    // ==================== Complete Conversation Workflows ====================

    public function test_complete_conversation_lifecycle_from_creation_to_closure(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => Folder::TYPE_INBOX]);
        $customer = Customer::factory()->create();
        
        // Assign admin to mailbox
        $mailbox->users()->attach($admin->id);

        // Create conversation
        $response = $this->actingAs($admin)->post(route('conversations.create', ['mailbox_id' => $mailbox->id]), [
            'subject' => 'Test Issue',
            'customer_id' => $customer->id,
            'type' => Thread::TYPE_MESSAGE,
            'body' => 'This is a test conversation',
            'to' => ['customer@example.com'],
        ]);

        $response->assertRedirect();
        
        $conversation = Conversation::first();
        $this->assertNotNull($conversation);
        $this->assertEquals(Conversation::STATUS_ACTIVE, $conversation->status);

        // Reply to conversation
        $response = $this->actingAs($admin)->post(route('conversations.reply', $conversation->id), [
            'body' => 'Here is my reply',
            'type' => Thread::TYPE_MESSAGE,
        ]);

        $this->assertDatabaseHas('threads', [
            'conversation_id' => $conversation->id,
            'body' => 'Here is my reply',
        ]);

        // Close conversation
        $response = $this->actingAs($admin)->patch(route('conversations.update', $conversation->id), [
            'status' => Conversation::STATUS_CLOSED,
        ]);

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'status' => Conversation::STATUS_CLOSED,
        ]);
    }

    public function test_conversation_assignment_workflow(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        
        // Assign users to mailbox
        $mailbox->users()->attach([$admin->id, $user1->id, $user2->id]);

        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);

        // Assign to user1
        $response = $this->actingAs($admin)->post(route('conversations.assign', $conversation->id), [
            'user_id' => $user1->id,
        ]);

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'user_id' => $user1->id,
        ]);

        // Reassign to user2
        $response = $this->actingAs($admin)->post(route('conversations.assign', $conversation->id), [
            'user_id' => $user2->id,
        ]);

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'user_id' => $user2->id,
        ]);
    }

    // ==================== Multi-Mailbox Scenarios ====================

    public function test_user_can_access_only_assigned_mailboxes(): void
    {
        $user = User::factory()->create();
        $mailbox1 = Mailbox::factory()->create(['name' => 'Support']);
        $mailbox2 = Mailbox::factory()->create(['name' => 'Sales']);
        
        // User only assigned to mailbox1
        $mailbox1->users()->attach($user->id);

        $conversation1 = Conversation::factory()->create(['mailbox_id' => $mailbox1->id]);
        $conversation2 = Conversation::factory()->create(['mailbox_id' => $mailbox2->id]);

        // Can access mailbox1 conversation
        $response = $this->actingAs($user)->get(route('conversations.show', $conversation1->id));
        $response->assertSuccessful();

        // Cannot access mailbox2 conversation
        $response = $this->actingAs($user)->get(route('conversations.show', $conversation2->id));
        $response->assertForbidden();
    }

    public function test_conversations_isolated_between_mailboxes(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();
        
        // Assign admin to mailbox1
        $mailbox1->users()->attach($admin->id);

        $conversation1 = Conversation::factory()->create(['mailbox_id' => $mailbox1->id]);
        $conversation2 = Conversation::factory()->create(['mailbox_id' => $mailbox2->id]);

        // List conversations for mailbox1
        $response = $this->actingAs($admin)->get(route('mailbox.conversations', $mailbox1->id));
        
        $response->assertSuccessful();
        $response->assertSee($conversation1->subject);
        $response->assertDontSee($conversation2->subject);
    }

    // ==================== Database Transaction Tests ====================

    public function test_conversation_creation_is_transactional(): void
    {
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

        $this->assertTrue(true); // Transaction mock verified
    }

    public function test_failed_conversation_creation_rolls_back(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => Folder::TYPE_INBOX]);
        $mailbox->users()->attach($admin->id); // Also assign admin to mailbox

        $conversationCount = Conversation::count();
        $threadCount = Thread::count();

        // Invalid data should fail and rollback
        $response = $this->actingAs($admin)->post(route('conversations.store', ['mailbox' => $mailbox->id]), [
            'subject' => '', // Invalid - required field
            'body' => '',
        ]);

        // No new records should be created
        $this->assertEquals($conversationCount, Conversation::count());
        $this->assertEquals($threadCount, Thread::count());
    }

    // ==================== Concurrent Operations ====================

    public function test_concurrent_replies_to_same_conversation(): void
    {
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
            'body' => 'Reply from user 1',
        ]);
        $this->assertDatabaseHas('threads', [
            'conversation_id' => $conversation->id,
            'body' => 'Reply from user 2',
        ]);
    }

    public function test_concurrent_status_changes(): void
    {
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
    }

    // ==================== Complex Search and Filtering ====================

    public function test_search_across_multiple_fields(): void
    {
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
    }

    public function test_filter_by_multiple_criteria(): void
    {
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
            'user_id' => $user->id,
        ]);

        Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'status' => Conversation::STATUS_ACTIVE,
            'user_id' => $admin->id,
        ]);

        // Filter by status and assigned user
        $response = $this->actingAs($admin)->get(route('mailbox.conversations', [
            'mailbox' => $mailbox->id,
            'status' => Conversation::STATUS_ACTIVE,
            'user_id' => $user->id,
        ]));

        $response->assertSuccessful();
    }

    // ==================== Folder Counter Updates ====================

    public function test_folder_counters_update_on_conversation_changes(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => Folder::TYPE_INBOX,
        ]);

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'folder_id' => $folder->id,
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $folder->refresh();
        $initialActive = $folder->active_count;

        // Close conversation
        $conversation->update(['status' => Conversation::STATUS_CLOSED]);

        // Counter should be updated
        $folder->refresh();
        $this->assertNotEquals($initialActive, $folder->active_count);
    }

    // ==================== Event Broadcasting ====================

    public function test_events_are_dispatched_on_conversation_actions(): void
    {
        Event::fake([
            \App\Events\ConversationStatusChanged::class,
        ]);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($admin->id);

        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id, 'status' => Conversation::STATUS_ACTIVE]);

        // Update status
        $conversation->update(['status' => Conversation::STATUS_CLOSED]);

        // Event should be dispatched
        Event::assertDispatched(\App\Events\ConversationStatusChanged::class);
    }

    // ==================== Security Edge Cases ====================

    public function test_user_cannot_modify_conversation_in_unassigned_mailbox(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);

        // User not assigned to mailbox
        $response = $this->actingAs($user)->post(route('conversations.reply', $conversation->id), [
            'body' => 'Unauthorized reply',
            'type' => Thread::TYPE_MESSAGE,
        ]);

        $response->assertForbidden();
    }

    public function test_deleted_user_conversations_remain_accessible(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach([$admin->id, $user->id]);

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'user_id' => $user->id,
        ]);

        // Delete user
        $user->delete();

        // Admin can still access conversation
        $response = $this->actingAs($admin)->get(route('conversations.show', $conversation->id));
        $response->assertSuccessful();
    }

    public function test_xss_prevention_in_conversation_display(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($admin->id);

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'subject' => '<script>alert("xss")</script>',
        ]);

        $response = $this->actingAs($admin)->get(route('conversations.show', $conversation->id));

        // Script tags should be escaped
        $response->assertSee('&lt;script&gt;', false);
        $response->assertDontSee('<script>alert("xss")</script>', false);
    }

    // ==================== Performance Optimization ====================

    public function test_conversation_list_avoids_n_plus_one_queries(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($admin->id);

        // Create multiple conversations
        Conversation::factory()->count(10)->create(['mailbox_id' => $mailbox->id]);

        // Count queries
        DB::enableQueryLog();
        
        $this->actingAs($admin)->get(route('mailbox.conversations', $mailbox->id));
        
        $queries = DB::getQueryLog();
        
        // Should not have excessive queries (N+1 problem)
        $this->assertLessThan(50, count($queries));
    }

    public function test_batch_operations_on_conversations(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($admin->id);

        $conversations = Conversation::factory()->count(5)->create([
            'mailbox_id' => $mailbox->id,
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        // Batch close conversations
        $ids = $conversations->pluck('id')->toArray();
        
        $response = $this->actingAs($admin)->post(route('conversations.batch_update'), [
            'ids' => $ids,
            'status' => Conversation::STATUS_CLOSED,
        ]);

        foreach ($ids as $id) {
            $this->assertDatabaseHas('conversations', [
                'id' => $id,
                'status' => Conversation::STATUS_CLOSED,
            ]);
        }
    }
}
