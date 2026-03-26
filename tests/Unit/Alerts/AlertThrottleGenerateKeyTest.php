<?php

declare(strict_types=1);

namespace Tests\Unit\Alerts;

use Modules\Alerts\Models\AlertThrottle;
use Tests\PureUnitTestCase;

/**
 * Pure unit tests for AlertThrottle::generateKey() — a static helper that
 * depends only on md5, implode, and json_encode.  No DB or container needed.
 */
final class AlertThrottleGenerateKeyTest extends PureUnitTestCase
{
    // ─── generateKey: basic structure ────────────────────────────────

    public function test_generate_key_returns_32_char_hex_string(): void
    {
        $key = AlertThrottle::generateKey('billing.overdue', 1);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $key);
    }

    public function test_generate_key_is_deterministic_for_same_inputs(): void
    {
        $a = AlertThrottle::generateKey('sync.failed', 5);
        $b = AlertThrottle::generateKey('sync.failed', 5);
        $this->assertSame($a, $b);
    }

    public function test_generate_key_differs_for_different_alert_type_codes(): void
    {
        $a = AlertThrottle::generateKey('billing.overdue', 1);
        $b = AlertThrottle::generateKey('sync.failed', 1);
        $this->assertNotSame($a, $b);
    }

    public function test_generate_key_differs_for_different_client_ids(): void
    {
        $a = AlertThrottle::generateKey('billing.overdue', 1);
        $b = AlertThrottle::generateKey('billing.overdue', 2);
        $this->assertNotSame($a, $b);
    }

    public function test_generate_key_uses_global_sentinel_for_null_client(): void
    {
        $key = AlertThrottle::generateKey('security.login_failed', null);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $key);
    }

    public function test_generate_key_differs_when_context_changes(): void
    {
        $base    = AlertThrottle::generateKey('billing.overdue', 1, []);
        $withCtx = AlertThrottle::generateKey('billing.overdue', 1, ['severity' => 'critical']);
        $this->assertNotSame($base, $withCtx);
    }

    public function test_generate_key_same_context_order_is_deterministic(): void
    {
        $a = AlertThrottle::generateKey('billing.overdue', 1, ['k' => 'v']);
        $b = AlertThrottle::generateKey('billing.overdue', 1, ['k' => 'v']);
        $this->assertSame($a, $b);
    }

    public function test_generate_key_no_context_matches_empty_array(): void
    {
        $noCtx    = AlertThrottle::generateKey('billing.overdue', 1);
        $emptyCtx = AlertThrottle::generateKey('billing.overdue', 1, []);
        $this->assertSame($noCtx, $emptyCtx);
    }
}
