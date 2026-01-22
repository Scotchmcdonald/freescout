<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\ClientUser;
use Tests\DuskTestCase;

/**
 * Multi-User Test Case Base Class
 * 
 * Provides helper methods for testing workflows involving multiple users:
 * - Admin users (internal staff)
 * - Client users (portal access)
 * 
 * USAGE:
 * 
 * class MyMultiUserTest extends MultiUserTestCase
 * {
 *     public function test_admin_and_client_interaction(): void
 *     {
 *         $this->browse(function (Browser $admin, Browser $client) {
 *             // Admin creates something
 *             $this->loginAsAdmin($admin)
 *                 ->visit('/admin/quotes/create')
 *                 ->createQuote();
 *             
 *             // Client views it in portal
 *             $this->loginAsClient($client)
 *                 ->visit('/portal/dashboard')
 *                 ->assertSee('Quote');
 *         });
 *     }
 * }
 */
abstract class MultiUserTestCase extends DuskTestCase
{
    /**
     * Admin user for tests
     */
    protected ?User $adminUser = null;

    /**
     * Client for tests
     */
    protected ?Client $testClient = null;

    /**
     * Client portal user for tests
     */
    protected ?ClientUser $clientUser = null;

    /**
     * Set up multi-user test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // These will be created on-demand via getters
        $this->adminUser = null;
        $this->testClient = null;
        $this->clientUser = null;
    }

    /**
     * Get or create admin user
     */
    protected function getAdminUser(): User
    {
        if (!$this->adminUser) {
            $this->adminUser = User::where('email', 'admin@example.com')
                ->orWhere('role', User::ROLE_ADMIN)
                ->first();
            
            if (!$this->adminUser) {
                $this->adminUser = User::factory()->create([
                    'email' => 'admin@example.com',
                    'role' => User::ROLE_ADMIN,
                    'password' => bcrypt('password'),
                ]);
            }
        }
        
        return $this->adminUser;
    }

    /**
     * Get or create test client
     */
    protected function getTestClient(): Client
    {
        if (!$this->testClient) {
            $this->testClient = Client::factory()->create([
                'name' => 'Test Client ' . now()->format('His'),
                'email' => 'testclient' . now()->format('His') . '@example.com',
                'status' => 'active',
            ]);
        }
        
        return $this->testClient;
    }

    /**
     * Get or create client portal user
     */
    protected function getClientUser(?Client $client = null): ClientUser
    {
        $client = $client ?? $this->getTestClient();
        
        if (!$this->clientUser || $this->clientUser->client_id !== $client->id) {
            $this->clientUser = ClientUser::factory()->create([
                'client_id' => $client->id,
                'name' => 'Portal User',
                'email' => 'portal' . now()->format('His') . '@example.com',
                'password' => bcrypt('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }
        
        return $this->clientUser;
    }

    /**
     * Login browser as admin user
     */
    protected function loginAsAdmin(Browser $browser): Browser
    {
        return $browser->loginAs($this->getAdminUser());
    }

    /**
     * Login browser as client portal user
     */
    protected function loginAsClient(Browser $browser, ?ClientUser $clientUser = null): Browser
    {
        $user = $clientUser ?? $this->getClientUser();
        
        return $browser->visit('/portal/login')
            ->type('email', $user->email)
            ->type('password', 'password')
            ->press('Sign in')
            ->waitForLocation('/portal/dashboard', 10)
            ->pause(500);
    }

    /**
     * Create a new client with portal user
     * 
     * @return array{client: Client, user: ClientUser}
     */
    protected function createClientWithPortalUser(array $clientAttributes = [], array $userAttributes = []): array
    {
        $client = Client::factory()->create(array_merge([
            'status' => 'active',
        ], $clientAttributes));

        $user = ClientUser::factory()->create(array_merge([
            'client_id' => $client->id,
            'is_active' => true,
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ], $userAttributes));

        return [
            'client' => $client,
            'user' => $user,
        ];
    }

    /**
     * Logout client portal user
     */
    protected function logoutClient(Browser $browser): Browser
    {
        return $browser->visit('/portal/dashboard')
            ->click('@logout-button')
            ->waitForLocation('/portal/login', 5);
    }

    /**
     * Assert browser is on client portal
     */
    protected function assertOnPortal(Browser $browser): void
    {
        $browser->assertPathBeginsWith('/portal');
    }

    /**
     * Assert browser is on admin area
     */
    protected function assertOnAdmin(Browser $browser): void
    {
        $browser->assertPathBeginsWith('/admin');
    }

    /**
     * Wait for notification/toast message
     */
    protected function waitForNotification(Browser $browser, string $expectedText = null): Browser
    {
        $browser->waitFor('.notification, .toast, .alert-success, .alert-info', 5);
        
        if ($expectedText) {
            $browser->assertSee($expectedText);
        }
        
        return $browser;
    }

    /**
     * Clean up after test
     */
    protected function tearDown(): void
    {
        // Clean up test data if needed
        if ($this->clientUser) {
            $this->clientUser->delete();
        }
        
        if ($this->testClient) {
            $this->testClient->delete();
        }
        
        parent::tearDown();
    }
}
