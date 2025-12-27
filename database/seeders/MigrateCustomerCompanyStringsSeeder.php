<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use Modules\Billing\Models\Company;
use Illuminate\Support\Facades\Log;

class MigrateCustomerCompanyStringsSeeder extends Seeder
{
    public function run()
    {
        $customers = Customer::whereNotNull('company')
                             ->where('company', '!=', '')
                             ->whereNull('company_id')
                             ->get();

        foreach ($customers as $customer) {
            $companyName = trim($customer->company);
            
            if (empty($companyName)) {
                continue;
            }

            // Find or create company
            $company = Company::firstOrCreate(
                ['name' => $companyName],
                ['pricing_tier' => 'standard'] // Default values
            );

            $customer->company_id = $company->id;
            $customer->save();

            Log::info("Migrated customer {$customer->id} to company {$company->id} ({$company->name})");
        }
    }
}
