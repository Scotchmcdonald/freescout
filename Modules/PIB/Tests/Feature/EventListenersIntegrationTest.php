<?php

use App\DataTransferObjects\AssetCountChangedData;
use Modules\AssetManagement\Events\AssetCountChanged;
use Modules\ContractManager\Events\ContractRevised;
use Modules\ContractManager\Models\BillingTemplate;
use Modules\ContractManager\Models\Contract;
use Modules\Crm\Models\Client;
use Modules\PIB\Listeners\RecalculateProrationOnContractChange;
use Modules\PIB\Listeners\UpdateEntitlementSnapshots;
use Modules\SoftwareSubscriptions\Events\SoftwareCountChanged;
use Modules\SoftwareSubscriptions\DataTransferObjects\SoftwareCountChangedData;
use Modules\PIB\Listeners\AdjustBillingOnSoftwareCountChange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Helper: create a Contract via ::create (no factory exists for Contract).
 */
function createTestContract(int $clientId, string $status = 'active'): Contract
{
    return Contract::create([
        'client_id' => $clientId,
        'contract_number' => 'CT-TEST-' . uniqid(),
        'start_date' => now()->subMonth(),
        'status' => $status,
    ]);
}

beforeEach(function () {
    // Enable event discovery
    Event::fake([
        ContractRevised::class,
        AssetCountChanged::class,
        SoftwareCountChanged::class,
    ]);
});

describe('PIB Event Listeners', function () {
    
    test('RecalculateProrationOnContractChange marks templates for proration', function () {
        $client = Client::factory()->create();
        $contract = createTestContract($client->id, 'active');
        
        $template = BillingTemplate::factory()->create([
            'client_id' => $client->id,
            'contract_id' => $contract->id,
            'status' => 'active',
            'next_invoice_date' => now()->addDays(15),
        ]);

        // Dispatch event
        $event = new ContractRevised(
            $contract,
            ['monthly_amount' => ['old' => 100, 'new' => 150]],
            'test-event-1'
        );

        $listener = new RecalculateProrationOnContractChange();
        $listener->handle($event);

        // Verify template is marked for proration
        $template->refresh();
        $config = $template->product_config ?? [];
        
        expect($config)->toHaveKey('proration_pending')
            ->and($config['proration_pending'])->toBeTrue()
            ->and($config)->toHaveKey('proration_effective_date');
    });

    test('RecalculateProrationOnContractChange skips inactive contracts', function () {
        $client = Client::factory()->create();
        $contract = createTestContract($client->id, 'draft');
        
        $template = BillingTemplate::factory()->create([
            'client_id' => $client->id,
            'contract_id' => $contract->id,
            'status' => 'active',
        ]);

        // Dispatch event
        $event = new ContractRevised(
            $contract,
            ['monthly_amount' => ['old' => 100, 'new' => 150]],
            'test-event-2'
        );

        $listener = new RecalculateProrationOnContractChange();
        $listener->handle($event);

        // Verify template is NOT marked for proration
        $template->refresh();
        $config = $template->product_config ?? [];
        
        expect($config)->not->toHaveKey('proration_pending');
    });

    test('UpdateEntitlementSnapshots updates asset count', function () {
        $client = Client::factory()->create();
        
        $template = BillingTemplate::factory()->create([
            'client_id' => $client->id,
            'status' => 'active',
            'product_type' => 'hardware',
        ]);

        // Create AssetCountChangedData
        $data = new AssetCountChangedData(
            clientId: $client->id,
            assetType: 'workstation',
            previousCount: 10,
            newCount: 15,
            changeReason: 'asset_added',
        );

        // Dispatch event
        $event = new AssetCountChanged($data, 'test-event-3');

        $listener = new UpdateEntitlementSnapshots();
        $listener->handle($event);

        // Verify template is marked for recalculation
        $template->refresh();
        $config = $template->product_config ?? [];
        
        expect($config)->toHaveKey('asset_count_changed')
            ->and($config['asset_count_changed'])->toBeTrue()
            ->and($config['previous_count'])->toBe(10)
            ->and($config['current_count'])->toBe(15);
    });

    test('UpdateEntitlementSnapshots skips non-asset templates', function () {
        $client = Client::factory()->create();
        
        $template = BillingTemplate::factory()->create([
            'client_id' => $client->id,
            'status' => 'active',
            'product_type' => 'software', // Not asset-based
        ]);

        $originalConfig = $template->product_config;

        // Create AssetCountChangedData
        $data = new AssetCountChangedData(
            clientId: $client->id,
            assetType: 'workstation',
            previousCount: 10,
            newCount: 15,
            changeReason: 'asset_added',
        );

        // Dispatch event
        $event = new AssetCountChanged($data, 'test-event-4');

        $listener = new UpdateEntitlementSnapshots();
        $listener->handle($event);

        // Verify template config is unchanged
        $template->refresh();
        expect($template->product_config)->toBe($originalConfig);
    });

    test('AdjustBillingOnSoftwareCountChange updates license count', function () {
        $client = Client::factory()->create();
        
        $subscriptionId = 123;
        
        $template = BillingTemplate::factory()->create([
            'client_id' => $client->id,
            'status' => 'active',
            'product_type' => 'software',
            'product_config' => [
                'subscription_id' => $subscriptionId,
                'license_count' => 5,
            ],
        ]);

        // Create SoftwareCountChanged event data
        $data = new SoftwareCountChangedData(
            subscriptionId: $subscriptionId,
            clientId: $client->id,
            softwareProductId: 1,
            productName: 'Test Software',
            previousCount: 5,
            newCount: 8,
            changeReason: 'license_added',
        );

        // Dispatch event
        $event = new SoftwareCountChanged($data, 'test-event-5');

        $listener = new AdjustBillingOnSoftwareCountChange();
        $listener->handle($event);

        // Verify template license count is updated
        $template->refresh();
        $config = $template->product_config ?? [];
        
        expect($config['license_count'])->toBe(8)
            ->and($config['previous_count'])->toBe(5)
            ->and($config)->toHaveKey('count_changed_at');
    });

    test('all event listeners are idempotent', function () {
        $client = Client::factory()->create();
        $contract = createTestContract($client->id, 'active');
        
        $template = BillingTemplate::factory()->create([
            'client_id' => $client->id,
            'contract_id' => $contract->id,
            'status' => 'active',
            'next_invoice_date' => now()->addDays(15),
        ]);

        // Create event with explicit ID
        $eventId = 'test-idempotency-event-1';
        $event = new ContractRevised($contract, ['test' => 'change'], $eventId);

        $listener = new RecalculateProrationOnContractChange();

        // First execution
        $listener->handle($event);
        $template->refresh();
        $firstConfig = $template->product_config;

        // Second execution with same event ID
        $listener->handle($event);
        $template->refresh();
        $secondConfig = $template->product_config;

        // Verify no duplicate processing
        expect($secondConfig)->toBe($firstConfig);
        
        // Verify processed_events table has entry
        $processed = DB::table('processed_events')
            ->where('event_id', $eventId)
            ->where('handler_class', RecalculateProrationOnContractChange::class)
            ->exists();
        expect($processed)->toBeTrue();
    });

    test('event listeners are registered in PIBServiceProvider', function () {
        // Verify listener registrations
        $listeners = Event::getListeners(ContractRevised::class);
        expect($listeners)->not->toBeEmpty();

        $listeners = Event::getListeners(AssetCountChanged::class);
        expect($listeners)->not->toBeEmpty();

        $listeners = Event::getListeners(SoftwareCountChanged::class);
        expect($listeners)->not->toBeEmpty();
    });

});
