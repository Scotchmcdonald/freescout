<?php

declare(strict_types=1);

namespace Modules\AppHealth\Services;

use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\AppHealth\Contracts\MetricIngestionContract;

/**
 * Ingestion adapter that derives Stage A trigger inputs from runtime signals:
 *
 * - api_p95_seconds:         estimated from histogram buckets recorded by RecordHttpRouteMetrics
 * - queue_wait_p95_seconds:  derived from queue job age (oldest pending job in seconds)
 * - failed_job_ratio:        counts failed_jobs / (failed_jobs + pending_jobs) in last 24h
 * - worker_cpu_breach_minutes_24h:  not available at runtime; returns 0.0 (needs GCP/cloud adapter)
 * - db_cpu_breach_minutes_24h:      not available at runtime; returns 0.0 (needs GCP/cloud adapter)
 *
 * All reads are wrapped in try/catch so a missing table or unavailable cache never
 * propagates to the evaluator — the fallback is 0.0 for each input.
 */
class RuntimeMetricIngestionService implements MetricIngestionContract
{
    private const HISTOGRAM_PREFIX = 'apphealth:histogram:';

    private const HISTOGRAM_INDEX_KEY = 'apphealth:metric:index';

    public function __construct(private readonly CacheRepository $cache) {}

    /**
     * @return array<string, float>
     */
    public function fetchInputs(): array
    {
        return [
            'api_p95_seconds' => $this->estimateApiP95(),
            'queue_wait_p95_seconds' => $this->estimateQueueWaitP95(),
            'failed_job_ratio' => $this->computeFailedJobRatio(),
            'worker_cpu_breach_minutes_24h' => 0.0,
            'db_cpu_breach_minutes_24h' => 0.0,
        ];
    }

    /**
     * Estimate p95 API latency from histogram buckets stored by RecordHttpRouteMetrics.
     *
     * Uses the interpolation: find the two buckets surrounding the 95th-percentile mark
     * and return the lower bound. Defaults to 0.0 when no histogram data is available.
     */
    private function estimateApiP95(): float
    {
        try {
            $bounds = config('apphealth.histogram_buckets', [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0, 10.0]);

            if (! is_array($bounds)) {
                return 0.0;
            }

            $index = $this->cache->get(self::HISTOGRAM_INDEX_KEY, []);

            if (! is_array($index)) {
                return 0.0;
            }

            $histogramKeys = $index[self::HISTOGRAM_PREFIX] ?? [];

            if (! is_array($histogramKeys) || $histogramKeys === []) {
                return 0.0;
            }

            // Aggregate all histogram bucket keys for http_request_duration_seconds
            $bucketCounts = [];

            foreach ($histogramKeys as $key) {
                if (! is_string($key) || ! str_contains($key, 'http_request_duration_seconds')) {
                    continue;
                }

                // Extract le value from key
                if (! preg_match('/le=([^,|]+)/', $key, $m)) {
                    continue;
                }

                $le = $m[1];
                $count = (int) $this->cache->get($key, 0);
                $bucketCounts[$le] = ($bucketCounts[$le] ?? 0) + $count;
            }

            if ($bucketCounts === []) {
                return 0.0;
            }

            // Total count = +Inf bucket
            $total = (int) ($bucketCounts['+Inf'] ?? array_sum($bucketCounts));

            if ($total === 0) {
                return 0.0;
            }

            $target = $total * 0.95;
            $sortedBounds = array_merge(array_filter($bounds, fn ($b) => $b !== '+Inf'), ['+Inf']);
            usort($sortedBounds, fn ($a, $b) => $a === '+Inf' ? 1 : ($b === '+Inf' ? -1 : $a <=> $b));

            $cumulative = 0;

            foreach ($sortedBounds as $bound) {
                $leKey = $bound === '+Inf' ? '+Inf' : (string) (float) $bound;
                $cumulative += $bucketCounts[$leKey] ?? 0;

                if ($cumulative >= $target) {
                    return $bound === '+Inf' ? 10.0 : (float) $bound;
                }
            }

            return 0.0;
        } catch (\Throwable) {
            return 0.0;
        }
    }

    /**
     * Estimate queue wait time p95 from the age of the oldest pending job.
     * This is a conservative approximation — real p95 needs queue timing data.
     */
    private function estimateQueueWaitP95(): float
    {
        try {
            if (! Schema::hasTable('jobs')) {
                return 0.0;
            }

            $oldest = DB::table('jobs')
                ->orderBy('available_at')
                ->value('available_at');

            if ($oldest === null) {
                return 0.0;
            }

            $ageSeconds = max(0, now()->timestamp - (int) $oldest);

            // Apply conservative p95 estimate: assume oldest job captures ~5th percentile of wait time
            // by returning the age directly as a worst-case approximation.
            return (float) $ageSeconds;
        } catch (\Throwable) {
            return 0.0;
        }
    }

    private function computeFailedJobRatio(): float
    {
        try {
            if (! Schema::hasTable('failed_jobs')) {
                return 0.0;
            }

            $failedLastDay = (int) DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subDay()->toDateTimeString())
                ->count();

            if (! Schema::hasTable('jobs')) {
                return $failedLastDay > 0 ? 1.0 : 0.0;
            }

            $pending = (int) DB::table('jobs')->count();
            $denominator = max($failedLastDay + $pending, 1);

            return $failedLastDay / $denominator;
        } catch (\Throwable) {
            return 0.0;
        }
    }
}
