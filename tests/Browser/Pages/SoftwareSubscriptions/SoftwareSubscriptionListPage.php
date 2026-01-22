<?php

/**
 * Software Subscriptions Page Object
 * 
 * Handles the SoftwareSubscriptions admin views.
 * 
 * MAINTENANCE NOTES:
 * -----------------
 * - Software assignments are polymorphic (contact or asset)
 * - Assignment counts update atomically
 * - May use Vue/Livewire for dynamic updates
 * 
 * ROUTES:
 * - admin/software-subscriptions (list)
 * - admin/software-subscriptions/create
 * - admin/software-subscriptions/{id} (show)
 * 
 * API ROUTES (for AJAX operations):
 * - api/v1/software-subscriptions/{id}/assignments
 */

namespace Tests\Browser\Pages\SoftwareSubscriptions;

use Laravel\Dusk\Browser;
use Tests\Browser\Pages\Page;

class SoftwareSubscriptionListPage extends Page
{
    /**
     * Get the URL for this page.
     */
    public function url(): string
    {
        return '/admin/software-subscriptions';
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
            // Header
            '@page-title' => '[dusk="page-title"], h1',
            '@create-btn' => '[dusk="create-subscription"], a[href*="create"], .btn-create',
            
            // Filters
            '@client-filter' => '[dusk="client-filter"], select[name="client_id"]',
            '@product-filter' => '[dusk="product-filter"], select[name="product_id"]',
            '@search-input' => '[dusk="search"], input[type="search"]',
            
            // Table
            '@subscriptions-table' => '[dusk="subscriptions-table"], table',
            '@subscription-row' => 'tbody tr',
            '@empty-state' => '[dusk="empty-state"], .empty-state',
            
            // Table Columns
            '@col-client' => 'td.client, td:nth-child(1)',
            '@col-product' => 'td.product, td:nth-child(2)',
            '@col-assignment-count' => 'td.count, td:nth-child(3)',
            '@col-status' => 'td.status, td:nth-child(4)',
        ];
    }

    /**
     * Click to create a new subscription.
     */
    public function clickCreate(Browser $browser): void
    {
        $browser->click('@create-btn')
            ->pause(300);
    }

    /**
     * Assert a subscription exists for a client and product.
     */
    public function assertSubscriptionExists(Browser $browser, string $clientName, string $productName): void
    {
        $browser->assertSeeIn('@subscriptions-table', $clientName)
            ->assertSeeIn('@subscriptions-table', $productName);
    }

    /**
     * Click on a subscription row.
     */
    public function clickSubscription(Browser $browser, string $identifier): void
    {
        $browser->click("@subscriptions-table tr:contains('{$identifier}') a")
            ->pause(300);
    }
}
