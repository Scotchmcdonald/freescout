# Architecture Compliance Resolution Plan

**Date:** January 22, 2026
**Status:** Draft
**Context:** Automated Architecture Compliance Review

## 1. Executive Summary

A comprehensive architecture compliance check was performed using the CI script suite. While the core infrastructure (Atomic Counters, Rate Limiting, Event Inheritance) is compliant, two specific violations regarding module coupling and event safety were identified.

### Compliance Scorecard

| Check | Script | Result | Notes |
| :--- | :--- | :--- | :--- |
| **Atomic Counters** | `check-atomic-counters.sh` | ✅ PASS | Critical billing counters are safe. |
| **Event Inheritance** | `check-event-inheritance.sh` | ✅ PASS | Event sourcing compliance is good. |
| **Rate Limiting** | `check-rate-limiter-usage.sh` | ✅ PASS | API endpoints are protected. |
| **Module Isolation** | `check-cross-module-imports.sh` | ❌ FAIL | **1 Violation**: Direct model import across modules. |
| **Idempotency** | `check-listener-inheritance.sh` | ❌ FAIL | **1 Violation**: Listener missing base class. |

---

## 2. Detailed Findings & Resolution Strategy

### 2.1. Cross-Module Import Violation (Coupling)

**Severity:** High (Breaks Modular Monolith Isolation)

**Violation Location:**
`Modules/ContractManager/Listeners/CreateQuoteApprovalRequest.php`

**The Issue:**
This listener imports `Modules\ClientPortal\Models\ApprovalRequest` directly. 
```php
use Modules\ClientPortal\Models\ApprovalRequest; // ❌ Violation
```
Refactoring `ContractManager` should not break `ClientPortal`, and vice-versa. ContractManager knows too much about how approvals are stored in the ClientPortal module.

**Proposed Resolution:**
**Pattern:** Inversion of Control via Event Bus.

1.  **Current Flow:**
    `QuoteSentToClient` (Event) -> `CreateQuoteApprovalRequest` (Listener in CM) -> creates `ApprovalRequest` (Model in CP).

2.  **Target Flow (Option A - Preferred):**
    `QuoteSentToClient` (Event in CM) -> `CreateApprovalRequestForQuote` (Listener in **ClientPortal**).
    
    *Move the listener logic.* The `ClientPortal` module should be responsible for deciding *if* and *how* to create an approval request when it observes a Quote being sent. This creates a "Core Blindness" where ContractManager emits events but doesn't know who is listening.

**Action Items:**
- [ ] Move listener logic from `ContractManager` to a new listener in `ClientPortal`.
- [ ] Update `EventServiceProvider` in `ClientPortal` to listen for `Modules\ContractManager\Events\QuoteSentToClient`.
- [ ] Delete valid `CreateQuoteApprovalRequest` in `ContractManager`.

---

### 2.2. Listener Inheritance Violation (Reliability)

**Severity:** Medium (Risk of duplicate alerts)

**Violation Location:**
`Modules/Alerts/Listeners/InvoiceUnusualListener.php`

**The Issue:**
The class does not extend `App\Listeners\IdempotentListener`.
```php
class InvoiceUnusualListener implements ShouldQueue // ❌ Violation
```
In a distributed system, events may be delivered more than once. Without idempotency checks, we risk sending duplicate "Unusual Invoice" alerts to admins.

**Proposed Resolution:**
Extend the base idempotent listener.

**Action Items:**
- [ ] Update class definition: `class InvoiceUnusualListener extends IdempotentListener`
- [ ] Change `handle($event)` method to `handleIdempotent($event)`.
- [ ] Verify `InvoiceUnusual` event has a unique ID.
    - *Note:* The `InvoiceUnusual` event currently lacks `VersionedEvent` inheritance or a dedicated UUID. We may need to pass an `eventId` in its constructor or generate a deterministic ID in the listener based on `template_id` + `timestamp`.

---

## 3. Verification Plan

After applying fixes, re-run the suite:

```bash
# 1. Verify Module Isolation
./scripts/ci/check-cross-module-imports.sh

# 2. Verify Idempotency
./scripts/ci/check-listener-inheritance.sh

# 3. Full Suite
./scripts/ci/check-architecture-compliance.sh
```
