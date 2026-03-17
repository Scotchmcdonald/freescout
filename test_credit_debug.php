<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Modules\ContractManager\Models\Contract;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;
use Modules\PIB\Models\ClientCredit;
use Modules\PIB\Models\Invoice;

// Setup
$company = Company::factory()->create();
$client = Client::factory()->create(['company_id' => $company->id]);

$admin = $client->company->users()->create([
    'first_name' => 'Admin',
    'last_name' => 'User',
    'email' => 'admin@test.local',
    'type' => 1,
    'role_id' => 2,
    'password' => bcrypt('password'),
]);

$contract = Contract::create([
    'client_id' => $client->id,
    'title' => 'Test',
    'contract_number' => 'CON-TEST',
    'status' => 'active',
    'start_date' => now(),
    'contract_type' => 'standard',
    'monthly_amount' => 50.00,
]);

ClientCredit::create([
    'client_id' => $client->id,
    'balance_cents' => 10000,
]);

// Test
$invoice = null;
auth()->login($admin);
$controller = new \Modules\ContractManager\Http\Controllers\ContractController(app('Illuminate\Database\DatabaseManager'));
try {
    $r = $controller->generateInvoice($contract);
    echo 'Response type: '.class_basename($r)."\n";
} catch (\Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
}

$invoice = Invoice::where('client_id', $client->id)->first();
echo 'Invoice total: '.$invoice->total_amount."\n";
echo 'Invoice credit_applied: '.$invoice->credit_applied."\n";
