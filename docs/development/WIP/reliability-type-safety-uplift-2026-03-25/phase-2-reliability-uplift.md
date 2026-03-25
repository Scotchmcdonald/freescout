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
- [x] Add branch-focused tests for service guardrails:
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

## Iteration 1 Progress (2026-03-25 Dashboard Widgets)
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

## Iteration 2 Progress (2026-03-25 Service Guardrails)
- Added service integration test suites covering all three Phase 2 priority services:
  - `tests/Integration/Services/AuditLogServiceTest.php` (20 tests)
    - logSensitiveOperation (w/o subject, w/o causer, default log name)
    - queryLogs (by log_name, causer_id, subject_type, subject_id, date_range, description_like, combined filters)
    - getSubjectAuditTrail (respects limit, latest-first ordering)
    - enrichProperties (adds ip_address, user_agent, timestamp)
  - `tests/Integration/Services/MetricsServiceTest.php` (18 tests)
    - trackEvent (info/warning/error levels, empty context, timestamp inclusion)
    - trackInvoiceGeneration (success + slow threshold boundary, near-threshold no-warning)
    - trackPaymentProcessed (success/failure/multiple gateways)
    - trackApiCall (all status codes 200/201/400/404/500/503, slow duration 3000ms threshold, level prioritization)
    - trackSecurityEvent (with/without context)
  - `tests/Integration/Services/EntitlementEngineServiceTest.php` (16 tests)
    - registerResolver/hasResolver/getRegisteredProductTypes (order preservation, empty state)
    - resolve (correct resolver invocation, unregistered throws, product type routing)
    - overwrite behavior (replacement without duplication)
- All tests passing:
  - Total: 280 tests / 616 assertions
  - Duration: 8.68s (parallel, 10 processes)
- Mutation safety re-validated after service tests:
  - Tier 2 MSI: 100
  - Covered MSI: 100
  - Killed: 1666 / 2743
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
