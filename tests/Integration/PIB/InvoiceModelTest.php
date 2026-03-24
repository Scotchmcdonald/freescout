<?php

declare(strict_types=1);

use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;
use Modules\Payment\Models\Payment;
use Modules\PIB\Models\Invoice;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->client = Client::factory()->create(['company_id' => $this->company->id]);
});

describe('Invoice outstanding balance', function () {
    it('returns the full total when no payments have been made', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'total_amount' => 500.00,
            'amount_paid' => 0,
        ]);

        expect($invoice->outstanding_balance)->toBe(500.00);
    });

    it('returns zero when invoice is fully paid', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'total_amount' => 250.00,
            'amount_paid' => 250.00,
            'status' => 'paid',
        ]);

        expect($invoice->outstanding_balance)->toBe(0.0);
    });

    it('returns the correct remaining balance after partial payment', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'total_amount' => 1000.00,
            'amount_paid' => 350.00,
        ]);

        expect($invoice->outstanding_balance)->toBe(650.00);
    });

    it('never returns a negative outstanding balance', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'total_amount' => 100.00,
            'amount_paid' => 150.00, // overpayment edge case
        ]);

        expect($invoice->outstanding_balance)->toBeGreaterThanOrEqual(0);
    });
});

describe('Invoice payments relationship', function () {
    it('returns associated payments', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'total_amount' => 500.00,
        ]);

        Payment::factory()->create([
            'company_id' => $this->company->id,
            'invoice_id' => $invoice->id,
            'amount' => 200.00,
            'status' => 'successful',
        ]);

        Payment::factory()->create([
            'company_id' => $this->company->id,
            'invoice_id' => $invoice->id,
            'amount' => 100.00,
            'status' => 'successful',
        ]);

        expect($invoice->payments)->toHaveCount(2);
        expect($invoice->payments->sum('amount'))->toBe(300.00);
    });

    it('does not return payments for other invoices', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
        ]);

        $otherInvoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
        ]);

        Payment::factory()->create([
            'company_id' => $this->company->id,
            'invoice_id' => $otherInvoice->id,
            'amount' => 999.00,
        ]);

        expect($invoice->payments)->toHaveCount(0);
    });
});

describe('Invoice amount_paid field', function () {
    it('is cast to decimal:2 string', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
            'amount_paid' => '123.45',
        ]);

        $fresh = $invoice->fresh();
        // decimal:2 cast returns a string with 2 decimal places
        expect($fresh->amount_paid)->toBe('123.45');
        expect((float) $fresh->amount_paid)->toBe(123.45);
    });

    it('defaults to zero for new invoices', function () {
        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
        ]);

        expect((float) $invoice->amount_paid)->toBe(0.0);
    });
});
