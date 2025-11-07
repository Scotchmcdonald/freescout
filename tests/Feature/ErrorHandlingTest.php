<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_existent_conversation_returns_404(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($user)->get(route('conversations.show', 99999));

        $response->assertNotFound();
    }

    public function test_non_existent_customer_returns_404(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($user)->get(route('customers.show', ['customer' => 9999]));

        $response->assertNotFound();
    }

    public function test_guest_access_to_protected_customer_route_redirects_to_login(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->get(route('customers.show', $customer->id));

        $response->assertRedirect(route('login'));
    }

    public function test_unauthorized_mailbox_conversation_access_is_prevented(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        
        // User not assigned to this mailbox
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->actingAs($user)->get(route('conversations.show', $conversation->id));

        // Should be forbidden, redirect, or not found
        $this->assertTrue(
            $response->status() === 403 || 
            $response->status() === 302 ||
            $response->status() === 404
        );
    }

    public function test_invalid_email_format_in_customer_creation_validates(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($user)->post('/customers', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'invalid-email-format', // Invalid
        ]);

        // Should have validation error
        $this->assertTrue(
            $response->status() === 302 && session()->has('errors') ||
            $response->status() === 422
        );
    }

    public function test_empty_required_customer_field_returns_validation_error(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($user)->post('/customers', [
            'first_name' => '', // Empty required field
            'email' => 'test@example.com',
        ]);

        $response->assertSessionHasErrors('first_name');
    }

    public function test_ajax_search_with_empty_query_handled_gracefully(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($user)->post(route('customers.ajax'), ['action' => 'search', 'q' => '']);

        // Should return valid JSON response
        $response->assertOk();
    }

    public function test_sql_injection_attempt_in_customer_search_handled_safely(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        // Attempt SQL injection
        $maliciousQuery = "'; DROP TABLE users; --";

        $response = $this->actingAs($user)->post('/customers/ajax?action=search&q=' . urlencode($maliciousQuery));

        // Should handle gracefully without SQL injection
        $response->assertOk();
        
        // Verify database tables still exist
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_xss_attempt_in_customer_name_is_escaped(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        
        $xssAttempt = "<script>alert('XSS')</script>";
        
        $customer = Customer::factory()->create([
            'first_name' => $xssAttempt,
            'last_name' => 'Test',
        ]);

        $response = $this->actingAs($user)->get(route('customers.show', $customer->id));

        $response->assertOk();
        // Verify script tag is escaped in output
        $response->assertDontSee('<script>', false);
    }

    public function test_accessing_conversation_in_deleted_mailbox_returns_404(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        
        $conversationId = $conversation->id;
        
        // Delete mailbox (which might cascade delete conversation)
        $mailbox->delete();

        $response = $this->actingAs($admin)->get(route('conversations.show', $conversationId));

        // Should return 404 since mailbox/conversation is deleted
        $response->assertNotFound();
    }

    public function test_very_long_search_query_handled_without_error(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        // Very long search query
        $longQuery = str_repeat('a', 1000);

        $response = $this->actingAs($user)->post('/customers/ajax?action=search&q=' . urlencode($longQuery));

        // Should handle gracefully
        $response->assertOk();
    }

    public function test_concurrent_conversation_reply_attempts_handled(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user);
        $customer = Customer::factory()->create();
        
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);

        // First reply
        $response1 = $this->actingAs($user)->post(route('conversations.reply', $conversation->id), [
            'body' => 'First reply',
            'type' => 1,
        ]);

        // Second reply immediately after
        $response2 = $this->actingAs($user)->post(route('conversations.reply', $conversation->id), [
            'body' => 'Second reply',
            'type' => 1,
        ]);

        // Both should succeed
        $this->assertTrue($response1->status() === 302 || $response1->status() === 200);
        $this->assertTrue($response2->status() === 302 || $response2->status() === 200);
    }
}
