<?php

use App\Models\User;
use Modules\Crm\Models\Client;
use Modules\PIB\Models\ClientCredit;

function getPIBAdmin(): User
{
    $admin = User::firstOrCreate(['email' => 'pib-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'PIB',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);

    if (! $admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }

    return $admin;
}

it('manages service usage collection', function () {
    $admin = getPIBAdmin();
    $client = Client::factory()->create([
        'name' => 'Service Usage Client '.uniqid(),
    ]);

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]')
        ->assertPathIs('/dashboard');

    // 1. Visit Service Usage Index
    $this->visit(route('admin.billing.service-usage.index'))
        ->assertPathIs('/billing/service-usage');

    // 2. Create Service Usage Entry
    $this->visit(route('admin.billing.service-usage.create'))
        ->assertPathIs('/billing/service-usage/create')
        ->select('client_id', (string) $client->id)
        ->select('service_type', 'Labor')
        ->type('description', 'Emergency Server Repair')
        ->type('service_date', date('Y-m-d'))
        ->type('hours', '2.5')
        ->type('hourly_rate', '150')
        ->press('Save Entry');

    $entry = \Modules\PIB\Models\ServiceUsage::where('client_id', $client->id)->latest()->first();
    expect($entry)->not->toBeNull();
    expect($entry->description)->toBe('Emergency Server Repair');
    expect((string) $entry->status)->toBe(\Modules\PIB\Models\ServiceUsage::STATUS_DRAFT);

    // Approve the generic service usage so it appears in unbilled summary
    \Modules\PIB\Models\ServiceUsage::where('client_id', $client->id)->update(['status' => \Modules\PIB\Models\ServiceUsage::STATUS_APPROVED]);

    // 4. Verify Unbilled Summary
    $this->visit(route('admin.billing.service-usage.unbilled'))
        ->assertPathIs('/billing/service-usage/unbilled');

    $entry->refresh();
    expect((float) $entry->hours)->toBe(2.5);
    expect((float) $entry->hourly_rate)->toBe(150.0);
})->group('pib', 'service-usage');

it('manages client credit ledger manually', function () {
    $admin = getPIBAdmin();
    $client = Client::factory()->create([
        'name' => 'Credit Ledger Client '.uniqid(),
    ]);

    // Ensure initial state
    ClientCredit::firstOrCreate(['client_id' => $client->id], [
        'balance_cents' => 0,
        'currency' => 'USD',
    ]);

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]')
        ->assertPathIs('/dashboard');

    // 1. Visit Credit Ledger for Client
    $this->visit(route('admin.billing.credit-ledger.show', $client))
        ->assertPathIs('/billing/credit-ledger/'.$client->id)

    // 2. Add Manual Credit
        ->click('text=Manual Adjustment')
        ->type('amount', '100.00')
        ->type('description', 'Goodwill Adjustment')
        ->click('text=Save Adjustment');

    $credit = ClientCredit::where('client_id', $client->id)->first();
    expect($credit)->not->toBeNull();
    expect((int) $credit->balance_cents)->toBe(10000);

    $ledgerRow = \Illuminate\Support\Facades\DB::table('client_credit_ledger')
        ->where('client_id', $client->id)
        ->where('description', 'Goodwill Adjustment')
        ->latest('id')
        ->first();

    expect($ledgerRow)->not->toBeNull();
})->group('pib', 'credit-ledger');
