<?php

declare(strict_types=1);

use Modules\AppHealth\Services\TriggerEvaluationService;

uses(Tests\TestCase::class);

test('trigger evaluator flags breaches using stage a thresholds', function (): void {
    config([
        'apphealth.thresholds' => [
            'api_p95_seconds' => 2.0,
            'queue_wait_p95_seconds' => 30.0,
            'failed_job_ratio' => 0.001,
            'worker_cpu_breach_minutes_24h' => 240,
            'db_cpu_breach_minutes_24h' => 240,
        ],
    ]);

    $service = app(TriggerEvaluationService::class);
    $result = $service->evaluate([
        'api_p95_seconds' => 2.7,
        'queue_wait_p95_seconds' => 45.0,
        'failed_job_ratio' => 0.002,
        'worker_cpu_breach_minutes_24h' => 180,
        'db_cpu_breach_minutes_24h' => 200,
    ]);

    expect($result['breach_count'])->toBe(3)
        ->and($result['recommendation'])->toBe('schedule_stage_a_work')
        ->and($result['overall_status'])->toBe('warning');
});

test('trigger evaluator remains in observation mode when fewer than two checks breach', function (): void {
    $service = app(TriggerEvaluationService::class);
    $result = $service->evaluate([
        'api_p95_seconds' => 1.1,
        'queue_wait_p95_seconds' => 22.0,
        'failed_job_ratio' => 0.0001,
        'worker_cpu_breach_minutes_24h' => 50,
        'db_cpu_breach_minutes_24h' => 20,
    ]);

    expect($result['breach_count'])->toBe(0)
        ->and($result['recommendation'])->toBe('continue_observation')
        ->and($result['overall_status'])->toBe('ok');
});
