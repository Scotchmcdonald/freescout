# Dusk Test Run Report & Resolution Strategy

**Date:** January 22, 2026
**Test Suite Status:** 10 Failures (out of ~100 tests)

## 1. Summary of Failures

### A. Environment Boot / configuration
- **Test:** `QuoteRejectionWorkflowTest`
- **Error:** `Cannot use object of type Illuminate\Support\Facades\Config as array`
- **Root Cause:** The test attempts to access the database (creating `Client`/`Company`) in `setUpBeforeClass()`. In Laravel Dusk, the application environment may not be fully booted at this static stage compared to the instance-level `setUp()`.

### B. Database Integrity (Missing Data)
- **Test:** `MultiUserWorkflowsTest` (`client_portal_invoice_viewing`, `automatic_invoice_flow`, etc.)
- **Error:** `SQLSTATE[HY000]: General error: 1364 Field 'company_id' doesn't have a default value`
- **Root Cause:** A recent migration added `company_id` to the `pib_invoices` table. The test factories (`InvoiceFactory`) and event listeners (`BillingTemplateDueListener`) were not updated to populate this required field.

### C. UI Interaction Instability
- **Test:** `ManualTestingPlanTest`, `MultiUserWorkflowsTest`
- **Error:** `NoSuchElementException`, `InvalidElementStateException`, `JavascriptErrorException`
- **Root Cause:** Tests are attempting to interact with UI elements (clicks, selects) before they are fully rendered or interactive. This violates the "State-Aware" principle of our UX Style Guide and leads to flaky tests.

## 2. General Resolution Strategy

### Phase 1: Fix Core Data & Environment (High Priority)
1.  **Fix Factories & Listeners:** Update `InvoiceFactory` and `BillingTemplateDueListener` to explicitly generate/fetch a `company_id`. This resolves the `QueryException`s.
2.  **Fix Lifecycle Methods:** Refactor `QuoteRejectionWorkflowTest` to move data setup from `setUpBeforeClass` to `setUp`. This resolves the `Config` error.

### Phase 2: Stabilize UI Tests
1.  **Implement "Wait" Logic:** Update failing tests to use `->waitFor('@selector')` or explicit pauses before interaction.
2.  **Ensure Interactability:** For complex components (like Selectize dropdowns), ensure elements are visible and not covered by animations before clicking.

### Phase 3: Structural Refactoring (Long Term)
1.  **Consolidate Migrations:** Execute the [Module Database Refactor Plan](docs/WIP/MODULE_DATABASE_REFACTOR_PLAN.md) to merge "add column" migrations into their base tables. This ensures new developer environments don't face "missing default" issues because the schema is clear from the start.

## 3. Execution Loop
We will follow this cycle to reach 100% green tests:
1.  **Apply Fix**: Edit code/test.
2.  **Reset DB**: `php artisan db:wipe && php artisan migrate && php artisan module:migrate`.
3.  **Run Target Test**: `php artisan dusk tests/Browser/SpecificTest.php`.
4.  **Repeat**.
