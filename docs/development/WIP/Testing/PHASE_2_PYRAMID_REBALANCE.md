# Phase 2: Pyramid Rebalance

## Objective

Shift the suite toward a healthier mix of unit, feature, integration, and browser coverage.

## Current Baseline

**PHASE 2 IN PROGRESS — 2026-03-19**

- unit file share: 181 / 585 = 30.9%
- integration file share: 167 / 585 = 28.5%
- browser file share: 57 / 585 = 9.7%
- unique unit files still referencing `RefreshDatabase`: 2
- full suite status after current batch: 5468 passed, 2 skipped, 0 failed (`php artisan test --parallel --processes=10`)

### Completed In This Batch

- Added Integration binding to `Modules/SoftwareSubscriptions/Tests/Pest.php`
- Moved DB-backed SoftwareSubscriptions tests from Unit to Integration:
	- `OffboardingTicketSystemPestTest.php`
	- `VendorCostReportPestTest.php`
- Merged SoftwareDiscovery persistence coverage into existing Integration suite and removed the redundant Unit copy
- Removed Unit duplicates already covered by Integration:
	- `OffboardingTicketListenerPestTest.php`
	- `SubscriptionCounterServicePestTest.php`
- Rewrote these Unit suites to become true pure-unit tests with no DB dependency:
	- `ClientSoftwareSubscriptionPestTest.php`
	- `SoftwareProductPestTest.php`
	- `DiscoveryEventsPestTest.php`
- Reclassified root persistence-heavy test to Integration:
	- `tests/Unit/Jobs/JobFailureRecoveryTest.php` -> `tests/Integration/Jobs/JobFailureRecoveryTest.php`
- Added new pure-unit arithmetic coverage in ContractManager:
	- `Modules/ContractManager/Tests/Unit/QuoteServiceMathPestTest.php`
- Added new pure-unit math/report-ordering coverage in PIB:
	- `Modules/PIB/Tests/Unit/BillingAnalysisServiceMathPestTest.php`
- Extracted pure helper methods from `Modules/PIB/Services/BillingAnalysisService.php` so percentage-change and unusual-variance logic can be tested without DB setup
- Removed duplicate low-signal browser tests whose behavior is already covered by Integration vendor-cost report tests:
	- `Modules/SoftwareSubscriptions/Tests/Browser/VendorCostReportPestTest.php`
	- `Modules/SoftwareSubscriptions/Tests/Browser/VendorCostReportBrowserTest.php`
- Added new pure-unit policy coverage in CaseManager:
	- `Modules/CaseManager/Tests/Unit/Services/FernBudgetServicePolicyTest.php`
- Added new pure-unit audience classification/flag policy coverage in CaseManager:
	- `Modules/CaseManager/Tests/Unit/Services/AudienceTargetingPolicyTest.php`
- Removed duplicate low-signal browser page-load suites already covered by Feature tests:
	- `tests/Browser/Admin/SystemPagesPestTest.php`
	- `tests/Browser/Admin/SettingsPagesPestTest.php`
- Migrated additional low-signal browser admin suites to Feature coverage:
	- `tests/Browser/Admin/Action1AuditPestTest.php` -> `tests/Feature/Admin/Action1AuditPestTest.php`
	- `tests/Browser/Admin/ReconciliationPestTest.php` -> `tests/Feature/Admin/ReconciliationPestTest.php`
- Removed redundant browser smoke assertion with no unique behavior:
	- `tests/Browser/SessionFlashPestTest.php`
- Refactored `Modules/CaseManager/Services/AudienceTargetingService.php` to expose pure audience policy seams while preserving integration behavior

### Remaining Gap To Exit Criteria

- Unit share is still far below the 55% target, so the next batches should focus on adding pure unit coverage in ContractManager, PIB, and CaseManager rather than only moving files out of Unit.
- Browser share remains 57 / 585 = 9.7%, so later Phase 2 work should consolidate or replace browser-only coverage where lower layers can carry the behavior.

## Exit Criteria

- unit tests reach at least 55% of the total suite
- browser tests drop to 5% or less of the total suite
- unit-scope `RefreshDatabase` usage is reduced to 5 allowlisted files or fewer
- cross-module DB usage in unit scope remains at 0

## Implementation Plan

1. Identify DB-using tests mislabeled as unit tests.
2. Move persistence-heavy tests to Integration.
3. Extract pure logic from integration-heavy services into new Unit tests.
4. Tighten the allowlist after each migration batch.

## High-Value Targets

- `Modules/SoftwareSubscriptions/Tests/Unit/OffboardingTicketSystemTest.php`
- `Modules/SoftwareSubscriptions/Tests/Unit/OffboardingTicketSystemPestTest.php`
- `Modules/SoftwareSubscriptions/Tests/Unit/VendorCostReportTest.php`
- `Modules/SoftwareSubscriptions/Tests/Unit/VendorCostReportPestTest.php`
- `Modules/SoftwareSubscriptions/Tests/Unit/SoftwareDiscoveryPestTest.php`

Critical inversion modules:
- ContractManager
- PIB
- CaseManager

## Autonomous Execution Guidance

Autonomy level: medium-high.

The agent should proceed autonomously when:
- moving tests between layers without changing behavior
- introducing pure unit tests for extracted logic
- shrinking the allowlist when migrated files no longer need DB state

The agent should pause only if:
- a service boundary must be redesigned to make unit testing possible
- layer reclassification changes the intended coverage contract with another module

## Safe Command Patterns

```bash
grep -RIl "RefreshDatabase" tests/Unit Modules/*/Tests/Unit --include='*.php'
find Modules/SoftwareSubscriptions/Tests/Unit -name '*Test.php'
php artisan test Modules/SoftwareSubscriptions/Tests/Unit
php artisan test Modules/SoftwareSubscriptions/Tests/Integration
```

## Effective LLM Prompt

```text
You are executing Phase 2 of the testing roadmap: Pyramid Rebalance.

Work autonomously where possible. Your job is to identify unit tests that use the database, move them to Integration where appropriate, and add pure unit coverage for any extracted logic. Shrink the RefreshDatabase allowlist as you go.

Constraints:
- do not weaken coverage
- preserve intent while moving layers
- validate touched files with focused php artisan test runs

Stop only if achieving unit isolation requires a meaningful production-code seam or architectural redesign. Otherwise continue until the selected batch is complete.
```
