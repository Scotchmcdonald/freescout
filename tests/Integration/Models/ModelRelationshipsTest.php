<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\IntegrationTestCase;

class ModelRelationshipsTest extends IntegrationTestCase
{
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
        $this->assertSoftDeleted('threads', ['id' => $thread1->id]);
        $this->assertSoftDeleted('threads', ['id' => $thread2->id]);
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
    /*
    public function test_conversation_belongs_to_many_folders(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->for($mailbox)->create();
        $folder1 = Folder::factory()->for($mailbox)->create();
        $folder2 = Folder::factory()->for($mailbox)->create();

        $conversation->folders()->attach([$folder1->id, $folder2->id]);

        $this->assertCount(2, $conversation->folders);
        $this->assertTrue($conversation->folders->contains($folder1));
        $this->assertTrue($conversation->folders->contains($folder2));
    }
    */

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

        $this->assertSoftDeleted('threads', ['id' => $threadId]);
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
