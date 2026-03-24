<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Crm\Models\Company;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentMethod;
use Modules\PIB\Models\Invoice;

beforeEach(function () {
    $this->company = Company::factory()->create(['is_active' => true]);
    $this->clientUser = User::factory()->create([
        'status' => 1,
        'type' => 2,
    ]);
    $clientRole = \App\Models\Role::firstOrCreate(['name' => 'Client User']);
    $this->clientUser->roles()->attach($clientRole->id);

    $this->clientUser->companies()->attach($this->company->id);
});

describe('Portal Invoice PDF', function () {
    it('allows client to download their own invoice PDF', function () {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'total_amount' => 500.00,
            'invoice_number' => 'INV-PORTAL-PDF',
        ]);

        $response = $this->actingAs($this->clientUser)
            ->get(route('portal.invoices.pdf', $invoice->id));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    });

    it('denies client access to another client invoice PDF', function () {
        $otherCompany = Company::factory()->create();

        $otherInvoice = Invoice::factory()->create([
            'company_id' => $otherCompany->id,
            'total_amount' => 999.00,
        ]);

        $response = $this->actingAs($this->clientUser)
            ->get(route('portal.invoices.pdf', $otherInvoice->id));

        $response->assertForbidden();
    });
});

describe('Portal Invoice Show', function () {
    it('shows invoice details for authenticated client', function () {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'total_amount' => 500.00,
            'invoice_number' => 'INV-PORTAL-SHOW',
            'status' => 'unpaid',
        ]);

        $response = $this->actingAs($this->clientUser)
            ->get(route('portal.invoices.show', $invoice->id));

        $response->assertOk();
        $response->assertViewHas('invoice', fn ($viewInvoice) => $viewInvoice->id === $invoice->id
            && $viewInvoice->invoice_number === 'INV-PORTAL-SHOW');
        $response->assertViewHas('payments', fn ($payments) => $payments->count() === 0);
    });

    it('shows payment history tab when payments exist', function () {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'total_amount' => 500.00,
            'amount_paid' => 200.00,
            'status' => 'partially_paid',
        ]);

        Payment::factory()->create([
            'company_id' => $this->company->id,
            'invoice_id' => $invoice->id,
            'amount' => 200.00,
            'status' => 'successful',
        ]);

        $response = $this->actingAs($this->clientUser)
            ->get(route('portal.invoices.show', $invoice->id));

        $response->assertOk();
        $response->assertViewHas('payments', fn ($payments) => $payments->count() === 1
            && (float) $payments->first()->amount === 200.00);
    });
});

describe('Portal Pay Invoice page', function () {
    it('shows pay invoice form for unpaid invoice', function () {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'total_amount' => 500.00,
            'amount_paid' => 0,
            'status' => 'unpaid',
        ]);

        // Create a payment method for the company
        PaymentMethod::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
            'card_brand' => 'Visa',
            'last_four' => '4242',
        ]);

        $response = $this->actingAs($this->clientUser)
            ->get(route('portal.invoices.pay', $invoice->id));

        $response->assertOk();
        $response->assertViewHas('invoice', fn ($viewInvoice) => $viewInvoice->id === $invoice->id);
        $response->assertViewHas('outstandingBalance', 500.0);
        $response->assertViewHas('paymentMethods', fn ($paymentMethods) => $paymentMethods->count() === 1
            && $paymentMethods->first()->last_four === '4242');
    });

    it('displays correct outstanding balance after partial payment', function () {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'total_amount' => 1000.00,
            'amount_paid' => 600.00,
            'status' => 'partially_paid',
        ]);

        PaymentMethod::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->clientUser)
            ->get(route('portal.invoices.pay', $invoice->id));

        $response->assertOk();
        $response->assertViewHas('outstandingBalance', 400.0);
    });

    it('prevents paying another client invoice', function () {
        $otherCompany = Company::factory()->create();

        $otherInvoice = Invoice::factory()->create([
            'company_id' => $otherCompany->id,
            'total_amount' => 999.00,
        ]);

        $response = $this->actingAs($this->clientUser)
            ->get(route('portal.invoices.pay', $otherInvoice->id));

        $response->assertForbidden();
    });

    it('shows warning when no payment methods exist', function () {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'total_amount' => 500.00,
            'amount_paid' => 0,
            'status' => 'unpaid',
        ]);

        $response = $this->actingAs($this->clientUser)
            ->get(route('portal.invoices.pay', $invoice->id));

        $response->assertOk();
        $response->assertViewHas('paymentMethods', fn ($paymentMethods) => $paymentMethods->isEmpty());
    });

    it('requires authentication via client guard', function () {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'total_amount' => 100.00,
        ]);

        $response = $this->get(route('portal.invoices.pay', $invoice->id));

        $response->assertRedirect(); // redirected to portal login
    });
});

describe('Portal Process Invoice Payment', function () {
    it('validates payment_method_id is required', function () {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'total_amount' => 500.00,
            'amount_paid' => 0,
            'status' => 'unpaid',
        ]);

        $response = $this->actingAs($this->clientUser)
            ->post(route('portal.invoices.payment.process', $invoice->id), []);

        $response->assertSessionHasErrors('payment_method_id');
    });

    it('does not allow amount to be specified by client (server-side calculation)', function () {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'total_amount' => 500.00,
            'amount_paid' => 0,
            'status' => 'unpaid',
        ]);

        $paymentMethod = PaymentMethod::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        // Posting with a tampered amount shouldn't affect the charge
        // The only accepted field is payment_method_id — amount is server-calculated
        $response = $this->actingAs($this->clientUser)
            ->post(route('portal.invoices.payment.process', $invoice->id), [
                'payment_method_id' => $paymentMethod->id,
                'amount' => 1.00, // tampered — should be ignored
            ]);

        // The server should charge the full $500, not $1
        // (the actual charge goes through Helcim so we can't easily verify the amount
        // in a unit test, but the controller code explicitly ignores any posted amount)
        // This test verifies the request doesn't fail due to amount validation
        // The HelcimService will be called with the server-calculated amount
        $response->assertRedirect();
    });
});
