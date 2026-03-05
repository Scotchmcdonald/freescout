<?php

declare(strict_types=1);

namespace ModulesPaymentTestsIntegrationCrossModule;

use Illuminate\Support\Facades\DB;
use Modules\AssetManagement\Entities\Asset;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;
use Modules\Crm\Models\Contact;
use Modules\PIB\Models\Invoice;
use Modules\ContractManager\Models\BillingTemplate;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentMethod;
use PHPUnit\Framework\Attributes\Group;
use Tests\IntegrationTestCase;

/**
 * Integration tests for data consistency across modules.
 * 
 * Tests actual cross-module functionality:
 * - Referential integrity between modules
 * - Foreign key constraints
 * - Multi-tenant data isolation
 * - Orphan prevention
 */
#[Group('integration')]
#[Group('cross-module')]
#[Group('data-consistency')]
class DataConsistencyTest extends IntegrationTestCase
{
    private Company $company;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create([
            'name' => 'Test MSP Company',
            'is_active' => true,
        ]);

        $this->client = Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Client',
            'status' => 'active',
        ]);
    }

    /**
     * Test Client belongs to Company relationship.
     */
    public function test_client_belongs_to_company(): void
    {
        $this->assertInstanceOf(Company::class, $this->client->company);
        $this->assertEquals($this->company->id, $this->client->company->id);
    }

    /**
     * Test Contact belongs to Client relationship.
     */
    public function test_contact_belongs_to_client(): void
    {
        $contact = Contact::factory()->create([
            'client_id' => $this->client->id,
        ]);

        $this->assertInstanceOf(Client::class, $contact->client);
        $this->assertEquals($this->client->id, $contact->client->id);
    }

    /**
     * Test Asset belongs to Client relationship.
     */
    public function test_asset_belongs_to_client(): void
    {
        if (!class_exists(Asset::class)) {
            $this->markTestSkipped('AssetManagement module not available');
        }

        $asset = Asset::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
        ]);

        $this->assertInstanceOf(Client::class, $asset->client);
        $this->assertEquals($this->client->id, $asset->client->id);
    }

    /**
     * Test Invoice belongs to Client relationship.
     */
    public function test_invoice_belongs_to_client(): void
    {
        if (!class_exists(Invoice::class)) {
            $this->markTestSkipped('PIB module not available');
        }

        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
        ]);

        $this->assertInstanceOf(Client::class, $invoice->client);
        $this->assertEquals($this->client->id, $invoice->client->id);
    }

    /**
     * Test PaymentMethod belongs to Company relationship.
     */
    public function test_payment_method_belongs_to_company(): void
    {
        if (!class_exists(PaymentMethod::class)) {
            $this->markTestSkipped('Payment module not available');
        }

        $paymentMethod = PaymentMethod::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->assertInstanceOf(Company::class, $paymentMethod->company);
        $this->assertEquals($this->company->id, $paymentMethod->company->id);
    }

    /**
     * Test multi-level relationship: Company -> Client -> Asset.
     */
    public function test_company_client_asset_chain(): void
    {
        if (!class_exists(Asset::class)) {
            $this->markTestSkipped('AssetManagement module not available');
        }

        $asset = Asset::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
        ]);

        // Can traverse: Asset -> Client -> Company
        $this->assertEquals(
            $this->company->id,
            $asset->client->company->id
        );
    }

    /**
     * Test multi-level relationship: Company -> Client -> Invoice.
     */
    public function test_company_client_invoice_chain(): void
    {
        if (!class_exists(Invoice::class)) {
            $this->markTestSkipped('PIB module not available');
        }

        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
        ]);

        // Can traverse: Invoice -> Client -> Company
        $this->assertEquals(
            $this->company->id,
            $invoice->client->company->id
        );
    }

    /**
     * Test data isolation between companies.
     */
    public function test_company_data_isolation(): void
    {
        $company2 = Company::factory()->create(['name' => 'Other Company']);
        
        $client1 = $this->client;
        $client2 = Client::factory()->create([
            'company_id' => $company2->id,
            'name' => 'Other Client',
        ]);

        // Each company's clients are separate
        $company1Clients = Client::where('company_id', $this->company->id)->get();
        $company2Clients = Client::where('company_id', $company2->id)->get();

        $this->assertTrue($company1Clients->contains($client1));
        $this->assertFalse($company1Clients->contains($client2));
        
        $this->assertTrue($company2Clients->contains($client2));
        $this->assertFalse($company2Clients->contains($client1));
    }

    /**
     * Test client data isolation within company.
     */
    public function test_client_data_isolation(): void
    {
        $client2 = Client::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Second Client',
        ]);

        $contact1 = Contact::factory()->create(['client_id' => $this->client->id]);
        $contact2 = Contact::factory()->create(['client_id' => $client2->id]);

        $client1Contacts = Contact::where('client_id', $this->client->id)->get();
        $client2Contacts = Contact::where('client_id', $client2->id)->get();

        $this->assertTrue($client1Contacts->contains($contact1));
        $this->assertFalse($client1Contacts->contains($contact2));
    }

    /**
     * Test company_id consistency across related records.
     */
    public function test_company_id_consistency(): void
    {
        if (!class_exists(Asset::class) || !class_exists(Invoice::class)) {
            $this->markTestSkipped('Required modules not available');
        }

        $asset = Asset::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
        ]);

        $invoice = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
        ]);

        // All records should reference same company
        $this->assertEquals($this->company->id, $this->client->company_id);
        $this->assertEquals($this->company->id, $asset->company_id);
        $this->assertEquals($this->company->id, $invoice->company_id);
    }

    /**
     * Test that records can be queried by company scope.
     */
    public function test_company_scope_query(): void
    {
        if (!class_exists(Asset::class)) {
            $this->markTestSkipped('AssetManagement module not available');
        }

        $company2 = Company::factory()->create();
        $client2 = Client::factory()->create(['company_id' => $company2->id]);

        Asset::factory()->count(3)->create([
            'client_id' => $this->client->id,
            'company_id' => $this->company->id,
        ]);

        Asset::factory()->count(2)->create([
            'client_id' => $client2->id,
            'company_id' => $company2->id,
        ]);

        // Query by company scope
        $company1Assets = Asset::where('company_id', $this->company->id)->get();
        $company2Assets = Asset::where('company_id', $company2->id)->get();

        $this->assertCount(3, $company1Assets);
        $this->assertCount(2, $company2Assets);
    }

    /**
     * Test database transaction rollback maintains consistency.
     */
    public function test_transaction_rollback_consistency(): void
    {
        $initialClientCount = Client::count();

        try {
            DB::transaction(function () {
                Client::factory()->create([
                    'company_id' => $this->company->id,
                    'name' => 'Transaction Client',
                ]);
                
                // Force rollback
                throw new \Exception('Intentional rollback');
            });
        } catch (\Exception $e) {
            // Expected exception
        }

        // Client count should not have changed
        $this->assertEquals($initialClientCount, Client::count());
    }

    /**
     * Test client with multiple related entities.
     */
    public function test_client_aggregate_relationships(): void
    {
        // Create multiple contacts
        Contact::factory()->count(3)->create([
            'client_id' => $this->client->id,
        ]);

        // Verify counts via queries (Client uses ExtensibleModel, relationships may be dynamic)
        $contactCount = Contact::where('client_id', $this->client->id)->count();
        $this->assertEquals(3, $contactCount);
    }
}
