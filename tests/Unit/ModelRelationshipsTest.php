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

        $this->assertCount(2, $mailbox->folders);
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
}
