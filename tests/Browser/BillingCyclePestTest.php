<?php

use App\Models\User;
use Modules\ContractManager\Models\BillingTemplate;
use Modules\ContractManager\Models\BillingTemplateLineItem;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\ClientUser;
use Modules\PIB\Models\Invoice;
use function Pest\Laravel\{actingAs};

/**
 * Helper to log in a user via the UI.
 */
function login($browser, $user, $password = 'password', $url = '/login') {
    $browser->visit($url)
        ->assertVisible('input[name="email"]')
        ->type('email', $user->email)
        ->type('password', $password)
        ->click('button[type="submit"]') // Generic submit button
        // Wait for redirect? Usually implicit by next assertion
        ->wait(); 
}

it('admin can generate invoice from template', function () {
    $admin = User::firstOrCreate(['email' => 'billing-cycle-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'Billing',
        'last_name' => 'CycleAdmin',
        'email_verified_at' => now(),
    ]);
    if (!$admin->isAdmin()) { $admin->role = User::ROLE_ADMIN; $admin->save(); }

    $client = Client::factory()->create(['name' => 'Invoice Template Client']);

    // Create a billing template with line items
    $template = BillingTemplate::factory()->create([
        'client_id' => $client->id,
        'name' => 'Monthly Service Plan',
        'product_type' => 'service_plan',
        'billing_cycle' => 'monthly',
        'status' => 'active',
        'next_invoice_date' => now()->subDay(),
    ]);

    BillingTemplateLineItem::create([
        'billing_template_id' => $template->id,
        'product_name' => 'Standard Support',
        'description' => 'Standard Support',
        'quantity' => 1,
        'unit_price' => 500.00,
        'product_type' => 'service_plan',
    ]);

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/billing/invoices/create')
        ->assertSee('Invoice');
})->group('billing', 'invoice');

test('client can pay invoice', function () {
    $client = Client::factory()->create();
    $clientUser = ClientUser::factory()->create([
        'client_id' => $client->id,
        'name' => 'Payment Test User',
        'email' => 'payment-' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(), // Important for login 
        'is_active' => true,
    ]);

    // Seed Invoice
    $invoice = Invoice::create([
        'client_id' => $client->id,
        'company_id' => $client->company_id ?? 1,
        'invoice_number' => 'INV-' . uniqid(),
        'status' => 'sent',
        'invoice_date' => now(),
        'due_date' => now()->addDays(30),
        'total_amount' => 1000.00,
        'subtotal' => 1000.00,
        'tax_amount' => 0
    ]);

    // Client Login at /portal/login
    $this->visit('/portal/login')
        ->assertVisible('input[name="email"]')
        ->type('email', $clientUser->email)
        ->type('password', 'password')
        ->click('button[type="submit"]'); // Assuming standard button

    $this->visit('/portal/invoices')
             ->assertVisible($invoice->invoice_number)
             ->click($invoice->invoice_number)
             ->assertVisible('Pay Now')
             ->assertSee('$1,000.00');

})->group('billing', 'payment');
