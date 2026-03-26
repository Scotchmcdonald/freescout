<?php

declare(strict_types=1);

namespace Tests\Unit\ContractManager;

use Modules\ContractManager\Models\Milestone;
use Tests\PureUnitTestCase;

// ── Stub ──────────────────────────────────────────────────────────────────────

final class StubMilestoneForStatus extends Milestone
{
    protected static function booted(): void {}

    public function getDateFormat(): string { return 'Y-m-d H:i:s'; }
}

// ── Test class ────────────────────────────────────────────────────────────────

final class MilestoneStatusTest extends PureUnitTestCase
{
    private function milestone(string $status, ?string $targetDate = null): StubMilestoneForStatus
    {
        $m = new StubMilestoneForStatus();
        $attrs = ['status' => $status];
        if ($targetDate !== null) {
            $attrs['target_date'] = $targetDate;
        }
        $m->setRawAttributes($attrs);

        return $m;
    }

    // ── status predicates ─────────────────────────────────────────────

    public function test_is_achieved(): void
    {
        $this->assertTrue($this->milestone('achieved')->isAchieved());
        $this->assertFalse($this->milestone('pending')->isAchieved());
    }

    public function test_is_pending(): void
    {
        $this->assertTrue($this->milestone('pending')->isPending());
        $this->assertFalse($this->milestone('achieved')->isPending());
    }

    public function test_is_in_progress(): void
    {
        $this->assertTrue($this->milestone('in_progress')->isInProgress());
        $this->assertFalse($this->milestone('pending')->isInProgress());
    }

    public function test_is_blocked(): void
    {
        $this->assertTrue($this->milestone('blocked')->isBlocked());
        $this->assertFalse($this->milestone('pending')->isBlocked());
    }

    public function test_is_skipped(): void
    {
        $this->assertTrue($this->milestone('skipped')->isSkipped());
        $this->assertFalse($this->milestone('achieved')->isSkipped());
    }

    // ── isOverdue ─────────────────────────────────────────────────────

    public function test_is_overdue_when_target_date_past_and_not_achieved(): void
    {
        $m = $this->milestone('in_progress', now()->subDays(5)->format('Y-m-d H:i:s'));
        $this->assertTrue($m->isOverdue());
    }

    public function test_is_not_overdue_when_achieved_even_if_past(): void
    {
        $m = $this->milestone('achieved', now()->subDays(5)->format('Y-m-d H:i:s'));
        $this->assertFalse($m->isOverdue());
    }

    public function test_is_not_overdue_when_target_date_in_future(): void
    {
        $m = $this->milestone('in_progress', now()->addDays(10)->format('Y-m-d H:i:s'));
        $this->assertFalse($m->isOverdue());
    }

    public function test_is_not_overdue_when_no_target_date(): void
    {
        $m = $this->milestone('in_progress');
        $this->assertFalse($m->isOverdue());
    }

    // ── getStatusInfo ─────────────────────────────────────────────────

    public function test_status_info_for_achieved(): void
    {
        $info = $this->milestone('achieved')->getStatusInfo();
        $this->assertSame('Achieved', $info['label']);
        $this->assertSame('check-circle', $info['icon']);
    }

    public function test_status_info_for_in_progress(): void
    {
        $info = $this->milestone('in_progress')->getStatusInfo();
        $this->assertSame('In Progress', $info['label']);
        $this->assertSame('clock', $info['icon']);
    }

    public function test_status_info_for_blocked(): void
    {
        $info = $this->milestone('blocked')->getStatusInfo();
        $this->assertSame('Blocked', $info['label']);
        $this->assertSame('x-circle', $info['icon']);
    }

    public function test_status_info_for_skipped(): void
    {
        $info = $this->milestone('skipped')->getStatusInfo();
        $this->assertSame('Skipped', $info['label']);
    }

    public function test_status_info_defaults_to_pending_for_unknown(): void
    {
        $info = $this->milestone('unknown_state')->getStatusInfo();
        $this->assertSame('Pending', $info['label']);
        $this->assertSame('clock', $info['icon']);
    }

    public function test_status_info_has_required_keys(): void
    {
        $info = $this->milestone('achieved')->getStatusInfo();
        $this->assertArrayHasKey('label', $info);
        $this->assertArrayHasKey('color', $info);
        $this->assertArrayHasKey('icon', $info);
        $this->assertArrayHasKey('ring', $info);
    }
}
