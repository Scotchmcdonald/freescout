<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\Conversation;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use Tests\IntegrationTestCase;

class FolderEnhancedTest extends IntegrationTestCase
{
    public function test_folder_belongs_to_mailbox(): void
    {
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id]);

        $this->assertInstanceOf(Mailbox::class, $folder->mailbox);
        $this->assertEquals($mailbox->id, $folder->mailbox->id);
    }

    public function test_folder_can_belong_to_user(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $folder->user);
        $this->assertEquals($user->id, $folder->user->id);
    }

    public function test_folder_has_many_conversations(): void
    {
        $folder = Folder::factory()->create();
        Conversation::factory()->count(3)->create(['folder_id' => $folder->id]);

        $this->assertCount(3, $folder->conversations);
    }

    public function test_folder_can_be_system_folder(): void
    {
        $folder = Folder::factory()->create(['user_id' => null, 'type' => Folder::TYPE_INBOX]);

        $this->assertNull($folder->user_id);
        $this->assertEquals(Folder::TYPE_INBOX, $folder->type);
    }

    public function test_folder_can_be_user_specific(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($user->id, $folder->user_id);
    }

    public function test_folder_eager_loads_relationships(): void
    {
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id]);
        Conversation::factory()->create(['folder_id' => $folder->id]);

        $loaded = Folder::with(['mailbox', 'conversations', 'user'])->first();

        $this->assertTrue($loaded->relationLoaded('mailbox'));
        $this->assertTrue($loaded->relationLoaded('conversations'));
    }

    // ===== BASIC FOLDER MODEL TESTS (Merged from FolderModelTest.php) =====

    public function test_folder_model_type_constants_are_defined(): void
    {
        $this->assertEquals(1, Folder::TYPE_INBOX);
        $this->assertEquals(2, Folder::TYPE_UNASSIGNED);
        $this->assertEquals(6, Folder::TYPE_SENT);
        $this->assertEquals(3, Folder::TYPE_DRAFTS);
        $this->assertEquals(4, Folder::TYPE_SPAM);
        $this->assertEquals(5, Folder::TYPE_TRASH);
        $this->assertEquals(20, Folder::TYPE_ASSIGNED);
        $this->assertEquals(25, Folder::TYPE_MINE);
        $this->assertEquals(30, Folder::TYPE_STARRED);
    }

    public function test_folder_model_belongs_to_mailbox(): void
    {
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        $this->assertInstanceOf(Mailbox::class, $folder->mailbox);
        $this->assertEquals($mailbox->id, $folder->mailbox->id);
    }

    public function test_folder_model_can_be_inbox_type(): void
    {
        $folder = Folder::factory()->create([
            'type' => Folder::TYPE_INBOX,
        ]);

        $this->assertEquals(Folder::TYPE_INBOX, $folder->type);
    }
}
