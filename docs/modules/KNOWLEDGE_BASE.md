# Knowledge Base v2 - Product Specification

> **⚠️ DESIGN BLUEPRINT ONLY**
> This document describes the planned architecture for Knowledge Base V2. 
> **Current Status:** Pre-Alpha. Controllers exist but views and database migrations are missing. 
> **Do not expect this feature to function in the current codebase.**

**Version:** 2.1
**Status:** Enhanced Requirements Definition (Post-Critique)
**Context:** MSP-Grade Knowledge Management System
**Last Updated:** February 9, 2026

---

## 1. Core Data Architecture

### A. Visibility Scopes
Every article enforces a hard privacy setting to ensure security.

*   **Internal Only**: Visible to Agents and Admins only.
    *   *Content:* Technical details, passwords, sensitive backend procedures, private network diagrams.
    *   **RBAC Enhancement:** Co-managed environments require granular control. Internal articles can be further restricted by role:
        *   `Level 1 Tech` - Standard procedures.
        *   `Senior Engineering` - Advanced configs, credentials.
        *   `Client IT Lead` (Co-managed) - Selected internal docs for trusted client partners.
*   **Client Facing**: Visible to Agents *and* Clients.
    *   *Content:* Sanitized, user-friendly instructions, "How-to" guides, public policy documents.

### B. The "Dual-Pane Editor" (Enhanced Shadow Pair)
**Problem:** Creating two separate articles and linking them manually is high friction. Editors will skip it.

**Solution:** Use a **Tabbed Editor** approach. One entry screen with two tabs:
*   **[Public Content]** - What the client sees.
*   **[Internal Notes]** - What the technician sees (passwords, backend steps, diagrams).

The system treats them as a unified pair automatically:
*   Searching for "VPN Setup" returns one result, but the technician can toggle between views.
*   The Public tab is automatically visible to Clients. The Internal tab is role-restricted.
*   **Use Case:** An Agent viewing the internal "Exchange Server 2019 Troubleshooting" sees a "Switch to Public View" button to instantly preview what the client would see, enabling them to grab the link without searching twice.

---

## 2. Allocation Logic (The "Tagging" System)

Articles are categorized into three allocation types to determine **relevance** and **visibility**.

| Allocation Type | Internal View Logic | Client View Logic |
| :--- | :--- | :--- |
| **Global / All** | Always visible. Searchable by default. | Visible to **all** logged-in clients. |
| **Product-Specific** | Visible when searching/filtering for a specific software or device (e.g., "3CX Phone", "Fortinet"). | Visible **only** if the Client account has that Product/Asset assigned to them. |
| **Company-Specific** | Visible when the Agent is working in the context of that specific Company (e.g., "Client 360" view, Ticket view). | Visible **only** to users belonging to that specific Company ID. |

---

## 3. Editorial Workflows

### A. The "Smart Fork" Workflow (Enhanced Company Pinning)
**Problem:** Simple "Deep Copy" creates a dead end. If the Global template is updated with a critical security fix, the ACME Corp fork won't know it's now outdated.

**Solution:** Implement **Parent-Child Inheritance** with change tracking.

#### Workflow:
1.  **Agent opens Global VPN Guide.**
2.  **Clicks "Customize for ACME Corp".**
3.  **System creates a child record:**
    *   Pre-fills `company_id` = ACME.
    *   Sets `parent_article_id` = Global VPN Guide ID.
    *   Adds a **Visual Diff Sidebar** showing what differs from the Global version.
4.  **Future Updates:**
    *   When the Global template is edited, the system flags the ACME fork: **"Parent article updated. Review changes?"**
    *   Editor can accept changes (merge), reject, or manually review.

#### Benefits:
*   **Isolation:** ACME's VPN setup is unique.
*   **Awareness:** Technicians know when a security patch applies.
*   **Audit Trail:** Track when and why deviations occurred.

### B. Product Association
*   **Product Picker**: A user-friendly, searchable dropdown in the editor to attach an article to specific asset types or software definitions.
*   **Data Source**: Pulls from the defined `Product` or `ServiceCatalog` entities.

---

## 4. User Experience (UX) Requirements

### A. Unified Search Engine with Context Hierarchy
A "Google-like" search bar that respects the user's current context.
*   **Fuzzy Matching**: Matches "wifi", "wi-fi", and "connection" seamlessly.
*   **Context Awareness & Search Precedence**: 
    *   When an agent is on a ticket for "ACME Corp," the search prioritizes results in this order:
        1.  **Company-Specific** (ACME's custom setup) - **Highest Priority**
        2.  **Product-Specific** (Products ACME actually uses)
        3.  **Global** (General knowledge)
    *   If an Agent is searching from the **Global Dashboard**, results include all Global articles without filtering.
    *   **Visual Indicator:** Search results show badges: `[ACME]`, `[Fortinet]`, `[Global]` so technicians understand *why* this result appeared.

### B. The "Technician's Cockpit"
A dedicated view for Agents designed for speed.
*   **Recent History**: "Articles you viewed in the last 24h".
*   **Commonly Viewed**: "Most popular articles for [Current Product Context]".
*   **One-Click Copy**: 
    *   Copy Public Link (for chat).
    *   Copy Content Snippet (for email body).

### C. "Verified" Badges & Content Freshness
**Problem:** Outdated articles are dangerous. Technical debt kills.

**Solution:** Articles have an `expiry_date` or `last_verified` field.
*   If an article hasn't been reviewed in **6 months**, it gets a **"Review Required"** flag.
*   Search results and article headers display freshness indicators:
    *   ✅ **Verified** (Reviewed within 6 months)
    *   ⚠️ **Review Required** (6+ months old)
    *   🚫 **Deprecated** (Marked as obsolete but retained for historical reference)
*   Technicians are warned before following 5-year-old screenshots.

---

## 5. Implementation Roadmap

### Phase 1: Database & Schema
*   Update `kb_articles` table:
    *   `visibility_scope` (ENUM: 'internal', 'public', 'co-managed')
    *   `internal_access_level` (ENUM: 'level_1', 'senior_eng', 'client_it_lead') - For RBAC
    *   `allocation_type` (ENUM: 'global', 'company', 'product')
    *   `company_id` (FK, nullable)
    *   `product_id` (FK, nullable)
    *   `parent_article_id` (Self-referencing FK, nullable) - For Parent-Child Inheritance
    *   `public_content` (TEXT) - Client-facing tab content
    *   `internal_content` (TEXT) - Internal-only tab content
    *   `last_verified_at` (TIMESTAMP, nullable) - For freshness tracking
    *   `expires_at` (TIMESTAMP, nullable) - For deprecation
    *   `verification_status` (ENUM: 'verified', 'review_required', 'deprecated')

### Phase 2: Backend Logic
*   Implement Filter Scopes in `ArticleRepository` with Context Hierarchy.
*   Implement "Smart Fork" service method with Parent-Child tracking.
*   Build Change Detection system to flag child articles when parent is updated.
*   Implement Freshness Check background job (flag articles older than 6 months).

### Phase 3: Interface
*   Build Dual-Pane (Tabbed) Editor with [Public Content] and [Internal Notes] tabs.
*   Update Article Editor with Smart Fork UI (Visual Diff Sidebar).
*   Build the Context-Aware Search component with precedence indicators.
*   Add Freshness Badges to search results and article headers.
*   Implement "Merge Changes" dialog for child articles when parent is updated.
---

## 6. Critical Enhancements (v2.1 Updates)

### A. Version Control vs. Forking (RESOLVED)
**Original Issue:** Deep Copy strategy created \"dead ends.\" Forked articles became isolated from parent updates, creating security risks when global templates received critical patches.

**Resolution:** Parent-Child Inheritance with change tracking. Child articles maintain `parent_article_id` reference. System notifies editors when parent is updated, enabling merge workflows.

### B. Security & Co-Managed Access (RESOLVED)
**Original Issue:** Binary \"Internal Only\" scope was insufficient for co-managed environments where Client IT Leads need selective internal access.

**Resolution:** Added `internal_access_level` field with RBAC granularity:
*   `level_1` - Standard technician procedures
*   `senior_eng` - Advanced configs, credentials
*   `client_it_lead` - Approved internal docs for trusted client partners

### C. Shadow Pair Friction (RESOLVED)
**Original Issue:** Manual linking of Internal/Public article pairs was high friction. Editors would skip this step.

**Resolution:** Dual-Pane (Tabbed) Editor. Single article with two content fields (`public_content`, `internal_content`). System treats them as unified pair automatically.

### D. Content Drift & Technical Debt (RESOLVED)
**Original Issue:** No mechanism to prevent technicians from following outdated procedures.

**Resolution:** Freshness Tracking via `last_verified_at` and `expires_at` fields. Articles older than 6 months receive \"Review Required\" badges. Deprecated content is flagged but retained for audit.

---

## 7. Success Metrics

### Adoption Indicators
*   **Search-to-Resolution Time**: Target <30 seconds from query to relevant article.
*   **Fork Merge Rate**: >80% of child articles should accept parent updates when applicable.
*   **Freshness Compliance**: <5% of active articles should have \"Review Required\" status.

### Quality Metrics
*   **User Satisfaction**: NPS >8 from technicians.
*   **Client Self-Service Rate**: 40% reduction in \"How do I...\" tickets.

---

## 8. Technical Considerations

### Performance
*   **Search Index**: Full-text search with context weighting requires ElasticSearch or similar.
*   **Diff Engine**: Visual diff for parent-child comparison requires content versioning (consider using `audits` table or JSON versioning field).

### Data Migration
*   Existing `allowed_roles` JSON field must be migrated to new `visibility_scope` + `internal_access_level` structure.
*   Legacy \"linked\" articles must be converted to dual-pane format or remain as separate paired entities during transition period.