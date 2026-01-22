<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Crm\Models\Client;
use Modules\PIB\Models\BillingTemplate;
use Modules\PIB\Models\Invoice;
use Tests\TestCase;

class BillingVarianceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_variance_report()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        // Setup Client
        $client = Client::create(['name' => 'Variance Client']);

        // Setup Template (Rent-to-Own)
        // Installment $200
        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'name' => 'Hardware Plan',
            'product_type' => 'rent_to_own',
            'product_config' => [
                'goal_amount' => 5000,
                'monthly_installment' => 200
            ], // Logic will resolve $200
            'billing_cycle' => 'monthly',
            'next_invoice_date' => now(),
            'status' => 'active'
        ]);

        // Create Previous Invoice for $100 (Maybe old installment)
        Invoice::create([
            'client_id' => $client->id,
            'billing_template_id' => $template->id,
            'invoice_number' => 'INV-001',
            'status' => 'paid',
            'invoice_date' => now()->subMonth(),
            'due_date' => now()->subMonth()->addDays(30),
            'subtotal' => 100.00,
            'tax_amount' => 0,
            'total_amount' => 100.00,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.billing.variance'));

        $response->assertOk();
        $response->assertViewIs('admin.billing.variance');
        
        // Expected Variance: ($200 - $100) / $100 = 100%
        $response->assertSee('100%'); 
        $response->assertSee('Unusual');
        $response->assertSee('$200.00');
    }

    public function test_normal_variance_is_displayed()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $client = Client::create(['name' => 'Normal Client']);

        $template = BillingTemplate::create([
            'client_id' => $client->id,
            'product_type' => 'rent_to_own',
            'product_config' => ['goal_amount' => 5000, 'monthly_installment' => 200],
            'billing_cycle' => 'monthly',
            'next_invoice_date' => now(),
            'status' => 'active'
        ]);

        // Previous invoice exactly same ($200)
        Invoice::create([
            'client_id' => $client->id,
            'billing_template_id' => $template->id,
            'invoice_number' => 'INV-002',
            'status' => 'paid',
            'invoice_date' => now()->subMonth(),
            'due_date' => now(),
            'subtotal' => 200.00,
            'tax_amount' => 0,
            'total_amount' => 200.00,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.billing.variance'));

        $response->assertOk();
        $response->assertSee('0%');
        $response->assertSee('Normal');
    }
}
