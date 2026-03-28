<?php

declare(strict_types=1);

namespace Tests\Unit\Alerts;

use Modules\Alerts\Models\AlertSubscription;
use Tests\PureUnitTestCase;

// ── Stub ──────────────────────────────────────────────────────────────────────

if (! class_exists(StubAlertSubscription::class)) {
    final class StubAlertSubscription extends AlertSubscription
    {
        protected static function booted(): void {}
    }
}

// ── Test class ────────────────────────────────────────────────────────────────

final class AlertSubscriptionTest extends PureUnitTestCase
{
    private function sub(array $rawAttrs): StubAlertSubscription
    {
        $s = new StubAlertSubscription;
        $s->setRawAttributes($rawAttrs);

        return $s;
    }

    // ── constants are distinct ────────────────────────────────────────

    public function test_channel_constants_are_distinct(): void
    {
        $channels = [
            AlertSubscription::CHANNEL_EMAIL,
            AlertSubscription::CHANNEL_SLACK,
            AlertSubscription::CHANNEL_SMS,
            AlertSubscription::CHANNEL_DATABASE,
        ];
        $this->assertSame(count($channels), count(array_unique($channels)));
    }

    public function test_frequency_constants_are_distinct(): void
    {
        $freqs = [
            AlertSubscription::FREQUENCY_IMMEDIATE,
            AlertSubscription::FREQUENCY_HOURLY,
            AlertSubscription::FREQUENCY_DAILY,
            AlertSubscription::FREQUENCY_WEEKLY,
        ];
        $this->assertSame(count($freqs), count(array_unique($freqs)));
    }

    // ── appliesToClient: null / empty client_ids → global ─────────────

    public function test_applies_to_client_when_client_ids_is_null(): void
    {
        $s = $this->sub(['client_ids' => null]);
        $this->assertTrue($s->appliesToClient(42));
    }

    public function test_applies_to_client_when_client_ids_is_empty_json(): void
    {
        $s = $this->sub(['client_ids' => '[]']);
        $this->assertTrue($s->appliesToClient(42));
    }

    // ── appliesToClient: restricted list ─────────────────────────────

    public function test_applies_to_client_when_id_is_in_list(): void
    {
        $s = new StubAlertSubscription;
        $s->forceFill(['client_ids' => [10, 20, 30]]);
        $this->assertTrue($s->appliesToClient(20));
    }

    public function test_applies_to_client_when_id_not_in_list(): void
    {
        $s = new StubAlertSubscription;
        $s->forceFill(['client_ids' => [10, 20]]);
        $this->assertFalse($s->appliesToClient(99));
    }

    public function test_applies_to_client_with_null_id_and_nonempty_list(): void
    {
        $s = new StubAlertSubscription;
        $s->forceFill(['client_ids' => [10, 20]]);
        $this->assertFalse($s->appliesToClient(null));
    }

    // ── hasChannel ────────────────────────────────────────────────────

    public function test_has_channel_returns_true_for_present_channel(): void
    {
        $s = new StubAlertSubscription;
        $s->forceFill(['channels' => ['email', 'slack']]);
        $this->assertTrue($s->hasChannel('email'));
    }

    public function test_has_channel_returns_false_for_absent_channel(): void
    {
        $s = new StubAlertSubscription;
        $s->forceFill(['channels' => ['email']]);
        $this->assertFalse($s->hasChannel('sms'));
    }

    public function test_has_channel_returns_false_when_channels_null(): void
    {
        $this->assertFalse($this->sub(['channels' => null])->hasChannel('email'));
    }

    // ── isDigest ──────────────────────────────────────────────────────

    public function test_is_digest_true_for_hourly(): void
    {
        $this->assertTrue($this->sub(['frequency' => AlertSubscription::FREQUENCY_HOURLY])->isDigest());
    }

    public function test_is_digest_true_for_daily(): void
    {
        $this->assertTrue($this->sub(['frequency' => AlertSubscription::FREQUENCY_DAILY])->isDigest());
    }

    public function test_is_digest_true_for_weekly(): void
    {
        $this->assertTrue($this->sub(['frequency' => AlertSubscription::FREQUENCY_WEEKLY])->isDigest());
    }

    public function test_is_digest_false_for_immediate(): void
    {
        $this->assertFalse($this->sub(['frequency' => AlertSubscription::FREQUENCY_IMMEDIATE])->isDigest());
    }
}
