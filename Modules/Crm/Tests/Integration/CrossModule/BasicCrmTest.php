<?php

declare(strict_types=1);

namespace ModulesCrmTestsIntegrationCrossModule;

use Illuminate\Support\Facades\Event;
use Modules\Crm\Events\ClientCreated;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;
use PHPUnit\Framework\Attributes\Group;
use Tests\IntegrationTestCase;

#[Group('integration')]
#[Group('cross-module')]
#[Group('crm-basic')]
class BasicCrmTest extends IntegrationTestCase
{
    /**
     * Test that we can create a company.
     */
    public function test_can_create_company(): void
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
        ]);

        $this->assertDatabaseHas('companies', [
            'name' => 'Test Company',
        ]);

        $this->assertNotNull($company->id);
        $this->assertEquals('Test Company', $company->name);
    }

    /**
     * Test that we can create a client.
     */
    public function test_can_create_client(): void
    {
        $company = Company::factory()->create();

        $client = Client::factory()->create([
            'company_id' => $company->id,
            'name' => 'Test Client',
        ]);

        $this->assertDatabaseHas('clients', [
            'name' => 'Test Client',
            'company_id' => $company->id,
        ]);

        $this->assertEquals('Test Client', $client->name);
        $this->assertEquals($company->id, $client->company_id);
    }

    /**
     * Test client belongs to company relationship.
     */
    public function test_client_belongs_to_company(): void
    {
        $company = Company::factory()->create([
            'name' => 'Parent Company',
        ]);

        $client = Client::factory()->create([
            'company_id' => $company->id,
        ]);

        $this->assertInstanceOf(Company::class, $client->company);
        $this->assertEquals($company->id, $client->company->id);
        $this->assertEquals('Parent Company', $client->company->name);
    }

    /**
     * Test that client creation event is dispatched.
     */
    public function test_client_creation_dispatches_event(): void
    {
        Event::fake([ClientCreated::class]);

        $company = Company::factory()->create();

        $client = Client::factory()->create([
            'company_id' => $company->id,
        ]);

        Event::assertDispatched(ClientCreated::class, function ($event) use ($client) {
            return $event->data->clientId === $client->id;
        });
    }

    /**
     * Test that multiple clients can belong to same company.
     */
    public function test_company_has_multiple_clients(): void
    {
        $company = Company::factory()->create();

        Client::factory()->create(['company_id' => $company->id]);
        Client::factory()->create(['company_id' => $company->id]);
        Client::factory()->create(['company_id' => $company->id]);

        $clientCount = Client::where('company_id', $company->id)->count();
        $this->assertEquals(3, $clientCount);
    }

    /**
     * Test client deletion.
     */
    public function test_client_can_be_deleted(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $clientId = $client->id;

        $client->delete();

        // Should not appear in queries after hard delete
        $this->assertNull(Client::find($clientId));
        
        // Verify it's actually gone from database
        $this->assertDatabaseMissing('clients', ['id' => $clientId]);
    }

    /**
     * Test company isolation between different companies.
     */
    public function test_company_data_isolation(): void
    {
        $company1 = Company::factory()->create(['name' => 'Company 1']);
        $company2 = Company::factory()->create(['name' => 'Company 2']);

        $client1 = Client::factory()->create(['company_id' => $company1->id]);
        $client2 = Client::factory()->create(['company_id' => $company2->id]);

        // Company 1 clients shouldn't include company 2 clients
        $company1Clients = Client::where('company_id', $company1->id)->get();
        $this->assertCount(1, $company1Clients);
        $this->assertTrue($company1Clients->contains($client1));
        $this->assertFalse($company1Clients->contains($client2));

        // Company 2 clients shouldn't include company 1 clients
        $company2Clients = Client::where('company_id', $company2->id)->get();
        $this->assertCount(1, $company2Clients);
        $this->assertTrue($company2Clients->contains($client2));
        $this->assertFalse($company2Clients->contains($client1));
    }

    /**
     * Test timestamp handling.
     */
    public function test_timestamps_are_set_correctly(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $this->assertNotNull($client->created_at);
        $this->assertNotNull($client->updated_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $client->created_at);
    }
}
