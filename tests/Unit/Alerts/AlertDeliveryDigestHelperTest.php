<?php

declare(strict_types=1);

namespace Tests\Unit\Alerts;

use Illuminate\Support\Carbon;
use Modules\Alerts\Models\AlertDeliveryLog;
use Modules\Alerts\Models\AlertDigestQueue;
use Modules\Alerts\Models\AlertSubscription;
use Tests\PureUnitTestCase;

final class TestAlertDeliveryLog extends AlertDeliveryLog
{
    /** @var array<string, mixed> */
    public array $lastUpdated = [];

    public function update(array $attributes = [], array $options = []): bool
    {
        $this->lastUpdated = $attributes;
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }

        return true;
    }

    protected static function booted(): void {}

    protected function casts(): array
    {
        return [];
    }
}

final class TestAlertDigestQueue extends AlertDigestQueue
{
    /** @var array<string, mixed> */
    public array $lastUpdated = [];

    /** @var array<string, mixed> */
    public static array $lastCreated = [];

    public function update(array $attributes = [], array $options = []): bool
    {
        $this->lastUpdated = $attributes;
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }

        return true;
    }

    public static function create(array $attributes = []): static
    {
        self::$lastCreated = $attributes;

        $instance = new self;
        foreach ($attributes as $key => $value) {
            $instance->attributes[$key] = $value;
        }

        return $instance;
    }

    public static function exposeCalculateDigestTime(AlertSubscription $subscription): Carbon
    {
        return self::calculateDigestTime($subscription);
    }

    protected static function booted(): void {}

    protected function casts(): array
    {
        return [];
    }
}

final class TestDigestSubscription extends AlertSubscription
{
    protected static function booted(): void {}

    protected function casts(): array
    {
        return [];
    }
}

class AlertDeliveryDigestHelperTest extends PureUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-03-24 10:15:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_mark_sent_sets_sent_status_and_timestamp(): void
    {
        $log = new TestAlertDeliveryLog;
        $log->markSent();

        $this->assertSame(AlertDeliveryLog::STATUS_SENT, $log->status);
        $this->assertArrayHasKey('sent_at', $log->lastUpdated);
    }

    public function test_mark_delivered_sets_delivered_status_and_timestamp(): void
    {
        $log = new TestAlertDeliveryLog;
        $log->markDelivered();

        $this->assertSame(AlertDeliveryLog::STATUS_DELIVERED, $log->status);
        $this->assertArrayHasKey('delivered_at', $log->lastUpdated);
    }

    public function test_mark_failed_sets_failed_status_error_and_increments_retry_count(): void
    {
        $log = new TestAlertDeliveryLog;
        $log->retry_count = 2;

        $log->markFailed('SMTP timeout');

        $this->assertSame(AlertDeliveryLog::STATUS_FAILED, $log->status);
        $this->assertSame('SMTP timeout', $log->error_message);
        $this->assertSame(3, $log->retry_count);
        $this->assertArrayHasKey('failed_at', $log->lastUpdated);
    }

    public function test_mark_throttled_sets_throttled_status(): void
    {
        $log = new TestAlertDeliveryLog;

        $log->markThrottled();

        $this->assertSame(AlertDeliveryLog::STATUS_THROTTLED, $log->status);
    }

    public function test_mark_processed_sets_is_processed_to_true(): void
    {
        $queue = new TestAlertDigestQueue;
        $queue->is_processed = false;

        $queue->markProcessed();

        $this->assertTrue($queue->is_processed);
    }

    private function subscription(string $frequency, ?string $digestTime = null, ?string $timezone = null): TestDigestSubscription
    {
        $subscription = new TestDigestSubscription;
        $subscription->id = 55;
        $subscription->frequency = $frequency;
        $subscription->digest_time = $digestTime;
        $subscription->digest_timezone = $timezone;

        return $subscription;
    }

    public function test_calculate_digest_time_hourly_rounds_to_next_hour(): void
    {
        $subscription = $this->subscription(AlertSubscription::FREQUENCY_HOURLY, null, 'UTC');

        $result = TestAlertDigestQueue::exposeCalculateDigestTime($subscription);

        $this->assertSame('2026-03-24 11:00:00', $result->format('Y-m-d H:i:s'));
    }

    public function test_calculate_digest_time_daily_uses_default_time_when_digest_time_missing(): void
    {
        $subscription = $this->subscription(AlertSubscription::FREQUENCY_DAILY, null, 'UTC');

        $result = TestAlertDigestQueue::exposeCalculateDigestTime($subscription);

        $this->assertSame('2026-03-25 08:00:00', $result->format('Y-m-d H:i:s'));
    }

    public function test_calculate_digest_time_daily_uses_configured_time_today_when_future(): void
    {
        $subscription = $this->subscription(AlertSubscription::FREQUENCY_DAILY, '12:30:00', 'UTC');

        $result = TestAlertDigestQueue::exposeCalculateDigestTime($subscription);

        $this->assertSame('2026-03-24 12:30:00', $result->format('Y-m-d H:i:s'));
    }

    public function test_calculate_digest_time_daily_rolls_to_next_day_when_time_has_passed(): void
    {
        $subscription = $this->subscription(AlertSubscription::FREQUENCY_DAILY, '09:00:00', 'UTC');

        $result = TestAlertDigestQueue::exposeCalculateDigestTime($subscription);

        $this->assertSame('2026-03-25 09:00:00', $result->format('Y-m-d H:i:s'));
    }

    public function test_calculate_digest_time_weekly_targets_next_monday_with_configured_time(): void
    {
        // 2026-03-24 is Tuesday, so next Monday is 2026-03-30
        $subscription = $this->subscription(AlertSubscription::FREQUENCY_WEEKLY, '07:45:00', 'UTC');

        $result = TestAlertDigestQueue::exposeCalculateDigestTime($subscription);

        $this->assertSame('2026-03-30 07:45:00', $result->format('Y-m-d H:i:s'));
    }

    public function test_calculate_digest_time_defaults_to_now_for_unknown_frequency(): void
    {
        $subscription = $this->subscription('custom', null, 'UTC');

        $result = TestAlertDigestQueue::exposeCalculateDigestTime($subscription);

        $this->assertSame('2026-03-24 10:15:00', $result->format('Y-m-d H:i:s'));
    }

    public function test_queue_for_digest_creates_payload_with_expected_fields(): void
    {
        $subscription = $this->subscription(AlertSubscription::FREQUENCY_HOURLY, null, 'UTC');

        $created = TestAlertDigestQueue::queueForDigest(
            $subscription,
            'billing.overdue',
            987,
            ['invoice_id' => 15]
        );

        $this->assertSame(55, TestAlertDigestQueue::$lastCreated['alert_subscription_id']);
        $this->assertSame('billing.overdue', TestAlertDigestQueue::$lastCreated['alert_type_code']);
        $this->assertSame(987, TestAlertDigestQueue::$lastCreated['client_id']);
        $this->assertSame(['invoice_id' => 15], TestAlertDigestQueue::$lastCreated['payload']);
        $this->assertFalse(TestAlertDigestQueue::$lastCreated['is_processed']);
        $this->assertInstanceOf(TestAlertDigestQueue::class, $created);
    }
}
