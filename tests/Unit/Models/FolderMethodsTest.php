<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Conversation;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use Tests\UnitTestCase;

/**
 * Tests for Folder model methods and constants added during migration.
 */
class FolderMethodsTest extends UnitTestCase
{
    protected User $user;
    protected Mailbox $mailbox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->mailbox = Mailbox::factory()->create();
        $this->mailbox->users()->attach($this->user->id);
    }

    // ===== TYPE_DELETED constant tests =====

    public function test_type_deleted_constant_exists(): void
    {
        $this->assertTrue(defined('App\Models\Folder::TYPE_DELETED'));
    }

    public function test_type_deleted_constant_is_integer(): void
    {
        $this->assertIsInt(Folder::TYPE_DELETED);
    }

    public function test_type_deleted_constant_value_is_unique(): void
    {
        $usedTypes = [
            Folder::TYPE_INBOX,
            Folder::TYPE_DRAFTS,
            Folder::TYPE_ASSIGNED,
            Folder::TYPE_CLOSED,
            Folder::TYPE_SPAM,
            Folder::TYPE_DELETED,
        ];

        // Check that TYPE_DELETED is unique among all types
        $this->assertEquals(count($usedTypes), count(array_unique($usedTypes)));
    }

    // ===== Folder creation tests =====

    public function test_deleted_folder_can_be_created(): void
    {
        $folder = Folder::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'type' => Folder::TYPE_DELETED,
            'name' => 'Deleted',
        ]);

        $this->assertEquals(Folder::TYPE_DELETED, $folder->type);
        $this->assertDatabaseHas('folders', [
            'id' => $folder->id,
            'type' => Folder::TYPE_DELETED,
        ]);
    }

    public function test_deleted_folder_counts_soft_deleted_conversations(): void
    {
        $deletedFolder = Folder::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'type' => Folder::TYPE_DELETED,
        ]);

        // Create some conversations in the deleted folder
        $conv1 = Conversation::factory()->for($this->mailbox)->create([
            'folder_id' => $deletedFolder->id,
            'state' => Conversation::STATE_DELETED,
        ]);

        $conv2 = Conversation::factory()->for($this->mailbox)->create([
            'folder_id' => $deletedFolder->id,
            'state' => Conversation::STATE_DELETED,
        ]);

        $this->assertEquals(2, Conversation::where('folder_id', $deletedFolder->id)->count());
    }

    // ===== Folder type identification tests =====

    public function test_can_identify_deleted_folder(): void
    {
        $folder = Folder::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'type' => Folder::TYPE_DELETED,
        ]);

        $this->assertEquals(Folder::TYPE_DELETED, $folder->type);
        $this->assertTrue($folder->type === Folder::TYPE_DELETED);
    }

    public function test_deleted_folder_is_different_from_spam(): void
    {
        $this->assertNotEquals(Folder::TYPE_DELETED, Folder::TYPE_SPAM);
    }

    public function test_deleted_folder_is_different_from_closed(): void
    {
        $this->assertNotEquals(Folder::TYPE_DELETED, Folder::TYPE_CLOSED);
    }

    // ===== Folder relationship tests =====

    public function test_deleted_folder_belongs_to_mailbox(): void
    {
        $folder = Folder::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'type' => Folder::TYPE_DELETED,
        ]);

        $this->assertInstanceOf(Mailbox::class, $folder->mailbox);
        $this->assertEquals($this->mailbox->id, $folder->mailbox->id);
    }

    public function test_deleted_folder_has_conversations(): void
    {
        $folder = Folder::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'type' => Folder::TYPE_DELETED,
        ]);

        $conv = Conversation::factory()->for($this->mailbox)->create([
            'folder_id' => $folder->id,
        ]);

        $this->assertTrue($folder->conversations->contains($conv->id));
    }

    // ===== Mailbox folder types tests =====

    public function test_mailbox_can_have_deleted_folder(): void
    {
        $folder = Folder::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'type' => Folder::TYPE_DELETED,
            'name' => 'Deleted',
        ]);

        $this->assertTrue($this->mailbox->folders->contains($folder));
    }

    public function test_multiple_folder_types_can_coexist(): void
    {
        Folder::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'type' => Folder::TYPE_INBOX,
        ]);

        Folder::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'type' => Folder::TYPE_SPAM,
        ]);

        $deletedFolder = Folder::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'type' => Folder::TYPE_DELETED,
        ]);

        $this->mailbox->refresh();

        $types = $this->mailbox->folders->pluck('type')->toArray();
        $this->assertContains(Folder::TYPE_INBOX, $types);
        $this->assertContains(Folder::TYPE_SPAM, $types);
        $this->assertContains(Folder::TYPE_DELETED, $types);
    }

    // ===== Folder active count tests =====

    public function test_deleted_folder_active_count_respects_state(): void
    {
        $deletedFolder = Folder::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'type' => Folder::TYPE_DELETED,
        ]);

        // Create conversation in deleted state
        Conversation::factory()->for($this->mailbox)->create([
            'folder_id' => $deletedFolder->id,
            'state' => Conversation::STATE_DELETED,
        ]);

        // Create conversation in published state (shouldn't normally be in deleted folder)
        Conversation::factory()->for($this->mailbox)->create([
            'folder_id' => $deletedFolder->id,
            'state' => Conversation::STATE_PUBLISHED,
        ]);

        $deletedStateCount = Conversation::where('folder_id', $deletedFolder->id)
            ->where('state', Conversation::STATE_DELETED)
            ->count();

        $this->assertEquals(1, $deletedStateCount);
    }
}
