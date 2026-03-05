<?php

use App\Models\User;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;
use Modules\PIB\Models\Invoice;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->client  = Client::factory()->create(['company_id' => $this->company->id]);
    $this->admin   = User::factory()->mspAdmin()->create();
});

// ── Valid transitions ─────────────────────────────────────────────────────────

describe('Invoice transitions – happy path', function () {
    it('finalizes a draft invoice', function () {
        $invoice = Invoice::factory()->create([
            'client_id'  => $this->client->id,
            'company_id' => $this->company->id,
            'status'     => Invoice::STATUS_DRAFT,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.billing.invoices.transition', $invoice->id), [
                'transition' => Invoice::STATUS_FINALIZED,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        expect($invoice->fresh()->status)->toBe(Invoice::STATUS_FINALIZED);
    });

    it('submits a finalized invoice to client', function () {
        $invoice = Invoice::factory()->create([
            'client_id'  => $this->client->id,
            'company_id' => $this->company->id,
            'status'     => Invoice::STATUS_FINALIZED,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.billing.invoices.transition', $invoice->id), [
                'transition' => Invoice::STATUS_SUBMITTED,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        expect($invoice->fresh()->status)->toBe(Invoice::STATUS_SUBMITTED);
    });

    it('marks a submitted invoice as overdue', function () {
        $invoice = Invoice::factory()->create([
            'client_id'  => $this->client->id,
            'company_id' => $this->company->id,
            'status'     => Invoice::STATUS_SUBMITTED,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.billing.invoices.transition', $invoice->id), [
                'transition' => Invoice::STATUS_OVERDUE,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        expect($invoice->fresh()->status)->toBe(Invoice::STATUS_OVERDUE);
    });

    it('re-submits a disputed invoice to client', function () {
        $invoice = Invoice::factory()->create([
            'client_id'  => $this->client->id,
            'company_id' => $this->company->id,
            'status'     => Invoice::STATUS_DISPUTED,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.billing.invoices.transition', $invoice->id), [
                'transition' => Invoice::STATUS_SUBMITTED,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        expect($invoice->fresh()->status)->toBe(Invoice::STATUS_SUBMITTED);
    });
});

// ── Dispute metadata ──────────────────────────────────────────────────────────

describe('Invoice transitions – dispute metadata', function () {
    it('stores dispute_reason and dispute_initiated_at when marking as disputed', function () {
        $invoice = Invoice::factory()->create([
            'client_id'  => $this->client->id,
            'company_id' => $this->company->id,
            'status'     => Invoice::STATUS_SUBMITTED,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.billing.invoices.transition', $invoice->id), [
                'transition' => Invoice::STATUS_DISPUTED,
                'notes'      => 'Client says hours are incorrect.',
            ]);

        $meta = $invoice->fresh()->metadata;
        expect($meta)->toHaveKey('dispute_reason');
        expect($meta)->toHaveKey('dispute_initiated_at');
        expect($meta)->toHaveKey('dispute_initiated_by');
        expect($meta)->toHaveKey('pre_dispute_status');
        expect($meta['dispute_reason'])->toBe('Client says hours are incorrect.');
        expect($meta['pre_dispute_status'])->toBe(Invoice::STATUS_SUBMITTED);
    });

    it('stores dispute_initiated_at even without a notes value', function () {
        $invoice = Invoice::factory()->create([
            'client_id'  => $this->client->id,
            'company_id' => $this->company->id,
            'status'     => Invoice::STATUS_OVERDUE,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.billing.invoices.transition', $invoice->id), [
                'transition' => Invoice::STATUS_DISPUTED,
                // no notes intentionally
            ]);

        $meta = $invoice->fresh()->metadata;
        expect($meta)->toHaveKey('dispute_initiated_at');
        expect($meta['dispute_reason'])->toBe('');
    });

    it('stores notes in metadata[notes] for non-dispute transitions', function () {
        $invoice = Invoice::factory()->create([
            'client_id'  => $this->client->id,
            'company_id' => $this->company->id,
            'status'     => Invoice::STATUS_SUBMITTED,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.billing.invoices.transition', $invoice->id), [
                'transition' => Invoice::STATUS_FINALIZED,
                'notes'      => 'Recalled to fix line item 3.',
            ]);

        $meta = $invoice->fresh()->metadata;
        expect($meta)->toHaveKey('notes');
        expect($meta['notes'])->toBe('Recalled to fix line item 3.');
        expect($meta)->not->toHaveKey('dispute_reason');
    });
});

// ── Invalid transitions ───────────────────────────────────────────────────────

describe('Invoice transitions – invalid transitions', function () {
    it('rejects a transition not in the allowed map', function () {
        $invoice = Invoice::factory()->create([
            'client_id'  => $this->client->id,
            'company_id' => $this->company->id,
            'status'     => Invoice::STATUS_DRAFT,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.billing.invoices.transition', $invoice->id), [
                'transition' => Invoice::STATUS_PAID, // draft cannot go directly to paid
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        expect($invoice->fresh()->status)->toBe(Invoice::STATUS_DRAFT);
    });

    it('rejects transition on a paid invoice', function () {
        $invoice = Invoice::factory()->create([
            'client_id'  => $this->client->id,
            'company_id' => $this->company->id,
            'status'     => Invoice::STATUS_PAID,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.billing.invoices.transition', $invoice->id), [
                'transition' => Invoice::STATUS_DISPUTED,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        expect($invoice->fresh()->status)->toBe(Invoice::STATUS_PAID);
    });

    it('requires authentication', function () {
        $invoice = Invoice::factory()->create([
            'client_id'  => $this->client->id,
            'company_id' => $this->company->id,
            'status'     => Invoice::STATUS_DRAFT,
        ]);

        $this->post(route('admin.billing.invoices.transition', $invoice->id), [
            'transition' => Invoice::STATUS_FINALIZED,
        ])->assertRedirect(); // redirects to login

        expect($invoice->fresh()->status)->toBe(Invoice::STATUS_DRAFT);
    });
});
