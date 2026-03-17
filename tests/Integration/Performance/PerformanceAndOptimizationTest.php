<?php

declare(strict_types=1);

namespace Tests\Integration\Performance;

use App\Models\Conversation;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\IntegrationTestCase;

class PerformanceAndOptimizationTest extends IntegrationTestCase
{
    // Query Optimization Tests
    public function test_select_only_needed_columns(): void
    {
        Conversation::factory()->count(10)->create();

        DB::enableQueryLog();

        $conversations = Conversation::select('id', 'subject', 'status')->get();

        $queries = DB::getQueryLog();
        // Normalize quotes for cross-database compatibility (MySQL uses backticks, SQLite/Postgres use double quotes)
        $normalizedQuery = str_replace(['"', '`'], '', strtolower($queries[0]['query']));
        $this->assertStringContainsString('select id, subject, status', $normalizedQuery);

        DB::disableQueryLog();
    }

    public function test_pagination_limits_results(): void
    {
        Conversation::factory()->count(100)->create();

        $paginated = Conversation::paginate(10);

        $this->assertCount(10, $paginated->items());
        $this->assertEquals(100, $paginated->total());
    }

    public function test_where_in_optimization(): void
    {
        $mailboxIds = Mailbox::factory()->count(5)->create()->pluck('id');
        Conversation::factory()->count(20)->create();

        DB::enableQueryLog();

        $conversations = Conversation::whereIn('mailbox_id', $mailboxIds)->get();

        $queryCount = count(DB::getQueryLog());
        $this->assertEquals(1, $queryCount);

        DB::disableQueryLog();
    }

    public function test_first_is_more_efficient_than_get_first(): void
    {
        Conversation::factory()->count(100)->create();

        DB::enableQueryLog();

        $conversation = Conversation::where('status', Conversation::STATUS_ACTIVE)->first();

        $queries = DB::getQueryLog();
        $this->assertStringContainsString('limit', strtolower($queries[0]['query']));

        DB::disableQueryLog();
    }

    // Caching Tests
    public function test_cache_remember_reduces_queries(): void
    {
        $mailbox = Mailbox::factory()->create();

        Cache::forget('mailbox_'.$mailbox->id);

        DB::enableQueryLog();

        $cached1 = Cache::remember('mailbox_'.$mailbox->id, 60, function () use ($mailbox) {
            return Mailbox::find($mailbox->id);
        });

        $queriesFirst = count(DB::getQueryLog());

        $cached2 = Cache::remember('mailbox_'.$mailbox->id, 60, function () use ($mailbox) {
            return Mailbox::find($mailbox->id);
        });

        $queriesSecond = count(DB::getQueryLog());

        $this->assertEquals($queriesFirst, $queriesSecond);

        DB::disableQueryLog();
        Cache::forget('mailbox_'.$mailbox->id);
    }

    // Batch Operations
    public function test_bulk_insert_is_efficient(): void
    {
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id]);

        $data = [];
        for ($i = 0; $i < 100; $i++) {
            $data[] = [
                'mailbox_id' => $mailbox->id,
                'subject' => "Test Subject $i",
                'status' => Conversation::STATUS_ACTIVE,
                'created_at' => now(),
                'updated_at' => now(),
                'number' => $i + 1000,
                'type' => Conversation::TYPE_EMAIL,
                'folder_id' => $folder->id,
                'preview' => 'Test Preview',
                'source_via' => Conversation::PERSON_CUSTOMER,
                'source_type' => 1,
            ];
        }

        DB::enableQueryLog();

        Conversation::insert($data);

        $queryCount = count(DB::getQueryLog());
        $this->assertEquals(1, $queryCount);

        DB::disableQueryLog();
    }

    public function test_bulk_update_is_efficient(): void
    {
        $conversations = Conversation::factory()->count(10)->create([
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        DB::enableQueryLog();

        Conversation::whereIn('id', $conversations->pluck('id'))
            ->update(['status' => Conversation::STATUS_CLOSED]);

        $queryCount = count(DB::getQueryLog());
        $this->assertEquals(1, $queryCount);

        DB::disableQueryLog();
    }

    // Index Usage Tests
    public function test_primary_key_lookup_is_fast(): void
    {
        $conversation = Conversation::factory()->create();

        DB::enableQueryLog();

        $found = Conversation::find($conversation->id);

        $queries = DB::getQueryLog();
        $normalizedQuery = str_replace(['"', '`'], '', strtolower($queries[0]['query']));
        // Eloquent adds table name to the query
        $this->assertStringContainsString('conversations.id =', $normalizedQuery);

        DB::disableQueryLog();
    }

    public function test_indexed_column_lookup_is_efficient(): void
    {
        Conversation::factory()->count(100)->create();

        DB::enableQueryLog();

        $conversations = Conversation::where('status', Conversation::STATUS_ACTIVE)->get();

        $queryCount = count(DB::getQueryLog());
        $this->assertEquals(1, $queryCount);

        DB::disableQueryLog();
    }

    // Memory Usage Tests
    public function test_lazy_collection_for_memory_efficiency(): void
    {
        Conversation::factory()->count(100)->create();

        $lazy = Conversation::lazy();

        $this->assertInstanceOf(\Illuminate\Support\LazyCollection::class, $lazy);
    }

    // Query Scope Performance
    public function test_scope_methods_are_chainable(): void
    {
        Conversation::factory()->count(50)->create(['status' => Conversation::STATUS_ACTIVE]);
        Conversation::factory()->count(50)->create(['status' => Conversation::STATUS_CLOSED]);

        DB::enableQueryLog();

        $conversations = Conversation::where('status', Conversation::STATUS_ACTIVE)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $queryCount = count(DB::getQueryLog());
        $this->assertEquals(1, $queryCount);

        DB::disableQueryLog();
    }

    // Relationship Loading Performance
    public function test_has_check_is_efficient(): void
    {
        $mailbox = Mailbox::factory()->create();
        Conversation::factory()->count(5)->create(['mailbox_id' => $mailbox->id]);

        DB::enableQueryLog();

        $mailboxes = Mailbox::has('conversations')->get();

        $queryCount = count(DB::getQueryLog());
        $this->assertLessThanOrEqual(2, $queryCount);

        DB::disableQueryLog();
    }

    public function test_where_has_with_callback_is_efficient(): void
    {
        $user = User::factory()->create();
        Conversation::factory()->count(5)->create();

        DB::enableQueryLog();

        $conversations = Conversation::whereHas('threads', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->get();

        $queryCount = count(DB::getQueryLog());
        $this->assertLessThanOrEqual(2, $queryCount);

        DB::disableQueryLog();
    }

    // Count Optimization
    public function test_count_uses_optimized_query(): void
    {
        Conversation::factory()->count(100)->create();

        DB::enableQueryLog();

        $count = Conversation::count();

        $queries = DB::getQueryLog();
        $this->assertStringContainsString('count(*)', strtolower($queries[0]['query']));

        DB::disableQueryLog();
    }

    // Pluck Optimization
    public function test_pluck_only_retrieves_specified_column(): void
    {
        Conversation::factory()->count(50)->create();

        DB::enableQueryLog();

        $ids = Conversation::pluck('id');

        $queries = DB::getQueryLog();
        $normalizedQuery = str_replace(['"', '`'], '', strtolower($queries[0]['query']));
        $this->assertStringContainsString('select id', $normalizedQuery);

        DB::disableQueryLog();
    }

    // Subquery Optimization
    public function test_subquery_in_select_is_efficient(): void
    {
        $mailbox = Mailbox::factory()->create();
        Conversation::factory()->count(10)->create(['mailbox_id' => $mailbox->id]);

        DB::enableQueryLog();

        $mailboxes = Mailbox::select('id', 'name')
            ->withCount('conversations')
            ->get();

        $queryCount = count(DB::getQueryLog());
        $this->assertEquals(1, $queryCount);

        DB::disableQueryLog();
    }
}
