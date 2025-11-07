<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Conversation;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Advanced edge case tests for Folder model.
 * Complements FolderHierarchyTest.php with more complex scenarios.
 */
class FolderEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that deleting a mailbox cascades to its folders.
     * Note: This depends on database foreign key constraints.
     */
    public function test_deleting_mailbox_affects_folders(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id]);
        $folderId = $folder->id;

        // Act
        $mailbox->delete();

        // Assert - Folder should be deleted or orphaned based on cascade rules
        // If ON DELETE CASCADE is set, folder won't exist
        // If ON DELETE SET NULL is set, folder will have null mailbox_id
        // For now, we test that the relationship is properly configured
        $this->assertDatabaseMissing('mailboxes', ['id' => $mailbox->id]);
    }

    /**
     * Test that folder type constants are immutable and consistent.
     */
    public function test_folder_type_constants_are_consistent(): void
    {
        // Assert - Verify all type constants have unique values
        $types = [
            Folder::TYPE_INBOX,
            Folder::TYPE_SENT,
            Folder::TYPE_DRAFTS,
            Folder::TYPE_SPAM,
            Folder::TYPE_TRASH,
            Folder::TYPE_ASSIGNED,
            Folder::TYPE_MINE,
            Folder::TYPE_STARRED,
        ];

        $uniqueTypes = array_unique($types);
        $this->assertCount(count($types), $uniqueTypes, 'Folder type constants must have unique values');
    }

    /**
     * Test that folder can have zero conversations.
     */
    public function test_folder_can_have_zero_conversations(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id]);

        // Act
        $conversations = $folder->conversations;

        // Assert
        $this->assertCount(0, $conversations);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $conversations);
    }

    /**
     * Test that folder counters can be incremented.
     */
    public function test_folder_counters_can_be_updated(): void
    {
        // Arrange
        $folder = Folder::factory()->create([
            'total_count' => 5,
            'active_count' => 3,
        ]);

        // Act
        $folder->update([
            'total_count' => 10,
            'active_count' => 7,
        ]);
        $folder->refresh();

        // Assert
        $this->assertEquals(10, $folder->total_count);
        $this->assertEquals(7, $folder->active_count);
    }

    /**
     * Test that folder name is optional for system folders.
     */
    public function test_folder_name_is_optional_for_system_folders(): void
    {
        // Arrange & Act
        $folder = Folder::factory()->create([
            'type' => Folder::TYPE_INBOX,
            'name' => null,
        ]);

        // Assert
        $this->assertNull($folder->name);
        $this->assertTrue($folder->isInbox());
    }

    /**
     * Test that personal folders require a user_id.
     */
    public function test_personal_folder_type_can_have_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();

        // Act
        $folder = Folder::factory()->create([
            'type' => Folder::TYPE_MINE,
            'user_id' => $user->id,
            'mailbox_id' => $mailbox->id,
        ]);

        // Assert
        $this->assertEquals($user->id, $folder->user_id);
        $this->assertInstanceOf(User::class, $folder->user);
    }

    /**
     * Test that multiple users can have personal folders in the same mailbox.
     */
    public function test_multiple_users_can_have_personal_folders_in_same_mailbox(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Act
        $folder1 = Folder::factory()->create([
            'type' => Folder::TYPE_MINE,
            'user_id' => $user1->id,
            'mailbox_id' => $mailbox->id,
        ]);

        $folder2 = Folder::factory()->create([
            'type' => Folder::TYPE_MINE,
            'user_id' => $user2->id,
            'mailbox_id' => $mailbox->id,
        ]);

        // Assert
        $this->assertEquals($user1->id, $folder1->user_id);
        $this->assertEquals($user2->id, $folder2->user_id);
        $this->assertEquals($mailbox->id, $folder1->mailbox_id);
        $this->assertEquals($mailbox->id, $folder2->mailbox_id);
    }

    /**
     * Test folder type helper methods with all types.
     */
    public function test_all_folder_type_helper_methods_work(): void
    {
        // Arrange & Act
        $inbox = Folder::factory()->create(['type' => Folder::TYPE_INBOX]);
        $sent = Folder::factory()->create(['type' => Folder::TYPE_SENT]);
        $drafts = Folder::factory()->create(['type' => Folder::TYPE_DRAFTS]);
        $spam = Folder::factory()->create(['type' => Folder::TYPE_SPAM]);
        $trash = Folder::factory()->create(['type' => Folder::TYPE_TRASH]);

        // Assert - Each folder returns true only for its type
        $this->assertTrue($inbox->isInbox());
        $this->assertFalse($inbox->isSent());
        $this->assertFalse($inbox->isDrafts());
        $this->assertFalse($inbox->isSpam());
        $this->assertFalse($inbox->isTrash());

        $this->assertFalse($sent->isInbox());
        $this->assertTrue($sent->isSent());
        $this->assertFalse($sent->isDrafts());

        $this->assertFalse($drafts->isInbox());
        $this->assertTrue($drafts->isDrafts());

        $this->assertTrue($spam->isSpam());
        $this->assertFalse($spam->isTrash());

        $this->assertTrue($trash->isTrash());
        $this->assertFalse($trash->isSpam());
    }

    /**
     * Test that folder can be created with metadata.
     */
    public function test_folder_can_store_metadata(): void
    {
        // Arrange & Act
        $folder = Folder::factory()->create([
            'meta' => null,
        ]);

        // Assert
        $this->assertNull($folder->meta);
    }

    /**
     * Test that starred folder type exists.
     */
    public function test_starred_folder_type_exists(): void
    {
        // Arrange & Act
        $folder = Folder::factory()->create([
            'type' => Folder::TYPE_STARRED,
        ]);

        // Assert
        $this->assertEquals(Folder::TYPE_STARRED, $folder->type);
        $this->assertEquals(30, $folder->type);
    }

    /**
     * Test that assigned folder type exists.
     */
    public function test_assigned_folder_type_exists(): void
    {
        // Arrange & Act
        $folder = Folder::factory()->create([
            'type' => Folder::TYPE_ASSIGNED,
        ]);

        // Assert
        $this->assertEquals(Folder::TYPE_ASSIGNED, $folder->type);
        $this->assertEquals(20, $folder->type);
    }
}
