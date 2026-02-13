# Remaining Issues & Resolution Plan

## Current Status
- **Core DB Refactor**: Completed and Verified.
- **Module DB Refactor**: Planned but not executed.
- **Test Suite**: 10 failures in `dusk` suite.
  - `QuoteRejectionWorkflowTest`: `Cannot use object of type Illuminate\Support\Facades\Config as array` (Environment/Boot issue).
  - `MultiUserWorkflowsTest`: `Field 'company_id' doesn't have a default value` (Database Integrity issue).

## Resolution Process (The Loop)
1.  **Fix Database Integrity (PIB Module)**
    - The `pib_invoices` table requires `company_id` (added by `2026_01_16_100001_add_company_id_to_pib_tables.php`).
    - **Fix Code**: Update `Modules/PIB/Listeners/BillingTemplateDueListener.php` to fetch and set `company_id`.
    - **Fix Tests**: Update `tests/Browser/MultiUserWorkflowsTest.php` to set `company_id`.
    - **Refactor Migration**: Merge `add_company_id_to_pib_tables.php` into `create_pib_tables.php` to enforce this constraint cleanly at creation.

2.  **Fix Environment Boot (Quote Rejection)**
    - The error suggests `setUpBeforeClass` is accessing `Config` before the application is booted.
    - **Fix**: Move `client` and `company` creation from `setUpBeforeClass` to `setUp` (instance method).

3.  **Fix UI/Interaction Failures (ManualTestingPlan & MultiUser)**
    - *UX Style Guide Alignment*: Tests typically fail because they try to interact before the UI is ready (Violation of "State-Aware").
    - **Strategy**: Implement "Stability Checks" before interaction.
    - **Fixes**:
        - `MultiUserWorkflowsTest` / `ManualTestingPlanTest`: Add `->waitFor('@selector')` before clicks.
        - `test_recurring_quote_to_billing_template`: The `InvalidElementStateException` likely occurs when trying to click a Selectize/Dropdown that is obscured or animating. Use `->script("arguments[0].click();", $element)` or wait for animation.
        - **JS Errors**: These usually stem from data issues (missing props). Fix the Database Integrity (#1) first, as missing `company_id` likely crashes Vue components expecting that prop.

4.  **Execute Module Refactoring**
    - Follow the `docs/WIP/MODULE_DATABASE_REFACTOR_PLAN.md`.
    - Merging migrations helps prevent "missing default value" errors by making the schema clear from day one.

5.  **Verification Loop**
    ```bash
    # 1. Apply code fixes
    # 2. Reset DB
    php artisan db:wipe && php artisan migrate && php artisan module:migrate
    # 3. Run specific failing tests
    php artisan dusk tests/Browser/MultiUserWorkflowsTest.php
    php artisan dusk tests/Browser/QuoteRejectionWorkflowTest.php
    # 4. If pass, run full suite
    php artisan dusk > reports/dusk_full_run_v2.txt 2>&1
    ```

## Immediate Action Items
- [ ] Fix `tests/Browser/QuoteRejectionWorkflowTest.php`: Refactor `setUpBeforeClass`.
- [ ] Fix `Modules/PIB/Listeners/BillingTemplateDueListener.php`: Add `company_id`.
- [ ] Fix `tests/Browser/MultiUserWorkflowsTest.php`: Add `company_id` and UI Waits.

