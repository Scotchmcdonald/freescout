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

    if (!$admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }
    
    return $admin;
}

it('manages service usage collection', function () {
    $admin = getPIBAdmin();
    $client = Client::factory()->create([
        'name' => 'Service Usage Client ' . uniqid(),
    ]);

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]')
        ->assertPathIs('/dashboard');

    // 1. Visit Service Usage Index
    $this->visit(route('admin.billing.service-usage.index'))
        ->assertSee('Service Usage');
        
        // 2. Create Service Usage Entry
    $this->visit(route('admin.billing.service-usage.create'))
        ->assertSee('Add Service Entry') // Corrected Header
        ->select('client_id', (string)$client->id)
        ->select('service_type', 'Labor')
        ->type('description', 'Emergency Server Repair')
        ->type('service_date', date('Y-m-d'))
        ->type('hours', '2.5')
        ->type('hourly_rate', '150')
        ->press('Save Entry')
        
        // 3. Verify Landing on Index/Show and Data
        ->assertSee('saved as draft')
        ->assertSee('Emergency Server Repair')
        ->assertSee('Draft'); // Default status
        
    // Approve the generic service usage so it appears in unbilled summary
    \Modules\PIB\Models\ServiceUsage::where('client_id', $client->id)->update(['status' => \Modules\PIB\Models\ServiceUsage::STATUS_APPROVED]);

    // 4. Verify Unbilled Summary
    $this->visit(route('admin.billing.service-usage.unbilled'))
        ->assertSee($client->name)
        ->assertSee('2.5') // Hours
        ->assertSee('375.00'); // 2.5 * 150

})->group('pib', 'service-usage');

it('manages client credit ledger manually', function () {
    $admin = getPIBAdmin();
    $client = Client::factory()->create([
        'name' => 'Credit Ledger Client ' . uniqid(),
    ]);

    // Ensure initial state
    ClientCredit::firstOrCreate(['client_id' => $client->id], [
        'balance_cents' => 0,
        'currency' => 'USD'
    ]);

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]')
        ->assertPathIs('/dashboard');

    // 1. Visit Credit Ledger for Client
    $this->visit(route('admin.billing.credit-ledger.show', $client))
        ->assertSee('Credit Ledger')
        ->assertSee('0.00')
        
    // 2. Add Manual Credit
        ->click('text=Manual Adjustment')
        ->type('amount', '100.00')
        ->type('description', 'Goodwill Adjustment')
        ->click('text=Save Adjustment')
        
        // 3. Verify Balance Update
        ->assertSee('Credit added')
        ->assertSee('100.00') // New Balance
        ->assertSee('Goodwill Adjustment')
        
        // 4. Verify Ledger Entry Row (UI check)
        // Checking if the description appears in the table
        ->assertSee('Goodwill Adjustment')
        ->assertSee('100.00');

})->group('pib', 'credit-ledger');
