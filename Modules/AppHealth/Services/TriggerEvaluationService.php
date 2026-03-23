<?php

declare(strict_types=1);

namespace Modules\AppHealth\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\AppHealth\Contracts\MetricIngestionContract;
use Modules\AppHealth\Contracts\TriggerEvaluatorContract;
use Modules\AppHealth\Models\ScalingScorecardSnapshot;

class TriggerEvaluationService implements TriggerEvaluatorContract
{
    public function __construct(private readonly MetricIngestionContract $ingestion) {}
    public function evaluate(?array $input = null): array
    {
        $resolvedInput = $input ?? $this->resolveInputsFromConfigAndRuntime();
        $thresholds = config('apphealth.thresholds', []);

        $checks = [
            $this->makeCheck(
                'api_p95_latency',
                (float) ($resolvedInput['api_p95_seconds'] ?? 0.0),
                (float) ($thresholds['api_p95_seconds'] ?? 2.0),
                '>'
            ),
            $this->makeCheck(
                'queue_wait_p95',
                (float) ($resolvedInput['queue_wait_p95_seconds'] ?? 0.0),
                (float) ($thresholds['queue_wait_p95_seconds'] ?? 30.0),
                '>'
            ),
            $this->makeCheck(
                'failed_job_ratio',
                (float) ($resolvedInput['failed_job_ratio'] ?? 0.0),
                (float) ($thresholds['failed_job_ratio'] ?? 0.001),
                '>'
            ),
            $this->makeCheck(
                'worker_cpu_cumulative_breach_minutes',
                (float) ($resolvedInput['worker_cpu_breach_minutes_24h'] ?? 0.0),
                (float) ($thresholds['worker_cpu_breach_minutes_24h'] ?? 240),
                '>'
            ),
            $this->makeCheck(
                'db_cpu_cumulative_breach_minutes',
                (float) ($resolvedInput['db_cpu_breach_minutes_24h'] ?? 0.0),
                (float) ($thresholds['db_cpu_breach_minutes_24h'] ?? 240),
                '>'
            ),
        ];

        $breachCount = collect($checks)->where('breached', true)->count();
        $shouldScheduleStageA = $breachCount >= 2;

        return [
            'evaluated_at' => now()->toIso8601String(),
            'snapshot_date' => now()->toDateString(),
            'stage' => 'A',
            'checks' => $checks,
            'breach_count' => $breachCount,
            'recommendation' => $shouldScheduleStageA ? 'schedule_stage_a_work' : 'continue_observation',
            'overall_status' => $shouldScheduleStageA ? 'warning' : 'ok',
            'inputs' => $resolvedInput,
        ];
    }

    public function persistDailyScorecard(?array $input = null): ScalingScorecardSnapshot
    {
        $scorecard = $this->evaluate($input);

        return ScalingScorecardSnapshot::query()->updateOrCreate(
            ['snapshot_date' => $scorecard['snapshot_date']],
            [
                'overall_status' => (string) $scorecard['overall_status'],
                'recommendation' => (string) $scorecard['recommendation'],
                'breach_count' => (int) $scorecard['breach_count'],
                'payload' => $scorecard,
                'evaluated_at' => now(),
            ]
        );
    }

    /**
     * @return array<string, float|int>
     */
    private function resolveInputsFromConfigAndRuntime(): array
    {
        $configured = config('apphealth.inputs', []);

        // Fetch live runtime signals when ingestion is enabled
        $ingested = config('apphealth.ingestion.enabled', true)
            ? $this->safeIngest()
            : [];

        return [
            'api_p95_seconds' => $this->floatInput($configured['api_p95_seconds'] ?? null)
                ?: $this->floatInput($ingested['api_p95_seconds'] ?? null),
            'queue_wait_p95_seconds' => $this->floatInput($configured['queue_wait_p95_seconds'] ?? null)
                ?: $this->floatInput($ingested['queue_wait_p95_seconds'] ?? null),
            'failed_job_ratio' => $this->floatInput($configured['failed_job_ratio'] ?? null)
                ?: $this->floatInput($ingested['failed_job_ratio'] ?? $this->failedJobRatioFallback()),
            'worker_cpu_breach_minutes_24h' => $this->floatInput($configured['worker_cpu_breach_minutes_24h'] ?? null)
                ?: $this->floatInput($ingested['worker_cpu_breach_minutes_24h'] ?? null),
            'db_cpu_breach_minutes_24h' => $this->floatInput($configured['db_cpu_breach_minutes_24h'] ?? null)
                ?: $this->floatInput($ingested['db_cpu_breach_minutes_24h'] ?? null),
        ];
    }

    /**
     * @return array<string, float>
     */
    private function safeIngest(): array
    {
        try {
            return $this->ingestion->fetchInputs();
        } catch (\Throwable) {
            return [];
        }
    }

    private function floatInput(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        return 0.0;
    }

    /**
     * @return array<string, mixed>
     */
    private function makeCheck(string $name, float $actual, float $threshold, string $operator): array
    {
        $breached = $operator === '>' ? $actual > $threshold : $actual < $threshold;

        return [
            'name' => $name,
            'actual' => $actual,
            'threshold' => $threshold,
            'operator' => $operator,
            'breached' => $breached,
            'status' => $breached ? 'breached' : 'ok',
        ];
    }

    private function failedJobRatioFallback(): float
    {
        try {
            if (! Schema::hasTable('failed_jobs')) {
                return 0.0;
            }

            $failedLastDay = (int) DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subDay())
                ->count();

            if (! Schema::hasTable('jobs')) {
                return $failedLastDay > 0 ? 1.0 : 0.0;
            }

            $queuedNow = (int) DB::table('jobs')->count();
            $denominator = max($failedLastDay + $queuedNow, 1);

            return $failedLastDay / $denominator;
        } catch (\Throwable) {
            return 0.0;
        }
    }
}
