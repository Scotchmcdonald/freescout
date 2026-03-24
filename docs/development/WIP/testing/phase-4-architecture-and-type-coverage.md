# Phase 4: Architecture And Type Coverage

Status: Completed (2026-03-24; local/targeted guard lanes green)
Duration: 4 to 7 days
Goal: Strengthen architectural safety nets and type-boundary verification to 2026 standards.

## Scope

- Expand architecture rules for strict dependency boundaries.
- Introduce and enforce type coverage metrics for critical domains.
- Align module boundary checks with maintainable guard patterns.

## Implementation Tasks

1. Architecture rule expansion
- Add stricter rules for critical namespaces (controllers, services, module boundaries).
- Introduce selective allowlists only where needed, with expiry and owner.
- Keep rules deterministic and fast for CI lane use.

2. Type coverage rollout
- Add type-coverage tooling for PHP code paths (critical services first).
- Enforce minimum threshold in CI for selected domains.
- Start with financial and billing services, then expand.

3. Guard maintenance quality
- Replace broad regex scans with focused rule sets where feasible.
- Ensure architecture tests remain readable and actionable.

## Acceptance Criteria

- Architecture lane includes strict critical-boundary checks and stays fast.
- Type-coverage threshold enforced for at least one critical domain group.
- No unowned rule exceptions.

## Risks

- Risk: over-strict static rules can block legitimate refactors.
- Mitigation: introduce rule-change process with short RFC + owner approval.

## Exit Gate

- 100 percent pass on architecture/type lanes for 10 consecutive runs.

## Kickoff Plan (Prepared)

1. Architecture boundary tightening (wave 1)
- Inventory current architecture tests and module isolation guards.
- Add focused, deterministic boundary checks for critical namespaces:
	- app/Http/Controllers must depend on contracts/services, not cross-module persistence internals.
	- app/Services and Modules/*/Services must avoid direct cross-module data writes unless explicitly allowed.
	- Listener and observer boundaries remain explicit and measurable.
- Add temporary exceptions only with owner, issue, and expires metadata.

2. Type coverage baseline (wave 2)
- Establish a type coverage baseline for a critical domain group first:
	- Billing and payment service paths (app/Services and Modules/PIB + Modules/Payment services).
- Record baseline percentages and define ratcheting thresholds.
- Wire threshold checks into the architecture/type lane with clear failure messaging.

3. Guard maintainability and speed (wave 3)
- Refactor broad scans into smaller rule sets where possible.
- Keep guard output actionable with file-level context.
- Preserve lane speed budget by favoring targeted scans and minimizing redundant filesystem traversal.

## Immediate Next Actions

- Create a Phase 4 evidence log documenting:
	- current architecture lane checks,
	- selected critical domain set for type coverage,
	- initial baseline metrics and target threshold.
- Implement first architecture boundary rule batch.
- Run focused architecture checks and capture pass/fail snapshots in reports.

## Wave 1 Progress Snapshot

- Added tests/Architecture/CriticalNamespaceBoundaryGuardTest.php to freeze critical namespace coupling while remediation continues.
- Baselines captured:
	- app controllers importing module models: max 5
	- app controllers importing module services: max 4
	- app services importing module models: max 0
	- module services importing app controllers: max 0
- Baseline metadata includes owner, issue, and expiry date for governance.

## Wave 2 Progress Snapshot

- Added tests/Architecture/BillingPaymentTypeCoverageGuardTest.php.
- Enforced type-coverage threshold for critical billing/payment service domain via strict-types coverage metric.
- Domain scope:
	- Modules/PIB/Services
	- Modules/Payment/Services
- Threshold configured:
	- min_strict_types_percent: 100.0
	- min_files_scanned: 10

## Wave 3 Progress Snapshot

- Updated scripts/ci/check-architecture-compliance.sh to run focused Phase 4 architecture guards:
	- tests/Architecture/CriticalNamespaceBoundaryGuardTest.php
	- tests/Architecture/BillingPaymentTypeCoverageGuardTest.php
- Guard integration uses parallel test execution and preserves fail-fast CI behavior.

## Closeout Summary

- Wave 1 complete: critical namespace boundary baseline guard added and enforced.
- Wave 2 complete: type-coverage threshold guard added for critical billing/payment services.
- Wave 3 complete: architecture compliance CI script now executes phase-4 guard subset using explicit single-file invocations for reliable paratest compatibility.

### Acceptance Evidence

- Architecture guard subset run (parallel) passed:
	- tests/Architecture/CriticalNamespaceBoundaryGuardTest.php
	- tests/Architecture/BillingPaymentTypeCoverageGuardTest.php
- Full scripts/ci/check-architecture-compliance.sh run passed after integration.
- Post-fix targeted rerun passed for both new guard files with reports written to reports/test-results-2026-03-24_01-04-44.log.
- No unowned exceptions were introduced; all new baselines include owner/issue/expires metadata.

### Evidence Files

- docs/development/WIP/testing/phase-4-wave-1-evidence.md
- docs/development/WIP/testing/phase-4-wave-2-3-evidence.md
