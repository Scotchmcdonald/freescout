<?php

declare(strict_types=1);

use App\Services\MetricsService;
use Modules\AppHealth\Services\LegacyMetricsCompatibilityAdapter;

uses(Tests\UnitTestCase::class);

test('recordCounter forwards normalized metric event name and payload', function (): void {
    $metrics = \Mockery::mock(MetricsService::class);
    $metrics->shouldReceive('trackEvent')
        ->once()
        ->with(
            'apphealth.counter.http_requests_total',
            [
                'value' => 12,
                'labels' => ['method' => 'GET', 'status' => 200],
            ]
        );

    $adapter = new LegacyMetricsCompatibilityAdapter($metrics);
    $adapter->recordCounter('http_requests_total', 12, ['method' => 'GET', 'status' => 200]);

    expect(true)->toBeTrue();
});

test('recordObservation forwards observation metric name and labels', function (): void {
    $metrics = \Mockery::mock(MetricsService::class);
    $metrics->shouldReceive('trackEvent')
        ->once()
        ->with(
            'apphealth.observe.request_duration_seconds',
            [
                'value' => 1.234,
                'labels' => ['route' => 'internal.health', 'source' => 'api'],
            ]
        );

    $adapter = new LegacyMetricsCompatibilityAdapter($metrics);
    $adapter->recordObservation('request_duration_seconds', 1.234, ['route' => 'internal.health', 'source' => 'api']);

    expect(true)->toBeTrue();
});

test('adapter accepts empty labels and zero values without mutation', function (): void {
    $metrics = \Mockery::mock(MetricsService::class);
    $metrics->shouldReceive('trackEvent')
        ->once()
        ->with('apphealth.counter.empty_case', ['value' => 0, 'labels' => []]);

    $adapter = new LegacyMetricsCompatibilityAdapter($metrics);
    $adapter->recordCounter('empty_case', 0, []);

    expect(true)->toBeTrue();
});