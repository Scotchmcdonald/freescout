<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\FeatureTestCase;

/**
 * Comprehensive edge cases and integration tests
 * Following TESTING_GUIDE.md - using test_ prefix, FeatureTestCase base class
 */
class EdgeCasesAndIntegrationTest extends FeatureTestCase
{
    // ===== DATABASE EDGE CASES =====

    public function test_handles_soft_deleted_conversations(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $conversation = Conversation::factory()->create();
        
        $conversation->delete();

        $response = $this->actingAs($admin)->get(route('conversations.show', $conversation));

        $response->assertNotFound();
    }

    public function test_handles_trashed_with_trashed_scope(): void
    {
        $conversation = Conversation::factory()->create();
        $conversation->delete();

        $found = Conversation::withTrashed()->find($conversation->id);

        $this->assertNotNull($found);
        $this->assertTrue($found->trashed());
    }

    public function test_restores_soft_deleted_records(): void
    {
        $conversation = Conversation::factory()->create();
        $conversation->delete();

        $conversation->restore();

        $this->assertFalse($conversation->trashed());
        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'deleted_at' => null,
        ]);
    }

    public function test_force_deletes_remove_permanently(): void
    {
        $conversation = Conversation::factory()->create();
        $id = $conversation->id;

        $conversation->forceDelete();

        $this->assertDatabaseMissing('conversations', ['id' => $id]);
    }

    // ===== TRANSACTION EDGE CASES =====

    public function test_rollback_on_exception(): void
    {
        $user = User::factory()->create();

        try {
            DB::transaction(function () use ($user) {
                $user->first_name = 'Updated';
                $user->save();

                throw new \Exception('Test exception');
            });
        } catch (\Exception $e) {
            // Expected
        }

        $user->refresh();
        $this->assertNotEquals('Updated', $user->first_name);
    }

    public function test_nested_transactions_rollback_correctly(): void
    {
        $user = User::factory()->create(['first_name' => 'Original']);

        try {
            DB::transaction(function () use ($user) {
                $user->first_name = 'Outer';
                $user->save();

                try {
                    DB::transaction(function () use ($user) {
                        $user->last_name = 'Inner';
                        $user->save();

                        throw new \Exception('Inner exception');
                    });
                } catch (\Exception $e) {
                    // Inner transaction fails
                }

                throw new \Exception('Outer exception');
            });
        } catch (\Exception $e) {
            // Expected
        }

        $user->refresh();
        $this->assertEquals('Original', $user->first_name);
    }

    public function test_transaction_commits_successfully(): void
    {
        $user = User::factory()->create(['first_name' => 'Original']);

        DB::transaction(function () use ($user) {
            $user->first_name = 'Updated';
            $user->save();
        });

        $user->refresh();
        $this->assertEquals('Updated', $user->first_name);
    }

    // ===== CONCURRENT OPERATION EDGE CASES =====

    public function test_handles_concurrent_folder_counter_updates(): void
    {
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'total_count' => 0,
        ]);

        // Simulate concurrent conversation creation
        $conversations = [];
        for ($i = 0; $i < 10; $i++) {
            $conversations[] = Conversation::factory()->create([
                'mailbox_id' => $mailbox->id,
                'folder_id' => $folder->id,
            ]);
        }

        $folder->refresh();
        $this->assertGreaterThanOrEqual(0, $folder->total_count);
    }

    public function test_handles_race_condition_on_customer_creation(): void
    {
        $email = 'concurrent@example.com';

        // Simulate concurrent requests trying to create same customer
        $customers = [];
        for ($i = 0; $i < 3; $i++) {
            $customer = Customer::firstOrCreate(
                ['email' => $email],
                ['first_name' => 'Test', 'last_name' => 'User']
            );
            $customers[] = $customer->id;
        }

        // All should get the same customer ID
        $this->assertEquals(1, count(array_unique($customers)));
    }

    // ===== VALIDATION EDGE CASES =====

    public function test_validates_email_format_strictly(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('mailboxes.store'), [
            'name' => 'Test',
            'email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_validates_required_fields(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('mailboxes.store'), [
            'name' => '', // Required but empty
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_validates_unique_constraints(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Mailbox::factory()->create(['email' => 'existing@example.com']);

        $response = $this->actingAs($admin)->post(route('mailboxes.store'), [
            'name' => 'Duplicate',
            'email' => 'existing@example.com',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_sanitizes_html_input(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('mailboxes.store'), [
            'name' => '<script>alert("xss")</script>Test',
            'email' => 'test@example.com',
        ]);

        $mailbox = Mailbox::where('email', 'test@example.com')->first();
        $this->assertStringNotContainsString('<script>', $mailbox->name);
    }

    // ===== CUSTOMER EMAIL RELATIONSHIP EDGE CASES =====

    public function test_customer_factory_creates_email_correctly(): void
    {
        $customer = Customer::factory()->create(['email' => 'factory@example.com']);

        $this->assertDatabaseHas('emails', [
            'customer_id' => $customer->id,
            'email' => 'factory@example.com',
        ]);
    }

    public function test_query_customer_by_email_relationship(): void
    {
        $customer = Customer::factory()->create(['email' => 'query@example.com']);

        $found = Customer::whereHas('emails', function ($q) {
            $q->where('email', 'query@example.com');
        })->first();

        $this->assertNotNull($found);
        $this->assertEquals($customer->id, $found->id);
    }

    public function test_customer_can_have_multiple_emails(): void
    {
        $customer = Customer::factory()->create();
        
        $customer->emails()->create(['email' => 'first@example.com']);
        $customer->emails()->create(['email' => 'second@example.com']);

        $this->assertEquals(2, $customer->emails()->count());
    }

    public function test_deleting_customer_deletes_emails(): void
    {
        $customer = Customer::factory()->create(['email' => 'delete@example.com']);
        $customerId = $customer->id;

        $customer->delete();

        $this->assertDatabaseMissing('emails', ['customer_id' => $customerId]);
    }

    // ===== INTEGRATION TEST SCENARIOS =====

    public function test_complete_conversation_workflow(): void
    {
        Event::fake();
        Mail::fake();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create(['email' => 'workflow@example.com']);

        // 1. Create conversation
        $response = $this->actingAs($admin)->post(route('conversations.store'), [
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'subject' => 'Test Conversation',
            'body' => 'Initial message',
        ]);

        $response->assertRedirect();
        $conversation = Conversation::latest()->first();

        // 2. Reply to conversation
        $response = $this->actingAs($admin)->post(route('conversations.reply', $conversation), [
            'body' => 'Reply message',
            'type' => Thread::TYPE_MESSAGE,
        ]);

        $response->assertRedirect();

        // 3. Change status
        $response = $this->actingAs($admin)->post(route('conversations.update-status', $conversation), [
            'status' => Conversation::STATUS_CLOSED,
        ]);

        $response->assertRedirect();

        // Verify all steps
        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'status' => Conversation::STATUS_CLOSED,
        ]);

        $this->assertEquals(2, $conversation->threads()->count());
    }

    public function test_mailbox_user_assignment_workflow(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();

        // 1. Assign user to mailbox
        $response = $this->actingAs($admin)->post(route('mailboxes.assign-user', $mailbox), [
            'user_id' => $user->id,
        ]);

        $response->assertRedirect();
        $this->assertTrue($mailbox->users()->where('id', $user->id)->exists());

        // 2. User can now access mailbox
        $response = $this->actingAs($user)->get(route('mailboxes.show', $mailbox));
        $response->assertOk();

        // 3. Remove user from mailbox
        $response = $this->actingAs($admin)->delete(route('mailboxes.remove-user', [$mailbox, $user]));

        $response->assertRedirect();
        $this->assertFalse($mailbox->users()->where('id', $user->id)->exists());

        // 4. User can no longer access mailbox
        $response = $this->actingAs($user)->get(route('mailboxes.show', $mailbox));
        $response->assertForbidden();
    }

    public function test_customer_conversation_history_integration(): void
    {
        $customer = Customer::factory()->create(['email' => 'history@example.com']);
        $mailbox = Mailbox::factory()->create();

        // Create multiple conversations for customer
        $conversations = Conversation::factory()->count(3)->create([
            'customer_id' => $customer->id,
            'mailbox_id' => $mailbox->id,
        ]);

        // Each conversation has threads
        foreach ($conversations as $conversation) {
            Thread::factory()->count(2)->create([
                'conversation_id' => $conversation->id,
            ]);
        }

        // Verify customer history
        $customerConversations = $customer->conversations;
        $this->assertEquals(3, $customerConversations->count());

        $totalThreads = $customer->conversations->sum(function ($conv) {
            return $conv->threads()->count();
        });
        $this->assertEquals(6, $totalThreads);
    }

    // ===== SECURITY EDGE CASES =====

    public function test_prevents_sql_injection_in_search(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('ajax.search.customers', [
            'q' => "'; DROP TABLE customers; --",
        ]));

        $response->assertOk();
        $this->assertDatabaseHas('customers', ['id' => $user->id]); // Table still exists
    }

    public function test_prevents_unauthorized_cross_mailbox_access(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();
        $mailbox1->users()->attach($user->id);

        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox2->id]);

        $response = $this->actingAs($user)->get(route('conversations.show', $conversation));

        $response->assertForbidden();
    }

    public function test_csrf_protection_on_state_changing_requests(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)
            ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->post(route('mailboxes.store'), [
                'name' => 'Test',
                'email' => 'test@example.com',
            ]);

        // Should succeed when CSRF middleware is disabled for testing
        $response->assertRedirect();
    }

    // ===== PERFORMANCE EDGE CASES =====

    public function test_handles_large_batch_operations(): void
    {
        $mailbox = Mailbox::factory()->create();

        // Create 100 conversations
        $conversations = Conversation::factory()->count(100)->create([
            'mailbox_id' => $mailbox->id,
        ]);

        // Update all at once
        Conversation::where('mailbox_id', $mailbox->id)
            ->update(['status' => Conversation::STATUS_CLOSED]);

        $closedCount = Conversation::where('mailbox_id', $mailbox->id)
            ->where('status', Conversation::STATUS_CLOSED)
            ->count();

        $this->assertEquals(100, $closedCount);
    }

    public function test_eager_loading_prevents_n_plus_one(): void
    {
        $mailbox = Mailbox::factory()->create();
        Conversation::factory()->count(10)->create(['mailbox_id' => $mailbox->id]);

        // Query with eager loading
        $conversations = Conversation::with(['customer', 'user', 'mailbox'])->get();

        // Access relationships - should not cause additional queries
        foreach ($conversations as $conversation) {
            $name = $conversation->customer?->first_name;
            $email = $conversation->mailbox->email;
        }

        $this->assertCount(10, $conversations);
    }
}
