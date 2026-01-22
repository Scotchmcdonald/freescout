<?php

/**
 * Test Data Factory Trait for Dusk Tests
 * 
 * Provides helper methods for creating test data with proper cleanup.
 * Use this trait in Dusk test classes that need to create entities.
 * 
 * USAGE:
 * ------
 * class MyDuskTest extends DuskTestCase
 * {
 *     use CreatesTestData;
 * 
 *     public function test_something(): void
 *     {
 *         $client = $this->createTestClient(['name' => 'Test Client']);
 *         // ... test logic
 *     }
 * }
 * 
 * MAINTENANCE NOTES:
 * ------------------
 * - Update model namespaces if they change
 * - Add new factory methods as modules are added
 * - These methods create data directly in DB, not via browser
 */

namespace Tests\Browser\Traits;

use Illuminate\Support\Facades\DB;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Contact;
use Modules\ContractManager\Models\Quote;
use Modules\ContractManager\Models\Contract;
use Modules\AssetManagement\Entities\Asset;

trait CreatesTestData
{
    /**
     * Track created entity IDs for cleanup.
     */
    protected static array $createdEntities = [];

    /**
     * Create a test client directly in database.
     * 
     * @param array $overrides Override default attributes
     * @return Client
     */
    protected function createTestClient(array $overrides = []): Client
    {
        $defaults = [
            'name' => 'DUSK-TestClient-' . uniqid(),
            'email' => 'dusk-' . uniqid() . '@test.example.com',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $attributes = array_merge($defaults, $overrides);
        
        // Use factory if available
        if (class_exists(Client::class) && method_exists(Client::class, 'factory')) {
            $client = Client::factory()->create($attributes);
        } else {
            // Direct insert fallback
            $client = Client::create($attributes);
        }

        self::$createdEntities['clients'][] = $client->id;
        
        return $client;
    }

    /**
     * Create a test contact directly in database.
     * 
     * @param int $clientId
     * @param array $overrides
     * @return Contact
     */
    protected function createTestContact(int $clientId, array $overrides = []): Contact
    {
        $defaults = [
            'client_id' => $clientId,
            'first_name' => 'DUSK',
            'last_name' => 'Contact-' . uniqid(),
            'email' => 'dusk-contact-' . uniqid() . '@test.example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $attributes = array_merge($defaults, $overrides);
        
        if (class_exists(Contact::class) && method_exists(Contact::class, 'factory')) {
            $contact = Contact::factory()->create($attributes);
        } else {
            $contact = Contact::create($attributes);
        }

        self::$createdEntities['contacts'][] = $contact->id;
        
        return $contact;
    }

    /**
     * Create a test asset directly in database.
     * 
     * @param array $overrides
     * @return Asset|null
     */
    protected function createTestAsset(array $overrides = []): ?Asset
    {
        if (!class_exists(Asset::class)) {
            return null;
        }

        $defaults = [
            'serial_number' => 'DUSK-' . strtoupper(uniqid()),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $attributes = array_merge($defaults, $overrides);
        
        if (method_exists(Asset::class, 'factory')) {
            $asset = Asset::factory()->create($attributes);
        } else {
            $asset = Asset::create($attributes);
        }

        self::$createdEntities['assets'][] = $asset->id;
        
        return $asset;
    }

    /**
     * Create a test quote directly in database.
     * 
     * @param int $clientId
     * @param array $overrides
     * @return Quote|null
     */
    protected function createTestQuote(int $clientId, array $overrides = []): ?Quote
    {
        if (!class_exists(Quote::class)) {
            return null;
        }

        $defaults = [
            'client_id' => $clientId,
            'title' => 'DUSK Test Quote ' . uniqid(),
            'status' => 'draft',
            'billing_type' => 'service_plan',
            'billing_cycle' => 'monthly',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $attributes = array_merge($defaults, $overrides);
        
        if (method_exists(Quote::class, 'factory')) {
            $quote = Quote::factory()->create($attributes);
        } else {
            $quote = Quote::create($attributes);
        }

        self::$createdEntities['quotes'][] = $quote->id;
        
        return $quote;
    }

    /**
     * Clean up all created test data.
     * Call this in tearDownAfterClass() or tearDown().
     */
    protected static function cleanupTestData(): void
    {
        // Delete in reverse order of dependencies
        
        if (!empty(self::$createdEntities['quotes'])) {
            if (class_exists(Quote::class)) {
                Quote::whereIn('id', self::$createdEntities['quotes'])->delete();
            }
        }

        if (!empty(self::$createdEntities['assets'])) {
            if (class_exists(Asset::class)) {
                Asset::whereIn('id', self::$createdEntities['assets'])->delete();
            }
        }

        if (!empty(self::$createdEntities['contacts'])) {
            if (class_exists(Contact::class)) {
                Contact::whereIn('id', self::$createdEntities['contacts'])->delete();
            }
        }

        if (!empty(self::$createdEntities['clients'])) {
            if (class_exists(Client::class)) {
                Client::whereIn('id', self::$createdEntities['clients'])->delete();
            }
        }

        self::$createdEntities = [];
    }

    /**
     * Get unique identifier for test run.
     */
    protected function getTestRunId(): string
    {
        return date('Ymd-His') . '-' . substr(uniqid(), -4);
    }
}
