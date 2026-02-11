<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Modules\ContractManager\Events\ContractRevised;
use Modules\ContractManager\Models\BillingTemplate;
use Modules\ContractManager\Models\Contract;
use Modules\Crm\Models\Client;
use Modules\PIB\Listeners\RecalculateProrationOnContractChange;
use Modules\SoftwareSubscriptions\DataTransferObjects\SoftwareCountChangedData;
use Modules\SoftwareSubscriptions\Events\SoftwareCountChanged;
use Modules\PIB\Listeners\AdjustBillingOnSoftwareCountChange;

uses(RefreshDatabase::class);

beforeEach(function () {
    Log::spy();
});

test('contract revised event triggers proration recalculation for active contracts', function () {
    // Create real client and contract to satisfy FK constraints
    $client = Client::factory()->create();
    $realContract = Contract::create([
        'client_id' => $client->id,
        'contract_number' => 'TEST-EL-001',
        'start_date' => now(),
        'status' => 'active',
    ]);

    // Create mock contract matching real IDs
    $contract = Mockery::mock(Contract::class)->makePartial();
    $contract->id = $realContract->id;
    $contract->client_id = $client->id;
    $contract->status = 'active';

    $template = BillingTemplate::factory()->create([
        'contract_id' => $realContract->id,
        'client_id' => $client->id,
        'status' => 'active',
        'next_invoice_date' => now()->addDays(15), // Mid-cycle
    ]);

    $event = new ContractRevised($contract, ['monthly_cost' => 100], 'test-event-1');
    $listener = new RecalculateProrationOnContractChange();

    // Handle event
    $listener->handle($event);

    // Verify logging occurred
    Log::shouldHaveReceived('info')
        ->with('PIB: Contract revised, checking proration', Mockery::type('array'))
        ->once();
});

test('contract revised event skips proration for inactive contracts', function () {
    // Create real client for FK, but mock contract is terminated so no DB lookup needed
    $client = Client::factory()->create();
    $realContract = Contract::create([
        'client_id' => $client->id,
        'contract_number' => 'TEST-EL-002',
        'start_date' => now(),
        'status' => 'terminated',
    ]);

    // Create mock contract
    $contract = Mockery::mock(Contract::class)->makePartial();
    $contract->id = $realContract->id;
    $contract->client_id = $client->id;
    $contract->status = 'terminated';

    $event = new ContractRevised($contract, ['monthly_cost' => 100], 'test-event-2');
    $listener = new RecalculateProrationOnContractChange();

    // Handle event
    $listener->handle($event);

    // Verify skipped due to status
    Log::shouldHaveReceived('info')
        ->with('PIB: Skipping proration - contract not active', Mockery::type('array'))
        ->once();
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
    $contract = Mockery::mock(Contract::class)->makePartial();
    $contract->id = $realContract->id;
    $contract->client_id = $client->id;
    $contract->status = 'active';

    // No active templates — use 'terminated' (valid enum value, not 'cancelled')
    BillingTemplate::factory()->create([
        'contract_id' => $realContract->id,
        'client_id' => $client->id,
        'status' => 'terminated',
    ]);

    $event = new ContractRevised($contract, ['monthly_cost' => 100], 'test-event-3');
    $listener = new RecalculateProrationOnContractChange();

    // Handle event
    $listener->handle($event);

    // Verify skipped due to no active templates
    Log::shouldHaveReceived('info')
        ->with('PIB: No active billing templates for contract', Mockery::type('array'))
        ->once();
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
    $listener = new AdjustBillingOnSoftwareCountChange();

    // Handle event
    $listener->handle($event);
    
    // Verify logging occurred
    Log::shouldHaveReceived('info')
        ->with('PIB: Software count changed, adjusting billing', Mockery::type('array'))
        ->once();
    
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
    $listener = new AdjustBillingOnSoftwareCountChange();

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
