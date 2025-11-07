<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Conversation;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationUpdateFolderTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function update_folder_assigns_active_assigned_conversation_to_assigned_folder(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();
        $user = User::factory()->create();
        
        $assignedFolder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 1, // Assigned folder
            'user_id' => $user->id,
        ]);

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'status' => Conversation::STATUS_ACTIVE,
            'user_id' => $user->id,
        ]);

        // Act
        $conversation->updateFolder();

        // Assert
        $conversation->refresh();
        $this->assertEquals($assignedFolder->id, $conversation->folder_id);
    }

    /** @test */
    public function update_folder_assigns_active_unassigned_conversation_to_unassigned_folder(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();
        
        $unassignedFolder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 2, // Unassigned folder
        ]);

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'status' => Conversation::STATUS_ACTIVE,
            'user_id' => null, // Unassigned
        ]);

        // Act
        $conversation->updateFolder();

        // Assert
        $conversation->refresh();
        $this->assertEquals($unassignedFolder->id, $conversation->folder_id);
    }

    /** @test */
    public function update_folder_assigns_pending_conversation_to_unassigned_folder(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();
        
        $unassignedFolder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 2, // Unassigned folder
        ]);

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'status' => Conversation::STATUS_PENDING,
        ]);

        // Act
        $conversation->updateFolder();

        // Assert
        $conversation->refresh();
        $this->assertEquals($unassignedFolder->id, $conversation->folder_id);
    }

    /** @test */
    public function update_folder_assigns_closed_conversation_to_closed_folder(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();
        
        $closedFolder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 4, // Closed/Deleted folder
        ]);

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'status' => Conversation::STATUS_CLOSED,
        ]);

        // Act
        $conversation->updateFolder();

        // Assert
        $conversation->refresh();
        $this->assertEquals($closedFolder->id, $conversation->folder_id);
    }

    /** @test */
    public function update_folder_assigns_spam_conversation_to_spam_folder(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();
        
        $spamFolder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 30, // Spam folder
        ]);

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'status' => Conversation::STATUS_SPAM,
        ]);

        // Act
        $conversation->updateFolder();

        // Assert
        $conversation->refresh();
        $this->assertEquals($spamFolder->id, $conversation->folder_id);
    }

    /** @test */
    public function update_folder_handles_missing_folder_gracefully(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();
        
        // Create a folder first, then we'll simulate it not existing for the updateFolder() call
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 1, // Assigned
        ]);
        $oldFolderId = $folder->id;

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'status' => Conversation::STATUS_ACTIVE,
            'user_id' => null, // Unassigned, so it will look for type 2 folder
            'folder_id' => $oldFolderId,
        ]);

        // Now the conversation tries to find type 2 folder (unassigned), but none exists
        // Act
        $conversation->updateFolder();

        // Assert - folder_id should remain unchanged if no matching folder found
        $conversation->refresh();
        $this->assertEquals($oldFolderId, $conversation->folder_id);
    }
}
