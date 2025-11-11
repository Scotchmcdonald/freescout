<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Folder;
use Illuminate\Console\Command;

class UpdateFolderCounters extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:update-folder-counters';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update counters for all folders';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $folders = Folder::all();

        if ($folders->isEmpty()) {
            $this->info('No folders found');
            return 0;
        }

        $this->info("Updating counters for {$folders->count()} folders...");

        $progressBar = $this->output->createProgressBar($folders->count());
        $progressBar->start();

        foreach ($folders as $folder) {
            try {
                $folder->updateCounters();
                $progressBar->advance();
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Error updating folder {$folder->id}: " . $e->getMessage());
            }
        }

        $progressBar->finish();
        $this->newLine();
        $this->info('Updating finished successfully!');

        return 0;
    }
}
