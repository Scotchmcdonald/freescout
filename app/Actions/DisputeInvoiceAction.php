<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Modules\Payment\DataTransferObjects\PaymentDisputedData;
use Modules\Payment\Events\PaymentDisputed;
use Modules\PIB\Models\BillingAdjustment;
use Modules\PIB\Models\Invoice;

/**
 * DisputeInvoiceAction
 *
 * Encapsulates the full invoice dispute state machine transition:
 *
 *   published | pending | sent  →  disputed
 *
 * Side effects (all within a single DB transaction):
 *   1. Invoice.status transitions to 'disputed'
 *   2. Invoice.metadata records dispute provenance
 *   3. A BillingAdjustment of type 'dispute' is created
 *   4. PaymentDisputed event is fired — halts any pending auto-pay job for this invoice
 *
 * Authorization:
 *   Enforces InvoicePolicy::dispute() which restricts to active client users owning the invoice.
 *   Internal callers (admin via admin panel) may bypass by passing $bypassPolicy = true.
 *
 * Graph edge covered: ACT_04 (xInvoices → InvoiceManager)
 *
 * Usage:
 *   app(DisputeInvoiceAction::class)->execute($invoice, $clientUser, 'Charge does not match contract.');
 */
class DisputeInvoiceAction
{
    /** Statuses from which a dispute transition is valid. */
    private const DISPUTABLE_STATUSES = ['submitted', 'overdue', 'partially_paid'];

    /**
     * Execute the invoice dispute workflow.
     *
     * @param  Invoice  $invoice  The invoice to dispute
     * @param  object  $disputedBy  User initiating the dispute (internal or external)
     * @param  string  $reason  Client-provided reason for the dispute
     * @param  bool  $bypassPolicy  Set true for admin-initiated disputes that skip gate check
     * @return Invoice The updated invoice (status = 'disputed')
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException If policy denies the action
     * @throws \LogicException If invoice is not in a disputable state
     */
    public function execute(
        Invoice $invoice,
        object $disputedBy,
        string $reason = '',
        bool $bypassPolicy = false
    ): Invoice {
        if (! $bypassPolicy) {
            Gate::forUser($disputedBy)->authorize('dispute', $invoice);
        }

        if (! in_array($invoice->status, self::DISPUTABLE_STATUSES, true)) {
            throw new \LogicException(
                "Invoice #{$invoice->invoice_number} cannot be disputed from status '{$invoice->status}'. ".
                'Only '.implode(', ', self::DISPUTABLE_STATUSES).' invoices may be disputed.'
            );
        }

        return DB::transaction(function () use ($invoice, $disputedBy, $reason): Invoice {
            // ── 1. Transition invoice status ───────────────────────────────────
            $invoice->status = 'disputed';
            $invoice->metadata = array_merge($invoice->metadata ?? [], [
                'disputed' => true,
                'dispute_reason' => $reason,
                'dispute_initiated_at' => now()->toIso8601String(),
                'dispute_initiated_by' => $this->resolveActorIdentifier($disputedBy),
                'pre_dispute_status' => $invoice->getOriginal('status'),
            ]);
            $invoice->save();

            // ── 2. Create BillingAdjustment for audit trail ───────────────────
            BillingAdjustment::create([
                'client_id' => $invoice->client_id,
                'adjustment_type' => 'dispute',
                'effective_date' => now()->toDateString(),
                'old_value' => $invoice->total_amount,
                'new_value' => $invoice->total_amount, // Amount unchanged until resolution
                'justification' => $reason ?: 'Client-initiated dispute via portal',
                'status' => 'pending',
                'created_by' => $disputedBy->id ?? 0,
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'initiated_by' => $this->resolveActorIdentifier($disputedBy),
                ],
            ]);

            // ── 3. Fire PaymentDisputed event (halts auto-pay) ─────────────────
            event(new PaymentDisputed(
                new PaymentDisputedData(
                    paymentId: 0,  // No payment yet — disputed at invoice stage
                    invoiceId: $invoice->id,
                    amount: (float) $invoice->total_amount,
                    reason: $reason,
                    disputeStatus: 'opened',
                )
            ));

            Log::info('DisputeInvoiceAction: invoice disputed', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'client_id' => $invoice->client_id,
                'disputed_by' => $this->resolveActorIdentifier($disputedBy),
                'reason' => $reason,
            ]);

            return $invoice;
        });
    }

    /**
     * Extract a human-readable identifier from the actor object.
     * Supports User model (both portal and admin users).
     */
    private function resolveActorIdentifier(object $actor): string
    {
        if (property_exists($actor, 'email') && is_string($actor->email)) {
            return $actor->email;
        }
        if (property_exists($actor, 'id')) {
            return get_class($actor).'#'.$actor->id;
        }

        return get_class($actor);
    }
}
