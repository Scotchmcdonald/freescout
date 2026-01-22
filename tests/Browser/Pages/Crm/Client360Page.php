<?php

/**
 * Client 360 Page Object
 * 
 * The Client 360 view is a cross-module aggregator displaying:
 * - Client basic info (CRM)
 * - Contacts (CRM)
 * - Assets (AssetManagement)
 * - Software Subscriptions (SoftwareSubscriptions)
 * - Invoices & Credits (PIB)
 * - Contracts (ContractManager)
 * 
 * MAINTENANCE NOTES:
 * -----------------
 * - Each module widget has its own section - update relevant selectors when that module's UI changes
 * - Widget sections may load asynchronously via Vue/Livewire
 * - If a module is disabled, its section won't exist (tests should handle gracefully)
 * 
 * ROUTE: admin/clients/{client} (route: admin.clients.show)
 * CONTROLLER: App\Http\Controllers\Admin\Client360Controller
 */

namespace Tests\Browser\Pages\Crm;

use Laravel\Dusk\Browser;
use Tests\Browser\Pages\Page;

class Client360Page extends Page
{
    protected int $clientId;

    public function __construct(int $clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * Get the URL for this page.
     */
    public function url(): string
    {
        return "/admin/clients/{$this->clientId}";
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
     * 
     * ORGANIZED BY MODULE - update the relevant section when that module's UI changes
     */
    public function elements(): array
    {
        return [
            // ============================================================
            // CLIENT BASIC INFO (CRM)
            // Location: Main header area
            // ============================================================
            '@client-name' => '[dusk="client-name"], h1, .client-name',
            '@client-email' => '[dusk="client-email"], .client-email',
            '@edit-client-btn' => '[dusk="edit-client"], a[href*="edit"], .edit-client',
            
            // ============================================================
            // CONTACTS SECTION (CRM)
            // Usually a card/panel with contact list
            // ============================================================
            '@contacts-section' => '[dusk="contacts-section"], #contacts, [data-section="contacts"]',
            '@add-contact-btn' => '[dusk="add-contact"], .add-contact, a[href*="contact"]',
            '@contact-list' => '[dusk="contact-list"], .contact-list, table.contacts',
            '@contact-row' => '[dusk="contact-row"], .contact-row, tr.contact',
            
            // ============================================================
            // ASSETS SECTION (AssetManagement)
            // Shows devices/assets assigned to this client
            // ============================================================
            '@assets-section' => '[dusk="assets-section"], #assets, [data-section="assets"]',
            '@asset-count' => '[dusk="asset-count"], .asset-count',
            '@view-all-assets-btn' => '[dusk="view-assets"], a[href*="asset"]',
            '@asset-list' => '[dusk="asset-list"], .asset-list, table.assets',
            
            // ============================================================
            // SOFTWARE SUBSCRIPTIONS (SoftwareSubscriptions)
            // Shows software assigned to this client
            // ============================================================
            '@software-section' => '[dusk="software-section"], #software, [data-section="software"]',
            '@software-list' => '[dusk="software-list"], .software-list',
            '@add-software-btn' => '[dusk="add-software"]',
            '@software-assignment-count' => '[dusk="assignment-count"]',
            
            // ============================================================
            // BILLING & INVOICES (PIB)
            // Shows invoices, credits, and financial summary
            // ============================================================
            '@billing-section' => '[dusk="billing-section"], #billing, [data-section="billing"]',
            '@credit-balance' => '[dusk="credit-balance"], .credit-balance',
            '@invoice-list' => '[dusk="invoice-list"], .invoice-list, table.invoices',
            '@view-credit-ledger-btn' => '[dusk="view-ledger"], a[href*="credit-ledger"]',
            
            // ============================================================
            // CONTRACTS (ContractManager)
            // Shows active contracts and quotes
            // ============================================================
            '@contracts-section' => '[dusk="contracts-section"], #contracts, [data-section="contracts"]',
            '@active-contract' => '[dusk="active-contract"], .active-contract',
            '@view-quotes-btn' => '[dusk="view-quotes"], a[href*="quote"]',
            
            // ============================================================
            // NAVIGATION TABS (if tabbed layout)
            // ============================================================
            '@tab-overview' => '[dusk="tab-overview"], .tab-overview, [data-tab="overview"]',
            '@tab-contacts' => '[dusk="tab-contacts"], .tab-contacts, [data-tab="contacts"]',
            '@tab-assets' => '[dusk="tab-assets"], .tab-assets, [data-tab="assets"]',
            '@tab-billing' => '[dusk="tab-billing"], .tab-billing, [data-tab="billing"]',
        ];
    }

    /**
     * Wait for all widget sections to load.
     * Some sections may be loaded via AJAX/Vue.
     */
    public function waitForWidgetsToLoad(Browser $browser): void
    {
        // Wait for main content
        $browser->waitFor('@client-name', 5);
        
        // Brief pause for async widgets
        $browser->pause(500);
    }

    /**
     * Assert all expected module sections are present.
     * Useful for verifying cross-module integration.
     */
    public function assertAllSectionsPresent(Browser $browser): void
    {
        $browser->assertPresent('@contacts-section')
            ->assertPresent('@assets-section')
            ->assertPresent('@billing-section');
    }

    /**
     * Get the displayed client name.
     */
    public function getClientName(Browser $browser): string
    {
        return $browser->text('@client-name');
    }

    /**
     * Check if assets section shows expected count.
     */
    public function assertAssetCount(Browser $browser, int $expectedCount): void
    {
        // This assumes the count is displayed somewhere
        // Update selector based on actual UI
        $browser->assertSeeIn('@assets-section', (string) $expectedCount);
    }

    /**
     * Check if credit balance displays expected amount.
     * 
     * @param Browser $browser
     * @param string $expectedBalance Formatted amount like "$175.00"
     */
    public function assertCreditBalance(Browser $browser, string $expectedBalance): void
    {
        $browser->waitFor('@credit-balance', 3)
            ->assertSeeIn('@billing-section', $expectedBalance);
    }

    /**
     * Navigate to the credit ledger for this client.
     */
    public function goToCreditLedger(Browser $browser): void
    {
        $browser->click('@view-credit-ledger-btn')
            ->pause(300);
    }

    /**
     * Click to add a new contact.
     */
    public function clickAddContact(Browser $browser): void
    {
        $browser->click('@add-contact-btn')
            ->pause(300);
    }

    /**
     * Assert a specific contact appears in the contact list.
     */
    public function assertContactExists(Browser $browser, string $contactName): void
    {
        $browser->assertSeeIn('@contacts-section', $contactName);
    }

    /**
     * Assert a specific asset appears in the asset list.
     */
    public function assertAssetExists(Browser $browser, string $serialNumber): void
    {
        $browser->assertSeeIn('@assets-section', $serialNumber);
    }
}
