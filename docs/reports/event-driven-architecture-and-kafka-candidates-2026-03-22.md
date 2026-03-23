# Event-Driven Architecture and Design Report

Date: 2026-03-22

## Executive Summary

The application uses a modular, Laravel-native event bus pattern where domain services, observers, jobs, controllers, and console commands emit events and module-specific EventServiceProviders bind listeners. The architecture is strongly event-oriented for cross-module decoupling, especially in CRM, ContractManager, SoftwareSubscriptions, GoogleAdmin, Action1, EmailMigration, PIB, and Alerts.

Three dominant styles coexist:

1. Internal domain events for business workflow orchestration.
2. UI/real-time broadcast events for mailbox, portal, and migration dashboards.
3. Versioned integration events (VersionedEvent) with eventId-based idempotency for safer replay and cross-module propagation.

## Current Event Topology

### Inventory Snapshot

- Event classes by area:
  - app: 12
  - SoftwareSubscriptions: 10
  - GoogleAdmin: 10
  - ContractManager: 10
  - PIB: 9
  - EmailMigration: 9
  - Crm: 9
  - ClientPortal: 4
  - Action1: 4
  - Payment: 3
  - AssetManagement: 2
  - Alerts: 1
- Listener classes by area:
  - app: 15
  - ContractManager: 12
  - PIB: 10
  - CaseManager: 9
  - Alerts: 9
  - SoftwareSubscriptions: 7
  - Crm: 5
  - ClientPortal: 2
  - AssetManagement: 2
  - EmailMigration: 1
- Broadcast-capable events: 11
- Queue-capable listeners: 11
- VersionedEvent descendants: 34

## Event Registration and Routing Model

### Core App Provider

The core provider maps framework auth events and helpdesk conversation lifecycle events to listeners:
- app/Providers/EventServiceProvider.php

Notable patterns:
- Explicit $listen registration for auth + conversation events.
- Observer registration for Conversation, User, Thread, etc.
- Event auto-discovery intentionally disabled in the core provider.

### Module Providers

Each module can register event handlers, including cross-module consumption:
- Modules/CaseManager/Providers/EventServiceProvider.php
- Modules/ContractManager/Providers/EventServiceProvider.php
- Modules/SoftwareSubscriptions/Providers/EventServiceProvider.php
- Modules/EmailMigration/Providers/EventServiceProvider.php
- Modules/Alerts/app/Providers/EventServiceProvider.php

Notable cross-module design choice:
- Some providers use string event class names to avoid hard compile-time coupling when modules are optional.

## Types of Events and Their Usage

### 1) Framework and Authentication Events

Examples:
- Registered
- Login
- Failed
- Logout
- Lockout
- PasswordReset

Usage:
- Security/audit listener hooks (successful login, lockout, password reset logging and notifications).
- User preference restoration (locale).

Source:
- app/Providers/EventServiceProvider.php

### 2) Core Conversation and Mailbox Lifecycle Events

Examples:
- ConversationStatusChanged
- ConversationUserChanged
- CustomerCreatedConversation
- CustomerReplied
- UserReplied
- NewMessageReceived

Usage:
- Counter updates and mailbox consistency maintenance.
- Auto-reply and notification fan-out.
- CaseManager intake and follow-up processing.

Emission points include:
- app/Observers/ConversationObserver.php
- app/Services/ImapService.php

### 3) Domain Workflow Events (Business Process)

Examples by module:
- ContractManager: QuoteCreated, QuoteApproved, ContractActivated, ContractExpiring, ContractTerminated.
- Crm: ClientCreated, ClientUpdated, ClientStatusChanged, ContactCreated.
- PIB: InvoiceGenerated, InvoiceUnusual, InvoicePaid, TimeEntryCreated/Updated/Deleted.
- Payment: PaymentSucceeded, PaymentFailed, PaymentDisputed.

Usage:
- Workflow transitions and downstream actions such as contract creation, reminder scheduling, billing adjustments, activity logs, ownership transfer.
- Business process decoupling between product modules.

Emission points include services, jobs, and console commands:
- Modules/ContractManager/Services/QuoteService.php
- Modules/ContractManager/Services/ContractService.php
- Modules/Crm/Services/ClientService.php
- Modules/PIB/Jobs/GenerateRecurringInvoicesJob.php
- Modules/ContractManager/Console/ProcessExpirationsCommand.php

### 4) Integration and Synchronization Events

Examples:
- Action1DeviceDiscovered, Action1SoftwareDiscovered, Action1SyncFailed.
- GoogleUserSynced, GoogleChromebookDiscovered, GoogleLicenseDiscovered, GoogleSyncFailed.
- DiscoveryCompleted, RateLimitDetected, MigrationProgressUpdated.

Usage:
- Inbound integration events from external systems are transformed into internal events.
- Cross-module reconciliation (for example, software discovery flowing into compliance and entitlement domains).
- Sync health signaling and alerting.

Emission points:
- Modules/Action1/Jobs/SyncAction1DevicesJob.php
- Modules/GoogleAdmin/Jobs/SyncGoogleUsersJob.php
- Modules/GoogleAdmin/Jobs/SyncGoogleChromebooksJob.php
- Modules/EmailMigration/Jobs/MigrateMailboxJob.php
- Modules/GoogleAdmin/Http/Controllers/GoogleDirectoryWebhookController.php

### 5) Real-Time Broadcast Events (UI/Portal/Status)

Representative broadcast events:
- app/Events/NewMessageReceived.php (private mailbox + per-user channels)
- app/Events/ConversationUpdated.php
- app/Events/UserViewingConversation.php
- Modules/ClientPortal/Events/InvoiceUpdated.php
- Modules/EmailMigration/Events/MigrationProgressUpdated.php

Usage:
- Live UI updates in mailbox and portal contexts.
- Migration dashboard progress streaming.

### 6) Alerting and Operational Signal Events

Examples:
- SoftwareComplianceAlert
- InvoiceUnusual
- ContractExpiring
- PaymentFailed
- Action1SyncFailed
- GoogleSyncFailed
- MigrationErrorThresholdReached

Usage:
- Consolidated alert generation through Alerts module listeners.
- Alert listeners often run on queues and some use idempotent processing.

Sources:
- Modules/Alerts/app/Providers/EventServiceProvider.php
- Modules/Alerts/Listeners/*.php

## Architectural Patterns in Use

### Versioned Event Contracts

A shared base class supports event ID, schema version, and migration:
- app/Events/VersionedEvent.php

Why it matters:
- Supports schema evolution without breaking consumers.
- Enables replay safety and eventual external streaming compatibility.

### Idempotent Listener Base

Idempotent processing uses processed_events table keyed by event ID and listener class:
- app/Listeners/IdempotentListener.php

Why it matters:
- Prevents duplicate side effects on retries/replays.
- Very relevant for queue retries and future Kafka consumption semantics.

### Asynchronous Processing with ShouldQueue

Multiple listeners implement ShouldQueue for non-blocking reaction:
- Modules/CaseManager/Listeners/HandleConversationCreated.php
- Modules/EmailMigration/Listeners/SendMigrationAlerts.php
- Modules/Alerts/Listeners/SoftwareComplianceAlertListener.php
- Modules/SoftwareSubscriptions/Listeners/CreateOffboardingTicketListener.php

### Cross-Module Decoupling

Modules consume each other’s events through provider mappings, including string-based event keys when module optionality is expected:
- Modules/SoftwareSubscriptions/Providers/EventServiceProvider.php

## Where Events Are Produced

The app emits events from many layers, not only controllers:
- Model observers (state transition hooks).
- Domain/application services (business command completion).
- Integration jobs (sync and reconciliation pipelines).
- Console jobs/commands (scheduled orchestration).
- Webhook controllers (external triggers converted to internal events).

Representative production points:
- app/Observers/ConversationObserver.php
- app/Services/ImapService.php
- Modules/ContractManager/Services/QuoteService.php
- Modules/Crm/Services/ClientService.php
- Modules/Action1/Jobs/SyncAction1DevicesJob.php
- Modules/GoogleAdmin/Http/Controllers/GoogleDirectoryWebhookController.php

## Kafka Candidates

There is no existing Kafka integration found in the repository. The current architecture is a good candidate for introducing Kafka incrementally via an Outbox pattern and selected high-value streams.

### Candidate Stream 1: Integration Discovery and Sync Events

Topic family examples:
- integration.action1.device-discovered
- integration.action1.software-discovered
- integration.google.user-synced
- integration.google.license-discovered
- integration.sync-failed

Why this is a strong fit:
- Potentially high volume.
- Naturally event-like, append-only updates from external systems.
- Enables independent consumers for asset, CRM, compliance, analytics.

Current producers:
- Action1 and GoogleAdmin jobs/webhooks.

Current consumers:
- AssetManagement, SoftwareSubscriptions, Alerts, CRM listeners.

### Candidate Stream 2: Billing and Revenue Lifecycle

Topic family examples:
- billing.invoice-generated
- billing.invoice-paid
- billing.invoice-unusual
- billing.payment-failed
- billing.contract-expiring

Why this is a strong fit:
- Critical business events used by multiple modules.
- Needs reliable fan-out to alerts, analytics, customer communication, and financial projections.

Current producers:
- PIB jobs/services, Payment flows, ContractManager expiration workflows.

Current consumers:
- Alerts, ContractManager, potential BI/reporting services.

### Candidate Stream 3: CRM and Client Lifecycle

Topic family examples:
- crm.client-created
- crm.client-updated
- crm.client-status-changed
- crm.contact-created

Why this is a strong fit:
- Core entities touched across many bounded contexts.
- Good for cache/materialized view projections and audit trails.

Current producers:
- CRM services/models.

Current consumers:
- SoftwareSubscriptions, DeploymentManager (planned), ticketing/case flows.

### Candidate Stream 4: Email Migration Telemetry

Topic family examples:
- migration.progress-updated
- migration.milestone-reached
- migration.error-threshold-reached
- migration.rate-limit-detected

Why this is a strong fit:
- Long-running workflows with rich progress events.
- Good for operational dashboards, alerting, and historical analytics.

Current producers:
- EmailMigration jobs/console routines.

Current consumers:
- Alerts, UI broadcasting, operations.

### Candidate Stream 5: Security and Access Signals

Topic family examples:
- auth.login-succeeded
- auth.login-failed
- auth.lockout
- auth.password-reset

Why this is a strong fit:
- Security analytics and anomaly detection pipelines benefit from immutable event streams.

Current producers:
- Core auth event hooks.

Current consumers:
- Logging and notification listeners.

## Kafka Introduction Strategy (Low Risk)

1. Start with transactional Outbox in existing write transactions.
2. Publish selected VersionedEvent-derived events to Kafka using canonical event envelopes.
3. Keep existing Laravel event listeners active during coexistence phase.
4. Add Kafka consumers for non-critical secondary actions first (analytics, notifications).
5. Migrate critical consumers gradually with idempotency keys already aligned to eventId.

Recommended envelope fields:
- event_id
- event_type
- event_version
- occurred_at
- producer_module
- tenant_or_company_id (if applicable)
- payload
- trace_id / correlation_id

Partitioning hints:
- CRM: client_id
- Billing: invoice_id or client_id
- Migration: project_id
- Integration discovery: source_system + endpoint/device identifier

## Risks and Design Considerations

- Event naming consistency is good but mixed class-string vs literal string registration can complicate static guarantees.
- Some event emissions are commented placeholders in payment/webhook flows; lifecycle coverage should be validated before externalizing those domains.
- Broadcast events and integration events serve different durability expectations and should not share topics blindly.
- Exactly-once is not guaranteed by Kafka alone; keep idempotent consumer behavior.

## Conclusion

The app already follows a strong event-driven modular design with practical patterns that are Kafka-ready: versioned event contracts, queued listeners, and idempotent processing. The best initial Kafka rollout targets are integration discovery/sync, billing lifecycle, and CRM lifecycle streams, introduced via Outbox while preserving existing Laravel dispatch/listener behavior.

## Brief Cost-Benefit Analysis: Moving to Kafka

### Benefits

1. Better fan-out at scale with lower producer-consumer coupling.
2. Durable event history for replay, backfill, and projection rebuilds.
3. Higher throughput headroom for growth in sync and integration domains.
4. Cleaner integration boundary for multi-consumer and multi-team workflows.
5. Stronger resilience for downstream outages through decoupled consumption.

### Costs

1. Additional infrastructure and operational burden (brokers, ACLs, monitoring, upgrades).
2. Engineering complexity around schema/version governance and consumer lifecycle.
3. More explicit eventual consistency tradeoffs in user-facing workflows.
4. Migration and coexistence cost (Outbox, producers, dual-run, consumer rollout).
5. Team learning curve for operating and debugging streaming systems.

### Indicators to Assess If and When Kafka Helps

1. Event throughput trend: peak and sustained events per minute by domain.
2. Queue health trend: listener lag, retry rates, and timeout/failure rates.
3. Consumer fan-out trend: number of independent downstream consumers per event.
4. Replay demand: frequency of requested reprocessing/backfill runs.
5. Coupling incidents: production issues caused by synchronous dependency chains.
6. Analytics latency pain: delays in reporting or operational dashboards.
7. Operational readiness: observability maturity and on-call capacity for stream ops.

### Practical Decision Trigger

1. Keep the current architecture if throughput is moderate, consumers are limited, and replay is rare.
2. Pilot Kafka if at least three indicators trend upward for two to three quarters.
3. Start with Outbox plus one or two high-value streams (integration sync and billing), then expand only if measured reliability or velocity improves.

## External Reviewer Context Pack (For Gemini Assessment)

### 1) Application Profile

- Platform style: modular Laravel monolith with module-level bounded contexts under Modules/.
- Primary architectural mode: synchronous request handling plus asynchronous event/listener orchestration.
- Event surfaces: internal domain events, cross-module integration events, and real-time broadcast events.
- Current maturity signals: versioned event contracts (VersionedEvent), queue-based listeners, idempotent listener base.

### 2) Domain Areas Most Relevant to Event Design

- Core helpdesk/conversation lifecycle (app/Events, app/Observers, app/Listeners).
- CRM lifecycle events (client/contact/status flows).
- Contract/Billing/Payment workflows (ContractManager + PIB + Payment).
- External system sync flows (Action1, GoogleAdmin, EmailMigration).
- Operational alerting aggregation (Alerts module consuming cross-module events).

### 3) Existing Event Pipeline Characteristics

- Producers exist in multiple layers: observers, services, jobs, console commands, webhook controllers.
- Event routing is primarily provider-based listener mapping; some modules use string event names for optional module coupling.
- Consumers include both immediate listeners and ShouldQueue listeners for async handling.
- Idempotency pattern exists for selected listeners via processed_events tracking keyed by eventId + handler class.

### 4) Constraints and Non-Goals for Review

- This report proposes incremental Kafka adoption, not full system decomposition into microservices.
- Existing Laravel event dispatch/listener behavior must remain functional during transition.
- Event schema evolution needs compatibility with current VersionedEvent usage.
- Real-time broadcast events and durable integration streams may need separate treatment.

### 5) Assumptions Behind Recommendations

- Growth in event volume and consumer fan-out is expected in integration and billing domains.
- Replay/backfill needs are likely to increase for analytics and operational support.
- Team can adopt Outbox pattern and evolve toward stream operations gradually.
- At-least-once semantics are acceptable if idempotent consumers are enforced.

### 6) Priority Questions for External Reviewer to Validate

1. Are the proposed first streams (integration sync, billing, CRM) the highest leverage given current producer/consumer topology?
2. Is the Outbox-first migration path sufficient to avoid data-loss and dual-write risk in this codebase?
3. Which current events should remain internal-only versus become externally durable Kafka topics?
4. Is event versioning strategy (CURRENT_VERSION + migrateUp) adequate for external stream contracts?
5. Are additional reliability controls needed (DLQ policy, poison-message handling, replay tooling)?

### 7) What Good External Feedback Should Include

- A recommended phased rollout sequence with clear entry and exit criteria per phase.
- Topic taxonomy guidance (naming, ownership, partition key choices) aligned to existing domains.
- Consumer governance model (schema registry strategy, compatibility mode, ownership boundaries).
- SLO/SLI recommendations for lag, delivery latency, replay recovery time, and failure rates.
- Risks by severity with concrete mitigations applicable to this Laravel modular architecture.

### 8) Suggested Scoring Rubric for Gemini Review

Score each dimension from 1 (weak) to 5 (strong):

1. Architectural fit with current modular monolith.
2. Migration safety and backward compatibility.
3. Operational feasibility for current team maturity.
4. Data consistency and idempotency adequacy.
5. Business impact relative to implementation cost.

Interpretation:

- 22-25: Strong candidate for phased Kafka adoption now.
- 16-21: Proceed with limited pilot and stricter guardrails.
- <=15: Defer Kafka; optimize current queue/event system first.
