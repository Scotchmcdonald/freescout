<?php

declare(strict_types=1);

namespace Tests\Unit\Alerts;

use Modules\Alerts\Models\AlertType;
use Tests\PureUnitTestCase;

// ── Stub ──────────────────────────────────────────────────────────────────────

if (! class_exists(StubAlertType::class)) {
    final class StubAlertType extends AlertType
    {
        protected static function booted(): void {}
    }
}

// ── Test class ────────────────────────────────────────────────────────────────

final class AlertTypeSeverityTest extends PureUnitTestCase
{
    private function alertType(string $severity): StubAlertType
    {
        $t = new StubAlertType;
        $t->setRawAttributes(['severity' => $severity]);

        return $t;
    }

    // ── getSeverityColorAttribute ─────────────────────────────────────

    public function test_severity_color_info_is_blue(): void
    {
        $this->assertSame('blue', $this->alertType(AlertType::SEVERITY_INFO)->severity_color);
    }

    public function test_severity_color_warning_is_yellow(): void
    {
        $this->assertSame('yellow', $this->alertType(AlertType::SEVERITY_WARNING)->severity_color);
    }

    public function test_severity_color_error_is_red(): void
    {
        $this->assertSame('red', $this->alertType(AlertType::SEVERITY_ERROR)->severity_color);
    }

    public function test_severity_color_critical_is_red(): void
    {
        $this->assertSame('red', $this->alertType(AlertType::SEVERITY_CRITICAL)->severity_color);
    }

    public function test_severity_color_unknown_is_gray(): void
    {
        $this->assertSame('gray', $this->alertType('unknown')->severity_color);
    }

    // ── getSeverityIconAttribute ──────────────────────────────────────

    public function test_severity_icon_info_is_information_circle(): void
    {
        $this->assertSame('heroicon-o-information-circle', $this->alertType(AlertType::SEVERITY_INFO)->severity_icon);
    }

    public function test_severity_icon_warning_is_exclamation_triangle(): void
    {
        $this->assertSame('heroicon-o-exclamation-triangle', $this->alertType(AlertType::SEVERITY_WARNING)->severity_icon);
    }

    public function test_severity_icon_error_is_x_circle(): void
    {
        $this->assertSame('heroicon-o-x-circle', $this->alertType(AlertType::SEVERITY_ERROR)->severity_icon);
    }

    public function test_severity_icon_critical_is_fire(): void
    {
        $this->assertSame('heroicon-o-fire', $this->alertType(AlertType::SEVERITY_CRITICAL)->severity_icon);
    }

    public function test_severity_icon_unknown_is_bell(): void
    {
        $this->assertSame('heroicon-o-bell', $this->alertType('unknown')->severity_icon);
    }
}
