<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a conversation belongs to a mailbox.
     */
    public function test_conversation_belongs_to_mailbox(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->for($mailbox)->create();

        $this->assertInstanceOf(Mailbox::class, $conversation->mailbox);
        $this->assertEquals($mailbox->id, $conversation->mailbox->id);
    }

    /**
     * Test that a conversation belongs to a customer.
     */
    public function test_conversation_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->for($customer)->create();

        $this->assertInstanceOf(Customer::class, $conversation->customer);
        $this->assertEquals($customer->id, $conversation->customer->id);
    }

    /**
     * Test that a conversation has many threads.
     */
    public function test_conversation_has_many_threads(): void
    {
        $conversation = Conversation::factory()->create();
        $thread1 = Thread::factory()->for($conversation)->create();
        $thread2 = Thread::factory()->for($conversation)->create();
        $thread3 = Thread::factory()->for($conversation)->create();

        $this->assertCount(3, $conversation->threads);
        $this->assertTrue($conversation->threads->contains($thread1));
        $this->assertTrue($conversation->threads->contains($thread2));
        $this->assertTrue($conversation->threads->contains($thread3));
    }

    /**
     * Test that a thread belongs to a conversation.
     */
    public function test_thread_belongs_to_conversation(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->for($conversation)->create();

        $this->assertInstanceOf(Conversation::class, $thread->conversation);
        $this->assertEquals($conversation->id, $thread->conversation->id);
    }

    /**
     * Test that a mailbox has many conversations.
     */
    public function test_mailbox_has_many_conversations(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conv1 = Conversation::factory()->for($mailbox)->create();
        $conv2 = Conversation::factory()->for($mailbox)->create();

        $this->assertCount(2, $mailbox->conversations);
        $this->assertTrue($mailbox->conversations->contains($conv1));
    }

    /**
     * Test that a mailbox has many folders.
     */
    public function test_mailbox_has_many_folders(): void
    {
        $mailbox = Mailbox::factory()->create();
        $folder1 = Folder::factory()->for($mailbox)->create();
        $folder2 = Folder::factory()->for($mailbox)->create();

        // 5 default folders (created by MailboxObserver) + 2 manually created = 7
        $this->assertCount(7, $mailbox->folders);
        $this->assertTrue($mailbox->folders->contains($folder1));
    }

    /**
     * Test that a mailbox belongs to many users.
     */
    public function test_mailbox_users_many_to_many(): void
    {
        $mailbox = Mailbox::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $mailbox->users()->attach([$user1->id, $user2->id, $user3->id]);

        $this->assertCount(3, $mailbox->users);
        $this->assertTrue($mailbox->users->contains($user1));
        $this->assertTrue($mailbox->users->contains($user2));
    }

    /**
     * Test that a user belongs to many mailboxes.
     */
    public function test_user_belongs_to_many_mailboxes(): void
    {
        $user = User::factory()->create();
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();

        $user->mailboxes()->attach([$mailbox1->id, $mailbox2->id]);

        $this->assertCount(2, $user->mailboxes);
        $this->assertTrue($user->mailboxes->contains($mailbox1));
    }

    /**
     * Test eager loading prevents N+1 queries on conversations.
     */
    public function test_eager_loading_prevents_n_plus_1_on_conversations(): void
    {
        // Create test data
        $mailbox = Mailbox::factory()->create();
        Conversation::factory()->count(10)->for($mailbox)->create();

        // Without eager loading - many queries (N+1 problem)
        DB::flushQueryLog();
        DB::enableQueryLog();
        $conversations = Conversation::all();
        foreach ($conversations as $conv) {
            $_ = $conv->mailbox->name;
        }
        $countWithout = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Clear model instances to ensure fresh queries
        Conversation::clearBootedModels();

        // With eager loading - fewer queries
        DB::flushQueryLog();
        DB::enableQueryLog();
        $conversations = Conversation::with('mailbox')->get();
        foreach ($conversations as $conv) {
            $_ = $conv->mailbox->name;
        }
        $countWith = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Eager loading should use FEWER queries than without eager loading
        // assertLessThan($expected, $actual) means $actual < $expected
        // We want: $countWith < $countWithout
        $this->assertLessThan($countWithout, $countWith, "Eager loading (got {$countWith} queries) should be less than without eager loading ({$countWithout} queries)");
    }

    /**
     * Test eager loading with multiple relations.
     */
    public function test_eager_loading_multiple_relations(): void
    {
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        Conversation::factory()
            ->count(5)
            ->for($mailbox)
            ->for($customer)
            ->create();

        DB::enableQueryLog();

        $conversations = Conversation::with(['mailbox', 'customer', 'threads'])->get();

        foreach ($conversations as $conv) {
            $_ = $conv->mailbox->name;
            $_ = $conv->customer->email;
            $_ = $conv->threads->count();
        }

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Should be 4 queries: conversations + mailboxes + customers + threads
        $this->assertLessThanOrEqual(4, $queryCount);
    }

    /**
     * Test that a conversation can belong to a user (assigned).
     */
    public function test_conversation_can_be_assigned_to_user(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $conversation->user);
        $this->assertEquals($user->id, $conversation->user->id);
    }

    /**
     * Test that a conversation can have null user (unassigned).
     */
    public function test_conversation_can_be_unassigned(): void
    {
        $conversation = Conversation::factory()->create(['user_id' => null]);

        $this->assertNull($conversation->user_id);
        $this->assertNull($conversation->user);
    }

    /**
     * Test that empty relationships return empty collections.
     */
    public function test_empty_relationships_return_empty_collections(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->for($mailbox)->create();

        // No threads created
        $this->assertCount(0, $conversation->threads);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $conversation->threads);
    }

    /**
     * Test pivot data on many-to-many relationship.
     */
    public function test_mailbox_user_pivot_data(): void
    {
        $mailbox = Mailbox::factory()->create();
        $user = User::factory()->create();

        $mailbox->users()->attach($user->id, ['after_send' => 1]);

        $attachedUser = $mailbox->users->first();

        $this->assertEquals($user->id, $attachedUser->id);
        $this->assertEquals(1, $attachedUser->pivot->after_send);
    }

    /**
     * Test that a conversation belongs to a folder.
     */
    public function test_conversation_belongs_to_folder(): void
    {
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->for($mailbox)->create();
        $conversation = Conversation::factory()->for($folder)->create();

        $this->assertInstanceOf(Folder::class, $conversation->folder);
        $this->assertEquals($folder->id, $conversation->folder->id);
    }

    /**
     * Test that a folder has many conversations.
     */
    public function test_folder_has_many_conversations(): void
    {
        $folder = Folder::factory()->create();
        $conv1 = Conversation::factory()->for($folder)->create();
        $conv2 = Conversation::factory()->for($folder)->create();

        $conversations = $folder->conversations;

        $this->assertCount(2, $conversations);
        $this->assertTrue($conversations->contains($conv1));
    }

    /**
     * Test that a thread can belong to a user (agent reply).
     */
    public function test_thread_can_belong_to_user(): void
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create([
            'created_by_user_id' => $user->id,
            'type' => 1,  // Use numeric type until constants defined
        ]);

        $this->assertInstanceOf(User::class, $thread->user);
        $this->assertEquals($user->id, $thread->user->id);
    }

    /**
     * Test that a thread can belong to a customer.
     */
    public function test_thread_can_belong_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $thread = Thread::factory()->create([
            'created_by_customer_id' => $customer->id,
            'type' => 2,  // Use numeric type until constants defined
        ]);

        $this->assertInstanceOf(Customer::class, $thread->customer);
        $this->assertEquals($customer->id, $thread->customer->id);
    }

    /**
     * Test that deleting a mailbox cascades to conversations.
     */
    public function test_mailbox_deletion_cascades_to_conversations(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->for($mailbox)->create();

        $mailbox->delete();

        // Conversation should be deleted (cascade delete)
        $this->assertDatabaseMissing('conversations', ['id' => $conversation->id]);
    }

    /**
     * Test that deleting a conversation deletes its threads.
     */
    public function test_conversation_deletion_cascades_to_threads(): void
    {
        $conversation = Conversation::factory()->create();
        $thread1 = Thread::factory()->for($conversation)->create();
        $thread2 = Thread::factory()->for($conversation)->create();

        $conversation->delete();

        // Threads should be deleted (cascade delete)
        $this->assertDatabaseMissing('threads', ['id' => $thread1->id]);
        $this->assertDatabaseMissing('threads', ['id' => $thread2->id]);
    }

    /**
     * Test conversation followers many-to-many relationship.
     */
    public function test_conversation_followers_relationship(): void
    {
        $conversation = Conversation::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $conversation->followers()->attach([$user1->id, $user2->id]);

        $this->assertCount(2, $conversation->followers);
        $this->assertTrue($conversation->followers->contains($user1));
        $this->assertTrue($conversation->followers->contains($user2));
    }

    /**
     * Test conversation can have multiple folders through many-to-many.
     */
    public function test_conversation_belongs_to_many_folders(): void
    {
        $mailbox = Mailbox::factory()->create();
        $folder1 = Folder::factory()->for($mailbox)->create();
        $folder2 = Folder::factory()->for($mailbox)->create();
        $conversation = Conversation::factory()->for($mailbox)->create();

        $conversation->folders()->attach([$folder1->id, $folder2->id]);

        $this->assertCount(2, $conversation->folders);
        $this->assertTrue($conversation->folders->contains($folder1));
        $this->assertTrue($conversation->folders->contains($folder2));
    }

    /**
     * Test that a conversation has createdByUser relationship.
     */
    public function test_conversation_created_by_user_relationship(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['created_by_user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $conversation->createdByUser);
        $this->assertEquals($user->id, $conversation->createdByUser->id);
    }

    /**
     * Test that a conversation has closedByUser relationship.
     */
    public function test_conversation_closed_by_user_relationship(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create([
            'closed_by_user_id' => $user->id,
            'closed_at' => now(),
        ]);

        $this->assertInstanceOf(User::class, $conversation->closedByUser);
        $this->assertEquals($user->id, $conversation->closedByUser->id);
    }

    /**
     * Test relationship query constraints work correctly.
     */
    public function test_mailbox_active_conversations_query(): void
    {
        $mailbox = Mailbox::factory()->create();
        $activeConv = Conversation::factory()->for($mailbox)->create(['status' => Conversation::STATUS_ACTIVE]);
        $closedConv = Conversation::factory()->for($mailbox)->create(['status' => Conversation::STATUS_CLOSED]);

        $activeConversations = $mailbox->conversations()->where('status', Conversation::STATUS_ACTIVE)->get();

        $this->assertCount(1, $activeConversations);
        $this->assertTrue($activeConversations->contains($activeConv));
        $this->assertFalse($activeConversations->contains($closedConv));
    }

    /**
     * Test lazy vs eager loading with counts.
     */
    public function test_eager_loading_with_counts(): void
    {
        $mailbox = Mailbox::factory()->create();
        Conversation::factory()->count(3)->for($mailbox)->create();

        DB::enableQueryLog();

        $mailboxes = Mailbox::withCount('conversations')->get();

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertEquals(3, $mailboxes->first()->conversations_count);
        // Should use 2 queries: one for mailboxes, one for counts
        $this->assertLessThanOrEqual(2, $queryCount);
    }

    /**
     * Test that thread can have multiple attachments.
     */
    public function test_thread_has_many_attachments_relationship(): void
    {
        $thread = Thread::factory()->create();

        // Create attachments directly in database using raw SQL to match actual schema
        DB::table('attachments')->insert([
            [
                'thread_id' => $thread->id,
                'conversation_id' => null,
                'file_name' => 'file1.pdf',
                'file_dir' => 'attachments/test1',
                'file_size' => 1024,
                'mime_type' => 'application/pdf',
                'embedded' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'thread_id' => $thread->id,
                'conversation_id' => null,
                'file_name' => 'file2.jpg',
                'file_dir' => 'attachments/test2',
                'file_size' => 2048,
                'mime_type' => 'image/jpeg',
                'embedded' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $thread = $thread->fresh();
        $attachments = $thread->attachments;

        $this->assertCount(2, $attachments);
    }

    /**
     * Test nested eager loading performance.
     */
    public function test_nested_eager_loading(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->for($mailbox)->create();
        Thread::factory()->count(3)->for($conversation)->create();

        DB::enableQueryLog();

        $conversations = Conversation::with(['mailbox', 'threads'])->get();

        foreach ($conversations as $conv) {
            $_ = $conv->mailbox->name;
            foreach ($conv->threads as $thread) {
                $_ = $thread->id;
            }
        }

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Should use 3 queries: conversations, mailboxes, threads
        $this->assertLessThanOrEqual(3, $queryCount);
    }

    /**
     * Test that conversation can have null relationships.
     */
    public function test_conversation_with_null_user_relationship(): void
    {
        $conversation = Conversation::factory()->create(['user_id' => null]);

        $this->assertNull($conversation->user_id);
        $this->assertNull($conversation->user);
    }

    /**
     * Test that conversation closed_by_user can be null.
     */
    public function test_conversation_with_null_closed_by_user(): void
    {
        $conversation = Conversation::factory()->create(['closed_by_user_id' => null]);

        $this->assertNull($conversation->closed_by_user_id);
        $this->assertNull($conversation->closedByUser);
    }

    /**
     * Test polymorphic relationship with different model types.
     */
    public function test_activity_log_subject_polymorphic_with_different_models(): void
    {
        $conversation = Conversation::factory()->create();
        $user = User::factory()->create();

        $log1 = \App\Models\ActivityLog::factory()->create([
            'subject_type' => Conversation::class,
            'subject_id' => $conversation->id,
        ]);

        $log2 = \App\Models\ActivityLog::factory()->create([
            'subject_type' => User::class,
            'subject_id' => $user->id,
        ]);

        $this->assertInstanceOf(Conversation::class, $log1->subject);
        $this->assertInstanceOf(User::class, $log2->subject);
    }

    /**
     * Test relationship with soft deletes (if applicable).
     */
    public function test_mailbox_users_detach(): void
    {
        $mailbox = Mailbox::factory()->create();
        $user = User::factory()->create();

        $mailbox->users()->attach($user->id);
        $this->assertCount(1, $mailbox->users);

        $mailbox->users()->detach($user->id);
        $this->assertCount(0, $mailbox->fresh()->users);
    }

    /**
     * Test relationship sync method.
     */
    public function test_mailbox_users_sync(): void
    {
        $mailbox = Mailbox::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $mailbox->users()->sync([$user1->id, $user2->id]);
        $this->assertCount(2, $mailbox->fresh()->users);

        $mailbox->users()->sync([$user2->id, $user3->id]);
        $this->assertCount(2, $mailbox->fresh()->users);
        $this->assertTrue($mailbox->users->contains($user2));
        $this->assertTrue($mailbox->users->contains($user3));
        $this->assertFalse($mailbox->users->contains($user1));
    }

    /**
     * Test that orphaned threads are handled when conversation is deleted.
     */
    public function test_orphaned_threads_handling(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->for($conversation)->create();

        $threadId = $thread->id;
        $conversation->delete();

        $this->assertDatabaseMissing('threads', ['id' => $threadId]);
    }

    /**
     * Test relationship counting without loading the relationship.
     */
    public function test_relationship_counting_without_loading(): void
    {
        $mailbox = Mailbox::factory()->create();
        Conversation::factory()->count(5)->for($mailbox)->create();

        $mailboxWithCount = Mailbox::withCount('conversations')->find($mailbox->id);

        $this->assertEquals(5, $mailboxWithCount->conversations_count);
        $this->assertFalse($mailboxWithCount->relationLoaded('conversations'));
    }

    /**
     * Test relationship existence queries.
     */
    public function test_relationship_existence_queries(): void
    {
        $mailbox = Mailbox::factory()->create();
        $convWithThreads = Conversation::factory()->for($mailbox)->create();
        Thread::factory()->count(3)->for($convWithThreads)->create();

        $convWithoutThreads = Conversation::factory()->for($mailbox)->create();

        $conversationsWithThreads = Conversation::has('threads')->get();

        $this->assertCount(1, $conversationsWithThreads);
        $this->assertTrue($conversationsWithThreads->contains($convWithThreads));
        $this->assertFalse($conversationsWithThreads->contains($convWithoutThreads));
    }

    /**
     * Test relationship whereHas queries.
     */
    public function test_relationship_where_has_queries(): void
    {
        $mailbox = Mailbox::factory()->create();
        $activeConv = Conversation::factory()->for($mailbox)->create(['status' => Conversation::STATUS_ACTIVE]);
        Thread::factory()->for($activeConv)->create(['type' => 1]);

        $closedConv = Conversation::factory()->for($mailbox)->create(['status' => Conversation::STATUS_CLOSED]);
        Thread::factory()->for($closedConv)->create(['type' => 2]);

        $conversationsWithUserThreads = Conversation::whereHas('threads', function ($query) {
            $query->where('type', 1);
        })->get();

        $this->assertCount(1, $conversationsWithUserThreads);
        $this->assertTrue($conversationsWithUserThreads->contains($activeConv));
    }
}
