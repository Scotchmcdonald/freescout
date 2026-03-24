<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\ContractManager\Events\ContractRevised;
use Modules\ContractManager\Models\BillingTemplate;
use Modules\ContractManager\Models\Contract;
use Modules\Crm\Models\Client;
use Modules\PIB\Listeners\AdjustBillingOnSoftwareCountChange;
use Modules\PIB\Listeners\RecalculateProrationOnContractChange;
use Modules\SoftwareSubscriptions\DataTransferObjects\SoftwareCountChangedData;
use Modules\SoftwareSubscriptions\Events\SoftwareCountChanged;

uses(RefreshDatabase::class);

test('contract revised event triggers proration recalculation for active contracts', function () {
    // Create real client and contract to satisfy FK constraints
    $client = Client::factory()->create();
    $realContract = Contract::create([
        'client_id' => $client->id,
        'contract_number' => 'TEST-EL-001',
        'start_date' => now(),
        'status' => 'active',
    ]);

    $contract = $realContract;

    $template = BillingTemplate::factory()->create([
        'contract_id' => $realContract->id,
        'client_id' => $client->id,
        'status' => 'active',
        'next_invoice_date' => now()->addDays(15), // Mid-cycle
    ]);

    $event = new ContractRevised($contract, ['monthly_cost' => 100], 'test-event-1');
    $listener = new RecalculateProrationOnContractChange;

    $listener->handle($event);

    $template->refresh();
    expect($template->product_config['proration_pending'])->toBeTrue();
});

test('contract revised event skips proration for inactive contracts', function () {
    $client = Client::factory()->create();
    $realContract = Contract::create([
        'client_id' => $client->id,
        'contract_number' => 'TEST-EL-002',
        'start_date' => now(),
        'status' => 'terminated',
    ]);

    // Create an active template to prove the listener does not touch it when the contract is inactive
    $template = BillingTemplate::factory()->create([
        'contract_id' => $realContract->id,
        'client_id' => $client->id,
        'status' => 'active',
    ]);

    $contract = $realContract;
    $event = new ContractRevised($contract, ['monthly_cost' => 100], 'test-event-2');
    $listener = new RecalculateProrationOnContractChange;

    $listener->handle($event);

    $template->refresh();
    expect($template->product_config)->not->toHaveKey('proration_pending');
});

test('contract revised event skips proration when no active templates exist', function () {
    // Create real client and contract to satisfy FK constraints
    $client = Client::factory()->create();
    $realContract = Contract::create([
        'client_id' => $client->id,
        'contract_number' => 'TEST-EL-003',
        'start_date' => now(),
        'status' => 'active',
    ]);

    // Create mock contract matching real IDs
    $contract = $realContract;

    // No active templates — use 'terminated' (valid enum value, not 'cancelled')
    $template = BillingTemplate::factory()->create([
        'contract_id' => $realContract->id,
        'client_id' => $client->id,
        'status' => 'terminated',
    ]);

    $event = new ContractRevised($contract, ['monthly_cost' => 100], 'test-event-3');
    $listener = new RecalculateProrationOnContractChange;

    $listener->handle($event);

    // Active contract but no active templates — proration should not be applied
    $template->refresh();
    expect($template->product_config)->not->toHaveKey('proration_pending');
});

test('software count changed event adjusts billing template license count', function () {
    $client = Client::factory()->create();

    $template = BillingTemplate::factory()->create([
        'client_id' => $client->id,
        'product_type' => 'software',
        'status' => 'active',
        'product_config' => [
            'subscription_id' => 123,
            'license_count' => 10,
        ],
    ]);

    $data = new SoftwareCountChangedData(
        subscriptionId: 123,
        clientId: $client->id,
        softwareProductId: 1,
        productName: 'Test Software',
        previousCount: 10,
        newCount: 15,
        changeReason: 'User added 5 licenses'
    );

    $event = new SoftwareCountChanged($data, 'test-event-4');
    $listener = new AdjustBillingOnSoftwareCountChange;

    // Handle event
    $listener->handle($event);

    // Refresh template and verify update
    $template->refresh();
    expect($template->product_config['license_count'])->toBe(15);
    expect($template->product_config['previous_count'])->toBe(10);
});

test('software count changed event ignores templates without matching subscription id', function () {
    $client = Client::factory()->create();

    $template = BillingTemplate::factory()->create([
        'client_id' => $client->id,
        'product_type' => 'software',
        'status' => 'active',
        'product_config' => [
            'subscription_id' => 456, // Different ID
            'license_count' => 10,
        ],
    ]);

    $data = new SoftwareCountChangedData(
        subscriptionId: 123,
        clientId: $client->id,
        softwareProductId: 1,
        productName: 'Test Software',
        previousCount: 10,
        newCount: 15,
        changeReason: 'User added 5 licenses'
    );

    $event = new SoftwareCountChanged($data, 'test-event-5');
    $listener = new AdjustBillingOnSoftwareCountChange;

    // Handle event
    $listener->handle($event);

    // Refresh template and verify NO update
    $template->refresh();
    expect($template->product_config['license_count'])->toBe(10); // Unchanged
});

test('event listeners are properly registered in service provider', function () {
    // Verify listeners are registered
    $listeners = Event::getListeners(ContractRevised::class);
    expect($listeners)->not->toBeEmpty();

    $listeners = Event::getListeners(SoftwareCountChanged::class);
    expect($listeners)->not->toBeEmpty();
});
