<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Crm\Models\Client;
use Modules\PIB\Models\BillingTemplate;
use Tests\TestCase;

class BillingTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_create_template_page()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $response = $this->actingAs($admin)->get(route('admin.billing.templates.create'));

        $response->assertOk();
        $response->assertViewIs('admin.billing.create-template');
    }

    public function test_admin_can_create_rent_to_own_template()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $client = Client::create(['name' => 'Demo Client']);

        $response = $this->actingAs($admin)->post(route('admin.billing.templates.store'), [
            'client_id' => $client->id,
            'name' => 'New Laptop Plan',
            'product_type' => 'rent_to_own',
            'billing_cycle' => 'monthly',
            'product_config' => [
                'goal_amount' => 2000,
                'monthly_installment' => 100
            ]
        ]);

        $response->assertRedirect(route('admin.billing.variance'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('pib_billing_templates', [
            'client_id' => $client->id,
            'name' => 'New Laptop Plan',
            'product_type' => 'rent_to_own',
            'status' => 'active'
        ]);
    }

    public function test_admin_can_create_silver_plan_template()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $client = Client::create(['name' => 'Demo Client']);

        $response = $this->actingAs($admin)->post(route('admin.billing.templates.store'), [
            'client_id' => $client->id,
            'name' => 'Managed Services',
            'product_type' => 'silver_plan',
            'billing_cycle' => 'monthly',
            'product_config' => [
                'base_rate' => 500,
                'per_user_rate' => 150
            ]
        ]);

        $response->assertRedirect(route('admin.billing.variance'));

        $this->assertDatabaseHas('pib_billing_templates', [
            'client_id' => $client->id,
            'name' => 'Managed Services',
            'product_type' => 'silver_plan'
        ]);
    }

    public function test_validation_works()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('admin.billing.templates.store'), [
            // Missing fields
            'name' => ''
        ]);

        $response->assertSessionHasErrors(['client_id', 'name', 'product_type']);
    }
}
