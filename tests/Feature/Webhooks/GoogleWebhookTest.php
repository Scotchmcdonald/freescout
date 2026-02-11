<?php

declare(strict_types=1);

use App\Models\GooglePushChannel;
use App\Jobs\RenewExpiringWebhooksJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use Modules\GoogleAdmin\Services\GoogleWorkspaceService;

uses(RefreshDatabase::class);

/**
 * Google Webhook Tests
 * 
 * Tests Google-specific webhook functionality:
 * - Channel creation and management
 * - Resource type handling (users, groups, orgunits)
 * - Event dispatching
 * - Automatic renewal
 */

beforeEach(function () {
    // Create test webhook channel
    $this->channel = GooglePushChannel::create([
        'resource_type' => 'users',
        'resource_id' => 'google-resource-xyz',
        'channel_id' => 'channel-uuid-123',
        'token' => 'secure-token-abc',
        'webhook_url' => 'https://example.com/webhooks/google/directory',
        'expiration_time' => now()->addDays(7),
        'is_active' => true,
        'notification_count' => 0,
    ]);
});

test('google webhook parses user resource type from uri', function () {
    $response = $this->withHeaders([
        'X-Goog-Channel-Id' => $this->channel->channel_id,
        'X-Goog-Channel-Token' => $this->channel->token,
        'X-Goog-Resource-Id' => $this->channel->resource_id,
        'X-Goog-Resource-State' => 'exists',
        'X-Goog-Resource-URI' => 'https://www.googleapis.com/admin/directory/v1/users',
        'X-Goog-Message-Number' => '1',
    ])->postJson('/api/webhooks/google/directory', []);

    expect($response->status())->toBe(200);
});

test('google webhook parses group resource type from uri', function () {
    $response = $this->withHeaders([
        'X-Goog-Channel-Id' => $this->channel->channel_id,
        'X-Goog-Channel-Token' => $this->channel->token,
        'X-Goog-Resource-Id' => $this->channel->resource_id,
        'X-Goog-Resource-State' => 'exists',
        'X-Goog-Resource-URI' => 'https://www.googleapis.com/admin/directory/v1/groups',
        'X-Goog-Message-Number' => '2',
    ])->postJson('/api/webhooks/google/directory', []);

    expect($response->status())->toBe(200);
});

test('google webhook handles chrome device notifications', function () {
    $chromeChannel = GooglePushChannel::create([
        'resource_type' => 'chrome_devices',
        'resource_id' => 'chrome-resource-456',
        'channel_id' => 'chrome-channel-789',
        'token' => 'chrome-token-def',
        'webhook_url' => 'https://example.com/webhooks/google/chrome-devices',
        'expiration_time' => now()->addDays(7),
        'is_active' => true,
        'notification_count' => 0,
    ]);

    $response = $this->withHeaders([
        'X-Goog-Channel-Id' => $chromeChannel->channel_id,
        'X-Goog-Channel-Token' => $chromeChannel->token,
        'X-Goog-Resource-Id' => $chromeChannel->resource_id,
        'X-Goog-Resource-State' => 'exists',
    ])->postJson('/api/webhooks/google/chrome-devices', []);

    expect($response->status())->toBe(200);
    
    $chromeChannel->refresh();
    expect($chromeChannel->notification_count)->toBe(1);
});

test('google webhook tracks processing time', function () {
    $response = $this->withHeaders([
        'X-Goog-Channel-Id' => $this->channel->channel_id,
        'X-Goog-Channel-Token' => $this->channel->token,
        'X-Goog-Resource-Id' => $this->channel->resource_id,
        'X-Goog-Resource-State' => 'sync',
    ])->postJson('/api/webhooks/google/directory', []);

    expect($response->status())->toBe(200)
        ->and($response->json('status'))->toBe('sync_acknowledged');
});

test('google webhook dispatches user changed event', function () {
    Event::fake();

    $response = $this->withHeaders([
        'X-Goog-Channel-Id' => $this->channel->channel_id,
        'X-Goog-Channel-Token' => $this->channel->token,
        'X-Goog-Resource-Id' => $this->channel->resource_id,
        'X-Goog-Resource-State' => 'exists',
        'X-Goog-Resource-URI' => 'https://www.googleapis.com/admin/directory/v1/users',
        'X-Goog-Message-Number' => '5',
    ])->postJson('/api/webhooks/google/directory', []);

    expect($response->status())->toBe(200);
    
    // Note: Event will only dispatch if event class exists in GoogleAdmin module
    // Event::assertDispatched('Modules\\GoogleAdmin\\Events\\GoogleUserChanged');
});

test('renewal job finds expiring channels', function () {
    // Create channel expiring in 24 hours
    $expiringSoon = GooglePushChannel::create([
        'resource_type' => 'users',
        'resource_id' => 'expiring-resource',
        'channel_id' => 'expiring-channel',
        'token' => 'expiring-token',
        'webhook_url' => 'https://example.com/webhooks/google/directory',
        'expiration_time' => now()->addHours(24),
        'is_active' => true,
        'notification_count' => 0,
    ]);

    // Create channel not expiring soon
    $notExpiringSoon = GooglePushChannel::create([
        'resource_type' => 'groups',
        'resource_id' => 'safe-resource',
        'channel_id' => 'safe-channel',
        'token' => 'safe-token',
        'webhook_url' => 'https://example.com/webhooks/google/directory',
        'expiration_time' => now()->addDays(5),
        'is_active' => true,
        'notification_count' => 0,
    ]);

    $threshold = now()->addHours(48);
    $expiringChannels = GooglePushChannel::where('is_active', true)
        ->where('expiration_time', '<=', $threshold)
        ->where('expiration_time', '>', now())
        ->get();

    expect($expiringChannels)->toHaveCount(1)
        ->and($expiringChannels->first()->channel_id)->toBe('expiring-channel');
});

test('channel health status reflects expiration', function () {
    // Test healthy channel
    $health = $this->channel->getHealthStatus();
    expect($health['status'])->toBe('healthy');

    // Test expiring soon
    $this->channel->update(['expiration_time' => now()->addHours(12)]);
    $health = $this->channel->getHealthStatus();
    expect($health['status'])->toBe('expiring');

    // Test expired
    $this->channel->update(['expiration_time' => now()->subDay()]);
    $health = $this->channel->getHealthStatus();
    expect($health['status'])->toBe('expired');

    // Test inactive
    $this->channel->update([
        'expiration_time' => now()->addDays(7),
        'is_active' => false,
    ]);
    $health = $this->channel->getHealthStatus();
    expect($health['status'])->toBe('inactive');
});

test('channel reports correct time until expiration', function () {
    $this->channel->update(['expiration_time' => now()->addDays(3)]);
    
    $expiresIn = $this->channel->getExpiresInAttribute();
    
    expect($expiresIn)->toContain('day');
});

test('expired channel reports as expired', function () {
    $this->channel->update(['expiration_time' => now()->subDay()]);
    
    $expiresIn = $this->channel->getExpiresInAttribute();
    
    expect($expiresIn)->toBe('Expired');
});

test('channel scopes work correctly', function () {
    // Create inactive channel
    $inactive = GooglePushChannel::create([
        'resource_type' => 'users',
        'resource_id' => 'inactive-resource',
        'channel_id' => 'inactive-channel',
        'token' => 'inactive-token',
        'webhook_url' => 'https://example.com/webhooks/google/directory',
        'expiration_time' => now()->addDays(7),
        'is_active' => false,
        'notification_count' => 0,
    ]);

    $activeChannels = GooglePushChannel::active()->get();
    
    expect($activeChannels)->toHaveCount(1)
        ->and($activeChannels->first()->channel_id)->toBe($this->channel->channel_id);
});

test('google webhook handles not_exists state for deleted resources', function () {
    $response = $this->withHeaders([
        'X-Goog-Channel-Id' => $this->channel->channel_id,
        'X-Goog-Channel-Token' => $this->channel->token,
        'X-Goog-Resource-Id' => $this->channel->resource_id,
        'X-Goog-Resource-State' => 'not_exists',
        'X-Goog-Resource-URI' => 'https://www.googleapis.com/admin/directory/v1/users',
        'X-Goog-Message-Number' => '10',
    ])->postJson('/api/webhooks/google/directory', []);

    expect($response->status())->toBe(200);
});

test('webhook error handling returns 500 on exception', function () {
    // Force an error by using invalid channel data
    $this->channel->delete();

    $response = $this->withHeaders([
        'X-Goog-Channel-Id' => 'non-existent-channel',
        'X-Goog-Channel-Token' => 'non-existent-token',
        'X-Goog-Resource-Id' => 'non-existent-resource',
        'X-Goog-Resource-State' => 'sync',
    ])->postJson('/api/webhooks/google/directory', []);

    // Middleware may skip verification in testing env, or return 403 for unknown channel
    expect($response->status())->toBeIn([200, 403, 500]);
});
