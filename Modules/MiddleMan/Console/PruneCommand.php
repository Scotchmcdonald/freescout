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
 *   php artisan middleman:prune                 # Uses config-driven per-table retention
 *   php artisan middleman:prune --logs-days=3   # Override log retention
 *   php artisan middleman:prune --intercepts-days=30  # Override intercept retention
 *   php artisan middleman:prune --dry-run       # Preview counts without deleting
 *
 * Scheduled daily by routes/console.php (see Schedule::command('middleman:prune')).
 */
class PruneCommand extends Command
{
    protected $signature = 'middleman:prune
        {--logs-days=       : Days to retain logs (overrides config middleman.prune.logs_days)}
        {--intercepts-days= : Days to retain resolved intercepts (overrides config middleman.prune.intercepts_days)}
        {--audit-days=      : Days to retain audit entries (overrides config middleman.prune.audit_days)}
        {--batch=1000 : Delete batch size to avoid table locking}
        {--dry-run : Preview counts without deleting}
        {--include-audit : Also prune the audit trail (excluded by default)}';

    protected $description = 'Prune old MiddleMan logs, resolved intercepts, and optionally audit entries';

    public function handle(): int
    {
        $logsDays       = (int) ($this->option('logs-days')       ?: config('middleman.prune.logs_days', 7));
        $interceptsDays = (int) ($this->option('intercepts-days') ?: config('middleman.prune.intercepts_days', 14));
        $auditDays      = (int) ($this->option('audit-days')      ?: config('middleman.prune.audit_days', 90));
        $batchSize      = (int) $this->option('batch');
        $dryRun         = (bool) $this->option('dry-run');
        $includeAudit   = (bool) $this->option('include-audit');

        $logsCutoff       = now()->subDays($logsDays);
        $interceptsCutoff = now()->subDays($interceptsDays);
        $auditCutoff      = now()->subDays($auditDays);

        $this->info(sprintf(
            'MiddleMan Prune — logs: %d days, intercepts: %d days, audit: %d days',
            $logsDays,
            $interceptsDays,
            $auditDays,
        ));

        if ($dryRun) {
            $this->warn('DRY RUN — no records will be deleted.');
        }

        $this->newLine();

        // 1. Prune logs older than logs_days
        $logCount = $this->pruneTable(
            'Logs',
            fn () => MiddleManLog::where('fired_at', '<', $logsCutoff),
            $batchSize,
            $dryRun,
        );

        // 2. Prune resolved/corrupted intercepts older than intercepts_days
        $interceptCount = $this->pruneTable(
            'Intercepts (fired/discarded/corrupted)',
            fn () => MiddleManIntercept::whereIn('status', [
                MiddleManIntercept::STATUS_FIRED,
                MiddleManIntercept::STATUS_DISCARDED,
                MiddleManIntercept::STATUS_CORRUPTED,
            ])->where('updated_at', '<', $interceptsCutoff),
            $batchSize,
            $dryRun,
        );

        // 3. Optionally prune audit trail entries older than audit_days
        $auditCount = 0;
        if ($includeAudit) {
            $auditCount = $this->pruneTable(
                'Audit Trail',
                fn () => MiddleManAuditEntry::where('created_at', '<', $auditCutoff),
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
