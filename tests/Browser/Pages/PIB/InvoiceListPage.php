<?php

/**
 * Invoice List Page Object
 * 
 * Lists all invoices in the PIB module.
 * 
 * ROUTE: admin/billing/invoices (estimated route)
 */

namespace Tests\Browser\Pages\PIB;

use Laravel\Dusk\Browser;
use Tests\Browser\Pages\Page;

class InvoiceListPage extends Page
{
    /**
     * Get the URL for this page.
     */
    public function url(): string
    {
        return '/admin/billing/invoices';
    }

    /**
     * Assert that the browser is on this page.
     */
    public function assert(Browser $browser): void
    {
        // May need to adjust based on actual implementation
        $browser->waitForText('Invoice', 5);
    }

    /**
     * Get the element shortcuts for this page.
     */
    public function elements(): array
    {
        return [
            '@page-title' => '[dusk="page-title"], h1',
            '@create-invoice-btn' => '[dusk="create-invoice"], a[href*="create"], button:contains("Create")',
            '@invoices-table' => '[dusk="invoices-table"], table',
            '@invoice-row' => 'tbody tr',
            '@search-input' => '[dusk="search"], input[type="search"]',
            '@filter-status' => '[dusk="filter-status"], select[name="status"]',
        ];
    }

    /**
     * Click create invoice button.
     */
    public function clickCreateInvoice(Browser $browser): void
    {
        if ($browser->element('@create-invoice-btn')) {
            $browser->click('@create-invoice-btn')
                ->pause(500);
        }
    }

    /**
     * Search for an invoice.
     */
    public function searchInvoice(Browser $browser, string $query): void
    {
        if ($browser->element('@search-input')) {
            $browser->type('@search-input', $query)
                ->pause(500);
        }
    }
}
