<?php

declare(strict_types=1);

namespace Tests\Integration\Workflows;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ContractManager\Models\BillingTemplate;
use Modules\ContractManager\Models\Contract;
use Modules\Crm\Models\Client;
use Modules\PIB\Services\InvoiceGenerator;
use Modules\SoftwareSubscriptions\Models\ClientSoftwareSubscription;
use Modules\SoftwareSubscriptions\Models\SoftwareProduct;
use Tests\TestCase;

class ClientEnrollmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_calculates_enrollment_invoice_correctly()
    {
        // 1. Foundation: Client
        $client = Client::factory()->create(['name' => 'Enrollment Client']);

        // 2. Contract & Service Plan (Billing Template)
        // Represents "Managed Service Plan" @ $50/user + $5/server
        // We simulate the product config that drives the invoice generator
        $billingTemplate = BillingTemplate::factory()->create([
            'client_id' => $client->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'product_type' => 'service_plan',
            'product_config' => [
                'plan_name' => 'Managed Service Plan',
                'base_price' => 550.00, // 11 users * $50
                // In a real dynamic system, this base_price would be calculated or the quantity would be dynamic.
                // For this test, we verify that One-Time fees and Fixed Recurring fees sum up correctly.
                'per_asset_price' => 5.00, // For servers
            ],
            'next_invoice_date' => now(),
        ]);

        // 3. Assets (2 Servers)
        // These contribute to the per_asset_price line item
        if (class_exists(\Modules\AssetManagement\Entities\Asset::class)) {
            \Modules\AssetManagement\Entities\Asset::factory()->count(2)->create([
                'client_id' => $client->id,
                'status' => 'active',
                'asset_type' => 'Server', // Assuming logic checks for 'Server' or uses all assets
            ]);
            // Create other assets that shouldn't initiate per_asset_price if logic differentiates,
            // but the prompt implied "Servers" specifically have the fee.
            // If the Billing Logic relies on count(), we assume it counts correctly.
            // For this test, valid Asset existence is key.
        }

        // 4. Software Subscriptions (Included)
        // We create these to ensure they Do NOT add to the bill.
        $softwareProduct = SoftwareProduct::factory()->create(['vendor_cost' => 10, 'default_price' => 20]);
        ClientSoftwareSubscription::factory()->create([
            'client_id' => $client->id,
            'software_product_id' => $softwareProduct->id,
            'billing_behavior' => 'included', // KEY: Included
            'purchased_quantity' => 11,
            'status' => 'active',
        ]);

        // 5. Generate Invoice
        $generator = app(InvoiceGenerator::class);
        $invoice = $generator->generateFromTemplate($billingTemplate);

        // 6. Assertions
        // Expect: $550 (Base) + $10 (2 Servers * $5) = $560
        // We assume the generator logic handles the asset counting for 'per_asset_price'
        // OR the base price covers it.
        // Based on previous InvoiceGenerator analysis:
        // 'service_plan' type handles 'base_price' AND 'per_asset_price' * asset_count.

        $expectedTotal = 560.00;

        // Check Line Items
        // 1. Plan Base: $550
        // 2. Asset Charge: $10 (2 * 5)

        // Note: The InvoiceGenerator code showed:
        // if (!empty($config['per_asset_price'])) { ... getAssetCount() ... }
        // We need to ensure getAssetCount return 2 for the 'active' assets we created.

        $this->assertEquals($expectedTotal, $invoice->total_amount, 'Invoice total should be $560.00');
    }

    public function test_enrollment_workflow_validates_client_authorization_context_is_preserved(): void
    {
        // Authorization boundary: the enrollment workflow must attach every
        // generated invoice to the authorized client — cross-module authorization
        // context must survive the Contract → PIB chain.
        $client = Client::factory()->create(['name' => 'Auth Context Client']);
        $billingTemplate = BillingTemplate::factory()->create([
            'client_id' => $client->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'product_type' => 'service_plan',
            'product_config' => ['plan_name' => 'Auth Test Plan', 'base_price' => 200.00],
        ]);

        $generator = app(InvoiceGenerator::class);
        $invoice = $generator->generateFromTemplate($billingTemplate);

        // Validation: generated invoice must be scoped to the authorized client
        $this->assertEquals(
            $client->id,
            $invoice->client_id,
            'Authorization context: invoice client_id must match the authorizing client'
        );
    }
}
