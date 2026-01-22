<?php

namespace Tests\Feature\Billing;

use Carbon\Carbon;
use Modules\ContractManager\Models\BillingTemplate;
use Modules\Crm\Models\Client;
use Modules\PIB\Services\InvoiceGenerator;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PlanChangeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Edge Case: Mid-Cycle Plan Changes
     * When a plan is upgraded mid-cycle, the system should either:
     * 1. Pro-rate the old and new plans.
     * 2. Or Bill the new plan from the change date.
     * 
     * Current Behavior Hypothesis:
     * The system overwrites the template config. When billing runs at end of month,
     * it sees only the NEW price and applies it to the WHOLE month.
     * Result: Overbilling (if price went up) or Revenue Loss (if price went down).
     */
    public function test_mid_cycle_plan_change_is_handled_correctly()
    {
        // 1. Setup - Jan 1
        $client = Client::factory()->create();
        
        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'name' => 'Service Plan',
            'product_type' => 'service_plan',
            'product_config' => ['base_price' => 100.00], // Plan A
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'next_invoice_date' => '2024-02-01',
        ]);

        // 2. Mid-Month Change - Jan 15
        Carbon::setTestNow('2024-01-15 12:00:00');
        
        // Upgrade to $200 Plan
        // In the real app, this happens via BillingTemplateService::updateTemplate
        $template->update([
            'product_config' => ['base_price' => 200.00]
        ]);

        // 3. Billing Run - Feb 1
        Carbon::setTestNow('2024-02-01');
        
        $generator = app(InvoiceGenerator::class);
        // Explicitly bill for January (Arrears) to capture the change on Jan 15
        $startDate = Carbon::parse('2024-01-01');
        $endDate = Carbon::parse('2024-01-31')->endOfDay();
        
        $invoice = $generator->generateFromTemplate($template, now(), $startDate, $endDate);

        // 4. Verification
        // Expected Math (Prorated):
        // 15 Days @ $100 + 16 Days @ $200
        // (100 * 0.48) + (200 * 0.52) = $48 + $104 = ~$152
        
        // Expected Behavior (Current Bug):
        // $200 (Full month at new price)
        
        $amount = $invoice->total_amount;
        
        $this->assertNotEquals(200.00, $amount, "Logic Error: System billed full month at new upgraded price, ignoring the first half of the month.");
        $this->assertNotEquals(100.00, $amount, "Logic Error: System billed full month at old price.");
        
        // Allow some float variance
        $this->assertEqualsWithDelta(150.00, $amount, 10.00, "Invoice total should reflect a mix of Plan A and Plan B pricing.");
    }
}
