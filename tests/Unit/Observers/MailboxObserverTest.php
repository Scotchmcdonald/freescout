<?php

declare(strict_types=1);

namespace Tests\Unit\Observers;

use App\Models\Conversation;
use App\Models\Folder;
use App\Models\Mailbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailboxObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_default_folders_when_mailbox_created()
    {
        $mailbox = Mailbox::factory()->create();

        // Should create 5 default folders
        $this->assertEquals(5, Folder::where('mailbox_id', $mailbox->id)->count());
    }

    public function test_it_creates_inbox_folder_when_mailbox_created()
    {
        $mailbox = Mailbox::factory()->create();

        $inbox = Folder::where('mailbox_id', $mailbox->id)
            ->where('type', Folder::TYPE_INBOX)
            ->first();

        $this->assertNotNull($inbox);
        $this->assertEquals('Inbox', $inbox->name);
        $this->assertNull($inbox->user_id); // Global folder
    }

    public function test_it_creates_assigned_folder_when_mailbox_created()
    {
        $mailbox = Mailbox::factory()->create();

        $assigned = Folder::where('mailbox_id', $mailbox->id)
            ->where('type', Folder::TYPE_ASSIGNED)
            ->first();

        $this->assertNotNull($assigned);
        $this->assertEquals('Assigned', $assigned->name);
    }

    public function test_it_creates_drafts_folder_when_mailbox_created()
    {
        $mailbox = Mailbox::factory()->create();

        $drafts = Folder::where('mailbox_id', $mailbox->id)
            ->where('type', Folder::TYPE_DRAFTS)
            ->first();

        $this->assertNotNull($drafts);
        $this->assertEquals('Drafts', $drafts->name);
    }

    public function test_it_creates_spam_folder_when_mailbox_created()
    {
        $mailbox = Mailbox::factory()->create();

        $spam = Folder::where('mailbox_id', $mailbox->id)
            ->where('type', Folder::TYPE_SPAM)
            ->first();

        $this->assertNotNull($spam);
        $this->assertEquals('Spam', $spam->name);
    }

    public function test_it_creates_trash_folder_when_mailbox_created()
    {
        $mailbox = Mailbox::factory()->create();

        $trash = Folder::where('mailbox_id', $mailbox->id)
            ->where('type', Folder::TYPE_TRASH)
            ->first();

        $this->assertNotNull($trash);
        $this->assertEquals('Trash', $trash->name);
    }

    public function test_it_deletes_all_conversations_when_mailbox_deleted()
    {
        $mailbox = Mailbox::factory()->create();
        $conversation1 = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $conversation2 = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);

        $mailbox->delete();

        $this->assertDatabaseMissing('conversations', ['id' => $conversation1->id]);
        $this->assertDatabaseMissing('conversations', ['id' => $conversation2->id]);
    }

    public function test_it_deletes_all_folders_when_mailbox_deleted()
    {
        $mailbox = Mailbox::factory()->create();

        // Should have 5 default folders
        $folders = Folder::where('mailbox_id', $mailbox->id)->get();
        $this->assertCount(5, $folders);

        $mailbox->delete();

        // All folders should be deleted
        $this->assertEquals(0, Folder::where('mailbox_id', $mailbox->id)->count());
    }

    public function test_it_deletes_both_global_and_user_folders_when_mailbox_deleted()
    {
        $mailbox = Mailbox::factory()->create();
        $user = \App\Models\User::factory()->create();

        // Create a user-specific folder
        $userFolder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'user_id' => $user->id,
            'type' => Folder::TYPE_MINE,
        ]);

        // Should have 5 default + 1 user folder = 6 total
        $this->assertEquals(6, Folder::where('mailbox_id', $mailbox->id)->count());

        $mailbox->delete();

        $this->assertEquals(0, Folder::where('mailbox_id', $mailbox->id)->count());
        $this->assertDatabaseMissing('folders', ['id' => $userFolder->id]);
    }
}
