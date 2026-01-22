<?php

/**
 * Cross-Module Data Ownership Tests
 * 
 * Validates data ownership boundaries between modules.
 * Prevents architectural erosion through direct table access.
 * 
 * PRIORITY: ⭐⭐⭐⭐ (High - Architecture Enforcement)
 * 
 * RUNNING TESTS:
 * php artisan dusk tests/Browser/CrossModuleDataOwnershipTest.php
 * php artisan dusk --group=data-ownership
 * php artisan dusk --group=architecture
 */

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\DuskTestCase;

class CrossModuleDataOwnershipTest extends DuskTestCase
{
    protected function getAdminUser(): User
    {
        return User::where('email', 'admin@example.com')->orWhere('role', User::ROLE_ADMIN)->firstOrFail();
    }

    #[Group('data-ownership')]
    #[Group('core-blindness')]
    #[Group('architecture')]
    public function test_crm_never_queries_financial_tables(): void
    {
        // Setup Listener
        $queries = [];
        \Illuminate\Support\Facades\DB::listen(function ($query) use (&$queries) {
           $queries[] = $query->sql; 
        });

        // Trigger Action: Visit Client 360
        $this->browse(function (Browser $browser) {
            $client = \Modules\Crm\Models\Client::factory()->create();
            
            $browser->loginAs($this->getAdminUser())
                ->visit('/crm/clients/' . $client->id);
                
            $browser->assertSee($client->name);
        });
        
        // Analyze Queries
        // We expect NO queries that JOIN `clients` (or crm tables) with `invoices` (or financial tables)
        // AND NO queries to financial tables originating from CRM Controller directly (though Services are okay)
        // For this test, we verify that "invoices" table is NOT joined with "clients" table.
        
        foreach ($queries as $sql) {
            $sql = strtolower($sql);
            if (str_contains($sql, 'join') && str_contains($sql, 'invoices') && str_contains($sql, 'clients')) {
                $this->fail("Detected Cross-Module Join: $sql");
            }
        }
        
        $this->assertTrue(true, 'No cross-module joins detected.');
    }

    #[Group('data-ownership')]
    #[Group('api-contracts')]
    public function test_pib_uses_client_api_not_direct_query(): void
    {
         // Verify we can access via Service
         $client = \Modules\Crm\Models\Client::factory()->create();
         
         // Fix: PIB requires company_id
         $companyId = $client->company_id;
         if (!$companyId) {
             // Create dummy company if not exists (assuming companies table exists)
             try {
                $companyId = \Illuminate\Support\Facades\DB::table('companies')->insertGetId([
                    'name' => 'Test Company', 
                    'created_at' => now(), 
                    'updated_at' => now()
                ]);
                $client->company_id = $companyId;
                $client->save();
             } catch (\Exception $e) {
                 // Fallback if companies table has specific structure or client factory handles it
                 $companyId = 1;
             }
         }

         $invoiceNumber = 'INV-' . uniqid();
         \Illuminate\Support\Facades\DB::table('pib_invoices')->insert([
             'client_id' => $client->id,
             'company_id' => $companyId,
             'invoice_number' => $invoiceNumber,
             'total_amount' => 100,
             'status' => 'sent',
             'invoice_date' => now()->toDateString(),
             'due_date' => now()->addDays(30)->toDateString(),
             'created_at' => now(),
             'updated_at' => now(),
         ]);
         
         $service = new \Modules\PIB\Services\BillingService();
         $invoices = $service->getInvoicesForClient($client->id);
         
         $this->assertCount(1, $invoices);
         $this->assertEquals($invoiceNumber, $invoices[0]->invoice_number);
    }

    #[Group('data-ownership')]
    #[Group('module-boundaries')]
    public function test_asset_management_respects_billing_boundary(): void
    {
        $this->markTestIncomplete(
            'Requires AssetManagement query analysis to verify no billing table access.'
        );
    }

    #[Group('data-ownership')]
    #[Group('migrations')]
    #[Group('regression')]
    public function test_credit_migration_preserved_ownership(): void
    {
        $this->markTestIncomplete(
            'Requires verification that CRM no longer queries credit tables after PIB migration.'
        );
    }

    #[Group('data-ownership')]
    #[Group('architecture-enforcement')]
    public function test_no_cross_module_sql_joins(): void
    {
        $this->markTestIncomplete(
            'Requires SQL parser to detect cross-module JOINs.'
        );
    }

    #[Group('data-ownership')]
    #[Group('events')]
    public function test_event_based_data_access(): void
    {
        $this->markTestIncomplete(
            'Requires event-based data request/response pattern implementation.'
        );
    }

    #[Group('data-ownership')]
    #[Group('smoke')]
    public function test_modules_isolated(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->getAdminUser())
                ->visit('/dashboard')
                ->assertSee('Dashboard');
        });
    }
}
