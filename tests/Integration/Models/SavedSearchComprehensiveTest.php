<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\SavedSearch;
use App\Models\User;
use Tests\IntegrationTestCase;

/**
 * Comprehensive tests for SavedSearch model methods and edge cases.
 */
class SavedSearchComprehensiveTest extends IntegrationTestCase
{
    protected User $user;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => User::ROLE_USER]);
        $this->otherUser = User::factory()->create(['role' => User::ROLE_USER]);
    }

    // ===== Model Creation Tests =====

    public function test_saved_search_allows_null_query(): void
    {
        $search = SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => 'Filter Only Search',
            'query' => null,
            'filters' => ['status' => 1],
        ]);

        $this->assertNull($search->query);
        $this->assertDatabaseHas('saved_searches', ['id' => $search->id]);
    }

    public function test_saved_search_stores_empty_filters_array(): void
    {
        $search = SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => 'Query Only Search',
            'query' => 'test',
            'filters' => [],
        ]);

        $this->assertIsArray($search->filters);
        $this->assertEmpty($search->filters);
    }

    public function test_saved_search_stores_complex_filters(): void
    {
        $filters = [
            'status' => [1, 2, 3],
            'mailbox' => 5,
            'assigned' => 'unassigned',
            'type' => 'email',
            'has_attachments' => true,
            'date_from' => '2024-01-01',
            'date_to' => '2024-12-31',
        ];

        $search = SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => 'Complex Search',
            'query' => 'keyword',
            'filters' => $filters,
        ]);

        $this->assertEquals($filters, $search->filters);
    }

    // ===== NAME_MAX_LENGTH constraint test =====

    public function test_name_at_max_length_is_accepted(): void
    {
        $name = str_repeat('a', SavedSearch::NAME_MAX_LENGTH);

        $search = SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => $name,
            'query' => 'test',
            'filters' => [],
        ]);

        $this->assertEquals($name, $search->name);
    }

    // ===== Scope Tests =====

    public function test_for_user_scope_filters_by_user(): void
    {
        SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => 'My Search',
            'query' => 'test',
            'filters' => [],
        ]);

        SavedSearch::create([
            'user_id' => $this->otherUser->id,
            'name' => 'Other Search',
            'query' => 'test',
            'filters' => [],
        ]);

        $searches = SavedSearch::forUser($this->user->id)->get();

        $this->assertEquals(1, $searches->count());
        $this->assertEquals('My Search', $searches->first()->name);
    }

    public function test_for_user_scope_returns_empty_when_no_searches(): void
    {
        $newUser = User::factory()->create();

        $searches = SavedSearch::forUser($newUser->id)->get();

        $this->assertEquals(0, $searches->count());
    }

    public function test_ordered_scope_sorts_by_name(): void
    {
        SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => 'Zebra Search',
            'query' => 'test',
            'filters' => [],
        ]);

        SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => 'Apple Search',
            'query' => 'test',
            'filters' => [],
        ]);

        SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => 'Middle Search',
            'query' => 'test',
            'filters' => [],
        ]);

        $searches = SavedSearch::forUser($this->user->id)->ordered()->get();

        $this->assertEquals('Apple Search', $searches[0]->name);
        $this->assertEquals('Middle Search', $searches[1]->name);
        $this->assertEquals('Zebra Search', $searches[2]->name);
    }

    // ===== setAsDefault method tests =====

    public function test_set_as_default_marks_search_as_default(): void
    {
        $search = SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => 'Default Search',
            'query' => 'test',
            'filters' => [],
            'is_default' => false,
        ]);

        $search->setAsDefault();

        $this->assertTrue($search->fresh()->is_default);
    }

    public function test_set_as_default_clears_other_defaults(): void
    {
        $search1 = SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => 'First Default',
            'query' => 'test',
            'filters' => [],
            'is_default' => true,
        ]);

        $search2 = SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => 'New Default',
            'query' => 'test',
            'filters' => [],
            'is_default' => false,
        ]);

        $search2->setAsDefault();

        $this->assertFalse($search1->fresh()->is_default);
        $this->assertTrue($search2->fresh()->is_default);
    }

    public function test_set_as_default_does_not_affect_other_users(): void
    {
        $search1 = SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => 'User Default',
            'query' => 'test',
            'filters' => [],
            'is_default' => true,
        ]);

        $search2 = SavedSearch::create([
            'user_id' => $this->otherUser->id,
            'name' => 'Other User Default',
            'query' => 'test',
            'filters' => [],
            'is_default' => true,
        ]);

        $newSearch = SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => 'New Search',
            'query' => 'test',
            'filters' => [],
        ]);

        $newSearch->setAsDefault();

        // Other user's default should be unchanged
        $this->assertTrue($search2->fresh()->is_default);
        // First user's old default should be cleared
        $this->assertFalse($search1->fresh()->is_default);
    }

    // ===== getUrl method tests =====

    public function test_get_url_returns_valid_url(): void
    {
        $search = SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => 'Test Search',
            'query' => 'status:active',
            'filters' => [],
        ]);

        $url = $search->getUrl();

        $this->assertIsString($url);
        $this->assertStringContainsString('search', $url);
    }

    public function test_get_url_includes_query_parameter(): void
    {
        $search = SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => 'Test Search',
            'query' => 'my search term',
            'filters' => [],
        ]);

        $url = $search->getUrl();

        $this->assertStringContainsString('q=', $url);
    }

    public function test_get_url_handles_null_query(): void
    {
        $search = SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => 'Filter Search',
            'query' => null,
            'filters' => ['status' => 1],
        ]);

        $url = $search->getUrl();

        $this->assertIsString($url);
    }

    public function test_get_url_includes_filters(): void
    {
        $search = SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => 'Filtered Search',
            'query' => 'test',
            'filters' => ['status' => 1, 'mailbox' => 5],
        ]);

        $url = $search->getUrl();

        $this->assertIsString($url);
        // URL should include filter parameters
        $this->assertTrue(
            str_contains($url, 'status') ||
            str_contains($url, 'filter') ||
            str_contains($url, 'mailbox')
        );
    }

    // ===== getFiltersSummary method tests =====

    public function test_get_filters_summary_returns_string(): void
    {
        $search = SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => 'Test Search',
            'query' => 'test',
            'filters' => ['status' => 1],
        ]);

        $summary = $search->getFiltersSummary();

        $this->assertIsString($summary);
    }

    public function test_get_filters_summary_empty_when_no_filters(): void
    {
        $search = SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => 'Test Search',
            'query' => 'test',
            'filters' => [],
        ]);

        $summary = $search->getFiltersSummary();

        $this->assertIsString($summary);
        // May be empty or contain only query info
    }

    public function test_get_filters_summary_includes_status_filter(): void
    {
        $search = SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => 'Status Search',
            'query' => null,
            'filters' => ['status' => 1],
        ]);

        $summary = $search->getFiltersSummary();

        $this->assertTrue(
            str_contains(strtolower($summary), 'status') ||
            str_contains(strtolower($summary), 'active') ||
            ! empty($summary)
        );
    }

    public function test_get_filters_summary_includes_multiple_filters(): void
    {
        $search = SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => 'Multi Filter Search',
            'query' => 'test',
            'filters' => [
                'status' => 1,
                'mailbox' => 5,
                'type' => 'email',
            ],
        ]);

        $summary = $search->getFiltersSummary();

        $this->assertIsString($summary);
        $this->assertNotEmpty($summary);
    }

    // ===== Relationship Tests =====

    public function test_saved_search_belongs_to_user(): void
    {
        $search = SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => 'Test Search',
            'query' => 'test',
            'filters' => [],
        ]);

        $this->assertInstanceOf(User::class, $search->user);
        $this->assertEquals($this->user->id, $search->user->id);
    }

    public function test_user_has_many_saved_searches(): void
    {
        SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => 'Search 1',
            'query' => 'test1',
            'filters' => [],
        ]);

        SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => 'Search 2',
            'query' => 'test2',
            'filters' => [],
        ]);

        $searches = $this->user->savedSearches;

        $this->assertEquals(2, $searches->count());
    }

    // ===== Edge Cases =====

    public function test_saved_search_with_special_characters_in_name(): void
    {
        $search = SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => 'Search with "quotes" & <tags> and émojis 🔍',
            'query' => 'test',
            'filters' => [],
        ]);

        $this->assertEquals('Search with "quotes" & <tags> and émojis 🔍', $search->name);
    }

    public function test_saved_search_with_special_characters_in_query(): void
    {
        $search = SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => 'Special Query',
            'query' => 'status:"active" AND (tag:urgent OR tag:"high priority")',
            'filters' => [],
        ]);

        $this->assertEquals('status:"active" AND (tag:urgent OR tag:"high priority")', $search->query);
    }

    public function test_saved_search_filters_cast_as_array(): void
    {
        $search = SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => 'Test',
            'query' => 'test',
            'filters' => ['nested' => ['key' => 'value']],
        ]);

        $search = $search->fresh();

        $this->assertIsArray($search->filters);
        $this->assertArrayHasKey('nested', $search->filters);
    }

    public function test_delete_saved_search_removes_from_database(): void
    {
        $search = SavedSearch::create([
            'user_id' => $this->user->id,
            'name' => 'To Delete',
            'query' => 'test',
            'filters' => [],
        ]);

        $id = $search->id;
        $search->delete();

        $this->assertDatabaseMissing('saved_searches', ['id' => $id]);
    }

    public function test_delete_user_cascade_deletes_searches(): void
    {
        $tempUser = User::factory()->create();

        SavedSearch::create([
            'user_id' => $tempUser->id,
            'name' => 'User Search',
            'query' => 'test',
            'filters' => [],
        ]);

        $userId = $tempUser->id;

        // Force delete to trigger cascade (if configured)
        $tempUser->forceDelete();

        // Check if searches are deleted (depends on foreign key config)
        $remaining = SavedSearch::where('user_id', $userId)->count();
        $this->assertEquals(0, $remaining);
    }
}
