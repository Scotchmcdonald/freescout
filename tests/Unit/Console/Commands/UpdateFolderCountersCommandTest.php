<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\UpdateFolderCounters;
use App\Models\Conversation;
use App\Models\Folder;
use App\Models\Mailbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** @group console */
class UpdateFolderCountersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_folder_counters_updates_all_folders(): void
    {
        $mailbox = Mailbox::factory()->create();

        $folder1 = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => Folder::TYPE_INBOX,
        ]);

        $folder2 = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => Folder::TYPE_DRAFTS,
        ]);

        $this->artisan('freescout:update-folder-counters')
            ->assertExitCode(0);
    }

    public function test_update_folder_counters_filters_by_mailbox_id(): void
    {
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();

        $folder1 = Folder::factory()->create([
            'mailbox_id' => $mailbox1->id,
            'type' => Folder::TYPE_INBOX,
        ]);

        $folder2 = Folder::factory()->create([
            'mailbox_id' => $mailbox2->id,
            'type' => Folder::TYPE_INBOX,
        ]);

        $this->artisan('freescout:update-folder-counters', [
            '--mailbox_id' => $mailbox1->id,
        ])
            ->assertExitCode(0);
    }

    public function test_update_folder_counters_handles_no_folders(): void
    {
        $this->artisan('freescout:update-folder-counters', [
            '--mailbox_id' => 999999,
        ])
            ->expectsOutput('No folders found')
            ->assertExitCode(0);
    }

    public function test_update_folder_counters_with_conversations(): void
    {
        $mailbox = Mailbox::factory()->create();

        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => Folder::TYPE_INBOX,
        ]);

        // Create some conversations for the folder
        Conversation::factory()->count(3)->create([
            'mailbox_id' => $mailbox->id,
            'folder_id' => $folder->id,
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $this->artisan('freescout:update-folder-counters')
            ->assertExitCode(0);
    }

    public function test_update_folder_counters_command_exists(): void
    {
        $this->assertTrue(
            class_exists(UpdateFolderCounters::class)
        );
    }

    public function test_update_folder_counters_with_multiple_mailboxes(): void
    {
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();

        Folder::factory()->create([
            'mailbox_id' => $mailbox1->id,
            'type' => Folder::TYPE_INBOX,
        ]);

        Folder::factory()->create([
            'mailbox_id' => $mailbox1->id,
            'type' => Folder::TYPE_DRAFTS,
        ]);

        Folder::factory()->create([
            'mailbox_id' => $mailbox2->id,
            'type' => Folder::TYPE_INBOX,
        ]);

        $this->artisan('freescout:update-folder-counters')
            ->assertExitCode(0);
    }
}
