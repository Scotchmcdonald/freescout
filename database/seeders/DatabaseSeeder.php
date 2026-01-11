<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Billing\Database\Seeders\MspScenarioSeeder;
use Modules\Billing\Database\Seeders\AssetCreditProductSeeder;
use Modules\Billing\Database\Seeders\AdHocProductSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Run the MSP Scenario Seeder
        // $this->call(MspScenarioSeeder::class);
        
        // Seed Billing Products
        // $this->call(AssetCreditProductSeeder::class);
        // $this->call(AdHocProductSeeder::class);
    }
}
