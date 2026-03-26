<?php

declare(strict_types=1);

namespace Tests\Unit\Payment;

use Modules\Payment\Models\ClientCreditLedger;
use Tests\PureUnitTestCase;

final class StubClientCreditLedger extends ClientCreditLedger
{
    protected static function booted(): void {}

    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:s';
    }
}

final class ClientCreditLedgerTest extends PureUnitTestCase
{
    private function make(array $attrs): StubClientCreditLedger
    {
        $ledger = new StubClientCreditLedger;
        $ledger->setRawAttributes($attrs);

        return $ledger;
    }

    // ─── getAmountAttribute ───────────────────────────────────────────────────

    public function test_amount_converts_cents_to_dollars(): void
    {
        $ledger = $this->make(['amount_cents' => 5000]);
        $this->assertSame(50.0, $ledger->amount);
    }

    public function test_amount_handles_fractional_cents(): void
    {
        $ledger = $this->make(['amount_cents' => 1099]);
        $this->assertSame(10.99, $ledger->amount);
    }

    // ─── getBalanceAfterAttribute ─────────────────────────────────────────────

    public function test_balance_after_converts_cents_to_dollars(): void
    {
        $ledger = $this->make(['balance_after_cents' => 20000]);
        $this->assertSame(200.0, $ledger->balance_after);
    }

    // ─── getBalanceBeforeAttribute ────────────────────────────────────────────

    public function test_balance_before_is_balance_after_minus_amount(): void
    {
        // balance_before = (balance_after_cents - amount_cents) / 100
        $ledger = $this->make(['balance_after_cents' => 20000, 'amount_cents' => 5000]);
        $this->assertSame(150.0, $ledger->balance_before);
    }

    public function test_balance_before_negative_when_before_was_zero(): void
    {
        // Started at 0, added 50.00 → balance_before = 0.00
        $ledger = $this->make(['balance_after_cents' => 5000, 'amount_cents' => 5000]);
        $this->assertSame(0.0, $ledger->balance_before);
    }

    // ─── getFormattedTypeAttribute ────────────────────────────────────────────

    public function test_formatted_type_credit(): void
    {
        $ledger = $this->make(['transaction_type' => ClientCreditLedger::TYPE_CREDIT]);
        $this->assertSame('Credit', $ledger->formatted_type);
    }

    public function test_formatted_type_debit(): void
    {
        $ledger = $this->make(['transaction_type' => ClientCreditLedger::TYPE_DEBIT]);
        $this->assertSame('Debit', $ledger->formatted_type);
    }

    public function test_formatted_type_adjustment(): void
    {
        $ledger = $this->make(['transaction_type' => ClientCreditLedger::TYPE_ADJUSTMENT]);
        $this->assertSame('Adjustment', $ledger->formatted_type);
    }

    public function test_formatted_type_unknown_ucfirst_fallback(): void
    {
        $ledger = $this->make(['transaction_type' => 'refund']);
        $this->assertSame('Refund', $ledger->formatted_type);
    }

    // ─── isCredit / isDebit ────────────────────────────────────────────────────

    public function test_is_credit_true_for_credit_type(): void
    {
        $ledger = $this->make(['transaction_type' => ClientCreditLedger::TYPE_CREDIT]);
        $this->assertTrue($ledger->isCredit());
    }

    public function test_is_credit_false_for_debit_type(): void
    {
        $ledger = $this->make(['transaction_type' => ClientCreditLedger::TYPE_DEBIT]);
        $this->assertFalse($ledger->isCredit());
    }

    public function test_is_debit_true_for_debit_type(): void
    {
        $ledger = $this->make(['transaction_type' => ClientCreditLedger::TYPE_DEBIT]);
        $this->assertTrue($ledger->isDebit());
    }

    public function test_is_debit_false_for_credit_type(): void
    {
        $ledger = $this->make(['transaction_type' => ClientCreditLedger::TYPE_CREDIT]);
        $this->assertFalse($ledger->isDebit());
    }

    // ─── Constants ────────────────────────────────────────────────────────────

    public function test_type_constants_are_distinct(): void
    {
        $types = [
            ClientCreditLedger::TYPE_CREDIT,
            ClientCreditLedger::TYPE_DEBIT,
            ClientCreditLedger::TYPE_ADJUSTMENT,
        ];

        $this->assertSame(count($types), count(array_unique($types)));
    }
}
