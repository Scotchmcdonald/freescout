<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Conversation;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use Tests\UnitTestCase;

class FolderEnhancedTest extends UnitTestCase
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
}
