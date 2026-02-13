# Comprehensive UI Inventory & Assembly Specification v4.0

> **📚 MASTER SPECIFICATION**  
> **Status:** Original design specification (reference document)  
> **Last Updated:** January 16, 2026  
> **Current Documentation:**  
> - [UI_IMPLEMENTED.md](UI_IMPLEMENTED.md) - Completed features (✅ what exists now)  
> - [UI_ROADMAP.md](UI_ROADMAP.md) - Remaining features (📋 what's next)  
>
> **Note:** This document serves as the master specification. For implementation status and planning, refer to the documents above.

---

**Associated Version:** Platform v4.0  
**Design Philosophy:** The Pilot's Cockpit (Density + Resilience)

---

## 1. CRM & Core Workspace (Foundation)
*The essential administrative hub for managing client relationships and identities.*

### 1.1 Client 360 Workspace
* **Usage:** Primary command center for a specific client.
* **Workflows:** Aggregating billing, assets, and contacts into a single source of truth.
* **Assembly:** Uses **Hybrid Tab Navigation** (Overview, Assets, Billing, Contacts).
* **Style Guide:** Clinical and precise; uses `x-card` for company vitals and high-density `x-data-table` for contact lists.

### 1.2 User Lifecycle Dashboard
* **Usage:** Manages individual user identities synced from external sources.
* **Workflows:** Reflecting security states from Google Workspace and Action1.
* **Assembly:** `Card` layout with `Badge` components for status indicators (e.g., Suspended, Active).

### 1.3 Contact & Permission Matrix
* **Usage:** Granular management of Role-Based Access Control (RBAC).
* **Assembly:** A dense `x-data-table` for managing portal access and specific client-scoped roles.

### 1.4 Custom Field Builder
* **Usage:** Defining dynamic attributes for clients and contacts.
* **Assembly:** Follows the **Guided Journey (Wizard)** pattern to ensure logical configuration flow.

---

## 2. Integration & Resilience Layer (The Infrastructure)
*Monitoring and managing the health of the system's event-driven backbone.*

### 2.1 Service Resilience Dashboard (Circuit Breaker)
* **Usage:** Real-time monitoring of external service health (Action1, Google, Helcim).
* **Workflows:** Visualizing "Open/Closed" states and allowing manual circuit overrides.
* **Assembly:** Live status cards with `x-badge` variants: `Danger` (Open), `Warning` (Half-Open), and `Success` (Closed).



### 2.2 Sync Operation Monitor
* **Usage:** Tracking the progress of long-running batch synchronization jobs.
* **Workflows:** Resuming stalled jobs and reviewing item-level failures.
* **Assembly:** Progressive feedback via `sync_operations` table data and a dedicated "Resume" button.

### 2.3 Webhook Gateway
* **Usage:** Managing real-time push notification channels (e.g., Google Directory API).
* **Assembly:** Management interface for `google_push_channels` including signature verification status.

### 2.4 Event Audit Log (The Blackbox)
* **Usage:** High-density troubleshooting of the event-driven system.
* **Assembly:** A "Terminal" view of the `processed_events` table including `event_signature` hashes and timing data.

---

## 3. Asset & Inventory Management
*Maintaining a reconciled technical state across all infrastructure.*

### 3.1 Global Fleet Inventory
* **Usage:** A unified, filterable list of all hardware across all sync sources.
* **Assembly:** Advanced `x-data-table` with multi-source filtering (e.g., Action1 Windows devices vs. Google Chromebooks).

### 3.2 Asset Staging & Conflict Console
* **Usage:** Resolving discrepancies between automated sources.
* **Assembly:** Side-by-side comparison `Card` layout for manual conflict resolution.

### 3.3 Reconciliation Run History
* **Usage:** Visualizes the results of weekly "Self-Healing" scans.
* **Style Guide:** Summarizes discrepancies found and corrections applied to prevent data drift.

### 3.4 Device Assignment Wizard
* **Usage:** Assigning specific assets to users to trigger billing entitlement updates.
* **Assembly:** A **Guided Journey (Wizard)** that ensures spatial context during the assignment process.

---

## 4. Billing, PIB & Financials (The Engine)
*The critical layer for accurate revenue generation and reconciliation.*

### 4.1 Billing Template Architect
* **Usage:** Configuring Silver/Gold Plan rules and Rent-To-Own goal amounts.
* **Assembly:** **Wizard** pattern to prevent accidental data loss during complex product setup.

### 4.2 Dry-Run Variance Explorer
* **Usage:** Pre-generation review of invoices to detect >20% month-over-month variances.
* **Assembly:** Semantic `x-badge` and `x-alert` components to flag high-variance line items.

### 4.3 Manual Billing Correction Tool
* **Usage:** Audited retroactive adjustments to historical counts or dates.
* **Style Guide:** Utilizes a `Vertical Stepper` to display the audit trail of adjustments.

### 4.4 Service Usage Collector
* **Usage:** Displays ad-hoc labor, technician hours, and milestone progress awaiting invoicing.
* **Assembly:** Aggregates data from the `service_usage` table for month-end reconciliation.

### 4.5 Credit Ledger Workspace
* **Usage:** Tracking up-front asset credits and historical balance deductions.
* **Style Guide:** Ledger-style table reflecting the `credit_ledger` transactions.

---

## 5. Client-Facing Transparency Portal
*Secure, high-trust interfaces for customer self-service.*

### 5.1 Executive Dashboard
* **Usage:** High-level overview of managed fleet, credits, and unpaid balances.
* **Assembly:** "Control Tower" dashboard with real-time updates via Reverb WebSockets.

### 5.2 Interactive "Smart" Invoice Detail
* **Usage:** Revealing the math behind complex proration and usage charges.
* **Assembly:** **Tabbed Content** (Summary, Details, Timeline) to reduce cognitive load.



### 5.3 Approval & Signature Center
* **Usage:** Reviewing quotes and signing off on project milestones.
* **Assembly:** **Wizard** flow ensuring spatial context from review to digital signature.

### 5.4 Wallet & Auto-Pay Manager
* **Usage:** Secure vaulting and management of Helcim payment methods.

---

## 6. Nice-to-Have & Premium Features
*Advanced automation and project tracking enhancements.*

### 6.1 Quote Architect (Interactive Canvas)
* **Usage:** Drag-and-drop tool for building complex hardware and labor proposals.

### 6.2 Milestone Progress Stepper
* **Usage:** Visual tracker for development projects.
* **Style Guide:** Uses "Pulse" animations for active phases and `x-badge` for status (Achieved, Pending).

### 6.3 Alert Subscription Center
* **Usage:** Matrix for users to subscribe to specific system events (e.g., Unusual Variance) via Slack/Email.

### 6.4 UI Component Storybook
* **Usage:** Internal documentation to ensure adherence to **ADR-006** (Shared Component Library).
* **Governance:** Platform Team source of truth for `x-button`, `x-data-table`, and `x-card` usage.

---