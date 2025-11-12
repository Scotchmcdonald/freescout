<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Folder;
use App\Models\Mailbox;
use Tests\UnitTestCase;

class FolderModelTest extends UnitTestCase
{

    public function test_folder_type_constants_are_defined(): void
    {
        $this->assertEquals(1, Folder::TYPE_INBOX);
        $this->assertEquals(2, Folder::TYPE_SENT);
        $this->assertEquals(3, Folder::TYPE_DRAFTS);
        $this->assertEquals(4, Folder::TYPE_SPAM);
        $this->assertEquals(5, Folder::TYPE_TRASH);
        $this->assertEquals(20, Folder::TYPE_ASSIGNED);
        $this->assertEquals(25, Folder::TYPE_MINE);
        $this->assertEquals(30, Folder::TYPE_STARRED);
    }

    public function test_folder_belongs_to_mailbox(): void
    {
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        $this->assertInstanceOf(Mailbox::class, $folder->mailbox);
        $this->assertEquals($mailbox->id, $folder->mailbox->id);
    }

    public function test_folder_can_be_inbox_type(): void
    {
        $folder = Folder::factory()->create([
            'type' => Folder::TYPE_INBOX,
        ]);

        $this->assertEquals(Folder::TYPE_INBOX, $folder->type);
    }
}
