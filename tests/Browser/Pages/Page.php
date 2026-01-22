<?php

/**
 * Base Page Object for all Dusk tests.
 * 
 * This class provides common functionality shared across all page objects.
 * Extend this class when creating new page objects.
 * 
 * @package Tests\Browser\Pages
 * 
 * MAINTENANCE NOTES:
 * -----------------
 * - When UI framework changes (e.g., Tailwind classes), update selectors here
 * - Common elements like navigation, flash messages are defined here
 * - All page objects inherit these elements and methods
 */

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page as BasePage;

abstract class Page extends BasePage
{
    /**
     * Get the global element shortcuts for the site.
     * 
     * SELECTOR STRATEGY (priority order):
     * 1. [dusk="..."] - First choice, add to templates when needed
     * 2. [name="..."] - Good for form fields
     * 3. #id - When IDs are stable
     * 4. .class - Avoid, CSS changes frequently
     *
     * @return array<string, string>
     */
    public static function siteElements(): array
    {
        return [
            // Flash Messages
            // UPDATE THESE if alert component changes
            '@success-message' => '[dusk="success-message"], .alert-success, [role="alert"].bg-green-100',
            '@error-message' => '[dusk="error-message"], .alert-danger, [role="alert"].bg-red-100',
            '@warning-message' => '[dusk="warning-message"], .alert-warning, [role="alert"].bg-yellow-100',
            
            // Navigation (may need adjustment based on your layout)
            '@sidebar' => '[dusk="sidebar"], #sidebar, .sidebar',
            '@main-content' => '[dusk="main-content"], main, #main-content, .main-content',
            
            // Loading States
            '@loading-spinner' => '[dusk="loading"], .loading, .spinner',
            
            // Modals
            '@modal' => '[dusk="modal"], .modal.show, [role="dialog"]',
            '@modal-close' => '[dusk="modal-close"], .modal .close, [data-dismiss="modal"]',
        ];
    }

    /**
     * Wait for page to fully load.
     * Override in child classes for pages with async content.
     */
    public function waitForPageLoad(Browser $browser): void
    {
        $browser->pause(300); // Brief pause for DOM to settle
    }

    /**
     * Assert a success flash message appears.
     * 
     * @param Browser $browser
     * @param string|null $text Optional text to assert within the message
     */
    public function assertSuccessMessage(Browser $browser, ?string $text = null): void
    {
        $browser->waitFor('@success-message', 5);
        
        if ($text) {
            $browser->assertSeeIn('@success-message', $text);
        }
    }

    /**
     * Assert an error flash message appears.
     */
    public function assertErrorMessage(Browser $browser, ?string $text = null): void
    {
        $browser->waitFor('@error-message', 5);
        
        if ($text) {
            $browser->assertSeeIn('@error-message', $text);
        }
    }
}
