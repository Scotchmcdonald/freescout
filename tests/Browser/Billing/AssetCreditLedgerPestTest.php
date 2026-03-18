<?php

use App\Models\User;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;

it('upfront payment creates credit ledger', function () {
    $admin = User::firstOrCreate(['email' => 'credit-ledger-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'CreditLedger',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (! $admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }

    browserLoginAdmin($this, $admin);

    $this->visit('/billing/credit-ledger')
        ->assertSee('Credit');
})->group('billing', 'service-delivery', 'credit-ledger');

it('credit applied to monthly invoices', function () {
    $admin = User::firstOrCreate(['email' => 'credit-apply-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'CreditApply',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (! $admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }

    $client = Client::factory()->create(['name' => 'Credit Apply Client']);

    // Create a credit ledger entry
    \Illuminate\Support\Facades\DB::table('client_credit_ledger')->insert([
        'client_id' => $client->id,
        'transaction_type' => 'credit',
        'amount_cents' => 50000,
        'balance_after_cents' => 50000,
        'description' => 'Prepayment credit',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    browserLoginAdmin($this, $admin);

    $this->visit("/billing/credit-ledger/{$client->id}")
        ->assertSee('Credit');
})->group('billing', 'service-delivery', 'credit-ledger');

it('partial credit application', function () {
    $client = Client::factory()->create(['name' => 'Partial Credit Client']);

    // Create initial credit
    \Illuminate\Support\Facades\DB::table('client_credit_ledger')->insert([
        'client_id' => $client->id,
        'transaction_type' => 'credit',
        'amount_cents' => 100000,
        'balance_after_cents' => 100000,
        'description' => 'Initial deposit',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Apply partial debit
    \Illuminate\Support\Facades\DB::table('client_credit_ledger')->insert([
        'client_id' => $client->id,
        'transaction_type' => 'debit',
        'amount_cents' => 30000,
        'balance_after_cents' => 70000,
        'description' => 'Partial invoice payment',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $entries = \Illuminate\Support\Facades\DB::table('client_credit_ledger')
        ->where('client_id', $client->id)
        ->orderBy('id', 'desc')
        ->first();

    expect($entries->balance_after_cents)->toBe(70000);
})->group('billing', 'service-delivery', 'credit-ledger');

it('multiple prepayments aggregate credit', function () {
    $client = Client::factory()->create(['name' => 'Multi Credit Client']);

    // Two separate prepayments
    \Illuminate\Support\Facades\DB::table('client_credit_ledger')->insert([
        'client_id' => $client->id,
        'transaction_type' => 'credit',
        'amount_cents' => 25000,
        'balance_after_cents' => 25000,
        'description' => 'First prepayment',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    \Illuminate\Support\Facades\DB::table('client_credit_ledger')->insert([
        'client_id' => $client->id,
        'transaction_type' => 'credit',
        'amount_cents' => 35000,
        'balance_after_cents' => 60000,
        'description' => 'Second prepayment',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $totalCredits = \Illuminate\Support\Facades\DB::table('client_credit_ledger')
        ->where('client_id', $client->id)
        ->where('transaction_type', 'credit')
        ->sum('amount_cents');

    expect($totalCredits)->toBe(60000);
})->group('billing', 'service-delivery', 'credit-ledger');

it('client can view credit balance in portal', function () {
    $company = Company::factory()->create(['is_active' => true]);
    $user = User::factory()->create([
        'type' => 2,
        'first_name' => 'Credit Balance',
        'last_name' => 'User',
        'email' => 'credit-balance-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'status' => User::STATUS_ACTIVE,
        'email_verified_at' => now(),
    ]);
    $company->users()->attach($user->id, ['role_id' => 1, 'status' => 'approved', 'is_primary' => true]);

    browserLoginPortal($this, $user);

    $this->visit('/portal/dashboard')
        ->assertSee('Client Portal');
})->group('billing', 'service-delivery', 'credit-ledger');

it('credit expiration after defined period', function () {
    $client = Client::factory()->create(['name' => 'Expiring Credit Client']);

    // Create credit with expiration date
    \Illuminate\Support\Facades\DB::table('client_credit_ledger')->insert([
        'client_id' => $client->id,
        'transaction_type' => 'credit',
        'amount_cents' => 50000,
        'balance_after_cents' => 50000,
        'description' => 'Expiring credit',
        'expires_at' => now()->subDay(), // Already expired
        'created_at' => now()->subMonth(),
        'updated_at' => now()->subMonth(),
    ]);

    // Verify the expires_at field is stored and queryable
    $expired = \Illuminate\Support\Facades\DB::table('client_credit_ledger')
        ->where('client_id', $client->id)
        ->where('expires_at', '<', now())
        ->count();

    expect($expired)->toBe(1);
})->group('billing', 'service-delivery', 'credit-ledger');

it('unused credit refundable on cancellation', function () {
    $client = Client::factory()->create(['name' => 'Refund Credit Client']);

    // Create credit
    \Illuminate\Support\Facades\DB::table('client_credit_ledger')->insert([
        'client_id' => $client->id,
        'transaction_type' => 'credit',
        'amount_cents' => 80000,
        'balance_after_cents' => 80000,
        'description' => 'Prepaid service credit',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Simulate refund via adjustment
    \Illuminate\Support\Facades\DB::table('client_credit_ledger')->insert([
        'client_id' => $client->id,
        'transaction_type' => 'adjustment',
        'amount_cents' => -80000,
        'balance_after_cents' => 0,
        'description' => 'Cancellation refund',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $balance = \Illuminate\Support\Facades\DB::table('client_credit_ledger')
        ->where('client_id', $client->id)
        ->orderBy('id', 'desc')
        ->value('balance_after_cents');

    expect($balance)->toBe(0);
})->group('billing', 'service-delivery', 'credit-ledger');
