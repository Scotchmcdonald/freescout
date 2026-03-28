<?php

declare(strict_types=1);

namespace Tests\Unit\SoftwareSubscriptions;

use Modules\SoftwareSubscriptions\Models\SoftwareAssignment;
use Tests\PureUnitTestCase;

if (! class_exists(StubSoftwareAssignment::class)) {
    final class StubSoftwareAssignment extends SoftwareAssignment
    {
        protected static function booted(): void {}

        public function getDateFormat(): string
        {
            return 'Y-m-d H:i:s';
        }
    }
}

final class SoftwareAssignmentTest extends PureUnitTestCase
{
    private function assignment(array $attrs): StubSoftwareAssignment
    {
        $a = new StubSoftwareAssignment;
        $a->setRawAttributes($attrs);

        return $a;
    }

    // ── deployment status constants ────────────────────────────────────

    public function test_deployment_status_constants_are_distinct(): void
    {
        $constants = [
            SoftwareAssignment::DEPLOYMENT_PENDING,
            SoftwareAssignment::DEPLOYMENT_IN_PROGRESS,
            SoftwareAssignment::DEPLOYMENT_COMPLETED,
            SoftwareAssignment::DEPLOYMENT_FAILED,
            SoftwareAssignment::DEPLOYMENT_NOT_REQUIRED,
        ];
        $this->assertSame(count($constants), count(array_unique($constants)));
    }

    public function test_revocation_reason_constants_are_distinct(): void
    {
        $constants = [
            SoftwareAssignment::REVOKED_USER_DEACTIVATED,
            SoftwareAssignment::REVOKED_ASSET_RETIRED,
            SoftwareAssignment::REVOKED_LICENSE_REASSIGNED,
            SoftwareAssignment::REVOKED_SUBSCRIPTION_CANCELLED,
            SoftwareAssignment::REVOKED_MANUAL,
        ];
        $this->assertSame(count($constants), count(array_unique($constants)));
    }

    // ── isActive ──────────────────────────────────────────────────────

    public function test_is_active_returns_true_when_revoked_at_is_null(): void
    {
        $this->assertTrue($this->assignment(['revoked_at' => null])->isActive());
    }

    public function test_is_active_returns_false_when_revoked_at_is_set(): void
    {
        $this->assertFalse(
            $this->assignment(['revoked_at' => '2024-01-15 10:00:00'])->isActive()
        );
    }

    // ── isPendingDeployment ────────────────────────────────────────────

    public function test_is_pending_deployment_true_for_pending_status(): void
    {
        $this->assertTrue(
            $this->assignment(['deployment_status' => SoftwareAssignment::DEPLOYMENT_PENDING])->isPendingDeployment()
        );
    }

    public function test_is_pending_deployment_false_for_completed_status(): void
    {
        $this->assertFalse(
            $this->assignment(['deployment_status' => SoftwareAssignment::DEPLOYMENT_COMPLETED])->isPendingDeployment()
        );
    }

    // ── isDeployed ────────────────────────────────────────────────────

    public function test_is_deployed_true_for_completed_status(): void
    {
        $this->assertTrue(
            $this->assignment(['deployment_status' => SoftwareAssignment::DEPLOYMENT_COMPLETED])->isDeployed()
        );
    }

    public function test_is_deployed_false_for_failed_status(): void
    {
        $this->assertFalse(
            $this->assignment(['deployment_status' => SoftwareAssignment::DEPLOYMENT_FAILED])->isDeployed()
        );
    }

    public function test_is_deployed_false_for_in_progress_status(): void
    {
        $this->assertFalse(
            $this->assignment(['deployment_status' => SoftwareAssignment::DEPLOYMENT_IN_PROGRESS])->isDeployed()
        );
    }
}
