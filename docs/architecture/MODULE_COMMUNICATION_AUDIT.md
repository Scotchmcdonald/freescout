# Module Connectivity & Data Flow Audit

**Date:** February 9, 2026  
**Status:** Current - Reflects Production Architecture

## 1. Executive Summary

This document maps all verified transmission paths, data flows, and dependencies between the Core Application (CRM) and its Feature Modules. It is generated based on a static analysis of the codebase.

## 2. Connectivity Matrix

| Source Module | Target Module | Interaction Type | Mechanism | Details |
| :--- | :--- | :--- | :--- | :--- |
| **Modules/AssetManagement** | **Modules/Crm** | Data Extension | Dynamic Relationship | Injects `assets()` hasMany relation onto `Client` and `Company` models. |
| **Modules/AssetManagement** | **Modules/GoogleAdmin** | Event Listener | `Event::listen` | Listens for `GoogleChromebookDiscovered`. |
| **Modules/AssetManagement** | **Modules/Action1** | Event Listener | `Event::listen` | Listens for `Action1DeviceDiscovered`. |
| **Modules/Crm** | **App (Core)** | Event Listener | `Event::listen` | Listens for `ConversationStatusChanged`, `ConversationUserChanged` (FreeScout events). |
| **Modules/ContractManager** | **Modules/ClientPortal** | Event Listener | `Event::listen` | Listens for `ApprovalApproved`, `ApprovalRejected`. |
| **Modules/PIB** | **Modules/ContractManager** | Event Listener | `Event::listen` | Listens for `BillingTemplateDue` (Trigger Invoice), `ContractActivated` (First Invoice). |
| **Modules/PIB** | **Modules/Payment** | Event Listener | `Event::listen` | Listens for `PaymentDisputed`. |
| **Modules/PIB** | **Modules/Crm** | Event Listener | `Event::listen` | Listens for `ConversationLinkedToClient` (Billing categorization). |
| **Modules/PIB** | **Modules/Crm** | Data Extension | Dynamic Relationship | Injects `billingMetadata()` hasOne relation onto `ClientConversation`. |
| **Modules/SoftwareSubscriptions**| **Modules/Crm** | Event Listener | `Event::listen` | Listens for `ContactCreated`, `ContactDeactivated`. |
| **Modules/SoftwareSubscriptions**| **Modules/AssetManagement**| Event Listener | `Event::listen` | Listens for `AssetCreated`, `AssetRetired`. |
| **Modules/SoftwareSubscriptions**| **Modules/Action1** | Event Listener | `Event::listen` | Listens for `Action1SoftwareDiscovered`. |
| **Modules/SoftwareSubscriptions**| **Modules/GoogleAdmin** | Event Listener | `Event::listen` | Listens for `GoogleLicenseDiscovered`. |
| **Modules/EmailMigration** | **N/A** | Event Listener | Self-contained | Listens to own events (`MigrationMilestoneReached`, etc.). |

## 3. Data Flow Sequences

### 3.1. Automatic Billing Flow
**Description:** How a signed contract transforms into a generated invoice without human intervention.
1.  **Trigger:** `ContractManager` detects a scheduled billing date.
2.  **Event Fired:** `Modules\ContractManager\Events\BillingTemplateDue`.
3.  **Transmission:** Laravel Event Bus.
4.  **Listener:** `Modules\PIB\Listeners\BillingTemplateDueListener`.
5.  **Action:**
    *   Retrieves `EntitlementEngine` (Singleton).
    *   Resolves usage from `AtomicCounterService`.
    *   Generates `Invoice` record in `pib_invoices` table.
6.  **Outcome:** Invoice created with status `pending` or `draft`.

### 3.2. Asset Discovery & Reconciliation
**Description:** How external hardware updates propagate to the asset inventory.
1.  **Source:** `GoogleAdmin` module poll or webhook.
2.  **Event Fired:** `Modules\GoogleAdmin\Events\GoogleChromebookDiscovered`.
3.  **Listener:** `Modules\AssetManagement\Listeners\GoogleChromebookDiscoveredListener`.
4.  **Action:**
    *   match `serial_number` against `assets` table.
    *   Update hardware specs / last_seen date.
5.  **Additional Flow:** If new asset created -> fires `AssetCreated` -> `SoftwareSubscriptions` listeners check for license deployment.

### 3.3. Ticket Billing Classification
**Description:** How support tickets are flagged as billable/non-billable.
1.  **Trigger:** User links a FreeScout conversation to a CRM Client.
2.  **Event Fired:** `Modules\Crm\Events\ConversationLinkedToClient`.
3.  **Listener:** `Modules\PIB\Listeners\ConversationLinkedToClientListener`.
4.  **Action:**
    *   Checks Client's `BillingTemplate` (e.g., "All-You-Can-Eat" vs "Hourly").
    *   Creates `ConversationBillingMetadata` record linked to the conversation.
    *   Sets flags `is_billable`, `billable_rate_cents`.

## 4. Architecture Graph

```ascii
    [Manual Input]        [GoogleAdmin]         [Action1]
       |  |                     |                   |
       |  '----.                | (Discovered)      | (Discovered)
       v       v                v                   v
  +---------+ +---------+   +---------------------------+
  | [CM]    | | [CRM]   |-->| [AssetManagement (AM)]    |
  | (Quote) | | (Client)|   | (Reconciles/Updates)      |
  +----|----+ +----|----+   +-----------|---------------+
       |           |                    |
       |           | (ContactCreated)   | (AssetCreated)
       |           v                    v
       |      +---------------------------+    +------------------+
       |      | [SoftwareSubscriptions]   |--->| [AtomicCounters] |
       |      | (Auto-Assigns Licenses)   |    | (Central Usage)  |
       |      +---------------------------+    +----^-------------+
       |                                            :
       | (BillingTemplateDue)                       : (Reads Usage)
       v                                            :
  +---------+       +-------------------+           :
  | [PIB]   |<------| [ContractManager] |...........:
  | (Engine)|       +-------------------+
  +----|----+
       | (Generates)
       v
  +---------+           +---------------------+
  | [Invoice] <--(Polls)|      [Payment]      |
  +---------+           | (Scheduled Process) |
       ^                +----------|----------+
       :                           v
       :                     [PaymentGateway]
       :
  +--------------------------------------------------+
  |               CLIENT 360 (Unified View)          |
  | (Aggregates: Invoices, Payments, Assets, Subs)   |
  +--------------------------------------------------+
```

## 5. Interface & Service Bindings

| Interface / Abstract | Implementation | Bound In |
| :--- | :--- | :--- |
| `App\Contracts\Billing\CreditLedgerInterface` | `Modules\PIB\Services\ClientCreditService` | `PIBServiceProvider` |
| `App\Services\Ui\WidgetRegistry` | *Consumed by Modules to register UI fragments* | `*ServiceProvider` |
| `Modules\ClientPortal\Services\PortalTabRegistry` | *Consumed by Modules to register Tabs* | `*ServiceProvider` |

## 6. Dynamic Relations Audit

*   **Client** (Crm):
    *   `assets` -> `Modules\AssetManagement\Entities\Asset` (hasMany)
    *   *Note: `invoices` relation is currently accessed via Controller query, not injected.*
*   **Company** (Crm):
    *   `assets` -> `Modules\AssetManagement\Entities\Asset` (hasMany)
*   **ClientConversation** (Crm):
    *   `billingMetadata` -> `Modules\PIB\Models\ConversationBillingMetadata` (hasOne)

