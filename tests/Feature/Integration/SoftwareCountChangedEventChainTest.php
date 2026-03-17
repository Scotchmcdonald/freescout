<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ContractManager\Models\BillingTemplate;
use Modules\Crm\Models\Client;
use Modules\SoftwareSubscriptions\DataTransferObjects\SoftwareCountChangedData;
use Modules\SoftwareSubscriptions\Events\SoftwareCountChanged;

uses(RefreshDatabase::class);

test('software count changed triggers both billing adjustment and entitlement snapshot update', function () {
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

    event(new SoftwareCountChanged(
        new SoftwareCountChangedData(
            subscriptionId: 123,
            clientId: $client->id,
            softwareProductId: 1,
            productName: 'Test Product',
            previousCount: 10,
            newCount: 15,
            changeReason: 'Integration test',
        ),
        'evt-chain-001'
    ));

    $template->refresh();
    $config = $template->product_config;

    expect($config['license_count'] ?? null)->toBe(15)
        ->and($config['previous_count'] ?? null)->toBe(10)
        ->and($config['current_count'] ?? null)->toBe(15)
        ->and($config['entitlement_snapshot_updated_at'] ?? null)->not->toBeNull();
});
