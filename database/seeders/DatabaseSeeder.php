<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Billing\Database\Seeders\MspScenarioSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Run the MSP Scenario Seeder
        $this->call(MspScenarioSeeder::class);
    }
}
