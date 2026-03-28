<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\DatabaseManager;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;

/**
 * Encapsulates all raw DB access for system-level diagnostics,
 * removing the need for controllers to touch the DB layer directly.
 *
 * Extracted from App\Http\Controllers\SystemController and
 * App\Http\Controllers\Admin\ResilienceController to satisfy the
 * 'controllers cannot call DB facade directly' architecture rule.
 */
final class SystemDiagnosticsService
{
    public function __construct(private readonly DatabaseManager $db) {}

    /**
     * Detect and return the database engine version string.
     */
    public function getDatabaseVersion(): string
    {
        try {
            $driver = $this->db->connection()->getDriverName();

            return match ($driver) {
                'mysql' => $this->db->select('SELECT VERSION() as version')[0]->version ?? 'Unknown',
                'sqlite' => $this->db->select('SELECT sqlite_version() as version')[0]->version ?? 'Unknown',
                'pgsql' => $this->db->select('SELECT version()')[0]->version ?? 'Unknown',
                default => 'Unknown',
            };
        } catch (\Throwable) {
            return 'Unknown';
        }
    }

    /**
     * Verify the database connection is alive.
     *
     * @return array{status: string, message: string}
     */
    public function checkDatabaseConnection(): array
    {
        try {
            $this->db->connection()->getPdo();

            return ['status' => 'ok', 'message' => 'Database connection successful'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Report on the queue health via the failed_jobs table.
     *
     * @return array{status: string, message: string}
     */
    public function checkQueueHealth(): array
    {
        try {
            if (! $this->db->getSchemaBuilder()->hasTable('failed_jobs')) {
                return ['status' => 'ok', 'message' => 'Queue table not configured'];
            }

            $failedJobs = $this->db->table('failed_jobs')->count();

            if ($failedJobs > 0) {
                return ['status' => 'warning', 'message' => "{$failedJobs} failed jobs found"];
            }

            return ['status' => 'ok', 'message' => 'Queue is healthy'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Paginated list of failed jobs, newest first.
     *
     * @return LengthAwarePaginator<int, \stdClass>
     */
    public function getFailedJobs(int $perPage = 50): LengthAwarePaginator
    {
        return $this->db->table('failed_jobs')->orderBy('failed_at', 'desc')->paginate($perPage);
    }

    /**
     * Delete all failed jobs for the given queue name.
     */
    public function deleteFailedJobsByQueue(string $queue): void
    {
        $this->db->table('failed_jobs')->where('queue', $queue)->delete();
    }

    /**
     * Retrieve all failed jobs for a given queue name.
     *
     * @return Collection<int, \stdClass>
     */
    public function getFailedJobsByQueue(string $queue): Collection
    {
        return $this->db->table('failed_jobs')->where('queue', $queue)->get();
    }

    /**
     * Cancel (delete) a pending job from the jobs table.
     */
    public function cancelJob(int|string $jobId): void
    {
        $this->db->table('jobs')->where('id', $jobId)->delete();
    }

    /**
     * Make a pending job immediately available for retry.
     */
    public function retryJob(int|string $jobId): void
    {
        $this->db->table('jobs')->where('id', $jobId)->update(['available_at' => time()]);
    }

    /**
     * Paginated event audit log from the polycast_events table, with optional
     * search/filter criteria applied.
     *
     * @param  array{search?: string, event_type?: string, date_from?: string, date_to?: string}  $filters
     * @return LengthAwarePaginator<int, \stdClass>
     */
    public function getPolycastEvents(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->db->table('polycast_events');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('event', 'like', "%{$search}%")
                    ->orWhere('payload', 'like', "%{$search}%")
                    ->orWhere('channel', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['event_type'])) {
            $query->where('event', 'like', "%{$filters['event_type']}%");
        }

        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to'].' 23:59:59');
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();
    }

    /**
     * Stream all matching polycast_events rows as CSV rows through a callback.
     *
     * @param  array{search?: string, event_type?: string, date_from?: string, date_to?: string}  $filters
     */
    public function streamPolycastEventsCsv(array $filters, callable $rowCallback): void
    {
        $query = $this->db->table('polycast_events');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('event', 'like', "%{$search}%")
                    ->orWhere('payload', 'like', "%{$search}%")
                    ->orWhere('channel', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['event_type'])) {
            $query->where('event', 'like', "%{$filters['event_type']}%");
        }

        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to'].' 23:59:59');
        }

        $query->orderBy('created_at', 'desc')->chunk(200, function (Collection $rows) use ($rowCallback): void {
            foreach ($rows as $row) {
                $rowCallback($row);
            }
        });
    }

    /**
     * Retry all failed jobs for a given queue via artisan queue:retry.
     */
    public function retryFailedJobsByQueue(string $queue): void
    {
        $jobs = $this->getFailedJobsByQueue($queue);

        foreach ($jobs as $job) {
            /** @var object{uuid: string} $job */
            Artisan::call('queue:retry', ['id' => [$job->uuid]]);
        }
    }
}
