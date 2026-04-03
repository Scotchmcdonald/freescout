# Phase 2 — Client Admin UX

**Goal:** Translate the powerful but technical RBAC backend into a UI a non-technical Client
Admin can confidently use, and give them an action-oriented landing page instead of a static
link list.

---

## 2.1 Dashboard Action Center

`Modules/ClientPortal/resources/views/dashboard.blade.php`

Replace the `Quick Links` widget card with a full **Action Center** section that:

1. Shows badge-counted tiles for each pending action type:
   - "N Contracts pending your signature" → links to `portal.approvals.index?status=pending&request_type=quote_approval`
   - "N Invoices overdue" → links to `portal.invoices.index` pre-filtered to overdue
   - "N Open tickets waiting on you" → links to `portal.support.tickets`

2. Admin-only tile (gated via permission):
   - "N Users without portal roles assigned" → links to admin RBAC matrix filtered to their company

3. All tiles are **empty-state aware** — when count = 0 the tile is dimmed (not hidden) so
   the Admin can confirm there is nothing outstanding.

4. Summary data is injected by `PortalController::getCompanySummary()` — add pending counts
   to the existing `$summary` array.

**Layout pattern:** Use the UX guide's "Control Tower" dashboard pattern. Warning-tier left
border for overdue invoices, primary-tier for pending approvals.

---

## 2.2 Admin Permission Matrix — Remove `prompt()` Dialog

`resources/views/admin/crm/permission-matrix.blade.php`

**Current gap:** "Quick Role Templates" section uses `prompt('Enter Client ID...')`.
This is both inaccessible (broken in some browsers, fails WCAG) and error-prone.

**Fix:** Replace `prompt()` with an inline expanded form:
1. Client selector from the already-loaded `$allClients` dropdown
2. Role selector from `$permissionTypes`
3. "Apply to all contacts" submit button with confirmation modal (not JS `confirm()`)
4. Show the contact count for the selected client as preview text: "This will update 5 contacts."

---

## 2.3 Admin Permission Matrix — Business-Language Legend

Add the new `getActionDescriptions()` data to the "Permission Details" section so each
role card displays toggle-style capability rows with plain-English text:

```
[✓] Can see past and current invoices
[✓] Can dispute invoices and authorise payment
[✗] Can add, edit, and deactivate staff in the portal
```

---

## 2.4 Effective Permissions View (Stretch)

When a Client Admin views a contact's profile in the portal (or admin CRM), show a
read-only "What can this person do?" panel:

- Derived from the contact's permission + any per-contact overrides
- Uses the new `getActionDescriptions()` text, not slug arrays
- Plain traffic-light indicators: green check = can do, grey × = cannot

---

## Acceptance Criteria

- [ ] Dashboard shows counted action tiles (pending approvals, overdue invoices, open tickets)
- [ ] Tiles show correctly when count is 0 (dimmed, not hidden)
- [ ] Quick Role Templates uses dropdown + modal, not `prompt()`
- [ ] Permission Details legend shows plain-English capability tags
- [ ] No hardcoded Tailwind colors — only semantic theme classes
