<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_conversation_workflow_from_creation_to_closure(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();

        // Create conversation
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'user_id' => null, // Unassigned initially
            'status' => 1, // Active
        ]);

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'status' => 1,
            'user_id' => null,
        ]);

        // Assign to user
        $conversation->update(['user_id' => $user->id]);

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'user_id' => $user->id,
        ]);

        // Add reply thread
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'type' => 1, // Message type
        ]);

        $this->assertDatabaseHas('threads', [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
        ]);

        // Close conversation
        $conversation->update(['status' => 3]); // Closed

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'status' => 3,
        ]);
    }

    public function test_conversation_reassignment_workflow(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user1->id]);

        // Reassign to different user
        $conversation->update(['user_id' => $user2->id]);

        $this->assertEquals($user2->id, $conversation->fresh()->user_id);
    }

    public function test_conversation_with_multiple_threads(): void
    {
        $conversation = Conversation::factory()->create();
        $user = User::factory()->create();

        // Customer initial message
        Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'type' => 2, // Customer type
        ]);

        // User replies
        Thread::factory()->count(2)->create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'type' => 1, // Message type
        ]);

        $this->assertCount(3, $conversation->fresh()->threads);
    }

    public function test_conversation_status_transitions(): void
    {
        $conversation = Conversation::factory()->create(['status' => 1]); // Active

        // Active -> Pending
        $conversation->update(['status' => 2]);
        $this->assertEquals(2, $conversation->fresh()->status);

        // Pending -> Closed
        $conversation->update(['status' => 3]);
        $this->assertEquals(3, $conversation->fresh()->status);

        // Closed -> Active (reopen)
        $conversation->update(['status' => 1]);
        $this->assertEquals(1, $conversation->fresh()->status);
    }
}
