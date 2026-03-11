# Event & Listener Robustness — App-Wide Strategy

> **Status:** ✅ Implemented (March 2026)  
> **Scope:** All queued event listeners across the entire application  
> **Related:** [CaseManager Architecture](../../modules/CASE_MANAGER_ARCHITECTURE.md) · [CaseManager Critique](../../modules/CASE_MANAGER_CRITIQUE.md)

---

## Problem Statement

Queued event listeners that fail silently create invisible data integrity issues. When a listener exhausts all retries, the event it was processing is lost — no alert fires, no log entry captures the failure, and no operator is notified. This is particularly dangerous for:

- **AI pipeline failures** (CaseManager) — cases get stuck in intermediate states.
- **Billing listeners** (ContractManager, PIB) — ownership transfers or invoices silently fail.
- **Alert listeners** (Alerts module) — the irony of alert dispatchers themselves failing silently.
- **Migration listeners** (EmailMigration) — migration progress stalls without notification.

---

## Solution Architecture

Two complementary systems provide defense-in-depth:

### Layer 1: `ResilientListener` Trait (App-Wide)

**Location:** `app/Traits/ResilientListener.php`

A generic, reusable trait for any `ShouldQueue` listener. Provides:

| Feature | Default | Customizable? |
|---|---|---|
| Max retries (`$tries`) | 3 | Define `tries()` method or override property |
| Backoff (`$backoff`) | 30 seconds | Define `backoff()` method or override property |
| `failed()` method | Logs + dispatches `listener.failed` alert | Override and call `resilientListenerFailed()` for custom behavior |
| Alert type code | `listener.failed` | Override `resilientListenerAlertTypeCode()` |

**How it works:**

```php
use App\Traits\ResilientListener;

class MyListener implements ShouldQueue
{
    use ResilientListener;

    public function handle(MyEvent $event): void
    {
        // Business logic — if this throws, Laravel queues a retry.
        // After 3 failures, ResilientListener::failed() fires automatically.
    }
}
```

When all retries are exhausted, `failed()`:
1. Logs the failure at `error` level with listener class, event class, error message, and truncated stack trace.
2. Dispatches a structured alert via `AlertService::dispatch(AlertPayload)` with type `listener.failed`.
3. Wraps the alert dispatch in its own try/catch — if alerting itself fails, it logs that too (no infinite loops).

**Alert type:** Seeded by migration `2026_03_04_200000_seed_listener_failed_alert_type.php`.

### Layer 2: `AiPipelineFailureHandler` Trait (CaseManager-Specific)

**Location:** `Modules/CaseManager/Traits/AiPipelineFailureHandler.php`

Domain-specific error handling for CaseManager listeners. Provides richer recovery beyond what `ResilientListener` offers:

- **State machine transition** → `api_error_needs_human` (guarded by state check: `new`, `triaging`, `awaiting_split_confirmation`)
- **FreeScout internal note** → visible to technicians in the conversation UI
- **Domain-specific alert** → `casemanager_api_error` alert type
- **Delayed safety net** → `CheckCaseApiErrorJob` dispatched with configurable delay (default 5 min)
- **Activity log entry** → full audit trail on the case record

The delayed `CheckCaseApiErrorJob` is the final backstop: if the listener's retries succeed before the job fires, it exits as a no-op. If the case is still stuck, it triggers the full error-handling chain.

### Layer 3: `IdempotentListener` Base Class (Deduplication)

**Location:** `app/Listeners/IdempotentListener.php`

Provides exactly-once processing via a `processed_events` database table. Events carry an `eventId`; the listener checks for prior processing before executing.

Used by: Alerts module listeners (4), SoftwareSubscriptions (1).

**Composition with ResilientListener:** `IdempotentListener` handles deduplication, `ResilientListener` handles retry + failure alerting. They work together:

```php
class ContractExpiringListener extends IdempotentListener implements ShouldQueue
{
    use ResilientListener;  // Adds retry config + failed() method

    protected function handleIdempotent(object $event): void
    {
        // Idempotent business logic
    }
}
```

---

## Current Listener Inventory

### CaseManager Listeners (Domain-Specific Resilience)

| Listener | Tries | Backoff | `failed()` | Error Handling |
|---|---|---|---|---|
| `HandleConversationCreated` | 3 | 30s | ✅ Custom | `AiPipelineFailureHandler` → state transition + internal note + alert + delayed check |
| `HandleCustomerReplied` | 3 | 15s | ✅ Custom | `AiPipelineFailureHandler` → same, guards `new`/`triaging`/`awaiting_split_confirmation` |
| `HandleConversationClosed` | 2 | 60s | ✅ Custom | `AiPipelineFailureHandler` → same |
| `HandleFernConversationCreated` | 3 | 30s | ✅ Custom | `AiPipelineFailureHandler` → re-throws for retry + delayed error check for `FernCaseRecord` |

### App-Wide Listeners (ResilientListener Trait)

| Listener | Module | Base Class | Tries | Backoff | `failed()` |
|---|---|---|---|---|---|
| `ContractExpiringListener` | Alerts | `IdempotentListener` | 3 | 30s | ✅ `ResilientListener` |
| `InvoicePublishedListener` | Alerts | `IdempotentListener` | 3 | 30s | ✅ `ResilientListener` |
| `InvoiceUnusualListener` | Alerts | `IdempotentListener` | 3 | 30s | ✅ `ResilientListener` |
| `SoftwareComplianceAlertListener` | Alerts | `IdempotentListener` | 3 | 30s | ✅ `ResilientListener` |
| `TransferOwnershipOnPayment` | ContractManager | — | 3 | 30s | ✅ `ResilientListener` |
| `SendMigrationAlerts` | EmailMigration | — | 3 | 30s | ✅ `ResilientListener` |
| `CreateOffboardingTicketListener` | SoftwareSubscriptions | `IdempotentListener` | 3 | 30s | ✅ `ResilientListener` |

### Synchronous Listeners (No Queue — No Retry Needed)

| Listener | Module | Notes |
|---|---|---|
| `UpdateQuoteOnApproval` | ContractManager | Runs synchronously in request cycle |
| `UpdateQuoteOnRejection` | ContractManager | Runs synchronously in request cycle |
| `GenerateFirstInvoice` | PIB | Runs synchronously in request cycle |

---

## Key Design Decisions

### Why a trait instead of a base class?

Several listeners already extend `IdempotentListener`. PHP doesn't support multiple inheritance, so a trait composes cleanly with any class hierarchy. The trait declares `$tries` and `$backoff` directly — these are the properties Laravel's `Dispatcher::propagateListenerOptions()` reads when creating `CallQueuedListener` jobs.

### Why not put $tries/$backoff in an initializer?

Laravel's `initialize{TraitName}()` convention works for Eloquent models, not for queued listeners. The queue system reads `$tries`/`$backoff` as static properties during job dispatch — they must be declared directly on the class (or trait).

### Why `listener.failed` as a separate alert type?

Module-specific alert types (e.g., `casemanager_api_error`) carry domain context. The generic `listener.failed` type catches everything else — billing listeners, sync listeners, etc. — without requiring each module to define its own alert type. Modules that want richer failure alerts can override `resilientListenerAlertTypeCode()`.

### Why do CaseManager listeners NOT use `ResilientListener`?

They define their own `$tries`, `$backoff`, and `failed()` methods with domain-specific logic (state transitions, internal notes, delayed checks). Adding `ResilientListener` would conflict with these declarations. The `AiPipelineFailureHandler` trait provides a superset of what `ResilientListener` offers.

---

## Bugs Fixed (March 2026)

### 1. `HandleFernConversationCreated` — Exception Swallowing

**Before:** The `handle()` method wrapped all processing in a try/catch that swallowed `\Throwable` — logging the error but never re-throwing. This meant:
- Queue retries never fired (the job always "succeeded")
- `failed()` was never called
- No alert was dispatched
- `FernCaseRecord` stayed stuck in `pending` forever

**After:** Exceptions propagate so the queue system retries (3 attempts, 30s backoff). A `dispatchDelayedErrorCheck()` is called in the catch block before re-throwing. The `isFernEnabled()` check is now before the try/catch for early return.

### 2. Missing `awaiting_split_confirmation` in State Guards

**Before:** Four locations checked `in_array($case->state, ['new', 'triaging'])` only, missing cases in `awaiting_split_confirmation`. If a split confirmation reply triggered an API failure, the case would be stuck with no error recovery.

**After:** All 4 guard locations now include `awaiting_split_confirmation`:
- `HandleCustomerReplied::failed()`
- `AiPipelineFailureHandler::handleApiFailure()`
- `CheckCaseApiErrorJob::handle()`
- The Architecture doc's [Transition Guards](../../modules/CASE_MANAGER_ARCHITECTURE.md) section

### 3. Seven Queued Listeners With Zero Failure Handling

**Before:** The 7 non-CaseManager queued listeners had no `$tries`, no `$backoff`, no `failed()` method, and no alert dispatching. Permanent failures were completely silent.

**After:** All 7 now use the `ResilientListener` trait, giving them 3 retries, 30s backoff, structured logging, and `listener.failed` alert dispatch.

---

## Adding Resilience to New Listeners

### For a new queued listener (any module):

```php
use App\Traits\ResilientListener;
use Illuminate\Contracts\Queue\ShouldQueue;

class MyNewListener implements ShouldQueue
{
    use ResilientListener;

    public function handle(SomeEvent $event): void
    {
        // Your logic here. Exceptions trigger retries.
    }
}
```

### For a new idempotent + resilient listener:

```php
use App\Listeners\IdempotentListener;
use App\Traits\ResilientListener;
use Illuminate\Contracts\Queue\ShouldQueue;

class MyNewListener extends IdempotentListener implements ShouldQueue
{
    use ResilientListener;

    protected function handleIdempotent(object $event): void
    {
        // Exactly-once processing with retry + alert on failure.
    }
}
```

### For custom retry counts:

```php
class MyNewListener implements ShouldQueue
{
    use ResilientListener;

    // Override the trait's defaults:
    public int $tries = 5;
    public int $backoff = 60;

    // ...
}
```

> **Note:** If you define `$tries`/`$backoff` directly, PHP requires that the trait's declaration is compatible (same type + visibility). Since both are `public int`, redeclaring with the same type in the using class is valid.

### For domain-specific failure handling:

```php
class MyNewListener implements ShouldQueue
{
    use ResilientListener;

    public function failed(object $event, \Throwable $exception): void
    {
        // Custom cleanup first
        $this->cleanupPartialState($event);

        // Then delegate to ResilientListener for logging + alert
        $this->resilientListenerFailed($event, $exception);
    }
}
```

---

## Monitoring & Observability

### Alert Channels

The `listener.failed` alert type is configured with `['mail', 'database']` default channels. Admins can customize channels and recipients via the Alerts module UI.

### Log Patterns

All resilience-related log entries use structured prefixes for easy filtering:

| Prefix | Source | Level |
|---|---|---|
| `[ResilientListener]` | Generic trait | `error` |
| `[CaseManager]` | AI pipeline failures | `error` / `warning` |
| `[CaseManager] Delayed error check` | `CheckCaseApiErrorJob` | `info` / `warning` |

### Recommended Log Monitoring Query

```
[ResilientListener] OR [CaseManager] AI pipeline failure
```

---

## Future Considerations

- **Circuit Breaker:** If a specific listener fails repeatedly (e.g., external API down), a circuit breaker could temporarily disable the listener rather than burning through retries. See CaseManager Critique #8.
- **Dead Letter Queue:** Laravel's `failed_jobs` table already captures failed queued listeners. A dashboard to review and replay these would complement the alert system.
- **Metrics:** Instrument retry counts and failure rates per listener for trend analysis. The `listener.failed` alerts provide event-level visibility, but aggregate metrics would reveal systemic issues earlier.
