<?php

/**
 * Software Subscription Detail Page Object
 * 
 * Shows subscription details and manages assignments.
 */

namespace Tests\Browser\Pages\SoftwareSubscriptions;

use Laravel\Dusk\Browser;
use Tests\Browser\Pages\Page;

class SoftwareSubscriptionDetailPage extends Page
{
    protected int $subscriptionId;

    public function __construct(int $subscriptionId)
    {
        $this->subscriptionId = $subscriptionId;
    }

    /**
     * Get the URL for this page.
     */
    public function url(): string
    {
        return "/admin/software-subscriptions/{$this->subscriptionId}";
    }

    /**
     * Assert that the browser is on this page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertPathBeginsWith('/admin/software-subscriptions/');
    }

    /**
     * Get the element shortcuts for this page.
     */
    public function elements(): array
    {
        return [
            // Header
            '@product-name' => '[dusk="product-name"], h1, .product-name',
            '@client-name' => '[dusk="client-name"], .client-name',
            '@status-badge' => '[dusk="status"], .status-badge',
            
            // Counts
            '@assignment-count' => '[dusk="assignment-count"], .assignment-count',
            '@license-count' => '[dusk="license-count"], .license-count',
            
            // Assignment Section
            '@assignments-section' => '[dusk="assignments"], #assignments',
            '@add-assignment-btn' => '[dusk="add-assignment"], .btn-add-assignment',
            '@assignments-table' => '[dusk="assignments-table"], table.assignments',
            '@assignment-row' => 'table.assignments tbody tr',
            
            // Add Assignment Modal/Form
            '@assignment-modal' => '[dusk="assignment-modal"], #assignmentModal',
            '@assignee-select' => '[dusk="assignee"], select[name="assignable_id"]',
            '@assignee-type-select' => '[dusk="assignee-type"], select[name="assignable_type"]',
            '@save-assignment-btn' => '[dusk="save-assignment"], .modal button[type="submit"]',
            
            // Actions
            '@edit-btn' => '[dusk="edit"], a[href*="edit"]',
            '@cancel-subscription-btn' => '[dusk="cancel"], .btn-cancel',
            
            // Billing Info
            '@billing-behavior' => '[dusk="billing-behavior"]',
            '@monthly-cost' => '[dusk="monthly-cost"]',
        ];
    }

    /**
     * Get the current assignment count.
     */
    public function getAssignmentCount(Browser $browser): int
    {
        $text = $browser->text('@assignment-count');
        return (int) preg_replace('/[^0-9]/', '', $text);
    }

    /**
     * Add an assignment to this subscription.
     * 
     * @param Browser $browser
     * @param string $type 'contact' or 'asset'
     * @param int $id The contact or asset ID
     */
    public function addAssignment(Browser $browser, string $type, int $id): void
    {
        $browser->click('@add-assignment-btn')
            ->pause(300);
        
        // Fill assignment form
        $browser->select('@assignee-type-select', $type)
            ->pause(200) // Wait for ID options to load
            ->select('@assignee-select', $id)
            ->click('@save-assignment-btn')
            ->pause(500);
    }

    /**
     * Remove an assignment by clicking revoke/remove.
     */
    public function removeAssignment(Browser $browser, string $assigneeName): void
    {
        $browser->click("@assignments-table tr:contains('{$assigneeName}') .btn-revoke, " .
                       "@assignments-table tr:contains('{$assigneeName}') .btn-remove")
            ->pause(300);
        
        // Accept confirmation if present
        try {
            $browser->acceptDialog();
        } catch (\Exception $e) {
            // No dialog
        }
        
        $browser->pause(500);
    }

    /**
     * Assert an assignment exists.
     */
    public function assertAssignmentExists(Browser $browser, string $assigneeName): void
    {
        $browser->assertSeeIn('@assignments-table', $assigneeName);
    }

    /**
     * Assert assignment count displays expected value.
     */
    public function assertAssignmentCount(Browser $browser, int $expected): void
    {
        $browser->assertSeeIn('@assignment-count', (string) $expected);
    }
}
