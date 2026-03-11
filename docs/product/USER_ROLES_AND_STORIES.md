# User Roles & Stories (Reorganized & Verified)

> **Design Principles**
> - **Client Users** are our highest-volume audience. Every interaction should feel fast, clear, and human.
> - Self-service actions (asking for help, finding help in the knowledgebase) should be easy and as close to magic as possible.
> - Outages should self-report when possible, and users should immediately know that tickets are already raised.
> - **Client Admins & Finance** are decision-makers. They need visibility, control, and confidence that we are delivering value.
> - Self-service actions (payments, user changes, approvals, requests) should be completable entirely within the portal — no emails, no phone calls needed.

---

## 1. External User (Client User)
**Access Level:** Access to tickets, Knowledge Base.
**Status:** ✅ Existing (`Client User` role in config/rbac.php)
**Current Permissions:** `view_tickets`, `view_knowledge_base`

> **Priority Note:** Client Users are our highest-volume audience. Every screen they touch should be fast, reassuring, and require as few clicks as possible. Where the system can solve a problem automatically, it should — and the user should be told about it immediately.

### User Stories
- **Ticket Submission:** As a User, I want to raise a support ticket in under a minute — choosing a category, writing a short description, and optionally attaching a screenshot — so I can get back to my work.
- **Ticket Progress:** As a User, I want to see a clear status timeline on my ticket (Received → Assigned → In Progress → Resolved) so I never feel like my request has disappeared.
- **Service Delivery:** As a User, when a request takes a few steps, I don't want the progress to be invisible. I want to see the progress displayed in a clear way similar to a Delivery status.
- **Reply & Update:** As a User, I want to add a comment or attach a new file to my open ticket directly from the portal without having to send an email.
- **Resolution Confirmation:** As a User, I want to be asked "Did this fix your issue?" before a ticket is marked Resolved, and be able to re-open it with one click if not.
- **Ask Anything:** As a User, I want to be able to ask anything about my computer and software, and get a helpful response fast. If a knowledgebase article exists for my question, I want that to come up right away.
- **Knowledge Base First:** As a User, when I start typing my support request, I want to see relevant Knowledge Base articles suggested in real time so I can solve common problems myself without waiting for a technician.
- **Guided Troubleshooting:** As a User, I want to follow step-by-step self-fix guides for common problems (e.g., "Printer not working") so I can resolve issues outside of business hours.
- **Immediate Automated Fixes:** As a User, I want the portal to request to run a safe remediation (e.g., restart a service, clear a cache) when I report a known fixable issue, and tell me what it did — so my problem is solved before a technician even sees it.
- **IT Health Dashboard:** As a User, I want a simple status panel on my portal home screen showing the health of my key workplace systems (email, internet, shared drives) so I know at a glance if there is a known outage affecting me.
- **Proactive Notifications:** As a User, I want to receive a portal notification or email when a system affecting me has an active incident — so I am not surprised when something stops working.
- **Automated Ticket Creation:** As a User, I want the system to automatically raise a ticket on my behalf when monitoring detects a critical issue on a device I use (e.g., server offline, disk full), with the issue pre-described — so I don't have to report something I may not know how to explain.
- **Hardware/Software Request:** As a User, I want to submit a request for new equipment or software from a simple catalog, knowing it will go to my manager for approval before being actioned.
- **Project Tasks & Approvals:** As a User, I want to see project tasks assigned to me directly in the portal (e.g., complete paperwork, approve a phase), so I can directly participate in project workflows.
- **Task File Uploads:** As a User, I want to be able to securely upload files and documents directly to my assigned project tasks, so the project team gets what they need without email chains.
- **Future Multi-Tenancy (Planned):** As an IT Consultant User, I want my single login email to span multiple Companies, with the system prompting me via dropdown which Company a new ticket/email applies to if it's ambiguous.

### Proposed / Nice-to-Have
- **Live Chat:** A chat widget offering real-time messaging with an available technician as a fast alternative to raising a formal ticket.
- **Mobile-Responsive Portal:** Full portal functionality on a mobile browser so users can check ticket status or raise a request from their phone.
- **Satisfaction Rating:** A simple 1–5 star rating on ticket close so the MSP can track service quality trends per client.

---

## 2. External Admin (Client Admin)
**Access Level:** Access to tickets, Knowledge base, can change user types in their company (as long as 1 Admin remains), edit and view company users, assets, software, view ticket stats.
**Status:** ✅ Existing (`Client Admin` role in config/rbac.php)
**Current Permissions:** `view_tickets`, `approve_users`, `view_assets`, `view_billing`, `view_knowledge_base`

> **Priority Note:** Client Admins are our primary 'decider' point of contact at a client company. If they feel in control of their account — able to onboard, offboard, and manage requests without emailing us — they become an extension of our support team rather than a bottleneck.

### User Stories
- **Invite Users:** As a Client Admin, I want to invite a new employee by entering their name and email address so they receive a welcome email and set their own password — without me needing to contact the MSP.
- **Deactivate Leavers:** As a Client Admin, I want to suspend or remove a user account immediately when someone leaves the company so they lose portal access at once.
- **Role Assignment:** As a Client Admin, I want to promote a user to "External Finance" or "External Admin" from a simple dropdown on their profile, with a clear tooltip explaining what each role can do.
- **Last Admin Protection:** As a Client Admin, I want the system to prevent me from demoting or deleting my own account if I am the only remaining Admin, with a clear message explaining why and a prompt to promote another user first.
- **Admin Failsafe:** As a Client Admin, I should be blocked from deleting or demoting myself if I am the last assigned Admin for my company, preventing my company from being locked out of portal management.
- **Delegated Administration:** As a Client Admin, I want to be able to add my own staff and manage their portal permissions, restricted strictly to external roles relevant to my Company, ensuring I can never invite a user as an MSP Admin.
- **Bulk Actions:** As a Client Admin, I want to select multiple users and change their roles or deactivate them in one action during a company restructure.
- **Approve Hardware/Software Requests:** As a Client Admin, I want to see a queue of pending requests from my team (e.g., "Jane Smith requested a new keyboard") and approve or reject each one with a note — directly in the portal without any back-and-forth email.
- **Request Tracking:** As a Client Admin, I want a clear history of all requests from my company showing who requested it, the estimated cost, and the current status (Pending → Approved → Ordered → Delivered).
- **Dispute a Ticket Outcome:** As a Client Admin, I want to formally dispute the resolution of a closed ticket on behalf of a team member and have it re-opened and escalated automatically.
- **Asset Assignment:** As a Client Admin, I want to assign or reassign an asset (e.g., Laptop #034) to a specific user in a single click so our asset register stays accurate without calling the MSP.
- **Software Licence Review:** As a Client Admin, I want to see which software licences are unassigned so I can request their removal and reduce our costs.
- **Company Health Summary:** As a Client Admin, I want a monthly summary dashboard showing open tickets, average resolution time, user count, and assets under management so I can review our partnership with the MSP.
- **User Activity Report:** As a Client Admin, I want to see which team members raise the most tickets and for what categories so I can identify training opportunities.
- **Project Visibility & Oversight:** As a Client Admin, I want to view the high-level progress, status, and milestones of active projects for my company in the portal, so I stay informed on delivery timelines.

### Proposed / Nice-to-Have
- **Onboarding Checklist:** When a new user is invited, a visible checklist for the MSP technician (Create email, Assign laptop, Add to Groups) that the Admin can track in real time.
- **Offboarding Wizard:** A guided checklist when a user is deactivated — revoke licences, unassign assets, archive tickets — with each step confirmed in the portal.
- **In-Portal Notifications:** Bell icon alerts for pending approvals so nothing gets missed if the Admin doesn't check email regularly.

---

## 3. External Finance (Client Finance)
**Access Level:** Access to tickets, knowledge base, make payments and view invoices, view company users, assets, software, ticket stats.
**Status:** ✅ Existing (`Client Finance` role in config/rbac.php)
**Current Permissions:** `view_tickets`, `view_billing`

> **Priority Note:** Client Finance users are the people who approve our invoices. They need a clean, professional view of their financial relationship with us, and the ability to act (pay, dispute, question) without picking up the phone.

### User Stories
- **Invoice Dashboard:** As a Finance Contact, I want a clear dashboard showing my current balance, the next payment due date, and recent payment history as the first thing I see when I log in.
- **Invoice Download:** As a Finance Contact, I want to download any invoice as a PDF with a clear line-item breakdown (users, assets, software licences) so I understand exactly what I am paying for.
- **Online Payment:** As a Finance Contact, I want to pay an outstanding invoice directly in the portal using a saved card or ACH bank transfer in under 30 seconds.
- **Dispute a Line Item:** As a Finance Contact, I want to flag a specific line item as disputed — with a short written reason — without it blocking payment of the remainder of the invoice.
- **Payment Confirmation:** As a Finance Contact, I want to receive an automatic email confirmation the moment a payment is processed, with a PDF receipt attached.
- **Asset Cost View:** As a Finance Contact, I want to see all company assets with their billing category (e.g., "Managed Workstation") so I can confirm we are only paying for equipment still in use.
- **Software Licence Audit:** As a Finance Contact, I want to see all software licences billed to our company with the per-seat cost and assigned user so I can identify any unused licences to retire.
- **User Access Overview:** As a Finance Contact, I want to see a list of active company users (name and role only) so I can flag if a former employee is still included in billing.
- **Support Usage Report:** As a Finance Contact, I want a monthly chart of ticket volume and average resolution time so I can assess the value of our support contract at renewal.

### Proposed / Nice-to-Have
- **Budget Threshold Alerts:** Notify me when variable monthly costs (e.g., per-seat licences) exceed a limit I define.
- **CSV/Excel Export:** One-click export of asset and software lists for internal budget spreadsheets.
- **Year-End Summary:** A single-page annual expenditure report covering all invoices, payments, and adjustments for the financial year.

---

## 4. Internal Technician (MSP Technician)
**Access Level:** Access to technical sides, conflict tables, core modules.
**Status:** ⚠️ Partial (`MSP Technician` role exists, "Conflict Tables" in development)
**Current Permissions:** `view_tickets`, `manage_tickets`, `view_crm`, `manage_crm`, `view_assets`, `manage_assets`, `view_action1`, `view_alerts`, `view_software_subscriptions`, `view_google_admin`, etc.

### User Stories
- **Ticket Resolution:** As a Technician, I want to view my assigned ticket queue sorted by SLA urgency so I always work on the highest-priority item first.
- **Asset Context:** As a Technician, I want to see the full asset record (specs, software, recent alerts) directly from a ticket so I don't need to open another tool.
- **Integrations:** As a technician, when I need to access our RMM or other integrations, I want this process to be reasonably automated with API integrations in our core application for common workflows.
- **Remote Actions:** As a Technician, I want to trigger remote actions on a device (restart, run script) via the RMM integration directly from a ticket without switching applications.
- **Conflict Resolution:** As a Technician, I want to access a Conflict Resolution Center to review and merge duplicate user/asset records that arise when Action1 and Google Admin report different data for the same entity.
- **Technical Alerts:** As a Technician, I want to see a real-time alert feed showing offline devices, low disk, and failed login events per client so I can proactively fix issues before a ticket is raised.
- **Patching Alerts:** As a Technician, I want to see realtime statuses of pending updates and remediations for security issues.
- **Patching Errors:** As a Technician, I want to see reports on issues during script or update deployments from our integrations.
- **Onboarding Users:** As a Technician, I want new accounts to show up in our app and be able to quickly map them to existing or new users.
- **Onboarding Assets:** As a Technician, I want new assets to show up in our app and be able to quickly map them to existing or new users.
- **Passive Identity Bridging:** As a Support Technician dealing with inbound email, if the sender domain matches an existing Company, I want the system to suggest linking the email to an existing User (e.g. name change) or spinning up a new User for that Company, rather than automatically doing it for me.
- **Case Management:** As a Technician, when I recieve a ticket, I want:
    - the problem to have been pre-screened for scope and clear issue
    - additional details already requested from the user
    - applicable diagnostic scripts to have been run automatically and already be available in the ticket
    - pre-research to be appended to the ticket and relevant knowledgebase noted
    - quick-win and easy first steps already sent to the client (ie, reboot your computer if uptime is large)
- **Project Template Usage:** As a Technician, I want to deploy best-practice templates for standardized projects so that setup and administration are not time-consuming burdens.
- **Project Task Breakdown:** As a Technician, I want to easily break down complex parent tasks into subtasks, so I can manage and track granular progress during a project.
- **Task Scratch Pad:** As a Technician, I want a quick, inline 'scratch pad' on a task to easily add, edit, or remove text spots with checkboxes for ad-hoc steps that don't need to be formal subtasks.
- **Task Auto-Completion Requirement:** As a Technician, when I complete all subtasks, or all scratch pad checkboxes on a task, I want the system to automatically complete, or prompt me to auto-complete, the parent task.
- **Client Project Collaboration:** As a Technician, I want to assign specific tasks (e.g., complete paperwork, approve design) securely to a client and have it appear in their portal, where they can pass files into the task to keep the workflow moving.

### Proposed / Nice-to-Have
- **Conflict Resolution UI:** A side-by-side `UserEventConflictTable` viewer with "Keep Left" / "Keep Right" / "Merge" actions and automatic CRM update on resolution.
- **Automated Resolution Notes:** When a script is run via Action1 from a ticket, the output and result are appended to the ticket thread automatically.
- **SLA Timer Widget:** Visible countdown on each ticket showing time remaining before SLA breach, colour coded green → amber → red.

---

## 5. Internal Finance (MSP Finance)
**Access Level:** Access to Financials, Invoicing.
**Status:** ✅ Existing (`MSP Finance` role in config/rbac.php)
**Current Permissions:** `view_tickets`, `view_reports`, `view_crm`, `view_billing`, `manage_billing`, `manage_payments`, `view_contracts`, `manage_contracts`, `view_software_subscriptions`, `manage_software_subscriptions`

### User Stories
- **Invoice Generation:** As a Finance Officer, I want to review automatically generated monthly invoices for all clients based on their active users, assets, and software so billing is accurate without manual counting.
- **Payment Reconciliation:** As a Finance Officer, I want to match incoming payments against open invoices and see a clear outstanding balance per client.
- **Catalog Management:** As a Finance Officer, I want to manage subscription tiers and per-unit pricing in the Service Catalog so contract changes are reflected in the next billing cycle automatically.
- **Failure Notifications:** As a Finance Officer, I want to be notified immediately when a client payment fails so I can follow up before it becomes overdue.
- **Invoice Disputes:** As a Finance Officer, I want to see when a client has disputed a line item and either accept or reject the dispute from a single screen.
- **Credit Management:** As a Finance Officer, I want to be able to review credits on a user account.

### Proposed / Nice-to-Have
- **Revenue Forecasting:** A report projecting next month's MRR based on active contracts and upcoming renewals.
- **Accounting Sync:** One-click export or scheduled push of generated invoices to QuickBooks or Xero.
- **Credit Management:** Ability to issue ad-hoc credits to a client's account that are automatically applied to their next invoice.

---

## 6. Internal Administrator (MSP Admin)
**Access Level:** Highest level of access, all screens.
**Status:** ✅ Existing (`MSP Admin` role config/rbac.php)
**Current Permissions:** `*` (Wildcard — gets every permission)

### User Stories
- **System Configuration:** As an Admin, I want to configure system-wide settings (Integrations, Mailboxes, Branding) so the platform functions as expected.
- **RBAC Management:** As an Admin, I want to manage all RBAC permissions so I can adapt access rights as the team evolves.
- **Audit Logging:** As an Admin, I want to view a searchable audit log of all user and system activity so I can investigate events.
- **User Impersonation:** As an Admin, I want to impersonate any Internal or External user so I can reproduce and resolve issues they report without needing a screen share.
- **Client Onboarding:** As an Admin, I want to provision a new client company — including their users, contracts, and assets — without needing to touch the database directly. I want to easily add new connections to mailboxes for ticket intake and know that the connection works immediately.
- **Outages:** As an Admin, I want to know when a mailbox or integration loses connection, and when it restores on its own.
- **Updates:** As an Admin, I want updates to be quick and painless, and either schedulable, or automatic with notification at a safe time.
- **Dependency Audits:** As an Admin, I want our stack to check for security issues with our dependencies regularly, with immediate notification when an audit flags a concern.
- **Single Identity Platform:** As an Admin, I want all humans capable of logging in to be unified in the main `users` table so we don't have to support multiple authentication guards, tables, and parallel logic for external clients.
- **Client Disambiguation:** As an Admin, I want to use the term "Company" unequivocally to describe business entities in the interface, and "User" for all people, with the term "Client" fully deprecated to eliminate confusion across modules.
- **Company Assignment:** As an Admin creating a new user, I want the system to dynamically show Company assignment fields when I select an "External" Role, allowing me to attach them to an existing company or create a new one inline if no collision exists.
- **Unknown Contact Moderation:** As an Admin, I want inbound tickets from completely unknown domains to remain unlinked strings until a human chooses to quickly spin up a Company and User for them, preventing spam from bloating the CRM database.
- **Onboarding Link:** As an Admin, I want a simple checkbox to "Send Invitation Email" when adding users—whether new internal hires or external clients—so they handle their own password creation securely.
- **Project Dashboard & Reporting:** As an Admin, I want a complete, robust project dashboard and reporting feature set to oversee project health, resource allocation, and timelines across all clients easily and reliably.
- **Project Governance:** As an Admin, I want to manage robust project templates to ensure best practices are followed platform-wide, preventing administration from becoming a time-consuming burden.

### Proposed / Nice-to-Have
- **NOC Dashboard:** Drag-and-drop widget layout showing real-time ticket queues, device health alerts, and billing exceptions across all clients.
- **Guided Onboarding Wizard:** Step-by-step setup flow for new MSP tenants (Company → Contacts → Contract → Mailbox assignment).

---

## 7. Prospective Client (Lead / Trial User)
**Access Level:** Restricted access (Demo / Trial mode).
**Status:** ⚠️ Partial (User exists in DB, but has no active contract).
**Key Characteristic:** Known user in the CRM who does not belong to a client with an ACTIVE contract.

> **Design Principle:** Show, don't just tell. Prospective clients should be able to see the portal's capabilities (creating a ticket, viewing assets) but with guardrails. They are excluded from high-cost features like AI Triage to prevent abuse.

### User Stories
- **Trial Experience:** As a Prospect, I want to log in and see a sample dashboard so I can understand the value the MSP provides before signing a contract.
- **Demo Data Toggle:** As a Prospect, I want to instantly populate my portal with "Demo Data" (Sample Tickets, Mock Invoices, Draft SLA Contract, Asset Inventory) so I can explore a populated system instead of seeing empty lists.
- **Clean Slate:** As a Prospect, I want to click "Clear Demo Data" to instantly remove all generated records when I'm ready to start real work, keeping only my user account and company profile.
- **Safe Isolation:** As a Prospect, my "Demo Contract" and "Demo Tickets" must be clearly flagged as non-billable and non-active, ensuring they never trigger real billing cycles or AI triage costs.
- **Limited Support:** As a Prospect, I want to submit a "Sales Inquiry" or "Demo Request" ticket, but I understand I won't get immediate AI diagnosis until I am a paying client.
- **Contract Signing:** As a Prospect, I want to view and digitally sign my service contract directly within the portal to transition instantly to a full Client User.
- **Onboarding Status:** As a Prospect, I want to see a checklist of onboarding steps (e.g., "Domain Verification", "Agent Installation") so I know what is needed to go live.

---

## Implementation Plan (Reassessed)

### Immediate (High Value, Directly Enabling Key Roles)
1. **Identity Consolidation:** Unified `users` table and deprecation of the term "Client" in favor of "Company/User" as defined in Architecture stories.
2. **Last Admin Standing Guard:** Server-side validation and client-side UI lock on the user role update endpoint — prevents demoting or deleting the last `Client Admin` in a company.
3. **In-Portal Approvals Queue:** Notification queue for `Client Admin` to approve/reject hardware and software requests raised by `Client User` accounts.

### Short-Term (Core UX Improvements)
4. **IT Health Status Panel:** Home screen widget for `Client Users` showing live system statuses pulled from monitoring alerts.
5. **Automated Ticket Creation:** Connect monitoring alerts (disk, offline, auth failure) to the ticket system so tickets are created and assigned automatically before users notice.
6. **KB Suggest-on-Type:** Wire Knowledge Base article suggestions into the "New Ticket" form so users see self-help options while describing their problem.
7. **In-Portal Dispute Flow:** Allow `Client Finance` to dispute individual invoice line items from the invoice view, with status visible to internal Finance.

### Medium-Term (Depth & Intelligence)
8. **Project Management Module:** Fully featured project administration module including project dashboard, templates, reporting, and a client portal integration (for view status, completing specific tasks, and uploading files). Features include granular subtasks, the "scratch pad" checklist on tasks, and task auto-completion logic.
9. **Conflict Resolution UI:** Frontend for `UserEventConflictTable` with side-by-side merge view for `MSP Technician` role.
10. **Budget Threshold Alerts:** Configurable spend alerts for `Client Finance` users when variable-cost line items exceed a defined monthly limit.
11. **Offboarding Wizard:** Guided checklist for `Client Admin` when deactivating a user — revoke licences, unassign assets, archive tickets.