<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Email;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    /** @test */
    public function user_can_view_list_of_customers(): void
    {
        // Arrange
        $customer = Customer::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'john@example.com',
        ]);

        // Act
        $response = $this->actingAs($this->user)->get('/customers');

        // Assert
        $response->assertStatus(200);
        $response->assertSee('John');
        $response->assertSee('Doe');
    }

    /** @test */
    public function user_can_view_single_customer_and_conversation_history(): void
    {
        // Arrange
        $customer = Customer::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'subject' => 'Test Conversation',
        ]);

        // Act
        $response = $this->actingAs($this->user)->get("/customer/{$customer->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertSee('Jane Smith');
        $response->assertSee('Test Conversation');
    }

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
    public function unauthenticated_user_cannot_access_customers_list(): void
    {
        // Act
        $response = $this->get('/customers');

        // Assert
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /** @test */
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

    /** @test */
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

    /** @test */
    public function cannot_update_customer_with_invalid_email_format(): void
    {
        // Arrange
        $customer = Customer::factory()->create();

        // Act
        $response = $this->actingAs($this->user)->patch("/customer/{$customer->id}", [
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

    /** @test */
    public function cannot_merge_customer_with_same_id(): void
    {
        // Arrange
        $customer = Customer::factory()->create();

        // Act
        $response = $this->actingAs($this->user)->post('/customers/merge', [
            'source_id' => $customer->id,
            'target_id' => $customer->id,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('target_id');
    }

    /** @test */
    public function cannot_merge_with_non_existent_customer(): void
    {
        // Arrange
        $customer = Customer::factory()->create();

        // Act
        $response = $this->actingAs($this->user)->post('/customers/merge', [
            'source_id' => $customer->id,
            'target_id' => 99999,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('target_id');
    }

    /** @test */
    public function user_can_search_customers(): void
    {
        // Arrange
        $customer1 = Customer::factory()->create([
            'first_name' => 'Alice',
            'last_name' => 'Johnson',
        ]);
        $customer2 = Customer::factory()->create([
            'first_name' => 'Bob',
            'last_name' => 'Smith',
        ]);

        // Act
        $response = $this->actingAs($this->user)->get('/customers?search=Alice');

        // Assert
        $response->assertStatus(200);
        $response->assertSee('Alice');
        $response->assertDontSee('Bob');
    }
}
