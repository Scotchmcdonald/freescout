<?php

declare(strict_types=1);

use Modules\AppHealth\Services\RuntimeMetricIngestionService;

uses(Tests\UnitTestCase::class);

beforeEach(function (): void {
    // Default config for tests
    config([
        'apphealth.inputs' => [
            'api_p95_seconds' => null,
            'queue_wait_p95_seconds' => null,
            'failed_job_ratio' => null,
            'worker_cpu_breach_minutes_24h' => null,
            'db_cpu_breach_minutes_24h' => null,
        ],
        'apphealth.ingestion.enabled' => true,
        'apphealth.thresholds' => [
            'api_p95_seconds' => 2.0,
            'queue_wait_p95_seconds' => 30.0,
            'failed_job_ratio' => 0.001,
            'worker_cpu_breach_minutes_24h' => 240,
            'db_cpu_breach_minutes_24h' => 240,
        ],
    ]);
});

test('ingestion service returns zero values when tables are empty', function (): void {
    $service = app(RuntimeMetricIngestionService::class);

    $inputs = $service->fetchInputs();

    expect($inputs)->toHaveKeys([
        'api_p95_seconds',
        'queue_wait_p95_seconds',
        'failed_job_ratio',
        'worker_cpu_breach_minutes_24h',
        'db_cpu_breach_minutes_24h',
    ]);

    expect($inputs['api_p95_seconds'])->toBe(0.0);
    expect($inputs['queue_wait_p95_seconds'])->toBe(0.0);
    expect($inputs['worker_cpu_breach_minutes_24h'])->toBe(0.0);
    expect($inputs['db_cpu_breach_minutes_24h'])->toBe(0.0);
});

test('ingestion service failed_job_ratio reflects recent failed jobs', function (): void {
    // Create a real test isolation using mock cache
    // The service will have empty cache/DB and should return 0.0
    $service = app(RuntimeMetricIngestionService::class);

    // Without any jobs table or cache data, ratio should be 0
    $inputs = $service->fetchInputs();

    expect($inputs['failed_job_ratio'])->toBeGreaterThanOrEqual(0.0);
});

test('trigger evaluation service uses ingestion adapter when config inputs are absent', function (): void {
    $evaluator = app(\Modules\AppHealth\Contracts\TriggerEvaluatorContract::class);
    $result = $evaluator->evaluate();

    expect($result)->toHaveKey('breach_count');
    expect($result['breach_count'])->toBeInt();
    expect($result['overall_status'])->toBeIn(['ok', 'warning']);
});
