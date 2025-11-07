<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Email;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_with_very_long_name_is_handled(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create([
            'first_name' => str_repeat('a', 50), // Max length
        ]);

        $response = $this->actingAs($user)->get(route('customers.show', $customer->id));
        
        $response->assertOk();
        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }

    public function test_customer_list_displays_with_many_records(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        // Create 30 customers
        Customer::factory()->count(30)->create();

        $response = $this->actingAs($user)->get('/customers');
        
        $response->assertOk();
        $response->assertViewHas('customers');
    }

    public function test_customer_with_special_characters_in_name_is_escaped(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create([
            'first_name' => '<script>alert("xss")</script>',
        ]);

        $response = $this->actingAs($user)->get(route('customers.show', $customer->id));
        
        $response->assertOk();
        // Verify XSS is escaped
        $response->assertDontSee('<script>', false);
    }

    public function test_customer_with_null_optional_fields_displays_correctly(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create([
            'first_name' => 'Test',
            'last_name' => null,
            'company' => null,
        ]);

        $response = $this->actingAs($user)->get(route('customers.show', $customer->id));
        
        $response->assertOk();
        $response->assertViewHas('customer');
    }

    public function test_customer_list_handles_no_customers(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($user)->get('/customers');
        
        $response->assertOk();
        $response->assertViewHas('customers');
    }

    public function test_guest_cannot_access_customer_list(): void
    {
        $response = $this->get('/customers');
        
        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_view_customer_detail(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->get(route('customers.show', $customer->id));
        
        $response->assertRedirect(route('login'));
    }

    public function test_non_existent_customer_returns_404(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($user)->get("/customers/99999");
        
        $response->assertStatus(404);
    }
}
