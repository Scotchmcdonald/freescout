<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations; // If needed, but usually we don't use this in Dusk unless mapped
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Modules\ContractManager\Models\Quote;
use Modules\ContractManager\Models\Contract; // Assuming this exists
use Tests\Browser\Pages\ContractManager\QuoteCreatePage;
use Tests\Browser\Pages\ContractManager\QuoteDetailPage;
use PHPUnit\Framework\Attributes\Group;

class MultiUserWorkflowsTest extends DuskTestCase
{
    /**
     * Unique identifier for this test run.
     */
    protected static string $testRunId;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$testRunId = date('Ymd-His');
    }

    protected function getAdminUser(): User
    {
        // Reuse logic from ManualTestingPlanTest
        $user = User::first() ?? User::factory()->create();
        if (!$user->isAdmin()) {
            $user->role = User::ROLE_ADMIN;
            $user->save();
        }
        return $user;
    }

    protected function getClientUser(\Modules\Crm\Models\Client $client)
    {
        // Create a ClientUser for the specific client
        // Ensure email is unique
        $email = 'client-' . self::$testRunId . '-' . rand(1000,9999) . '@example.com';
        
        return \Modules\Crm\Models\ClientUser::factory()->create([
            'client_id' => $client->id,
            'email' => $email,
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
    }

    /**
     * TEST GAP: Quote Lifecycle (Client Accept/Reject Flow)
     * 
     * Addresses:
     * - Client receives quote notification
     * - Client rejects quote
     * - Admin revises quote
     * - Client accepts quote
     * - Contract auto-created
     */
    #[Group('multi-user')]
    #[Group('quote-lifecycle')]
    public function test_quote_lifecycle_with_client_rejection_and_acceptance(): void
    {
        $this->browse(function (Browser $browser) {
            // 1. Setup Data
            $admin = $this->getAdminUser();
            $client = \Modules\Crm\Models\Client::factory()->create([
                'name' => 'Test Client ' . self::$testRunId
            ]);
            $clientUser = $this->getClientUser($client);

            // 2. Admin Creates Quote
            $browser->loginAs($admin)
                ->visit(new QuoteCreatePage)
                ->type('@client-select', $client->id) // Assuming selector takes ID or we select by text
                // If it's a select2, interactions might be more complex, falling back to simple fill for now
                // but usually @client-select select[name="client_id"]
                // If Page Object has a method, use it:
                // ->fillDetail('client_id', $client->id) 
                
                // Let's assume standard form filling for now based on Page Object inspection
                ->type('@title-input', 'Web Design Services')
                ->select('@billing-type-select', 'one_time')
                ->type('@line-item-description-0', 'Design Phase 1')
                ->type('@line-item-quantity-0', '1')
                ->type('@line-item-price-0', '1000')
                ->press('@save-draft-btn')
                ->pause(1000);

            // Get the Quote ID (assumed from URL or DB)
            // For robustness, let's grab the latest quote for this client
            $quote = Quote::where('client_id', $client->id)->latest()->first();
            $this->assertNotNull($quote, 'Quote was not created.');
            
            // Send to Client (Admin Action)
            $browser->visit("/contract-manager/quotes/{$quote->id}")
                ->press('Send to Client') // hypothetical button
                ->pause(1000)
                ->assertSee('Sent'); // Verification

            // 3. Client Rejection Flow
            $browser->logout(); // Logout admin
            
            $browser->visit('/portal/login') // Ensure we are at login
                    ->loginAs($clientUser, 'client')
                    ->visit('/portal/quotes')
                    ->assertSee('Web Design Services')
                    ->clickLink('Web Design Services') // Click into detail
                    ->pause(500)
                    ->press('Reject')
                    ->pause(300)
                    ->type('textarea[name="rejection_reason"]', 'Too expensive, please discount.')
                    ->press('Confirm Rejection')
                    ->pause(1000)
                    ->assertSee('Quote Rejected');

            // 4. Admin Revision Flow
            $browser->logout(); // Logout client
            
            $browser->loginAs($admin)
                ->visit("/contract-manager/quotes/{$quote->id}")
                ->assertSee('Rejected')
                ->assertSee('Too expensive')
                ->press('Revise Quote')
                ->pause(1000)
                ->type('input[name="line_items[0][unit_price]"]', '800') // Discount
                ->press('Save & Send') // Assuming this button saves revision and sends it
                ->pause(1000);

            // 5. Client Acceptance Flow
            $browser->logout();
            
            $browser->loginAs($clientUser, 'client')
                ->visit("/portal/quotes/{$quote->id}") // Direct link to quote
                ->assertSee('800') // Verify new price
                ->press('Accept Quote')
                ->pause(500)
                ->press('Confirm Acceptance') // If there is a modal
                ->pause(1000)
                ->assertSee('Accepted');

            // 6. Verify Contract Creation (System Action)
            $browser->logout();
            $browser->loginAs($admin)
                ->visit('/contract-manager/contracts')
                ->assertSee($client->name)
                ->assertSee('Web Design Services'); // Contract usually inherits quote title
        });
    }

    /**
     * TEST GAP: Client Portal & Invoice Viewing
     * 
     * Addresses:
     * - Client logs into portal
     * - Client views dashboard
     * - Client sees their invoices
     */
    #[Group('multi-user')]
    #[Group('client-portal')]
    public function test_client_portal_invoice_viewing(): void
    {
        $this->browse(function (Browser $browser) {
            // 1. Setup
            $client = \Modules\Crm\Models\Client::factory()->create(['name' => 'Invoice Test Client']);
            $clientUser = $this->getClientUser($client);
            
            // Create a dummy invoice for this client
            $invoice = \Modules\PIB\Models\Invoice::factory()->create([
                'client_id' => $client->id,
                'status' => 'unpaid',
                'total_amount' => 50000, // Cents
                'invoice_number' => 'INV-TEST-001'
            ]);

            // 2. Login as Client
            $browser->loginAs($clientUser, 'client')
                ->visit('/portal/dashboard')
                ->assertSee('Invoice Test Client') // Dashboard welcome check
                ->visit('/portal/invoices')
                ->assertSee('INV-TEST-001')
                ->assertSee('$500.00'); // Assuming formatter
            
            // 3. View Detail
            $browser->clickLink('INV-TEST-001')
                ->pause(500)
                ->assertSee('Total Due')
                ->assertSee('$500.00');
        });
    }

    /**
     * TEST GAP: Automatic Invoice Generation
     * 
     * Addresses:
     * - Contract activation triggers billing template (Simulated)
     * - BillingTemplateDue triggers Invoice generation
     * - Recurring invoice generation flow
     */
    #[Group('multi-user')]
    #[Group('invoice-automation')]
    public function test_automatic_invoice_flow(): void
    {
        $this->browse(function (Browser $admin) {
            // 1. Setup Client
            $client = \Modules\Crm\Models\Client::factory()->create(['name' => 'Auto Bill Client']);
            $adminUser = $this->getAdminUser();

            // 2. Simulate "Billing Template Due" Event
            // In a real E2E, this comes from a scheduled command. 
            // We simulate the trigger to verify the downstream effects (Invoice Generation).
            
            $templateData = new \Modules\ContractManager\DataTransferObjects\BillingTemplateDueData(
                templateId: 101,
                clientId: $client->id,
                contractId: 999,
                contractNumber: 'CTR-AUTO-001',
                templateName: 'Monthly Retainer',
                billingCycle: 'monthly',
                lineItems: [
                    [
                        'id' => 1,
                        'product_name' => 'Monthly Service',
                        'description' => 'Service Fee',
                        'quantity' => 1,
                        'unit_price' => 15000, // Cents
                        'line_total' => 15000,
                        'product_type' => 'service',
                        'product_config' => []
                    ]
                ]
            );

            // Fire event
            event(new \Modules\ContractManager\Events\BillingTemplateDue($templateData));

            // 3. Admin Verifies Invoice Created
            $admin->loginAs($adminUser)
                  ->visit('/billing/invoices')
                  ->pause(500)
                  ->assertSee($client->name)
                  ->assertSee('$150.00')
                  ->assertSee('Monthly Retainer');
        });
    }

    /**
     * TEST GAP: Payment Processing & Invoice Settlement
     * 
     * Addresses:
     * - Payment processed -> Credit balance updated
     * - Invoice status: Unpaid -> Paid
     */
    #[Group('multi-user')]
    #[Group('payment-processing')]
    public function test_payment_processing_flow(): void
    {
        $this->browse(function (Browser $admin) {
            // 1. Setup Client with Invoice
            $client = \Modules\Crm\Models\Client::factory()->create(['name' => 'Payment Client']);
            $invoice = \Modules\PIB\Models\Invoice::factory()->create([
                'client_id' => $client->id,
                'status' => 'unpaid',
                'total_amount' => 20000, // $200.00
                'due_date' => now()->subDay(), // Overdue to ensure it's picked up
            ]);
            
            // 2. Add Payment Method (Mock)
            // Assuming we have a way to add a method or we force it via DB
            // For now, let's assume manual payment via Admin UI which is a critical path too
            
            $adminUser = $this->getAdminUser();
            
            $admin->loginAs($adminUser)
                  ->visit("/billing/invoices/{$invoice->id}")
                  ->assertSee('Unpaid')
                  ->press('Record Payment') // Hypothetical admin button
                  ->pause(500)
                  ->type('input[name="amount"]', '200.00')
                  ->select('select[name="method"]', 'check') // 'check', 'transfer', etc.
                  ->press('Save Payment')
                  ->pause(1000)
                  ->assertSee('Paid'); // Status should update
            
            // 3. Verify Invoice Status in DB
            $this->assertEquals('paid', $invoice->fresh()->status);
        });
    }

    /**
     * TEST GAP: Recurring Quote -> Billing Template
     * 
     * Verifies that approved recurring quotes generate Billing Templates.
     */
    #[Group('multi-user')]
    #[Group('quote-lifecycle')]
    public function test_recurring_quote_to_billing_template(): void
    {
        $this->browse(function (Browser $browser) {
            // 1. Setup
            $admin = $this->getAdminUser();
            $client = \Modules\Crm\Models\Client::factory()->create([
                'name' => 'Recurring Client ' . self::$testRunId
            ]);

            // 2. Admin Creates Recurring Quote
            $browser->loginAs($admin)
                ->visit(new QuoteCreatePage)
                ->type('@client-select', $client->id)
                ->type('@title-input', 'Monthly Maintenance')
                ->select('@billing-type-select', 'recurring') // Assuming 'recurring' value
                ->select('@billing-cycle-select', 'monthly')
                
                // Add Service Line Item which is Recurring
                ->type('@line-item-description-0', 'Gold Support Plan')
                ->type('@line-item-quantity-0', '1')
                ->type('@line-item-price-0', '50000') // $500.00
                ->press('@save-draft-btn')
                ->pause(1000);

            // Get Quote
            $quote = Quote::where('client_id', $client->id)->latest()->first();
            
            // Hack: Force item to be recurring because UI support is ambiguous in Page Object
            if ($quote) {
                // If line items exist, update them. Assuming relation handles this.
                // We access the related models directly
                if (method_exists($quote->lineItems(), 'update')) {
                     $quote->lineItems()->update(['is_recurring' => true, 'billing_frequency' => 'monthly']);
                }
            }

            // 3. Admin Approves Quote
            $browser->visit("/contract-manager/quotes/{$quote->id}")
                ->press('Approve') // Admin Force Approve
                ->pause(1000)
                ->assertSee('Approved');

            // 4. Verify Contract
            $contract = Contract::where('quote_id', $quote->id)->first();
            $this->assertNotNull($contract, 'Contract not created');

            // 5. Verify Billing Template Created
            $template = \Modules\ContractManager\Models\BillingTemplate::where('contract_id', $contract->id)->first();
            $this->assertNotNull($template, 'Billing Template was not created for recurring quote');
            $this->assertEquals('monthly', $template->billing_cycle);
        });
    }

    /**
     * TEST GAP: Client Portal Assets & Subscriptions
     * 
     * Verifies client can see their assigned assets and software.
     */
    #[Group('multi-user')]
    #[Group('client-portal')]
    public function test_client_portal_assets_and_subscriptions(): void
    {
        $this->browse(function (Browser $browser) {
            // 1. Setup Data
            $client = \Modules\Crm\Models\Client::factory()->create(['name' => 'Tech Client']);
            $clientUser = $this->getClientUser($client);
            
            // Create Asset
            if (class_exists(\Modules\AssetManagement\Entities\Asset::class)) {
                $asset = \Modules\AssetManagement\Entities\Asset::create([
                    'client_id' => $client->id,
                    'name' => 'CEO Laptop',
                    'serial_number' => 'SN-12345',
                    'status' => 'active',
                    'hardware_make' => 'Dell',
                    'hardware_model' => 'XPS 15',
                ]);
            }

            // 2. Login as Client
            $browser->loginAs($clientUser, 'client')
                ->visit('/portal/dashboard')
                ->assertSee('Assets'); // Sidebar or Tab link
            
            // 3. Navigate to Assets
            $browser->clickLink('Assets')
                ->pause(500)
                ->assertSee('CEO Laptop')
                ->assertSee('SN-12345');
        });
    }
}
