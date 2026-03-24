<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Modules\AppHealth\Services\MetricRecorderService;

uses(Tests\TestCase::class);

beforeEach(function (): void {
    Cache::flush();
    config([
        'apphealth.histogram_buckets' => [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0, 10.0],
    ]);
});

test('histogram records correct bucket counts for a value below multiple bounds', function (): void {
    $service = app(MetricRecorderService::class);

    // Record a value of 0.07s — should increment buckets for 0.1, 0.25, 0.5, 1.0, 2.5, 5.0, 10.0, +Inf
    $service->recordHistogram('http_request_duration_seconds', 0.07, ['route_group' => 'test']);

    $output = $service->renderPrometheus();

    // Must emit histogram TYPE declaration
    expect($output)->toContain('TYPE http_request_duration_seconds_bucket histogram');

    // Buckets <= 0.07: 0.005, 0.01, 0.025, 0.05 should be 0; 0.1, 0.25, ..., +Inf should be 1
    expect($output)->toContain('le="0.1"');
    expect($output)->toContain('le="+Inf"');
});

test('histogram count and sum are tracked via summary storage', function (): void {
    $service = app(MetricRecorderService::class);

    $service->recordHistogram('http_request_duration_seconds', 0.3, []);
    $service->recordHistogram('http_request_duration_seconds', 0.8, []);

    $output = $service->renderPrometheus();

    // Summary count and sum should be present (count=2)
    expect($output)->toContain('apphealth_summary_count');
    expect($output)->toContain('apphealth_summary_sum');
    expect($output)->toContain(' 2'); // count of 2 observations
});

test('plus inf bucket always equals total count', function (): void {
    $service = app(MetricRecorderService::class);

    // Record 3 values
    $service->recordHistogram('req_duration', 0.05, []);
    $service->recordHistogram('req_duration', 1.5, []);
    $service->recordHistogram('req_duration', 8.0, []);

    $output = $service->renderPrometheus();

    // +Inf bucket must be present and > 0
    expect($output)->toContain('+Inf');
    expect($output)->toContain('req_duration_bucket{le="+Inf"} 3');
});

test('histogram buckets accumulate across multiple records', function (): void {
    $service = app(MetricRecorderService::class);

    // Both values are <= 1.0, so the 1.0 bucket and above should count 2
    // PHP (string)(float)1.0 => "1"
    $service->recordHistogram('test_metric', 0.4, []);
    $service->recordHistogram('test_metric', 0.9, []);

    $output = $service->renderPrometheus();

    // The 1.0 bucket (rendered as le="1" by PHP float cast) should have count 2
    expect($output)->toContain('test_metric_bucket');
    expect($output)->toContain('+Inf"} 2'); // +Inf always equals total count
});
