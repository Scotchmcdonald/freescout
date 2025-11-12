<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversation_list_loads_quickly_with_many_conversations(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        
        // Give user access to mailbox
        $mailbox->users()->attach($user->id);

        // Create 100 conversations (reduced from 1000 for faster test execution)
        Conversation::factory()->count(100)->create([
            'mailbox_id' => $mailbox->id,
        ]);

        // Measure response time
        $startTime = microtime(true);

        $response = $this->actingAs($user)
            ->get(route('conversations.index', $mailbox));

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $response->assertOk();

        // Should load in under 2 seconds (generous threshold for CI environments)
        $this->assertLessThan(2.0, $duration,
            "Conversation list took {$duration}s to load (should be < 2.0s)");
    }

    public function test_database_queries_are_optimized(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        
        // Give user access to mailbox
        $mailbox->users()->attach($user->id);
        
        Conversation::factory()->count(20)->create([
            'mailbox_id' => $mailbox->id,
        ]);

        // Enable query logging
        DB::enableQueryLog();

        $this->actingAs($user)
            ->get(route('conversations.index', $mailbox))
            ->assertOk();

        $queries = DB::getQueryLog();

        // Should not have excessive N+1 query problems
        // Adjust number based on expected queries (includes auth, session, etc.)
        $this->assertLessThan(50, count($queries),
            "Too many database queries: " . count($queries));

        DB::disableQueryLog();
    }

    public function test_customer_list_pagination_performance(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // Create 50 customers
        Customer::factory()->count(50)->create();

        $startTime = microtime(true);

        $response = $this->actingAs($user)
            ->get(route('customers.index'));

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $response->assertOk();

        // Should load quickly
        $this->assertLessThan(1.0, $duration,
            "Customer list took {$duration}s to load (should be < 1.0s)");
    }

    public function test_conversation_show_page_performance(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        
        // Give user access to mailbox
        $mailbox->users()->attach($user->id);
        
        $conversation = Conversation::factory()
            ->hasThreads(10) // Conversation with 10 threads
            ->create(['mailbox_id' => $mailbox->id]);

        // Enable query logging
        DB::enableQueryLog();

        $startTime = microtime(true);

        $response = $this->actingAs($user)
            ->get(route('conversations.show', $conversation));

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $response->assertOk();

        $queries = DB::getQueryLog();

        // Check query count
        $this->assertLessThan(30, count($queries),
            "Too many queries for conversation detail: " . count($queries));

        // Check response time
        $this->assertLessThan(1.0, $duration,
            "Conversation detail took {$duration}s to load (should be < 1.0s)");

        DB::disableQueryLog();
    }

    public function test_dashboard_loads_quickly(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // Create some test data
        $mailbox = Mailbox::factory()->create();
        Conversation::factory()->count(10)->create(['mailbox_id' => $mailbox->id]);

        $startTime = microtime(true);

        $response = $this->actingAs($user)
            ->get(route('dashboard'));

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $response->assertOk();

        $this->assertLessThan(1.5, $duration,
            "Dashboard took {$duration}s to load (should be < 1.5s)");
    }

    public function test_search_performance_with_results(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();

        // Create conversations with searchable content
        for ($i = 0; $i < 20; $i++) {
            Conversation::factory()->create([
                'mailbox_id' => $mailbox->id,
                'subject' => "Test Subject {$i}",
            ]);
        }

        $startTime = microtime(true);

        $response = $this->actingAs($user)
            ->get(route('conversations.search', ['q' => 'Test']));

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $response->assertOk();

        $this->assertLessThan(1.0, $duration,
            "Search took {$duration}s (should be < 1.0s)");
    }

    public function test_mailbox_list_performance(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // Create multiple mailboxes
        Mailbox::factory()->count(10)->create();

        DB::enableQueryLog();

        $startTime = microtime(true);

        $response = $this->actingAs($user)
            ->get(route('mailboxes.index'));

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $queries = DB::getQueryLog();

        $response->assertOk();

        $this->assertLessThan(0.5, $duration,
            "Mailbox list took {$duration}s (should be < 0.5s)");

        $this->assertLessThan(25, count($queries),
            "Too many queries for mailbox list: " . count($queries));

        DB::disableQueryLog();
    }

    public function test_no_n_plus_one_in_conversation_threads(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        
        // Give user access to mailbox
        $mailbox->users()->attach($user->id);

        // Create conversation with multiple threads
        $conversation = Conversation::factory()
            ->hasThreads(5)
            ->create(['mailbox_id' => $mailbox->id]);

        DB::enableQueryLog();

        $this->actingAs($user)
            ->get(route('conversations.show', $conversation))
            ->assertOk();

        $queries = DB::getQueryLog();

        // Check that we're not making separate queries for each thread
        // The exact number depends on eager loading implementation
        $this->assertLessThan(25, count($queries),
            "Potential N+1 query detected: " . count($queries) . " queries");

        DB::disableQueryLog();
    }
}
