<?php

declare(strict_types=1);

use App\Models\User;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;
use Modules\Payment\Models\Payment;
use Modules\PIB\Models\Invoice;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->client = Client::factory()->create(['company_id' => $this->company->id]);
    $this->admin = User::factory()->mspAdmin()->create();
});

describe('Invoice PDF generation', function () {
    it('generates a downloadable PDF for an existing invoice', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'total_amount' => 500.00,
            'invoice_number' => 'INV-PDF-TEST',
            'status' => 'unpaid',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.billing.invoices.pdf', $invoice->id));

        $response->assertOk();
        // DomPDF returns content-disposition attachment header
        expect($response->headers->get('content-disposition'))
            ->toContain('invoice-INV-PDF-TEST.pdf');
    });

    it('includes payment records in PDF when invoice is paid', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'total_amount' => 300.00,
            'amount_paid' => 300.00,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        Payment::factory()->create([
            'company_id' => $this->company->id,
            'invoice_id' => $invoice->id,
            'amount' => 300.00,
            'status' => 'successful',
            'payment_type' => 'check',
            'reference_number' => 'CHK-PDF-TEST',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.billing.invoices.pdf', $invoice->id));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    });

    it('returns 404 for non-existent invoice', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.billing.invoices.pdf', 99999));

        $response->assertNotFound();
    });

    it('requires authentication to download PDF', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'total_amount' => 100.00,
        ]);

        $response = $this->get(route('admin.billing.invoices.pdf', $invoice->id));
        $response->assertRedirect(); // redirect to login
    });
});

describe('Invoice show page', function () {
    it('displays invoice details and payment history', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'total_amount' => 500.00,
            'amount_paid' => 200.00,
            'invoice_number' => 'INV-SHOW-TEST',
            'status' => 'partially_paid',
        ]);

        Payment::factory()->create([
            'company_id' => $this->company->id,
            'invoice_id' => $invoice->id,
            'amount' => 200.00,
            'status' => 'successful',
            'payment_type' => 'check',
            'is_manual' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.billing.invoices.show', $invoice->id));

        $response->assertOk();
        $response->assertViewHas('invoice', fn ($viewInvoice) => $viewInvoice->id === $invoice->id
            && $viewInvoice->invoice_number === 'INV-SHOW-TEST'
            && (float) $viewInvoice->total_amount === 500.00
            && (float) $viewInvoice->amount_paid === 200.00
            && $viewInvoice->isPayable());
        $response->assertViewHas('payments', fn ($payments) => $payments->count() === 1
            && (float) $payments->first()->amount === 200.00);
    });

    it('shows record payment button for unpaid invoices', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'total_amount' => 100.00,
            'amount_paid' => 0,
            'status' => 'submitted', // submitted is a valid payable status
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.billing.invoices.show', $invoice->id));

        $response->assertOk();
        $response->assertViewHas('invoice', fn ($viewInvoice) => $viewInvoice->id === $invoice->id
            && $viewInvoice->isPayable());
    });

    it('hides record payment button for paid invoices', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'total_amount' => 100.00,
            'amount_paid' => 100.00,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.billing.invoices.show', $invoice->id));

        $response->assertOk();
        $response->assertViewHas('invoice', fn ($viewInvoice) => $viewInvoice->id === $invoice->id
            && ! $viewInvoice->isPayable());
    });
});
