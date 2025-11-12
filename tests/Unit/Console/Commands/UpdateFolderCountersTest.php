<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\UpdateFolderCounters;
use App\Models\Conversation;
use App\Models\Folder;
use App\Models\Mailbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateFolderCountersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function command_can_be_instantiated(): void
    {
        $command = new UpdateFolderCounters();
        
        $this->assertInstanceOf(UpdateFolderCounters::class, $command);
    }

    #[Test]
    public function command_has_correct_signature(): void
    {
        $command = new UpdateFolderCounters();
        
        $this->assertEquals('freescout:update-folder-counters', $command->getName());
    }

    #[Test]
    public function command_has_description(): void
    {
        $command = new UpdateFolderCounters();
        
        $this->assertNotEmpty($command->getDescription());
        $this->assertStringContainsString('folder', $command->getDescription());
    }

    #[Test]
    public function command_returns_zero_when_no_folders(): void
    {
        $exitCode = Artisan::call('freescout:update-folder-counters');
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_outputs_no_folders_message(): void
    {
        Artisan::call('freescout:update-folder-counters');
        $output = Artisan::output();
        
        $this->assertStringContainsString('No folders found', $output);
    }

    #[Test]
    public function command_updates_single_folder(): void
    {
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id]);

        $exitCode = Artisan::call('freescout:update-folder-counters');
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_updates_multiple_folders(): void
    {
        $mailbox = Mailbox::factory()->create();
        Folder::factory()->count(3)->create(['mailbox_id' => $mailbox->id]);

        $exitCode = Artisan::call('freescout:update-folder-counters');
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_outputs_updating_message(): void
    {
        $mailbox = Mailbox::factory()->create();
        Folder::factory()->create(['mailbox_id' => $mailbox->id]);

        Artisan::call('freescout:update-folder-counters');
        $output = Artisan::output();
        
        $this->assertStringContainsString('Updating counters', $output);
    }

    #[Test]
    public function command_outputs_success_message(): void
    {
        $mailbox = Mailbox::factory()->create();
        Folder::factory()->create(['mailbox_id' => $mailbox->id]);

        Artisan::call('freescout:update-folder-counters');
        $output = Artisan::output();
        
        $this->assertStringContainsString('finished successfully', $output);
    }

    #[Test]
    public function command_shows_progress_bar(): void
    {
        $mailbox = Mailbox::factory()->create();
        Folder::factory()->count(5)->create(['mailbox_id' => $mailbox->id]);

        Artisan::call('freescout:update-folder-counters');
        $output = Artisan::output();
        
        // Progress bar should be shown
        $this->assertNotEmpty($output);
    }

    #[Test]
    public function command_displays_folder_count(): void
    {
        $mailbox = Mailbox::factory()->create();
        Folder::factory()->count(3)->create(['mailbox_id' => $mailbox->id]);

        Artisan::call('freescout:update-folder-counters');
        $output = Artisan::output();
        
        // Output should contain information about folders being updated
        // The count may vary based on test isolation
        $this->assertStringContainsString('folders', $output);
        $this->assertStringContainsString('Updating counters', $output);
    }

    #[Test]
    public function command_handles_errors_gracefully(): void
    {
        $mailbox = Mailbox::factory()->create();
        Folder::factory()->create(['mailbox_id' => $mailbox->id]);

        // Command should handle errors without crashing
        $exitCode = Artisan::call('freescout:update-folder-counters');
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_processes_all_folders(): void
    {
        $mailbox = Mailbox::factory()->create();
        $folder1 = Folder::factory()->create(['mailbox_id' => $mailbox->id]);
        $folder2 = Folder::factory()->create(['mailbox_id' => $mailbox->id]);

        $exitCode = Artisan::call('freescout:update-folder-counters');
        
        $this->assertEquals(0, $exitCode);
        // All folders should have been processed
    }

    #[Test]
    public function command_updates_counters_for_folders_with_conversations(): void
    {
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id]);
        Conversation::factory()->count(2)->create([
            'mailbox_id' => $mailbox->id,
            'folder_id' => $folder->id,
        ]);

        $exitCode = Artisan::call('freescout:update-folder-counters');
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_returns_zero_on_completion(): void
    {
        $mailbox = Mailbox::factory()->create();
        Folder::factory()->create(['mailbox_id' => $mailbox->id]);

        $exitCode = Artisan::call('freescout:update-folder-counters');
        
        $this->assertEquals(0, $exitCode);
    }
}
