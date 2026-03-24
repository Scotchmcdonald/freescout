<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\GooglePushChannel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\PureUnitTestCase;

class GooglePushChannelTest extends PureUnitTestCase
{
    private function newChannel(bool $active, Carbon $expirationTime, ?Carbon $lastNotificationAt = null): GooglePushChannel
    {
        $channel = new class extends GooglePushChannel
        {
            protected function casts(): array
            {
                return [];
            }
        };

        $channel->is_active = $active;
        $channel->expiration_time = $expirationTime;
        $channel->last_notification_at = $lastNotificationAt;

        return $channel;
    }

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-03-24 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_get_health_status_returns_inactive_when_channel_is_disabled(): void
    {
        $channel = $this->newChannel(false, now()->addDays(2));

        $status = $channel->getHealthStatus();

        $this->assertSame('inactive', $status['status']);
        $this->assertSame('gray', $status['color']);
    }

    public function test_get_health_status_returns_expired_when_active_but_past_expiration(): void
    {
        $channel = $this->newChannel(true, now()->subHour());

        $status = $channel->getHealthStatus();

        $this->assertSame('expired', $status['status']);
        $this->assertSame('danger', $status['color']);
        $this->assertStringContainsString('ago', $status['message']);
    }

    public function test_get_health_status_returns_expiring_when_within_24_hours(): void
    {
        $channel = $this->newChannel(true, now()->addHours(8));

        $status = $channel->getHealthStatus();

        $this->assertSame('expiring', $status['status']);
        $this->assertSame('warning', $status['color']);
    }

    public function test_get_health_status_returns_stale_when_no_notification_for_24_hours(): void
    {
        $channel = $this->newChannel(true, now()->addDays(3), now()->subHours(30));

        $status = $channel->getHealthStatus();

        $this->assertSame('stale', $status['status']);
        $this->assertSame('warning', $status['color']);
    }

    public function test_get_health_status_returns_healthy_for_recent_activity_and_safe_expiry_window(): void
    {
        $channel = $this->newChannel(true, now()->addDays(3), now()->subMinutes(10));

        $status = $channel->getHealthStatus();

        $this->assertSame('healthy', $status['status']);
        $this->assertSame('success', $status['color']);
    }

    public function test_expires_in_attribute_returns_expired_for_past_channels_and_human_value_for_future_channels(): void
    {
        $expired = $this->newChannel(true, now()->subMinute());
        $future = $this->newChannel(true, now()->addHours(2));

        $this->assertSame('Expired', $expired->expires_in);
        $this->assertStringContainsString('2 hour', $future->expires_in);
    }

    public function test_is_expired_and_is_expiring_soon_helpers_cover_boundary_cases(): void
    {
        $expired = $this->newChannel(true, now()->subSecond());
        $withinWindow = $this->newChannel(true, now()->addHours(24));
        $outsideWindow = $this->newChannel(true, now()->addHours(25));

        $this->assertTrue($expired->isExpired());
        $this->assertFalse($withinWindow->isExpired());

        $this->assertTrue($withinWindow->isExpiringSoon());
        $this->assertFalse($outsideWindow->isExpiringSoon());
    }

    public function test_scope_active_adds_is_active_filter(): void
    {
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->once()->with('is_active', true)->andReturnSelf();

        (new GooglePushChannel)->scopeActive($builder);

        $this->assertTrue(true);
    }

    public function test_scope_expired_filters_expiration_time_before_now(): void
    {
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->once()->with(
            'expiration_time',
            '<',
            Mockery::on(fn ($value): bool => $value instanceof Carbon)
        )->andReturnSelf();

        (new GooglePushChannel)->scopeExpired($builder);

        $this->assertTrue(true);
    }

    public function test_scope_expiring_soon_adds_windowed_future_filter(): void
    {
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->once()->with(
            'expiration_time',
            '>',
            Mockery::on(fn ($value): bool => $value instanceof Carbon)
        )->andReturnSelf();
        $builder->shouldReceive('where')->once()->with(
            'expiration_time',
            '<=',
            Mockery::on(fn ($value): bool => $value instanceof Carbon)
        )->andReturnSelf();

        (new GooglePushChannel)->scopeExpiringSoon($builder);

        $this->assertTrue(true);
    }
}
