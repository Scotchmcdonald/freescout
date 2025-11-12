<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Conversation;
use App\Models\Folder;
use App\Models\Mailbox;
use Tests\UnitTestCase;

class MailboxObserverTest extends UnitTestCase
{

    public function test_created_creates_default_folders(): void
    {
        $mailbox = Mailbox::factory()->create();

        $this->assertDatabaseHas('folders', [
            'mailbox_id' => $mailbox->id,
            'type' => Folder::TYPE_INBOX,
            'name' => 'Inbox',
        ]);

        $this->assertDatabaseHas('folders', [
            'mailbox_id' => $mailbox->id,
            'type' => Folder::TYPE_ASSIGNED,
            'name' => 'Assigned',
        ]);

        $this->assertDatabaseHas('folders', [
            'mailbox_id' => $mailbox->id,
            'type' => Folder::TYPE_DRAFTS,
            'name' => 'Drafts',
        ]);

        $this->assertDatabaseHas('folders', [
            'mailbox_id' => $mailbox->id,
            'type' => Folder::TYPE_SPAM,
            'name' => 'Spam',
        ]);

        $this->assertDatabaseHas('folders', [
            'mailbox_id' => $mailbox->id,
            'type' => Folder::TYPE_TRASH,
            'name' => 'Trash',
        ]);
    }

    public function test_deleting_removes_conversations(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);

        $mailbox->delete();

        $this->assertDatabaseMissing('conversations', ['id' => $conversation->id]);
    }

    public function test_deleting_removes_folders(): void
    {
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id]);

        $mailbox->delete();

        $this->assertDatabaseMissing('folders', ['id' => $folder->id]);
    }
}
