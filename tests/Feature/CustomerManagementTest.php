<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Email;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => User::ROLE_USER,
        ]);
    }

    #[Test]
    public function user_can_view_list_of_customers(): void
    {
        // Arrange
        $customer1 = Customer::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        Email::factory()->create([
            'customer_id' => $customer1->id,
            'email' => 'john@example.com',
        ]);

        $customer2 = Customer::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);
        Email::factory()->create([
            'customer_id' => $customer2->id,
            'email' => 'jane@example.com',
        ]);

        // Act
        $response = $this->actingAs($this->user)->get('/customers');

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('customers.index');
        $response->assertSee('John Doe');
        $response->assertSee('john@example.com');
        $response->assertSee('Jane Smith');
        $response->assertSee('jane@example.com');
    }

    #[Test]
    public function user_can_view_single_customer_and_conversation_history(): void
    {
        // Arrange
        $customer = Customer::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);
        $email = Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'jane@example.com',
        ]);

        $conversation1 = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'subject' => 'First Issue',
        ]);
        $conversation2 = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'subject' => 'Second Issue',
        ]);

        // Act
        $response = $this->actingAs($this->user)->get("/customer/{$customer->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('customers.show');
        $response->assertSee('Jane Smith');
        $response->assertSee('jane@example.com');
        $response->assertSee('First Issue');
        $response->assertSee('Second Issue');
        $response->assertViewHas('customer', function ($c) use ($customer) {
            return $c->id === $customer->id;
        });
    }

    #[Test]
    public function user_can_update_customer_details(): void
    {
        // Arrange
        $customer = Customer::factory()->create([
            'first_name' => 'Old',
            'last_name' => 'Name',
        ]);

        // Act
        $response = $this->actingAs($this->user)->patch("/customer/{$customer->id}", [
            'first_name' => 'New',
            'last_name' => 'Updated',
            'company' => 'Acme Corp',
            'job_title' => 'Developer',
            'city' => 'New York',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Customer updated successfully.',
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'first_name' => 'New',
            'last_name' => 'Updated',
            'company' => 'Acme Corp',
        ]);
    }

    #[Test]
    public function cannot_create_customer_with_duplicate_email_if_meant_to_be_unique(): void
    {
        // Arrange
        $customer = Customer::factory()->create();
        $existingEmail = Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'existing@example.com',
        ]);

        // Act
        // Note: Based on the current implementation, Customer::create() method
        // will return the existing customer if the email already exists
        $newCustomer = Customer::create('existing@example.com', [
            'first_name' => 'Duplicate',
            'last_name' => 'Customer',
        ]);

        // Assert
        // The method should return the existing customer, not create a new one
        $this->assertEquals($customer->id, $newCustomer->id);
        $this->assertDatabaseCount('customers', 1);
    }

    #[Test]
    public function merging_two_customers_reassigns_all_conversations(): void
    {
        // Arrange
        $sourceCustomer = Customer::factory()->create([
            'first_name' => 'Source',
            'last_name' => 'Customer',
        ]);
        Email::factory()->create([
            'customer_id' => $sourceCustomer->id,
            'email' => 'source@example.com',
        ]);

        $targetCustomer = Customer::factory()->create([
            'first_name' => 'Target',
            'last_name' => 'Customer',
        ]);
        Email::factory()->create([
            'customer_id' => $targetCustomer->id,
            'email' => 'target@example.com',
        ]);

        $conversation1 = Conversation::factory()->create([
            'customer_id' => $sourceCustomer->id,
            'subject' => 'Source Conversation 1',
        ]);
        $conversation2 = Conversation::factory()->create([
            'customer_id' => $sourceCustomer->id,
            'subject' => 'Source Conversation 2',
        ]);

        // Act
        $response = $this->actingAs($this->user)->post('/customers/merge', [
            'source_id' => $sourceCustomer->id,
            'target_id' => $targetCustomer->id,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Customers merged successfully.',
        ]);

        // Verify conversations were moved
        $this->assertDatabaseHas('conversations', [
            'id' => $conversation1->id,
            'customer_id' => $targetCustomer->id,
        ]);
        $this->assertDatabaseHas('conversations', [
            'id' => $conversation2->id,
            'customer_id' => $targetCustomer->id,
        ]);

        // Verify source customer was deleted
        $this->assertDatabaseMissing('customers', [
            'id' => $sourceCustomer->id,
        ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_customers_list(): void
    {
        // Act
        $response = $this->get('/customers');

        // Assert
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    #[Test]
    public function unauthenticated_user_cannot_view_customer_details(): void
    {
        // Arrange
        $customer = Customer::factory()->create();

        // Act
        $response = $this->get("/customer/{$customer->id}");

        // Assert
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    #[Test]
    public function unauthenticated_user_cannot_update_customer(): void
    {
        // Arrange
        $customer = Customer::factory()->create();

        // Act
        $response = $this->patch("/customer/{$customer->id}", [
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ]);

        // Assert
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    #[Test]
    public function cannot_update_customer_with_invalid_email_format(): void
    {
        // Arrange
        $customer = Customer::factory()->create();

        // Act
        $response = $this->actingAs($this->user)->patchJson("/customer/{$customer->id}", [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'emails' => [
                ['email' => 'invalid-email', 'type' => 'primary'],
            ],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('emails.0.email');
    }

    #[Test]
    public function cannot_merge_customer_with_same_id(): void
    {
        // Arrange
        $customer = Customer::factory()->create();

        // Act
        $response = $this->actingAs($this->user)->postJson('/customers/merge', [
            'source_id' => $customer->id,
            'target_id' => $customer->id,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('target_id');
    }

    #[Test]
    public function cannot_merge_with_non_existent_customer(): void
    {
        // Arrange
        $customer = Customer::factory()->create();

        // Act
        $response = $this->actingAs($this->user)->postJson('/customers/merge', [
            'source_id' => $customer->id,
            'target_id' => 99999,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('target_id');
    }

    #[Test]
    public function user_can_search_customers(): void
    {
        // Arrange
        $customer1 = Customer::factory()->create([
            'first_name' => 'Alice',
            'last_name' => 'Johnson',
        ]);
        Email::factory()->create([
            'customer_id' => $customer1->id,
            'email' => 'alice@example.com',
        ]);

        $customer2 = Customer::factory()->create([
            'first_name' => 'Bob',
            'last_name' => 'Smith',
        ]);
        Email::factory()->create([
            'customer_id' => $customer2->id,
            'email' => 'bob@example.com',
        ]);

        // Act
        $response = $this->actingAs($this->user)->get('/customers?search=Alice');

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('customers.index');
        $response->assertSee('Alice Johnson');
        $response->assertSee('alice@example.com');
        $response->assertDontSee('Bob Smith');
        $response->assertViewHas('customers', function ($customers) use ($customer1) {
            return $customers->count() === 1 && $customers->first()->id === $customer1->id;
        });
    }

    #[Test]
    public function user_can_access_customer_edit_page(): void
    {
        // Arrange
        $customer = Customer::factory()->create([
            'first_name' => 'Edit',
            'last_name' => 'Test',
            'company' => 'Test Corp',
        ]);
        $email = Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'edit@example.com',
        ]);

        // Act
        $response = $this->actingAs($this->user)->get("/customer/{$customer->id}/edit");

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('customers.edit');
        $response->assertSee('Edit Customer');
        $response->assertSee('value="Edit"', false);
        $response->assertSee('value="Test"', false);
        $response->assertSee('edit@example.com');
        $response->assertSee('Test Corp');
        $response->assertSee('First Name');
        $response->assertSee('Last Name');
    }

    #[Test]
    public function unauthenticated_user_cannot_access_customer_edit_page(): void
    {
        // Arrange
        $customer = Customer::factory()->create();

        // Act
        $response = $this->get("/customer/{$customer->id}/edit");

        // Assert
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    #[Test]
    public function updating_customer_with_empty_optional_fields_works(): void
    {
        // Arrange
        $customer = Customer::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'company' => 'Old Company',
        ]);

        // Act
        $response = $this->actingAs($this->user)->patch("/customer/{$customer->id}", [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'company' => '',
            'job_title' => null,
            'city' => '',
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'first_name' => 'John',
            'company' => null,
        ]);
    }

    #[Test]
    public function cannot_update_customer_without_first_name(): void
    {
        // Arrange
        $customer = Customer::factory()->create();

        // Act
        $response = $this->actingAs($this->user)->patchJson("/customer/{$customer->id}", [
            'first_name' => '',
            'last_name' => 'Doe',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('first_name');
    }

    #[Test]
    public function can_update_customer_with_valid_country_code(): void
    {
        // Arrange
        $customer = Customer::factory()->create();

        // Act
        $response = $this->actingAs($this->user)->patch("/customer/{$customer->id}", [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'country' => 'US',
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'country' => 'US',
        ]);
    }

    #[Test]
    public function merge_fails_with_missing_source_id(): void
    {
        // Arrange
        $customer = Customer::factory()->create();

        // Act
        $response = $this->actingAs($this->user)->postJson('/customers/merge', [
            'target_id' => $customer->id,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('source_id');
    }

    #[Test]
    public function merge_fails_with_missing_target_id(): void
    {
        // Arrange
        $customer = Customer::factory()->create();

        // Act
        $response = $this->actingAs($this->user)->postJson('/customers/merge', [
            'source_id' => $customer->id,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('target_id');
    }

    #[Test]
    public function customer_list_is_paginated(): void
    {
        // Arrange
        Customer::factory()->count(60)->create();

        // Act
        $response = $this->actingAs($this->user)->get('/customers');

        // Assert
        $response->assertStatus(200);
        // Default pagination is 50 per page
        $response->assertSee('50'); // Should see pagination info
    }

    #[Test]
    public function customer_show_page_loads_conversations(): void
    {
        // Arrange
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'subject' => 'Loaded Conversation',
        ]);

        // Act
        $response = $this->actingAs($this->user)->get("/customer/{$customer->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertSee('Loaded Conversation');
    }

    #[Test]
    public function updating_customer_with_social_profiles_works(): void
    {
        // Arrange
        $customer = Customer::factory()->create();

        // Act
        $response = $this->actingAs($this->user)->patch("/customer/{$customer->id}", [
            'first_name' => 'Social',
            'last_name' => 'User',
            'social_profiles' => [
                ['type' => 'twitter', 'value' => '@socialuser'],
            ],
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'first_name' => 'Social',
        ]);
    }

    #[Test]
    public function updating_customer_with_websites_works(): void
    {
        // Arrange
        $customer = Customer::factory()->create();

        // Act
        $response = $this->actingAs($this->user)->patch("/customer/{$customer->id}", [
            'first_name' => 'Web',
            'last_name' => 'User',
            'websites' => [
                ['value' => 'https://example.com'],
            ],
        ]);

        // Assert
        $response->assertStatus(200);
    }
}

