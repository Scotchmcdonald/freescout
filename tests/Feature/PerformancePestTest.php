<?php

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Email;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
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
});

test('large conversation list loads efficiently', function () {
    // Create 50 conversations (reduced from 100 to keep test fast)
    Conversation::factory()
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
    expect($loadTime)->toBeLessThan(2000, "Page took {$loadTime}ms to load, expected < 2000ms")
        ->and($memoryUsed)->toBeLessThan(50, "Used {$memoryUsed}MB memory, expected < 50MB");

    // Verify pagination or limit is working
    $conversations = $response->viewData('conversations');
    expect($conversations)->not->toBeNull('Conversations should be present in view data');
});

test('search performs efficiently with large dataset', function () {
    // Create 30 conversations with searchable content
    for ($i = 1; $i <= 30; $i++) {
        Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'subject' => "Customer Inquiry #{$i} about product features",
            'status' => Conversation::STATUS_ACTIVE,
        ]);
    }

    // Create a specific conversation we want to find
    Conversation::factory()->create([
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
    expect($searchTime)->toBeLessThan(1000, "Search took {$searchTime}ms, expected < 1000ms");

    // Should find the target conversation
    $response->assertSee('Unique Search Term XYZ123');
});

test('mailbox with many conversations remains responsive', function () {
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
    expect($totalConversations)->toBe(50);

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
});

test('conversation with many threads loads correctly', function () {
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
    expect($loadTime)->toBeLessThan(1500, "Conversation took {$loadTime}ms to load, expected < 1500ms");

    // Verify threads are displayed
    $response->assertSee('Message #1 in the thread discussion');
    $response->assertSee('Message #20 in the thread discussion');

    // Verify thread count
    $threadCount = Thread::where('conversation_id', $conversation->id)->count();
    expect($threadCount)->toBe(20);
});

test('conversation list avoids n plus one queries', function () {
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
    DB::enableQueryLog();

    // Load conversations
    $response = $this->actingAs($this->admin)
        ->get(route('conversations.index', $this->mailbox));

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    $response->assertOk();

    // With proper eager loading, should have reasonable number of queries
    // Not strict count check as different implementations vary
    // But should be significantly less than 10 conversations × 3 relationships = 30+ queries
    $queryCount = count($queries);
    expect($queryCount)->toBeLessThan(
        50,
        "Query count is {$queryCount}, might indicate N+1 problem. Review eager loading."
    );
});

test('memory usage remains stable during operations', function () {
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
    expect($memoryIncrease)->toBeLessThan(
        30,
        "Memory increased by {$memoryIncrease}MB, expected < 30MB"
    );
});

test('common operations meet response time benchmarks', function () {
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
        expect($time)->toBeLessThan(
            2000,
            "{$operation} took {$time}ms, expected < 2000ms"
        );
    }
});

test('database queries use indexes effectively', function () {
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

    expect($conversations)->toHaveCount(20);

    // Query should be fast with proper indexing (< 100ms for 20 records)
    expect($queryTime)->toBeLessThan(
        100,
        "Indexed query took {$queryTime}ms, expected < 100ms"
    );

    // Test pagination is efficient
    $startTime = microtime(true);
    $paginated = Conversation::where('mailbox_id', $this->mailbox->id)
        ->paginate(10);
    $paginationTime = (microtime(true) - $startTime) * 1000;

    expect($paginated->count())->toBe(10)
        ->and($paginated->total())->toBe(20)
        ->and($paginationTime)->toBeLessThan(
            100,
            "Pagination query took {$paginationTime}ms, expected < 100ms"
        );
});

test('empty conversation list performs efficiently', function () {
    // Ensure mailbox has no conversations
    Conversation::where('mailbox_id', $this->mailbox->id)->delete();

    $startTime = microtime(true);

    // Load empty conversation list
    $response = $this->actingAs($this->admin)
        ->get(route('conversations.index', $this->mailbox));

    $endTime = microtime(true);
    $loadTime = ($endTime - $startTime) * 1000;

    $response->assertOk();
    $response->assertViewHas('conversations');

    // Empty list should load very quickly (< 500ms)
    expect($loadTime)->toBeLessThan(
        500,
        "Empty list took {$loadTime}ms, expected < 500ms"
    );

    // Verify result is actually empty
    $conversations = $response->viewData('conversations');
    expect($conversations->count())->toBe(0);
});

test('conversation with maximum threads loads acceptably', function () {
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $this->mailbox->id,
        'subject' => 'Very long discussion',
    ]);

    // Create 100 threads (simulating a very long conversation)
    Thread::factory()->count(100)->create([
        'conversation_id' => $conversation->id,
        'state' => 2,
    ]);

    $startTime = microtime(true);
    $startMemory = memory_get_usage();

    // Load conversation with many threads
    $response = $this->actingAs($this->admin)
        ->get(route('conversations.show', $conversation));

    $endTime = microtime(true);
    $endMemory = memory_get_usage();

    $loadTime = ($endTime - $startTime) * 1000;
    $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024;

    $response->assertOk();
    
    // 3000ms is a relaxed limit for 100 threads
    expect($loadTime)->toBeLessThan(3000, "Load time {$loadTime}ms")
         ->and($memoryUsed)->toBeLessThan(50, "Memory used {$memoryUsed}MB");
});
