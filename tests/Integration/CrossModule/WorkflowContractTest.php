<?php

declare(strict_types=1);

/**
 * B-2 Cross-Module Workflow Contract Tests
 *
 * Verifies that the event-dispatch seams between modules are correctly wired
 * and that each chain produces the expected persisted business outcome.
 *
 * These tests fire events through the real Laravel event system (not faked)
 * so they prove both the listener registration and the persistence contract.
 *
 * Chains tested:
 *   1. Action1 → AssetManagement → SoftwareSubscriptions
 *   2. GoogleAdmin → Crm (customer identity resolution)
 *   3. ContractManager ← PIB (InvoicePaid triggers ownership transfer)
 */

use App\DataTransferObjects\GoogleUserSyncedData;
use Modules\Action1\DataTransferObjects\Action1SoftwareDiscoveredData;
use Modules\Action1\Events\Action1SoftwareDiscovered;
use Modules\ContractManager\Models\Contract;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;
use Modules\GoogleAdmin\Events\GoogleUserSynced;
use Modules\PIB\Events\InvoicePaid;
use Modules\PIB\Models\Invoice;
use Modules\SoftwareSubscriptions\Models\SoftwareDiscovery;

// ─────────────────────────────────────────────────────────────────────────────
// Chain 1: Action1 → AssetManagement → SoftwareSubscriptions
//
// Contract: when Action1SoftwareDiscovered fires with an endpoint that maps to
// an Asset, the ReconcileAction1SoftwareDiscovery listener MUST persist a
// SoftwareDiscovery record with correct source, client, and asset linkage.
// ─────────────────────────────────────────────────────────────────────────────

it('Action1SoftwareDiscovered event causes SoftwareDiscovery persistence via the seam', function () {
    if (! class_exists('\Modules\AssetManagement\Entities\Asset')) {
        test()->markTestSkipped('AssetManagement module not available');
    }

    $company = Company::factory()->create();
    $client = Client::factory()->create(['company_id' => $company->id]);

    // Asset linked to an Action1 endpoint — the seam between Action1 and SoftwareSubscriptions
    $asset = \Modules\AssetManagement\Entities\Asset::create([
        'serial_number' => 'SN-WC-B2-001',
        'name' => 'B2 Test Station',
        'status' => 'active',
        'asset_type' => 'workstation',
        'source' => 'action1',
        'action1_endpoint_id' => 'ep-b2-chain1-001',
        'client_id' => $client->id,
    ]);

    $data = new Action1SoftwareDiscoveredData(
        endpointId: 'ep-b2-chain1-001',
        endpointName: 'B2 Test Station',
        softwareName: 'UnknownSoftwareB2 v3.0',
        version: '3.0.1',
        publisher: 'Unknown Corp',
        installDate: null,
        assetId: $asset->id,
        clientId: $client->id,
    );

    // Dispatch through the real event system — proves the listener registration seam
    event(new Action1SoftwareDiscovered($data));

    $discovery = SoftwareDiscovery::where('source_identifier', 'ep-b2-chain1-001')
        ->where('raw_software_name', 'UnknownSoftwareB2 v3.0')
        ->first();

    expect($discovery)->not->toBeNull()
        ->and($discovery->source)->toBe(SoftwareDiscovery::SOURCE_ACTION1)
        ->and($discovery->client_id)->toBe($client->id)
        ->and($discovery->asset_id)->toBe($asset->id)
        ->and($discovery->reconciliation_status)->toBe(SoftwareDiscovery::STATUS_UNRECOGNIZED);
});

// ─────────────────────────────────────────────────────────────────────────────
// Chain 2: GoogleAdmin → Crm
//
// Contract: when GoogleUserSynced fires for a previously unknown email, the
// GoogleUserSyncedListener MUST create a Customer record and its matching
// Email record — proving the GoogleAdmin→Crm identity resolution seam.
// ─────────────────────────────────────────────────────────────────────────────

it('GoogleUserSynced event causes Crm customer creation via the seam', function () {
    $uniqueEmail = 'wf-b2-chain2-'.uniqid().'@example.com';

    $dto = new GoogleUserSyncedData(
        clientId: 1,
        email: $uniqueEmail,
        firstName: 'WorkflowB2',
        lastName: 'Chain2User',
        googleId: 'google-b2-chain2-001',
        suspended: false,
        orgUnitPath: '/Engineering/QA',
        metadata: [],
    );

    // Dispatch through the real event system — proves the CrmServiceProvider
    // conditional listener registration seam for GoogleAdmin→Crm
    event(new GoogleUserSynced($dto));

    // Customer and Email records must be persisted by the listener
    test()->assertDatabaseHas('customers', [
        'first_name' => 'WorkflowB2',
        'last_name' => 'Chain2User',
    ]);

    test()->assertDatabaseHas('emails', [
        'email' => $uniqueEmail,
    ]);
});

// ─────────────────────────────────────────────────────────────────────────────
// Chain 3: ContractManager ← PIB (InvoicePaid)
//
// Contract: when InvoicePaid fires on a rent-to-own contract whose total paid
// reaches the purchase price, the TransferOwnershipOnPayment listener MUST
// flip ownership_status to 'transferred' and status to 'completed'.
//
// This test proves the PIB→ContractManager event-seam contract end-to-end,
// distinct from OwnershipTransferContractTest which tests the model layer.
// ─────────────────────────────────────────────────────────────────────────────

it('InvoicePaid event triggers ContractManager ownership transfer via the seam', function () {
    $client = Client::factory()->create();

    $contract = Contract::create([
        'client_id' => $client->id,
        'contract_number' => 'WC-B2-CHAIN3-001',
        'title' => 'B2 RTO Workflow Chain',
        'status' => 'active',
        'contract_type' => 'rent_to_own',
        'ownership_status' => 'pending',
        'purchase_price' => 300.00,
        'monthly_rental_fee' => 100.00,
        'start_date' => now(),
    ]);

    // Prior invoice already paid — puts total paid at 200
    Invoice::factory()->create([
        'client_id' => $client->id,
        'contract_id' => $contract->id,
        'total_amount' => 200.00,
        'status' => 'paid',
        'is_final_payment' => false,
        'is_buyout' => false,
    ]);

    // Final invoice that tips the total to the purchase price threshold
    $finalInvoice = Invoice::factory()->create([
        'client_id' => $client->id,
        'contract_id' => $contract->id,
        'total_amount' => 100.00,
        'status' => 'paid',
        'is_final_payment' => true,
        'is_buyout' => false,
    ]);

    // Dispatch InvoicePaid through the real event system — proves the
    // ContractManager EventServiceProvider seam for PIB→ContractManager
    event(new InvoicePaid($finalInvoice));

    $contract->refresh();

    expect($contract->ownership_status)->toBe('transferred')
        ->and($contract->status)->toBe('completed');
});
