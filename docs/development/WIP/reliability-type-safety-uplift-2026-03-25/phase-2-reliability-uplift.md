# Phase 2 - Reliability Uplift to 90%

## Objective
Raise reliability by closing breadth gaps: increase coverage in business-critical low-coverage namespaces while preserving high mutation quality.

## Priority Focus Namespaces (from latest audit)
1. `app/Widgets/Dashboard`
2. `app/Services` (AuditLogService, EntitlementEngine, MetricsService)
3. `Modules/Action1/Services` (contract and failure-path coverage)

## Workstreams
### A. Coverage Breadth Expansion
- [x] Add feature/integration tests for dashboard widgets (happy + negative + role-based variants).
- [ ] Add branch-focused tests for service guardrails:
  - Null/invalid inputs
  - boundary thresholds
  - exception and rollback paths
- [ ] Add contract tests for Action1 service responses and error mapping.

### B. Assertion Depth Upgrade
- [ ] Replace shallow assertions (`status 200 only`) with semantic assertions:
  - domain state changes
  - emitted events
  - persisted side effects
  - response contract invariants
- [ ] Add explicit anti-regression assertions for known escaped-mutant patterns.

### C. Mutation Safety Preservation
- [x] Keep Tier 1 and Tier 2 MSI at >=95.
- [ ] Investigate any increase in escapedCount immediately; no deferred fixes.

## Iteration 1 Progress (2026-03-25)
- Added widget integration suite: `tests/Integration/Widgets/DashboardWidgetsTest.php`
  - 7 passing tests / 33 assertions
  - Covers role gating and core render branches for Admin/Agent/Finance/Reporter widgets
- Fixed reliability defects surfaced by tests:
  - `app/Widgets/Dashboard/FinanceDashboardWidget.php`
    - payment status filter now accepts `successful` (plus `completed`)
    - `number_format()` now casts `total_amount` to float to avoid TypeError
- Mutation guard validated after changes:
  - Tier 2 MSI: 100
  - Covered MSI: 100
  - Escaped: 0

## Reliability Milestones
- M1: Global executable coverage >=60%
- M2: Global executable coverage >=70%
- M3: Global executable coverage >=75% + MSI >=95 in both tiers
- M4: Reliability KPI reaches >=90

## Acceptance Criteria
- Coverage threshold for reliability model met.
- Mutation remains green at thresholds.
- Boundary-critical tests include failing-path coverage.
