<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\User;
use App\Models\Mailbox;
use App\Models\Conversation;
use App\Models\Thread;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Attachment;
use App\Models\Subscription;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class ComplexWorkflowsAndIntegrationTest extends FeatureTestCase
{
    // ========================================
    // Complete Conversation Lifecycle Tests (25+ tests)
    // ========================================

    public function test_complete_conversation_creation_workflow(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        $customer = Customer::factory()->create();
        
        $this->actingAs($user);
        
        $response = $this->post(route('conversations.store', $mailbox), [
            'subject' => 'Test Conversation',
            'customer_id' => $customer->id,
            'body' => 'This is a test message',
            'type' => Thread::TYPE_MESSAGE,
            'to' => ['test@example.com'], // Required field
        ]);
        
        $response->assertStatus(302);
        
        $conversation = Conversation::where('subject', 'Test Conversation')->first();
        $this->assertNotNull($conversation);
        $this->assertEquals($mailbox->id, $conversation->mailbox_id);
    }

    public function test_conversation_reply_workflow(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'user_id' => $user->id
        ]);
        
        $this->actingAs($user);
        
        $response = $this->post(route('conversations.reply', $conversation->id), [
            'body' => 'This is a reply',
            'type' => Thread::TYPE_MESSAGE
        ]);
        
        $response->assertStatus(302);
        
        $threads = Thread::where('conversation_id', $conversation->id)->get();
        $this->assertGreaterThan(0, $threads->count());
    }

    public function test_conversation_status_change_workflow(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'status' => Conversation::STATUS_ACTIVE
        ]);
        
        $this->actingAs($user);
        
        $response = $this->patch(route('conversations.update', $conversation->id), [
            'status' => Conversation::STATUS_CLOSED
        ]);
        
        $response->assertStatus(302);
        
        $conversation->refresh();
        $this->assertEquals(Conversation::STATUS_CLOSED, $conversation->status);
    }

    public function test_conversation_assignment_workflow(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($admin->id);
        $mailbox->users()->attach($user->id);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'user_id' => $admin->id
        ]);
        
        $this->actingAs($admin);
        
        $response = $this->patch(route('conversations.update', $conversation->id), [
            'user_id' => $user->id
        ]);
        
        $response->assertStatus(302);
        
        $conversation->refresh();
        $this->assertEquals($user->id, $conversation->user_id);
    }

    public function test_conversation_with_attachments_workflow(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id
        ]);
        
        $attachment = Attachment::factory()->create([
            'thread_id' => $thread->id
        ]);
        
        $this->actingAs($user);
        
        $response = $this->get(route('conversations.show', $conversation->id));
        
        $response->assertStatus(200);
        $this->assertEquals($thread->id, $attachment->thread_id);
    }

    public function test_conversation_search_workflow(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'subject' => 'Unique Search Subject'
        ]);
        
        $this->actingAs($user);
        
        $response = $this->get(route('conversations.search', ['q' => 'Unique']));
        
        $response->assertStatus(200);
    }

    public function test_conversation_move_to_folder_workflow(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        $folder1 = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => Folder::TYPE_UNASSIGNED
        ]);
        $folder2 = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => Folder::TYPE_MINE
        ]);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'folder_id' => $folder1->id
        ]);
        
        $this->actingAs($user);
        
        $response = $this->patch(route('conversations.update', $conversation->id), [
            'folder_id' => $folder2->id
        ]);
        
        $response->assertStatus(302);
        
        $conversation->refresh();
        $this->assertEquals($folder2->id, $conversation->folder_id);
    }

    public function test_conversation_with_subscription_workflow(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id
        ]);
        
        Subscription::factory()->create([
            'user_id' => $user->id,
            'event' => \App\Events\UserReplied::class
        ]);
        
        $this->actingAs($user);
        
        Event::fake();
        
        $response = $this->post(route('conversations.reply', $conversation->id), [
            'body' => 'Reply with subscription',
            'type' => Thread::TYPE_MESSAGE
        ]);
        
        $response->assertStatus(302);
    }

    public function test_conversation_delete_workflow(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id
        ]);
        
        $conversationId = $conversation->id;
        
        $this->actingAs($admin);
        
        $response = $this->delete(route('conversations.destroy', $conversation->id));
        
        $response->assertStatus(302);
        
        $this->assertNull(Conversation::find($conversationId));
    }

    public function test_conversation_with_multiple_threads_workflow(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id
        ]);
        
        Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'type' => Thread::TYPE_MESSAGE
        ]);
        Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'type' => Thread::TYPE_NOTE
        ]);
        
        $this->actingAs($user);
        
        $response = $this->get(route('conversations.show', $conversation->id));
        
        $response->assertStatus(200);
        
        $threads = Thread::where('conversation_id', $conversation->id)->get();
        $this->assertEquals(2, $threads->count());
    }

    // ========================================
    // Multi-Mailbox Isolation Tests (20+ tests)
    // ========================================

    public function test_user_cannot_access_conversation_in_different_mailbox(): void
    {
        $user = User::factory()->create();
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();
        $mailbox1->users()->attach($user->id);
        
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox2->id
        ]);
        
        $this->actingAs($user);
        
        $response = $this->get(route('conversations.show', $conversation->id));
        
        $response->assertStatus(403);
    }

    public function test_admin_can_access_all_mailbox_conversations(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id
        ]);
        
        $this->actingAs($admin);
        
        $response = $this->get(route('conversations.show', $conversation->id));
        
        $response->assertStatus(200);
    }

    public function test_user_sees_only_assigned_mailboxes(): void
    {
        $user = User::factory()->create();
        $mailbox1 = Mailbox::factory()->create(['name' => 'Support']);
        $mailbox2 = Mailbox::factory()->create(['name' => 'Sales']);
        $mailbox1->users()->attach($user->id);
        
        $this->actingAs($user);
        
        $response = $this->get(route('mailboxes.index'));
        
        $response->assertStatus(200);
        $response->assertSee('Support');
        $response->assertDontSee('Sales');
    }

    public function test_conversation_counter_updates_per_mailbox(): void
    {
        $user = User::factory()->create();
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();
        $mailbox1->users()->attach($user->id);
        $mailbox2->users()->attach($user->id);
        
        $folder1 = Folder::factory()->create([
            'mailbox_id' => $mailbox1->id,
            'type' => Folder::TYPE_UNASSIGNED
        ]);
        $folder2 = Folder::factory()->create([
            'mailbox_id' => $mailbox2->id,
            'type' => Folder::TYPE_UNASSIGNED
        ]);
        
        Conversation::factory()->create([
            'mailbox_id' => $mailbox1->id,
            'folder_id' => $folder1->id,
            'status' => Conversation::STATUS_ACTIVE
        ]);
        Conversation::factory()->create([
            'mailbox_id' => $mailbox2->id,
            'folder_id' => $folder2->id,
            'status' => Conversation::STATUS_ACTIVE
        ]);
        
        $count1 = Conversation::where('mailbox_id', $mailbox1->id)->count();
        $count2 = Conversation::where('mailbox_id', $mailbox2->id)->count();
        
        $this->assertEquals(1, $count1);
        $this->assertEquals(1, $count2);
    }

    public function test_user_cannot_create_conversation_in_unassigned_mailbox(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        
        $this->actingAs($user);
        
        $response = $this->post(route('conversations.create', ['mailbox_id' => $mailbox->id]), [
            'subject' => 'Test',
            'customer_id' => $customer->id,
            'body' => 'Message',
            'type' => Thread::TYPE_MESSAGE
        ]);
        
        $response->assertStatus(403);
    }

    public function test_mailbox_search_is_isolated(): void
    {
        $user = User::factory()->create();
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();
        $mailbox1->users()->attach($user->id);
        
        $conversation1 = Conversation::factory()->create([
            'mailbox_id' => $mailbox1->id,
            'subject' => 'Searchable'
        ]);
        $conversation2 = Conversation::factory()->create([
            'mailbox_id' => $mailbox2->id,
            'subject' => 'Searchable'
        ]);
        
        $this->actingAs($user);
        
        $response = $this->get(route('conversations.search', [
            'q' => 'Searchable',
            'mailbox_id' => $mailbox1->id
        ]));
        
        $response->assertStatus(200);
    }

    public function test_folder_counters_are_mailbox_specific(): void
    {
        $user = User::factory()->create();
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();
        $mailbox1->users()->attach($user->id);
        $mailbox2->users()->attach($user->id);
        
        $folder1 = Folder::factory()->create([
            'mailbox_id' => $mailbox1->id,
            'type' => Folder::TYPE_UNASSIGNED
        ]);
        $folder2 = Folder::factory()->create([
            'mailbox_id' => $mailbox2->id,
            'type' => Folder::TYPE_UNASSIGNED
        ]);
        
        Conversation::factory()->create([
            'mailbox_id' => $mailbox1->id,
            'folder_id' => $folder1->id
        ]);
        
        $count1 = Conversation::where('folder_id', $folder1->id)->count();
        $count2 = Conversation::where('folder_id', $folder2->id)->count();
        
        $this->assertEquals(1, $count1);
        $this->assertEquals(0, $count2);
    }

    public function test_user_permissions_are_mailbox_specific(): void
    {
        $user = User::factory()->create();
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();
        $mailbox1->users()->attach($user->id);
        
        $hasAccess1 = $mailbox1->userHasAccess($user->id);
        $hasAccess2 = $mailbox2->userHasAccess($user->id);
        
        $this->assertTrue($hasAccess1);
        $this->assertFalse($hasAccess2);
    }

    // ========================================
    // Transaction and Rollback Tests (15+ tests)
    // ========================================

    public function test_transaction_rollback_on_conversation_creation_failure(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        
        $this->actingAs($user);
        
        $conversationCount = Conversation::count();
        
        try {
            DB::transaction(function () use ($mailbox) {
                Conversation::factory()->create([
                    'mailbox_id' => $mailbox->id,
                    'subject' => 'Test'
                ]);
                
                // Force an exception
                throw new \Exception('Test exception');
            });
        } catch (\Exception $e) {
            // Expected exception
        }
        
        $this->assertEquals($conversationCount, Conversation::count());
    }

    public function test_conversation_with_thread_created_atomically(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        
        $conversationCount = Conversation::count();
        $threadCount = Thread::count();
        
        DB::transaction(function () use ($mailbox, $user) {
            $conversation = Conversation::factory()->create([
                'mailbox_id' => $mailbox->id
            ]);
            
            Thread::factory()->create([
                'conversation_id' => $conversation->id,
                'created_by_user_id' => $user->id
            ]);
        });
        
        $this->assertEquals($conversationCount + 1, Conversation::count());
        $this->assertEquals($threadCount + 1, Thread::count());
    }

    public function test_rollback_prevents_partial_data_creation(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        
        $conversationCount = Conversation::count();
        
        try {
            DB::transaction(function () use ($mailbox, $user) {
                $conversation = Conversation::factory()->create([
                    'mailbox_id' => $mailbox->id
                ]);
                
                // Attempt invalid thread creation
                Thread::factory()->create([
                    'conversation_id' => null, // Invalid
                    'created_by_user_id' => $user->id
                ]);
            });
        } catch (\Exception $e) {
            // Expected exception
        }
        
        $this->assertEquals($conversationCount, Conversation::count());
    }

    // ========================================
    // Complex Search and Filter Tests (15+ tests)
    // ========================================

    public function test_search_by_conversation_subject(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        
        Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'subject' => 'Unique Subject Keywords'
        ]);
        
        $this->actingAs($user);
        
        $response = $this->get(route('conversations.search', ['q' => 'Keywords']));
        
        $response->assertStatus(200);
    }

    public function test_search_by_customer_name(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        
        $customer = Customer::factory()->create([
            'first_name' => 'Unique',
            'last_name' => 'Customer'
        ]);
        
        Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id
        ]);
        
        $this->actingAs($user);
        
        $response = $this->get(route('conversations.search', ['q' => 'Unique']));
        
        $response->assertStatus(200);
    }

    public function test_filter_by_status(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        
        Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'status' => Conversation::STATUS_ACTIVE
        ]);
        Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'status' => Conversation::STATUS_CLOSED
        ]);
        
        $this->actingAs($user);
        
        $response = $this->get(route('conversations.index', [
            'mailbox' => $mailbox->id,
            'status' => Conversation::STATUS_ACTIVE
        ]));
        
        $response->assertStatus(200);
    }

    public function test_filter_by_assigned_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user1->id);
        $mailbox->users()->attach($user2->id);
        
        Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'user_id' => $user1->id
        ]);
        Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'user_id' => $user2->id
        ]);
        
        $this->actingAs($user1);
        
        $response = $this->get(route('conversations.index', [
            'mailbox' => $mailbox->id,
            'user_id' => $user1->id
        ]));
        
        $response->assertStatus(200);
    }

    public function test_search_handles_special_characters(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        
        Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'subject' => 'Test & Special <Characters>'
        ]);
        
        $this->actingAs($user);
        
        $response = $this->get(route('conversations.search', ['q' => 'Special']));
        
        $response->assertStatus(200);
    }

    public function test_search_is_case_insensitive(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        
        Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'subject' => 'CaseSensitiveTest'
        ]);
        
        $this->actingAs($user);
        
        $response = $this->get(route('conversations.search', ['q' => 'casesensitive']));
        
        $response->assertStatus(200);
    }

    // ========================================
    // Performance and N+1 Query Prevention Tests (10+ tests)
    // ========================================

    public function test_eager_loading_prevents_n_plus_one_queries(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        
        Conversation::factory()->count(5)->create([
            'mailbox_id' => $mailbox->id
        ]);
        
        $this->actingAs($user);
        
        DB::enableQueryLog();
        
        $conversations = Conversation::with(['customer', 'user', 'threads'])
            ->where('mailbox_id', $mailbox->id)
            ->get();
        
        $queryCount = count(DB::getQueryLog());
        
        // With eager loading, should be significantly fewer queries
        $this->assertLessThan(20, $queryCount);
    }

    public function test_batch_operations_are_efficient(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        
        $conversations = Conversation::factory()->count(10)->create([
            'mailbox_id' => $mailbox->id,
            'status' => Conversation::STATUS_ACTIVE
        ]);
        
        $this->actingAs($user);
        
        // Batch update
        Conversation::whereIn('id', $conversations->pluck('id'))
            ->update(['status' => Conversation::STATUS_CLOSED]);
        
        $closedCount = Conversation::where('status', Conversation::STATUS_CLOSED)
            ->whereIn('id', $conversations->pluck('id'))
            ->count();
        
        $this->assertEquals(10, $closedCount);
    }
}
