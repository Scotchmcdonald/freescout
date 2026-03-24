<?php

declare(strict_types=1);

namespace Tests\Unit\Alerts;

use Modules\Alerts\Models\AlertThrottle;
use Modules\Alerts\Models\AlertType;
use Tests\PureUnitTestCase;

/**
 * Pure unit tests for AlertThrottle::generateKey() — a static helper that
 * depends only on md5, implode, and json_encode.  No DB or container needed.
 */
class AlertThrottleGenerateKeyTest extends PureUnitTestCase
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
        // null client_id should produce a stable, context-independent key
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

// ─────────────────────────────────────────────────────────────────────────────
// AlertType: constant / category correctness (pure, no DB)
// ─────────────────────────────────────────────────────────────────────────────

class AlertTypeConstantsTest extends PureUnitTestCase
{
    public function test_severity_constants_are_distinct(): void
    {
        $severities = [
            AlertType::SEVERITY_INFO,
            AlertType::SEVERITY_WARNING,
            AlertType::SEVERITY_ERROR,
            AlertType::SEVERITY_CRITICAL,
        ];

        $this->assertSame(count($severities), count(array_unique($severities)));
    }

    public function test_category_constants_match_categories_array(): void
    {
        $constants = [
            AlertType::CATEGORY_BILLING,
            AlertType::CATEGORY_SYNC,
            AlertType::CATEGORY_SECURITY,
            AlertType::CATEGORY_SYSTEM,
            AlertType::CATEGORY_MIGRATION,
            AlertType::CATEGORY_CONTRACT,
            AlertType::CATEGORY_SOFTWARE,
            AlertType::CATEGORY_ASSET,
            AlertType::CATEGORY_AI,
        ];

        foreach ($constants as $constant) {
            $this->assertContains($constant, AlertType::CATEGORIES, "Constant '{$constant}' missing from CATEGORIES");
        }
    }

    public function test_categories_array_has_no_duplicate_entries(): void
    {
        $this->assertSame(
            count(AlertType::CATEGORIES),
            count(array_unique(AlertType::CATEGORIES))
        );
    }

    public function test_all_category_constants_cover_categories_array(): void
    {
        // Every entry in CATEGORIES must have a corresponding constant
        $this->assertCount(9, AlertType::CATEGORIES);
    }
}
