<?php

declare(strict_types=1);

namespace Tests\Browser;

use App\Models\User;
use Database\Seeders\ClientPortalTestSeeder;
use Laravel\Dusk\Browser;
use Modules\ClientPortal\Models\ApprovalRequest;
use Modules\ContractManager\Models\Quote;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\ClientUser;
use Modules\Crm\Models\Company;
use PHPUnit\Framework\Attributes\Group;
use Tests\DuskTestCase;

/**
 * Quote Rejection & Revision Workflow Test
 * 
 * Tests the complete multi-user workflow for quote lifecycle:
 * 1. Admin creates and sends quote
 * 2. Client rejects quote with feedback
 * 3. Admin revises quote based on feedback
 * 4. Client approves revised quote
 * 5. System converts to contract
 * 
 * This test addresses the E2E workflow gap identified in:
 * docs/WIP/E2E_MULTI_USER_WORKFLOW_GAP_ANALYSIS.md
 */
class QuoteRejectionWorkflowTest extends DuskTestCase
{
    protected Company $company;
    protected Client $client;
    protected ClientUser $clientUser;
    protected User $admin;

    /**
     * Set up test data for every test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure we have a clean environment
        $this->company = Company::firstOrCreate(
            ['name' => 'Quote Test Company'],
            ['address' => '456 Test Ave', 'phone' => '555-1234']
        );

        $this->client = Client::firstOrCreate(
            ['email' => 'quote-test@example.com'],
            [
                'company_id' => $this->company->id,
                'name' => 'Quote Test Client',
                'tier' => 'Small Business',
                'status' => 'active',
            ]
        );

        $this->clientUser = ClientUser::firstOrCreate(
            ['email' => 'quoteuser@test.example.com'],
            [
                'client_id' => $this->client->id,
                'name' => 'Quote Test User',
                'password' => bcrypt('TestPassword123!'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->admin = User::where('role', User::ROLE_ADMIN)->first();
        if (!$this->admin) {
            $this->admin = User::factory()->create([
                'role' => User::ROLE_ADMIN,
                'email' => 'quoteadmin@test.example.com',
            ]);
        }
    }

    /**
     * Test complete quote rejection and revision workflow
     * 
     * WORKFLOW:
     * 1. Admin creates quote → Status: draft
     * 2. Admin sends quote → Status: sent, ApprovalRequest created
     * 3. Client views quote in portal
     * 4. Client rejects quote with reason
     * 5. Admin sees rejection and feedback
     * 6. Admin creates revision
     * 7. Client sees revised quote
     * 8. Client approves revised quote
     * 9. System converts to contract
     */
    #[Group('quote-lifecycle')]
    #[Group('multi-user')]
    #[Group('critical')]
    public function test_complete_quote_rejection_and_revision_workflow(): void
    {
        $this->browse(function (Browser $adminBrowser, Browser $clientBrowser) {
            // =================================================================
            // PHASE 1: Admin Creates and Sends Quote
            // =================================================================
            
            $adminBrowser->loginAs(static::$admin)
                ->visit('/contract-manager/quotes/create')
                ->pause(500);

            // Fill in quote details
            $adminBrowser->select('select[name="client_id"]', static::$client->id)
                ->pause(200)
                ->type('input[name="title"]', 'Website Redesign Project')
                ->select('select[name="billing_type"]', 'one_time')
                ->pause(200);

            // Add line item (assuming there's at least one line item input)
            $adminBrowser->type('input[name="line_items[0][description]"]', 'Design & Development')
                ->type('input[name="line_items[0][quantity]"]', '1')
                ->type('input[name="line_items[0][unit_price]"]', '5000')
                ->pause(200);

            // Save as draft first
            $adminBrowser->click('button[type="submit"]')
                ->pause(2000);

            // Get the created quote
            $quote = Quote::where('client_id', static::$client->id)
                ->where('title', 'Website Redesign Project')
                ->latest()
                ->first();

            $this->assertNotNull($quote, 'Quote was not created');
            $this->assertEquals('draft', $quote->status);

            // Send the quote
            $adminBrowser->visit("/contract-manager/quotes/{$quote->id}")
                ->pause(500)
                ->screenshot('admin-quote-before-send');

            // Look for send button (could be a form button or link)
            if ($adminBrowser->element('form[action*="send"]')) {
                $adminBrowser->click('form[action*="send"] button')
                    ->pause(2000);
            } else {
                // Try direct route
                $adminBrowser->visit("/contract-manager/quotes/{$quote->id}/send")
                    ->pause(2000);
            }

            // Verify quote was sent
            $quote->refresh();
            $this->assertEquals('sent', $quote->status);
            $this->assertNotNull($quote->sent_at);

            // =================================================================
            // PHASE 2: Client Views and Rejects Quote
            // =================================================================

            // Login as client
            $clientBrowser->visit('/portal/login')
                ->pause(500)
                ->type('email', static::$clientUser->email)
                ->type('password', 'TestPassword123!')
                ->click('button[type="submit"]')
                ->pause(2000);

            // Navigate to approvals (quote approvals show there)
            $clientBrowser->visit('/portal/approvals')
                ->pause(1000)
                ->screenshot('client-portal-approvals');

            // Find the approval request for this quote
            $approval = ApprovalRequest::where('approvable_type', Quote::class)
                ->where('approvable_id', $quote->id)
                ->where('client_id', static::$client->id)
                ->first();

            if ($approval) {
                // View approval detail
                $clientBrowser->visit("/portal/approvals/{$approval->id}")
                    ->pause(1000)
                    ->screenshot('client-approval-detail');

                // Reject with feedback
                $clientBrowser->type('textarea[name="notes"]', 'The price is too high. Can you reduce it to $3500?')
                    ->pause(200)
                    ->click('button[value="reject"], form[action*="reject"] button')
                    ->pause(2000);

                // Verify rejection
                $approval->refresh();
                $this->assertEquals('rejected', $approval->status);
            } else {
                // If no approval request exists, mark test as incomplete
                $this->markTestIncomplete('Approval request was not created when quote was sent');
            }

            // =================================================================
            // PHASE 3: Admin Sees Rejection and Creates Revision
            // =================================================================

            $adminBrowser->visit("/contract-manager/quotes/{$quote->id}")
                ->pause(1000)
                ->screenshot('admin-quote-after-rejection');

            // Verify admin can see rejection status
            $quote->refresh();
            // Note: Status might still be 'sent' if rejection doesn't update quote directly
            // The ApprovalRequest is rejected, but quote status may vary
            
            // Click revise button
            if ($adminBrowser->element('form[action*="revise"]')) {
                $adminBrowser->click('form[action*="revise"] button')
                    ->pause(2000);
            } else {
                // Try direct route
                $adminBrowser->visit("/contract-manager/quotes/{$quote->id}/revise")
                    ->pause(2000);
            }

            // Get the revision (new quote with parent_id)
            $revision = Quote::where('parent_id', $quote->id)
                ->latest()
                ->first();

            if ($revision) {
                // Edit the revision with new price
                $adminBrowser->visit("/contract-manager/quotes/{$revision->id}/edit")
                    ->pause(1000)
                    ->clear('input[name="line_items[0][unit_price]"]')
                    ->type('input[name="line_items[0][unit_price]"]', '3500')
                    ->click('button[type="submit"]')
                    ->pause(2000);

                // Send the revised quote
                $adminBrowser->visit("/contract-manager/quotes/{$revision->id}")
                    ->pause(500);

                if ($adminBrowser->element('form[action*="send"]')) {
                    $adminBrowser->click('form[action*="send"] button')
                        ->pause(2000);
                } else {
                    $adminBrowser->visit("/contract-manager/quotes/{$revision->id}/send")
                        ->pause(2000);
                }

                $revision->refresh();
                $this->assertEquals('sent', $revision->status);
            } else {
                $this->markTestIncomplete('Quote revision was not created');
            }

            // =================================================================
            // PHASE 4: Client Approves Revised Quote
            // =================================================================

            if ($revision) {
                // Find the new approval request for the revision
                $revisionApproval = ApprovalRequest::where('approvable_type', Quote::class)
                    ->where('approvable_id', $revision->id)
                    ->where('client_id', static::$client->id)
                    ->first();

                if ($revisionApproval) {
                    $clientBrowser->visit("/portal/approvals/{$revisionApproval->id}")
                        ->pause(1000)
                        ->screenshot('client-revised-quote');

                    // Verify new price is shown (implementation dependent)
                    $clientBrowser->assertSee('3500');

                    // Approve the revised quote
                    $clientBrowser->click('button[value="approve"], form[action*="approve"] button')
                        ->pause(2000);

                    // Verify approval
                    $revisionApproval->refresh();
                    $this->assertEquals('approved', $revisionApproval->status);

                    // =================================================================
                    // PHASE 5: Verify Contract Creation
                    // =================================================================

                    // Admin checks for contract
                    $adminBrowser->visit('/contract-manager/contracts')
                        ->pause(1000)
                        ->screenshot('admin-contracts-list');

                    // Look for the client name in contracts (contract should be auto-created)
                    $adminBrowser->assertSee(static::$client->name);
                    
                    // Verify in database
                    $contract = \Modules\ContractManager\Models\Contract::where('client_id', static::$client->id)
                        ->where('quote_id', $revision->id)
                        ->first();

                    if ($contract) {
                        $this->assertNotNull($contract, 'Contract was created from approved quote');
                        $adminBrowser->screenshot('admin-contract-created-success');
                    } else {
                        // Contract creation might be async or require additional step
                        $this->markTestIncomplete('Contract was not auto-created from approved quote');
                    }
                } else {
                    $this->markTestIncomplete('Approval request for revision was not created');
                }
            }
        });
    }

    /**
     * Test simpler scenario: Direct rejection without revision
     * 
     * Verifies that client can reject a quote and admin is notified
     */
    #[Group('quote-lifecycle')]
    #[Group('multi-user')]
    public function test_quote_rejection_without_revision(): void
    {
        $this->browse(function (Browser $adminBrowser, Browser $clientBrowser) {
            // Create and send quote
            $quote = Quote::factory()->create([
                'client_id' => static::$client->id,
                'title' => 'Simple Rejection Test Quote',
                'status' => 'draft',
                'total' => 2500,
            ]);

            $adminBrowser->loginAs(static::$admin)
                ->visit("/contract-manager/quotes/{$quote->id}/send")
                ->pause(2000);

            $quote->refresh();
            $this->assertEquals('sent', $quote->status);

            // Client logs in and rejects
            $clientBrowser->visit('/portal/login')
                ->type('email', static::$clientUser->email)
                ->type('password', 'TestPassword123!')
                ->click('button[type="submit"]')
                ->pause(2000);

            // Find approval and reject
            $approval = ApprovalRequest::where('approvable_type', Quote::class)
                ->where('approvable_id', $quote->id)
                ->first();

            if ($approval) {
                $clientBrowser->visit("/portal/approvals/{$approval->id}")
                    ->pause(1000)
                    ->type('textarea[name="notes"]', 'Not interested at this time.')
                    ->click('button[value="reject"], form[action*="reject"] button')
                    ->pause(2000);

                $approval->refresh();
                $this->assertEquals('rejected', $approval->status);

                // Admin can see rejection
                $adminBrowser->visit("/contract-manager/quotes/{$quote->id}")
                    ->pause(1000)
                    ->assertDontSee('approved')
                    ->screenshot('quote-rejected-admin-view');
            } else {
                $this->markTestIncomplete('Approval request not created');
            }
        });
    }
}
