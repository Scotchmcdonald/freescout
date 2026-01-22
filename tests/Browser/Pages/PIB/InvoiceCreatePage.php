<?php

/**
 * Invoice Create Page Object
 * 
 * Manual invoice creation form in PIB module.
 * 
 * ROUTE: admin/billing/invoices/create (estimated route)
 */

namespace Tests\Browser\Pages\PIB;

use Laravel\Dusk\Browser;
use Tests\Browser\Pages\Page;

class InvoiceCreatePage extends Page
{
    /**
     * Get the URL for this page.
     */
    public function url(): string
    {
        return '/admin/billing/invoices/create';
    }

    /**
     * Assert that the browser is on this page.
     */
    public function assert(Browser $browser): void
    {
        $browser->waitForText('Create', 5);
    }

    /**
     * Get the element shortcuts for this page.
     */
    public function elements(): array
    {
        return [
            '@client-select' => '[dusk="client-select"], select[name="client_id"], #client_id',
            '@description-input' => '[dusk="description"], input[name="description"], textarea[name="description"]',
            '@amount-input' => '[dusk="amount"], input[name="amount"]',
            '@due-date-input' => '[dusk="due-date"], input[name="due_date"]',
            '@status-select' => '[dusk="status"], select[name="status"]',
            '@save-draft-btn' => '[dusk="save-draft"], button:contains("Draft"), button[type="submit"]',
            '@publish-btn' => '[dusk="publish"], button:contains("Publish")',
            '@cancel-btn' => '[dusk="cancel"], a.cancel',
            
            // Line items
            '@line-items-container' => '[dusk="line-items"], #line-items',
            '@add-line-item-btn' => '[dusk="add-line-item"], button:contains("Add")',
            '@line-item-description-0' => 'input[name="line_items[0][description]"], textarea[name="line_items[0][description]"]',
            '@line-item-amount-0' => 'input[name="line_items[0][amount]"]',
        ];
    }

    /**
     * Fill invoice details.
     * 
     * @param Browser $browser
     * @param array $details Keys: client_id, description, amount, status
     */
    public function fillInvoiceDetails(Browser $browser, array $details): void
    {
        if (isset($details['client_id']) && $browser->element('@client-select')) {
            $browser->select('@client-select', (string) $details['client_id']);
        }

        if (isset($details['description']) && $browser->element('@description-input')) {
            $browser->type('@description-input', $details['description']);
        }

        if (isset($details['amount']) && $browser->element('@amount-input')) {
            $browser->type('@amount-input', (string) $details['amount']);
        }

        if (isset($details['status']) && $browser->element('@status-select')) {
            $browser->select('@status-select', $details['status']);
        }
    }

    /**
     * Add a line item.
     */
    public function addLineItem(Browser $browser, string $description, float $amount): void
    {
        // Check if we can add line items
        if ($browser->element('@line-item-description-0')) {
            $browser->type('@line-item-description-0', $description)
                ->type('@line-item-amount-0', (string) $amount);
        }
    }

    /**
     * Submit as draft.
     */
    public function submitAsDraft(Browser $browser): void
    {
        $browser->click('@save-draft-btn')
            ->pause(1000);
    }

    /**
     * Publish invoice.
     */
    public function publishInvoice(Browser $browser): void
    {
        if ($browser->element('@publish-btn')) {
            $browser->click('@publish-btn')
                ->pause(1000);
        }
    }
}
