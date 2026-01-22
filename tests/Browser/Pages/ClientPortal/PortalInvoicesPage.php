<?php

/**
 * Client Portal Invoices Page Object
 * 
 * Client-facing invoice list and detail pages.
 * 
 * ROUTE: portal/invoices (route: portal.invoices.index)
 */

namespace Tests\Browser\Pages\ClientPortal;

use Laravel\Dusk\Browser;
use Tests\Browser\Pages\Page;

class PortalInvoicesPage extends Page
{
    /**
     * Get the URL for this page.
     */
    public function url(): string
    {
        return '/portal/invoices';
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
            '@page-title' => '[dusk="page-title"], h1',
            '@invoices-list' => '[dusk="invoices-list"], table, .invoices-list',
            '@invoice-row' => 'tbody tr, .invoice-item',
            '@invoice-link' => 'a[href*="invoices"]',
            '@empty-state' => '[dusk="empty-state"], .empty-state',
            '@filter-status' => '[dusk="filter-status"], select[name="status"]',
        ];
    }

    /**
     * Wait for invoices to load.
     */
    public function waitForInvoicesToLoad(Browser $browser): void
    {
        $browser->waitFor('@invoices-list', 5);
    }

    /**
     * Click on an invoice.
     */
    public function clickInvoice(Browser $browser, string $invoiceIdentifier): void
    {
        $browser->clickLink($invoiceIdentifier)
            ->pause(500);
    }

    /**
     * Assert invoice exists in list.
     */
    public function assertInvoiceExists(Browser $browser, string $identifier): void
    {
        $browser->assertSee($identifier);
    }
}
