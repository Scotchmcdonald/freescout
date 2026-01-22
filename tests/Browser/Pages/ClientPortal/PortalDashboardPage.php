<?php

/**
 * Client Portal Dashboard Page Object
 * 
 * Main dashboard for client-facing portal.
 * 
 * ROUTE: portal/dashboard (route: portal.dashboard)
 */

namespace Tests\Browser\Pages\ClientPortal;

use Laravel\Dusk\Browser;
use Tests\Browser\Pages\Page;

class PortalDashboardPage extends Page
{
    /**
     * Get the URL for this page.
     */
    public function url(): string
    {
        return '/portal/dashboard';
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
            '@dashboard-title' => '[dusk="dashboard-title"], h1, h2',
            '@invoices-section' => '[dusk="invoices-section"], .invoices-widget',
            '@assets-section' => '[dusk="assets-section"], .assets-widget',
            '@quotes-section' => '[dusk="quotes-section"], .quotes-widget',
            '@invoices-link' => '[dusk="invoices-link"], a[href*="invoices"]',
            '@assets-link' => '[dusk="assets-link"], a[href*="assets"]',
            '@logout-btn' => '[dusk="logout"], button:contains("Logout"), a:contains("Logout")',
        ];
    }

    /**
     * Navigate to invoices.
     */
    public function goToInvoices(Browser $browser): void
    {
        if ($browser->element('@invoices-link')) {
            $browser->click('@invoices-link')
                ->pause(500);
        }
    }

    /**
     * Check if section exists.
     */
    public function assertSectionExists(Browser $browser, string $section): void
    {
        $element = "@{$section}-section";
        if ($browser->element($element)) {
            $browser->assertVisible($element);
        }
    }
}
