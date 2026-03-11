# Application Overview
**Audience:** Incoming architects and senior engineers  
**Last Updated:** March 1, 2026

---

## What Is This Application?

This is a **self-hosted MSP (Managed Service Provider) platform** built on Laravel 12. It extends [FreeScout](https://freescout.net) — an open-source shared-mailbox/helpdesk — with a full suite of MSP-specific business capabilities: client management, asset tracking, billing, contracts, service subscriptions, and a customer-facing client portal.

The product is designed to run on a single server (or Docker container) with a shared MySQL/SQLite database. It is **not** a SaaS multi-tenant platform; each deployment serves **one MSP organization** (the operator).

### Tenancy & User Roles

The system has three distinct access layers:

| Layer | Who | Data Scope |
|---|---|---|
| **MSP staff** | Employees of the single operator MSP | Global — all companies, unrestricted |
| **Client companies** | The MSP's managed clients (`Company` records) | Each company is an isolated billing/asset/contract scope |
| **Client portal users** | End-users belonging to a client company | Strictly scoped to their own company via `company_user` pivot + `ScopeCompany` middleware |

RBAC roles (seeded at first run via `RbacSeeder`):

| Role | Layer | Notes |
|---|---|---|
| `MSP Admin` | MSP staff | Global bypass — full access to everything |
| `MSP Finance` | MSP staff | Global — billing and invoicing |
| `MSP Technician` | MSP staff | Global — technical operations |
| `Client Admin` | Client portal | Company-scoped — manages their own company data |
| `Client User` | Client portal | Company-scoped — read / limited access |

> **Note on `DeploymentManager`:** This module operates *outside* the client-company model. It is a **vendor-side fleet management** tool used by the software vendor (TreeScout) to track, license, and deliver module updates to multiple MSP instances across its customer base. `DeploymentRecord.client_id` refers to a vendor-side customer, not a local `companies` row.

### Core Business Capabilities

| Capability | Module(s) |
|---|---|
| Ticket / email management (shared mailbox) | **FreeScout Core** (`app/`) |
| Client & contact management (CRM) | `Crm` |
| Product catalog & invoicing | `PIB` (Partner Invoicing & Billing) |
| Payment processing (Helcim gateway) | `Payment` |
| Contract & billing template management | `ContractManager` |
| Hardware/software asset tracking | `AssetManagement` |
| Software license management | `SoftwareSubscriptions` |
| Customer-facing self-service portal | `ClientPortal` |
| Knowledge base / documentation | `KnowledgeBase` |
| Google Workspace user sync | `GoogleAdmin` |
| Proactive alert subscriptions | `Alerts` |
| RMM tool integration (Action1) | `Action1` |
| Widget composition system | `WidgetRegistry` |
| Internal developer feedback | `DevFeedback` |
| Email data migration tooling | `EmailMigration` |

---

## High-Level Architecture

```
┌────────────────────────────────────────────────────────────────────────┐
│                              Browser / API                             │
└───────────────────────────────┬────────────────────────────────────────┘
                                │ HTTP
┌───────────────────────────────▼────────────────────────────────────────┐
│                         Laravel 12 Application                         │
│                                                                        │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │                          app/ — Shared Core                      │  │
│  │                                                                  │  │
│  │  Models: Conversation, Thread, User, Mailbox, Role, Permission   │  │
│  │  Services: IMAP, SMTP, Users, Auth, Global Navigation            │  │
│  │  Events: ConversationStatusChanged, UserReplied …                │  │
│  │  Contracts: BillingTemplateInterface, EntitlementResolver,       │  │
│  │             UserProvider, PaymentGateway, CreditWriter           │  │
│  └──────┬────────────────────┬────────────────────┬─────────────────┘  │
│         │ Interfaces         │ DB / Auth          │ Events             │
│  ┌──────▼──────┐      ┌──────▼──────┐      ┌──────▼──────┐             │
│  │     Crm     │      │ ContractMgr │      │     PIB     │  (Billing   │
│  │ (Companies) │      │ (Templates) │      │ (Invoicing) │   Engine)   │
│  └──────┬──────┘      └─────────────┘      └─────────────┘             │
│         │ Scopes             ▲                   ▲                     │
│  ┌──────▼──────┐      ┌──────┴──────┐      ┌─────┴───────┐             │
│  │   Assets    │      │  SoftSubs   │      │   Payment   │             │
│  └─────────────┘      └─────────────┘      └─────────────┘             │
│                                                                        │
│  ┌─────────────┐      ┌─────────────┐      ┌─────────────┐             │
│  │   Portal    │      │ Action1/RMM │      │ GoogleAdmin │             │
│  └─────────────┘      └─────────────┘      └─────────────┘             │
│                                                                        │
│                  … 5 further independent modules …                     │
└────────────────────────────────────────────────────────────────────────┘
                                │
                        ┌───────▼───────┐
                        │ MySQL / SQLite│
                        │ (shared schema│
                        └───────────────┘

========================================================================
┌────────────────────────────────────────────────────────────────────────┐
│                    DeploymentManager (TSDM)                   │
│   (Vendor-side Management. Exists outside the Client-Company scope)    │
└────────────────────────────────────────────────────────────────────────┘
```

---

## Directory Map

```
/var/www/html
├── app/                    ← Shared Core (Laravel conventions)
│   ├── Console/Commands/   ← Artisan commands (many module-lifecycle commands here)
│   ├── Contracts/          ← Interfaces that core exposes for modules to implement
│   ├── Events/             ← Domain events (ConversationStatusChanged, etc.)
│   ├── Http/Controllers/   ← Core controllers (conversations, mailboxes, users …)
│   ├── Listeners/          ← Core event listeners
│   ├── Models/             ← Core Eloquent models
│   ├── Providers/          ← AppServiceProvider, EventServiceProvider,
│   │                            ModuleCompatibilityServiceProvider
│   ├── Services/           ← Core services (IMAP, SMTP, Navigation)
│   └── helpers.php         ← Global helper functions
│
├── Modules/                ← All feature modules (nwidart/laravel-modules)
│   ├── Crm/
│   ├── PIB/
│   ├── Payment/
│   ├── ContractManager/
│   ├── AssetManagement/
│   ├── SoftwareSubscriptions/
│   ├── ClientPortal/
│   ├── KnowledgeBase/
│   ├── GoogleAdmin/
│   ├── Alerts/
│   ├── Action1/
│   ├── WidgetRegistry/
│   ├── DevFeedback/
│   └── EmailMigration/
│
├── config/                 ← Laravel config (modules.php controls nwidart)
├── database/
│   ├── migrations/         ← Core schema migrations
│   └── seeders/            ← DatabaseSeeder, RbacSeeder, UserSeeder, demo seeders
├── routes/
│   ├── web.php             ← Core web routes (561 lines)
│   └── api.php             ← Core API routes
├── resources/views/        ← Core Blade templates
├── tests/                  ← Global + per-suite test directories
├── docs/                   ← This documentation
└── modules_statuses.json   ← Runtime enable/disable state for each module
```

### Standard Module Layout

Every module follows the same internal structure (enforced by nwidart):

```
Modules/ExampleModule/
├── module.json             ← Metadata: name, alias, providers, requires []
├── composer.json           ← Module-local autoload (PSR-4 Modules\ExampleModule\)
├── start.php               ← Legacy bootstrapping hook (optional)
├── Config/                 ← Module config files
├── Database/
│   ├── Migrations/         ← Loaded automatically via loadMigrationsFrom()
│   ├── Factories/
│   └── Seeders/
├── Events/                 ← Module-specific events
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── routes.php          ← Module routes
├── Listeners/
├── Models/
├── Policies/
├── Providers/
│   └── ExampleServiceProvider.php   ← Single entry point, boots everything
├── Resources/views/        ← Module Blade templates
├── Services/
└── Tests/
    ├── Feature/
    ├── Integration/
    └── Unit/
```

---

## Component Inventory

### app/ — Shared Core

**Scope:** Tenant-agnostic infrastructure. Owns the email/ticket pipeline (conversations, threads, mailboxes), the user/auth layer, and the global RBAC tables (`roles`, `permissions`, `company_user`). Exposes stable `Contracts/` interfaces consumed by billing modules. Does **not** own client-company business entities — those live in feature modules.

| Category | Components |
|---|---|
| **Models** | `ActivityLog`, `Attachment`, `Channel`, `Conversation`, `Customer`, `CustomerChannel`, `Email`, `Folder`, `GooglePushChannel`, `Mailbox`, `MailboxUser`, `Module`, `ModuleActivityLog`, `Option`, `Permission`, `Role`, `SavedSearch`, `SendLog`, `Subscription`, `SyncOperation`, `Theme`, `Thread`, `User` |
| **Services** | `AtomicCounterService`, `AuditLogService`, `CacheService`, `CachedMailboxService`, `CircuitBreakerService`, `ImapService`, `MetricsService`, `ModuleSourceService`, `NavigationService`, `RateLimiterService`, `SmtpService`, `UserDirectoryRegistryService` |
| **Events** | `ConversationStatusChanged`, `ConversationUpdated`, `ConversationUserChanged`, `CustomerCreatedConversation`, `CustomerReplied`, `NewMessageReceived`, `UserAddedNote`, `UserCreatedConversation`, `UserDeleted`, `UserReplied`, `UserViewingConversation`, `VersionedEvent` |
| **Contracts** | `BillingTemplateInterface` ¹, `EntitlementResolver` ¹, `UserProvider`, `Billing/BillingServiceInterface`, `Billing/CreditReader`, `Billing/CreditWriter` |

> ¹ Stable abstractions consumed by `EntitlementEngineService` (lives in `Modules/PIB/Services/`); implemented by `ContractManager` and `PIB` modules. Kept in `app/Contracts/` so neither module hard-imports the other — the **Core Blindness** pattern.

---

### Modules

#### Action1 — RMM tool integration

**Scope:** MSP-staff only. Integrates with the Action1 RMM platform to sync devices and software discovered on client endpoints. Discovered devices flow to `AssetManagement`; discovered software to `SoftwareSubscriptions`. No client portal exposure.

| Category | Components |
|---|---|
| **Models** | `Action1Config`, `Action1GroupMapping` |
| **Services** | `Action1Service` |
| **Events** | `Action1DeviceDiscovered`, `Action1DeviceUpdated`, `Action1SoftwareDiscovered`, `Action1SyncFailed` |

#### Alerts — Proactive alert subscriptions

**Scope:** MSP-staff only. Defines and dispatches proactive alert subscriptions for monitored client environments. Delivers digest notifications via email/webhook. No client portal exposure.

| Category | Components |
|---|---|
| **Models** | `AlertDeliveryLog`, `AlertDigestQueue`, `AlertSubscription`, `AlertThrottle`, `AlertType`, `NotificationSubscription` |
| **Services** | `AlertService`, `AlertSubscriptionService` |
| **Events** | `AlertDispatched` |

#### AssetManagement — Hardware/software asset tracking

**Scope:** MSP-staff (management) + optional client portal (read-only). Owns the hardware and software asset inventory per client company. Assets are linked to `Company` and optionally assigned to a `Contact`. Asset counts are a primary input to entitlement calculations in `PIB`.

| Category | Components |
|---|---|
| **Entities** | `Asset`, `AssetStagingRecord` |
| **Services** | `AssetCounterService`, `AssetStatusService` |
| **Events** | `AssetCountChanged`, `AssetStatusChanged` |

> Uses `Entities/` instead of `Models/` (DDD-style naming).

#### ClientPortal — Customer-facing self-service portal

**Scope:** Client portal users. The outward-facing surface for end-users of client companies. Exposes a company-scoped view of invoices, open tickets, approval requests, and knowledge base content. All queries are gated by the `company_user` pivot and `ScopeCompany` middleware — a portal user cannot see data from any other company.

| Category | Components |
|---|---|
| **Models** | `ApprovalRequest` |
| **Services** | `PortalTabRegistry` |
| **Events** | `ApprovalApproved`, `ApprovalRejected`, `InvoiceUpdated`, `PortalNotification` |

#### ContractManager — Contract & billing template management

**Scope:** MSP-staff. Owns the canonical `BillingTemplate` and `Contract` models. Contracts are per client company and drive invoice generation in `PIB` via the `BillingTemplateInterface` contract. Emits lifecycle events (`ContractActivated`, `ContractExpiring`, etc.) consumed downstream.

| Category | Components |
|---|---|
| **Models** | `BillingTemplate`, `BillingTemplateLineItem`, `Contract`, `ContractSchedule`, `Milestone`, `Quote`, `QuoteLineItem`, `QuoteRevision` |
| **Services** | `BillingTemplateService`, `ContractService`, `QuoteService` |
| **Events** | `BillingTemplateDue`, `ContractActivated`, `ContractExpiring`, `ContractRevised`, `ContractTerminated`, `MilestoneReadyForBilling`, `QuoteApproved`, `QuoteCreated`, `QuoteRevised`, `QuoteSentToClient` |

#### Crm — Client & contact management

**Scope:** MSP-staff. Owns `Company` — the **primary client-company entity** referenced by all other modules — and `Contact` (individual people within a company). This is the authoritative identity source for client scoping across the system. Also owns the conversation↔client linking logic that connects the FreeScout ticket pipeline to CRM records.

| Category | Components |
|---|---|
| **Models** | `BillingTemplate`, `Client`, `ClientConversation`, `ClientServiceMetric`, `ClientUser`, `Company`, `CompanyDomain`, `Contact`, `ContactPermission`, `CrmStagingRecord`, `CustomField`, `FieldDefinition`, `TicketLifecycleEvent` |
| **Services** | `ClientService`, `ClientTicketService`, `CrmTabRegistry`, `TicketLifecycleService` |
| **Events** | `ClientArchived`, `ClientCreated`, `ClientStatusChanged`, `ClientUpdated`, `ContactCreated`, `ConversationLinkedToClient`, `ServiceMetricsCalculated`, `TicketLifecycleEventRecorded`, `UserStatusChanged` |

#### DevFeedback — Internal developer feedback

**Scope:** MSP-staff (internal tooling only). UI-only feedback collection for developers and MSP staff. No data model, no cross-module dependencies, no client portal exposure.

No models, services, events, or contracts (UI-only module).

#### EmailMigration — Email data migration tooling

**Scope:** MSP-staff. Standalone tooling for migrating historical mailbox data from external IMAP/Google sources into FreeScout. Operates independently of the billing and CRM pipeline; its data model is fully self-contained. Typically used once per client onboarding, not in day-to-day operations.

| Category | Components |
|---|---|
| **Models** | `MigrationBatch`, `MigrationCheckpoint`, `MigrationJobLog`, `MigrationLog`, `MigrationMailbox`, `MigrationMapping`, `MigrationMessage`, `MigrationProfile`, `MigrationProject`, `MigrationSubscription`, `MigrationWebsocketEvent` |
| **Services** | `ConnectivityAuditor`, `GoogleMigrationService`, `ImapDiscoveryService`, `ImapErrorParser`, `LabHealthService`, `LabManager`, `LabValidator`, `MappingCsvService`, `MigrationTicketService`, `ProviderProfileFactory`, `TestConnectionService` |
| **Events** | `DiscoveryCompleted`, `MailboxCompleted`, `MigrationDailySummaryReady`, `MigrationErrorThresholdReached`, `MigrationLogCreated`, `MigrationMilestoneReached`, `MigrationProgressUpdated`, `ProjectStatsUpdated`, `RateLimitDetected` |

#### GoogleAdmin — Google Workspace user sync

**Scope:** MSP-staff. Syncs Google Workspace users and Chromebook devices for client companies that use Google. Discovered users feed into `Crm` (as contacts); discovered devices feed into `AssetManagement`. Google license data feeds into `SoftwareSubscriptions` for reconciliation.

| Category | Components |
|---|---|
| **Models** | `GoogleConfig` |
| **Services** | `GoogleUserProvider`, `GoogleWorkspaceService` |
| **Events** | `ChromeDeviceChanged`, `GoogleChromebookDiscovered`, `GoogleGroupChanged`, `GoogleGroupDeleted`, `GoogleLicenseDiscovered`, `GoogleOrgUnitChanged`, `GoogleSyncFailed`, `GoogleUserChanged`, `GoogleUserDeleted`, `GoogleUserSynced` |

#### KnowledgeBase — Documentation / knowledge base

**Scope:** MSP-staff (authoring) + client portal users (reading). MSP staff create and manage articles and categories. Articles can be marked internal (MSP-only) or published for client portal consumption. Uses a non-standard internal `app/` subdirectory layout.

| Category | Components |
|---|---|
| **Models** | `Article`, `Category`, `TourAnalytics`, `UserTourProgress` |
| **Services** | `ArticleService`, `DemoAccountService` |

#### Payment — Payment processing (Helcim gateway)

**Scope:** MSP-staff (management) + client portal users (payment submission). Owns the Helcim gateway integration, stored payment methods, and the client credit ledger. Payments are applied against invoices generated by `PIB`. Implements the `CreditLedgerInterface` and `CreditWriter` contracts defined in `app/Contracts/Billing/`.

| Category | Components |
|---|---|
| **Models** | `ClientCreditLedger`, `Payment`, `PaymentMethod`, `Transaction` |
| **Services** | `ClientCreditService`, `HelcimService` |
| **Events** | `PaymentDisputed`, `PaymentFailed`, `PaymentSucceeded` |
| **Contracts** | `PaymentGateway` |

#### PIB — Product catalog & invoicing

**Scope:** MSP-staff. The billing computation engine. Owns the product catalog, invoices, entitlements, line items, and time entries — all per client company. Consumes `BillingTemplateInterface` / `EntitlementResolver` contracts (implemented by `ContractManager`) and asset counts from `AssetManagement` to compute and generate monthly invoices. Billing template ownership lives in `ContractManager`.

| Category | Components |
|---|---|
| **Models** | `BillingAdjustment`, `ClientCredit`, `ClientCreditLedger`, `ConversationBillingMetadata`, `Entitlement`, `EntitlementAddon`, `Invoice`, `InvoiceLineItem`, `Product`, `ReconciliationDiscrepancy`, `ReconciliationRun`, `ServiceUsage`, `TimeEntry` |
| **Services** | `BillingAnalysisService`, `BillingService`, `ClientCreditService`, `EntitlementEngineService`, `EntitlementService`, `InvoiceGenerator`, `ProrationService`, `TimeEntryService` |
| **Resolvers** | `ServicePlanEntitlementResolver`, `RentToOwnEntitlementResolver` |
| **Events** | `AdHocBucketExceeded`, `InvoiceGenerated`, `InvoicePaid`, `InvoicePublished`, `InvoiceUnusual`, `RentToOwnGoalReached`, `TimeEntryCreated`, `TimeEntryDeleted`, `TimeEntryUpdated` |

#### SoftwareSubscriptions — Software license management

**Scope:** MSP-staff. Manages per-company software product assignments and license counts. Reconciles discovered software (from `AssetManagement` and `GoogleAdmin`) against purchased subscriptions, and feeds software costs into `PIB` for invoicing.

| Category | Components |
|---|---|
| **Models** | `ClientSoftwareSubscription`, `SoftwareAssignment`, `SoftwareDiscovery`, `SoftwareProduct`, `SoftwareSubscriptionSnapshot` |
| **Services** | `LicenseDeploymentService`, `SoftwareReconciliationService`, `SubscriptionCounterService`, `VendorReconciliationService` |
| **Events** | `SoftwareAssignmentAdded`, `SoftwareAssignmentRevoked`, `SoftwareComplianceAlert`, `SoftwareCountChanged`, `SoftwareCountersReconciled`, `SoftwareDeploymentCompleted`, `SoftwareDeploymentFailed`, `SoftwareReconciled`, `SoftwareSubscriptionCreated`, `UnrecognizedSoftwareDetected` |

#### DeploymentManager — Module deployment management

**Scope:** Vendor-side only — `MSP Admin` role exclusively. **Not part of the client-company model.** Used by the software vendor (TreeScout) to track and manage activations across its MSP customer base. Each `DeploymentRecord` represents a distinct customer MSP instance (`client_id` is a vendor-side reference, not a local `companies` row). Handles One-Time Activation Code (OTAC) issuance and Git provider token delivery for module deployments.

| Category | Components |
|---|---|
| **Models** | `DeployedModule`, `DeploymentActivation`, `DeploymentRecord` |
| **Services** | `ActivationService`, `GitProviderService` |

#### WidgetRegistry — Widget composition system

**Scope:** Cross-cutting (all roles). Provides a composable widget slot system that allows modules to inject UI components into shared views (e.g., the CRM company detail page, the client portal dashboard). No data model; purely a rendering and composition contract. Modules register widgets against named slots; the host view renders all registered widgets for that slot.

| Category | Components |
|---|---|
| **Services** | `WidgetRegistryService` |
| **Contracts** | `Widget` |

---

## Key Third-Party Packages

| Package | Purpose |
|---|---|
| `nwidart/laravel-modules` v12 | Module system — discovery, loading, artisan scaffolding |
| `tormjens/eventy` | WordPress-style action/filter hook system for loose coupling between modules |
| `webklex/php-imap` | IMAP email fetching |
| `qirolab/laravel-themer` | Multi-theme support |
| `spatie/laravel-activitylog` | Audit trail |
| `laravel/reverb` | WebSocket server (real-time notifications) |
| `lab404/laravel-impersonate` | Admin user impersonation |
| `wikimedia/composer-merge-plugin` | Allows each module to ship its own `composer.json` |

---

## Data Flow: Ticket Lifecycle

```
Inbound email (IMAP)
        │
        ▼
  FetchEmails command
        │
        ▼
  Conversation + Thread created (app/Models)
        │
        ├──► Event: NewMessageReceived
        │         └── Listeners: SendNotificationToUsers, HandleNewMessage, SendAutoReply
        │
        ├──► Observer: ConversationObserver (app/Observers)
        │
        └──► CRM: ConversationEventListener.handleCreated()
                  └── Auto-links conversation to Client if email matches a Contact
```

---

## Team Conventions

- **PHP 8.2+**, `declare(strict_types=1)` in all new files
- **Pest** v4 for tests (PHPUnit 12 underneath)
- **Laravel Pint** for code style
- **PHPStan / Larastan** at level configured in `phpstan.neon`
- Module-level cross-dependencies must be declared in `module.json` under `"requires"`
- **Cross-module relation injection:** use `Model::resolveRelationUsing('name', $resolver)` in the owning module's `ServiceProvider::boot()` instead of hard-coding `HasMany`/`BelongsTo` methods on foreign-module models (see `Alerts` → `User::notificationSubscriptions`, `ContractManager` → `Milestone::contract`, `PIB` → `Milestone::invoice`)
- **Feature code belongs in the owning module.** Models, controllers, services, and events whose primary scope is a feature module live under `Modules/{Owner}/` — not in `app/`. Core `app/` is reserved for the shared email/ticket pipeline and stable Contracts interfaces
