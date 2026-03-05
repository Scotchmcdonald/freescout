<?php

use Modules\Payment\Models\Payment;
use Modules\Crm\Models\Company;

beforeEach(function () {
    $this->company = Company::factory()->create();
});

describe('Payment manual fields', function () {
    it('stores manual payment with reference number and notes', function () {
        $payment = Payment::factory()->create([
            'company_id' => $this->company->id,
            'is_manual' => true,
            'reference_number' => 'CHK-9876',
            'admin_notes' => 'Received via mail on Monday',
            'received_date' => '2025-01-15',
            'payment_type' => 'check',
        ]);

        $fresh = $payment->fresh();
        expect($fresh->is_manual)->toBeTrue();
        expect($fresh->reference_number)->toBe('CHK-9876');
        expect($fresh->admin_notes)->toBe('Received via mail on Monday');
        expect($fresh->received_date->format('Y-m-d'))->toBe('2025-01-15');
        expect($fresh->payment_type)->toBe('check');
    });

    it('casts is_manual to boolean', function () {
        $payment = Payment::factory()->create([
            'company_id' => $this->company->id,
            'is_manual' => 1,
        ]);

        expect($payment->fresh()->is_manual)->toBeTrue();

        $payment2 = Payment::factory()->create([
            'company_id' => $this->company->id,
            'is_manual' => 0,
        ]);

        expect($payment2->fresh()->is_manual)->toBeFalse();
    });

    it('casts received_date to date', function () {
        $payment = Payment::factory()->create([
            'company_id' => $this->company->id,
            'received_date' => '2025-03-01',
        ]);

        $fresh = $payment->fresh();
        expect($fresh->received_date)->toBeInstanceOf(\Carbon\Carbon::class);
        expect($fresh->received_date->format('Y-m-d'))->toBe('2025-03-01');
    });

    it('allows null reference_number and admin_notes', function () {
        $payment = Payment::factory()->create([
            'company_id' => $this->company->id,
            'reference_number' => null,
            'admin_notes' => null,
        ]);

        $fresh = $payment->fresh();
        expect($fresh->reference_number)->toBeNull();
        expect($fresh->admin_notes)->toBeNull();
    });
});

describe('Payment relationships', function () {
    it('belongs to an invoice', function () {
        $invoice = \Modules\PIB\Models\Invoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => \Modules\Crm\Models\Client::factory()->create(['company_id' => $this->company->id])->id,
        ]);

        $payment = Payment::factory()->create([
            'company_id' => $this->company->id,
            'invoice_id' => $invoice->id,
        ]);

        expect($payment->invoice)->not->toBeNull();
        expect($payment->invoice->id)->toBe($invoice->id);
    });

    it('belongs to an initiatedBy user', function () {
        $user = \App\Models\User::factory()->create();

        $payment = Payment::factory()->create([
            'company_id' => $this->company->id,
            'initiated_by_user_id' => $user->id,
        ]);

        expect($payment->initiatedBy)->not->toBeNull();
        expect($payment->initiatedBy->id)->toBe($user->id);
    });
});

describe('Payment factory states', function () {
    it('creates successful payment by default', function () {
        $payment = Payment::factory()->create([
            'company_id' => $this->company->id,
        ]);

        expect($payment->status)->toBe('successful');
    });

    it('creates failed payment with failed state', function () {
        $payment = Payment::factory()->failed()->create([
            'company_id' => $this->company->id,
        ]);

        expect($payment->status)->toBe('failed');
    });

    it('creates pending payment with pending state', function () {
        $payment = Payment::factory()->pending()->create([
            'company_id' => $this->company->id,
        ]);

        expect($payment->status)->toBe('pending');
    });
});
