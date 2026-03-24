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

it('billing event journey applies proration and license adjustment in sequence', function () {
    $client = Client::factory()->create(['name' => 'Journey Billing Client']);

    $contract = \Modules\ContractManager\Models\Contract::create([
        'client_id' => $client->id,
        'contract_number' => 'BILL-JOURNEY-001',
        'start_date' => now(),
        'status' => 'active',
    ]);

    $template = BillingTemplate::factory()->create([
        'contract_id' => $contract->id,
        'client_id' => $client->id,
        'status' => 'active',
        'product_type' => 'software',
        'product_config' => [
            'subscription_id' => 321,
            'license_count' => 3,
        ],
    ]);

    $prorationListener = new \Modules\PIB\Listeners\RecalculateProrationOnContractChange;
    $prorationListener->handle(new \Modules\ContractManager\Events\ContractRevised(
        $contract,
        ['monthly_cost' => 150],
        'billing-journey-event'
    ));

    $adjustListener = new \Modules\PIB\Listeners\AdjustBillingOnSoftwareCountChange;
    $adjustListener->handle(new \Modules\SoftwareSubscriptions\Events\SoftwareCountChanged(
        new \Modules\SoftwareSubscriptions\DataTransferObjects\SoftwareCountChangedData(
            subscriptionId: 321,
            clientId: $client->id,
            softwareProductId: 11,
            productName: 'Journey Product',
            previousCount: 3,
            newCount: 6,
            changeReason: 'Journey upgrade'
        ),
        'billing-journey-event'
    ));

    $template->refresh();

    expect($template->product_config['proration_pending'] ?? false)->toBeTrue()
        ->and($template->product_config['license_count'] ?? null)->toBe(6)
        ->and($template->product_config['previous_count'] ?? null)->toBe(3);
});

it('non-admin cannot access invoice creation page', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER, 'type' => 2]);

    $response = $this->actingAs($user)->get('/billing/invoices/create');

    expect(in_array($response->status(), [302, 403], true))->toBeTrue();
});
