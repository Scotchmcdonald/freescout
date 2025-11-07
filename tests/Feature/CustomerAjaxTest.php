<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Email;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAjaxTest extends TestCase
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
    public function ajax_search_returns_matching_customers_by_first_name(): void
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
        $response = $this->actingAs($this->user)->post('/customers/ajax', [
            'action' => 'search',
            'query' => 'Alice',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        
        $customers = $response->json('customers');
        $this->assertCount(1, $customers);
        $this->assertEquals('Alice Johnson', $customers[0]['name']);
    }

    /** @test */
    public function ajax_search_returns_matching_customers_by_last_name(): void
    {
        // Arrange
        $customer = Customer::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        // Act
        $response = $this->actingAs($this->user)->post('/customers/ajax', [
            'action' => 'search',
            'query' => 'Doe',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        
        $customers = $response->json('customers');
        $this->assertGreaterThanOrEqual(1, count($customers));
        $this->assertStringContainsString('Doe', $customers[0]['name']);
    }

    /** @test */
    public function ajax_search_limits_results_to_25(): void
    {
        // Arrange
        Customer::factory()->count(30)->create([
            'first_name' => 'Test',
        ]);

        // Act
        $response = $this->actingAs($this->user)->post('/customers/ajax', [
            'action' => 'search',
            'query' => 'Test',
        ]);

        // Assert
        $response->assertStatus(200);
        $customers = $response->json('customers');
        $this->assertLessThanOrEqual(25, count($customers));
    }

    /** @test */
    public function ajax_conversations_returns_customer_conversations(): void
    {
        // Arrange
        $customer = Customer::factory()->create();
        $conversation1 = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'subject' => 'Test Subject 1',
            'state' => 2, // Published
        ]);
        $conversation2 = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'subject' => 'Test Subject 2',
            'state' => 2,
        ]);
        // Create a draft conversation that should not be returned
        $draftConversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'subject' => 'Draft Conversation',
            'state' => 1, // Draft
        ]);

        // Act
        $response = $this->actingAs($this->user)->post('/customers/ajax', [
            'action' => 'conversations',
            'customer_id' => $customer->id,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        
        $conversations = $response->json('conversations');
        $this->assertEquals(2, count($conversations));
    }

    /** @test */
    public function ajax_conversations_orders_by_last_reply_at_desc(): void
    {
        // Arrange
        $customer = Customer::factory()->create();
        $oldConversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'subject' => 'Old Conversation',
            'state' => 2,
            'last_reply_at' => now()->subDays(5),
        ]);
        $newConversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'subject' => 'New Conversation',
            'state' => 2,
            'last_reply_at' => now()->subDays(1),
        ]);

        // Act
        $response = $this->actingAs($this->user)->post('/customers/ajax', [
            'action' => 'conversations',
            'customer_id' => $customer->id,
        ]);

        // Assert
        $response->assertStatus(200);
        $conversations = $response->json('conversations');
        
        // First conversation should be the newest one
        $this->assertEquals('New Conversation', $conversations[0]['subject']);
        $this->assertEquals('Old Conversation', $conversations[1]['subject']);
    }

    /** @test */
    public function ajax_conversations_limits_to_50_results(): void
    {
        // Arrange
        $customer = Customer::factory()->create();
        Conversation::factory()->count(60)->create([
            'customer_id' => $customer->id,
            'state' => 2,
        ]);

        // Act
        $response = $this->actingAs($this->user)->post('/customers/ajax', [
            'action' => 'conversations',
            'customer_id' => $customer->id,
        ]);

        // Assert
        $response->assertStatus(200);
        $conversations = $response->json('conversations');
        $this->assertLessThanOrEqual(50, count($conversations));
    }

    /** @test */
    public function ajax_returns_error_for_invalid_action(): void
    {
        // Act
        $response = $this->actingAs($this->user)->post('/customers/ajax', [
            'action' => 'invalid_action',
        ]);

        // Assert
        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Invalid action',
        ]);
    }

    /** @test */
    public function ajax_requires_authentication(): void
    {
        // Act
        $response = $this->post('/customers/ajax', [
            'action' => 'search',
            'query' => 'test',
        ]);

        // Assert
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /** @test */
    public function ajax_search_returns_empty_array_when_no_matches(): void
    {
        // Arrange
        Customer::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        // Act
        $response = $this->actingAs($this->user)->post('/customers/ajax', [
            'action' => 'search',
            'query' => 'NonExistentCustomer',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'customers' => [],
        ]);
    }

    /** @test */
    public function ajax_search_returns_customer_with_email(): void
    {
        // Arrange
        $customer = Customer::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);
        Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'jane@example.com',
            'type' => 1, // Primary
        ]);

        // Act
        $response = $this->actingAs($this->user)->post('/customers/ajax', [
            'action' => 'search',
            'query' => 'Jane',
        ]);

        // Assert
        $response->assertStatus(200);
        $customers = $response->json('customers');
        $this->assertEquals('jane@example.com', $customers[0]['email']);
    }

    /** @test */
    public function ajax_conversations_returns_empty_array_for_customer_with_no_conversations(): void
    {
        // Arrange
        $customer = Customer::factory()->create();

        // Act
        $response = $this->actingAs($this->user)->post('/customers/ajax', [
            'action' => 'conversations',
            'customer_id' => $customer->id,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'conversations' => [],
        ]);
    }
}
