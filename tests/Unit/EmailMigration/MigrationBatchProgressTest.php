<?php

declare(strict_types=1);

namespace Tests\Unit\EmailMigration;

use Modules\EmailMigration\Models\MigrationBatch;
use Tests\PureUnitTestCase;

if (! class_exists(StubMigrationBatch::class)) {
    final class StubMigrationBatch extends MigrationBatch
    {
        protected static function booted(): void {}

        public function getDateFormat(): string
        {
            return 'Y-m-d H:i:s';
        }
    }
}

final class MigrationBatchProgressTest extends PureUnitTestCase
{
    private function make(int $totalMailboxes, int $completedMailboxes, int $totalEmails, int $migratedEmails): StubMigrationBatch
    {
        $batch = new StubMigrationBatch;
        $batch->setRawAttributes([
            'total_mailboxes' => $totalMailboxes,
            'completed_mailboxes' => $completedMailboxes,
            'total_emails' => $totalEmails,
            'migrated_emails' => $migratedEmails,
        ]);

        return $batch;
    }

    public function test_progress_percent_zero_when_no_mailboxes(): void
    {
        $batch = $this->make(0, 0, 0, 0);
        $this->assertSame(0.0, $batch->progress_percent);
    }

    public function test_progress_percent_half(): void
    {
        $batch = $this->make(10, 5, 100, 50);
        $this->assertSame(50.0, $batch->progress_percent);
    }

    public function test_progress_percent_fully_complete(): void
    {
        $batch = $this->make(4, 4, 0, 0);
        $this->assertSame(100.0, $batch->progress_percent);
    }

    public function test_progress_percent_rounded_to_two_decimals(): void
    {
        // 1/3 = 33.333... → rounds to 33.33
        $batch = $this->make(3, 1, 0, 0);
        $this->assertSame(33.33, $batch->progress_percent);
    }

    public function test_email_progress_percent_zero_when_no_emails(): void
    {
        $batch = $this->make(0, 0, 0, 0);
        $this->assertSame(0.0, $batch->email_progress_percent);
    }

    public function test_email_progress_percent_sixty(): void
    {
        $batch = $this->make(10, 6, 1000, 600);
        $this->assertSame(60.0, $batch->email_progress_percent);
    }

    public function test_email_progress_percent_fully_migrated(): void
    {
        $batch = $this->make(5, 5, 250, 250);
        $this->assertSame(100.0, $batch->email_progress_percent);
    }

    public function test_email_progress_percent_rounded(): void
    {
        // 2/3 = 66.666... → rounds to 66.67
        $batch = $this->make(3, 2, 3, 2);
        $this->assertSame(66.67, $batch->email_progress_percent);
    }
}
