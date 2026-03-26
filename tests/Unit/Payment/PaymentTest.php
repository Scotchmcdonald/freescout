<?php

declare(strict_types=1);

namespace Tests\Unit\Payment;

use Modules\Payment\Models\Payment;
use Tests\PureUnitTestCase;

// ── Stub ──────────────────────────────────────────────────────────────────────

final class StubPayment extends Payment
{
    protected static function booted(): void {}

    public function getDateFormat(): string { return 'Y-m-d H:i:s'; }
}

// ── Test class ────────────────────────────────────────────────────────────────

final class PaymentTest extends PureUnitTestCase
{
    private function payment(array $rawAttrs): StubPayment
    {
        $p = new StubPayment();
        $p->setRawAttributes($rawAttrs);

        return $p;
    }

    // ── isSuccessful ──────────────────────────────────────────────────

    public function test_is_successful_when_status_is_successful(): void
    {
        $this->assertTrue($this->payment(['status' => 'successful'])->isSuccessful());
    }

    public function test_is_not_successful_when_status_is_pending(): void
    {
        $this->assertFalse($this->payment(['status' => 'pending'])->isSuccessful());
    }

    // ── isFailed ──────────────────────────────────────────────────────

    public function test_is_failed_when_status_is_failed(): void
    {
        $this->assertTrue($this->payment(['status' => 'failed'])->isFailed());
    }

    public function test_is_failed_when_status_is_declined(): void
    {
        $this->assertTrue($this->payment(['status' => 'declined'])->isFailed());
    }

    public function test_is_not_failed_when_status_is_successful(): void
    {
        $this->assertFalse($this->payment(['status' => 'successful'])->isFailed());
    }

    // ── isPending ─────────────────────────────────────────────────────

    public function test_is_pending_when_status_is_pending(): void
    {
        $this->assertTrue($this->payment(['status' => 'pending'])->isPending());
    }

    public function test_is_pending_when_status_is_processing(): void
    {
        $this->assertTrue($this->payment(['status' => 'processing'])->isPending());
    }

    public function test_is_not_pending_when_status_is_successful(): void
    {
        $this->assertFalse($this->payment(['status' => 'successful'])->isPending());
    }

    // ── isRefunded ────────────────────────────────────────────────────

    public function test_is_refunded_when_status_is_refunded(): void
    {
        $this->assertTrue($this->payment(['status' => 'refunded'])->isRefunded());
    }

    public function test_is_refunded_when_status_is_partially_refunded(): void
    {
        $this->assertTrue($this->payment(['status' => 'partially_refunded'])->isRefunded());
    }

    public function test_is_not_refunded_when_status_is_successful(): void
    {
        $this->assertFalse($this->payment(['status' => 'successful'])->isRefunded());
    }

    // ── canBeRefunded ─────────────────────────────────────────────────

    public function test_can_be_refunded_when_conditions_met(): void
    {
        $p = new StubPayment();
        $p->setRawAttributes([
            'status'           => 'successful',
            'refunded_amount'  => '0.00',
            'total_amount'     => '100.00',
            'created_at'       => now()->subDays(10)->format('Y-m-d H:i:s'),
            'dispute_status'   => null,
        ]);
        $this->assertTrue($p->canBeRefunded());
    }

    public function test_cannot_be_refunded_when_not_successful(): void
    {
        $p = new StubPayment();
        $p->setRawAttributes([
            'status'           => 'pending',
            'refunded_amount'  => '0.00',
            'total_amount'     => '100.00',
            'created_at'       => now()->subDays(10)->format('Y-m-d H:i:s'),
            'dispute_status'   => null,
        ]);
        $this->assertFalse($p->canBeRefunded());
    }

    public function test_cannot_be_refunded_when_fully_refunded(): void
    {
        $p = new StubPayment();
        $p->setRawAttributes([
            'status'           => 'successful',
            'refunded_amount'  => '100.00',
            'total_amount'     => '100.00',
            'created_at'       => now()->subDays(10)->format('Y-m-d H:i:s'),
            'dispute_status'   => null,
        ]);
        $this->assertFalse($p->canBeRefunded());
    }

    public function test_cannot_be_refunded_outside_180_day_window(): void
    {
        $p = new StubPayment();
        $p->setRawAttributes([
            'status'           => 'successful',
            'refunded_amount'  => '0.00',
            'total_amount'     => '100.00',
            'created_at'       => now()->subDays(200)->format('Y-m-d H:i:s'),
            'dispute_status'   => null,
        ]);
        $this->assertFalse($p->canBeRefunded());
    }

    public function test_cannot_be_refunded_when_disputed(): void
    {
        $p = new StubPayment();
        $p->setRawAttributes([
            'status'           => 'successful',
            'refunded_amount'  => '0.00',
            'total_amount'     => '100.00',
            'created_at'       => now()->subDays(10)->format('Y-m-d H:i:s'),
            'dispute_status'   => 'open',
        ]);
        $this->assertFalse($p->canBeRefunded());
    }
}
