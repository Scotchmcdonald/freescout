<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Modules\Crm\Models\Client;
use Modules\PIB\Models\Invoice;
use PHPUnit\Framework\Attributes\Group;
use Illuminate\Support\Facades\DB;

/**
 * Complete Sales-to-Cash Cycle E2E Tests
 * 
 * Tests the complete business workflow from quote to payment.
 */
class SalesToCashE2ETest extends MultiUserTestCase
{
    /**
     * Test 1: Complete Sales-to-Cash Lifecycle
     */
    #[Group('sales-to-cash')]
    #[Group('e2e')]
    #[Group('critical')]
    #[Group('revenue-pipeline')]
    public function test_complete_sales_to_cash_cycle(): void
    {
        // Setup: Create client with portal access
        $setup = $this->createClientWithPortalUser([
            'name' => 'New Client Corp ' . uniqid(),
            'email' => 'billing_' . uniqid() . '@example.com',
        ]);
        
        $client = $setup['client'];
        $clientUser = $setup['user'];
        
        // Shared state
        $quoteId = null;
        $contractId = null;
        $invoiceId = null;
        
        $this->browse(function (Browser $admin, Browser $clientBrowser) 
            use ($client, $clientUser, &$quoteId, &$contractId, &$invoiceId) {
            
            // =================================================================
            // PHASE 1: SALES - Quote Creation (Admin)
            // =================================================================
            
            $this->loginAsAdmin($admin)
                ->visit('/contracts/quotes/create');
            
            // Debugging: Check for page content presence
            try {
                $admin->waitForText('Create New Quote', 5);
                fwrite(STDERR, "[DEBUG] 'Create New Quote' header found.\n");
            } catch (\Exception $e) {
                $bodyText = $admin->text();
                fwrite(STDERR, "\n[DEBUG] Expected 'Create New Quote' not found.\n");
                fwrite(STDERR, "[DEBUG] Page Title: " . $admin->driver->getTitle() . "\n");
                fwrite(STDERR, "[DEBUG] Visible Text: " . substr($bodyText, 0, 300) . "...\n");
                
                // If we see "Forbidden" or "Error", it's a server/permission issue
                if (str_contains($bodyText, 'Forbidden')) {
                    throw new \Exception("Access Forbidden to Quotes Create");
                }
            }

            $admin->waitFor('@quote-create-form', 5);
            
            // Create quote
            $admin->select('@client-select', $client->id)
                ->type('title', 'Managed Services Package')
                ->type('line_items[0][description]', 'Monthly IT Support')
                ->type('line_items[0][unit_price]', '500.00')
                ->click('@save-quote-button'); // Uses dusk selector

            // Wait for redirect to Quote Details
            $admin->waitUntil("window.location.pathname.match(/\/contracts\/quotes\/\d+/)", 10);
            
            // Capture ID from URL
            $url = $admin->driver->getCurrentURL();
            preg_match('/quotes\/(\d+)/', $url, $matches);
            $quoteId = $matches[1];
            $this->assertNotNull($quoteId, 'Quote ID should be captured from URL');

            // Send to Client
            $admin->waitFor('@send-quote')
                  ->click('@send-quote')
                  ->waitForText('Sent', 10); // Check for flash message or status change
            
            // =================================================================
            // PHASE 2: CLIENT APPROVAL (Client Portal)
            // =================================================================
            
            $this->loginAsClient($clientBrowser, $clientUser)
                ->visit('/portal/approvals')
                ->waitFor('table')
                ->waitForText('Managed Services Package'); // Ensure our quote is there
            
            // Click View Details (Assuming most recent is first, or filtering)
            $clientBrowser->click("table tbody tr:first-child a") 
                ->waitFor('@approve-request-button');
                
            // Interactive Approval
            $clientBrowser->waitFor('@approve-request-button')
                ->click('@approve-request-button') // Toggles form
                ->waitFor('@confirm-approval-button')
                ->type('notes', 'Looks good to me!')
                ->pause(500) // Wait for Alpine transition
                ->click('@confirm-approval-button');
                
            // Wait for success
            try {
                $clientBrowser->waitForText('approved successfully', 15);
                $clientBrowser->assertSee('approved successfully');
            } catch (\Exception $e) {
                // Get visible text from body for debugging
                $text = $clientBrowser->element('body')->getText();
                fwrite(STDERR, "\n[DEBUG] Approval success message not found.\n");
                
                // Screenshot for visual debugging
                $clientBrowser->screenshot('approval_failure');
                
                fwrite(STDERR, "[DEBUG] Visible Text: " . substr($text, 0, 500) . "...\n");
                throw $e;
            }

            // =================================================================
            // PHASE 3: CONTRACT VERIFICATION (Admin)
            // =================================================================
            
            $admin->refresh()
                ->waitFor('@contract-link'); // Contract button appears after approval
                
            $admin->click('@contract-link')
                  ->waitUntil("window.location.pathname.match(/\/contracts\/agreements\/\d+/)", 10)
                  ->assertSee('Contract');
                  
            // Capture Contract ID
            $url = $admin->driver->getCurrentURL();
            preg_match('/contracts\/agreements\/(\d+)/', $url, $matches);
            $contractId = $matches[1];
            
            // =================================================================
            // PHASE 4: INVOICE GENERATION (Admin)
            // =================================================================
            
            // Navigate to Billing Template to generate invoice manually/trigger it
            $admin->visit('/contracts/billing-templates')
                 ->waitForText($client->name);
                 
            // Click View on the template
            // Assuming the template exists because contract was created
            // Use XPath to find the row with client name
            $admin->driver->findElement(\Facebook\WebDriver\WebDriverBy::xpath("//tr[contains(., \"{$client->name}\")]"))
                ->findElement(\Facebook\WebDriver\WebDriverBy::linkText('View'))
                ->click();
            
            $admin->waitForText('Generate Invoice Now', 10)
                  ->press('Generate Invoice Now')
                  ->pause(2000); // Wait for processing

            // Verify Invoice Exists in DB
            $invoice = Invoice::where('client_id', $client->id)->latest()->first();
            $this->assertNotNull($invoice, 'Invoice should be generated in DB');
            $invoiceId = $invoice->id;

            // =================================================================
            // PHASE 5: CLIENT PAYMENT (Client Portal)
            // =================================================================

            $clientBrowser->visit('/portal/invoices')
                ->waitForText('Invoices')
                // Wait for the invoice row
                ->waitForText(number_format($invoice->total, 2)); 
            
            // Click invoice
            $clientBrowser->clickLink('View Details') // Assuming link text
                ->waitForText('Invoice #');
                
            // =================================================================
            // PHASE 6: ADMIN MARK PAID (Admin)
            // =================================================================
            // Since Admin UI for recording payment is currently unavailable, we simulate it
            
            $invoice->fresh();
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
            
            // Verify Client sees Paid
            $clientBrowser->refresh()
                ->waitForText('Paid');
        });
    }
}
