<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\ClientUser;
use Modules\Crm\Models\Company;

/**
 * Client Portal Test Seeder
 * 
 * Creates test data for Client Portal browser tests:
 * - Two active clients for data isolation testing
 * - Client users with known credentials for login testing
 * - Test invoices for each client (if PIB module exists)
 * 
 * Usage:
 * php artisan db:seed --class=ClientPortalTestSeeder
 */
class ClientPortalTestSeeder extends Seeder
{
    /**
     * Known test credentials for browser tests
     */
    public const CLIENT_A_EMAIL = 'clienta@test.example.com';
    public const CLIENT_A_PASSWORD = 'TestPassword123!';
    public const CLIENT_B_EMAIL = 'clientb@test.example.com';
    public const CLIENT_B_PASSWORD = 'TestPassword456!';

    /**
     * Run the database seeds
     */
    public function run(): void
    {
        // Create or get test company
        $company = Company::firstOrCreate(
            ['name' => 'Portal Test Company'],
            [
                'address' => '123 Test St',
                'phone' => '555-0000',
            ]
        );

        // Create Client A
        $clientA = Client::firstOrCreate(
            ['email' => 'billing-a@test.example.com'],
            [
                'company_id' => $company->id,
                'name' => 'Test Client A',
                'tier' => 'Small Business',
                'phone' => '555-0001',
                'status' => 'active',
            ]
        );
        // Ensure company_id is set
        if (!$clientA->company_id) {
            $clientA->update(['company_id' => $company->id]);
        }

        // Create Client A user
        $clientUserA = ClientUser::firstOrCreate(
            ['email' => self::CLIENT_A_EMAIL],
            [
                'client_id' => $clientA->id,
                'name' => 'Alice Test',
                'password' => Hash::make(self::CLIENT_A_PASSWORD),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create Client B
        $clientB = Client::firstOrCreate(
            ['email' => 'billing-b@test.example.com'],
            [
                'company_id' => $company->id,
                'name' => 'Test Client B',
                'tier' => 'Non-Profit',
                'phone' => '555-0002',
                'status' => 'active',
            ]
        );
        // Ensure company_id is set
        if (!$clientB->company_id) {
            $clientB->update(['company_id' => $company->id]);
        }

        // Create Client B user
        $clientUserB = ClientUser::firstOrCreate(
            ['email' => self::CLIENT_B_EMAIL],
            [
                'client_id' => $clientB->id,
                'name' => 'Bob Test',
                'password' => Hash::make(self::CLIENT_B_PASSWORD),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create test invoices if PIB module is available
        if (class_exists(\Modules\PIB\Models\Invoice::class)) {
            $this->seedInvoices($clientA, $clientB);
        }

        // Output summary
        $this->command->info('Client Portal Test Data Created:');
        $this->command->table(
            ['Client', 'User Email', 'Password'],
            [
                ['Test Client A', self::CLIENT_A_EMAIL, self::CLIENT_A_PASSWORD],
                ['Test Client B', self::CLIENT_B_EMAIL, self::CLIENT_B_PASSWORD],
            ]
        );
    }

    /**
     * Seed test invoices for data isolation testing
     */
    protected function seedInvoices(Client $clientA, Client $clientB): void
    {
        $Invoice = \Modules\PIB\Models\Invoice::class;

        // Create invoices for Client A
        $Invoice::firstOrCreate(
            ['client_id' => $clientA->id, 'invoice_number' => 'INV-TEST-A001'],
            [
                'company_id' => $clientA->company_id,
                'invoice_date' => now()->subDays(30),
                'due_date' => now()->addDays(30),
                'total_amount' => 1500.00,
                'status' => 'pending',
            ]
        );

        $Invoice::firstOrCreate(
            ['client_id' => $clientA->id, 'invoice_number' => 'INV-TEST-A002'],
            [
                'company_id' => $clientA->company_id,
                'invoice_date' => now()->subDays(60),
                'due_date' => now()->subDays(30),
                'total_amount' => 2000.00,
                'status' => 'paid',
            ]
        );

        // Create invoices for Client B
        $Invoice::firstOrCreate(
            ['client_id' => $clientB->id, 'invoice_number' => 'INV-TEST-B001'],
            [
                'company_id' => $clientB->company_id,
                'invoice_date' => now()->subDays(15),
                'due_date' => now()->addDays(15),
                'total_amount' => 3500.00,
                'status' => 'pending',
            ]
        );

        $this->command->info('Test invoices seeded for both clients.');
    }
}
