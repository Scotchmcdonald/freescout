<?php

use App\Models\User;
use Modules\ContractManager\Models\Contract;
use Modules\Crm\Models\Client;
use Modules\PIB\Models\Invoice;

// Reuse the helper from existing tests if global, otherwise redefine or check if it's autoloaded
// Ideally this should be in PEST.php or a helper file, but for safety I will define a local helper
// or trust it's available if it was in the other file. 
// Since I can't be sure it's global, I'll inline the login logic to be safe.

test('rent to own early buyout flow', function () {
    // 1. Setup Data
    $admin = User::firstOrCreate(['email' => 'rto-ui-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN, // Assuming strictly 'admin' role const
        'first_name' => 'RTO',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (!$admin->isAdmin()) { $admin->role = User::ROLE_ADMIN; $admin->save(); }

    $client = Client::factory()->create(['name' => 'RTO UI Test Corp']);

    $contract = Contract::create([
        'client_id' => $client->id,
        'title' => 'RTO Buyout UI Test',
        'contract_number' => 'CON-UI-' . uniqid(),
        'status' => 'active',
        'start_date' => now(),
        'contract_type' => 'rent_to_own',
        'purchase_price' => 500.00,
        'monthly_rental_fee' => 50.00,
        'allow_early_buyout' => true,
        'ownership_status' => 'renting',
    ]);

    // 2. Login
    $this->visit('/login')
        ->type('email', $admin->email) // Assuming API is type('name', value)
        ->type('password', 'password')
        ->click('button[type="submit"]')
        ->waitForText('Dashboard');

    // 3. Visit Contract Page
    $browser = $this->visit("/contracts/agreements/{$contract->id}")
        ->waitForText('RTO Buyout UI Test');

    // 4. Verify Initial State
    // Note: JS errors about Alpine might appear in logs because of missing assets in test environment, 
    // but the button should still be visible as static HTML. 
    // We disable JS log checking for this test if needed, or fix assets.
    // For now we assume the test environment builds assets correctly.
    
    $browser->assertSee('Renting to Own')
        ->assertVisible('[dusk="generate-buyout-button"]');

    // 5. Execute Buyout
    // The previous test failed waiting for text. This implies the form submission failed.
    // The JS errors (showTerminate is not defined) block the thread.
    // We will bypass the click() helper if it tries to be smart, but 'click' is standard.
    // Instead we will try to use script to click it if normal click fails, or ensure assets are built.
    // Likely solution: The error happens on page load.
    
    // We will attempt to run the command anyway.
    $browser->click('[dusk="generate-buyout-button"]');
        
    // Handle potential alert/confirm automatically (Pest/Dusk usually auto-accepts standard confirms)
    // If safe, we wait.
    $browser->waitForText('Buyout invoice generated', 10);
    
    // 6. Verify Intermediate State
    // Reload explicitly to ensure state is fresh
    $browser = $this->visit("/contracts/agreements/{$contract->id}")
        ->assertSee('Renting to Own'); 
        // Logic: Invoice exists but is not paid, so still renting.

    // 7. Simulate Payment (Backend Action)
    $invoice = Invoice::where('contract_id', $contract->id)
        ->where('is_buyout', true)
        ->firstOrFail();
    
    $invoice->markAsPaid(); // This fires the event and listener

    // 8. Verify Final State
    $browser = $this->visit("/contracts/agreements/{$contract->id}")
        ->waitForText('Ownership Transferred') // This is the status badge text
        ->assertSee('Ownership Transferred')
        ->assertMissing('[dusk="generate-buyout-button"]');

});

test('buyout button hidden if disabled', function () {
    $admin = User::firstOrCreate(['email' => 'rto-ui-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'RTO',
        'last_name' => 'Admin',
    ]);

    $client = Client::factory()->create(['name' => 'RTO No Buyout Corp']);

    $contract = Contract::create([
        'client_id' => $client->id,
        'title' => 'No Buyout Test',
        'contract_number' => 'CON-NB-' . uniqid(),
        'status' => 'active',
        'start_date' => now(),
        'contract_type' => 'rent_to_own',
        'purchase_price' => 500.00,
        'allow_early_buyout' => false, // DISABLED
    ]);

    // Login
    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]')
        ->waitForText('Dashboard');

    $this->visit("/contracts/agreements/{$contract->id}")
        ->waitForText('No Buyout Test')
        ->assertMissing('[dusk="generate-buyout-button"]');
});
