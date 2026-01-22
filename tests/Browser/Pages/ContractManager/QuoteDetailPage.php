<?php

/**
 * Quote Detail Page Object
 * 
 * Handles the quote detail/show page with actions like Approve, Send, etc.
 * 
 * MAINTENANCE NOTES:
 * -----------------
 * - Quote actions (Approve, Send, Revise) are context-dependent
 * - Status determines which actions are available
 * - Line items table displays quote contents
 * 
 * ROUTE: contracts/quotes/{quote} (route: contractmanager.quotes.show)
 * CONTROLLER: Modules\ContractManager\Http\Controllers\QuoteController@show
 */

namespace Tests\Browser\Pages\ContractManager;

use Laravel\Dusk\Browser;
use Tests\Browser\Pages\Page;

class QuoteDetailPage extends Page
{
    protected int $quoteId;

    public function __construct(int $quoteId)
    {
        $this->quoteId = $quoteId;
    }

    /**
     * Get the URL for this page.
     */
    public function url(): string
    {
        return "/contracts/quotes/{$this->quoteId}";
    }

    /**
     * Assert that the browser is on this page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertPathBeginsWith('/contracts/quotes/');
    }

    /**
     * Get the element shortcuts for this page.
     */
    public function elements(): array
    {
        return [
            // Quote Header
            '@quote-number' => '[dusk="quote-number"], .quote-number',
            '@quote-status' => '[dusk="quote-status"], .quote-status, .badge',
            '@quote-title' => '[dusk="quote-title"], h1, .quote-title',
            '@client-name' => '[dusk="client-name"], .client-name',
            
            // Quote Details
            '@billing-type' => '[dusk="billing-type"]',
            '@billing-cycle' => '[dusk="billing-cycle"]',
            '@valid-until' => '[dusk="valid-until"]',
            '@created-date' => '[dusk="created-date"]',
            
            // Line Items Table
            '@line-items-table' => '[dusk="line-items"], table.line-items, .line-items',
            '@line-item-row' => 'table.line-items tbody tr, .line-item',
            '@quote-subtotal' => '[dusk="subtotal"], .subtotal',
            '@quote-total' => '[dusk="total"], .total',
            
            // Action Buttons - availability depends on quote status
            '@edit-btn' => '[dusk="edit-quote"], a[href*="edit"]',
            '@approve-btn' => '[dusk="approve-quote"], form[action*="approve"] button, .btn-approve',
            '@send-btn' => '[dusk="send-quote"], form[action*="send"] button, .btn-send',
            '@revise-btn' => '[dusk="revise-quote"], form[action*="revise"] button',
            '@duplicate-btn' => '[dusk="duplicate-quote"], form[action*="duplicate"] button',
            '@delete-btn' => '[dusk="delete-quote"], form[action*="delete"] button, .btn-delete',
            '@pdf-btn' => '[dusk="download-pdf"], a[href*="pdf"]',
            
            // Contract Creation (after approval)
            '@create-contract-btn' => '[dusk="create-contract"], .btn-create-contract',
            '@contract-link' => '[dusk="contract-link"], a[href*="contracts/agreements"]',
            
            // Notes
            '@notes-section' => '[dusk="notes"], .notes',
            
            // Revision History (if visible)
            '@revision-history' => '[dusk="revision-history"], .revision-history',
        ];
    }

    /**
     * Get the displayed quote status.
     */
    public function getStatus(Browser $browser): string
    {
        return trim($browser->text('@quote-status'));
    }

    /**
     * Approve the quote.
     */
    public function approve(Browser $browser): void
    {
        $browser->click('@approve-btn')
            ->pause(500); // Wait for action to complete
        
        // Handle confirmation dialog if present
        try {
            $browser->acceptDialog();
        } catch (\Exception $e) {
            // No dialog present, continue
        }
        
        $browser->pause(300);
    }

    /**
     * Assert the quote has a specific status.
     */
    public function assertStatus(Browser $browser, string $expectedStatus): void
    {
        $browser->assertSeeIn('@quote-status', $expectedStatus);
    }

    /**
     * Assert the quote total matches expected amount.
     */
    public function assertTotal(Browser $browser, string $expectedTotal): void
    {
        $browser->assertSeeIn('@quote-total', $expectedTotal);
    }

    /**
     * Assert a line item exists with the given description.
     */
    public function assertLineItemExists(Browser $browser, string $description): void
    {
        $browser->assertSeeIn('@line-items-table', $description);
    }

    /**
     * Click to edit the quote.
     */
    public function clickEdit(Browser $browser): void
    {
        $browser->waitFor('[dusk="edit-quote"]', 10)
            ->click('[dusk="edit-quote"]')
            ->pause(300);
    }

    /**
     * Navigate to the associated contract (if quote is approved).
     */
    public function goToContract(Browser $browser): void
    {
        $browser->click('@contract-link')
            ->pause(300);
    }

    /**
     * Assert that the approve action is available.
     */
    public function assertCanApprove(Browser $browser): void
    {
        $browser->assertPresent('@approve-btn');
    }

    /**
     * Assert that a contract has been created from this quote.
     */
    public function assertContractCreated(Browser $browser): void
    {
        $browser->assertPresent('@contract-link');
    }
}
