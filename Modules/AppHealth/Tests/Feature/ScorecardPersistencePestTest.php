<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AppHealth\Jobs\EvaluateScalingTriggersJob;
use Modules\AppHealth\Models\ScalingScorecardSnapshot;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'apphealth.enabled' => true,
        'apphealth.trigger_evaluation_enabled' => true,
        'apphealth.scheduler.enabled' => false,
        'apphealth.inputs' => [
            'api_p95_seconds' => 2.5,
            'queue_wait_p95_seconds' => 50,
            'failed_job_ratio' => 0.002,
            'worker_cpu_breach_minutes_24h' => 260,
            'db_cpu_breach_minutes_24h' => 10,
        ],
    ]);
});

test('trigger evaluator job persists daily scorecard snapshot', function (): void {
    dispatch_sync(new EvaluateScalingTriggersJob);

    $snapshot = ScalingScorecardSnapshot::query()->first();

    expect($snapshot)->not->toBeNull();
    expect($snapshot?->breach_count)->toBeGreaterThanOrEqual(2);
    expect($snapshot?->recommendation)->toBe('schedule_stage_a_work');
});
