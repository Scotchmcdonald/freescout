<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Console;

use Illuminate\Console\Command;
use Modules\MiddleMan\Models\MiddleManAuditEntry;
use Modules\MiddleMan\Models\MiddleManIntercept;
use Modules\MiddleMan\Models\MiddleManLog;

/**
 * Auto-pruning command to prevent log/intercept tables from exhausting disk space.
 *
 * Safe to run via scheduler (daily recommended) or manually.
 * Deletes in batches to avoid locking the table during large purges.
 *
 * Usage:
 *   php artisan middleman:prune                 # Uses config default (7 days)
 *   php artisan middleman:prune --days=30       # Keep 30 days
 *   php artisan middleman:prune --days=1        # Aggressive cleanup
 *   php artisan middleman:prune --dry-run       # Preview what would be deleted
 */
class PruneCommand extends Command
{
    protected $signature = 'middleman:prune
        {--days= : Number of days to retain (default: config value)}
        {--batch=1000 : Delete batch size to avoid table locking}
        {--dry-run : Preview counts without deleting}
        {--include-audit : Also prune the audit trail (excluded by default)}';

    protected $description = 'Prune old MiddleMan logs, completed intercepts, and optionally audit entries';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('middleman.log_retention_days', 7));
        $batchSize = (int) $this->option('batch');
        $dryRun = (bool) $this->option('dry-run');
        $includeAudit = (bool) $this->option('include-audit');

        $cutoff = now()->subDays($days);

        $this->info("MiddleMan Prune — Retention: {$days} days (cutoff: {$cutoff->toDateTimeString()})");

        if ($dryRun) {
            $this->warn('DRY RUN — no records will be deleted.');
        }

        $this->newLine();

        // 1. Prune logs
        $logCount = $this->pruneTable(
            'Logs',
            fn () => MiddleManLog::where('fired_at', '<', $cutoff),
            $batchSize,
            $dryRun,
        );

        // 2. Prune completed/discarded intercepts
        $interceptCount = $this->pruneTable(
            'Intercepts (fired/discarded)',
            fn () => MiddleManIntercept::whereIn('status', [
                MiddleManIntercept::STATUS_FIRED,
                MiddleManIntercept::STATUS_DISCARDED,
                'corrupted',
            ])->where('updated_at', '<', $cutoff),
            $batchSize,
            $dryRun,
        );

        // 3. Optionally prune audit trail
        $auditCount = 0;
        if ($includeAudit) {
            $auditCount = $this->pruneTable(
                'Audit Trail',
                fn () => MiddleManAuditEntry::where('created_at', '<', $cutoff),
                $batchSize,
                $dryRun,
            );
        }

        $this->newLine();
        $total = $logCount + $interceptCount + $auditCount;

        if ($dryRun) {
            $this->info("Would delete {$total} total records.");
        } else {
            $this->info("Pruned {$total} total records.");
        }

        return self::SUCCESS;
    }

    /**
     * Delete records in batches to avoid table-level locks.
     */
    private function pruneTable(string $label, callable $queryFactory, int $batchSize, bool $dryRun): int
    {
        $query = $queryFactory();
        $total = $query->count();

        if ($total === 0) {
            $this->line("  [{$label}] No records to prune.");
            return 0;
        }

        if ($dryRun) {
            $this->line("  [{$label}] Would delete {$total} records.");
            return $total;
        }

        $deleted = 0;
        do {
            $batch = $queryFactory()
                ->limit($batchSize)
                ->pluck('id');

            if ($batch->isEmpty()) {
                break;
            }

            // Use the correct model class based on the query
            $batchDeleted = $queryFactory()
                ->whereIn('id', $batch->all())
                ->delete();

            $deleted += $batchDeleted;

            $this->output->write("\r  [{$label}] Deleted {$deleted}/{$total}...");
        } while ($batchDeleted > 0);

        $this->line("\r  [{$label}] Deleted {$deleted} records.          ");

        return $deleted;
    }
}
