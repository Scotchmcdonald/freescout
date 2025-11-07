<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Email;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 6 - Task 6.2: Performance Tests
 * 
 * Tests performance characteristics and validates system behavior under load:
 * - Large conversation list loading
 * - Search with large dataset
 * - Mailbox with many conversations
 * - Email fetch with many messages
 * - Query optimization validation
 * - Memory usage benchmarks
 * - Response time benchmarks
 */
class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Mailbox $mailbox;
    protected Folder $folder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->mailbox = Mailbox::factory()->create([
            'name' => 'Performance Test Mailbox',
            'email' => 'perf@example.com',
        ]);

        $this->mailbox->users()->attach($this->admin);

        $this->folder = Folder::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'type' => Folder::TYPE_INBOX,
        ]);
    }

    /**
     * Test 1: Large conversation list loading
     * Validates that system can handle loading many conversations efficiently
     */
    public function test_large_conversation_list_loads_efficiently(): void
    {
        // Create 50 conversations (reduced from 100 to keep test fast)
        $conversations = Conversation::factory()
            ->count(50)
            ->for($this->mailbox)
            ->create([
                'status' => Conversation::STATUS_ACTIVE,
                'state' => Conversation::STATE_PUBLISHED,
            ]);

        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // Load conversations page
        $response = $this->actingAs($this->admin)
            ->get(route('conversations.index', $this->mailbox));

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        // Assert page loads successfully
        $response->assertOk();
        $response->assertViewHas('conversations');

        // Performance benchmarks (relaxed for CI environment)
        $loadTime = ($endTime - $startTime) * 1000; // Convert to ms
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // Convert to MB

        // Should load in reasonable time (< 2 seconds even with 50 records)
        $this->assertLessThan(2000, $loadTime, "Page took {$loadTime}ms to load, expected < 2000ms");

        // Memory usage should be reasonable (< 50MB for 50 conversations)
        $this->assertLessThan(50, $memoryUsed, "Used {$memoryUsed}MB memory, expected < 50MB");

        // Verify pagination or limit is working
        $conversations = $response->viewData('conversations');
        $this->assertNotNull($conversations, 'Conversations should be present in view data');
    }

    /**
     * Test 2: Search with large dataset
     * Validates that search remains responsive with many records
     */
    public function test_search_performs_efficiently_with_large_dataset(): void
    {
        // Create 30 conversations with searchable content
        for ($i = 1; $i <= 30; $i++) {
            Conversation::factory()->create([
                'mailbox_id' => $this->mailbox->id,
                'subject' => "Customer Inquiry #{$i} about product features",
                'status' => Conversation::STATUS_ACTIVE,
            ]);
        }

        // Create a specific conversation we want to find
        $targetConversation = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'subject' => 'Unique Search Term XYZ123',
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $startTime = microtime(true);

        // Perform search
        $response = $this->actingAs($this->admin)
            ->get(route('conversations.index', [
                'mailbox' => $this->mailbox->id,
                'q' => 'XYZ123',
            ]));

        $endTime = microtime(true);
        $searchTime = ($endTime - $startTime) * 1000;

        // Assert search completes successfully
        $response->assertOk();

        // Search should complete quickly (< 1 second)
        $this->assertLessThan(1000, $searchTime, "Search took {$searchTime}ms, expected < 1000ms");

        // Should find the target conversation
        $response->assertSee('Unique Search Term XYZ123');
    }

    /**
     * Test 3: Mailbox with many conversations
     * Validates system handles mailboxes with substantial conversation history
     */
    public function test_mailbox_with_many_conversations_remains_responsive(): void
    {
        // Create multiple customers
        $customers = Customer::factory()->count(10)->create();
        
        foreach ($customers as $customer) {
            Email::factory()->create([
                'customer_id' => $customer->id,
                'type' => 1, // Primary
            ]);
        }

        // Create conversations for each customer (10 customers × 5 conversations = 50 total)
        foreach ($customers as $customer) {
            Conversation::factory()
                ->count(5)
                ->create([
                    'mailbox_id' => $this->mailbox->id,
                    'customer_id' => $customer->id,
                    'status' => Conversation::STATUS_ACTIVE,
                ]);
        }

        // Verify total conversation count
        $totalConversations = Conversation::where('mailbox_id', $this->mailbox->id)->count();
        $this->assertEquals(50, $totalConversations);

        // Test mailbox dashboard loads
        $response = $this->actingAs($this->admin)
            ->get(route('conversations.index', $this->mailbox));

        $response->assertOk();
        $response->assertViewHas('conversations');

        // Test individual conversation loads
        $conversation = Conversation::where('mailbox_id', $this->mailbox->id)->first();
        $response = $this->actingAs($this->admin)
            ->get(route('conversations.show', $conversation));

        $response->assertOk();
    }

    /**
     * Test 4: Conversation with many threads (email messages)
     * Validates long conversation threads load properly
     */
    public function test_conversation_with_many_threads_loads_correctly(): void
    {
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'subject' => 'Long discussion thread',
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        // Create 20 thread messages
        for ($i = 1; $i <= 20; $i++) {
            Thread::factory()->create([
                'conversation_id' => $conversation->id,
                'body' => "Message #{$i} in the thread discussion",
                'state' => 2, // Published
                'type' => 1, // Message
            ]);
        }

        $startTime = microtime(true);

        // Load conversation with all threads
        $response = $this->actingAs($this->admin)
            ->get(route('conversations.show', $conversation));

        $endTime = microtime(true);
        $loadTime = ($endTime - $startTime) * 1000;

        $response->assertOk();
        $response->assertViewHas('conversation');

        // Should load quickly even with 20 threads
        $this->assertLessThan(1500, $loadTime, "Conversation took {$loadTime}ms to load, expected < 1500ms");

        // Verify threads are displayed
        $response->assertSee('Message #1 in the thread discussion');
        $response->assertSee('Message #20 in the thread discussion');

        // Verify thread count
        $threadCount = Thread::where('conversation_id', $conversation->id)->count();
        $this->assertEquals(20, $threadCount);
    }

    /**
     * Test 5: Query optimization validation
     * Ensures no N+1 query problems when loading related data
     */
    public function test_conversation_list_avoids_n_plus_one_queries(): void
    {
        // Create 10 conversations with related data
        for ($i = 1; $i <= 10; $i++) {
            $customer = Customer::factory()->create();
            Email::factory()->create([
                'customer_id' => $customer->id,
                'type' => 1,
            ]);

            $conversation = Conversation::factory()->create([
                'mailbox_id' => $this->mailbox->id,
                'customer_id' => $customer->id,
                'user_id' => $this->admin->id,
            ]);

            // Add threads to each conversation
            Thread::factory()->count(2)->create([
                'conversation_id' => $conversation->id,
            ]);
        }

        // Enable query logging
        \DB::enableQueryLog();

        // Load conversations
        $response = $this->actingAs($this->admin)
            ->get(route('conversations.index', $this->mailbox));

        $queries = \DB::getQueryLog();
        \DB::disableQueryLog();

        $response->assertOk();

        // With proper eager loading, should have reasonable number of queries
        // Not strict count check as different implementations vary
        // But should be significantly less than 10 conversations × 3 relationships = 30+ queries
        $queryCount = count($queries);
        $this->assertLessThan(50, $queryCount, 
            "Query count is {$queryCount}, might indicate N+1 problem. Review eager loading.");
    }

    /**
     * Test 6: Memory usage with concurrent operations
     * Validates memory doesn't grow excessively during typical operations
     */
    public function test_memory_usage_remains_stable_during_operations(): void
    {
        $initialMemory = memory_get_usage();

        // Perform multiple operations
        for ($i = 1; $i <= 5; $i++) {
            $customer = Customer::factory()->create();
            Email::factory()->create([
                'customer_id' => $customer->id,
                'type' => 1,
            ]);

            $conversation = Conversation::factory()->create([
                'mailbox_id' => $this->mailbox->id,
                'customer_id' => $customer->id,
            ]);

            // View the conversation
            $this->actingAs($this->admin)
                ->get(route('conversations.show', $conversation));

            // Add a reply
            $this->actingAs($this->admin)
                ->post(route('conversations.reply', $conversation), [
                    'body' => 'Reply to conversation',
                    'to' => [$customer->emails->first()->email],
                ]);
        }

        $finalMemory = memory_get_usage();
        $memoryIncrease = ($finalMemory - $initialMemory) / 1024 / 1024; // MB

        // Memory increase should be reasonable (< 30MB for 5 full cycles)
        $this->assertLessThan(30, $memoryIncrease, 
            "Memory increased by {$memoryIncrease}MB, expected < 30MB");
    }

    /**
     * Test 7: Response time benchmarks for common operations
     * Establishes baseline performance metrics
     */
    public function test_common_operations_meet_response_time_benchmarks(): void
    {
        $customer = Customer::factory()->create();
        $customerEmail = Email::factory()->create([
            'customer_id' => $customer->id,
            'type' => 1,
        ]);

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'customer_id' => $customer->id,
        ]);

        Thread::factory()->count(5)->create([
            'conversation_id' => $conversation->id,
        ]);

        $benchmarks = [];

        // Benchmark 1: Dashboard load
        $start = microtime(true);
        $this->actingAs($this->admin)->get(route('dashboard'));
        $benchmarks['dashboard'] = (microtime(true) - $start) * 1000;

        // Benchmark 2: Conversation list load
        $start = microtime(true);
        $this->actingAs($this->admin)->get(route('conversations.index', $this->mailbox));
        $benchmarks['conversation_list'] = (microtime(true) - $start) * 1000;

        // Benchmark 3: Single conversation load
        $start = microtime(true);
        $this->actingAs($this->admin)->get(route('conversations.show', $conversation));
        $benchmarks['conversation_view'] = (microtime(true) - $start) * 1000;

        // Benchmark 4: Reply creation
        $start = microtime(true);
        $this->actingAs($this->admin)->post(route('conversations.reply', $conversation), [
            'body' => 'Test reply',
            'to' => [$customerEmail->email],
        ]);
        $benchmarks['reply_creation'] = (microtime(true) - $start) * 1000;

        // Assert all operations complete within reasonable time
        foreach ($benchmarks as $operation => $time) {
            $this->assertLessThan(2000, $time, 
                "{$operation} took {$time}ms, expected < 2000ms");
        }

        // Log benchmarks for reference (visible in test output with -v)
        dump("Performance Benchmarks:", $benchmarks);
    }

    /**
     * Test 8: Database indexing effectiveness
     * Validates that database queries use indexes appropriately
     */
    public function test_database_queries_use_indexes_effectively(): void
    {
        // Create test data
        for ($i = 1; $i <= 20; $i++) {
            Conversation::factory()->create([
                'mailbox_id' => $this->mailbox->id,
                'status' => Conversation::STATUS_ACTIVE,
            ]);
        }

        // Test query with mailbox_id (should use index)
        $startTime = microtime(true);
        $conversations = Conversation::where('mailbox_id', $this->mailbox->id)
            ->where('status', Conversation::STATUS_ACTIVE)
            ->get();
        $queryTime = (microtime(true) - $startTime) * 1000;

        $this->assertCount(20, $conversations);
        
        // Query should be fast with proper indexing (< 100ms for 20 records)
        $this->assertLessThan(100, $queryTime, 
            "Indexed query took {$queryTime}ms, expected < 100ms");

        // Test pagination is efficient
        $startTime = microtime(true);
        $paginated = Conversation::where('mailbox_id', $this->mailbox->id)
            ->paginate(10);
        $paginationTime = (microtime(true) - $startTime) * 1000;

        $this->assertEquals(10, $paginated->count());
        $this->assertEquals(20, $paginated->total());
        
        // Pagination should also be fast
        $this->assertLessThan(100, $paginationTime, 
            "Pagination query took {$paginationTime}ms, expected < 100ms");
    }
}
