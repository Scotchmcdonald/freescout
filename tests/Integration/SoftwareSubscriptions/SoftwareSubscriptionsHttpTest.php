<?php

declare(strict_types=1);

namespace Tests\Integration\SoftwareSubscriptions;

use App\Models\User;
use Modules\Crm\Models\Client;
use Modules\SoftwareSubscriptions\Models\ClientSoftwareSubscription;
use Modules\SoftwareSubscriptions\Models\SoftwareProduct;
use Tests\IntegrationTestCase;

/**
 * HTTP smoke tests for SoftwareSubscriptions module controllers.
 *
 * Verifies auth protection and that admin can access all major routes.
 */
class SoftwareSubscriptionsHttpTest extends IntegrationTestCase
{
    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->user = User::factory()->create(['role' => User::ROLE_USER]);
    }

    // ─── Auth protection ──────────────────────────────────────────────────────

    public function test_index_requires_authentication(): void
    {
        $response = $this->get(route('admin.softwaresubscriptions.index'));
        $response->assertRedirect();
    }

    public function test_products_index_requires_authentication(): void
    {
        $response = $this->get(route('admin.softwaresubscriptions.products.index'));
        $response->assertRedirect();
    }

    // ─── Admin access ─────────────────────────────────────────────────────────

    public function test_index_accessible_by_admin(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.softwaresubscriptions.index'));
        $response->assertStatus(200);
    }

    public function test_create_accessible_by_admin(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.softwaresubscriptions.create'));
        $response->assertStatus(200);
    }

    public function test_catalog_accessible_by_admin(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.softwaresubscriptions.catalog'));
        $response->assertStatus(200);
    }

    public function test_products_index_accessible_by_admin(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.softwaresubscriptions.products.index'));
        $response->assertStatus(200);
    }

    public function test_products_create_accessible_by_admin(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.softwaresubscriptions.products.create'));
        $response->assertStatus(200);
    }

    public function test_vendor_cost_report_accessible_by_admin(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.softwaresubscriptions.reports.vendor_cost'));
        $response->assertStatus(200);
    }

    public function test_products_import_page_accessible_by_admin(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.softwaresubscriptions.products.import'));
        $response->assertStatus(200);
    }

    // ─── Show / Edit with specific record ─────────────────────────────────────

    public function test_subscription_show_page_accessible_by_admin(): void
    {
        $client = Client::factory()->create();
        $product = SoftwareProduct::factory()->create();
        $sub = ClientSoftwareSubscription::factory()->create([
            'client_id' => $client->id,
            'software_product_id' => $product->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.softwaresubscriptions.show', $sub->id));
        $response->assertStatus(200);
    }

    public function test_subscription_edit_page_accessible_by_admin(): void
    {
        $client = Client::factory()->create();
        $product = SoftwareProduct::factory()->create();
        $sub = ClientSoftwareSubscription::factory()->create([
            'client_id' => $client->id,
            'software_product_id' => $product->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.softwaresubscriptions.edit', $sub->id));
        $response->assertStatus(200);
    }

    public function test_products_show_accessible_by_admin(): void
    {
        $product = SoftwareProduct::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('admin.softwaresubscriptions.products.show', $product->id));
        $response->assertStatus(200);
    }

    public function test_products_edit_accessible_by_admin(): void
    {
        $product = SoftwareProduct::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('admin.softwaresubscriptions.products.edit', $product->id));
        $response->assertStatus(200);
    }

    // ─── 404 for non-existent resources ───────────────────────────────────────

    public function test_subscription_show_returns_404_for_missing(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.softwaresubscriptions.show', 999999));
        $response->assertStatus(404);
    }
}
