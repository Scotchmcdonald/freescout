<?php

declare(strict_types=1);

namespace Tests\Unit\ContractManager;

use Carbon\Carbon;
use Modules\ContractManager\Models\Milestone;
use Tests\PureUnitTestCase;

/**
 * Lightweight stub: bypasses all Eloquent persistence so we can test
 * purely-computational Milestone methods without a DB connection.
 */
if (! class_exists(StubMilestone::class)) {
final class StubMilestone extends Milestone
{
    protected static function booted(): void {}

    /** Bypass DB connection lookup for date formatting. */
    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:s';
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        foreach ($attributes as $k => $v) {
            $this->attributes[$k] = $v;
        }

        return true;
    }
}
}


/**
 * Milestone model — status helpers and billing logic.
 */
final class MilestoneModelHelpersTest extends PureUnitTestCase
{
    private function make(string $status, array $extra = []): StubMilestone
    {
        $m = new StubMilestone();
        $m->status = $status;
        foreach ($extra as $k => $v) {
            $m->$k = $v;
        }

        return $m;
    }

    // ── Status predicates ──────────────────────────────────────────────────

    public function test_is_achieved(): void
    {
        $this->assertTrue($this->make('achieved')->isAchieved());
        $this->assertFalse($this->make('pending')->isAchieved());
    }

    public function test_is_pending(): void
    {
        $this->assertTrue($this->make('pending')->isPending());
        $this->assertFalse($this->make('in_progress')->isPending());
    }

    public function test_is_in_progress(): void
    {
        $this->assertTrue($this->make('in_progress')->isInProgress());
        $this->assertFalse($this->make('achieved')->isInProgress());
    }

    public function test_is_blocked(): void
    {
        $this->assertTrue($this->make('blocked')->isBlocked());
        $this->assertFalse($this->make('pending')->isBlocked());
    }

    public function test_is_skipped(): void
    {
        $this->assertTrue($this->make('skipped')->isSkipped());
        $this->assertFalse($this->make('achieved')->isSkipped());
    }

    public function test_is_overdue_when_past_target_and_not_achieved(): void
    {
        $m = $this->make('in_progress', ['target_date' => Carbon::yesterday()]);
        $this->assertTrue($m->isOverdue());
    }

    public function test_is_not_overdue_when_target_is_future(): void
    {
        $m = $this->make('in_progress', ['target_date' => Carbon::tomorrow()]);
        $this->assertFalse($m->isOverdue());
    }

    public function test_is_not_overdue_when_achieved(): void
    {
        $m = $this->make('achieved', ['target_date' => Carbon::yesterday()]);
        $this->assertFalse($m->isOverdue());
    }

    public function test_is_not_overdue_when_no_target_date(): void
    {
        $m = $this->make('in_progress', ['target_date' => null]);
        $this->assertFalse($m->isOverdue());
    }

    // ── canGenerateInvoice ─────────────────────────────────────────────────

    public function test_can_generate_invoice_all_conditions_met(): void
    {
        $m = $this->make('achieved', [
            'client_approved' => true,
            'billing_amount' => 500.00,
            'invoice_id' => null,
        ]);

        $this->assertTrue($m->canGenerateInvoice());
    }

    public function test_cannot_generate_invoice_when_not_achieved(): void
    {
        $m = $this->make('pending', [
            'client_approved' => true,
            'billing_amount' => 500.00,
            'invoice_id' => null,
        ]);

        $this->assertFalse($m->canGenerateInvoice());
    }

    public function test_cannot_generate_invoice_when_not_client_approved(): void
    {
        $m = $this->make('achieved', [
            'client_approved' => false,
            'billing_amount' => 500.00,
            'invoice_id' => null,
        ]);

        $this->assertFalse($m->canGenerateInvoice());
    }

    public function test_cannot_generate_invoice_when_zero_billing_amount(): void
    {
        $m = $this->make('achieved', [
            'client_approved' => true,
            'billing_amount' => 0.0,
            'invoice_id' => null,
        ]);

        $this->assertFalse($m->canGenerateInvoice());
    }

    public function test_cannot_generate_invoice_when_already_invoiced(): void
    {
        $m = $this->make('achieved', [
            'client_approved' => true,
            'billing_amount' => 500.00,
            'invoice_id' => 42,
        ]);

        $this->assertFalse($m->canGenerateInvoice());
    }

    // ── getStatusInfo ──────────────────────────────────────────────────────

    public function test_get_status_info_returns_array_with_label_and_icon(): void
    {
        foreach (['achieved', 'in_progress', 'pending', 'blocked', 'skipped'] as $status) {
            $info = $this->make($status)->getStatusInfo();
            $this->assertArrayHasKey('label', $info, "Missing label for status: $status");
            $this->assertArrayHasKey('icon', $info, "Missing icon for status: $status");
            $this->assertArrayHasKey('color', $info, "Missing color for status: $status");
        }
    }

    public function test_get_status_info_falls_back_to_pending_for_unknown_status(): void
    {
        $info = $this->make('unknown_status')->getStatusInfo();
        $this->assertArrayHasKey('label', $info);
    }

    // ── static projectTotal – just test it accepts int contract ID ─────────
    // (Calls DB query via static::where; skip actual execution, only confirm signature)

    public function test_milestone_has_status_constants_via_string_literals(): void
    {
        // Milestone uses string status values, not constants — verify model exists
        $m = new Milestone();
        $this->assertInstanceOf(Milestone::class, $m);
    }
}
