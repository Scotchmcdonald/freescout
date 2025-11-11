<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Events\CustomerCreatedConversation;
use App\Events\CustomerReplied;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Tests for the TestEventSystem command.
 * This command tests the event system by manually firing events.
 */
class TestEventSystemTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test command runs successfully with existing conversation.
     */
    public function test_command_fires_events_with_existing_conversation(): void
    {
        // Arrange
        Event::fake([
            CustomerCreatedConversation::class,
            CustomerReplied::class,
        ]);

        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()
            ->for($mailbox)
            ->for($customer)
            ->create();
        $thread = Thread::factory()
            ->for($conversation)
            ->create([
                'customer_id' => $customer->id,
                'created_by_customer_id' => $customer->id,
            ]);

        // Act
        $this->artisan('freescout:test-events')
            ->expectsOutput('Testing event system...')
            ->expectsOutputToContain("Testing with Conversation ID: {$conversation->id}")
            ->expectsOutputToContain("Customer: {$customer->getMainEmail()}")
            ->expectsOutput('Firing CustomerCreatedConversation event...')
            ->expectsOutput('Firing CustomerReplied event...')
            ->expectsOutputToContain('Events dispatched. Check storage/logs/laravel.log')
            ->assertExitCode(0);

        // Assert
        Event::assertDispatched(CustomerCreatedConversation::class, function ($event) use ($conversation, $thread, $customer) {
            return $event->conversation->id === $conversation->id
                && $event->thread->id === $thread->id
                && $event->customer->id === $customer->id;
        });

        Event::assertDispatched(CustomerReplied::class, function ($event) use ($conversation, $thread, $customer) {
            return $event->conversation->id === $conversation->id
                && $event->thread->id === $thread->id
                && $event->customer->id === $customer->id;
        });
    }

    /**
     * Test command fails when no conversations exist.
     */
    public function test_command_fails_when_no_conversations_exist(): void
    {
        // No conversations created

        // Act
        $this->artisan('freescout:test-events')
            ->expectsOutput('No conversations found. Run freescout:fetch-emails first.')
            ->assertExitCode(1);
    }

    /**
     * Test command fails when conversation has no threads.
     */
    public function test_command_fails_when_conversation_has_no_threads(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        Conversation::factory()
            ->for($mailbox)
            ->for($customer)
            ->create();
        // No threads created

        // Act
        $this->artisan('freescout:test-events')
            ->expectsOutput('Conversation missing thread or customer.')
            ->assertExitCode(1);
    }

    /**
     * Test command fails when conversation has no customer.
     */
    public function test_command_fails_when_conversation_has_no_customer(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()
            ->for($mailbox)
            ->create(['customer_id' => null]);

        // Act
        $this->artisan('freescout:test-events')
            ->expectsOutput('Conversation missing thread or customer.')
            ->assertExitCode(1);
    }

    /**
     * Test command dispatches both events correctly.
     */
    public function test_command_dispatches_both_events(): void
    {
        // Arrange
        Event::fake();

        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()
            ->for($mailbox)
            ->for($customer)
            ->create();
        Thread::factory()
            ->for($conversation)

            ->create();

        // Act
        $this->artisan('freescout:test-events')
            ->assertExitCode(0);

        // Assert - both events dispatched exactly once
        Event::assertDispatchedTimes(CustomerCreatedConversation::class, 1);
        Event::assertDispatchedTimes(CustomerReplied::class, 1);
    }

    /**
     * Test command displays customer email correctly.
     */
    public function test_command_displays_customer_email(): void
    {
        // Arrange
        Event::fake();

        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()
            ->for($mailbox)
            ->for($customer)
            ->create();
        Thread::factory()
            ->for($conversation)

            ->create();

        // Act & Assert
        $this->artisan('freescout:test-events')
            ->expectsOutputToContain("Customer: {$customer->getMainEmail()}")
            ->assertExitCode(0);
    }

    /**
     * Test command uses first conversation with threads.
     */
    public function test_command_uses_first_conversation_with_threads(): void
    {
        // Arrange
        Event::fake();

        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();

        // Create first conversation with thread
        $conversation1 = Conversation::factory()
            ->for($mailbox)
            ->for($customer)
            ->create();
        Thread::factory()
            ->for($conversation1)

            ->create();

        // Create second conversation with thread
        $conversation2 = Conversation::factory()
            ->for($mailbox)
            ->for($customer)
            ->create();
        Thread::factory()
            ->for($conversation2)

            ->create();

        // Act
        $this->artisan('freescout:test-events')
            ->expectsOutputToContain("Testing with Conversation ID: {$conversation1->id}")
            ->assertExitCode(0);
    }

    /**
     * Test command successfully loads conversation with relationships.
     */
    public function test_command_loads_conversation_with_relationships(): void
    {
        // Arrange
        Event::fake();

        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()
            ->for($mailbox)
            ->for($customer)
            ->create();
        $thread = Thread::factory()
            ->for($conversation)

            ->create();

        // Act
        $this->artisan('freescout:test-events')
            ->assertExitCode(0);

        // Assert events were dispatched with proper data
        Event::assertDispatched(CustomerCreatedConversation::class, function ($event) use ($conversation) {
            return $event->conversation->id === $conversation->id
                && $event->conversation->relationLoaded('threads')
                && $event->conversation->relationLoaded('customer');
        });
    }

    /**
     * Test command outputs testing message.
     */
    public function test_command_outputs_testing_message(): void
    {
        // Arrange
        Event::fake();

        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()
            ->for($mailbox)
            ->for($customer)
            ->create();
        Thread::factory()
            ->for($conversation)

            ->create();

        // Act & Assert
        $this->artisan('freescout:test-events')
            ->expectsOutput('Testing event system...')
            ->assertExitCode(0);
    }

    /**
     * Test command outputs log file location.
     */
    public function test_command_outputs_log_file_location(): void
    {
        // Arrange
        Event::fake();

        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()
            ->for($mailbox)
            ->for($customer)
            ->create();
        Thread::factory()
            ->for($conversation)

            ->create();

        // Act & Assert
        $this->artisan('freescout:test-events')
            ->expectsOutputToContain('storage/logs/laravel.log')
            ->assertExitCode(0);
    }

    /**
     * Test command with multiple threads in conversation.
     */
    public function test_command_with_multiple_threads_in_conversation(): void
    {
        // Arrange
        Event::fake();

        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()
            ->for($mailbox)
            ->for($customer)
            ->create();

        // Create multiple threads
        $thread1 = Thread::factory()
            ->for($conversation)

            ->create(['created_at' => now()->subHours(2)]);
        $thread2 = Thread::factory()
            ->for($conversation)

            ->create(['created_at' => now()->subHour()]);

        // Act
        $this->artisan('freescout:test-events')
            ->assertExitCode(0);

        // Assert - should use first thread
        Event::assertDispatched(CustomerCreatedConversation::class, function ($event) use ($thread1) {
            return $event->thread->id === $thread1->id;
        });
    }

    /**
     * Test command handles database integrity with invalid customer ID.
     */
    public function test_command_handles_invalid_customer_data_gracefully(): void
    {
        // Note: SQLite enforces foreign key constraints, so we can't create
        // a conversation with an invalid customer_id. This test documents
        // that the database prevents invalid data at the constraint level.

        // Arrange
        $mailbox = Mailbox::factory()->create();

        // Attempting to create conversation with invalid customer_id would fail
        // at database level due to foreign key constraint, which is correct behavior

        // Instead, test with NULL customer_id
        $conversation = Conversation::factory()
            ->for($mailbox)
            ->create(['customer_id' => null]);

        // Act
        $this->artisan('freescout:test-events')
            ->expectsOutput('Conversation missing thread or customer.')
            ->assertExitCode(1);
    }

    /**
     * Test command fires both event types.
     */
    public function test_command_fires_both_event_types(): void
    {
        // Arrange
        Event::fake();

        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()
            ->for($mailbox)
            ->for($customer)
            ->create();
        Thread::factory()
            ->for($conversation)
            ->create();

        // Act
        $this->artisan('freescout:test-events')
            ->expectsOutput('Firing CustomerCreatedConversation event...')
            ->expectsOutput('Firing CustomerReplied event...')
            ->assertExitCode(0);

        // Assert - both event types dispatched
        Event::assertDispatched(CustomerCreatedConversation::class);
        Event::assertDispatched(CustomerReplied::class);
    }

    /**
     * Test command displays correct conversation ID.
     */
    public function test_command_displays_correct_conversation_id(): void
    {
        // Arrange
        Event::fake();

        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()
            ->for($mailbox)
            ->for($customer)
            ->create();
        Thread::factory()
            ->for($conversation)
            ->create();

        // Act & Assert
        $this->artisan('freescout:test-events')
            ->expectsOutputToContain("Conversation ID: {$conversation->id}")
            ->assertExitCode(0);
    }

    /**
     * Test command with customer that has multiple email addresses.
     */
    public function test_command_displays_customer_main_email(): void
    {
        // Arrange
        Event::fake();

        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()
            ->for($mailbox)
            ->for($customer)
            ->create();
        Thread::factory()
            ->for($conversation)
            ->create();

        $mainEmail = $customer->getMainEmail();

        // Act
        $this->artisan('freescout:test-events')
            ->expectsOutputToContain($mainEmail)
            ->assertExitCode(0);
    }

    /**
     * Test command successfully with minimal data setup.
     */
    public function test_command_works_with_minimal_data_setup(): void
    {
        // Arrange - Create minimal required data
        Event::fake();

        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()
            ->for($mailbox)
            ->for($customer)
            ->create();
        Thread::factory()
            ->for($conversation)
            ->create();

        // Act
        $this->artisan('freescout:test-events')
            ->expectsOutput('Testing event system...')
            ->expectsOutput('Firing CustomerCreatedConversation event...')
            ->expectsOutput('Firing CustomerReplied event...')
            ->assertExitCode(0);

        // Assert both events dispatched
        Event::assertDispatched(CustomerCreatedConversation::class);
        Event::assertDispatched(CustomerReplied::class);
    }

    /**
     * Test command verifies relationships are eager loaded.
     */
    public function test_command_eager_loads_required_relationships(): void
    {
        // Arrange
        Event::fake();

        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()
            ->for($mailbox)
            ->for($customer)
            ->create();
        Thread::factory()
            ->for($conversation)
            ->create();

        // Act
        $this->artisan('freescout:test-events')
            ->assertExitCode(0);

        // Assert - conversation should have threads and customer relationships loaded
        Event::assertDispatched(CustomerCreatedConversation::class, function ($event) {
            return $event->conversation->relationLoaded('threads')
                && $event->conversation->relationLoaded('customer');
        });
    }

    /**
     * Test command handles multiple conversations correctly.
     */
    public function test_command_selects_first_conversation_when_multiple_exist(): void
    {
        // Arrange
        Event::fake();

        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();

        // Create multiple conversations
        $conversation1 = Conversation::factory()
            ->for($mailbox)
            ->for($customer)
            ->create(['created_at' => now()->subDays(2)]);
        Thread::factory()->for($conversation1)->create();

        $conversation2 = Conversation::factory()
            ->for($mailbox)
            ->for($customer)
            ->create(['created_at' => now()->subDay()]);
        Thread::factory()->for($conversation2)->create();

        // Act
        $this->artisan('freescout:test-events')
            ->expectsOutputToContain("Conversation ID: {$conversation1->id}")
            ->assertExitCode(0);

        // Assert - uses first conversation
        Event::assertDispatched(CustomerCreatedConversation::class, function ($event) use ($conversation1) {
            return $event->conversation->id === $conversation1->id;
        });
    }
}
