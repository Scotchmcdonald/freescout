<?php

/**
 * Software Catalog Page Object
 * 
 * Lists available software products that can be assigned to clients.
 * 
 * ROUTE: admin/software-subscriptions (route: admin.softwaresubscriptions.index)
 */

namespace Tests\Browser\Pages\SoftwareSubscriptions;

use Laravel\Dusk\Browser;
use Tests\Browser\Pages\Page;

class SoftwareCatalogPage extends Page
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
            '@page-title' => '[dusk="page-title"], h1, h2',
            '@create-btn' => '[dusk="create-subscription"], a[href*="create"]',
            '@products-list' => '[dusk="products-list"], .products-list, table',
            '@product-row' => 'tbody tr, .product-item',
            '@search-input' => '[dusk="search"], input[type="search"], input[name="search"]',
            '@filter-vendor' => '[dusk="filter-vendor"], select[name="vendor"]',
        ];
    }

    /**
     * Wait for products to load.
     */
    public function waitForProductsToLoad(Browser $browser): void
    {
        $browser->waitFor('@products-list', 5);
    }

    /**
     * Assert that products are visible.
     */
    public function assertProductsVisible(Browser $browser): void
    {
        $browser->assertPresent('@products-list')
            ->assertVisible('@products-list');
    }

    /**
     * Search for a product.
     */
    public function searchProduct(Browser $browser, string $query): void
    {
        if ($browser->element('@search-input')) {
            $browser->type('@search-input', $query)
                ->pause(500);
        }
    }
}
