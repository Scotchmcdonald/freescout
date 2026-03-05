<?php

use App\Models\User;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;
use Modules\ContractManager\Models\BillingTemplate;
use Modules\PIB\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;

beforeEach(function () {
    if (!class_exists(\Modules\ContractManager\Models\BillingTemplate::class)) {
        $this->markTestSkipped('Billing module not available');
    }
});

test('admin can view create template page', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    
    $this->actingAs($admin)
        ->get(route('admin.billing.templates.create'))
        ->assertOk()
        ->assertViewIs('admin.billing.create-template');
});

test('admin can create rent to own template', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $company = Company::create(['name' => 'Demo Company']);
    $client = Client::create(['name' => 'Demo Client', 'company_id' => $company->id]);

    $this->actingAs($admin)
        ->post(route('admin.billing.templates.store'), [
            'client_id' => $client->id,
            'name' => 'New Laptop Plan',
            'product_type' => 'rent_to_own',
            'billing_cycle' => 'monthly',
            'product_config' => [
                'goal_amount' => 2000,
                'monthly_installment' => 100
            ]
        ])
        ->assertRedirect(route('admin.billing.variance'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('cm_billing_templates', [
        'product_type' => 'rent_to_own',
        'status' => 'active'
    ]);
});

test('admin can create service plan template', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $company = Company::create(['name' => 'Demo Company']);
    $client = Client::create(['name' => 'Demo Client', 'company_id' => $company->id]);

    $this->actingAs($admin)
        ->post(route('admin.billing.templates.store'), [
            'client_id' => $client->id,
            'name' => 'Managed Services',
            'product_type' => 'subscription_service_plan',
            'billing_cycle' => 'monthly',
            'product_config' => [
                'base_rate' => 500,
                'per_user_rate' => 150
            ]
        ])
        ->assertRedirect(route('admin.billing.variance'));

    $this->assertDatabaseHas('cm_billing_templates', [
        'product_type' => 'subscription_service_plan'
    ]);
});

test('validation works', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->post(route('admin.billing.templates.store'), [
            // Missing fields
            'name' => ''
        ])
        ->assertSessionHasErrors(['client_id', 'name', 'product_type']);
});

test('admin can view variance report', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    
    // Setup Client
    $company = Company::create(['name' => 'Variance Company']);
    $client = Client::create(['name' => 'Variance Client', 'company_id' => $company->id]);

    // Setup Template (Rent-to-Own)
    // Installment $200
    $template = BillingTemplate::create([
        'client_id' => $client->id,
        'company_id' => $company->id,
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
        'company_id' => $company->id,
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
});

test('normal variance is displayed', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $company = Company::create(['name' => 'Normal Company']);
    $client = Client::create(['name' => 'Normal Client', 'company_id' => $company->id]);

    $template = BillingTemplate::create([
        'client_id' => $client->id,
            'name' => 'Test Template',
        'company_id' => $company->id,
        'product_type' => 'rent_to_own',
        'product_config' => ['goal_amount' => 5000, 'monthly_installment' => 200],
        'billing_cycle' => 'monthly',
        'next_invoice_date' => now(),
        'status' => 'active'
    ]);

    // Previous invoice exactly same ($200)
    Invoice::create([
        'client_id' => $client->id,
        'company_id' => $company->id,
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
});
