# Knowledge Base & Documentation Strategy

## Overview
The Knowledge Base (KB) is evolving to support distinct views for technicians and end-users, with granular visibility controls based on Company and Product context.

## 1. Views & Audiences

### A. Technician View
*   **Audience**: Internal agents, technicians, admins.
*   **Content Types**:
    *   **Generic**: Standard Operating Procedures (SOPs), Troubleshooting guides applicable to all clients.
    *   **Company-Specific**: Network diagrams, passwords (referenced), specialized configurations for Client X.
    *   **Product-Specific**: Troubleshooting steps for specific software (e.g., "QuickBooks Server Setup") only relevant if the technician is working on a client that *has* that software.

### B. End-User View (Client Portal)
*   **Audience**: Customers/Clients logging into the portal.
*   **Content Types**:
    *   **Generic**: "How to submit a ticket", "Office 365 Basics".
    *   **Company-Specific**: "How to access the VPN (Client X)", "New Employee Onboarding (Client X)".
    *   **Product-Specific**: "How to use your VoIP Phone" (Only visible if the client has VoIP service).

## 2. Implementation Strategy

To achieve this without over-complicating the schema, we will leverage a JSON-based visibility attribute on the `kb_articles` table.

### Schema Enhancements

**Table**: `kb_articles`
**New Column**: `visibility_rules` (JSON)

**Structure**:
```json
{
  "companies": [101, 102],       // Only visible to users belonging to these Company IDs
  "products": ["o365", "voip"],  // Only visible if Company has these "products" or "tags"
  "match_mode": "any"            // "any" (company OR product) vs "all" (must match company AND product - rare)
}
```

### Logic Flow

1.  **Auth Check**: First, `allowed_roles` (Admin/Agent vs Client) is checked.
2.  **Context Check**:
    *   If user is **Internal** (Agent):
        *   Can view ALL "Generic" articles.
        *   Can view "Company-Specific" articles *when context is set* (e.g., Viewing Client 360 or searching specifically for Client X). *Alternatively, Agent sees ALL, but they are categorized.*
        *   **Refinement**: Agents usually need to see *everything* to learn. Restrictions are primarily for **End Users** to reduce noise.
    *   If user is **External** (Client):
        *   If `article.companies` is set: User.company_id must be in the list.
        *   If `article.products` is set: User.company.products must include at least one match.
        *   If both are null/empty: Visible to all (Generic).

## 3. Tasks

### Phase 1: Schema & Data
- [ ] Create migration to add `visibility_rules` to `kb_articles`.
- [ ] Determine where "Company Products" are stored (likely `companies.settings` or `companies.tags`).

### Phase 2: User Interface
- [ ] **Article Editor**: Add UI to select "Restrict to Companies" (multi-select) and "Related Products" (tags).
- [ ] **Portal View**: Update the Article Repository query to filter based on the logged-in user's company context.

### Phase 3: Content Organization
- [ ] Update Seeder to include example "Company Specific" and "Product Specific" articles.
