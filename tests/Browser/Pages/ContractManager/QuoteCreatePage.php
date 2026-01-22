<?php

/**
 * Quote Create Page Object
 * 
 * Handles the quote creation form in ContractManager.
 * 
 * MAINTENANCE NOTES:
 * -----------------
 * - Line items are dynamically added via JavaScript
 * - Client dropdown may use Select2 or similar
 * - Form validation happens both client-side and server-side
 * 
 * ROUTE: contracts/quotes/create (route: contractmanager.quotes.create)
 * CONTROLLER: Modules\ContractManager\Http\Controllers\QuoteController
 * VIEW: Modules/ContractManager/resources/views/quotes/create.blade.php
 */

namespace Tests\Browser\Pages\ContractManager;

use Laravel\Dusk\Browser;
use Tests\Browser\Pages\Page;

class QuoteCreatePage extends Page
{
    /**
     * Get the URL for this page.
     */
    public function url(): string
    {
        return '/contracts/quotes/create';
    }

    /**
     * Assert that the browser is on this page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url())
            ->assertSee('Create'); // "Create Quote" or similar heading
    }

    /**
     * Get the element shortcuts for this page.
     * 
     * FORM STRUCTURE:
     * 1. Quote Details (client, title, billing type, cycle, valid_until, notes)
     * 2. Line Items (dynamically added rows)
     * 3. Submit buttons (Save Draft, etc.)
     */
    public function elements(): array
    {
        return [
            // ============================================================
            // QUOTE DETAILS SECTION
            // ============================================================
            '@client-select' => '[dusk="client-select"], select[name="client_id"], #client_id',
            '@title-input' => '[dusk="title-input"], input[name="title"], #title',
            '@billing-type-select' => '[dusk="billing-type"], select[name="billing_type"], #billing_type',
            '@billing-cycle-select' => '[dusk="billing-cycle"], select[name="billing_cycle"], #billing_cycle',
            '@valid-until-input' => '[dusk="valid-until"], input[name="valid_until"], #valid_until',
            '@notes-textarea' => '[dusk="notes"], textarea[name="notes"], #notes',
            
            // ============================================================
            // LINE ITEMS SECTION
            // These are dynamically added - selectors use array notation
            // ============================================================
            '@line-items-container' => '[dusk="line-items"], #line-items',
            '@add-line-item-btn' => '[dusk="add-line-item"], .add-line-item, button:contains("Add")',
            
            // First line item (index 0) - update index for additional items
            '@line-item-description-0' => 'input[name="line_items[0][description]"]',
            '@line-item-quantity-0' => 'input[name="line_items[0][quantity]"]',
            '@line-item-price-0' => 'input[name="line_items[0][unit_price]"]',
            '@line-item-remove-0' => '.line-item:first-child .remove-line-item',
            
            // Second line item (index 1)
            '@line-item-description-1' => 'input[name="line_items[1][description]"]',
            '@line-item-quantity-1' => 'input[name="line_items[1][quantity]"]',
            '@line-item-price-1' => 'input[name="line_items[1][unit_price]"]',
            
            // ============================================================
            // FORM ACTIONS
            // ============================================================
            '@save-draft-btn' => '[dusk="save-draft"], button[type="submit"], input[type="submit"]',
            '@cancel-btn' => '[dusk="cancel"], a.cancel, a[href*="quotes"]',
            
            // ============================================================
            // TOTALS DISPLAY (if visible during creation)
            // ============================================================
            '@subtotal' => '[dusk="subtotal"], .subtotal',
            '@total' => '[dusk="total"], .total',
        ];
    }

    /**
     * Fill in the quote details section.
     * 
     * @param Browser $browser
     * @param array $details Keys: client_id, title, billing_type, billing_cycle, valid_until, notes
     */
    public function fillQuoteDetails(Browser $browser, array $details): void
    {
        if (isset($details['client_id'])) {
            $browser->select('@client-select', $details['client_id']);
        }
        
        if (isset($details['title'])) {
            $browser->type('@title-input', $details['title']);
        }
        
        if (isset($details['billing_type'])) {
            $browser->select('@billing-type-select', $details['billing_type']);
        }
        
        if (isset($details['billing_cycle'])) {
            $browser->select('@billing-cycle-select', $details['billing_cycle']);
        }
        
        if (isset($details['valid_until'])) {
            // Date inputs may need special handling
            $browser->type('@valid-until-input', $details['valid_until']);
        }
        
        if (isset($details['notes'])) {
            $browser->type('@notes-textarea', $details['notes']);
        }
    }

    /**
     * Add a line item to the quote.
     * 
     * @param Browser $browser
     * @param int $index Line item index (0-based)
     * @param string $description
     * @param int|float $quantity
     * @param float $unitPrice
     */
    public function fillLineItem(Browser $browser, int $index, string $description, $quantity, float $unitPrice): void
    {
        // If we need to add a new line item row (index > 0)
        if ($index > 0) {
            // Check if the row exists, if not click add button
            $selector = "input[name=\"line_items[{$index}][description]\"]";
            
            try {
                $browser->assertPresent($selector);
            } catch (\Exception $e) {
                $browser->click('@add-line-item-btn')
                    ->pause(300);
            }
        }
        
        $browser
            ->type("input[name=\"line_items[{$index}][description]\"]", $description)
            ->type("input[name=\"line_items[{$index}][quantity]\"]", (string) $quantity)
            ->type("input[name=\"line_items[{$index}][unit_price]\"]", (string) $unitPrice);
    }

    /**
     * Submit the quote form.
     */
    public function submitQuote(Browser $browser): void
    {
        $browser->click('@save-draft-btn')
            ->pause(500); // Wait for form submission
    }

    /**
     * Complete quote creation with details and line items.
     * 
     * @param Browser $browser
     * @param array $details Quote details
     * @param array $lineItems Array of [description, quantity, price] arrays
     */
    public function createQuote(Browser $browser, array $details, array $lineItems): void
    {
        $this->fillQuoteDetails($browser, $details);
        
        foreach ($lineItems as $index => $item) {
            $this->fillLineItem(
                $browser,
                $index,
                $item['description'],
                $item['quantity'],
                $item['price']
            );
        }
        
        $this->submitQuote($browser);
    }
}
