<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

const APPHEALTH_TOKEN = 'test-apphealth-token';

beforeEach(function (): void {
    config([
        'apphealth.enabled' => true,
        'apphealth.metrics_enabled' => true,
        'apphealth.security.internal_token' => APPHEALTH_TOKEN,
        'apphealth.scheduler.enabled' => false,
    ]);
});

test('internal health endpoint rejects requests without token', function (): void {
    $this->getJson('/internal/health')->assertForbidden();
});

test('internal health endpoint returns ok with token', function (): void {
    $response = $this->withHeader('X-AppHealth-Token', APPHEALTH_TOKEN)
        ->getJson('/internal/health');

    expect($response->status())->toBeIn([200, 503]);
    $response->assertJsonStructure([
        'status',
        'timestamp',
        'checks' => ['database'],
    ]);
});

test('detailed health endpoint returns check payload', function (): void {
    $response = $this->withHeader('X-AppHealth-Token', APPHEALTH_TOKEN)
        ->getJson('/internal/health/detailed');

    expect($response->status())->toBeIn([200, 503]);
    $response->assertJsonStructure([
        'status',
        'timestamp',
        'checks' => ['database', 'redis', 'queue', 'storage'],
    ]);
});

test('metrics endpoint is token protected', function (): void {
    $this->get('/internal/metrics')->assertForbidden();
});

test('metrics endpoint exposes prometheus payload for token callers', function (): void {
    $this->withHeader('X-AppHealth-Token', APPHEALTH_TOKEN)
        ->get('/internal/health');

    $response = $this->withHeader('X-AppHealth-Token', APPHEALTH_TOKEN)
        ->get('/internal/metrics');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/plain');
    expect($response->getContent())->toContain('apphealth_up');
    expect($response->getContent())->toContain('http_requests_total');
    expect($response->getContent())->toContain('http_request_duration_seconds');
});

test('scorecard endpoint requires authenticated operator even with internal token', function (): void {
    $this->withHeader('X-AppHealth-Token', APPHEALTH_TOKEN)
        ->getJson('/internal/scaling/scorecard')
        ->assertStatus(401);
});

test('scorecard endpoint allows admin with internal token', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->withHeader('X-AppHealth-Token', APPHEALTH_TOKEN)
        ->getJson('/internal/scaling/scorecard')
        ->assertOk()
        ->assertJsonStructure(['source', 'scorecard']);
});
