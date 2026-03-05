<?php

declare(strict_types=1);

use App\Models\GooglePushChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

/**
 * Webhook Security Tests
 * 
 * Tests security features:
 * - Signature verification
 * - Replay attack prevention
 * - IP whitelisting
 * - Rate limiting
 * - HTTPS enforcement
 */

beforeEach(function () {
    // Create a test webhook channel
    $this->channel = GooglePushChannel::create([
        'resource_type' => 'users',
        'resource_id' => 'test-resource-123',
        'channel_id' => 'test-channel-456',
        'token' => 'test-token-secure-789',
        'webhook_url' => 'https://example.com/webhooks/google/directory',
        'expiration_time' => now()->addDays(7),
        'is_active' => true,
        'notification_count' => 0,
    ]);
});

test('google webhook requires all required headers', function () {
    $response = $this->postJson('/api/webhooks/google/directory', []);

    expect($response->status())->toBe(403);
});

test('google webhook verifies channel token', function () {
    $response = $this->withHeaders([
        'X-Goog-Channel-Id' => $this->channel->channel_id,
        'X-Goog-Channel-Token' => $this->channel->token,
        'X-Goog-Resource-Id' => $this->channel->resource_id,
        'X-Goog-Resource-State' => 'sync',
    ])->postJson('/api/webhooks/google/directory', []);

    expect($response->status())->toBe(200);
});

test('google webhook rejects invalid token', function () {
    $response = $this->withHeaders([
        'X-Goog-Channel-Id' => $this->channel->channel_id,
        'X-Goog-Channel-Token' => 'invalid-token',
        'X-Goog-Resource-Id' => $this->channel->resource_id,
        'X-Goog-Resource-State' => 'sync',
    ])->postJson('/api/webhooks/google/directory', []);

    expect($response->status())->toBe(403);
});

test('google webhook rejects unknown channel', function () {
    $response = $this->withHeaders([
        'X-Goog-Channel-Id' => 'unknown-channel-id',
        'X-Goog-Channel-Token' => 'some-token',
        'X-Goog-Resource-Id' => 'some-resource',
        'X-Goog-Resource-State' => 'sync',
    ])->postJson('/api/webhooks/google/directory', []);

    expect($response->status())->toBe(403);
});

test('google webhook rejects expired channel', function () {
    // Set channel as expired
    $this->channel->update([
        'expiration_time' => now()->subDay(),
    ]);

    $response = $this->withHeaders([
        'X-Goog-Channel-Id' => $this->channel->channel_id,
        'X-Goog-Channel-Token' => $this->channel->token,
        'X-Goog-Resource-Id' => $this->channel->resource_id,
        'X-Goog-Resource-State' => 'sync',
    ])->postJson('/api/webhooks/google/directory', []);

    expect($response->status())->toBe(403);
});

test('google webhook rejects inactive channel', function () {
    $this->channel->update(['is_active' => false]);

    $response = $this->withHeaders([
        'X-Goog-Channel-Id' => $this->channel->channel_id,
        'X-Goog-Channel-Token' => $this->channel->token,
        'X-Goog-Resource-Id' => $this->channel->resource_id,
        'X-Goog-Resource-State' => 'sync',
    ])->postJson('/api/webhooks/google/directory', []);

    expect($response->status())->toBe(403);
});

test('google webhook handles sync message', function () {
    $response = $this->withHeaders([
        'X-Goog-Channel-Id' => $this->channel->channel_id,
        'X-Goog-Channel-Token' => $this->channel->token,
        'X-Goog-Resource-Id' => $this->channel->resource_id,
        'X-Goog-Resource-State' => 'sync',
    ])->postJson('/api/webhooks/google/directory', []);

    expect($response->status())->toBe(200)
        ->and($response->json('status'))->toBe('sync_acknowledged');
});

test('google webhook updates notification count', function () {
    $initialCount = $this->channel->notification_count;

    $this->withHeaders([
        'X-Goog-Channel-Id' => $this->channel->channel_id,
        'X-Goog-Channel-Token' => $this->channel->token,
        'X-Goog-Resource-Id' => $this->channel->resource_id,
        'X-Goog-Resource-State' => 'sync',
    ])->postJson('/api/webhooks/google/directory', []);

    $this->channel->refresh();
    
    expect($this->channel->notification_count)->toBe($initialCount + 1)
        ->and($this->channel->last_notification_at)->not->toBeNull();
});

test('google webhook processes user change notification', function () {
    $response = $this->withHeaders([
        'X-Goog-Channel-Id' => $this->channel->channel_id,
        'X-Goog-Channel-Token' => $this->channel->token,
        'X-Goog-Resource-Id' => $this->channel->resource_id,
        'X-Goog-Resource-State' => 'exists',
        'X-Goog-Resource-URI' => 'https://www.googleapis.com/admin/directory/v1/users',
        'X-Goog-Message-Number' => '123',
    ])->postJson('/api/webhooks/google/directory', []);

    expect($response->status())->toBe(200)
        ->and($response->json('status'))->toBe('processed');
});

test('webhook rate limiting prevents abuse', function () {
    // Make 61 requests (over the limit of 60/minute)
    for ($i = 0; $i < 61; $i++) {
        $response = $this->withHeaders([
            'X-Goog-Channel-Id' => $this->channel->channel_id,
            'X-Goog-Channel-Token' => $this->channel->token,
            'X-Goog-Resource-Id' => $this->channel->resource_id,
            'X-Goog-Resource-State' => 'sync',
        ])->postJson('/api/webhooks/google/directory', []);

        if ($i < 60) {
            expect($response->status())->toBeLessThan(429);
        } else {
            expect($response->status())->toBe(429);
        }
    }
});

test('action1 webhook requires signature header', function () {
    config(['action1.webhook_secret' => 'test-secret']);

    $response = $this->postJson('/api/webhooks/action1/devices', [
        'device_id' => 'test-device-123',
    ]);

    expect($response->status())->toBe(403);
});

test('action1 webhook verifies hmac signature', function () {
    $secret = 'test-secret';
    config(['action1.webhook_secret' => $secret]);

    $timestamp = time();
    $payload = json_encode(['device_id' => 'test-device-123']);
    $signature = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

    $response = $this->withHeaders([
        'X-Action1-Signature' => $signature,
        'X-Action1-Timestamp' => (string) $timestamp,
        'X-Action1-Event' => 'device.created',
    ])->postJson('/api/webhooks/action1/devices', [
        'device_id' => 'test-device-123',
    ]);

    expect($response->status())->toBe(200);
});

test('action1 webhook rejects invalid signature', function () {
    config(['action1.webhook_secret' => 'test-secret']);

    $timestamp = time();
    $invalidSignature = 'sha256=' . hash_hmac('sha256', 'wrong-data', 'test-secret');

    $response = $this->withHeaders([
        'X-Action1-Signature' => $invalidSignature,
        'X-Action1-Timestamp' => (string) $timestamp,
        'X-Action1-Event' => 'device.created',
    ])->postJson('/api/webhooks/action1/devices', [
        'device_id' => 'test-device-123',
    ]);

    expect($response->status())->toBe(403);
});

test('action1 webhook rejects old timestamps', function () {
    $secret = 'test-secret';
    config(['action1.webhook_secret' => $secret]);

    // Timestamp from 10 minutes ago (should be rejected)
    $timestamp = time() - 600;
    $payload = json_encode(['device_id' => 'test-device-123']);
    $signature = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

    $response = $this->withHeaders([
        'X-Action1-Signature' => $signature,
        'X-Action1-Timestamp' => (string) $timestamp,
        'X-Action1-Event' => 'device.created',
    ])->postJson('/api/webhooks/action1/devices', [
        'device_id' => 'test-device-123',
    ]);

    expect($response->status())->toBe(403);
});

test('webhook logs failed authentication attempts', function () {
    // Mock the security logger
    $securityLogger = Mockery::mock('Psr\Log\LoggerInterface');
    $securityLogger->shouldReceive('warning')
        ->once()
        ->with('Webhook signature verification failed', \Mockery::any());
    
    // Expect channel('security') to return our mock
    Log::shouldReceive('channel')
        ->with('security')
        ->andReturn($securityLogger);
    
    // Allow other logging calls on the main Log facade (e.g. operational warnings)
    Log::shouldReceive('warning')->andReturnNull();
    Log::shouldReceive('error')->andReturnNull();
    Log::shouldReceive('info')->andReturnNull();

    $this->withHeaders([
        'X-Goog-Channel-Id' => 'invalid',
        'X-Goog-Channel-Token' => 'invalid',
        'X-Goog-Resource-Id' => 'invalid',
        'X-Goog-Resource-State' => 'sync',
    ])->postJson('/api/webhooks/google/directory', []);
});
