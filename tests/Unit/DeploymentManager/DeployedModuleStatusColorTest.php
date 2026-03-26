<?php

declare(strict_types=1);

namespace Tests\Unit\DeploymentManager;

use Modules\DeploymentManager\Models\DeployedModule;
use Tests\PureUnitTestCase;

// ── Stub ──────────────────────────────────────────────────────────────────────

final class StubDeployedModule extends DeployedModule
{
    protected static function booted(): void {}
}

// ── Test class ────────────────────────────────────────────────────────────────

final class DeployedModuleStatusColorTest extends PureUnitTestCase
{
    private function module(string $status): StubDeployedModule
    {
        $m = new StubDeployedModule();
        $m->setRawAttributes(['status' => $status]);

        return $m;
    }

    public function test_status_color_active_is_success(): void
    {
        $this->assertSame('success', $this->module('active')->statusColor());
    }

    public function test_status_color_disabled_is_warning(): void
    {
        $this->assertSame('warning', $this->module('disabled')->statusColor());
    }

    public function test_status_color_error_is_danger(): void
    {
        $this->assertSame('danger', $this->module('error')->statusColor());
    }

    public function test_status_color_unknown_is_gray(): void
    {
        $this->assertSame('gray', $this->module('unknown')->statusColor());
    }
}
