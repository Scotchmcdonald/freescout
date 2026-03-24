<?php

declare(strict_types=1);

namespace Tests\Integration\SoftwareSubscriptions;

use Modules\Crm\Models\Client;
use Modules\SoftwareSubscriptions\Models\ClientSoftwareSubscription;
use Modules\SoftwareSubscriptions\Models\SoftwareAssignment;
use Modules\SoftwareSubscriptions\Models\SoftwareProduct;
use Modules\SoftwareSubscriptions\Services\VendorReconciliationService;
use Tests\IntegrationTestCase;

class VendorReconciliationServiceTest extends IntegrationTestCase
{
    private VendorReconciliationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new VendorReconciliationService;
    }

    public function test_reconcile_subscription_reports_discrepancy_against_active_assignments(): void
    {
        $client = Client::factory()->create();
        $subscription = $this->createSubscription($client->id, 'SKU-RECON-1', 'Recon Product', 5);

        // Use explicit assignable_id values to avoid unique constraint collision on (subscription_id, assignable_type, assignable_id)
        SoftwareAssignment::factory()->forSubscription($subscription)->create(['revoked_at' => null, 'assignable_id' => 1]);
        SoftwareAssignment::factory()->forSubscription($subscription)->create(['revoked_at' => null, 'assignable_id' => 2]);
        SoftwareAssignment::factory()->forSubscription($subscription)->create(['revoked_at' => now(), 'assignable_id' => 3]);

        $result = $this->service->reconcileSubscription($subscription->fresh());

        $this->assertSame(5, $result['stored_count']);
        $this->assertSame(2, $result['actual_count']);
        $this->assertSame(3, $result['discrepancy']);
        $this->assertTrue($result['has_discrepancy']);
    }

    public function test_reconcile_all_returns_only_discrepant_subscriptions(): void
    {
        $client = Client::factory()->create();

        $matched = $this->createSubscription($client->id, 'SKU-MATCHED', 'Matched', 2);
        // Use sequence() for count() to generate explicit unique assignable_id values
        SoftwareAssignment::factory()->forSubscription($matched)->count(2)->sequence(
            ['assignable_id' => 101],
            ['assignable_id' => 102],
        )->create();

        $mismatch = $this->createSubscription($client->id, 'SKU-MISMATCH', 'Mismatch', 4);
        SoftwareAssignment::factory()->forSubscription($mismatch)->count(1)->sequence(
            ['assignable_id' => 201],
        )->create();

        $results = $this->service->reconcileAll();

        $this->assertCount(1, $results);
        $this->assertSame($mismatch->id, $results->first()['subscription_id']);
    }

    public function test_auto_fix_discrepancies_dry_run_does_not_persist_changes(): void
    {
        $client = Client::factory()->create();
        $subscription = $this->createSubscription($client->id, 'SKU-DRY', 'Dry Run', 6);
        SoftwareAssignment::factory()->forSubscription($subscription)->count(2)->sequence(
            ['assignable_id' => 301],
            ['assignable_id' => 302],
        )->create();

        $result = $this->service->autoFixDiscrepancies(true);
        $subscription->refresh();

        $this->assertTrue($result['dry_run']);
        $this->assertSame(1, $result['total_fixed']);
        $this->assertSame(6, $subscription->assigned_count);
        $this->assertSame(6, $result['fixed'][0]['would_fix_from']);
        $this->assertSame(2, $result['fixed'][0]['would_fix_to']);
    }

    public function test_auto_fix_discrepancies_updates_stored_count_when_not_dry_run(): void
    {
        $client = Client::factory()->create();
        $subscription = $this->createSubscription($client->id, 'SKU-FIX', 'Fix Run', 7);
        SoftwareAssignment::factory()->forSubscription($subscription)->count(3)->sequence(
            ['assignable_id' => 401],
            ['assignable_id' => 402],
            ['assignable_id' => 403],
        )->create();

        $result = $this->service->autoFixDiscrepancies(false);
        $subscription->refresh();

        $this->assertFalse($result['dry_run']);
        $this->assertSame(1, $result['total_fixed']);
        $this->assertSame(3, $subscription->assigned_count);
        $this->assertSame(7, $result['fixed'][0]['fixed_from']);
        $this->assertSame(3, $result['fixed'][0]['fixed_to']);
    }

    public function test_compare_with_vendor_report_handles_match_vendor_missing_and_not_tracked(): void
    {
        $client = Client::factory()->create();

        $matched = $this->createSubscription($client->id, 'SKU-MATCH', 'Match', 5);
        $vendorMissing = $this->createSubscription($client->id, 'SKU-MISSING', 'Vendor Missing', 2);

        // Suspended subscription should be excluded by active() scope.
        $suspendedProduct = SoftwareProduct::factory()->create(['sku' => 'SKU-SUSPENDED']);
        ClientSoftwareSubscription::factory()
            ->forClient($client->id)
            ->forProduct($suspendedProduct)
            ->suspended()
            ->create(['assigned_count' => 9]);

        $report = $this->service->compareWithVendorReport($client->id, [
            'SKU-MATCH' => 5,
            'SKU-EXTERNAL' => 12,
        ]);

        $this->assertSame($client->id, $report['client_id']);
        $this->assertSame(0, $report['mismatches']);
        $this->assertSame(1, $report['matches']);
        $this->assertSame(1, $report['not_tracked']);
        $this->assertSame(3, $report['total_comparisons']);

        $statusesBySku = collect($report['comparisons'])->mapWithKeys(function (array $row): array {
            return [($row['product_sku'] ?? 'unknown') => $row['status']];
        })->toArray();

        $this->assertSame('matched', $statusesBySku['SKU-MATCH']);
        $this->assertSame('vendor_not_reported', $statusesBySku['SKU-MISSING']);
        $this->assertSame('not_tracked', $statusesBySku['SKU-EXTERNAL']);

        // Guard to ensure active filter is respected.
        $this->assertArrayNotHasKey('SKU-SUSPENDED', $statusesBySku);
        $this->assertNotNull($matched);
        $this->assertNotNull($vendorMissing);
    }

    private function createSubscription(int $clientId, string $sku, string $name, int $assignedCount): ClientSoftwareSubscription
    {
        $product = SoftwareProduct::factory()->create([
            'sku' => $sku,
            'name' => $name,
        ]);

        return ClientSoftwareSubscription::factory()
            ->forClient($clientId)
            ->forProduct($product)
            ->create([
                'assigned_count' => $assignedCount,
                'status' => ClientSoftwareSubscription::STATUS_ACTIVE,
            ]);
    }
}
