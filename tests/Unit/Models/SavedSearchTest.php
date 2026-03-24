<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\SavedSearch;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Tests\PureUnitTestCase;

class SavedSearchTest extends PureUnitTestCase
{
    private ?Container $previousContainer = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousContainer = Container::getInstance();

        $app = new Application(getcwd());
        $app->instance('translator', new class
        {
            public function get(string $key, array $replace = [], ?string $locale = null, bool $fallback = true): string
            {
                foreach ($replace as $token => $value) {
                    $key = str_replace(':'.$token, (string) $value, $key);
                }

                return $key;
            }
        });

        Container::setInstance($app);
    }

    protected function tearDown(): void
    {
        Container::setInstance($this->previousContainer);

        parent::tearDown();
    }

    private function newSearch(?string $name = 'Important Search', ?array $filters = null): SavedSearch
    {
        $search = new SavedSearch;
        $search->name = $name;
        $search->filters = $filters;

        return $search;
    }

    public function test_get_display_name_returns_name_when_present(): void
    {
        $search = $this->newSearch('VIP Tickets');

        $this->assertSame('VIP Tickets', $search->getDisplayName());
    }

    public function test_get_display_name_falls_back_to_translated_default_when_name_is_empty(): void
    {
        $search = $this->newSearch('');

        $this->assertSame('Unnamed Search', $search->getDisplayName());
    }

    public function test_get_filters_summary_returns_empty_string_when_filters_are_missing(): void
    {
        $search = $this->newSearch(filters: null);

        $this->assertSame('', $search->getFiltersSummary());
    }

    public function test_get_filters_summary_builds_summary_for_supported_filter_keys(): void
    {
        $search = $this->newSearch(filters: [
            'mailbox' => 42,
            'assigned' => 7,
            'status' => 'active',
            'type' => 'email',
            'date_from' => '2026-03-01',
            'date_to' => '2026-03-31',
        ]);

        $summary = $search->getFiltersSummary();

        $this->assertSame(
            'Mailbox: 42, Assigned: 7, Status: active, Type: email, From: 2026-03-01, To: 2026-03-31',
            $summary
        );
    }

    public function test_get_filters_summary_uses_blank_values_for_non_scalar_entries_but_keeps_labels(): void
    {
        $search = $this->newSearch(filters: [
            'mailbox' => ['nested'],
            'status' => (object) ['value' => 'active'],
            'type' => 'chat',
        ]);

        $summary = $search->getFiltersSummary();

        $this->assertSame('Mailbox: , Status: , Type: chat', $summary);
    }
}
