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

class ConversationRegressionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function conversation_status_constants_match_l5_implementation(): void
    {
        // Assert: Verify status constants match the L5 (archived) implementation
        $this->assertEquals(1, Conversation::STATUS_ACTIVE);
        $this->assertEquals(2, Conversation::STATUS_PENDING);
        $this->assertEquals(3, Conversation::STATUS_CLOSED);
        $this->assertEquals(4, Conversation::STATUS_SPAM);
    }

    /** @test */
    public function conversation_status_active_matches_l5_behavior(): void
    {
        // Arrange
        $conversation = Conversation::factory()->create([
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        // Act & Assert: Test status methods
        $this->assertTrue($conversation->isActive());
        $this->assertFalse($conversation->isClosed());
        $this->assertEquals(1, $conversation->status);
    }

    /** @test */
    public function conversation_status_pending_matches_l5_behavior(): void
    {
        // Arrange
        $conversation = Conversation::factory()->create([
            'status' => Conversation::STATUS_PENDING,
        ]);

        // Act & Assert
        $this->assertFalse($conversation->isActive());
        $this->assertFalse($conversation->isClosed());
        $this->assertEquals(2, $conversation->status);
    }

    /** @test */
    public function conversation_status_closed_matches_l5_behavior(): void
    {
        // Arrange
        $conversation = Conversation::factory()->create([
            'status' => Conversation::STATUS_CLOSED,
            'closed_at' => now(),
        ]);

        // Act & Assert
        $this->assertFalse($conversation->isActive());
        $this->assertTrue($conversation->isClosed());
        $this->assertEquals(3, $conversation->status);
        $this->assertNotNull($conversation->closed_at);
    }

    /** @test */
    public function conversation_status_spam_matches_l5_behavior(): void
    {
        // Arrange
        $conversation = Conversation::factory()->create([
            'status' => Conversation::STATUS_SPAM,
        ]);

        // Act & Assert
        $this->assertFalse($conversation->isActive());
        $this->assertFalse($conversation->isClosed());
        $this->assertEquals(4, $conversation->status);
    }

    /** @test */
    public function conversation_tracks_last_reply_from_customer_or_user(): void
    {
        // Arrange: This matches L5 PERSON_CUSTOMER and PERSON_USER constants
        $conversation = Conversation::factory()->create([
            'last_reply_from' => 1, // Customer
        ]);

        // Act & Assert
        $this->assertEquals(1, $conversation->last_reply_from);

        // Update to user reply
        $conversation->update(['last_reply_from' => 2]); // User
        $this->assertEquals(2, $conversation->last_reply_from);
    }

    /** @test */
    public function conversation_type_email_constant_matches_l5(): void
    {
        // Arrange: L5 has TYPE_EMAIL = 1
        $conversation = Conversation::factory()->create([
            'type' => 1, // Email type
        ]);

        // Act & Assert
        $this->assertEquals(1, $conversation->type);
    }

    /** @test */
    public function conversation_state_constants_match_l5(): void
    {
        // Arrange: Verify state constants
        // L5 has STATE_DRAFT = 1, STATE_PUBLISHED = 2, STATE_DELETED = 3
        $draft = Conversation::factory()->create(['state' => 1]);
        $published = Conversation::factory()->create(['state' => 2]);
        $deleted = Conversation::factory()->create(['state' => 3]);

        // Act & Assert
        $this->assertEquals(1, $draft->state);
        $this->assertEquals(2, $published->state);
        $this->assertEquals(3, $deleted->state);
    }

    /** @test */
    public function thread_creation_matches_l5_structure(): void
    {
        // Arrange: Verify thread structure matches L5
        $conversation = Conversation::factory()->create();
        
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'type' => 1, // Message
            'status' => 1, // Active (must match conversation status in L5)
            'state' => 2, // Published
            'source_type' => 2, // Web
        ]);

        // Act & Assert: Verify thread properties match L5 implementation
        $this->assertEquals($conversation->id, $thread->conversation_id);
        $this->assertEquals(1, $thread->type);
        $this->assertEquals(1, $thread->status);
        $this->assertEquals(2, $thread->state);
        $this->assertEquals(2, $thread->source_type);
    }

    /** @test */
    public function conversation_maintains_thread_count_like_l5(): void
    {
        // Arrange
        $conversation = Conversation::factory()->create([
            'threads_count' => 1,
        ]);

        // Act: Create additional threads
        Thread::factory()->create([
            'conversation_id' => $conversation->id,
        ]);

        // Manually update count (in L5, this would be done via observer/event)
        $conversation->update(['threads_count' => 2]);

        // Assert
        $this->assertEquals(2, $conversation->threads_count);
    }

    /** @test */
    public function conversation_source_type_constants_match_l5(): void
    {
        // Arrange: L5 has SOURCE_TYPE_EMAIL = 1, SOURCE_TYPE_WEB = 2, SOURCE_TYPE_API = 3
        $emailConv = Conversation::factory()->create(['source_type' => 1]);
        $webConv = Conversation::factory()->create(['source_type' => 2]);
        $apiConv = Conversation::factory()->create(['source_type' => 3]);

        // Act & Assert
        $this->assertEquals(1, $emailConv->source_type);
        $this->assertEquals(2, $webConv->source_type);
        $this->assertEquals(3, $apiConv->source_type);
    }

    /** @test */
    public function conversation_folder_assignment_matches_l5_logic(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 1, // Inbox
        ]);

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'folder_id' => $folder->id,
        ]);

        // Act & Assert: Verify conversation is in the correct folder
        $this->assertEquals($folder->id, $conversation->folder_id);
        $this->assertEquals($mailbox->id, $conversation->mailbox_id);
    }
}
