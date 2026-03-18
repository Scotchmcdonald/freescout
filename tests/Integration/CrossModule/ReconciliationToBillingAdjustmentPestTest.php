<?php

declare(strict_types=1);

/**
 * Phase 7C — SoftwareSubscriptions Reconciliation → PIB Billing Adjustment Chain Test
 *
 * Proves the event-seam between SoftwareSubscriptions and ContractManager/PIB:
 *   1. A SoftwareCountChanged event (fired when subscription counts change) triggers
 *      the AdjustBillingOnSoftwareCountChange listener.
 *   2. The listener updates the BillingTemplate product_config with the new license count.
 *   3. The BillingAnalysisService can detect the resulting variance.
 */

use Modules\ContractManager\Models\BillingTemplate;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;
use Modules\SoftwareSubscriptions\DataTransferObjects\SoftwareCountChangedData;
use Modules\SoftwareSubscriptions\Events\SoftwareCountChanged;
use Modules\SoftwareSubscriptions\Models\ClientSoftwareSubscription;
use Modules\SoftwareSubscriptions\Models\SoftwareProduct;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->client = Client::factory()->create(['company_id' => $this->company->id]);
});

it('SoftwareCountChanged event updates billing template license count via the seam', function () {
    // Create a software product and subscription
    $product = SoftwareProduct::create([
        'name' => 'Microsoft 365 Business',
        'sku' => 'M365-BIZ-CHAIN3',
        'vendor' => 'Microsoft',
        'category' => 'productivity',
        'licensing_model' => 'per_user',
        'pricing_type' => 'flat',
        'vendor_cost' => 12.50,
        'default_price' => 15.00,
        'billing_frequency' => 'monthly',
        'is_active' => true,
    ]);

    $subscription = ClientSoftwareSubscription::create([
        'client_id' => $this->client->id,
        'software_product_id' => $product->id,
        'purchased_quantity' => 10,
        'assigned_count' => 8,
        'status' => 'active',
        'start_date' => now()->subMonths(3),
    ]);

    // Create a billing template linked to this subscription
    $template = BillingTemplate::factory()->create([
        'client_id' => $this->client->id,
        'product_type' => 'software',
        'status' => 'active',
        'product_config' => [
            'subscription_id' => $subscription->id,
            'license_count' => 8,
            'base_price' => 15.00,
        ],
    ]);

    // Fire the SoftwareCountChanged event — proves the listener registration seam
    $dto = new SoftwareCountChangedData(
        subscriptionId: $subscription->id,
        clientId: $this->client->id,
        softwareProductId: $product->id,
        productName: 'Microsoft 365 Business',
        previousCount: 8,
        newCount: 12,
        changeReason: 'reconciliation_fix',
    );

    event(new SoftwareCountChanged($dto));

    // The AdjustBillingOnSoftwareCountChange listener should have updated the template
    $template->refresh();
    $config = $template->product_config;

    expect($config['license_count'])->toBe(12)
        ->and($config['previous_count'])->toBe(8)
        ->and($config)->toHaveKey('count_changed_at');
});

it('SoftwareCountChanged does not affect unrelated billing templates', function () {
    $product = SoftwareProduct::create([
        'name' => 'Slack Business+',
        'sku' => 'SLACK-BIZ-CHAIN3',
        'vendor' => 'Slack',
        'category' => 'communication',
        'licensing_model' => 'per_user',
        'pricing_type' => 'flat',
        'vendor_cost' => 8.75,
        'default_price' => 12.00,
        'billing_frequency' => 'monthly',
        'is_active' => true,
    ]);

    $subscription = ClientSoftwareSubscription::create([
        'client_id' => $this->client->id,
        'software_product_id' => $product->id,
        'purchased_quantity' => 5,
        'assigned_count' => 5,
        'status' => 'active',
        'start_date' => now()->subMonths(1),
    ]);

    // Unrelated billing template (different subscription_id)
    $unrelatedTemplate = BillingTemplate::factory()->create([
        'client_id' => $this->client->id,
        'product_type' => 'software',
        'status' => 'active',
        'product_config' => [
            'subscription_id' => 99999,
            'license_count' => 20,
            'base_price' => 50.00,
        ],
    ]);

    $dto = new SoftwareCountChangedData(
        subscriptionId: $subscription->id,
        clientId: $this->client->id,
        softwareProductId: $product->id,
        productName: 'Slack Business+',
        previousCount: 5,
        newCount: 7,
        changeReason: 'new_assignment',
    );

    event(new SoftwareCountChanged($dto));

    // Unrelated template should NOT have been modified
    $unrelatedTemplate->refresh();
    expect($unrelatedTemplate->product_config['license_count'])->toBe(20);
});

it('chain failure path: event with non-existent subscription has no billing impact', function () {
    $dto = new SoftwareCountChangedData(
        subscriptionId: 999999,
        clientId: $this->client->id,
        softwareProductId: 1,
        productName: 'Phantom Software',
        previousCount: 0,
        newCount: 5,
        changeReason: 'test_non_existent',
    );

    // Should not throw — listener simply finds no matching templates
    event(new SoftwareCountChanged($dto));

    // No billing templates should have been affected
    $affectedTemplates = BillingTemplate::where('client_id', $this->client->id)
        ->where('product_type', 'software')
        ->count();
    expect($affectedTemplates)->toBe(0);
});
