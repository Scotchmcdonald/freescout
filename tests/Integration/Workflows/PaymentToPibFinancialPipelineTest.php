<?php

declare(strict_types=1);

namespace Tests\Integration\Workflows;

use App\Actions\DisputeInvoiceAction;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;
use Modules\Payment\DataTransferObjects\PaymentDisputedData;
use Modules\Payment\Events\PaymentDisputed;
use Modules\Payment\Models\ClientCreditLedger;
use Modules\Payment\Services\ClientCreditService;
use Modules\PIB\Events\InvoicePaid;
use Modules\PIB\Listeners\PaymentDisputedListener;
use Modules\PIB\Models\Invoice;
use PHPUnit\Framework\Attributes\Group;
use Tests\IntegrationTestCase;

/**
 * Integration test: Payment module credit operations → PIB invoice state transitions.
 *
 * Covers the cross-module financial pipeline:
 *   ClientCreditService::addCredit()
 *     → applyToInvoice()           (deducts credit, settles invoice)
 *     → Invoice transitions to `paid`, paid_at stamped
 *     → Ledger debit entry written
 *     → Final balance assertion
 *
 * Dispute path:
 *   PaymentDisputed event
 *     → PaymentDisputedListener marks invoice as `disputed` with metadata
 *     → Credit reinstated manually
 *     → Balance reconciliation verified
 */
class PaymentToPibFinancialPipelineTest extends IntegrationTestCase
{
    private Company $company;

    private Client $client;

    private ClientCreditService $creditService;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(Invoice::class)) {
            $this->markTestSkipped('PIB module not available');
        }

        $this->company = Company::factory()->create(['name' => 'Pipeline Test MSP']);
        $this->client = Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Pipeline Test Client',
        ]);

        // Use Payment module's ClientCreditService — it owns applyToInvoice().
        $this->creditService = app(ClientCreditService::class);
    }

    /**
     * Happy path: credit added then applied fully pays an invoice.
     *
     * Chain: addCredit → applyToInvoice → Invoice.status=paid → Ledger debit entry.
     */
    public function test_credit_applied_fully_pays_invoice_and_writes_ledger(): void
    {
        // Arrange: client has $200 credit on account.
        $this->creditService->addCredit($this->client, 200.00, 'Prepayment credit');
        $this->assertEqualsWithDelta(200.00, $this->creditService->getBalance($this->client), 0.01, 'Initial credit balance should be $200');

        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'status' => 'submitted',
            'total_amount' => 150.00,
        ]);

        // Act: apply $150 credit to settle the $150 invoice.
        $ledgerEntry = $this->creditService->applyToInvoice($this->client, $invoice, 150.00);

        // Assert: ledger entry is a debit for $150 (stored as negative cents).
        $this->assertInstanceOf(ClientCreditLedger::class, $ledgerEntry);
        $this->assertEquals(ClientCreditLedger::TYPE_DEBIT, $ledgerEntry->transaction_type);
        $this->assertEquals(-15000, $ledgerEntry->amount_cents, 'Debit amount_cents should be -15000');
        $this->assertEquals(5000, $ledgerEntry->balance_after_cents, 'Remaining balance_after_cents should be 5000 ($50)');

        // Assert: invoice is now paid with paid_at timestamp.
        $invoice->refresh();
        $this->assertEquals(Invoice::STATUS_PAID, $invoice->status);
        $this->assertNotNull($invoice->paid_at, 'paid_at should be stamped when invoice is settled');

        // Assert: client balance is now $200 - $150 = $50.
        $this->assertEqualsWithDelta(50.00, $this->creditService->getBalance($this->client), 0.01, 'Final credit balance should be $50');
    }

    /**
     * Ledger audit trail: all entries are preserved in insert order.
     *
     * Multiple credit operations must each create distinct ledger rows.
     */
    public function test_ledger_records_full_audit_trail(): void
    {
        $this->creditService->addCredit($this->client, 100.00, 'First top-up');
        $this->creditService->addCredit($this->client, 50.00, 'Second top-up');

        // Apply $80 to an invoice.
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'status' => 'submitted',
            'total_amount' => 80.00,
        ]);
        $this->creditService->applyToInvoice($this->client, $invoice, 80.00);

        $entries = ClientCreditLedger::where('client_id', $this->client->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(3, $entries, '2 credits + 1 debit = 3 ledger entries');
        $this->assertEquals(ClientCreditLedger::TYPE_CREDIT, $entries[0]->transaction_type);
        $this->assertEquals(ClientCreditLedger::TYPE_CREDIT, $entries[1]->transaction_type);
        $this->assertEquals(ClientCreditLedger::TYPE_DEBIT, $entries[2]->transaction_type);

        // Running balance: +100 → 100, +50 → 150, -80 → 70.
        $this->assertEquals(10000, $entries[0]->balance_after_cents);
        $this->assertEquals(15000, $entries[1]->balance_after_cents);
        $this->assertEquals(7000, $entries[2]->balance_after_cents);
    }

    /**
     * Partial credit application leaves invoice open.
     *
     * When credit covers only part of the invoice total, the invoice must
     * NOT transition to `paid` — it should remain in its current status.
     */
    public function test_partial_credit_does_not_pay_invoice(): void
    {
        // Arrange: client has $50, invoice needs $150.
        $this->creditService->addCredit($this->client, 50.00, 'Partial credit');

        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'status' => 'submitted',
            'total_amount' => 150.00,
        ]);

        // Act: apply only $50 (partial).
        $ledgerEntry = $this->creditService->applyToInvoice($this->client, $invoice, 50.00);

        // Assert: invoice is NOT paid.
        $invoice->refresh();
        $this->assertNotEquals(Invoice::STATUS_PAID, $invoice->status, 'Partial payment must not mark invoice as paid');

        // Assert: ledger debit recorded correctly.
        $this->assertEquals(ClientCreditLedger::TYPE_DEBIT, $ledgerEntry->transaction_type);
        $this->assertEquals(-5000, $ledgerEntry->amount_cents);
        $this->assertEquals(0, $ledgerEntry->balance_after_cents);

        // Assert: client balance drained to zero.
        $this->assertEqualsWithDelta(0.00, $this->creditService->getBalance($this->client), 0.01);
    }

    /**
     * Invoice::markAsPaid() fires the InvoicePaid domain event.
     *
     * Downstream consumers (e.g. notification listeners) rely on this event.
     */
    public function test_mark_as_paid_dispatches_invoice_paid_event(): void
    {
        Event::fake([InvoicePaid::class]);

        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'status' => 'submitted',
            'total_amount' => 100.00,
        ]);

        $invoice->markAsPaid();

        Event::assertDispatched(InvoicePaid::class, function (InvoicePaid $event) use ($invoice): bool {
            return $event->invoice->id === $invoice->id;
        });
    }

    /**
     * PaymentDisputedListener marks invoice as `disputed` with provenance metadata.
     *
     * Simulates a gateway chargeback: the listener is driven directly to verify
     * it writes the correct state without routing through the action layer.
     */
    public function test_payment_disputed_listener_marks_invoice_as_disputed(): void
    {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'status' => 'submitted',
            'total_amount' => 100.00,
        ]);

        $event = new PaymentDisputed(
            new PaymentDisputedData(
                paymentId: 42,
                invoiceId: $invoice->id,
                amount: 100.00,
                reason: 'Chargeback from card issuer',
                disputeStatus: 'opened',
            )
        );

        $listener = app(PaymentDisputedListener::class);
        $listener->handle($event);

        $invoice->refresh();
        $this->assertEquals(Invoice::STATUS_DISPUTED, $invoice->status);
        $this->assertEquals('Chargeback from card issuer', $invoice->metadata['last_dispute_reason']);
        $this->assertEquals(42, $invoice->metadata['disputed_payment_id']);
        $this->assertArrayHasKey('last_dispute_date', $invoice->metadata);
    }

    /**
     * End-to-end: DisputeInvoiceAction → PaymentDisputed event → listener → DB state.
     *
     * Exercises the full event chain without Event::fake() so the registered
     * PIBServiceProvider listener actually processes the dispute.
     */
    public function test_dispute_action_end_to_end_transitions_invoice_to_disputed(): void
    {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'status' => 'submitted',
            'total_amount' => 250.00,
        ]);

        $actor = User::factory()->create();
        $action = app(DisputeInvoiceAction::class);

        // Act: execute dispute workflow (policy bypassed for integration test).
        $result = $action->execute($invoice, $actor, 'Service not delivered', bypassPolicy: true);

        // Assert: action returned an invoice in the disputed state.
        $this->assertEquals(Invoice::STATUS_DISPUTED, $result->status);

        // Assert: invoice in the DB reflects the dispute with metadata.
        $invoice->refresh();
        $this->assertEquals(Invoice::STATUS_DISPUTED, $invoice->status);
        $this->assertArrayHasKey('dispute_reason', $invoice->metadata);
        $this->assertEquals('Service not delivered', $invoice->metadata['dispute_reason']);
        $this->assertArrayHasKey('pre_dispute_status', $invoice->metadata);
        $this->assertEquals('submitted', $invoice->metadata['pre_dispute_status']);
    }

    /**
     * Disputed payment reversal: credit refund restores balance after chargeback.
     *
     * Full reconciliation scenario:
     *   1. Client has $100 credit.
     *   2. $100 credit applied to invoice → invoice paid, balance $0.
     *   3. Card issuer files chargeback → PaymentDisputed event marks invoice disputed.
     *   4. MSP reinstates credit → balance returns to $100.
     *   5. Ledger shows 3 entries with correct running balance.
     */
    public function test_disputed_payment_credit_refund_restores_balance(): void
    {
        // Step 1: seed $100 credit.
        $this->creditService->addCredit($this->client, 100.00, 'Initial credit');
        $this->assertEqualsWithDelta(100.00, $this->creditService->getBalance($this->client), 0.01);

        // Step 2: apply credit to invoice → invoice paid, balance $0.
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'status' => 'submitted',
            'total_amount' => 100.00,
        ]);
        $this->creditService->applyToInvoice($this->client, $invoice, 100.00);

        $invoice->refresh();
        $this->assertEquals(Invoice::STATUS_PAID, $invoice->status, 'Invoice should be paid after full credit application');
        $this->assertEqualsWithDelta(0.00, $this->creditService->getBalance($this->client), 0.01, 'Balance should be $0 after applying full credit');

        // Step 3 + 4: gateway chargeback → listener marks invoice as disputed.
        $disputeEvent = new PaymentDisputed(
            new PaymentDisputedData(
                paymentId: 99,
                invoiceId: $invoice->id,
                amount: 100.00,
                reason: 'Fraudulent transaction',
                disputeStatus: 'opened',
            )
        );
        $listener = app(PaymentDisputedListener::class);
        $listener->handle($disputeEvent);

        $invoice->refresh();
        $this->assertEquals(Invoice::STATUS_DISPUTED, $invoice->status, 'Invoice should be disputed after chargeback');
        $this->assertEquals('Fraudulent transaction', $invoice->metadata['last_dispute_reason']);

        // Step 5: MSP reinstates credit as part of dispute resolution.
        $this->creditService->addCredit(
            $this->client,
            100.00,
            'Credit reinstated: chargeback resolved for Invoice #'.$invoice->invoice_number,
            $invoice
        );

        // Step 6: balance reconciliation — back to $100.
        $finalBalance = $this->creditService->getBalance($this->client);
        $this->assertEqualsWithDelta(100.00, $finalBalance, 0.01, 'Final balance should be reinstated to $100');

        // Verify ledger audit trail: +100 initial, -100 applied, +100 reinstated.
        $ledger = ClientCreditLedger::where('client_id', $this->client->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(3, $ledger, 'Ledger must have exactly 3 entries: credit, debit, credit');
        $this->assertEquals(ClientCreditLedger::TYPE_CREDIT, $ledger[0]->transaction_type);
        $this->assertEquals(ClientCreditLedger::TYPE_DEBIT, $ledger[1]->transaction_type);
        $this->assertEquals(ClientCreditLedger::TYPE_CREDIT, $ledger[2]->transaction_type);

        // Running balance in cents: +100 → 10000, -100 → 0, +100 → 10000.
        $this->assertEquals(10000, $ledger[0]->balance_after_cents);
        $this->assertEquals(0, $ledger[1]->balance_after_cents);
        $this->assertEquals(10000, $ledger[2]->balance_after_cents);
    }

    /**
     * Over-credit application is rejected before touching the invoice.
     *
     * `applyToInvoice` must throw when the credit amount exceeds the invoice balance due,
     * leaving both the invoice and ledger unchanged.
     */
    public function test_cannot_apply_more_credit_than_invoice_balance_due(): void
    {
        $this->creditService->addCredit($this->client, 500.00, 'Excess credit');

        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'status' => 'submitted',
            'total_amount' => 100.00,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/exceed/i');

        $this->creditService->applyToInvoice($this->client, $invoice, 200.00);
    }
}
