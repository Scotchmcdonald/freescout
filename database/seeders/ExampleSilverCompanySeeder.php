<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Modules\Crm\Models\Company;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Contact;
use Modules\AssetManagement\Entities\Asset;
use Modules\SoftwareSubscriptions\Models\SoftwareProduct;
use Modules\SoftwareSubscriptions\Models\ClientSoftwareSubscription;
use Modules\Payment\Models\ClientCreditLedger;
use Modules\PIB\Models\Invoice;
use Modules\PIB\Models\InvoiceLineItem;
use Modules\Payment\Models\Payment;

class ExampleSilverCompanySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $startDate = Carbon::now()->subMonths(6)->startOfDay();

            // 1. Create Company
            $company = Company::firstOrCreate(
                ['name' => 'Silver Tech Solutions'],
                [
                    'email' => 'admin@silvertech.com',
                    'phone' => '555-0199',
                    'pricing_tier' => 'Silver',
                    'is_active' => true,
                    'address' => '123 Silver Lane',
                    'city' => 'Techville',
                    'state' => 'CA',
                    'zip' => '90210',
                    'country' => 'US',
                    'created_at' => $startDate,
                    'updated_at' => $startDate,
                ]
            );

            // 2. Create Client (Organization unit in CRM)
            $client = Client::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'Silver Tech Solutions'],
                [
                    'tier' => 'Small Business',
                    'status' => 'active',
                    'email' => 'billing@silvertech.com',
                    'phone' => '555-0199',
                    'created_at' => $startDate,
                    'updated_at' => $startDate,
                ]
            );

            $this->command->info("Ensured Company and Client exist: {$company->name}");

            // 3. Create Employees (Contacts)
            $employees = [];
            for ($i = 1; $i <= 20; $i++) {
                $email = "employee{$i}@silvertech.com";
                $contact = Contact::firstOrCreate(
                    ['email' => $email, 'client_id' => $client->id],
                    [
                        'first_name' => 'Employee',
                        'last_name' => (string)$i,
                        'is_primary' => $i === 1,
                        'role' => 'Employee',
                        'created_at' => $startDate,
                        'updated_at' => $startDate,
                    ]
                );
                $employees[] = $contact;
            }
            $this->command->info("Ensured 20 Employees exist");

            // 4. Financials - Add Credit then Purchase Laptops
            $chromebookCost = 300.00;
            $windowsCost = 800.00;
            $totalHardwareCost = (10 * $chromebookCost) + (10 * $windowsCost);
            $totalCents = (int)round($totalHardwareCost * 100);

            // Check if initial funding exists to avoid duplication
            if (!ClientCreditLedger::where('client_id', $client->id)->where('description', 'Upfront laptop funding')->exists()) {
                // Add Credit (Upfront charge)
                ClientCreditLedger::create([
                    'client_id' => $client->id,
                    'amount_cents' => $totalCents,
                    'balance_after_cents' => $totalCents,
                    'transaction_type' => 'credit',
                    'description' => 'Upfront laptop funding',
                    'created_at' => $startDate,
                ]);

                // Purchase Laptops (Deduction)
                ClientCreditLedger::create([
                    'client_id' => $client->id,
                    'amount_cents' => -$totalCents,
                    'balance_after_cents' => 0,
                    'transaction_type' => 'debit',
                    'description' => 'Purchase of 10 Chromebooks and 10 Windows Laptops',
                    'created_at' => $startDate->copy()->addHour(),
                ]);
                $this->command->info("Processed financial transactions: \${$totalHardwareCost}");
            } else {
                $this->command->info("Skipped financial transactions (already exist)");
            }

            // 5. Create Assets
            foreach ($employees as $index => $employee) {
                // First 10 are Chromebooks, Next 10 are Windows
                $isChromebook = $index < 10;
                $p = $index + 1;
                $serial = "SN-SILVER-{$p}";
                
                Asset::firstOrCreate(
                    ['serial_number' => $serial, 'client_id' => $client->id],
                    [
                        'company_id' => $company->id,
                        'hostname' => "Laptop-{$p}",
                        'asset_type' => $isChromebook ? 'chromebook' : 'windows',
                        'status' => 'active',
                        'assigned_user_email' => $employee->email,
                        'source' => 'Manual',
                        'procurement_metadata' => [
                            'purchase_date' => $startDate->toDateString(),
                            'cost' => $isChromebook ? $chromebookCost : $windowsCost,
                            'ownership' => 'Purchased',
                        ],
                        'created_at' => $startDate,
                        'updated_at' => $startDate,
                    ]
                );
            }
            $this->command->info("Ensured 20 Assets exist");

            // 6. Software Subscriptions Setup
            $googleProduct = SoftwareProduct::firstOrCreate(
                ['name' => 'Google Workspace'],
                [
                    'vendor' => 'Google',
                    'pricing_type' => 'flat',
                    'licensing_model' => 'per_user',
                    'billing_frequency' => 'monthly',
                    'vendor_cost' => 11.99,
                    'default_price' => 11.99,
                    'is_active' => true,
                ]
            );

            $action1Product = SoftwareProduct::firstOrCreate(
                ['name' => 'Action1 RMM'],
                [
                    'vendor' => 'Action1',
                    'pricing_type' => 'flat',
                    'licensing_model' => 'per_device',
                    'billing_frequency' => 'monthly',
                    'vendor_cost' => 10.00, // $1000/100
                    'default_price' => 10.00,
                    'is_active' => true,
                    'applicable_asset_types' => ['windows'],
                ]
            );

            $avastProduct = SoftwareProduct::firstOrCreate(
                ['name' => 'Avast Antivirus'],
                [
                    'vendor' => 'Avast',
                    'pricing_type' => 'flat',
                    'licensing_model' => 'per_device',
                    'billing_frequency' => 'monthly',
                    'vendor_cost' => 2.99, // $29.99/10
                    'default_price' => 2.99,
                    'is_active' => true,
                    'applicable_asset_types' => ['windows'],
                ]
            );

            // New Product: Adobe Creative Cloud (Added later)
            $adobeProduct = SoftwareProduct::firstOrCreate(
                ['name' => 'Adobe Creative Cloud'],
                [
                    'vendor' => 'Adobe',
                    'pricing_type' => 'flat',
                    'licensing_model' => 'per_user',
                    'billing_frequency' => 'monthly',
                    'vendor_cost' => 54.99,
                    'default_price' => 54.99,
                    'is_active' => true,
                ]
            );

            // Initial Subscriptions
            // Google - All 20 users
            ClientSoftwareSubscription::firstOrCreate(
                ['client_id' => $client->id, 'software_product_id' => $googleProduct->id],
                [
                    'status' => 'active',
                    'purchased_quantity' => 20,
                    'assigned_count' => 20,
                    'billing_behavior' => 'fixed',
                    'custom_price' => $googleProduct->default_price,
                    'start_date' => $startDate,
                    'created_at' => $startDate,
                ]
            );

            // Windows Only Subs (10 users)
            ClientSoftwareSubscription::firstOrCreate(
                ['client_id' => $client->id, 'software_product_id' => $action1Product->id],
                [
                    'status' => 'active',
                    'purchased_quantity' => 10,
                    'assigned_count' => 10,
                    'billing_behavior' => 'fixed',
                    'custom_price' => $action1Product->default_price,
                    'start_date' => $startDate,
                    'created_at' => $startDate,
                ]
            );

            $avastSub = ClientSoftwareSubscription::firstOrCreate(
                ['client_id' => $client->id, 'software_product_id' => $avastProduct->id],
                [
                    'status' => 'active',
                    'purchased_quantity' => 10,
                    'assigned_count' => 10,
                    'billing_behavior' => 'fixed',
                    'custom_price' => $avastProduct->default_price,
                    'start_date' => $startDate,
                    'created_at' => $startDate,
                ]
            );

            $this->command->info("Ensured Software Subscriptions exist");

            // 7. Generate 6 Months of History (Invoices & Payments)
            $currentMonth = $startDate->copy();
            $now = Carbon::now();
            $monthIndex = 0;

            // Tracking vars
            $userCount = 20;
            $windowsCount = 10;
            $chromebookCount = 10;
            $adobeUserCount = 0;

            // Retrieve subs for updates (variables from step 6 assumed available in scope)
            // Just to be safe, let's re-fetch them if needed, but they should be in scope.
            // Actually, in the previous replace I used firstOrCreate but didn't assign to variable names like $googleSub.
            // I need to ensure variables are assigned.
            // Let's re-fetch them here to be safe and clear.
            $googleSub = ClientSoftwareSubscription::where('client_id', $client->id)->where('software_product_id', $googleProduct->id)->first();
            $action1Sub = ClientSoftwareSubscription::where('client_id', $client->id)->where('software_product_id', $action1Product->id)->first();
            $avastSub = ClientSoftwareSubscription::where('client_id', $client->id)->where('software_product_id', $avastProduct->id)->first();

            while ($currentMonth->lte($now)) {
                $monthIndex++;
                $monthName = $currentMonth->format('F Y');
                $invoiceDate = $currentMonth->copy()->addDays(1);
                
                // EVENT: Month 3 - Hiring Spree (Add 2 employees)
                if ($monthIndex === 3) {
                    $this->command->info("  -> Event: Hired 2 employees (Adobe users)");
                    // Add 2 Employees
                    for ($i = 21; $i <= 22; $i++) {
                        $newEmployee = Contact::firstOrCreate(
                            ['email' => "employee{$i}@silvertech.com", 'client_id' => $client->id],
                            [
                                'first_name' => 'New Hire',
                                'last_name' => (string)$i,
                                'role' => 'Employee',
                                'created_at' => $invoiceDate,
                            ]
                        );
                        $employees[] = $newEmployee;
                        
                        // Buy 2 Windows Laptops
                        Asset::firstOrCreate(
                            ['serial_number' => "SN-SILVER-{$i}"],
                            [
                                'client_id' => $client->id,
                                'company_id' => $company->id,
                                'hostname' => "Laptop-{$i}",
                                'asset_type' => 'windows',
                                'status' => 'active',
                                'assigned_user_email' => $newEmployee->email,
                                'source' => 'Manual',
                                'procurement_metadata' => [
                                    'purchase_date' => $invoiceDate->toDateString(),
                                    'cost' => $windowsCost,
                                    'ownership' => 'Purchased',
                                ],
                                'created_at' => $invoiceDate,
                            ]
                        );
                    }
                    
                    // Hardware Purchase Transaction
                    $hwCost = 2 * $windowsCost;
                    if (!ClientCreditLedger::where('client_id', $client->id)->where('description', 'Funding for New Hires')->exists()) {
                        ClientCreditLedger::create([
                            'client_id' => $client->id,
                            'amount_cents' => (int)($hwCost * 100),
                            'balance_after_cents' => 0, // Simplified tracking
                            'transaction_type' => 'credit', 
                            'description' => 'Funding for New Hires',
                            'created_at' => $invoiceDate,
                        ]);
                        ClientCreditLedger::create([
                            'client_id' => $client->id,
                            'amount_cents' => -(int)($hwCost * 100),
                            'balance_after_cents' => 0,
                            'transaction_type' => 'debit',
                            'description' => 'Purchase of 2 Windows Laptops',
                            'created_at' => $invoiceDate->copy()->addMinute(),
                        ]);
                    }

                    $userCount += 2;
                    $windowsCount += 2;
                    
                    // Update Subscription Counts
                    if ($googleSub) $googleSub->update(['assigned_count' => $userCount, 'purchased_quantity' => $userCount]);
                    if ($action1Sub) $action1Sub->update(['assigned_count' => $windowsCount, 'purchased_quantity' => $windowsCount]);
                    if ($avastSub) $avastSub->update(['assigned_count' => $windowsCount, 'purchased_quantity' => $windowsCount]);
                }

                // EVENT: Month 4 - Adobe Added
                if ($monthIndex === 4) {
                    $this->command->info("  -> Event: Added Adobe Creative Cloud (5 users)");
                    $adobeUserCount = 5;
                    ClientSoftwareSubscription::firstOrCreate(
                        ['client_id' => $client->id, 'software_product_id' => $adobeProduct->id],
                        [
                            'status' => 'active',
                            'purchased_quantity' => 5,
                            'assigned_count' => 5,
                            'billing_behavior' => 'fixed',
                            'custom_price' => $adobeProduct->default_price,
                            'start_date' => $invoiceDate,
                            'created_at' => $invoiceDate,
                        ]
                    );
                }

                // EVENT: Month 5 - Staff Departure
                if ($monthIndex === 5) {
                    $this->command->info("  -> Event: Employee 5 left the company");
                    // Ensure we have the employee object from the array (which might be mixed with new hires but index 4 is safe if array is ordered)
                    // Better to find by email to be safe
                    $leavingEmail = "employee5@silvertech.com";
                    $leavingEmployee = Contact::where('email', $leavingEmail)->first();
                    
                    if ($leavingEmployee) {
                        // Unassign Asset (Find asset for this user)
                        Asset::where('assigned_user_email', $leavingEmployee->email)
                            ->update(['status' => 'inactive', 'assigned_user_email' => null]);
                    }
                    
                    $userCount -= 1;
                    if ($googleSub) $googleSub->update(['assigned_count' => $userCount, 'purchased_quantity' => $userCount]);
                }

                // Invoice Items
                $items = [
                    [
                        'description' => "Silver Plan - Base Fee ({$monthName})",
                        'quantity' => 1,
                        'unit_price' => 50.00,
                    ],
                    [
                        'description' => "Silver Plan - Per User ({$monthName})",
                        'quantity' => $userCount,
                        'unit_price' => 10.00,
                    ],
                    [
                        'description' => "Google Workspace Licenses ({$monthName})",
                        'quantity' => $userCount,
                        'unit_price' => 11.99,
                    ],
                    [
                        'description' => "Action1 RMM ({$monthName})",
                        'quantity' => $windowsCount,
                        'unit_price' => 10.00,
                    ],
                    [
                        'description' => "Avast Antivirus ({$monthName})",
                        'quantity' => ceil($windowsCount / 10), // Billed as block of 10
                        'unit_price' => 29.99, 
                    ],
                ];

                if ($adobeUserCount > 0) {
                     $items[] = [
                        'description' => "Adobe Creative Cloud ({$monthName})",
                        'quantity' => $adobeUserCount,
                        'unit_price' => 54.99,
                    ];
                }

                $subtotal = 0;
                foreach ($items as $item) {
                    $subtotal += $item['quantity'] * $item['unit_price'];
                }

                // Check for existing invoice
                $existingInvoice = Invoice::where('client_id', $client->id)
                    ->whereYear('invoice_date', $invoiceDate->year)
                    ->whereMonth('invoice_date', $invoiceDate->month)
                    ->first();

                if (!$existingInvoice) {
                    $dueDate = $invoiceDate->copy()->addDays(15);
                    
                    // Create Invoice
                    $invoice = Invoice::create([
                        'client_id' => $client->id,
                        'company_id' => $company->id,
                        'invoice_number' => 'INV-' . strtoupper(uniqid()),
                        'status' => 'paid', // All past invoices paid
                        'invoice_date' => $invoiceDate,
                        'due_date' => $dueDate,
                        'subtotal' => $subtotal,
                        'tax_amount' => 0, // Simplified
                        'total_amount' => $subtotal,
                        'paid_at' => $invoiceDate->copy()->addDays(2),
                        'created_at' => $invoiceDate,
                        'updated_at' => $invoiceDate->copy()->addDays(2),
                    ]);

                    // Create Line Items
                    foreach ($items as $item) {
                        InvoiceLineItem::create([
                            'invoice_id' => $invoice->id,
                            'description' => $item['description'],
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['unit_price'],
                            'total' => $item['quantity'] * $item['unit_price'],
                            'created_at' => $invoiceDate,
                            'updated_at' => $invoiceDate,
                        ]);
                    }

                    // Create Payment
                    Payment::create([
                        'company_id' => $company->id,
                        'invoice_id' => $invoice->id,
                        'amount' => $subtotal,
                        'total_amount' => $subtotal,
                        'currency' => 'USD',
                        'status' => 'successful',
                        'payment_type' => 'card',
                        'transaction_type' => 'purchase',
                        'description' => "Payment for {$invoice->invoice_number}",
                        'processed_at' => $invoice->paid_at,
                        'reconciled' => true,
                        'reconciled_at' => $invoice->paid_at,
                        'created_at' => $invoice->paid_at,
                    ]);

                    $this->command->info("Generated Invoice for {$monthName}: \${$subtotal}");
                } else {
                    $this->command->info("Skipped Invoice for {$monthName} (already exists)");
                }

                $currentMonth->addMonth();
            }

        });
    }
}
