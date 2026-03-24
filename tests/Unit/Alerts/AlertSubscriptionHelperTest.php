<?php

declare(strict_types=1);

namespace Tests\Unit\Alerts;

use Modules\Alerts\Models\AlertSubscription;
use Modules\Alerts\Models\AlertType;
use Modules\Alerts\Models\NotificationSubscription;
use Tests\PureUnitTestCase;

final class TestAlertSubscription extends AlertSubscription
{
    protected static function booted(): void {}

    protected function casts(): array
    {
        return [];
    }
}

final class TestAlertType extends AlertType
{
    protected static function booted(): void {}

    protected function casts(): array
    {
        return [];
    }
}

final class TestNotificationSubscription extends NotificationSubscription
{
    protected static function booted(): void {}

    protected function casts(): array
    {
        return [];
    }
}

class AlertSubscriptionHelperTest extends PureUnitTestCase
{
    private function subscription(array $attrs = []): TestAlertSubscription
    {
        $model = new TestAlertSubscription;
        foreach ($attrs as $key => $value) {
            $model->{$key} = $value;
        }

        return $model;
    }

    private function alertType(string $severity): TestAlertType
    {
        $type = new TestAlertType;
        $type->severity = $severity;

        return $type;
    }

    public function test_applies_to_client_returns_true_when_client_filter_is_empty(): void
    {
        $subscription = $this->subscription(['client_ids' => []]);

        $this->assertTrue($subscription->appliesToClient(123));
        $this->assertTrue($subscription->appliesToClient(null));
    }

    public function test_applies_to_client_returns_true_when_client_id_is_in_filter(): void
    {
        $subscription = $this->subscription(['client_ids' => [10, 20, 30]]);

        $this->assertTrue($subscription->appliesToClient(20));
    }

    public function test_applies_to_client_returns_false_when_client_id_is_not_in_filter(): void
    {
        $subscription = $this->subscription(['client_ids' => [10, 20, 30]]);

        $this->assertFalse($subscription->appliesToClient(999));
    }

    public function test_applies_to_client_returns_false_when_client_id_is_null_and_filter_is_set(): void
    {
        $subscription = $this->subscription(['client_ids' => [10]]);

        $this->assertFalse($subscription->appliesToClient(null));
    }

    public function test_has_channel_returns_true_when_channel_is_enabled(): void
    {
        $subscription = $this->subscription(['channels' => ['email', 'slack']]);

        $this->assertTrue($subscription->hasChannel('slack'));
    }

    public function test_has_channel_returns_false_when_channel_is_not_enabled(): void
    {
        $subscription = $this->subscription(['channels' => ['email', 'slack']]);

        $this->assertFalse($subscription->hasChannel('sms'));
    }

    public function test_has_channel_returns_false_when_channels_are_null(): void
    {
        $subscription = $this->subscription(['channels' => null]);

        $this->assertFalse($subscription->hasChannel('email'));
    }

    public function test_is_digest_returns_true_for_hourly_frequency(): void
    {
        $subscription = $this->subscription(['frequency' => AlertSubscription::FREQUENCY_HOURLY]);

        $this->assertTrue($subscription->isDigest());
    }

    public function test_is_digest_returns_true_for_daily_frequency(): void
    {
        $subscription = $this->subscription(['frequency' => AlertSubscription::FREQUENCY_DAILY]);

        $this->assertTrue($subscription->isDigest());
    }

    public function test_is_digest_returns_true_for_weekly_frequency(): void
    {
        $subscription = $this->subscription(['frequency' => AlertSubscription::FREQUENCY_WEEKLY]);

        $this->assertTrue($subscription->isDigest());
    }

    public function test_is_digest_returns_false_for_immediate_frequency(): void
    {
        $subscription = $this->subscription(['frequency' => AlertSubscription::FREQUENCY_IMMEDIATE]);

        $this->assertFalse($subscription->isDigest());
    }

    public function test_get_email_recipient_returns_user_email_when_relation_is_loaded(): void
    {
        $subscription = $this->subscription();
        $subscription->setRelation('user', (object) ['email' => 'alerts@example.com']);

        $this->assertSame('alerts@example.com', $subscription->getEmailRecipient());
    }

    public function test_get_email_recipient_returns_null_when_user_relation_is_missing(): void
    {
        $subscription = $this->subscription();
        $subscription->setRelation('user', null);

        $this->assertNull($subscription->getEmailRecipient());
    }

    public function test_get_sms_recipient_returns_user_phone_when_relation_is_loaded(): void
    {
        $subscription = $this->subscription();
        $subscription->setRelation('user', (object) ['phone' => '+15550000']);

        $this->assertSame('+15550000', $subscription->getSmsRecipient());
    }

    public function test_get_sms_recipient_returns_null_when_user_relation_is_missing(): void
    {
        $subscription = $this->subscription();
        $subscription->setRelation('user', null);

        $this->assertNull($subscription->getSmsRecipient());
    }

    public function test_alert_type_severity_color_mappings(): void
    {
        $this->assertSame('blue', $this->alertType(AlertType::SEVERITY_INFO)->getSeverityColorAttribute());
        $this->assertSame('yellow', $this->alertType(AlertType::SEVERITY_WARNING)->getSeverityColorAttribute());
        $this->assertSame('red', $this->alertType(AlertType::SEVERITY_ERROR)->getSeverityColorAttribute());
        $this->assertSame('red', $this->alertType(AlertType::SEVERITY_CRITICAL)->getSeverityColorAttribute());
    }

    public function test_alert_type_severity_color_defaults_to_gray_for_unknown_values(): void
    {
        $this->assertSame('gray', $this->alertType('mystery')->getSeverityColorAttribute());
    }

    public function test_alert_type_severity_icon_mappings(): void
    {
        $this->assertSame('heroicon-o-information-circle', $this->alertType(AlertType::SEVERITY_INFO)->getSeverityIconAttribute());
        $this->assertSame('heroicon-o-exclamation-triangle', $this->alertType(AlertType::SEVERITY_WARNING)->getSeverityIconAttribute());
        $this->assertSame('heroicon-o-x-circle', $this->alertType(AlertType::SEVERITY_ERROR)->getSeverityIconAttribute());
        $this->assertSame('heroicon-o-fire', $this->alertType(AlertType::SEVERITY_CRITICAL)->getSeverityIconAttribute());
    }

    public function test_alert_type_severity_icon_defaults_to_bell_for_unknown_values(): void
    {
        $this->assertSame('heroicon-o-bell', $this->alertType('mystery')->getSeverityIconAttribute());
    }

    public function test_notification_subscription_get_alert_types_contains_expected_keys(): void
    {
        $types = NotificationSubscription::getAlertTypes();

        $this->assertArrayHasKey('unusual_variance', $types);
        $this->assertArrayHasKey('circuit_breaker', $types);
        $this->assertArrayHasKey('asset_conflict', $types);
        $this->assertArrayHasKey('module_update', $types);
        $this->assertArrayHasKey('high_error_rate', $types);
    }

    public function test_notification_subscription_has_channel_returns_true_for_present_channel(): void
    {
        $subscription = new TestNotificationSubscription;
        $subscription->channels = ['email', 'slack'];

        $this->assertTrue($subscription->hasChannel('email'));
    }

    public function test_notification_subscription_has_channel_returns_false_for_absent_channel_or_null_channels(): void
    {
        $subscription = new TestNotificationSubscription;
        $subscription->channels = ['email'];

        $this->assertFalse($subscription->hasChannel('sms'));

        $subscription->channels = null;
        $this->assertFalse($subscription->hasChannel('email'));
    }
}
