<?php

declare(strict_types=1);

namespace Tests\Unit\Action1;

use Modules\Action1\Enums\MspScriptCategory;
use Tests\PureUnitTestCase;

final class MspScriptCategoryTest extends PureUnitTestCase
{
    // ─── prefix ──────────────────────────────────────────────────────────────

    public function test_diagnostic_prefix_is_msp_dx_underscore(): void
    {
        $this->assertSame('msp_dx_', MspScriptCategory::Diagnostic->prefix());
    }

    public function test_remediation_prefix_is_msp_rx_underscore(): void
    {
        $this->assertSame('msp_rx_', MspScriptCategory::Remediation->prefix());
    }

    // ─── label ───────────────────────────────────────────────────────────────

    public function test_diagnostic_label(): void
    {
        $this->assertSame('Diagnostic (DX)', MspScriptCategory::Diagnostic->label());
    }

    public function test_remediation_label(): void
    {
        $this->assertSame('Remediation (RX)', MspScriptCategory::Remediation->label());
    }

    // ─── badgeColor ───────────────────────────────────────────────────────────

    public function test_diagnostic_badge_color_is_blue(): void
    {
        $this->assertSame('blue', MspScriptCategory::Diagnostic->badgeColor());
    }

    public function test_remediation_badge_color_is_amber(): void
    {
        $this->assertSame('amber', MspScriptCategory::Remediation->badgeColor());
    }

    // ─── fromScriptName ───────────────────────────────────────────────────────

    public function test_from_script_name_returns_diagnostic_for_dx_prefix(): void
    {
        $this->assertSame(MspScriptCategory::Diagnostic, MspScriptCategory::fromScriptName('msp_dx_disk_usage'));
    }

    public function test_from_script_name_returns_remediation_for_rx_prefix(): void
    {
        $this->assertSame(MspScriptCategory::Remediation, MspScriptCategory::fromScriptName('msp_rx_clear_temp'));
    }

    public function test_from_script_name_is_case_insensitive(): void
    {
        $this->assertSame(MspScriptCategory::Diagnostic, MspScriptCategory::fromScriptName('MSP_DX_UPTIME'));
    }

    public function test_from_script_name_returns_null_for_non_msp_script(): void
    {
        $this->assertNull(MspScriptCategory::fromScriptName('vendor_script'));
    }

    public function test_from_script_name_returns_null_for_bare_msp_prefix(): void
    {
        // "msp_" alone has no category
        $this->assertNull(MspScriptCategory::fromScriptName('msp_unknown_script'));
    }

    // ─── isMspScript ──────────────────────────────────────────────────────────

    public function test_is_msp_script_true_for_msp_prefixed_name(): void
    {
        $this->assertTrue(MspScriptCategory::isMspScript('msp_dx_disk_usage'));
    }

    public function test_is_msp_script_true_for_uppercase(): void
    {
        $this->assertTrue(MspScriptCategory::isMspScript('MSP_RX_CLEAR_TEMP'));
    }

    public function test_is_msp_script_false_for_non_msp_script(): void
    {
        $this->assertFalse(MspScriptCategory::isMspScript('vendor_script_install'));
    }

    // ─── extractSlug ──────────────────────────────────────────────────────────

    public function test_extract_slug_removes_msp_dx_prefix(): void
    {
        $this->assertSame('disk_usage', MspScriptCategory::Diagnostic->extractSlug('msp_dx_disk_usage'));
    }

    public function test_extract_slug_removes_msp_rx_prefix(): void
    {
        $this->assertSame('clear_temp', MspScriptCategory::Remediation->extractSlug('msp_rx_clear_temp'));
    }

    public function test_extract_slug_returns_original_when_prefix_does_not_match(): void
    {
        // Diagnostic passed a remediation script name → no prefix match → returns full name
        $this->assertSame('msp_rx_clear_temp', MspScriptCategory::Diagnostic->extractSlug('msp_rx_clear_temp'));
    }
}
