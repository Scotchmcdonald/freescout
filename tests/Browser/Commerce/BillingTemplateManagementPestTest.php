<?php

declare(strict_types=1);

use App\Models\User;
use Modules\ContractManager\Models\BillingTemplate;
use Modules\Crm\Models\Client;

function getBillingTemplateAdmin(): User
{
    $admin = User::firstOrCreate(['email' => 'billing-template-admin@example.com'], [
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'first_name' => 'BillingTpl',
        'last_name' => 'Admin',
        'email_verified_at' => now(),
    ]);
    if (! $admin->isAdmin()) {
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }

    return $admin;
}

it('billing template list loads', function () {
    $admin = getBillingTemplateAdmin();

    browserLoginAdmin($this, $admin);

    $this->visit('/contracts/billing-templates')
        ->assertSee('Billing Templates');
})->group('commerce', 'billing-template');

it('billing template shows empty state', function () {
    $admin = getBillingTemplateAdmin();

    browserLoginAdmin($this, $admin);

    $this->visit('/contracts/billing-templates')
        ->assertSee('No billing templates found');
})->group('commerce', 'billing-template');

it('billing template detail page loads', function () {
    $admin = getBillingTemplateAdmin();
    $client = Client::factory()->create(['name' => 'BT Detail Client']);

    $template = BillingTemplate::factory()->create([
        'client_id' => $client->id,
        'name' => 'Monthly Service Plan',
        'status' => 'active',
    ]);

    browserLoginAdmin($this, $admin);

    $this->visit("/contracts/billing-templates/{$template->id}")
        ->assertSee('Monthly Service Plan');
})->group('commerce', 'billing-template');
