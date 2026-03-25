<?php

declare(strict_types=1);

use App\Models\GooglePushChannel;

beforeEach(function () {
    $this->channel = GooglePushChannel::create([
        'resource_type' => 'chrome_devices',
        'resource_id' => 'chrome-resource-rl-1',
        'channel_id' => 'chrome-channel-rl-1',
        'token' => 'chrome-token-rl-1',
        'webhook_url' => 'https://example.com/webhooks/google/chrome-devices',
        'expiration_time' => now()->addDays(7),
        'is_active' => true,
        'notification_count' => 0,
    ]);
});

test('google chrome devices webhook endpoint is rate limited to 60 requests per minute per ip', function () {
    $ip = '10.250.1.20';

    for ($i = 0; $i < 60; $i++) {
        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => $ip])
            ->withHeaders([
                'X-Goog-Channel-Id' => $this->channel->channel_id,
                'X-Goog-Channel-Token' => $this->channel->token,
                'X-Goog-Resource-Id' => $this->channel->resource_id,
                'X-Goog-Resource-State' => 'sync',
            ])
            ->postJson('/api/webhooks/google/chrome-devices', []);

        expect($response->status())->toBe(200);
    }

    $this->channel->refresh();

    expect($this->channel->notification_count)->toBe(60);
    expect($this->channel->last_notification_at)->not->toBeNull();

    $throttled = $this
        ->withServerVariables(['REMOTE_ADDR' => $ip])
        ->withHeaders([
            'X-Goog-Channel-Id' => $this->channel->channel_id,
            'X-Goog-Channel-Token' => $this->channel->token,
            'X-Goog-Resource-Id' => $this->channel->resource_id,
            'X-Goog-Resource-State' => 'sync',
        ])
        ->postJson('/api/webhooks/google/chrome-devices', []);

    $throttled->assertStatus(429);
    $throttled->assertHeader('Retry-After');
    $throttled->assertHeader('X-RateLimit-Limit');

    $this->channel->refresh();

    expect($this->channel->notification_count)->toBe(60);
})->group('boundary');
