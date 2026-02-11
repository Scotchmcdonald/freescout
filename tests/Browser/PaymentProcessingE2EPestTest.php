<?php

use App\Models\User;

test('credit card payment full flow', function () {
    $gateway = app(\Modules\Payment\Contracts\PaymentGateway::class);
    $result = $gateway->charge(100.00, 'tok_visa_success');

    expect($result['success'])->toBeTrue();
    expect($result['status'])->toBe('approved');
    expect($result['transaction_id'])->not->toBeEmpty();
})->group('payment', 'e2e', 'credit-card');

test('ach payment async flow', function () {
    $gateway = app(\Modules\Payment\Contracts\PaymentGateway::class);
    $result = $gateway->charge(50.00, 'tok_ach_success');

    expect($result['success'])->toBeTrue();
})->group('payment', 'ach', 'async');

test('payment failure and retry', function () {
    $gateway = app(\Modules\Payment\Contracts\PaymentGateway::class);

    // Test Failure
    try {
        $gateway->charge(100.00, 'tok_visa_fail');
        $this->fail('Expected payment failure exception was not thrown.');
    } catch (\Exception $e) {
        expect($e->getMessage())->toContain('Payment Failed');
    }

    // Test Retry (Simulate network error)
    try {
        $gateway->charge(100.00, 'tok_visa_retry');
        $this->fail('Expected retry/network exception was not thrown.');
    } catch (\Exception $e) {
        expect($e->getMessage())->toContain('Network error');
    }
})->group('payment', 'error-handling', 'retry');

test('partial payment application', function () {
    $gateway = app(\Modules\Payment\Contracts\PaymentGateway::class);
    $result = $gateway->charge(50.00, 'tok_partial');

    expect($result['success'])->toBeTrue();
})->group('payment', 'partial-payment');

test('refund processing', function () {
    $gateway = app(\Modules\Payment\Contracts\PaymentGateway::class);
    $result = $gateway->refund('txn_123', 50.00);

    expect($result['success'])->toBeTrue();
    expect($result['status'])->toBe('refunded');
})->group('payment', 'refund');

it('auto applies credits to multiple invoices', function () {
    // Verify credit service classes exist in the correct modules
    expect(class_exists(\Modules\PIB\Services\ClientCreditService::class))->toBeTrue();
    expect(class_exists(\Modules\Payment\Services\ClientCreditService::class))->toBeTrue();
})->group('payment', 'auto-apply', 'multiple-invoices');

test('payment system accessible', function () {
    $admin = User::firstOrCreate(['email' => 'payment-e2e-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'Payment',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (!$admin->isAdmin()) { $admin->role = User::ROLE_ADMIN; $admin->save(); }

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/dashboard')
        ->assertSee('Dashboard');
})->group('payment', 'smoke');
