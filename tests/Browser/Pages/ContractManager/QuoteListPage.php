<?php

/**
 * Quote List Page Object
 * 
 * Handles the quotes listing page in ContractManager.
 * 
 * MAINTENANCE NOTES:
 * -----------------
 * - Table may use DataTables or similar library
 * - Filters and search may load results via AJAX
 * - Pagination handled here
 * 
 * ROUTE: contracts/quotes (route: contractmanager.quotes.index)
 * CONTROLLER: Modules\ContractManager\Http\Controllers\QuoteController@index
 */

namespace Tests\Browser\Pages\ContractManager;

use Laravel\Dusk\Browser;
use Tests\Browser\Pages\Page;

class QuoteListPage extends Page
{
    /**
     * Get the URL for this page.
     */
    public function url(): string
    {
        return '/contracts/quotes';
    }

    /**
     * Assert that the browser is on this page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url());
    }

    /**
     * Get the element shortcuts for this page.
     */
    public function elements(): array
    {
        return [
            // Page Header
            '@page-title' => '[dusk="page-title"], h1',
            '@create-quote-btn' => '[dusk="create-quote"], a[href*="create"], .btn-create',
            
            // Search & Filters
            '@search-input' => '[dusk="search"], input[type="search"], .search-input',
            '@status-filter' => '[dusk="status-filter"], select.status-filter',
            '@client-filter' => '[dusk="client-filter"], select.client-filter',
            
            // Table
            '@quotes-table' => '[dusk="quotes-table"], table, .data-table',
            '@table-row' => 'tbody tr',
            '@empty-state' => '[dusk="empty-state"], .empty-state, .no-results',
            
            // Pagination
            '@pagination' => '[dusk="pagination"], .pagination, nav[aria-label*="pagination"]',
            '@next-page' => '.pagination .next, [rel="next"]',
            '@prev-page' => '.pagination .prev, [rel="prev"]',
        ];
    }

    /**
     * Click the Create Quote button.
     */
    public function clickCreateQuote(Browser $browser): void
    {
        $browser->click('@create-quote-btn')
            ->pause(300);
    }

    /**
     * Search for a quote.
     */
    public function search(Browser $browser, string $term): void
    {
        $browser->type('@search-input', $term)
            ->pause(500); // Wait for search results (may be debounced)
    }

    /**
     * Assert a quote exists in the table by quote number or title.
     */
    public function assertQuoteExists(Browser $browser, string $identifier): void
    {
        $browser->assertSeeIn('@quotes-table', $identifier);
    }

    /**
     * Click on a quote row to view details.
     */
    public function clickQuote(Browser $browser, string $identifier): void
    {
        // Find and click the row containing the identifier
        $browser->click("@quotes-table tr:contains('{$identifier}')")
            ->pause(300);
    }

    /**
     * Get the count of quotes displayed.
     */
    public function getQuoteCount(Browser $browser): int
    {
        return count($browser->elements('@table-row'));
    }
}
