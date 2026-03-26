<?php

declare(strict_types=1);

namespace Tests\Unit\ContractManager;

use Illuminate\Support\Carbon;
use Modules\ContractManager\Models\Milestone;
use Tests\PureUnitTestCase;

// Guard against class redeclaration when ParaTest loads this file across
// multiple workers in the same process (prevents fatal "already in use" crash).
if (! class_exists(StubMilestone::class)) {
    final class StubMilestone extends Milestone
    {
        protected static function booted(): void {}

        public function getDateFormat(): string
        {
            return 'Y-m-d H:i:s';
        }
    }
}

final class MilestonePredicatesTest extends PureUnitTestCase
{
    // ─── isAchieved ───────────────────────────────────────────────────────────

    public function test_is_achieved_true_for_achieved_status(): void
    {
        $m = new StubMilestone;
        $m->status = 'achieved';
        $this->assertTrue($m->isAchieved());
    }

    public function test_is_achieved_false_for_other_status(): void
    {
        $m = new StubMilestone;
        $m->status = 'pending';
        $this->assertFalse($m->isAchieved());
    }

    // ─── isPending ────────────────────────────────────────────────────────────

    public function test_is_pending_true_for_pending_status(): void
    {
        $m = new StubMilestone;
        $m->status = 'pending';
        $this->assertTrue($m->isPending());
    }

    public function test_is_pending_false_for_in_progress(): void
    {
        $m = new StubMilestone;
        $m->status = 'in_progress';
        $this->assertFalse($m->isPending());
    }

    // ─── isInProgress ─────────────────────────────────────────────────────────

    public function test_is_in_progress_true_for_in_progress_status(): void
    {
        $m = new StubMilestone;
        $m->status = 'in_progress';
        $this->assertTrue($m->isInProgress());
    }

    // ─── isBlocked ────────────────────────────────────────────────────────────

    public function test_is_blocked_true_for_blocked_status(): void
    {
        $m = new StubMilestone;
        $m->status = 'blocked';
        $this->assertTrue($m->isBlocked());
    }

    public function test_is_blocked_false_for_pending(): void
    {
        $m = new StubMilestone;
        $m->status = 'pending';
        $this->assertFalse($m->isBlocked());
    }

    // ─── isSkipped ────────────────────────────────────────────────────────────

    public function test_is_skipped_true_for_skipped_status(): void
    {
        $m = new StubMilestone;
        $m->status = 'skipped';
        $this->assertTrue($m->isSkipped());
    }

    // ─── isOverdue ────────────────────────────────────────────────────────────

    public function test_is_overdue_false_when_no_target_date(): void
    {
        $m = new StubMilestone;
        $m->status = 'pending';
        $this->assertFalse($m->isOverdue());
    }

    public function test_is_overdue_true_when_past_target_date_and_not_achieved(): void
    {
        $m = new StubMilestone;
        $m->setRawAttributes([
            'status' => 'pending',
            'target_date' => Carbon::now()->subDays(5)->toDateTimeString(),
        ]);
        $this->assertTrue($m->isOverdue());
    }

    public function test_is_overdue_false_when_achieved_even_if_past_target(): void
    {
        $m = new StubMilestone;
        $m->setRawAttributes([
            'status' => 'achieved',
            'target_date' => Carbon::now()->subDays(5)->toDateTimeString(),
        ]);
        $this->assertFalse($m->isOverdue());
    }

    public function test_is_overdue_false_when_target_date_in_future(): void
    {
        $m = new StubMilestone;
        $m->setRawAttributes([
            'status' => 'pending',
            'target_date' => Carbon::now()->addDays(5)->toDateTimeString(),
        ]);
        $this->assertFalse($m->isOverdue());
    }

    // ─── getStatusInfo ────────────────────────────────────────────────────────

    public function test_get_status_info_achieved_has_correct_label(): void
    {
        $m = new StubMilestone;
        $m->status = 'achieved';
        $info = $m->getStatusInfo();
        $this->assertSame('Achieved', $info['label']);
        $this->assertArrayHasKey('color', $info);
        $this->assertArrayHasKey('icon', $info);
    }

    public function test_get_status_info_blocked_has_danger_color(): void
    {
        $m = new StubMilestone;
        $m->status = 'blocked';
        $info = $m->getStatusInfo();
        $this->assertStringContainsString('danger', $info['color']);
    }

    public function test_get_status_info_in_progress_has_info_color(): void
    {
        $m = new StubMilestone;
        $m->status = 'in_progress';
        $info = $m->getStatusInfo();
        $this->assertStringContainsString('info', $info['color']);
    }

    public function test_get_status_info_unknown_status_falls_back_to_pending(): void
    {
        $m = new StubMilestone;
        $m->status = 'nonexistent_status';
        $info = $m->getStatusInfo();
        $this->assertSame('Pending', $info['label']);
    }

    // ─── getDurationAttribute ─────────────────────────────────────────────────

    public function test_get_duration_returns_null_when_no_started_at(): void
    {
        $m = new StubMilestone;
        $this->assertNull($m->getDurationAttribute());
    }

    public function test_get_duration_returns_day_label_for_multi_day_range(): void
    {
        $m = new StubMilestone;
        $m->setRawAttributes([
            'started_at' => Carbon::now()->subDays(3)->toDateTimeString(),
            'completed_at' => Carbon::now()->toDateTimeString(),
        ]);

        $duration = $m->getDurationAttribute();
        $this->assertStringContainsString('day', $duration);
    }

    public function test_get_duration_singular_day(): void
    {
        $m = new StubMilestone;
        $m->setRawAttributes([
            'started_at' => Carbon::now()->subDays(1)->subHours(2)->toDateTimeString(),
            'completed_at' => Carbon::now()->toDateTimeString(),
        ]);

        $duration = $m->getDurationAttribute();
        $this->assertStringContainsString('day', $duration);
    }

    // ─── canGenerateInvoice ───────────────────────────────────────────────────

    public function test_can_generate_invoice_true_when_all_conditions_met(): void
    {
        $m = new StubMilestone;
        $m->status = 'achieved';
        $m->client_approved = true;
        $m->billing_amount = 500.0;
        $m->invoice_id = null;

        $this->assertTrue($m->canGenerateInvoice());
    }

    public function test_can_generate_invoice_false_when_not_achieved(): void
    {
        $m = new StubMilestone;
        $m->status = 'pending';
        $m->client_approved = true;
        $m->billing_amount = 500.0;
        $m->invoice_id = null;

        $this->assertFalse($m->canGenerateInvoice());
    }

    public function test_can_generate_invoice_false_when_not_client_approved(): void
    {
        $m = new StubMilestone;
        $m->status = 'achieved';
        $m->client_approved = false;
        $m->billing_amount = 500.0;
        $m->invoice_id = null;

        $this->assertFalse($m->canGenerateInvoice());
    }

    public function test_can_generate_invoice_false_when_billing_amount_is_zero(): void
    {
        $m = new StubMilestone;
        $m->status = 'achieved';
        $m->client_approved = true;
        $m->billing_amount = 0.0;
        $m->invoice_id = null;

        $this->assertFalse($m->canGenerateInvoice());
    }

    public function test_can_generate_invoice_false_when_invoice_already_exists(): void
    {
        $m = new StubMilestone;
        $m->status = 'achieved';
        $m->client_approved = true;
        $m->billing_amount = 500.0;
        $m->invoice_id = 42;

        $this->assertFalse($m->canGenerateInvoice());
    }
}
