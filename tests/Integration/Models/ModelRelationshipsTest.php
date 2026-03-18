<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\Thread;
use Tests\IntegrationTestCase;

class ModelRelationshipsTest extends IntegrationTestCase
{
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
}
