<?php

declare(strict_types=1);

namespace Modules\AppHealth\Services;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Modules\AppHealth\Contracts\MetricIngestionContract;
use Modules\AppHealth\Contracts\TriggerEvaluatorContract;
use Modules\AppHealth\Models\ScalingScorecardSnapshot;

class TriggerEvaluationService implements TriggerEvaluatorContract
{
    public function __construct(
        private readonly MetricIngestionContract $ingestion,
        private readonly ConnectionInterface $db,
        private readonly SchemaBuilder $schema
    ) {}

    public function evaluate(?array $input = null): array
    {
        $resolvedInput = $input ?? $this->resolveInputsFromConfigAndRuntime();
        $configuredThresholds = config('apphealth.thresholds', []);
        $thresholds = is_array($configuredThresholds) ? $configuredThresholds : [];

        $checks = [
            $this->makeCheck(
                'api_p95_latency',
                (float) ($resolvedInput['api_p95_seconds'] ?? 0.0),
                $this->floatInput($thresholds['api_p95_seconds'] ?? 2.0),
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

        $snapshotDate = is_string($scorecard['snapshot_date'] ?? null) ? $scorecard['snapshot_date'] : now()->toDateString();
        $overallStatus = is_string($scorecard['overall_status'] ?? null) ? $scorecard['overall_status'] : 'ok';
        $recommendation = is_string($scorecard['recommendation'] ?? null) ? $scorecard['recommendation'] : 'continue_observation';
        $breachCount = is_numeric($scorecard['breach_count'] ?? null) ? (int) $scorecard['breach_count'] : 0;

        return ScalingScorecardSnapshot::query()->updateOrCreate(
            ['snapshot_date' => $snapshotDate],
            [
                'overall_status' => $overallStatus,
                'recommendation' => $recommendation,
                'breach_count' => $breachCount,
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
        $configuredInputs = config('apphealth.inputs', []);
        $configured = is_array($configuredInputs) ? $configuredInputs : [];

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
            if (! $this->schema->hasTable('failed_jobs')) {
                return 0.0;
            }

            $failedLastDay = (int) $this->db->table('failed_jobs')
                ->where('failed_at', '>=', now()->subDay())
                ->count();

            if (! $this->schema->hasTable('jobs')) {
                return $failedLastDay > 0 ? 1.0 : 0.0;
            }

            $queuedNow = (int) $this->db->table('jobs')->count();
            $denominator = max($failedLastDay + $queuedNow, 1);

            return $failedLastDay / $denominator;
        } catch (\Throwable) {
            return 0.0;
        }
    }
}
