<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolderHierarchyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that folder type helper methods work correctly.
     */
    public function test_folder_type_helper_methods(): void
    {
        // Arrange & Act
        $inboxFolder = Folder::factory()->create(['type' => Folder::TYPE_INBOX]);
        $sentFolder = Folder::factory()->create(['type' => Folder::TYPE_SENT]);
        $draftsFolder = Folder::factory()->create(['type' => Folder::TYPE_DRAFTS]);
        $spamFolder = Folder::factory()->create(['type' => Folder::TYPE_SPAM]);
        $trashFolder = Folder::factory()->create(['type' => Folder::TYPE_TRASH]);

        // Assert
        $this->assertTrue($inboxFolder->isInbox());
        $this->assertFalse($inboxFolder->isSent());
        
        $this->assertTrue($sentFolder->isSent());
        $this->assertFalse($sentFolder->isInbox());
        
        $this->assertTrue($draftsFolder->isDrafts());
        $this->assertFalse($draftsFolder->isSpam());
        
        $this->assertTrue($spamFolder->isSpam());
        $this->assertFalse($spamFolder->isTrash());
        
        $this->assertTrue($trashFolder->isTrash());
        $this->assertFalse($trashFolder->isDrafts());
    }

    /**
     * Test that folders belong to correct mailbox.
     */
    public function test_folders_belong_to_correct_mailbox(): void
    {
        // Arrange
        $mailbox1 = Mailbox::factory()->create(['name' => 'Support']);
        $mailbox2 = Mailbox::factory()->create(['name' => 'Sales']);
        
        $folder1 = Folder::factory()->create(['mailbox_id' => $mailbox1->id, 'type' => Folder::TYPE_INBOX]);
        $folder2 = Folder::factory()->create(['mailbox_id' => $mailbox1->id, 'type' => Folder::TYPE_SENT]);
        $folder3 = Folder::factory()->create(['mailbox_id' => $mailbox2->id, 'type' => Folder::TYPE_INBOX]);

        // Act & Assert
        $mailbox1Folders = $mailbox1->folders;
        $this->assertCount(2, $mailbox1Folders);
        $this->assertTrue($mailbox1Folders->contains($folder1));
        $this->assertTrue($mailbox1Folders->contains($folder2));
        $this->assertFalse($mailbox1Folders->contains($folder3));
    }

    /**
     * Test that folders can belong to a specific user (personal folders).
     */
    public function test_folders_can_belong_to_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        
        $personalFolder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'user_id' => $user->id,
            'type' => Folder::TYPE_MINE,
        ]);

        // Act & Assert
        $this->assertInstanceOf(User::class, $personalFolder->user);
        $this->assertEquals($user->id, $personalFolder->user->id);
    }

    /**
     * Test that system folders (Inbox, Sent, etc.) have no user_id.
     */
    public function test_system_folders_have_no_user(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();
        
        $inboxFolder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => Folder::TYPE_INBOX,
            'user_id' => null,
        ]);

        // Act & Assert
        $this->assertNull($inboxFolder->user_id);
        $this->assertNull($inboxFolder->user);
        $this->assertTrue($inboxFolder->isInbox());
    }

    /**
     * Test that folder counters are properly tracked.
     */
    public function test_folder_counters_are_tracked(): void
    {
        // Arrange
        $folder = Folder::factory()->create([
            'total_count' => 10,
            'active_count' => 5,
        ]);

        // Act & Assert
        $this->assertEquals(10, $folder->total_count);
        $this->assertEquals(5, $folder->active_count);
    }

    /**
     * Test that folders can have conversations through the pivot table.
     */
    public function test_folders_can_have_conversations_via_pivot(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id]);
        
        // Note: This test requires Conversation model and factory
        // If not available yet, this test validates the relationship method exists
        $this->assertTrue(method_exists($folder, 'conversationsViaFolder'));
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsToMany::class,
            $folder->conversationsViaFolder()
        );
    }

    /**
     * Test that multiple folders can exist per mailbox.
     */
    public function test_mailbox_can_have_multiple_folder_types(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();
        
        $inbox = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => Folder::TYPE_INBOX]);
        $sent = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => Folder::TYPE_SENT]);
        $drafts = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => Folder::TYPE_DRAFTS]);
        $spam = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => Folder::TYPE_SPAM]);
        $trash = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => Folder::TYPE_TRASH]);

        // Act
        $folders = $mailbox->folders;

        // Assert
        $this->assertCount(5, $folders);
        $this->assertTrue($folders->contains($inbox));
        $this->assertTrue($folders->contains($sent));
        $this->assertTrue($folders->contains($drafts));
        $this->assertTrue($folders->contains($spam));
        $this->assertTrue($folders->contains($trash));
    }
}
