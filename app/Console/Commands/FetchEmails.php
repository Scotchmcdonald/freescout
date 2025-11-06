<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Mailbox;
use App\Services\ImapService;
use Illuminate\Console\Command;

class FetchEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:fetch-emails 
                            {mailbox_id? : Specific mailbox ID to fetch from}
                            {--test : Test connection without fetching}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch emails from mailbox IMAP servers';

    /**
     * Execute the console command.
     */
    public function handle(ImapService $imapService): int
    {
        $mailboxId = $this->argument('mailbox_id');
        $testMode = $this->option('test');

        // Get mailboxes to process
        $mailboxes = $mailboxId
            ? Mailbox::where('id', $mailboxId)->get()
            : Mailbox::whereNotNull('in_server')->where('in_server', '!=', '')->get();

        if ($mailboxes->isEmpty()) {
            $this->warn('No mailboxes configured for IMAP.');

            return 1;
        }

        $this->info('Processing '.$mailboxes->count().' mailbox(es)...');

        $totalFetched = 0;
        $totalCreated = 0;
        $totalErrors = 0;

        foreach ($mailboxes as $mailbox) {
            $this->line('');
            $this->info("Processing mailbox: {$mailbox->name} ({$mailbox->email})");

            if ($testMode) {
                // Test connection only
                $result = $imapService->testConnection($mailbox);

                if ($result['success']) {
                    $this->info('✓ '.$result['message']);
                } else {
                    $this->error('✗ '.$result['message']);
                    $totalErrors++;
                }
            } else {
                // Fetch emails
                $stats = $imapService->fetchEmails($mailbox);

                $this->line("  Fetched: {$stats['fetched']}");
                $this->line("  Created: {$stats['created']}");
                $this->line("  Errors: {$stats['errors']}");

                if (! empty($stats['messages'])) {
                    foreach ($stats['messages'] as $message) {
                        $this->warn("  • {$message}");
                    }
                }

                $totalFetched += $stats['fetched'];
                $totalCreated += $stats['created'];
                $totalErrors += $stats['errors'];
            }
        }

        if (! $testMode) {
            $this->line('');
            $this->info('=== Summary ===');
            $this->line("Total fetched: {$totalFetched}");
            $this->line("Total created: {$totalCreated}");
            $this->line("Total errors: {$totalErrors}");
        }

        return $totalErrors > 0 ? 1 : 0;
    }
}
