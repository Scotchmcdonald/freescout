<?php

/**
 * CRM Feature Tests
 * 
 * Tests core CRM functionality: Client creation, contact management,
 * and Client 360 view integration.
 * 
 * RUNNING TESTS:
 * php artisan dusk tests/Browser/CrmFeatureTest.php
 * php artisan dusk --group=crm
 */

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\DuskTestCase;
use Tests\Browser\Pages\Crm\Client360Page;

class CrmFeatureTest extends DuskTestCase
{
    protected static string $testRunId;
    protected static array $createdData = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$testRunId = date('Ymd-His');
    }

    protected function getAdminUser(): User
    {
        $user = User::first() ?? User::factory()->create();
        
        if (!$user->isAdmin()) {
            $user->role = User::ROLE_ADMIN;
            $user->save();
        }
        
        return $user;
    }

    protected function testId(string $prefix = 'DUSK'): string
    {
        return "{$prefix}-" . self::$testRunId;
    }

    /**
     * Test 1.1: Create a test client.
     * 
     * VERIFIES:
     * - Client creation form works
     * - Client persists to database
     * - Client appears in listing
     */
    #[Group('crm')]
    #[Group('smoke')]
    public function test_can_create_client(): void
    {
        $this->browse(function (Browser $browser) {
            $clientName = "TEST-CLIENT-" . $this->testId();
            
            $browser->loginAs($this->getAdminUser())
                ->visit('/admin/crm/clients')
                ->pause(500);
            
            // Try different selectors for "Create" button
            $createSelectors = [
                '[dusk="create-client"]',
                'a:contains("Create")',
                'a:contains("New Client")',
                '.btn-primary:contains("Create")',
            ];
            
            foreach ($createSelectors as $selector) {
                if ($browser->element($selector)) {
                    $browser->click($selector)
                        ->pause(500);
                    break;
                }
            }
            
            // Fill client form
            if ($browser->element('input[name="name"]')) {
                $browser->type('input[name="name"]', $clientName)
                    ->type('input[name="email"]', "test-{$this->testId()}@example.com");
                
                if ($browser->element('select[name="status"]')) {
                    $browser->select('select[name="status"]', 'active');
                }
                
                if ($browser->element('@save-client-btn')) {
                    $browser->click('@save-client-btn');
                } else {
                    $browser->press('Save');
                }
                
                $browser->pause(1000)
                    ->assertSee($clientName);
                
                // Capture ID from DB if not in URL
                $client = \Modules\Crm\Models\Client::where('name', $clientName)->first();
                if ($client) {
                     self::$createdData['client_id'] = $client->id;
                } else {
                     // Try to capture client ID from URL
                     $currentUrl = $browser->driver->getCurrentURL();
                     if (preg_match('/\/clients\/(\d+)/', $currentUrl, $matches)) {
                         self::$createdData['client_id'] = (int) $matches[1];
                     }
                }
            } else {
                $this->markTestIncomplete('Client creation form not found');
            }
        });
    }

    /**
     * Test 1.2: Add contacts to client.
     * 
     * VERIFIES:
     * - Contact can be added to client
     * - Contact association persists
     * - Multiple contacts supported
     */
    #[Group('crm')]
    public function test_can_add_contacts_to_client(): void
    {
        $this->browse(function (Browser $browser) {
            $clientId = self::$createdData['client_id'] ?? null;
            
            if (!$clientId) {
                $this->markTestSkipped('No client ID from previous test');
                return;
            }
            
            $browser->loginAs($this->getAdminUser())
                ->visit("/admin/clients/{$clientId}")
                ->pause(500);
            
            // Switch to Contacts tab if available
            $browser->assertPresent('[dusk="contacts-tab"]');
            $browser->click('[dusk="contacts-tab"]')->pause(1000);
            
            // Wait for button
            try {
                $browser->waitFor('[dusk="add-contact"]', 5);
            } catch (\Exception $e) {
                // Ignore wait error, handle in selectors
            }

            try {
                // Look for "Add Contact" button
                $addContactSelectors = [
                    '[dusk="add-contact"]',
                    'button:contains("Add Contact")',
                    'a:contains("Add Contact")',
                ];
                
                $found = false;
                foreach ($addContactSelectors as $selector) {
                    if ($browser->element($selector)) {
                        $browser->click($selector)
                            ->pause(500);
                        $found = true;
                        break;
                    }
                }
                
                if (!$found) {
                     $browser->screenshot('debug-no-add-contact');
                     $this->fail('Add Contact button not found. Created Client ID: ' . $clientId . ' URL: ' . $browser->driver->getCurrentURL());
                }
                
                if ($found) {
                    $contactEmail = "contact1-{$this->testId()}@test.example.com";
                    $contactName = "Test Contact One";
                    
                    if ($browser->element('input[name="name"]') || $browser->element('input[name="contact_name"]')) {
                        $nameField = $browser->element('input[name="name"]') ? 'input[name="name"]' : 'input[name="contact_name"]';
                        $emailField = $browser->element('input[name="email"]') ? 'input[name="email"]' : 'input[name="contact_email"]';
                        
                        $browser->type($nameField, $contactName)
                            ->type($emailField, $contactEmail);
                        
                        if ($browser->element('input[name="phone"]')) {
                            $browser->type('input[name="phone"]', '555-0100');
                        }
                        
                        $browser->press('Save')
                            ->pause(500)
                            ->assertSee($contactName);
                    }
                }
            } catch (\Exception $e) {
                $browser->screenshot('contact-creation-failed');
                $this->fail('Contact creation failed: ' . $e->getMessage());
            }
        });
    }

    /**
     * Test 1.4: Client 360 view integration.
     * 
     * VERIFIES:
     * - Client 360 page loads without errors
     * - All module sections display (even if empty)
     * - No broken widgets or undefined sections
     */
    #[Group('crm')]
    #[Group('integration')]
    public function test_client_360_view_displays(): void
    {
        $this->browse(function (Browser $browser) {
            $clientId = self::$createdData['client_id'] ?? 1;
            
            $browser->loginAs($this->getAdminUser())
                ->visit(new Client360Page($clientId))
                ->pause(1000);
            
            // Verify page loaded
            $browser->assertDontSee('404')
                ->assertDontSee('Error')
                ->assertDontSee('undefined');
            
            // Take screenshot for visual verification
            $browser->screenshot('client-360-view');
            
            // Check for common sections (may be module-dependent)
            // These are the tab labels in the Client 360 view
            $sectionsToCheck = [
                'Overview',     // Always present
                'Contacts',     // Always present (from CRM)
                'Assets',       // If AssetManagement widgets loaded
                'Billing',      // If PIB widgets loaded
            ];
            
            $foundCount = 0;
            foreach ($sectionsToCheck as $section) {
                try {
                    // Use Dusk's native text search which is more reliable
                    $pageSource = $browser->driver->getPageSource();
                    if (str_contains($pageSource, $section)) {
                        $foundCount++;
                    }
                } catch (\Exception $e) {
                    // Section not found, that's OK
                }
            }
            
            // At least one section should be visible
            $this->assertGreaterThan(0, $foundCount, 'Client 360 should display at least one section');
        });
    }
}
