<?php

use App\Models\User;
use Modules\Crm\Models\Client;
use Modules\PIB\Models\Entitlement;
use Modules\PIB\Models\Product;

test('entitlement ux lifecycle', function () {
    // 1. Setup Data
    $user = User::factory()->create(['role' => User::ROLE_ADMIN, 'password' => bcrypt('password')]);
    $client = Client::factory()->create(['name' => 'Browser Test Client']);

    // 2. Login
    $this->visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->click('button[type="submit"]')
        ->assertPathIs('/dashboard');

    // 3. Create Product
    $this->visit('/billing/products/create')
        ->waitForText('Product Name')
        ->type('#name', 'Browser Product')
        ->type('#code', 'BROWSER-001')
        ->select('#status', 'active')
        ->click('button[type="submit"]')
        ->waitForText('Product created successfully', 10)
        ->assertPathIs('/billing/products')
        ->assertSee('Browser Product');

    $product = Product::where('code', 'BROWSER-001')->first();

    // 4. Provision Entitlement - Use specific selector for sidebar form
    $this->visit("/billing/clients/{$client->id}/entitlements")
        ->assertSee('Entitlements')
        ->select('select[name="product_id"]', (string) $product->id)
        ->type('input[name="quantity"]', '5')
        ->type('input[name="rate"]', '25.00')
        ->select('select[name="billing_cycle"]', 'monthly')
        ->click('button[type="submit"]')
        ->waitForText('Entitlement provisioned successfully')
        ->assertPathIs("/billing/clients/{$client->id}/entitlements")
        ->assertSee('5');

    // 5. Update Entitlement via Modal
    $entitlement = Entitlement::where('client_id', $client->id)->first();
    expect($entitlement)->not->toBeNull();

    $browser = $this->visit("/billing/clients/{$client->id}/entitlements");
    $browser->assertSee($product->name)
        ->assertDontSee('No active entitlements found.')
         // Click edit button via JavaScript since Alpine.js modal needs JS
        ->script('document.querySelector("button.btn-outline-primary").click()');

    // Wait for modal and interact with it
    $browser->waitForText('Edit Entitlement');
    $browser->script('const modalInput = document.querySelector("#editModal-'.$entitlement->id.' input[name=quantity]"); modalInput.value = ""; modalInput.dispatchEvent(new Event("input"));');
    $browser->type("#editModal-{$entitlement->id} input[name='quantity']", '10')
        ->click("#editModal-{$entitlement->id} button[type='submit']")
        ->waitForText('Entitlement updated')
        ->assertPathIs("/billing/clients/{$client->id}/entitlements")
        ->assertSee('10');

    // Refresh entitlement from database
    $entitlement->refresh();

    // 6. Cancel Entitlement
    $browser = $this->visit("/billing/clients/{$client->id}/entitlements");
    $browser->assertSee('Browser Product')
        ->assertSee('10');
    // Use class selector since there's only one cancel button
    $browser->script('document.querySelector("button.btn-outline-danger").click()');
    $browser->waitForText('Cancel Entitlement')
        ->script('document.querySelector("#cancelModal-'.$entitlement->id.' textarea[name=cancellation_reason]").value = "Test cancellation";');
    $browser->type("#cancelModal-{$entitlement->id} textarea[name='cancellation_reason']", 'Test cancellation')
        ->click("#cancelModal-{$entitlement->id} button[type='submit']")
        ->waitForText('Entitlement cancelled')
        ->assertPathIs("/billing/clients/{$client->id}/entitlements")
        ->assertSee('No active entitlements');

    // 7. Verify History & Restore
    $browser = $this->visit("/billing/clients/{$client->id}/entitlements/history");
    $browser->assertSee('Entitlement History')
        ->assertSee('Browser Product')
        ->script('document.querySelector("#restore-btn-'.$entitlement->id.'").click()');

    $browser->waitForText('Entitlement restored')
        ->assertPathIs("/billing/clients/{$client->id}/entitlements")
        ->assertSee('Browser Product');
})->group('pib', 'browser');
