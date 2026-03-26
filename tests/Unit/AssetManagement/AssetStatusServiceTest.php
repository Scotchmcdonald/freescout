<?php

declare(strict_types=1);

namespace Tests\Unit\AssetManagement;

use Illuminate\Database\DatabaseManager;
use Modules\AssetManagement\Exceptions\InvalidStatusTransitionException;
use Modules\AssetManagement\Services\AssetStatusService;
use Tests\PureUnitTestCase;

final class AssetStatusServiceTest extends PureUnitTestCase
{
    private AssetStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $db = $this->createMock(DatabaseManager::class);
        $this->service = new AssetStatusService($db);
    }

    // ─── getAllStatuses ────────────────────────────────────────────────────────

    public function test_get_all_statuses_returns_all_five(): void
    {
        $statuses = $this->service->getAllStatuses();

        $this->assertCount(5, $statuses);
        $this->assertContains(AssetStatusService::STATUS_PENDING, $statuses);
        $this->assertContains(AssetStatusService::STATUS_ACTIVE, $statuses);
        $this->assertContains(AssetStatusService::STATUS_INACTIVE, $statuses);
        $this->assertContains(AssetStatusService::STATUS_MAINTENANCE, $statuses);
        $this->assertContains(AssetStatusService::STATUS_RETIRED, $statuses);
    }

    public function test_status_constants_are_distinct(): void
    {
        $constants = [
            AssetStatusService::STATUS_PENDING,
            AssetStatusService::STATUS_ACTIVE,
            AssetStatusService::STATUS_INACTIVE,
            AssetStatusService::STATUS_MAINTENANCE,
            AssetStatusService::STATUS_RETIRED,
        ];

        $this->assertSame(count($constants), count(array_unique($constants)));
    }

    // ─── isValidTransition – same status ──────────────────────────────────────

    public function test_same_status_transition_is_always_valid(): void
    {
        foreach ($this->service->getAllStatuses() as $status) {
            $this->assertTrue(
                $this->service->isValidTransition($status, $status),
                "Expected same-status transition to be valid for: $status"
            );
        }
    }

    // ─── isValidTransition – valid transitions ────────────────────────────────

    public function test_pending_can_transition_to_active(): void
    {
        $this->assertTrue($this->service->isValidTransition('pending', 'active'));
    }

    public function test_pending_can_transition_to_inactive(): void
    {
        $this->assertTrue($this->service->isValidTransition('pending', 'inactive'));
    }

    public function test_pending_can_transition_to_retired(): void
    {
        $this->assertTrue($this->service->isValidTransition('pending', 'retired'));
    }

    public function test_active_can_transition_to_inactive(): void
    {
        $this->assertTrue($this->service->isValidTransition('active', 'inactive'));
    }

    public function test_active_can_transition_to_maintenance(): void
    {
        $this->assertTrue($this->service->isValidTransition('active', 'maintenance'));
    }

    public function test_active_can_transition_to_retired(): void
    {
        $this->assertTrue($this->service->isValidTransition('active', 'retired'));
    }

    public function test_inactive_can_transition_to_active(): void
    {
        $this->assertTrue($this->service->isValidTransition('inactive', 'active'));
    }

    public function test_maintenance_can_transition_to_active(): void
    {
        $this->assertTrue($this->service->isValidTransition('maintenance', 'active'));
    }

    // ─── isValidTransition – invalid transitions ──────────────────────────────

    public function test_retired_cannot_transition_to_active(): void
    {
        $this->assertFalse($this->service->isValidTransition('retired', 'active'));
    }

    public function test_retired_cannot_transition_to_inactive(): void
    {
        $this->assertFalse($this->service->isValidTransition('retired', 'inactive'));
    }

    public function test_active_cannot_transition_to_pending(): void
    {
        $this->assertFalse($this->service->isValidTransition('active', 'pending'));
    }

    public function test_unknown_status_returns_false(): void
    {
        $this->assertFalse($this->service->isValidTransition('unknown', 'active'));
    }

    // ─── getValidTargetStatuses ───────────────────────────────────────────────

    public function test_retired_has_no_valid_targets(): void
    {
        $this->assertSame([], $this->service->getValidTargetStatuses('retired'));
    }

    public function test_pending_has_three_valid_targets(): void
    {
        $targets = $this->service->getValidTargetStatuses('pending');
        $this->assertCount(3, $targets);
        $this->assertContains('active', $targets);
        $this->assertContains('inactive', $targets);
        $this->assertContains('retired', $targets);
    }

    public function test_get_valid_targets_for_unknown_returns_empty(): void
    {
        $this->assertSame([], $this->service->getValidTargetStatuses('no_such_status'));
    }

    // ─── InvalidStatusTransitionException message ─────────────────────────────

    public function test_exception_contains_asset_id_and_statuses_in_message(): void
    {
        $ex = new InvalidStatusTransitionException(
            assetId: 99,
            fromStatus: 'retired',
            toStatus: 'active',
            validTargets: []
        );

        $this->assertStringContainsString('99', $ex->getMessage());
        $this->assertStringContainsString('retired', $ex->getMessage());
        $this->assertStringContainsString('active', $ex->getMessage());
        $this->assertStringContainsString('terminal state', $ex->getMessage());
    }

    public function test_exception_lists_valid_targets_in_message(): void
    {
        $ex = new InvalidStatusTransitionException(
            assetId: 5,
            fromStatus: 'active',
            toStatus: 'pending',
            validTargets: ['inactive', 'maintenance', 'retired']
        );

        $this->assertStringContainsString('inactive', $ex->getMessage());
        $this->assertStringContainsString('maintenance', $ex->getMessage());
        $this->assertStringContainsString('retired', $ex->getMessage());
    }

    public function test_exception_custom_message_is_used_when_provided(): void
    {
        $ex = new InvalidStatusTransitionException(
            assetId: 1,
            fromStatus: 'x',
            toStatus: 'y',
            validTargets: [],
            message: 'custom error message'
        );

        $this->assertSame('custom error message', $ex->getMessage());
    }

    public function test_authorization_boundary_retired_asset_is_permanently_locked(): void
    {
        // Authorization boundary: once an asset reaches the 'retired' terminal state,
        // ALL further transitions must be denied — retirement is a permanent
        // decommission authorization gate with no recovery path.
        $allStatuses = $this->service->getAllStatuses();

        foreach ($allStatuses as $targetStatus) {
            if ($targetStatus === 'retired') {
                continue; // self-transition is always valid, skip
            }
            $this->assertFalse(
                $this->service->isValidTransition('retired', $targetStatus),
                "Authorization boundary: retired asset must not be allowed to transition to {$targetStatus}"
            );
        }

        $this->assertSame([], $this->service->getValidTargetStatuses('retired'),
            'Authorization boundary: retired status must have zero authorized target transitions'
        );
    }
}
