<?php

/**
 * Credit Ledger Page Object
 * 
 * Handles the PIB credit ledger view for a client.
 * Shows all credit/debit transactions with running balance.
 * 
 * MAINTENANCE NOTES:
 * -----------------
 * - This page may be accessed from Client 360 view
 * - Shows credit transactions in a table format
 * - May include ability to add credits (admin action)
 * 
 * ROUTE: admin/billing/credit-ledger/{client} (route: admin.billing.credit-ledger.show)
 * CONTROLLER: Modules\PIB\Http\Controllers\CreditLedgerController
 */

namespace Tests\Browser\Pages\PIB;

use Laravel\Dusk\Browser;
use Tests\Browser\Pages\Page;

class CreditLedgerPage extends Page
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
        return "/admin/billing/credit-ledger/{$this->clientId}";
    }

    /**
     * Assert that the browser is on this page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertPathBeginsWith('/admin/billing/credit-ledger/');
    }

    /**
     * Get the element shortcuts for this page.
     */
    public function elements(): array
    {
        return [
            // Header
            '@page-title' => '[dusk="page-title"], h1',
            '@client-name' => '[dusk="client-name"], .client-name',
            '@current-balance' => '[dusk="current-balance"]',
            
            // Add Credit Form/Button
            '@add-credit-btn' => '[dusk="add-credit"]',
            '@credit-amount-input' => '[dusk="credit-amount"], input[name="amount"]',
            '@credit-description-input' => '[dusk="credit-description"], input[name="description"], textarea[name="description"]',
            '@credit-submit-btn' => '[dusk="submit-credit"], form button[type="submit"]',
            
            // Add Debit Form/Button (if separate)
            '@add-debit-btn' => '[dusk="add-debit"], .add-debit',
            
            // Ledger Table
            '@ledger-table' => '[dusk="ledger-table"], table, .ledger-table',
            '@ledger-row' => 'tbody tr',
            '@empty-state' => '[dusk="empty-ledger"], .empty-state',
            
            // Table Columns (for specific assertions)
            '@col-date' => 'td.date, td:nth-child(1)',
            '@col-type' => 'td.type, td:nth-child(2)',
            '@col-amount' => 'td.amount, td:nth-child(3)',
            '@col-description' => 'td.description, td:nth-child(4)',
            '@col-balance' => 'td.balance, td:nth-child(5)',
            
            // Filters
            '@date-from' => '[dusk="date-from"], input[name="from"]',
            '@date-to' => '[dusk="date-to"], input[name="to"]',
            '@type-filter' => '[dusk="type-filter"], select[name="type"]',
            
            // Export
            '@export-btn' => '[dusk="export"], a[href*="export"], .btn-export',
            
            // Modal (if add credit uses modal)
            '@credit-modal' => '[dusk="credit-modal"], #creditModal, .modal',
        ];
    }

    /**
     * Get the current balance displayed on the page.
     */
    public function getCurrentBalance(Browser $browser): string
    {
        return trim($browser->text('@current-balance'));
    }

    /**
     * Add a credit to the client's account.
     * 
     * @param Browser $browser
     * @param float $amount
     * @param string $description
     */
    public function addCredit(Browser $browser, float $amount, string $description): void
    {
        // Click add credit button (may open modal or expand form)
        $browser->waitFor('@add-credit-btn')
            ->click('@add-credit-btn')
            ->pause(300);
        
        // Fill the form
        $browser->type('@credit-amount-input', (string) $amount)
            ->type('@credit-description-input', $description)
            ->click('@credit-submit-btn')
            ->pause(500);
    }

    /**
     * Assert the current balance matches expected.
     * 
     * @param Browser $browser
     * @param string $expectedBalance Formatted like "$175.00"
     */
    public function assertBalance(Browser $browser, string $expectedBalance): void
    {
        $browser->assertSeeIn('@current-balance', $expectedBalance);
    }

    /**
     * Assert a transaction appears in the ledger.
     */
    public function assertTransactionExists(Browser $browser, string $description): void
    {
        $browser->assertSeeIn('@ledger-table', $description);
    }

    /**
     * Assert the ledger shows expected number of entries.
     */
    public function assertTransactionCount(Browser $browser, int $expected): void
    {
        $rows = $browser->elements('@ledger-row');
        \PHPUnit\Framework\Assert::assertCount($expected, $rows);
    }

    /**
     * Assert a credit transaction appears (positive amount).
     */
    public function assertCreditTransaction(Browser $browser, float $amount, string $description): void
    {
        $formattedAmount = number_format($amount, 2);
        $browser->assertSeeIn('@ledger-table', $description)
            ->assertSeeIn('@ledger-table', $formattedAmount);
    }

    /**
     * Assert a debit transaction appears (negative amount).
     */
    public function assertDebitTransaction(Browser $browser, float $amount, string $description): void
    {
        $formattedAmount = number_format($amount, 2);
        $browser->assertSeeIn('@ledger-table', $description)
            ->assertSeeIn('@ledger-table', $formattedAmount);
    }

    /**
     * Export the ledger (if feature available).
     */
    public function clickExport(Browser $browser): void
    {
        $browser->click('@export-btn');
    }
}
