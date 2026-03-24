<?php

declare(strict_types=1);

namespace Tests\Unit\PIB;

use Modules\PIB\Models\Invoice;
use Tests\PureUnitTestCase;

/**
 * Isolated subclass: prevents DB-touching booted() and skips casts that
 * need the encryption key or Carbon resolver in a bare PHP environment.
 */
final class TestInvoice extends Invoice
{
    protected static function booted(): void {}

    protected function casts(): array
    {
        return [];
    }
}

class InvoiceModelTest extends PureUnitTestCase
{
    private function invoice(string $status, float $total = 0.0, float $paid = 0.0): TestInvoice
    {
        $inv = new TestInvoice;
        $inv->status = $status;
        $inv->total_amount = $total;
        $inv->amount_paid = $paid;

        return $inv;
    }

    // ─── Status constants are distinct ───────────────────────────────

    public function test_all_status_constants_are_distinct(): void
    {
        $constants = [
            Invoice::STATUS_DRAFT,
            Invoice::STATUS_FINALIZED,
            Invoice::STATUS_SUBMITTED,
            Invoice::STATUS_OVERDUE,
            Invoice::STATUS_DISPUTED,
            Invoice::STATUS_PARTIALLY_PAID,
            Invoice::STATUS_PAID,
        ];

        $this->assertSame(count($constants), count(array_unique($constants)));
    }

    // ─── isPaid ──────────────────────────────────────────────────────

    public function test_is_paid_returns_true_when_status_is_paid(): void
    {
        $this->assertTrue($this->invoice(Invoice::STATUS_PAID)->isPaid());
    }

    public function test_is_paid_returns_false_for_draft(): void
    {
        $this->assertFalse($this->invoice(Invoice::STATUS_DRAFT)->isPaid());
    }

    // ─── isDraft ─────────────────────────────────────────────────────

    public function test_is_draft_returns_true_for_draft(): void
    {
        $this->assertTrue($this->invoice(Invoice::STATUS_DRAFT)->isDraft());
    }

    public function test_is_draft_returns_false_for_submitted(): void
    {
        $this->assertFalse($this->invoice(Invoice::STATUS_SUBMITTED)->isDraft());
    }

    // ─── isFinalized ─────────────────────────────────────────────────

    public function test_is_finalized_returns_true_for_finalized(): void
    {
        $this->assertTrue($this->invoice(Invoice::STATUS_FINALIZED)->isFinalized());
    }

    public function test_is_finalized_returns_false_for_draft(): void
    {
        $this->assertFalse($this->invoice(Invoice::STATUS_DRAFT)->isFinalized());
    }

    // ─── isSubmitted ─────────────────────────────────────────────────

    public function test_is_submitted_returns_true_for_submitted(): void
    {
        $this->assertTrue($this->invoice(Invoice::STATUS_SUBMITTED)->isSubmitted());
    }

    public function test_is_submitted_returns_false_for_finalized(): void
    {
        $this->assertFalse($this->invoice(Invoice::STATUS_FINALIZED)->isSubmitted());
    }

    // ─── isDisputed ──────────────────────────────────────────────────

    public function test_is_disputed_returns_true_for_disputed(): void
    {
        $this->assertTrue($this->invoice(Invoice::STATUS_DISPUTED)->isDisputed());
    }

    public function test_is_disputed_returns_false_for_paid(): void
    {
        $this->assertFalse($this->invoice(Invoice::STATUS_PAID)->isDisputed());
    }

    // ─── isPartiallyPaid ─────────────────────────────────────────────

    public function test_is_partially_paid_returns_true(): void
    {
        $this->assertTrue($this->invoice(Invoice::STATUS_PARTIALLY_PAID)->isPartiallyPaid());
    }

    public function test_is_partially_paid_returns_false_for_paid(): void
    {
        $this->assertFalse($this->invoice(Invoice::STATUS_PAID)->isPartiallyPaid());
    }

    // ─── isOverdue ───────────────────────────────────────────────────

    public function test_is_overdue_returns_true_for_overdue_status(): void
    {
        $this->assertTrue($this->invoice(Invoice::STATUS_OVERDUE)->isOverdue());
    }

    public function test_is_overdue_returns_true_for_legacy_past_due(): void
    {
        $this->assertTrue($this->invoice('past_due')->isOverdue());
    }

    public function test_is_overdue_returns_false_for_paid(): void
    {
        $this->assertFalse($this->invoice(Invoice::STATUS_PAID)->isOverdue());
    }

    // ─── isEditable ──────────────────────────────────────────────────

    public function test_is_editable_returns_true_for_draft(): void
    {
        $this->assertTrue($this->invoice(Invoice::STATUS_DRAFT)->isEditable());
    }

    public function test_is_editable_returns_true_for_finalized(): void
    {
        $this->assertTrue($this->invoice(Invoice::STATUS_FINALIZED)->isEditable());
    }

    public function test_is_editable_returns_false_for_submitted(): void
    {
        $this->assertFalse($this->invoice(Invoice::STATUS_SUBMITTED)->isEditable());
    }

    public function test_is_editable_returns_false_for_paid(): void
    {
        $this->assertFalse($this->invoice(Invoice::STATUS_PAID)->isEditable());
    }

    // ─── isPayable ───────────────────────────────────────────────────

    public function test_is_payable_returns_true_for_submitted(): void
    {
        $this->assertTrue($this->invoice(Invoice::STATUS_SUBMITTED)->isPayable());
    }

    public function test_is_payable_returns_true_for_overdue(): void
    {
        $this->assertTrue($this->invoice(Invoice::STATUS_OVERDUE)->isPayable());
    }

    public function test_is_payable_returns_true_for_disputed(): void
    {
        $this->assertTrue($this->invoice(Invoice::STATUS_DISPUTED)->isPayable());
    }

    public function test_is_payable_returns_true_for_partially_paid(): void
    {
        $this->assertTrue($this->invoice(Invoice::STATUS_PARTIALLY_PAID)->isPayable());
    }

    public function test_is_payable_returns_false_for_draft(): void
    {
        $this->assertFalse($this->invoice(Invoice::STATUS_DRAFT)->isPayable());
    }

    // ─── canTransitionTo ─────────────────────────────────────────────

    public function test_can_transition_draft_to_finalized(): void
    {
        $this->assertTrue($this->invoice(Invoice::STATUS_DRAFT)->canTransitionTo(Invoice::STATUS_FINALIZED));
    }

    public function test_cannot_transition_draft_to_paid_directly(): void
    {
        $this->assertFalse($this->invoice(Invoice::STATUS_DRAFT)->canTransitionTo(Invoice::STATUS_PAID));
    }

    public function test_cannot_transition_paid_to_any_status(): void
    {
        $paid = $this->invoice(Invoice::STATUS_PAID);
        $this->assertFalse($paid->canTransitionTo(Invoice::STATUS_DRAFT));
        $this->assertFalse($paid->canTransitionTo(Invoice::STATUS_SUBMITTED));
    }

    public function test_can_transition_finalized_to_submitted(): void
    {
        $this->assertTrue($this->invoice(Invoice::STATUS_FINALIZED)->canTransitionTo(Invoice::STATUS_SUBMITTED));
    }

    // ─── getOutstandingBalanceAttribute ──────────────────────────────

    public function test_outstanding_balance_is_total_minus_paid(): void
    {
        $inv = $this->invoice(Invoice::STATUS_SUBMITTED, 150.0, 50.0);
        $this->assertEqualsWithDelta(100.0, $inv->getOutstandingBalanceAttribute(), 0.001);
    }

    public function test_outstanding_balance_floors_at_zero_when_overpaid(): void
    {
        $inv = $this->invoice(Invoice::STATUS_PAID, 100.0, 200.0);
        $this->assertEqualsWithDelta(0.0, $inv->getOutstandingBalanceAttribute(), 0.001);
    }

    public function test_outstanding_balance_equals_total_when_nothing_paid(): void
    {
        $inv = $this->invoice(Invoice::STATUS_SUBMITTED, 250.0, 0.0);
        $this->assertEqualsWithDelta(250.0, $inv->getOutstandingBalanceAttribute(), 0.001);
    }

    // ─── statusBadgeVariant ──────────────────────────────────────────

    public function test_status_badge_variant_paid_is_success(): void
    {
        $this->assertSame('success', $this->invoice(Invoice::STATUS_PAID)->statusBadgeVariant());
    }

    public function test_status_badge_variant_overdue_is_danger(): void
    {
        $this->assertSame('danger', $this->invoice(Invoice::STATUS_OVERDUE)->statusBadgeVariant());
    }

    public function test_status_badge_variant_disputed_is_danger(): void
    {
        $this->assertSame('danger', $this->invoice(Invoice::STATUS_DISPUTED)->statusBadgeVariant());
    }

    public function test_status_badge_variant_partially_paid_is_warning(): void
    {
        $this->assertSame('warning', $this->invoice(Invoice::STATUS_PARTIALLY_PAID)->statusBadgeVariant());
    }

    public function test_status_badge_variant_draft_falls_to_default(): void
    {
        $this->assertSame('default', $this->invoice(Invoice::STATUS_DRAFT)->statusBadgeVariant());
    }

    public function test_status_badge_variant_submitted_is_primary(): void
    {
        $this->assertSame('primary', $this->invoice(Invoice::STATUS_SUBMITTED)->statusBadgeVariant());
    }

    // ─── statusLabel ─────────────────────────────────────────────────

    public function test_status_label_draft(): void
    {
        $this->assertSame('Draft', $this->invoice(Invoice::STATUS_DRAFT)->statusLabel());
    }

    public function test_status_label_finalized(): void
    {
        $this->assertSame('Finalized', $this->invoice(Invoice::STATUS_FINALIZED)->statusLabel());
    }

    public function test_status_label_submitted(): void
    {
        $this->assertSame('Submitted', $this->invoice(Invoice::STATUS_SUBMITTED)->statusLabel());
    }

    public function test_status_label_paid(): void
    {
        $this->assertSame('Paid', $this->invoice(Invoice::STATUS_PAID)->statusLabel());
    }

    public function test_status_label_overdue(): void
    {
        $this->assertSame('Overdue', $this->invoice(Invoice::STATUS_OVERDUE)->statusLabel());
    }

    public function test_status_label_disputed(): void
    {
        $this->assertSame('Disputed', $this->invoice(Invoice::STATUS_DISPUTED)->statusLabel());
    }

    public function test_status_label_partially_paid(): void
    {
        $this->assertSame('Partially Paid', $this->invoice(Invoice::STATUS_PARTIALLY_PAID)->statusLabel());
    }

    public function test_status_label_unknown_status_ucfirsts_value(): void
    {
        $this->assertSame('Pending', $this->invoice('pending')->statusLabel());
    }

    // ─── getFormattedTotalAttribute ───────────────────────────────────

    public function test_formatted_total_includes_dollar_sign_and_two_decimals(): void
    {
        $inv = $this->invoice(Invoice::STATUS_DRAFT, 1234.5);
        $this->assertSame('$1,234.50', $inv->getFormattedTotalAttribute());
    }

    public function test_formatted_total_zero(): void
    {
        $inv = $this->invoice(Invoice::STATUS_DRAFT, 0.0);
        $this->assertSame('$0.00', $inv->getFormattedTotalAttribute());
    }
}
