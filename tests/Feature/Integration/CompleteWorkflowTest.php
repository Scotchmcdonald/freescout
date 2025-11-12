<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompleteWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_complete_full_ticket_lifecycle(): void
    {
        // 1. Create admin user
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // 2. Create mailbox
        $this->actingAs($admin)
            ->post(route('mailboxes.store'), [
                'name' => 'Support',
                'email' => 'support@example.com',
            ])
            ->assertRedirect();

        $mailbox = Mailbox::first();
        $this->assertNotNull($mailbox);
        $this->assertEquals('Support', $mailbox->name);

        // 3. Create customer
        $customer = Customer::factory()->create();

        // 4. Create conversation
        $customerEmail = $customer->getMainEmail();
        $this->assertNotNull($customerEmail, 'Customer should have email');
        
        $response = $this->actingAs($admin)
            ->post(route('conversations.store', $mailbox), [
                'mailbox_id' => $mailbox->id,
                'customer_id' => $customer->id,
                'subject' => 'Test Ticket',
                'body' => 'This is a test message',
                'to' => [$customerEmail],
            ]);
        
        if ($response->status() !== 302) {
            dump($response->getContent());
        }
        $response->assertRedirect();

        $conversation = Conversation::first();
        $this->assertNotNull($conversation);
        $this->assertEquals('Test Ticket', $conversation->subject);

        // 5. Reply to conversation
        $this->actingAs($admin)
            ->post(route('conversations.reply', $conversation), [
                'body' => 'Thank you for contacting us.',
                'type' => 1, // Message type
            ])
            ->assertRedirect();

        // 6. Verify thread created
        $this->assertGreaterThanOrEqual(1, $conversation->fresh()->threads()->count());

        // 7. Close conversation
        $this->actingAs($admin)
            ->patch(route('conversations.update', $conversation), [
                'status' => Conversation::STATUS_CLOSED,
            ])
            ->assertRedirect();

        // 8. Verify conversation closed
        $this->assertEquals(Conversation::STATUS_CLOSED, $conversation->fresh()->status);
    }

    public function test_regular_user_workflow_respects_permissions(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();

        // User cannot create mailbox (admin only)
        $this->actingAs($user)
            ->post(route('mailboxes.store'), [
                'name' => 'Test',
                'email' => 'test@example.com',
            ])
            ->assertForbidden();

        // Grant mailbox access
        $mailbox->users()->attach($user->id);

        // User CAN create conversation in assigned mailbox
        $customer = Customer::factory()->create();
        $this->actingAs($user)
            ->post(route('conversations.store', $mailbox), [
                'mailbox_id' => $mailbox->id,
                'customer_id' => $customer->id,
                'subject' => 'Test',
                'body' => 'Message',
                'to' => [$customer->getMainEmail()],
            ])
            ->assertRedirect();

        $this->assertCount(1, Conversation::all());
    }

    public function test_customer_management_workflow(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // Create customer
        $response = $this->actingAs($admin)
            ->post(route('customers.store'), [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
            ])
            ->assertRedirect();

        $customer = Customer::whereHas('emails', function ($query) {
            $query->where('email', 'john@example.com');
        })->first();
        $this->assertNotNull($customer);
        $this->assertEquals('John', $customer->first_name);

        // Update customer
        $this->actingAs($admin)
            ->patchJson(route('customers.update', $customer), [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertEquals('Jane', $customer->fresh()->first_name);

        // View customer profile
        $this->actingAs($admin)
            ->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('Jane Doe');
    }

    public function test_user_management_workflow(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // Create new user
        $this->actingAs($admin)
            ->post(route('users.store'), [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'testuser@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => User::ROLE_USER,
                'status' => User::STATUS_ACTIVE,
            ])
            ->assertRedirect();

        $newUser = User::where('email', 'testuser@example.com')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals(User::ROLE_USER, $newUser->role);

        // Update user
        $this->actingAs($admin)
            ->put(route('users.update', $newUser), [
                'first_name' => 'Updated',
                'last_name' => 'User',
                'email' => 'testuser@example.com',
                'role' => User::ROLE_USER,
                'status' => User::STATUS_ACTIVE,
            ])
            ->assertRedirect();

        $this->assertEquals('Updated', $newUser->fresh()->first_name);
    }

    public function test_mailbox_settings_workflow(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();

        // Update mailbox settings
        $this->actingAs($admin)
            ->patch(route('mailboxes.update', $mailbox), [
                'name' => 'Updated Mailbox',
                'email' => $mailbox->email, // Keep original email
            ])
            ->assertRedirect();

        $this->assertEquals('Updated Mailbox', $mailbox->fresh()->name);

        // Access mailbox settings page
        $this->actingAs($admin)
            ->get(route('mailboxes.settings', $mailbox))
            ->assertOk();
    }

    public function test_conversation_search_workflow(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();

        // Create conversations with searchable content
        Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'subject' => 'Unique Search Term ABC123',
        ]);

        Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'subject' => 'Different Subject',
        ]);

        // Search for specific conversation
        $response = $this->actingAs($user)
            ->get(route('conversations.search', ['q' => 'ABC123']))
            ->assertOk();

        // Note: Actual search implementation may vary
        // This tests that the route is accessible
    }

    public function test_authentication_required_for_protected_routes(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create();
        $customer = Customer::factory()->create();
        $user = User::factory()->create();

        // Test that all main routes require authentication
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('mailboxes.index'))->assertRedirect(route('login'));
        $this->get(route('conversations.show', $conversation))->assertRedirect(route('login'));
        $this->get(route('customers.index'))->assertRedirect(route('login'));
        $this->get(route('settings'))->assertRedirect(route('login'));
    }

    public function test_error_pages_are_accessible(): void
    {
        // Test error pages don't crash
        $response = $this->get('/nonexistent-page');
        $this->assertTrue(in_array($response->status(), [404]));
    }
}
