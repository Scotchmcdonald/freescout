# Dusk Test Suite Review & Roadmap

**Date:** January 22, 2026
**Status:** Work In Progress

## 1. Executive Summary
The Browser Automation suite is well-structured for core workflows like "Quote-to-Cash" and "Software Licensing." However, a gap analysis against available modules reveals significant missing coverage for `EmailMigration` (a large application module) and several smaller administrative modules.

## 2. Module Coverage Matrix

| Module | UI Complexity | Primary Test Suite | Status |
| :--- | :--- | :--- | :--- |
| **Action1** | Low (Audit/Sync) | *None* | 🔴 Missing |
| **Alerts** | Medium (Dashboard) | *None* | 🔴 Missing |
| **AssetManagement** | High (CRUD, Assignment) | `AssetManagementTest` | 🟢 Covered |
| **ClientPortal** | High (Client-Facing) | `ClientPortalTest` | 🟢 Covered |
| **ContractManager** | High (Complex Workflow) | `MultiUserQuoteLifecycleTest` | 🟢 Covered |
| **Crm** | High (Core Data) | `CrmFeatureTest` | 🟢 Covered |
| **DevFeedback** | Low (Form) | *None* | ⚪ Excluded |
| **EmailMigration** | **Very High** (Wizards, Dashboards) | *None* | ⚪ Excluded |
| **GoogleAdmin** | Medium (Config/Audit) | *None* | 🔴 Missing |
| **PIB** | High (Billing) | `SalesToCashE2ETest` | 🟢 Covered |
| **Payment** | High (Transaction) | `PaymentProcessingE2ETest` | 🟡 Partial |
| **SoftwareSubscriptions** | High (Licensing) | `SoftwareSubscriptionTest` | 🟢 Covered |
| **WidgetRegistry** | Low (Internal) | `WidgetRegistryIntegrationTest` | 🟢 Covered |

## 3. Workflow Coverage Analysis

### A. Sales & Revenue (Lead -> Cash)
*   **Workflow**: CRM Lead -> Quote Creation (Admin) -> Quote Acceptance (Client Portal) -> Invoice Generation -> Payment.
*   **Coverage**: `SalesToCashE2ETest.php` covers this extensively.
*   **Gap**: Failed payments handling and refund workflows.

### B. Operations (Service Delivery)
*   **Workflow**: Asset Assignment, Software Provisioning.
*   **Coverage**: `AssetManagementTest` and `SoftwareSubscriptionTest` cover the individual actions.
*   **Gap**: **Cross-Module Integration**.
    *   *Scenario*: Assigning a Software License to a specific Asset (Laptop) and seeing that reflected in the Asset's history.
    *   *Scenario*: Decommissioning an Asset -> Auto-unassigning Software.

### C. Migration Services
*   **Workflow**: Email Migration Project Setup -> Execution -> Reporting.
*   **Coverage**: **Zero**.
*   **Gap**: The `EmailMigration` module is highly complex with wizards and dashboards but has no browser tests.

## 4. Prioritized Recommendations

### Priority 1: Administrative Coverage (System Health)
1.  **System Health & Alerts**:
    *   Create `SystemHealthTest.php` to cover `Alerts` and `GoogleAdmin` status pages.
    *   Verify `Action1` audit log visibility.
    *   **Goal**: Ensure admins can access critical monitoring dashboards and configuration pages without errors.

### Priority 2: Deepening Cross-Module Integration
1.  **Asset <-> Subscription Link**:
    *   Extend `SoftwareSubscriptionTest` to verify that assigning a license to an Asset *also* updates the Asset's "Installed Software" tab (if it exists).

### Priority 3: Edge Cases & Resilience
1.  **Payment Failure**: Add test cases for declined cards in `PaymentProcessingE2ETest`.
2.  **Quote Expiration**: Implementing the placeholder `test_quote_expiration` in `MultiUserQuoteLifecycleTest`.

## 5. Current Failures & Remediation
*See [docs/WIP/REMAINING_ISSUES_PLAN.md](docs/WIP/REMAINING_ISSUES_PLAN.md) for the active plan to fix 2026-01-22 test failures.*

## Appendix: Recent Improvements
*   **Software Subscriptions**: 
    *   The "Assignment Deletion" gap identified in `UX_IMPROVEMENT_PLAN.md` has been addressed. 
    *   Test case `test_can_delete_assignment` covers the UI interaction, confirmation dialog handling, and counter verification.
*   **Atomic Counters**: 
    *   Race condition verification exists (`test_atomic_counter_prevents_overallocation`), ensuring data integrity during rapid UI actions.
