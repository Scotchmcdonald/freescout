<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;

/**
 * Client Portal Test Seeder
 * 
 * Creates test data for Client Portal browser tests:
 * - Two active companies/clients for data isolation testing
 * - Users (type=2, external) with known credentials for login testing
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
                'is_active' => true,
            ]
        );

        // Create Client A (legacy entity — still needed for invoice FK)
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
        if (!$clientA->company_id) {
            $clientA->update(['company_id' => $company->id]);
        }

        // Create User A (unified identity)
        $userA = User::firstOrCreate(
            ['email' => self::CLIENT_A_EMAIL],
            [
                'first_name' => 'Alice',
                'last_name' => 'Test',
                'password' => Hash::make(self::CLIENT_A_PASSWORD),
                'type' => 2, // External / Client
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
            ]
        );
        if (!$company->users()->where('user_id', $userA->id)->exists()) {
            $company->users()->attach($userA->id, [
                'role_id' => 1,
                'status' => 'approved',
                'is_primary' => true,
            ]);
        }

        // Create Client B (legacy entity — still needed for invoice FK)
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
        if (!$clientB->company_id) {
            $clientB->update(['company_id' => $company->id]);
        }

        // Create User B (unified identity)
        $userB = User::firstOrCreate(
            ['email' => self::CLIENT_B_EMAIL],
            [
                'first_name' => 'Bob',
                'last_name' => 'Test',
                'password' => Hash::make(self::CLIENT_B_PASSWORD),
                'type' => 2,
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
            ]
        );
        if (!$company->users()->where('user_id', $userB->id)->exists()) {
            $company->users()->attach($userB->id, [
                'role_id' => 1,
                'status' => 'approved',
                'is_primary' => false,
            ]);
        }

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
