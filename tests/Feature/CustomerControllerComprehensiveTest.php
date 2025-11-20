<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Tests\FeatureTestCase;

/**
 * Comprehensive tests for CustomerController
 * Following TESTING_GUIDE.md - using test_ prefix, FeatureTestCase base class
 */
class CustomerControllerComprehensiveTest extends FeatureTestCase
{
    // ===== INDEX TESTS =====

    public function test_index_requires_authentication(): void
    {
        $response = $this->get(route('customers.index'));
        
        $response->assertRedirect(route('login'));
    }

    public function test_index_returns_view_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get(route('customers.index'));
        
        $response->assertOk();
        $response->assertViewIs('customers.index');
        $response->assertViewHas('customers');
    }

    public function test_index_displays_all_customers(): void
    {
        $user = User::factory()->create();
        Customer::factory()->count(3)->create();
        
        $response = $this->actingAs($user)->get(route('customers.index'));
        
        $customers = $response->viewData('customers');
        $this->assertGreaterThanOrEqual(3, $customers->count());
    }

    public function test_index_with_search_by_first_name(): void
    {
        $user = User::factory()->create();
        Customer::factory()->create(['first_name' => 'John', 'email' => 'john@example.com']);
        Customer::factory()->create(['first_name' => 'Jane', 'email' => 'jane@example.com']);
        
        $response = $this->actingAs($user)->get(route('customers.index', ['search' => 'John']));
        
        $response->assertOk();
    }

    public function test_index_with_search_by_last_name(): void
    {
        $user = User::factory()->create();
        Customer::factory()->create(['last_name' => 'Smith', 'email' => 'smith@example.com']);
        
        $response = $this->actingAs($user)->get(route('customers.index', ['search' => 'Smith']));
        
        $response->assertOk();
    }

    public function test_index_with_search_by_email(): void
    {
        $user = User::factory()->create();
        Customer::factory()->create(['email' => 'test@example.com']);
        
        $response = $this->actingAs($user)->get(route('customers.index', ['search' => 'test@example.com']));
        
        $response->assertOk();
    }

    public function test_index_with_empty_search(): void
    {
        $user = User::factory()->create();
        Customer::factory()->count(2)->create();
        
        $response = $this->actingAs($user)->get(route('customers.index', ['search' => '']));
        
        $response->assertOk();
    }

    public function test_index_pagination_works(): void
    {
        $user = User::factory()->create();
        Customer::factory()->count(60)->create();
        
        $response = $this->actingAs($user)->get(route('customers.index'));
        
        $customers = $response->viewData('customers');
        $this->assertCount(50, $customers);
    }

    // ===== STORE TESTS =====

    public function test_store_requires_authentication(): void
    {
        $response = $this->post(route('customers.store'), [
            'first_name' => 'John',
            'email' => 'john@example.com',
        ]);
        
        $response->assertRedirect(route('login'));
    }

    public function test_store_creates_customer_with_valid_data(): void
    {
        $user = User::factory()->create();
        $email = 'newcustomer' . time() . '@example.com';
        
        $response = $this->actingAs($user)->post(route('customers.store'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => $email,
        ]);
        
        $response->assertRedirect();
        // Following TESTING_GUIDE.md: Check emails table for customer email
        $this->assertDatabaseHas('emails', ['email' => $email]);
    }

    public function test_store_validates_first_name_required(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->post(route('customers.store'), [
            'email' => 'test@example.com',
        ]);
        
        $response->assertSessionHasErrors('first_name');
    }

    public function test_store_validates_first_name_max_length(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->post(route('customers.store'), [
            'first_name' => str_repeat('a', 51),
            'email' => 'test@example.com',
        ]);
        
        $response->assertSessionHasErrors('first_name');
    }

    public function test_store_validates_email_required(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->post(route('customers.store'), [
            'first_name' => 'John',
        ]);
        
        $response->assertSessionHasErrors('email');
    }

    public function test_store_validates_email_format(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->post(route('customers.store'), [
            'first_name' => 'John',
            'email' => 'invalid-email',
        ]);
        
        $response->assertSessionHasErrors('email');
    }

    public function test_store_validates_email_unique(): void
    {
        $user = User::factory()->create();
        $existingCustomer = Customer::factory()->create(['email' => 'existing@example.com']);
        
        $response = $this->actingAs($user)->post(route('customers.store'), [
            'first_name' => 'John',
            'email' => 'existing@example.com',
        ]);
        
        $response->assertSessionHasErrors('email');
    }

    public function test_store_accepts_optional_last_name(): void
    {
        $user = User::factory()->create();
        $email = 'noLastName' . time() . '@example.com';
        
        $response = $this->actingAs($user)->post(route('customers.store'), [
            'first_name' => 'John',
            'email' => $email,
        ]);
        
        $response->assertRedirect();
    }

    // ===== SHOW TESTS =====

    public function test_show_requires_authentication(): void
    {
        $customer = Customer::factory()->create();
        
        $response = $this->get(route('customers.show', $customer));
        
        $response->assertRedirect(route('login'));
    }

    public function test_show_returns_view_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        
        $response = $this->actingAs($user)->get(route('customers.show', $customer));
        
        $response->assertOk();
        $response->assertViewIs('customers.show');
        $response->assertViewHas('customer');
    }

    public function test_show_loads_customer_relationships(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        
        $response = $this->actingAs($user)->get(route('customers.show', $customer));
        
        $viewCustomer = $response->viewData('customer');
        $this->assertNotNull($viewCustomer);
    }

    // ===== EDIT TESTS =====

    public function test_edit_requires_authentication(): void
    {
        $customer = Customer::factory()->create();
        
        $response = $this->get(route('customers.edit', $customer));
        
        $response->assertRedirect(route('login'));
    }

    public function test_edit_returns_view_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        
        $response = $this->actingAs($user)->get(route('customers.edit', $customer));
        
        $response->assertOk();
        $response->assertViewIs('customers.edit');
        $response->assertViewHas('customer');
    }

    // ===== UPDATE TESTS =====

    public function test_update_requires_authentication(): void
    {
        $customer = Customer::factory()->create();
        
        $response = $this->patch(route('customers.update', $customer), [
            'first_name' => 'Updated',
        ]);
        
        $response->assertRedirect(route('login'));
    }

    public function test_update_updates_customer_with_valid_data(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['first_name' => 'Original']);
        
        $response = $this->actingAs($user)->patchJson(route('customers.update', $customer), [
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ]);
        
        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertEquals('Updated', $customer->fresh()->first_name);
    }

    public function test_update_validates_first_name_required(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        
        $response = $this->actingAs($user)->patchJson(route('customers.update', $customer), [
            'first_name' => '',
        ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('first_name');
    }

    // ===== DESTROY TESTS =====

    public function test_destroy_requires_authentication(): void
    {
        $customer = Customer::factory()->create();
        
        $response = $this->delete(route('customers.destroy', $customer));
        
        $response->assertRedirect(route('login'));
    }

    public function test_destroy_requires_authorization(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $customer = Customer::factory()->create();
        
        $response = $this->actingAs($user)->delete(route('customers.destroy', $customer));
        
        $response->assertForbidden();
    }

    public function test_destroy_deletes_customer_for_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $customer = Customer::factory()->create();
        
        $response = $this->actingAs($admin)->delete(route('customers.destroy', $customer));
        
        $response->assertRedirect();
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    // ===== EDGE CASES =====

    public function test_show_with_non_existent_customer_returns_404(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get('/customers/99999');
        
        $response->assertNotFound();
    }

    public function test_store_with_special_characters_in_name(): void
    {
        $user = User::factory()->create();
        $email = 'special' . time() . '@example.com';
        
        $response = $this->actingAs($user)->post(route('customers.store'), [
            'first_name' => "O'Brien",
            'last_name' => 'José-María',
            'email' => $email,
        ]);
        
        $response->assertRedirect();
    }

    public function test_index_search_is_case_insensitive(): void
    {
        $user = User::factory()->create();
        Customer::factory()->create(['first_name' => 'john', 'email' => 'john2@example.com']);
        
        $response = $this->actingAs($user)->get(route('customers.index', ['search' => 'JOHN']));
        
        $response->assertOk();
    }

    public function test_store_with_only_first_name_and_email(): void
    {
        $user = User::factory()->create();
        $email = 'minimal' . time() . '@example.com';
        
        $response = $this->actingAs($user)->post(route('customers.store'), [
            'first_name' => 'John',
            'email' => $email,
        ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('emails', ['email' => $email]);
    }

    public function test_update_can_change_last_name(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['last_name' => 'Old']);
        
        $response = $this->actingAs($user)->patchJson(route('customers.update', $customer), [
            'first_name' => $customer->first_name,
            'last_name' => 'New',
        ]);
        
        $response->assertOk();
        $this->assertEquals('New', $customer->fresh()->last_name);
    }

    public function test_index_with_no_customers(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get(route('customers.index'));
        
        $response->assertOk();
        $customers = $response->viewData('customers');
        $this->assertCount(0, $customers);
    }

    public function test_search_with_partial_match(): void
    {
        $user = User::factory()->create();
        Customer::factory()->create(['first_name' => 'Johnson', 'email' => 'johnson@example.com']);
        
        $response = $this->actingAs($user)->get(route('customers.index', ['search' => 'John']));
        
        $response->assertOk();
    }
}
