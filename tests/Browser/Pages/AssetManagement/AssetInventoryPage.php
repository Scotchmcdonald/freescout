<?php

/**
 * Asset Inventory Page Object
 * 
 * Handles the AssetManagement inventory listing and creation.
 * 
 * MAINTENANCE NOTES:
 * -----------------
 * - Assets are listed in a table with filters
 * - Manual asset creation may be via modal or separate page
 * - Asset status changes may trigger events to PIB
 * 
 * ROUTE: admin/assets/inventory (route: admin.assets.inventory)
 * CONTROLLER: Modules\AssetManagement\Http\Controllers\AssetController
 */

namespace Tests\Browser\Pages\AssetManagement;

use Laravel\Dusk\Browser;
use Tests\Browser\Pages\Page;

class AssetInventoryPage extends Page
{
    /**
     * Get the URL for this page.
     */
    public function url(): string
    {
        return '/admin/assets/inventory';
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
            '@create-asset-btn' => '[dusk="create-asset"]',
            
            // Search & Filters
            '@search-input' => '[dusk="search"], input[type="search"], input[name="search"]',
            '@type-filter' => '[dusk="type-filter"], select[name="type"]',
            '@status-filter' => '[dusk="status-filter"], select[name="status"]',
            '@client-filter' => '[dusk="client-filter"], select[name="client_id"]',
            '@apply-filters-btn' => '[dusk="apply-filters"], button:contains("Filter")',
            '@clear-filters-btn' => '[dusk="clear-filters"], button:contains("Clear")',
            
            // Assets Table
            '@assets-table' => '[dusk="assets-table"], table, .data-table',
            '@asset-row' => 'tbody tr',
            '@empty-state' => '[dusk="empty-state"], .empty-state, .no-results',
            
            // Bulk Actions
            '@select-all-checkbox' => 'thead input[type="checkbox"]',
            '@bulk-actions-dropdown' => '[dusk="bulk-actions"]',
            
            // Pagination
            '@pagination' => '[dusk="pagination"], .pagination',
            
            // Export
            '@export-btn' => '[dusk="export"], a[href*="export"]',
            
            // Asset Creation Modal (if using modal)
            '@asset-modal' => '[dusk="asset-modal"], #assetModal, .modal',
            '@modal-serial-input' => '[dusk="serial-number"], input[name="serial_number"]',
            '@modal-type-select' => '[dusk="asset-type"], select[name="asset_type_id"], select[name="type"]',
            '@modal-model-input' => '[dusk="model"], input[name="model"]',
            '@modal-status-select' => '[dusk="status"], select[name="status"]',
            '@modal-client-select' => '[dusk="client"], select[name="client_id"]',
            '@modal-save-btn' => '[dusk="save-asset"], .modal button[type="submit"]',
        ];
    }

    /**
     * Click to create a new asset.
     */
    public function clickCreateAsset(Browser $browser): void
    {
        $browser->waitFor('@create-asset-btn')
            ->click('@create-asset-btn')
            ->pause(300);
    }

    /**
     * Search for an asset.
     */
    public function searchProduct(Browser $browser, string $keyword): void
    {
        $browser->waitFor('@search-input')
            ->type('@search-input', $keyword)
            ->pause(1000)
            ->waitFor('@assets-table');
    }

    /**
     * Create a new asset (assumes modal or inline form).
     * 
     * @param Browser $browser
     * @param array $asset Keys: serial_number, type, model, status, client_id
     */
    public function createAsset(Browser $browser, array $asset): void
    {
        $this->clickCreateAsset($browser);
        
        // Wait for modal/form to appear
        $browser->pause(300);
        
        if (isset($asset['serial_number'])) {
            $browser->type('@modal-serial-input', $asset['serial_number']);
        }
        
        if (isset($asset['type'])) {
            $browser->select('@modal-type-select', $asset['type']);
        }
        
        if (isset($asset['model'])) {
            $browser->type('@modal-model-input', $asset['model']);
        }
        
        if (isset($asset['status'])) {
            $browser->select('@modal-status-select', $asset['status']);
        }
        
        if (isset($asset['client_id'])) {
            $browser->select('@modal-client-select', $asset['client_id']);
        }
        
        $browser->click('@modal-save-btn')
            ->pause(500);
    }

    /**
     * Search for assets.
     */
    public function search(Browser $browser, string $term): void
    {
        $browser->type('@search-input', $term)
            ->pause(500); // Debounce wait
    }

    /**
     * Filter by asset type.
     */
    public function filterByType(Browser $browser, string $type): void
    {
        $browser->select('@type-filter', $type)
            ->pause(300);
    }

    /**
     * Filter by status.
     */
    public function filterByStatus(Browser $browser, string $status): void
    {
        $browser->select('@status-filter', $status)
            ->pause(300);
    }

    /**
     * Assert an asset appears in the table.
     */
    public function assertAssetExists(Browser $browser, string $serialNumber): void
    {
        $browser->assertSeeIn('@assets-table', $serialNumber);
    }

    /**
     * Assert an asset does NOT appear in the table.
     */
    public function assertAssetNotExists(Browser $browser, string $serialNumber): void
    {
        $browser->assertDontSeeIn('@assets-table', $serialNumber);
    }

    /**
     * Click on an asset row to view details.
     */
    public function clickAsset(Browser $browser, string $serialNumber): void
    {
        $browser->click("@assets-table tr:contains('{$serialNumber}') a")
            ->pause(300);
    }

    /**
     * Get count of assets displayed.
     */
    public function getAssetCount(Browser $browser): int
    {
        return count($browser->elements('@asset-row'));
    }
}
