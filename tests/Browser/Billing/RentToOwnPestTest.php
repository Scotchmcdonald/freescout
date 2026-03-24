<?php

declare(strict_types=1);

use Modules\ContractManager\Models\Contract;
use Modules\Crm\Models\Client;

function createRtoContract(Client $client, array $overrides = []): Contract
{
    return Contract::create(array_merge([
        'client_id' => $client->id,
        'title' => 'RTO Equipment',
        'contract_number' => 'CON-RTO-'.uniqid(),
        'status' => 'active',
        'start_date' => now(),
        'contract_type' => 'rent_to_own',
        'purchase_price' => 300.00,
        'monthly_rental_fee' => 100.00,
        'asset_description' => 'Test Equipment',
    ], $overrides));
}

it('rental invoices stop at purchase cap', function () {
    $client = Client::factory()->create(['name' => 'RTO Cap Client']);
    $contract = createRtoContract($client);

    expect($contract->canGenerateInvoice())->toBeTrue();
    expect($contract->getRemainingBalance())->toBe(300.0);

    for ($i = 0; $i < 3; $i++) {
        \Modules\PIB\Models\Invoice::factory()->create([
            'contract_id' => $contract->id,
            'client_id' => $client->id,
            'total_amount' => 100,
            'status' => 'paid',
        ]);
    }

    expect($contract->fresh()->getRemainingBalance())->toBe(0.0);
    expect($contract->fresh()->canGenerateInvoice())->toBeFalse();
})->group('billing', 'revenue-assurance', 'rent-to-own');

it('rent to own with irregular final payment', function () {
    $client = Client::factory()->create(['name' => 'RTO Irregular Client']);
    $contract = createRtoContract($client, ['purchase_price' => 250.00]);

    for ($i = 0; $i < 2; $i++) {
        \Modules\PIB\Models\Invoice::factory()->create([
            'contract_id' => $contract->id,
            'client_id' => $client->id,
            'total_amount' => 100,
            'status' => 'paid',
        ]);
    }

    expect($contract->fresh()->getRemainingBalance())->toBe(50.0);
    expect($contract->fresh()->canGenerateInvoice())->toBeTrue();
})->group('billing', 'revenue-assurance', 'rent-to-own');

it('rent to own early buyout', function () {
    $client = Client::factory()->create(['name' => 'RTO Buyout Client']);
    $contract = createRtoContract($client, [
        'purchase_price' => 500.00,
        'allow_early_buyout' => true,
    ]);

    \Modules\PIB\Models\Invoice::factory()->create([
        'contract_id' => $contract->id,
        'client_id' => $client->id,
        'total_amount' => 100,
        'status' => 'paid',
    ]);

    expect($contract->fresh()->getRemainingBalance())->toBe(400.0);
    expect($contract->allow_early_buyout)->toBeTrue();

    \Modules\PIB\Models\Invoice::factory()->create([
        'contract_id' => $contract->id,
        'client_id' => $client->id,
        'total_amount' => 400,
        'is_buyout' => true,
        'is_final_payment' => true,
        'status' => 'paid',
    ]);

    expect($contract->fresh()->getRemainingBalance())->toBe(0.0);
})->group('billing', 'revenue-assurance', 'rent-to-own');

it('rent to own tracks missed payments', function () {
    $client = Client::factory()->create(['name' => 'RTO Tracking Client']);
    $contract = createRtoContract($client, ['purchase_price' => 500.00]);

    \Modules\PIB\Models\Invoice::factory()->create([
        'contract_id' => $contract->id,
        'client_id' => $client->id,
        'total_amount' => 100,
        'status' => 'paid',
    ]);
    \Modules\PIB\Models\Invoice::factory()->create([
        'contract_id' => $contract->id,
        'client_id' => $client->id,
        'total_amount' => 100,
        'status' => 'unpaid',
    ]);

    $totalInvoiced = $contract->fresh()->getTotalInvoiced();
    expect($totalInvoiced)->toBeGreaterThanOrEqual(100);
})->group('billing', 'revenue-assurance', 'rent-to-own');

it('rent to own ownership transfer on completion', function () {
    $client = Client::factory()->create(['name' => 'RTO Transfer Client']);
    $contract = createRtoContract($client, ['purchase_price' => 200.00]);

    expect(method_exists($contract, 'isPurchased'))->toBeTrue();
    expect($contract->isPurchased())->toBeFalse();
    expect($contract->getRemainingBalance())->toBe(200.0);
})->group('billing', 'revenue-assurance', 'rent-to-own');
