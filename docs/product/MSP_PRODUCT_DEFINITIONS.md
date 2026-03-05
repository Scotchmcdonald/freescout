# MSP Product Definition Document
**Version:** 1.0
**Status:** Product Specification
**Associated Architecture:** System Architecture v4.7

## 1. Managed Service Plans

### 1.1 Silver Service Plan
**Description:** Comprehensive user-centric management plan providing Windows or Chromebook support.
[cite_start]**Billing Basis:** Per-User Entitlement[cite: 539].
**Rate Logic:**
* **Base Rate:** Defined by client company type (Business, Non-Profit, or Consumer).
* **Overrides:** Support for company-specific price overrides at the `BillingTemplate` level.
* [cite_start]**Included Entitlements:** 1 primary asset per active user is included in the base rate[cite: 548].
**Additional Fees:**
* [cite_start]**Additional User Assets:** Charged for any user-assigned assets beyond the initial 1-per-user allowance[cite: 549].
* [cite_start]**Non-Allocated Assets:** Charged for active assets in the inventory not currently assigned to a user[cite: 546].
* [cite_start]**Server Maintenance:** Per-unit fee for server-type assets[cite: 547].
**Included Services:**
* Cloud services management (Microsoft 365, Google Workspace provisioning and administration)
* Security tool deployment and management (Avast, Action1, endpoint security)
* Infrastructure services (network management, backup configuration, patch management)
* User training and onboarding support
* Domain and SSL certificate management as part of service delivery
[cite_start]**Technical Implementation:** Uses the `SilverPlanEntitlementResolver` and `client_asset_counters` with allocation tracking[cite: 543, 557].

### 1.2 Gold Service Plan
**Description:** Enhanced managed service plan with priority support, proactive monitoring, and faster response times.
**Billing Basis:** Per-User Entitlement with premium rate multiplier.
**Rate Logic:**
* **Base Rate:** Premium tier pricing (typically 1.5x-2x Silver rates).
* **Overrides:** Support for company-specific price overrides at the `BillingTemplate` level.
* **Included Entitlements:** Up to 2 primary assets per active user included in the base rate.
**Additional Fees:**
* **Additional User Assets:** Charged for any user-assigned assets beyond the 2-per-user allowance.
* **Non-Allocated Assets:** Charged for active assets in the inventory not currently assigned to a user.
* **Server Maintenance:** Per-unit fee for server-type assets (discounted from Silver rate).
**Enhanced Services:**
* Priority ticket response (2-hour response SLA)
* 24/7 emergency support line
* Proactive system health monitoring and alerting
* Monthly client review meetings
* Quarterly infrastructure assessments
* All services included in Silver Plan
**Technical Implementation:** Uses the `GoldPlanEntitlementResolver` with enhanced allocation tracking and SLA monitoring.

### 1.3 Platinum Service Plan
**Description:** Premium enterprise-grade managed service plan with dedicated support, strategic IT planning, and white-glove service.
**Billing Basis:** Per-User Entitlement with enterprise rate multiplier.
**Rate Logic:**
* **Base Rate:** Enterprise tier pricing (typically 2x-3x Silver rates).
* **Overrides:** Support for company-specific price overrides at the `BillingTemplate` level.
* **Included Entitlements:** Unlimited primary assets per active user included in the base rate.
**Additional Fees:**
* **Non-Allocated Assets:** Reduced or waived fees for active assets in the inventory.
* **Server Maintenance:** Included in base rate for standard server counts.
**Premium Services:**
* Dedicated account manager and technical lead
* 1-hour priority response SLA with 24/7/365 support
* Named on-site technician visits (quarterly minimum)
* Quarterly business reviews and strategic IT planning
* Annual technology roadmap development
* Vendor management and procurement assistance
* All services included in Gold and Silver Plans
**Technical Implementation:** Uses the `PlatinumPlanEntitlementResolver` with unlimited allocation tracking and premium SLA monitoring.

### 1.4 Basic Service Plan
**Description:** Entry-level managed service plan for cost-conscious clients requiring essential support without proactive management.
**Billing Basis:** Per-User Entitlement at reduced rate.
**Rate Logic:**
* **Base Rate:** Economy tier pricing (typically 0.5x-0.7x Silver rates).
* **Overrides:** Support for company-specific price overrides at the `BillingTemplate` level.
* **Included Entitlements:** 1 primary asset per active user is included in the base rate.
**Additional Fees:**
* **Additional User Assets:** Charged for any user-assigned assets beyond the initial 1-per-user allowance.
* **Non-Allocated Assets:** Charged for active assets in the inventory not currently assigned to a user.
* **Server Maintenance:** Per-unit fee for server-type assets.
**Service Limitations:**
* Break-fix support only (no proactive monitoring)
* Business hours support (8 AM - 6 PM, weekdays)
* Standard response time (4-8 hours)
* Basic security tool management
* Limited to reactive infrastructure support
**Technical Implementation:** Uses the `BasicPlanEntitlementResolver` with standard allocation tracking.

## 2. Usage & Labor Services

### 2.1 Ad-hoc Service Charge
**Description:** On-demand support for clients without a monthly service plan. Covers all reactive IT services including break-fix, cloud management, security incidents, infrastructure troubleshooting, and user support.
**Billing Logic:**
* **Pre-approval:** Support for monthly ticket buckets (limit $L$).
* [cite_start]**Collection:** Tickets are logged as they occur throughout the month[cite: 569, 575].
* [cite_start]**Threshold Alerts:** Dispatches `AdHocBucketExceeded` if usage exceeds the pre-approved limit[cite: 772].
**Service Scope:**
* Remote and on-site break-fix support
* Cloud services troubleshooting (Microsoft 365, Google Workspace, Azure, AWS)
* Security incident response
* Infrastructure support (network, server, workstation issues)
* User support and training (billed per incident)
[cite_start]**technical Implementation:** Aggregated via the `service_usage` table and invoiced via `GenerateAdHocInvoicesJob` at month-end[cite: 571, 575].

### 2.2 Labor Rates (Technician / Consultative / Development)
**Description:** Hourly professional services for migrations, setup, consulting, or software development. Encompasses all project-based work, strategic IT consulting, infrastructure projects, and specialized technical services.
**Billing Logic:**
* **Variable Rates:** Fees may be specific to the service type or the individual technician's seniority.
* [cite_start]**Assignment:** Hours may be attached directly to a service ticket or a specific project project[cite: 570].
**Service Categories:**
* **Technician Rate:** Standard support work, installations, configurations, routine maintenance.
* **Consultative Rate:** vCIO/vCTO services, strategic IT planning, business reviews, vendor management, compliance consulting, IT documentation projects.
* **Development Rate:** Custom software development, integrations, automation scripting.
* **Project Rate:** Infrastructure projects (network redesign, server migrations, cloud migrations), software implementations (CRM, ERP), large-scale deployments.
[cite_start]**Technical Implementation:** Triggered by `LaborHoursLogged` events and stored in `service_usage`[cite: 767, 571].

## 3. Procurement & Credits

### 3.1 Hardware Procurement
**Description:** Tailored hardware procurement based on specific client requirements. Includes laptops, desktops, servers, network equipment, peripherals, and related infrastructure components.
[cite_start]**Metadata Tracking:** Assets must track CPU, RAM, Storage, and Vendor details[cite: 598, 599].
[cite_start]**Pricing Logic:** Cost-basis plus a standard markup percentage[cite: 600].
**Procurement Scope:**
* End-user devices (laptops, desktops, tablets, Chromebooks)
* Servers and storage systems
* Network infrastructure (switches, routers, firewalls, access points)
* Peripherals (monitors, keyboards, mice, docking stations)
* Telecommunications equipment
[cite_start]**Technical Implementation:** Emits `AssetProcured` with a JSON metadata payload for the `PIB` module to generate a one-time invoice[cite: 771, 602].

### 3.2 Up-front Asset Credit
**Description:** Pre-paid financial credits applied to the client's account balance.
[cite_start]**Billing Logic:** Invoice is generated for the credit amount; funds are directed to "Company Credit" upon payment[cite: 583, 584].
[cite_start]**Technical Implementation:** Managed via the `credit_ledger` and the `credit_balance` column in the `clients` table[cite: 586, 587].

## 4. Development Project Billing

### 4.1 Development Flat Fee
**Description:** Project-based development with tiered payment triggers.
**Sub-types:**
* **Up-Front:** Invoiced upon project kickoff.
* [cite_start]**Milestone:** Invoiced upon achievement of specific project phases[cite: 606].
* **Delivery:** Invoiced upon final project handover.
* **Maintenance / Change Request:** Recurring or one-time fees for ongoing support.
[cite_start]**Technical Implementation:** Tracked via the `milestones` table; achievements trigger the `MilestoneAchieved` event[cite: 607, 769].

### 4.2 Development Rent-To-Own
**Description:** Monthly installments for software development that cease once a goal amount is reached.
[cite_start]**Billing Logic:** Monthly fee tapers or stops entirely when the cumulative amount paid equals the goal amount ($G$)[cite: 559, 562].
**Calculation:** $$B = \min(I, G - P_{total})$$
*Where $I$ is the monthly installment and $P_{total}$ is the total paid to date.*
[cite_start]**Technical Implementation:** Handled by the `RentToOwnEntitlementResolver` and audited via `rent_to_own_progress`[cite: 551, 568].

---

## 5. Software Products & Subscriptions

### 5.1 Overview

**Purpose:** Track third-party software licenses that we provide to clients, enabling accurate cost analysis, subscription management, and deployment tracking.

**Business Context:**
- **Cost Allocation:** Determine whether software costs are included in service packages or billed separately
- **Financial Analysis:** Know exact subscription counts, per-seat/device costs, and profit margins
- **Deployment Tracking:** Know what software to install when provisioning new users/assets
- **Vendor Reconciliation:** Compare our internal counts to vendor invoices

### 5.2 Software Product Catalog

#### **5.2.1 Security Software**

**Avast Business Antivirus**
**Description:** Endpoint protection for Windows, macOS, and servers.
**Licensing Model:** Per-Device
**Pricing Tiers:**
| Tier | Devices | Monthly Cost | Per-Device Cost |
|------|---------|--------------|-----------------|
| Starter | 1-10 | $30.00 | $3.00 |
| Business | 11-50 | $125.00 | $2.50 |
| Enterprise | 51+ | Custom | ~$2.00 |
**Assignment:** Linked to `Asset` records (type: workstation, laptop, server)
**Billing Behavior:**
- **Included:** Covered under Silver/Gold/Platinum service plans
- **Direct Billing:** Charged to Basic plan clients or ad-hoc clients
**Technical Implementation:** `SoftwareProductResolver` with device-count pricing tiers.

---

**Malwarebytes Endpoint Protection**
**Description:** Advanced malware detection and remediation.
**Licensing Model:** Per-Device
**Pricing Tiers:**
| Tier | Devices | Monthly Cost | Per-Device Cost |
|------|---------|--------------|-----------------|
| Basic | 1-25 | $50.00 | $2.00 |
| Pro | 26-100 | $175.00 | $1.75 |
| Enterprise | 101+ | Custom | ~$1.50 |
**Assignment:** Linked to `Asset` records
**Billing Behavior:** Same as Avast (included in managed plans, billed to others)

---

**DNS Filtering / Web Protection**
**Description:** Cloud-based DNS filtering for malware and content control.
**Licensing Model:** Per-User OR Per-Network
**Pricing Options:**
| Model | Unit | Monthly Cost |
|-------|------|--------------|
| Per-User | User | $2.00/user |
| Per-Network | Site/Location | $25.00/site |
**Assignment:** Linked to `Contact` (user) or `Company.locations` (site-based)
**Billing Behavior:** Typically included in managed plans; billed separately for larger deployments

#### **5.2.2 Productivity Software**

**Microsoft 365 Business Basic**
**Description:** Cloud productivity suite with Exchange Online, Teams, SharePoint, OneDrive (web apps only).
**Licensing Model:** Per-User
**Pricing Tiers:**
| Tier | Users | Monthly Cost | Per-User Cost |
|------|-------|--------------|---------------|
| Small | 1-10 | $60.00 | $6.00 |
| Medium | 11-50 | $275.00 | $5.50 |
| Large | 51+ | Custom | ~$5.00 |
**Assignment:** Linked to `Contact` records with `has_m365 = true` or dedicated `software_assignments` table
**Billing Behavior:**
- **Passthrough:** Cost billed directly to client (no markup)
- **Bundled:** Included in premium service tiers
- **Markup:** Cost + margin billed to client
**Technical Implementation:** `M365SubscriptionResolver` integrating with Microsoft Partner Center API for license reconciliation.

---

**Microsoft 365 Business Standard**
**Description:** Full M365 suite with desktop apps.
**Licensing Model:** Per-User
**Pricing Tiers:**
| Tier | Users | Monthly Cost | Per-User Cost |
|------|-------|--------------|---------------|
| Small | 1-10 | $125.00 | $12.50 |
| Medium | 11-50 | $575.00 | $11.50 |
| Large | 51+ | Custom | ~$11.00 |
**Assignment:** Linked to `Contact` records
**Billing Behavior:** Same as M365 Business Basic

---

**Microsoft 365 Business Premium**
**Description:** Full M365 suite plus advanced security (Intune, Defender, Azure AD P1).
**Licensing Model:** Per-User
**Pricing Tiers:**
| Tier | Users | Monthly Cost | Per-User Cost |
|------|-------|--------------|---------------|
| Small | 1-10 | $220.00 | $22.00 |
| Medium | 11-50 | $1,000.00 | $20.00 |
| Large | 51+ | Custom | ~$18.00 |
**Assignment:** Linked to `Contact` records
**Billing Behavior:** Same as M365 Business Basic

---

**Google Workspace Business Starter**
**Description:** Google's cloud productivity suite (Gmail, Drive, Meet, Docs).
**Licensing Model:** Per-User
**Pricing Tiers:**
| Tier | Users | Monthly Cost | Per-User Cost |
|------|-------|--------------|---------------|
| Starter | 1-10 | $60.00 | $6.00 |
| Business | 11-50 | $275.00 | $5.50 |
| Enterprise | 51+ | Custom | ~$5.00 |
**Assignment:** Linked to `Contact` records synced via GoogleAdmin module
**Billing Behavior:** Passthrough or bundled

---

**Google Workspace Business Standard**
**Description:** Enhanced storage (2TB) and advanced collaboration features.
**Licensing Model:** Per-User
**Pricing Tiers:**
| Tier | Users | Monthly Cost | Per-User Cost |
|------|-------|--------------|---------------|
| Starter | 1-10 | $120.00 | $12.00 |
| Business | 11-50 | $550.00 | $11.00 |
| Enterprise | 51+ | Custom | ~$10.00 |
**Assignment:** Linked to `Contact` records
**Billing Behavior:** Passthrough or bundled

#### **5.2.3 Backup & Recovery Software**

**Cloud Backup (Per-Device)**
**Description:** Endpoint backup solution for workstations and laptops.
**Licensing Model:** Per-Device
**Pricing Tiers:**
| Tier | Devices | Monthly Cost | Per-Device Cost |
|------|---------|--------------|-----------------|
| Basic | 1-10 | $50.00 | $5.00 |
| Business | 11-50 | $200.00 | $4.00 |
| Enterprise | 51+ | Custom | ~$3.00 |
**Assignment:** Linked to `Asset` records (workstations, laptops)
**Storage Add-on:** Additional $0.10/GB beyond included storage
**Billing Behavior:** Typically included in managed plans

---

**Server Backup**
**Description:** Full server backup with bare-metal recovery.
**Licensing Model:** Per-Server + Storage
**Pricing:**
| Component | Unit | Monthly Cost |
|-----------|------|--------------|
| Agent License | Server | $25.00 |
| Storage | Per 100GB | $10.00 |
**Assignment:** Linked to `Asset` records (type: server)
**Billing Behavior:** Usually separate line item due to variable storage costs

#### **5.2.4 Remote Management Software**

**Action1 RMM**
**Description:** Remote monitoring and management for Windows/macOS/Linux.
**Licensing Model:** Per-Device
**Pricing Tiers:**
| Tier | Devices | Monthly Cost | Per-Device Cost |
|------|---------|--------------|-----------------|
| Starter | 1-25 | $50.00 | $2.00 |
| Pro | 26-100 | $150.00 | $1.50 |
| Enterprise | 101+ | Custom | ~$1.00 |
**Assignment:** Linked to `Asset` records synced via Action1 module
**Billing Behavior:** Included in all managed plans (operational cost)

---

**TeamViewer / Remote Support**
**Description:** On-demand remote support sessions.
**Licensing Model:** Per-Technician (Concurrent Channels)
**Pricing:**
| Channels | Monthly Cost |
|----------|--------------|
| 1 | $50.00 |
| 3 | $125.00 |
| 5 | $200.00 |
**Assignment:** Linked to internal technicians (not client-facing)
**Billing Behavior:** Operational overhead (not billed to clients)

#### **5.2.5 Documentation & Password Management**

**IT Glue / Hudu / Documentation Platform**
**Description:** IT documentation and knowledge base.
**Licensing Model:** Per-User (Technician) + Per-Client Sync
**Pricing:**
| Component | Unit | Monthly Cost |
|-----------|------|--------------|
| Technician Seat | User | $35.00 |
| Client Documentation | Client | $5.00 |
**Assignment:** Technician seats internal; client documentation linked to `Company`
**Billing Behavior:** Operational cost (documentation fee can be passed through)

---

**Password Manager (Business)**
**Description:** Enterprise password management (e.g., Bitwarden, 1Password).
**Licensing Model:** Per-User
**Pricing Tiers:**
| Tier | Users | Monthly Cost | Per-User Cost |
|------|-------|--------------|---------------|
| Team | 1-10 | $30.00 | $3.00 |
| Business | 11-50 | $125.00 | $2.50 |
| Enterprise | 51+ | Custom | ~$2.00 |
**Assignment:** Linked to `Contact` records
**Billing Behavior:** Can be included in premium plans or billed separately

### 5.3 Licensing Models

#### **5.3.1 Per-User Licensing**
**Calculation:** $$\text{Cost} = \text{UserCount} \times \text{PerUserRate}$$
**Tier Selection:** Based on total user count across the organization
**Example:** 
- 25 users of M365 Business Basic at $5.50/user tier = $137.50/month
**Assignment Entity:** `Contact` (users)

#### **5.3.2 Per-Device Licensing**
**Calculation:** $$\text{Cost} = \text{DeviceCount} \times \text{PerDeviceRate}$$
**Tier Selection:** Based on total device count
**Example:**
- 40 devices with Avast at $2.50/device tier = $100.00/month
**Assignment Entity:** `Asset` (devices)

#### **5.3.3 Per-Unit with Tiers**
**Calculation:** 
$$\text{Cost} = \begin{cases} 
\text{Tier1Price} & \text{if } n \leq T_1 \\
\text{Tier2Price} & \text{if } T_1 < n \leq T_2 \\
\text{TierNPrice} & \text{if } n > T_{n-1}
\end{cases}$$
**Implementation:** `TieredPricingResolver` selects appropriate tier based on count

#### **5.3.4 Flat Rate + Per-Unit Overage**
**Calculation:** $$\text{Cost} = \text{BaseRate} + \max(0, n - \text{Included}) \times \text{OverageRate}$$
**Example:** 
- Base $100/month includes 10 users, $8/user overage
- 15 users = $100 + (5 × $8) = $140/month

#### **5.3.5 Site/Location-Based Licensing**
**Calculation:** $$\text{Cost} = \text{SiteCount} \times \text{PerSiteRate}$$
**Assignment Entity:** `Company.locations` or dedicated `sites` table

### 5.4 Billing Behavior Types

| Behavior | Description | Use Case |
|----------|-------------|----------|
| **Included** | Cost absorbed into service plan | Core tools bundled in Silver/Gold/Platinum |
| **Passthrough** | Exact vendor cost billed to client | M365 licenses at Microsoft pricing |
| **Markup** | Vendor cost + percentage/fixed margin | M365 at cost + 10% management fee |
| **Direct** | Standalone subscription (not in plan) | Software-only clients, Basic plan add-ons |

### 5.5 Technical Implementation

**Module:** `SoftwareSubscriptions` (NEW)
**Dependencies:** CRM, AssetManagement, PIB

**Database Tables:**
```sql
-- Software product catalog
software_products (
    id, name, vendor, category, 
    licensing_model ENUM('per_user','per_device','per_site','flat'),
    pricing_tiers JSON,
    default_billing_behavior ENUM('included','passthrough','markup','direct'),
    is_active, created_at, updated_at
)

-- Client software subscriptions
client_software_subscriptions (
    id, client_id, software_product_id,
    billing_behavior ENUM('included','passthrough','markup','direct'),
    custom_pricing JSON, -- Override default pricing
    effective_date, termination_date,
    billing_template_id, -- Link to PIB for billing
    notes, created_at, updated_at
)

-- Individual assignments (for tracking and deployment)
software_assignments (
    id, subscription_id,
    assignable_type ENUM('contact','asset'),
    assignable_id,
    license_key, assigned_at, revoked_at,
    deployment_status ENUM('pending','deployed','failed'),
    created_at, updated_at
)

-- Monthly subscription snapshots (for billing reconciliation)
software_subscription_snapshots (
    id, subscription_id, snapshot_date,
    assigned_count, tier_applied,
    calculated_cost, vendor_cost,
    created_at
)
```

**Events Published:**
- `SoftwareSubscriptionCreated`
- `SoftwareAssignmentAdded`
- `SoftwareAssignmentRevoked`
- `SoftwareCountChanged` → Triggers PIB billing recalculation

**Events Listened:**
- `ContactCreated` → Suggest software assignments for new users
- `AssetCreated` → Suggest software assignments for new devices
- `ContactDeactivated` → Auto-revoke software assignments
- `AssetRetired` → Auto-revoke software assignments

**Resolver:** `SoftwareProductEntitlementResolver`
```php
// Example calculation for per-user tiered software
public function calculate(BillingTemplateInterface $template): EntitlementResult
{
    $subscription = $template->softwareSubscription;
    $assignedCount = $subscription->assignments()->active()->count();
    $tier = $this->selectTier($subscription->product->pricing_tiers, $assignedCount);
    
    $cost = match($subscription->product->licensing_model) {
        'per_user', 'per_device' => $assignedCount * $tier['per_unit_cost'],
        'flat' => $tier['flat_cost'],
        default => 0,
    };
    
    return new EntitlementResult(
        amount: $cost,
        breakdown: [
            'product' => $subscription->product->name,
            'assigned_count' => $assignedCount,
            'tier' => $tier['name'],
            'per_unit_cost' => $tier['per_unit_cost'] ?? null,
        ]
    );
}
```

### 5.6 Financial Analysis Reports

**Required Reports:**
1. **Subscription Cost Summary** - Total cost per software product across all clients
2. **Client Software Breakdown** - Per-client software costs vs. service plan revenue
3. **Vendor Reconciliation** - Internal counts vs. vendor invoice line items
4. **Unassigned License Report** - Purchased but unassigned licenses (waste)
5. **Deployment Status** - Pending vs. deployed software by client
6. **Margin Analysis** - Revenue from markups vs. vendor costs

**Dashboard Metrics:**
- Total active subscriptions by product
- Total monthly software spend (vendor costs)
- Total monthly software revenue (client charges)
- Software margin percentage
- Assignment coverage (% of users/devices with required software)