<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\SavedSearch;
use App\Models\User;
use Tests\IntegrationTestCase;

class SavedSearchModelTest extends IntegrationTestCase
{
    public function test_model_can_be_instantiated(): void
    {
        $savedSearch = new SavedSearch;
        $this->assertInstanceOf(SavedSearch::class, $savedSearch);
    }

    public function test_model_has_fillable_attributes(): void
    {
        $savedSearch = new SavedSearch([
            'user_id' => 1,
            'name' => 'Test Search',
            'query' => 'test query',
            'filters' => ['status' => 1],
            'is_default' => false,
            'sort_order' => 1,
        ]);

        $this->assertEquals(1, $savedSearch->user_id);
        $this->assertEquals('Test Search', $savedSearch->name);
        $this->assertEquals('test query', $savedSearch->query);
        $this->assertIsArray($savedSearch->filters);
        $this->assertFalse($savedSearch->is_default);
        $this->assertEquals(1, $savedSearch->sort_order);
    }

    public function test_filters_cast_to_array(): void
    {
        $filters = ['mailbox' => 1, 'status' => 2, 'assigned' => 3];
        $user = User::factory()->create();

        $savedSearch = SavedSearch::create([
            'user_id' => $user->id,
            'name' => 'Test Search',
            'filters' => $filters,
        ]);

        $savedSearch->refresh();

        $this->assertIsArray($savedSearch->filters);
        $this->assertEquals($filters, $savedSearch->filters);
    }

    public function test_is_default_cast_to_boolean(): void
    {
        $user = User::factory()->create();

        $savedSearch = SavedSearch::create([
            'user_id' => $user->id,
            'name' => 'Test Search',
            'is_default' => 1,
        ]);

        $savedSearch->refresh();

        $this->assertTrue($savedSearch->is_default);
        $this->assertIsBool($savedSearch->is_default);
    }

    public function test_sort_order_cast_to_integer(): void
    {
        $user = User::factory()->create();

        $savedSearch = SavedSearch::create([
            'user_id' => $user->id,
            'name' => 'Test Search',
            'sort_order' => '5',
        ]);

        $savedSearch->refresh();

        $this->assertIsInt($savedSearch->sort_order);
        $this->assertEquals(5, $savedSearch->sort_order);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $savedSearch = SavedSearch::create([
            'user_id' => $user->id,
            'name' => 'Test Search',
        ]);

        $this->assertInstanceOf(User::class, $savedSearch->user);
        $this->assertEquals($user->id, $savedSearch->user->id);
    }

    public function test_scope_for_user_filters_by_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        SavedSearch::create(['user_id' => $user1->id, 'name' => 'Search 1']);
        SavedSearch::create(['user_id' => $user1->id, 'name' => 'Search 2']);
        SavedSearch::create(['user_id' => $user2->id, 'name' => 'Search 3']);

        $user1Searches = SavedSearch::forUser($user1->id)->get();
        $user2Searches = SavedSearch::forUser($user2->id)->get();

        $this->assertCount(2, $user1Searches);
        $this->assertCount(1, $user2Searches);
        $this->assertTrue($user1Searches->every(fn ($s) => $s->user_id === $user1->id));
    }

    public function test_scope_ordered_sorts_by_sort_order_and_name(): void
    {
        $user = User::factory()->create();

        SavedSearch::create(['user_id' => $user->id, 'name' => 'Zebra', 'sort_order' => 2]);
        SavedSearch::create(['user_id' => $user->id, 'name' => 'Apple', 'sort_order' => 1]);
        SavedSearch::create(['user_id' => $user->id, 'name' => 'Banana', 'sort_order' => 1]);

        $searches = SavedSearch::forUser($user->id)->ordered()->get();

        $this->assertEquals('Apple', $searches[0]->name);
        $this->assertEquals('Banana', $searches[1]->name);
        $this->assertEquals('Zebra', $searches[2]->name);
    }

    public function test_get_default_for_user_returns_default_search(): void
    {
        $user = User::factory()->create();

        SavedSearch::create(['user_id' => $user->id, 'name' => 'Not Default', 'is_default' => false]);
        $defaultSearch = SavedSearch::create(['user_id' => $user->id, 'name' => 'Default', 'is_default' => true]);

        $result = SavedSearch::getDefaultForUser($user->id);

        $this->assertNotNull($result);
        $this->assertEquals($defaultSearch->id, $result->id);
    }

    public function test_get_default_for_user_returns_null_when_no_default(): void
    {
        $user = User::factory()->create();

        SavedSearch::create(['user_id' => $user->id, 'name' => 'Not Default', 'is_default' => false]);

        $result = SavedSearch::getDefaultForUser($user->id);

        $this->assertNull($result);
    }

    public function test_set_as_default_clears_other_defaults(): void
    {
        $user = User::factory()->create();

        $search1 = SavedSearch::create(['user_id' => $user->id, 'name' => 'Search 1', 'is_default' => true]);
        $search2 = SavedSearch::create(['user_id' => $user->id, 'name' => 'Search 2', 'is_default' => false]);

        $search2->setAsDefault();

        $search1->refresh();
        $search2->refresh();

        $this->assertFalse($search1->is_default);
        $this->assertTrue($search2->is_default);
    }

    public function test_set_as_default_does_not_affect_other_users(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $search1 = SavedSearch::create(['user_id' => $user1->id, 'name' => 'Search 1', 'is_default' => true]);
        $search2 = SavedSearch::create(['user_id' => $user2->id, 'name' => 'Search 2', 'is_default' => false]);

        $search2->setAsDefault();

        $search1->refresh();

        $this->assertTrue($search1->is_default); // User1's default should not be affected
        $this->assertTrue($search2->is_default);
    }

    public function test_get_display_name_returns_name(): void
    {
        $savedSearch = new SavedSearch(['name' => 'My Custom Search']);

        $this->assertEquals('My Custom Search', $savedSearch->getDisplayName());
    }

    public function test_get_display_name_returns_default_when_empty(): void
    {
        $savedSearch = new SavedSearch(['name' => '']);

        $this->assertEquals(__('Unnamed Search'), $savedSearch->getDisplayName());
    }

    public function test_get_filters_summary_returns_empty_for_no_filters(): void
    {
        $savedSearch = new SavedSearch(['filters' => []]);

        $this->assertEquals('', $savedSearch->getFiltersSummary());
    }

    public function test_get_filters_summary_returns_empty_for_null_filters(): void
    {
        $savedSearch = new SavedSearch(['filters' => null]);

        $this->assertEquals('', $savedSearch->getFiltersSummary());
    }

    public function test_get_filters_summary_includes_mailbox(): void
    {
        $savedSearch = new SavedSearch(['filters' => ['mailbox' => 5]]);

        $summary = $savedSearch->getFiltersSummary();

        $this->assertStringContainsString('Mailbox:', $summary);
        $this->assertStringContainsString('5', $summary);
    }

    public function test_get_filters_summary_includes_assigned(): void
    {
        $savedSearch = new SavedSearch(['filters' => ['assigned' => 3]]);

        $summary = $savedSearch->getFiltersSummary();

        $this->assertStringContainsString('Assigned:', $summary);
        $this->assertStringContainsString('3', $summary);
    }

    public function test_get_filters_summary_includes_status(): void
    {
        $savedSearch = new SavedSearch(['filters' => ['status' => 'active']]);

        $summary = $savedSearch->getFiltersSummary();

        $this->assertStringContainsString('Status:', $summary);
        $this->assertStringContainsString('active', $summary);
    }

    public function test_get_filters_summary_includes_type(): void
    {
        $savedSearch = new SavedSearch(['filters' => ['type' => 'email']]);

        $summary = $savedSearch->getFiltersSummary();

        $this->assertStringContainsString('Type:', $summary);
        $this->assertStringContainsString('email', $summary);
    }

    public function test_get_filters_summary_includes_date_range(): void
    {
        $savedSearch = new SavedSearch(['filters' => [
            'date_from' => '2024-01-01',
            'date_to' => '2024-12-31',
        ]]);

        $summary = $savedSearch->getFiltersSummary();

        $this->assertStringContainsString('From:', $summary);
        $this->assertStringContainsString('To:', $summary);
        $this->assertStringContainsString('2024-01-01', $summary);
        $this->assertStringContainsString('2024-12-31', $summary);
    }

    public function test_get_filters_summary_combines_multiple_filters(): void
    {
        $savedSearch = new SavedSearch(['filters' => [
            'mailbox' => 1,
            'status' => 'closed',
            'type' => 'phone',
        ]]);

        $summary = $savedSearch->getFiltersSummary();

        $this->assertStringContainsString('Mailbox:', $summary);
        $this->assertStringContainsString('Status:', $summary);
        $this->assertStringContainsString('Type:', $summary);
        $this->assertStringContainsString(',', $summary); // Parts should be comma-separated
    }

    public function test_name_max_length_constant_exists(): void
    {
        $this->assertEquals(255, SavedSearch::NAME_MAX_LENGTH);
    }

    public function test_query_can_be_null(): void
    {
        $user = User::factory()->create();
        $savedSearch = SavedSearch::create([
            'user_id' => $user->id,
            'name' => 'Filter Only Search',
            'query' => null,
            'filters' => ['status' => 1],
        ]);

        $savedSearch->refresh();

        $this->assertNull($savedSearch->query);
    }

    public function test_multiple_searches_with_same_name_different_users(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $search1 = SavedSearch::create(['user_id' => $user1->id, 'name' => 'Same Name']);
        $search2 = SavedSearch::create(['user_id' => $user2->id, 'name' => 'Same Name']);

        $this->assertNotEquals($search1->id, $search2->id);
        $this->assertEquals($search1->name, $search2->name);
    }
}
