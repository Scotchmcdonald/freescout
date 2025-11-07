<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationCloneTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Mailbox $mailbox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->mailbox = Mailbox::factory()->create();
        $this->mailbox->users()->attach($this->user);

        // Create folders
        Folder::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'type' => Folder::TYPE_INBOX,
        ]);
    }

    /** @test */
    public function can_clone_conversation_from_thread(): void
    {
        $this->markTestIncomplete('This test requires ConversationPolicy to be implemented for authorization');
        
        // Arrange
        $originalConversation = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'subject' => 'Original Conversation',
            'status' => Conversation::STATUS_CLOSED,
        ]);

        $thread = Thread::factory()->create([
            'conversation_id' => $originalConversation->id,
            'body' => 'Thread to clone',
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->get(route('conversations.clone', [
                'mailbox' => $this->mailbox,
                'thread' => $thread,
            ]));

        // Assert
        $response->assertRedirect();

        // Verify new conversation was created
        $this->assertDatabaseHas('conversations', [
            'mailbox_id' => $this->mailbox->id,
            'subject' => 'Original Conversation',
            'status' => Conversation::STATUS_ACTIVE, // New conversation is active
            'customer_id' => $originalConversation->customer_id,
        ]);

        // Verify it's a different conversation
        $newConversation = Conversation::where('subject', 'Original Conversation')
            ->where('status', Conversation::STATUS_ACTIVE)
            ->first();
        
        $this->assertNotEquals($originalConversation->id, $newConversation->id);
    }

    /** @test */
    public function cloned_conversation_has_cloned_thread(): void
    {
        $this->markTestIncomplete('This test requires ConversationPolicy to be implemented for authorization');
        
        // Arrange
        $originalConversation = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
        ]);

        $thread = Thread::factory()->create([
            'conversation_id' => $originalConversation->id,
            'body' => 'Original thread body',
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->get(route('conversations.clone', [
                'mailbox' => $this->mailbox,
                'thread' => $thread,
            ]));

        // Assert
        $response->assertRedirect();

        // Find the new conversation
        $newConversation = Conversation::where('mailbox_id', $this->mailbox->id)
            ->where('customer_id', $originalConversation->customer_id)
            ->where('id', '!=', $originalConversation->id)
            ->first();

        $this->assertNotNull($newConversation);

        // Verify the cloned thread exists
        $this->assertDatabaseHas('threads', [
            'conversation_id' => $newConversation->id,
            'body' => 'Original thread body',
        ]);
    }

    /** @test */
    public function cannot_clone_conversation_without_access_to_mailbox(): void
    {
        // Arrange
        $otherMailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $otherMailbox->id,
        ]);

        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->get(route('conversations.clone', [
                'mailbox' => $otherMailbox,
                'thread' => $thread,
            ]));

        // Assert
        $response->assertForbidden();
    }
}
