<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ContractManager\Models\BillingTemplate;
use Modules\Crm\Models\Client;

uses(RefreshDatabase::class);

it('invoice creation page loads for admin', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    Client::factory()->create(['name' => 'Invoice Client']);

    $this->actingAs($admin)
        ->get('/billing/invoices/create')
        ->assertOk();
});

it('billing templates page loads and shows active templates', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $client = Client::factory()->create(['name' => 'Template Client']);
    $template = BillingTemplate::factory()->create([
        'client_id' => $client->id,
        'name' => 'Monthly Service',
        'product_type' => 'service_plan',
        'billing_cycle' => 'monthly',
        'status' => 'active',
    ]);

    $this->actingAs($admin)
        ->get('/contracts/billing-templates')
        ->assertOk();

    expect($template->fresh()->status)->toBe('active');
});
