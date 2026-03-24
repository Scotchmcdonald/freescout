<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Folder;
use Tests\PureUnitTestCase;

final class TestFolderHelper extends Folder
{
    protected function casts(): array
    {
        return [];
    }
}

class FolderHelperTest extends PureUnitTestCase
{
    private function folder(int $type): TestFolderHelper
    {
        $f = new TestFolderHelper;
        $f->type = $type;

        return $f;
    }

    public function test_folder_type_helpers_return_true_only_for_matching_type(): void
    {
        $inbox = $this->folder(Folder::TYPE_INBOX);
        $sent = $this->folder(Folder::TYPE_SENT);
        $drafts = $this->folder(Folder::TYPE_DRAFTS);
        $spam = $this->folder(Folder::TYPE_SPAM);
        $trash = $this->folder(Folder::TYPE_TRASH);

        // isInbox
        $this->assertTrue($inbox->isInbox());
        $this->assertFalse($sent->isInbox());

        // isSent
        $this->assertTrue($sent->isSent());
        $this->assertFalse($inbox->isSent());

        // isDrafts
        $this->assertTrue($drafts->isDrafts());
        $this->assertFalse($inbox->isDrafts());

        // isSpam
        $this->assertTrue($spam->isSpam());
        $this->assertFalse($inbox->isSpam());

        // isTrash
        $this->assertTrue($trash->isTrash());
        $this->assertFalse($inbox->isTrash());
    }

    public function test_all_type_constants_are_distinct(): void
    {
        $types = [
            Folder::TYPE_INBOX,
            Folder::TYPE_UNASSIGNED,
            Folder::TYPE_DRAFTS,
            Folder::TYPE_SPAM,
            Folder::TYPE_TRASH,
            Folder::TYPE_SENT,
            Folder::TYPE_CLOSED,
            Folder::TYPE_DELETED,
            Folder::TYPE_ASSIGNED,
            Folder::TYPE_MINE,
            Folder::TYPE_STARRED,
        ];

        $this->assertSame(count($types), count(array_unique($types)));
    }
}
