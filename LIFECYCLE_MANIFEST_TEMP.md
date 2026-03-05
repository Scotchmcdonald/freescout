## 🚀 Implemented
* [External User] - Ticket Submission (Evidence: `Modules/ClientPortal/Http/Controllers/SupportController.php`, `portal.support.ticket.store` routing)
* [External User] - Ticket Progress (Evidence: `Modules/ClientPortal/resources/views/support/show.blade.php` includes a full timeline component)
* [External User] - Reply & Update (Evidence: `Modules/ClientPortal/Http/Controllers/SupportController.php` `reply` function)
* [External Admin] - Approve Hardware/Software Requests (Evidence: `Modules/ClientPortal/Http/Controllers/ApprovalController.php` fully implements approve/reject logic)
* [External Admin] - Request Tracking (Evidence: `Modules/ClientPortal/Http/Controllers/ApprovalController.php` aggregates metrics like pending, approved, rejected, signed)
* [External Finance] - Invoice Download (Evidence: `Modules/ClientPortal/Http/Controllers/InvoiceController.php` handles `downloadPdf`)
* [External Finance] - Online Payment (Evidence: `Modules/ClientPortal/Http/Controllers/ClientPaymentController.php` processes payments directly inside the portal)
* [External Finance] - Dispute a Line Item (Evidence: `Modules/ClientPortal/Http/Controllers/InvoiceController.php` handles `initiateDispute`)
* [External Finance] - Credit Management (Evidence: `Modules/ClientPortal/Http/Controllers/ClientPaymentController.php` manages credits via the `billing/account` route)
* [Internal Technician] - Ticket Resolution (Evidence: `App/Http/Controllers/ConversationController.php` fully manages mailboxes, ticket assignment, and routing logic)
* [Internal Technician] - Technical Alerts (Evidence: `App/Http/Controllers/AlertSubscriptionController.php` handles internal alert distributions)
* [Internal Finance] - Invoice Disputes (Evidence: `Modules/ClientPortal/Http/Controllers/InvoiceController.php` handles portal side, visible in backend)
* [Internal Administrator] - System Configuration (Evidence: `App/Http/Controllers/SettingsController.php` covers data import, integrators, and basic system settings)
* [Internal Administrator] - RBAC Management (Evidence: `App/Http/Controllers/RbacController.php` full role matrix and assignment logic exists)
* [Internal Administrator] - Audit Logging (Evidence: `App/Http/Controllers/SystemController.php` routes direct logic to logging and downloads via `logs.download`)
* [Internal Administrator] - User Impersonation (Evidence: `App/Http/Controllers/ImpersonationController.php` implements auth swapping and session rescue logic)
* [Internal Administrator] - Updates (Evidence: `App/Http/Controllers/SystemController.php` successfully implements `performUpdate` and `pullUpdate`)
* [Internal Administrator] - Onboarding Link (Evidence: `App/Http/Controllers/UserController@userSetup` handles password invitations publicly)

## 🏗️ Implementing
* [External User] - Service Delivery (Status: Progress timeline exists under tickets, but distinct UI mimicking visual tracking delivery logic is incomplete)
* [External User] - Resolution Confirmation (Status: UI includes re-open forms and rating logic, but no explicit gate blocking resolution exists)
* [External User] - Automated Ticket Creation (Status: Webhook payload mappings exist under `Action1WebhookController@alerts`, but automatic routing to active tickets is incomplete)
* [External User] - Future Multi-Tenancy (Status: Conceptual foundations discovered, unified `user` scopes operate contextually but interface flow needs refinement)
* [External Admin] - Invite Users (Status: Partial feature exists via `UserProvisioningController` directly bridging Google Workspace, but standalone portal invitations are bypassed)
* [External Admin] - Deactivate Leavers (Status: Partial feature exists via `portal.users.deprovision` but it manages only synced credentials rather than direct comprehensive unassignments)
* [External Admin] - Last Admin Protection (Status: Backend validation exists inside `RbacController` for system admins, but local protection triggers inside the Client Admin UI are missing)
* [External Admin] - Admin Failsafe (Status: Backend supports it, but client interface is currently missing safety rails)
* [External Admin] - Asset Assignment (Status: Endpoints like `admin.assets.assign` are robust in the backend `AssetManagement` module, though unexposed locally in the portal for simple admins)
* [Internal Technician] - Asset Context (Status: Backend module handles linkage, but frontend views alongside ticket contexts still require unification)
* [Internal Technician] - Integrations (Status: Webhooks active for services like Action1 and Google Admin, but contextual user interfaces inside ticket scopes are WIP)
* [Internal Technician] - Remote Actions (Status: Actions are stubbed via Action1 modules but interactive triggering UI logic is missing)
* [Internal Technician] - Conflict Resolution (Status: Concurrency detection works via `CollisionController`, conflicts managed in `AssetController@conflicts`, but parallel UI visual merges are WIP)
* [Internal Technician] - Patching Alerts (Status: Handled remotely by action hooks, aggregation screens are partially developed)
* [Internal Technician] - Patching Errors (Status: Basic webhook parsers are implemented minus frontend diagnostics panels)
* [Internal Technician] - Onboarding Users (Status: `UserLifecycleController` exists but logic operates contextually and is missing wizard features)
* [Internal Technician] - Onboarding Assets (Status: Basic scaffolding works under `Modules/AssetManagement` but requires polish)
* [Internal Technician] - Passive Identity Bridging (Status: Under heavy active development within `ReconciliationController` in the `Modules/PIB` folder)
* [Internal Technician] - Case Management (Status: Foundations defined in `Modules/CaseManager` but routes lack substantial internal logic mapping)
* [Internal Finance] - Invoice Generation (Status: Exploring elements via `VarianceExplorer` inside `BillingController` logic, standard PDF pipelines exist but full generation cycles are WIP)
* [Internal Finance] - Payment Reconciliation (Status: Connected deeply with `ReconciliationController` but automated bank/matching logic is incomplete)
* [Internal Administrator] - Client Onboarding (Status: Uses basic `crm.clients.create` forms vs cohesive orchestration wizards)
* [Internal Administrator] - Outages (Status: Circuit breaker states operate within `ResilienceController`, detailed system visualization is pending)
* [Internal Administrator] - Single Identity Platform (Status: Structural refactoring heavily underway unifying tables, overlapping aliases still exist)
* [Internal Administrator] - Client Disambiguation (Status: CRM entities handle this currently under `CustomerController` vs CRM terminology alias mapping)
* [Internal Administrator] - Company Assignment (Status: Underway via backend CRM models; unified frontend assignments are partial)
* [Prospective Client] - Contract Signing (Status: Partial integration exists utilizing standard Quotes and Signatures via `ApprovalController@sign`.)

## 📅 Future
* [External User] - Ask Anything
* [External User] - Knowledge Base First
* [External User] - Guided Troubleshooting
* [External User] - Immediate Automated Fixes
* [External User] - IT Health Dashboard
* [External User] - Proactive Notifications
* [External User] - Hardware/Software Request
* [External Admin] - Role Assignment
* [External Admin] - Delegated Administration
* [External Admin] - Bulk Actions
* [External Admin] - Dispute a Ticket Outcome
* [External Admin] - Software Licence Review
* [External Admin] - Company Health Summary
* [External Admin] - User Activity Report
* [External Finance] - Invoice Dashboard
* [External Finance] - Asset Cost View
* [External Finance] - Software Licence Audit
* [External Finance] - User Access Overview
* [External Finance] - Support Usage Report
* [Internal Finance] - Catalog Management
* [Internal Finance] - Failure Notifications
* [Internal Administrator] - Dependency Audits
* [Internal Administrator] - Unknown Contact Moderation
* [Prospective Client] - Trial Experience
* [Prospective Client] - Demo Data Toggle
* [Prospective Client] - Clean Slate
* [Prospective Client] - Safe Isolation
* [Prospective Client] - Limited Support
* [Prospective Client] - Onboarding Status