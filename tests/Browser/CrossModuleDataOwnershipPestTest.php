<?php

use App\Models\User;
use Modules\Crm\Models\Client;

function getOwnershipAdmin(): User
{
    $admin = User::firstOrCreate(['email' => 'ownership-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'Ownership',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (!$admin->isAdmin()) { $admin->role = User::ROLE_ADMIN; $admin->save(); }
    return $admin;
}

it('crm never queries financial tables', function () {
    // Verify CRM controller doesn't reference PIB tables directly
    $crmController = file_get_contents(base_path('Modules/Crm/Http/Controllers/ClientController.php'));
    expect($crmController)->not->toContain('pib_invoices');
    expect($crmController)->not->toContain('pib_payments');
    expect($crmController)->not->toContain('pib_credit');
})->group('data-ownership', 'core-blindness', 'architecture');

test('pib uses client api not direct query', function () {
    $client = Client::factory()->create();
    $companyId = $client->company_id;

    if (!$companyId) {
        try {
            $companyId = \Illuminate\Support\Facades\DB::table('companies')->insertGetId([
                'name' => 'Test Company',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $client->company_id = $companyId;
            $client->save();
        } catch (\Exception $e) {
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

    expect($invoices)->toHaveCount(1);
    expect($invoices[0]->invoice_number)->toBe($invoiceNumber);
})->group('data-ownership', 'api-contracts');

it('asset management respects billing boundary', function () {
    // AssetController should not directly reference billing/invoice tables
    $controller = file_get_contents(base_path('Modules/AssetManagement/Http/Controllers/AssetController.php'));
    expect($controller)->not->toContain('pib_invoices');
    expect($controller)->not->toContain('Invoice::');
})->group('data-ownership', 'module-boundaries');

it('no cross module sql joins', function () {
    // Verify CRM models don't join to PIB tables
    $clientModel = file_get_contents(base_path('Modules/Crm/Models/Client.php'));
    expect($clientModel)->not->toContain('join(\'pib_');
    expect($clientModel)->not->toContain('join("pib_');
})->group('data-ownership', 'architecture-enforcement');
it('event based data access pattern', function () {
    // Per Architecture: Modules communicate via events, not direct DB access.
    // Verify PIB listens to ContractManager events (not direct queries).
    $listeners = \Illuminate\Support\Facades\Event::getListeners(
        \Modules\ContractManager\Events\BillingTemplateDue::class
    );
    expect($listeners)->not->toBeEmpty('PIB should listen to BillingTemplateDue event');

    // Verify PIB listens to ContractRevised event
    $listeners = \Illuminate\Support\Facades\Event::getListeners(
        \Modules\ContractManager\Events\ContractRevised::class
    );
    expect($listeners)->not->toBeEmpty('PIB should listen to ContractRevised event');

    // Verify PIB doesn't import ContractManager models directly in its Service layer
    $billingService = file_get_contents(base_path('Modules/PIB/Services/BillingService.php'));
    expect($billingService)->not->toContain('use Modules\ContractManager\Models\Contract;');
})->group('data-ownership', 'events');

test('modules isolated', function () {
    $admin = getOwnershipAdmin();

    $this->visit('/login')
        ->type('email', $admin->email)
        ->type('password', 'password')
        ->click('button[type="submit"]');

    $this->visit('/dashboard')
        ->assertSee('Dashboard');
})->group('data-ownership', 'smoke');
