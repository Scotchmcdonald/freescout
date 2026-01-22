<?php

/**
 * Login Page Object
 * 
 * Handles authentication flow for Dusk tests.
 * 
 * MAINTENANCE NOTES:
 * -----------------
 * - If login form changes, update selectors in elements()
 * - If OAuth/SSO is added, add new methods here
 * - The login URL is configured here - update if auth routes change
 * 
 * SELECTOR UPDATE GUIDE:
 * If login form breaks, check:
 * 1. Input field names (email, password)
 * 2. Submit button text or class
 * 3. Error message container
 */

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

class LoginPage extends Page
{
    /**
     * Get the URL for this page.
     */
    public function url(): string
    {
        return '/login';
    }

    /**
     * Assert that the browser is on this page.
     */
    public function assert(Browser $browser): void
    {
        // Just verify we're on the login path
        // Different auth packages may have different text (Email, E-mail, Sign in, Log in)
        $browser->assertPathIs($this->url());
    }

    /**
     * Get the element shortcuts for this page.
     * 
     * UPDATE THESE when login form changes:
     */
    public function elements(): array
    {
        return [
            // Form Fields - using name attributes (most stable for forms)
            '@email-input' => 'input[name="email"]',
            '@password-input' => 'input[name="password"]',
            '@remember-checkbox' => 'input[name="remember"]',
            
            // Submit Button - multiple fallbacks
            '@login-button' => '[dusk="login-button"], button[type="submit"], input[type="submit"]',
            
            // Error Messages
            '@login-error' => '[dusk="login-error"], .alert-danger, .invalid-feedback',
        ];
    }

    /**
     * Perform login action.
     * 
     * @param Browser $browser
     * @param string $email
     * @param string $password
     * @param bool $remember
     * @return void
     */
    public function login(Browser $browser, string $email, string $password, bool $remember = false): void
    {
        $browser
            ->type('@email-input', $email)
            ->type('@password-input', $password);
        
        if ($remember) {
            $browser->check('@remember-checkbox');
        }
        
        $browser->click('@login-button')
            ->pause(500); // Wait for redirect
    }

    /**
     * Assert login failed with error message.
     */
    public function assertLoginFailed(Browser $browser): void
    {
        $browser->assertPresent('@login-error');
    }
}
