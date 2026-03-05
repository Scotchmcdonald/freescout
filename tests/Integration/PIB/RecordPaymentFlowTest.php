<?php

use App\Models\User;
use Modules\PIB\Models\Invoice;
use Modules\Payment\Models\Payment;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;
use Illuminate\Support\Facades\Event;
use Modules\PIB\Events\InvoicePaid;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->client = Client::factory()->create(['company_id' => $this->company->id]);
    $this->admin = User::factory()->mspAdmin()->create();
});

describe('Record Payment – validation', function () {
    it('rejects payment with missing required fields', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'total_amount' => 500.00,
            'amount_paid' => 0,
            'status' => Invoice::STATUS_SUBMITTED,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.billing.invoices.payment', $invoice->id), []);

        $response->assertSessionHasErrors(['amount', 'payment_method', 'received_date']);
    });

    it('rejects payment exceeding outstanding balance', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'total_amount' => 200.00,
            'amount_paid' => 150.00,
            'status' => 'partially_paid',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.billing.invoices.payment', $invoice->id), [
                'amount' => 100.00, // only $50 outstanding
                'payment_method' => 'check',
                'received_date' => now()->format('Y-m-d'),
            ]);

        $response->assertSessionHasErrors('amount');
    });

    it('rejects payment with invalid payment method', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'total_amount' => 500.00,
            'amount_paid' => 0,
            'status' => Invoice::STATUS_SUBMITTED,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.billing.invoices.payment', $invoice->id), [
                'amount' => 100.00,
                'payment_method' => 'bitcoin', // not in allowed list
                'received_date' => now()->format('Y-m-d'),
            ]);

        $response->assertSessionHasErrors('payment_method');
    });

    it('rejects future dates for received_date', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'total_amount' => 500.00,
            'amount_paid' => 0,
            'status' => Invoice::STATUS_SUBMITTED,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.billing.invoices.payment', $invoice->id), [
                'amount' => 100.00,
                'payment_method' => 'check',
                'received_date' => now()->addDays(5)->format('Y-m-d'),
            ]);

        $response->assertSessionHasErrors('received_date');
    });
});

describe('Record Payment – partial payment flow', function () {
    it('records a partial payment and updates invoice status', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'total_amount' => 1000.00,
            'amount_paid' => 0,
            'status' => Invoice::STATUS_SUBMITTED,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.billing.invoices.payment', $invoice->id), [
                'amount' => 400.00,
                'payment_method' => 'check',
                'reference_number' => 'CHK-1234',
                'received_date' => now()->format('Y-m-d'),
                'admin_notes' => 'First partial payment',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $invoice->refresh();
        expect((float) $invoice->amount_paid)->toBe(400.00);
        expect($invoice->status)->toBe('partially_paid');

        // Verify Payment record was created
        $payment = Payment::where('invoice_id', $invoice->id)->first();
        expect($payment)->not->toBeNull();
        expect((float) $payment->amount)->toBe(400.00);
        expect($payment->is_manual)->toBeTrue();
        expect($payment->reference_number)->toBe('CHK-1234');
        expect($payment->payment_type)->toBe('check');
        expect($payment->status)->toBe('successful');
        expect($payment->admin_notes)->toBe('First partial payment');
        expect($payment->initiated_by_user_id)->toBe($this->admin->id);
    });

    it('records a second partial payment that fully pays the invoice', function () {
        Event::fake([InvoicePaid::class]);

        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'total_amount' => 500.00,
            'amount_paid' => 300.00,
            'status' => 'partially_paid',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.billing.invoices.payment', $invoice->id), [
                'amount' => 200.00,
                'payment_method' => 'wire',
                'reference_number' => 'WIRE-5678',
                'received_date' => now()->format('Y-m-d'),
            ]);

        $response->assertRedirect();

        $invoice->refresh();
        expect((float) $invoice->amount_paid)->toBe(500.00);
        expect($invoice->status)->toBe('paid');
        expect($invoice->paid_at)->not->toBeNull();

        Event::assertDispatched(InvoicePaid::class);
    });
});

describe('Record Payment – full payment flow', function () {
    it('marks invoice as fully paid in a single payment', function () {
        Event::fake([InvoicePaid::class]);

        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'total_amount' => 250.00,
            'amount_paid' => 0,
            'status' => Invoice::STATUS_SUBMITTED,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.billing.invoices.payment', $invoice->id), [
                'amount' => 250.00,
                'payment_method' => 'cash',
                'received_date' => now()->format('Y-m-d'),
            ]);

        $response->assertRedirect();

        $invoice->refresh();
        expect($invoice->status)->toBe('paid');
        expect($invoice->paid_at)->not->toBeNull();
        expect((float) $invoice->amount_paid)->toBe(250.00);

        Event::assertDispatched(InvoicePaid::class);
    });

    it('rejects payment against an already-paid invoice', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'total_amount' => 100.00,
            'amount_paid' => 100.00,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.billing.invoices.payment', $invoice->id), [
                'amount' => 50.00,
                'payment_method' => 'cash',
                'received_date' => now()->format('Y-m-d'),
            ]);

        $response->assertSessionHas('error', 'Invoice is already fully paid.');
        expect(Payment::where('invoice_id', $invoice->id)->count())->toBe(0);
    });
});

describe('Record Payment – audit trail', function () {
    it('stores metadata with recorder info', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'total_amount' => 300.00,
            'amount_paid' => 0,
            'status' => Invoice::STATUS_SUBMITTED,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.billing.invoices.payment', $invoice->id), [
                'amount' => 100.00,
                'payment_method' => 'ach',
                'received_date' => now()->format('Y-m-d'),
            ]);

        $payment = Payment::where('invoice_id', $invoice->id)->first();
        expect($payment->metadata)->toHaveKey('recorded_by');
        expect($payment->metadata)->toHaveKey('recorded_at');
        expect($payment->metadata['recorded_by'])->toBe($this->admin->name);
    });
});

describe('Record Payment – access control', function () {
    it('requires authentication', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'total_amount' => 500.00,
            'status' => Invoice::STATUS_SUBMITTED,
        ]);

        $response = $this->post(route('admin.billing.invoices.payment', $invoice->id), [
            'amount' => 100.00,
            'payment_method' => 'cash',
            'received_date' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect(); // redirects to login
    });
});

describe('Record Payment – isPayable guard', function () {
    it('blocks payment on a draft invoice', function () {
        $invoice = Invoice::factory()->create([
            'client_id'   => $this->client->id,
            'company_id'  => $this->company->id,
            'total_amount' => 300.00,
            'amount_paid' => 0,
            'status'      => Invoice::STATUS_DRAFT,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.billing.invoices.payment', $invoice->id), [
                'amount'         => 100.00,
                'payment_method' => 'cash',
                'received_date'  => now()->format('Y-m-d'),
            ]);

        $response->assertSessionHas('error');
        expect(Payment::where('invoice_id', $invoice->id)->count())->toBe(0);
        expect($invoice->fresh()->status)->toBe(Invoice::STATUS_DRAFT);
    });

    it('blocks payment on a finalized invoice', function () {
        $invoice = Invoice::factory()->create([
            'client_id'   => $this->client->id,
            'company_id'  => $this->company->id,
            'total_amount' => 200.00,
            'amount_paid' => 0,
            'status'      => Invoice::STATUS_FINALIZED,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.billing.invoices.payment', $invoice->id), [
                'amount'         => 100.00,
                'payment_method' => 'cash',
                'received_date'  => now()->format('Y-m-d'),
            ]);

        $response->assertSessionHas('error');
        expect(Payment::where('invoice_id', $invoice->id)->count())->toBe(0);
    });

    it('allows payment on a disputed invoice', function () {
        $invoice = Invoice::factory()->create([
            'client_id'   => $this->client->id,
            'company_id'  => $this->company->id,
            'total_amount' => 500.00,
            'amount_paid' => 0,
            'status'      => Invoice::STATUS_DISPUTED,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.billing.invoices.payment', $invoice->id), [
                'amount'         => 500.00,
                'payment_method' => 'wire',
                'received_date'  => now()->format('Y-m-d'),
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        expect($invoice->fresh()->status)->toBe(Invoice::STATUS_PAID);
    });

    it('allows payment on an overdue invoice', function () {
        $invoice = Invoice::factory()->create([
            'client_id'   => $this->client->id,
            'company_id'  => $this->company->id,
            'total_amount' => 400.00,
            'amount_paid' => 0,
            'status'      => Invoice::STATUS_OVERDUE,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.billing.invoices.payment', $invoice->id), [
                'amount'         => 400.00,
                'payment_method' => 'ach',
                'received_date'  => now()->format('Y-m-d'),
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        expect($invoice->fresh()->status)->toBe(Invoice::STATUS_PAID);
    });
});

describe('Record Payment page', function () {
    it('displays the record payment form with invoice summary', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'total_amount' => 750.00,
            'amount_paid' => 250.00,
            'status' => 'partially_paid',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.billing.invoices.record-payment', $invoice->id));

        $response->assertOk();
        $response->assertSee('Record Payment');
        $response->assertSee('$750.00');  // total amount
        $response->assertSee('$250.00');  // amount paid
        $response->assertSee('$500.00');  // outstanding balance
    });
});
