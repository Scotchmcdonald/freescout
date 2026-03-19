# Phase 3: Critical Coverage

## Objective

Add deep unit coverage for critical financial and decisioning services before mutation gating is enforced.

## Current Baseline

Critical services with unit-test presence:
- QuoteService: 0
- BillingTemplateService: 0
- BillingAnalysisService: 0
- VendorReconciliationService: 0
- LicenseDeploymentService: 0
- ProrationService: 3
- InvoiceGenerator: 0
- HelcimService: 1

Mutation artifacts: none found.
Coverage artifacts: none found.

## Exit Criteria

- all critical services have dedicated unit tests
- each critical service reaches at least 85% line coverage
- arithmetic-heavy services are ready for mutation targets above 80%

## Implementation Plan

1. Start with QuoteService and BillingAnalysisService.
2. Cover branch logic, arithmetic boundaries, exceptional paths, and invalid input handling.
3. Expand to BillingTemplateService, VendorReconciliationService, LicenseDeploymentService, and InvoiceGenerator.
4. Generate baseline coverage artifacts for the critical set.

## High-Value Targets

- `app/Services/QuoteService.php`
- `Modules/PIB/Services/BillingAnalysisService.php`
- `Modules/ContractManager/Services/BillingTemplateService.php`
- `Modules/SoftwareSubscriptions/Services/VendorReconciliationService.php`
- `Modules/SoftwareSubscriptions/Services/LicenseDeploymentService.php`
- `Modules/PIB/Services/InvoiceGenerator.php`

## Autonomous Execution Guidance

Autonomy level: medium-high.

The agent should proceed autonomously when:
- adding pure unit tests for deterministic service logic
- creating fixtures, builders, or data providers for branch coverage
- refactoring small seams to improve testability without changing behavior

The agent should pause only if:
- a service is so entangled with infrastructure that unit isolation would materially alter design
- the business rules are ambiguous or contradictory

## Safe Command Patterns

```bash
php artisan test --filter QuoteService
php artisan test --filter BillingAnalysisService
grep -RIn "class QuoteService\|class BillingAnalysisService" app Modules --include='*.php'
tail -n 40 reports/test-results-latest.log
```

## Effective LLM Prompt

```text
You are executing Phase 3 of the testing roadmap: Critical Coverage.

Work autonomously where possible. Add or expand unit test suites for critical financial and decisioning services, starting with QuoteService and BillingAnalysisService. Cover arithmetic boundaries, branch logic, invalid inputs, and domain invariants. Use focused php artisan test runs after each batch.

Do not stop at skeleton tests. Deliver meaningful branch coverage and report which services still lack adequate unit depth.

Pause only if business rules are unclear or the service cannot be isolated without a larger architectural decision.
```
