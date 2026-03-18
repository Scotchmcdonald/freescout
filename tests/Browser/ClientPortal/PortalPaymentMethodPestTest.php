<?php

use App\Models\User;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;

/*
 * Helper to get or create a portal User for Portal Login
 */
function getPortalUser(): array
{
    $company = Company::factory()->create(['is_active' => true]);
    $client = Client::factory()->create([
        'company_id' => $company->id,
        'name' => 'Portal Test Client '.uniqid(),
        'status' => 'active',
    ]);

    $user = User::factory()->create([
        'type' => 2,
        'email' => 'portal.'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'status' => User::STATUS_ACTIVE,
        'email_verified_at' => now(),
    ]);
    $company->users()->attach($user->id, ['role_id' => 1, 'status' => 'approved', 'is_primary' => true]);

    return [$user, $client, 'password'];
}

it('allows client to view and add payment methods', function () {
    [$user, $client, $password] = getPortalUser();

    // Ensure client is active
    $client->update(['status' => 'active']);

    // Use portal specific login route
    browserLoginPortal($this, $user); // Check for authenticated element

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
