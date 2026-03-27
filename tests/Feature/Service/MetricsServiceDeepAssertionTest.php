<?php

declare(strict_types=1);

/**
 * Deep assertion tests for MetricsService.
 *
 * Unlike the existing integration smoke tests which only verify methods
 * don't throw, these tests assert the correct log level, channel, and
 * context are used for each conditional path — addressing "shallow test"
 * debt where line coverage is high but mutation score is low.
 */

use App\Services\MetricsService;
use Illuminate\Support\Facades\Log;

// ── trackInvoiceGeneration: threshold boundary ─────────────────────

it('logs info for fast invoice and no performance warning', function () {
    Log::shouldReceive('channel')
        ->with('business')
        ->once()
        ->andReturnSelf();
    Log::shouldReceive('info')
        ->once()
        ->withArgs(fn (string $msg) => str_contains($msg, 'invoice.generated'));

    // Below 5000ms — no performance warning expected
    app(MetricsService::class)->trackInvoiceGeneration(1, 100, 4999.0);
});

it('logs performance warning for slow invoice generation above 5000ms', function () {
    Log::shouldReceive('channel')
        ->with('business')
        ->andReturnSelf();
    Log::shouldReceive('info')->once();

    Log::shouldReceive('channel')
        ->with('performance')
        ->once()
        ->andReturnSelf();
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn (string $msg, array $ctx) => str_contains($msg, 'Slow invoice')
            && $ctx['client_id'] === 1
            && $ctx['duration_ms'] === 5001.0);

    app(MetricsService::class)->trackInvoiceGeneration(1, 100, 5001.0);
});

// ── trackPaymentProcessed: level switches on success flag ──────────

it('uses info level for successful payments', function () {
    Log::shouldReceive('channel')
        ->with('business')
        ->once()
        ->andReturnSelf();
    Log::shouldReceive('info')
        ->once()
        ->withArgs(fn (string $msg, array $ctx) => $ctx['context']['success'] === true
            && $ctx['context']['gateway'] === 'helcim');

    app(MetricsService::class)->trackPaymentProcessed(42, 9900, 'helcim', true);
});

it('uses error level for failed payments', function () {
    Log::shouldReceive('channel')
        ->with('business')
        ->once()
        ->andReturnSelf();
    Log::shouldReceive('error')
        ->once()
        ->withArgs(fn (string $msg, array $ctx) => $ctx['context']['success'] === false
            && $ctx['context']['amount_cents'] === 9900);

    app(MetricsService::class)->trackPaymentProcessed(42, 9900, 'helcim', false);
});

// ── trackApiCall: tri-level log routing ─────────────────────────────

it('routes 2xx status codes to info level', function () {
    Log::shouldReceive('channel')->with('performance')->once()->andReturnSelf();
    Log::shouldReceive('info')->once();

    app(MetricsService::class)->trackApiCall('action1', '/org', 100.0, 201);
});

it('routes 4xx status codes to warning level', function () {
    Log::shouldReceive('channel')->with('performance')->once()->andReturnSelf();
    Log::shouldReceive('warning')->once();

    app(MetricsService::class)->trackApiCall('action1', '/org', 100.0, 422);
});

it('routes 5xx status codes to error level', function () {
    Log::shouldReceive('channel')->with('performance')->once()->andReturnSelf();
    Log::shouldReceive('error')->once();

    app(MetricsService::class)->trackApiCall('action1', '/org', 100.0, 502);
});

it('promotes slow 2xx to warning level', function () {
    Log::shouldReceive('channel')->with('performance')->once()->andReturnSelf();
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn (string $msg, array $ctx) => $ctx['duration_ms'] === 3001.0);

    app(MetricsService::class)->trackApiCall('google', '/users', 3001.0, 200);
});

// ── trackQueueJob: slow-job guard + error reporting ────────────────

it('warns on queue jobs exceeding 30s threshold', function () {
    Log::shouldReceive('channel')->andReturnSelf();
    Log::shouldReceive('info')->once();
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn (string $msg, array $ctx) => str_contains($msg, 'Slow queue job')
            && $ctx['duration_ms'] === 30001.0);

    app(MetricsService::class)->trackQueueJob('App\\Jobs\\LargeImport', 30001.0, true);
});

it('does not warn for queue jobs under 30s', function () {
    Log::shouldReceive('channel')->with('queue')->once()->andReturnSelf();
    Log::shouldReceive('info')->once();

    app(MetricsService::class)->trackQueueJob('App\\Jobs\\Quick', 29999.0, true);
});

it('includes error message in failed job log context', function () {
    Log::shouldReceive('channel')->with('queue')->once()->andReturnSelf();
    Log::shouldReceive('error')
        ->once()
        ->withArgs(fn (string $msg, array $ctx) => $ctx['error'] === 'Connection reset by peer');

    app(MetricsService::class)->trackQueueJob('App\\Jobs\\Sync', 500.0, false, 'Connection reset by peer');
});

// ── trackSlowQuery: threshold + truncation ─────────────────────────

it('logs queries above 1000ms threshold', function () {
    Log::shouldReceive('channel')->with('performance')->once()->andReturnSelf();
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn (string $msg, array $ctx) => $ctx['duration_ms'] === 1001.0);

    app(MetricsService::class)->trackSlowQuery('SELECT 1', 1001.0);
});

it('ignores queries at or below 1000ms threshold', function () {
    Log::spy();
    app(MetricsService::class)->trackSlowQuery('SELECT 1', 1000.0);
    Log::shouldNotHaveReceived('channel');
});

it('truncates query strings to 200 characters in log context', function () {
    $longQuery = str_repeat('A', 500);

    Log::shouldReceive('channel')->with('performance')->once()->andReturnSelf();
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn (string $msg, array $ctx) => strlen($ctx['query']) === 200);

    app(MetricsService::class)->trackSlowQuery($longQuery, 2000.0);
});

// ── trackWebhookProcessed: slow threshold ──────────────────────────

it('uses info level for webhooks processed under 1s', function () {
    Log::shouldReceive('channel')->andReturnSelf();
    Log::shouldReceive('info')->atLeast()->once();

    app(MetricsService::class)->trackWebhookProcessed('google', 'directory', 0.999);
});

it('uses warning level for webhooks processed over 1s', function () {
    Log::shouldReceive('channel')->andReturnSelf();
    Log::shouldReceive('warning')->atLeast()->once();
    Log::shouldReceive('info')->byDefault();

    app(MetricsService::class)->trackWebhookProcessed('google', 'directory', 1.001);
});
