<?php

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('conversation list loads quickly with many conversations', function () {
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
    expect($duration)->toBeLessThan(2.0, "Conversation list took {$duration}s to load");
});

test('database queries are optimized', function () {
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
    DB::disableQueryLog();

    // Should not have excessive N+1 query problems
    expect(count($queries))->toBeLessThan(50, "Too many database queries: " . count($queries));
});

test('customer list pagination performance', function () {
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
    expect($duration)->toBeLessThan(1.0, "Customer list took {$duration}s to load");
});

test('conversation show page performance', function () {
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

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    $response->assertOk();

    // Check query count
    expect(count($queries))->toBeLessThan(30, "Too many queries for conversation detail: " . count($queries))
        ->and($duration)->toBeLessThan(1.0, "Conversation detail took {$duration}s to load");
});

test('dashboard loads quickly', function () {
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

    expect($duration)->toBeLessThan(1.5, "Dashboard took {$duration}s to load");
});

test('search performance with results', function () {
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

    expect($duration)->toBeLessThan(1.0, "Search took {$duration}s");
});

test('mailbox list performance', function () {
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
    DB::disableQueryLog();

    $response->assertOk();

    expect($duration)->toBeLessThan(0.5, "Mailbox list took {$duration}s")
        ->and(count($queries))->toBeLessThan(30, "Too many queries for mailbox list: " . count($queries));
});

test('no n plus one in conversation threads', function () {
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
    DB::disableQueryLog();

    // Check that we're not making separate queries for each thread
    // The exact number depends on eager loading implementation
    expect(count($queries))->toBeLessThan(25, "Potential N+1 query detected: " . count($queries) . " queries");
});
