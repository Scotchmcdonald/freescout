<?php

use App\Models\GooglePushChannel;
use App\Models\User;

function getWebhookGatewayAdmin(): User
{
    $admin = User::firstOrCreate(['email' => 'webhook-gateway-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'WebhookGW',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (! $admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }

    return $admin;
}

it('webhook gateway page loads', function () {
    $admin = getWebhookGatewayAdmin();

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/admin/webhooks')
        ->assertSee('Webhook Gateway');
})->group('admin', 'webhook-gateway');

it('webhook gateway shows empty state', function () {
    $admin = getWebhookGatewayAdmin();

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/admin/webhooks')
        ->assertSee('No webhook channels configured');
})->group('admin', 'webhook-gateway');

it('google push channel model exists', function () {
    expect(class_exists(GooglePushChannel::class))->toBeTrue();

    $channel = new GooglePushChannel;
    expect($channel->getTable())->toBe('google_push_channels');

    // Verify key methods exist
    expect(method_exists($channel, 'isExpired'))->toBeTrue();
    expect(method_exists($channel, 'isExpiringSoon'))->toBeTrue();
    expect(method_exists($channel, 'getHealthStatus'))->toBeTrue();
})->group('admin', 'webhook-gateway');

it('google push channel tracks expiration status', function () {
    $channel = GooglePushChannel::create([
        'channel_id' => 'test-channel-'.uniqid(),
        'resource_id' => 'resource-'.uniqid(),
        'resource_type' => 'users',
        'webhook_url' => 'https://test.example.com/webhook',
        'expiration_time' => now()->subHour(),
        'is_active' => true,
    ]);

    expect($channel->isExpired())->toBeTrue();

    // Update to future expiration
    $channel->update(['expiration_time' => now()->addDays(7)]);
    $channel->refresh();

    expect($channel->isExpired())->toBeFalse();

    // Health status should exist and have required keys
    $health = $channel->getHealthStatus();
    expect($health)->toHaveKeys(['status', 'color', 'message']);
})->group('admin', 'webhook-gateway');
