<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AssetManagement\Entities\Asset;
use Modules\Crm\Models\Client;
use Modules\PIB\Models\Invoice;
use Modules\PIB\Models\BillingTemplate;
use Tests\TestCase;

class Client360Test extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_admin_can_view_client_360_workspace()
    {
        $client = Client::factory()->create([
            'name' => 'Acme Corp',
            'email' => 'contact@acme.com',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.clients.show', $client));

        $response->assertStatus(200);
        $response->assertViewIs('admin.clients.show');
        $response->assertSee('Acme Corp');
        $response->assertSee('contact@acme.com');
    }

    public function test_client_360_displays_assets()
    {
        $client = Client::factory()->create();
        
        $assets = Asset::factory()->count(3)->create([
            'client_id' => $client->id,
            'status' => 'active',
        ]);

        $otherClient = Client::factory()->create();
        $otherAsset = Asset::factory()->create([
            'client_id' => $otherClient->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.clients.show', $client));

        $response->assertSee($assets[0]->hostname);
        $response->assertSee($assets[1]->serial_number);
        $response->assertDontSee($otherAsset->hostname);
        $response->assertViewHas('assets', function ($viewAssets) use ($assets) {
            return $viewAssets->count() === 3;
        });
    }

    public function test_client_360_displays_billing_information()
    {
        $client = Client::factory()->create();
        
        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'total_amount' => 500.00,
            'status' => 'paid',
        ]);

        $template = BillingTemplate::factory()->create([
            'client_id' => $client->id,
            'name' => 'Monthly Retainer',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.clients.show', $client));

        $response->assertSee('500.00');
        $response->assertSee('Paid');
        $response->assertSee('Monthly Retainer');
        
        $response->assertViewHas('invoices', function ($viewInvoices) {
            return $viewInvoices->count() === 1;
        });

        $response->assertViewHas('billingTemplates', function ($viewTemplates) {
            return $viewTemplates->count() === 1;
        });
    }

    public function test_client_360_displays_contacts()
    {
        $client = Client::factory()->create();
        
        $contact = \Modules\Crm\Models\Contact::factory()->create([
            'client_id' => $client->id,
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'email' => 'alice@example.com',
            'role' => 'Manager',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.clients.show', $client));

        $response->assertSee('Alice Smith');
        $response->assertSee('alice@example.com');
        $response->assertSee('Manager');
        
        $response->assertViewHas('contacts', function ($viewContacts) {
            return $viewContacts->count() === 1;
        });
    }

    public function test_non_admin_cannot_view_client_360()
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $client = Client::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.clients.show', $client));

        $response->assertStatus(403);
    }
}
