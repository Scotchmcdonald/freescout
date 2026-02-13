# Test Implementation Checklist

## Pre-Execution Validation

### Database & Models
- [ ] `BillingTemplate` model exists with factory
- [ ] `Contract` model exists with factory
- [ ] `Invoice` model exists with factory
- [ ] `BillingItem` or line items table exists
- [ ] `CreditLedger` model/table exists
- [ ] `Project` model exists with milestones support
- [ ] `Milestone` model/table exists
- [ ] `SoftwareSubscription` model exists
- [ ] `SoftwareAssignment` table with atomic counter column
- [ ] `Ticket` model exists (Helpdesk)
- [ ] `EmailMigration` model exists
- [ ] Factories for all models above

### Module Verification
- [ ] `Modules/EmailMigration` directory exists
- [ ] Email migration routes registered
- [ ] Helpdesk/Support module enabled
- [ ] Software Subscriptions module enabled
- [ ] All module migrations run

### Blade Templates & Test Selectors

#### Billing Section
- [ ] `/contracts/create` - Add selectors: `@client-select`, `@billing-template-select`, `@price-override-field`
- [ ] `/contracts/quotes/create` - Add: `@quote-type-select`, `@add-line-item-button`
- [ ] `/billing/invoices` - Add: `@invoice-row`, `@view-latest-invoice`
- [ ] `/billing/credit-ledger` - Add: `@credit-balance`, `@credit-history`
- [ ] `/billing/payments/create` - Add: `@payment-type`, `@amount`

#### Projects Section
- [ ] `/projects/create` - Add: `@billing-type`, `@add-milestone-button`
- [ ] `/projects/{id}` - Add: `@milestone-{n}-complete-button`, `@completion-notes`

#### Service/Assets Section
- [ ] `/portal/assets/request` - Add: `@asset-name`, `@asset-type`, `@submit-request-button`
- [ ] `/assets/requests` - Add: `@approve-asset-request`, `@asset-requests-table`
- [ ] Entitlement UI - Add limit warnings/error messages

#### Software Subscriptions
- [ ] `/modules/software-subscriptions/create` - Add: `@software-select`, `@quantity`
- [ ] Software assignment UI - Add: `@assign-license-button`, `@user-select`
- [ ] Capacity display - Add: `@seats-assigned`, `@seats-available`

#### Helpdesk Section
- [ ] `/portal/support` - Add: `@new-ticket-button`, `@submit-ticket-button`
- [ ] `/helpdesk/tickets` - Add: `@reply-message`, `@send-reply-button`
- [ ] Ticket detail - Add: `@billable-checkbox`, `@billable-hours`, `@billable-rate`
- [ ] Rating UI - Add: `@rating-5-stars`, `@feedback-comment`
- [ ] Timeline - Add: `@timeline-item-{n}`, `@timeline-timestamps`

#### Email Migration Section
- [ ] `/modules/email-migration/create` - Wizard UI with step navigation
- [ ] Add: `@source-provider`, `@destination-provider`, `@verify-connection-button`
- [ ] Add: `@next-button`, `@save-draft-button`, `@start-migration-button`
- [ ] Progress page - Add: `@progress-bar`, `@mailbox-status-list`

### Application Logic Requirements

#### Billing Features
- [ ] Plan price override logic in Contract model
- [ ] Ticket billing integration (billable flag, hours, rate calculation)
- [ ] Hardware procurement event listener (AssetProcured → Invoice)
- [ ] Project milestone invoice generation on completion
- [ ] Rent-to-own capping logic (sum payments ≤ purchase price)
- [ ] Credit ledger automatic application to invoices
- [ ] FIFO credit depletion logic

#### Service Features
- [ ] Entitlement limits by plan (Silver: 1, Gold: 5, Platinum: unlimited)
- [ ] Per-user entitlement calculation
- [ ] Software license atomic counter (prevent oversale)
- [ ] Race condition prevention in license assignment
- [ ] Asset ownership transfer on rent-to-own completion

#### Client Experience Features
- [ ] Email migration wizard state persistence
- [ ] Connection verification for migration providers
- [ ] Ticket file attachment support
- [ ] Ticket email notifications (creation, reply, resolution)
- [ ] Ticket reopening workflow
- [ ] Client self-service ticket closure
- [ ] Ticket rating system

## Test Execution Order

### Phase 1: Foundation Tests (Run First)
1. `PlanOverridesTest` - Basic billing override
2. `EntitlementEnforcementTest` - Service limits
3. `AssetCreditLedgerTest` - Credit system

### Phase 2: Integration Tests
4. `TicketBillingTest` - Helpdesk → Billing
5. `HardwareProcurementTest` - Quotes → Invoices
6. `ProjectMilestonesTest` - Project → Billing

### Phase 3: Complex Features
7. `RentToOwnTest` - Multi-month calculations
8. `SoftwareAssignmentTest` - Atomic counter (enhanced)

### Phase 4: User Experience
9. `MigrationWizardTest` - Multi-step wizard
10. `ClientTicketInteractionTest` - Full lifecycle

## Common Issues & Solutions

### Selector Not Found
**Error:** `Element not found: @selector-name`  
**Solution:** Add `dusk="selector-name"` to HTML element

### Multi-Browser Timeout
**Error:** Browser instances don't sync  
**Solution:** Add `waitForText()` or `waitFor()` before assertions

### Factory Missing
**Error:** `Call to undefined method create()`  
**Solution:** Create factory file: `php artisan make:factory ModelNameFactory`

### Module Not Loaded
**Error:** Route not found  
**Solution:** Check `config/modules.php`, run `php artisan module:migrate`

### Database State Issues
**Error:** Duplicate entries or stale data  
**Solution:** Ensure `RefreshDatabase` trait or database reset in setUp()

## Manual Verification Steps

After running automated tests:

1. **Visual Inspection**
   - Open browser at `http://localhost/dusk/dashboard`
   - Verify UI elements match test selectors

2. **Database Verification**
   ```sql
   -- Check invoices created
   SELECT * FROM invoices WHERE client_id = ? ORDER BY created_at DESC;
   
   -- Check credit ledger
   SELECT * FROM credit_ledger WHERE client_id = ?;
   
   -- Check software assignments
   SELECT COUNT(*) FROM software_assignments GROUP BY client_subscription_id;
   ```

3. **Log Review**
   ```bash
   tail -f storage/logs/laravel.log
   # Look for errors during test execution
   ```

## Test Output Analysis

### Success Indicators
- ✅ All assertions pass
- ✅ No JavaScript errors in browser console
- ✅ Database records created correctly
- ✅ Email notifications sent (if applicable)

### Failure Patterns
- ❌ Selector timeout → Missing or incorrect `dusk` attribute
- ❌ Assertion failure → Business logic not implemented
- ❌ Database error → Missing migration or factory
- ❌ Module not found → Module disabled or routes not registered

## Next Steps After First Run

1. Document actual failures in `reports/coverage_gap_tests_first_run.txt`
2. Create GitHub issues for missing features/selectors
3. Update implementation priority based on test results
4. Refine tests based on actual UI structure
5. Add missing factories and seeders
6. Iterate until 90%+ pass rate achieved
