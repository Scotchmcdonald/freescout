<?php

declare(strict_types=1);

namespace Tests\Unit\DeploymentManager;

use Modules\DeploymentManager\Models\DeploymentRecord;
use Tests\PureUnitTestCase;

// ── Stub ──────────────────────────────────────────────────────────────────────

if (! class_exists(StubDeploymentRecord::class)) {
    final class StubDeploymentRecord extends DeploymentRecord
    {
        protected static function booted(): void {}
    }
}

// ── DeploymentRecordTest ──────────────────────────────────────────────────────

final class DeploymentRecordTest extends PureUnitTestCase
{
    private function record(string $status): StubDeploymentRecord
    {
        $r = new StubDeploymentRecord;
        $r->setRawAttributes(['status' => $status]);

        return $r;
    }

    public function test_is_active_when_status_is_active(): void
    {
        $this->assertTrue($this->record('active')->isActive());
    }

    public function test_is_not_active_when_status_is_pending(): void
    {
        $this->assertFalse($this->record('pending')->isActive());
    }

    public function test_is_suspended_when_status_is_suspended(): void
    {
        $this->assertTrue($this->record('suspended')->isSuspended());
    }

    public function test_is_suspended_when_status_is_revoked(): void
    {
        $this->assertTrue($this->record('revoked')->isSuspended());
    }

    public function test_is_not_suspended_when_active(): void
    {
        $this->assertFalse($this->record('active')->isSuspended());
    }

    public function test_status_color_active_is_success(): void
    {
        $this->assertSame('success', $this->record('active')->statusColor());
    }

    public function test_status_color_pending_is_warning(): void
    {
        $this->assertSame('warning', $this->record('pending')->statusColor());
    }

    public function test_status_color_suspended_is_danger(): void
    {
        $this->assertSame('danger', $this->record('suspended')->statusColor());
    }

    public function test_status_color_revoked_is_danger(): void
    {
        $this->assertSame('danger', $this->record('revoked')->statusColor());
    }

    public function test_status_color_unknown_is_gray(): void
    {
        $this->assertSame('gray', $this->record('unknown')->statusColor());
    }
}
