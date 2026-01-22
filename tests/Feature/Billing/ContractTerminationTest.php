<?php

namespace Tests\Feature\Billing;

use Carbon\Carbon;
use Modules\ContractManager\Models\BillingTemplate;
use Modules\ContractManager\Models\Contract;
use Modules\Crm\Models\Client;
use Modules\PIB\Services\InvoiceGenerator;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ContractTerminationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Edge Case: Churn Revenue Leakage
     * When a contract is terminated mid-cycle, the system must generate a final "Wrap-up" invoice
     * for the partial period service.
     * 
     * Current Behavior Hypothesis: 
     * InvoiceGenerator only queries 'active' templates. Terminated contracts are ignored,
     * causing 100% revenue leakage for the final partial period.
     */
    public function test_terminated_contract_generates_final_invoice()
    {
        // 1. Setup
        $client = Client::factory()->create(['name' => 'Churning Client']);
        
        // Contract active from Jan 1
        $contract = Contract::create([
            'client_id' => $client->id,
            'title' => 'Service Agreement',
            'status' => 'active',
            'start_date' => '2024-01-01',
        ]);

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'contract_id' => $contract->id,
            'name' => 'Monthly Retainer',
            'product_type' => 'service_plan',
            'product_config' => ['base_price' => 1000.00],
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'next_invoice_date' => '2024-02-01', // Due Feb 1
        ]);

        // 2. Churn Event on Jan 15
        Carbon::setTestNow('2024-01-15');
        
        // Terminate Contract and Template
        $contract->update(['status' => 'terminated', 'end_date' => now()]);
        $template->update(['status' => 'terminated']);

        // 3. Billing Run on Feb 1
        Carbon::setTestNow('2024-02-01');
        
        $generator = app(InvoiceGenerator::class);
        
        // We simulate the cron job grabbing "Due Templates"
        // Note: We use the actual service method used by the cron
        $dueTemplates = $generator->getDueTemplates();
        
        // The ID of our churning template should be in the list OR 
        // a specific "Final Bill" process should have run.
        // If it's not in the list, no invoice is generated.
        
        // Fails if the system ignores terminated templates with outstanding balances
        $this->assertTrue($dueTemplates->contains('id', $template->id), 
            "The billing engine ignored the terminated contract, resulting in revenue leakage.");

        // If we manually force generation (simulating a 'final bill' action):
        $invoice = $generator->generateFromTemplate($template, now(), Carbon::parse('2024-01-01'), Carbon::parse('2024-01-15'));
        
        $this->assertNotNull($invoice);
    }
}
