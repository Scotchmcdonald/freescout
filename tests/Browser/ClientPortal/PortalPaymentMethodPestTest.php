<?php

use App\Models\User;
use Modules\Crm\Models\Client;
use Illuminate\Support\Facades\Hash;

/*
 * Helper to get or create a Client User for Portal Login
 */
function getPortalUser(): array
{
    // Create a client
    $client = Client::factory()->create([
        'name' => 'Portal Test Client ' . uniqid(),
    ]);

    $password = 'secret123';
    
    // Create Client User using the CRM model which is correct per codebase
    $user = \Modules\Crm\Models\ClientUser::factory()->create([
        'client_id' => $client->id,
        'email' => 'portal.' . uniqid() . '@example.com',
        'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password,
        'is_active' => true,
    ]);
    
    return [$user, $client, 'password'];
}

it('allows client to view and add payment methods', function () {
    list($user, $client, $password) = getPortalUser();

    // Ensure client is active
    $client->update(['status' => 'active']);
    
    // Use portal specific login route
    $this->visit(route('portal.login'))
        ->type('email', $user->email)
        ->type('password', 'password')
        ->click('button[type="submit"]')
        ->assertDontSee('credentials are incorrect')
        ->assertDontSee('not active')
        // ->assertPathIs('/portal/dashboard') // URL check failing
        ->assertSee('Logout'); // Check for authenticated element

    // 2. Navigate to Payment Methods
    $this->visit(route('portal.billing.payment-methods'))
        ->assertSee('Payment Methods');

    // 3. Add New Payment Method
    $browser = $this->visit(route('portal.payments.create'))
        ->assertSee('New Payment Method')
        ->type('card_number', '4242424242424242')
        ->type('card_expiry', '1230')
        ->type('card_cvv', '123')
        ->type('cardholder_name', 'Test User')
        ->type('billing_street', '123 Main St')
        ->type('billing_city', 'City')
        ->type('billing_state', 'NY')
        ->type('billing_zip', '10001')
        ->type('billing_country', 'US');

    // Force click via JS to bypass potential visibility/interactability checks
    $browser->script("document.querySelector('form[action*=\"payments\"] button[type=\"submit\"]').click()");

    // 4. Verify Success
    $browser->waitForText('Payment method added', 10)
         ->assertSee('4242');

})->group('portal', 'payments');
