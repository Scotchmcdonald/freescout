# Phase 3 — Persona-Targeted Screen Improvements

**Goal:** Close the remaining per-persona UX gaps identified in the audit.

---

## 3.1 Approval Signature UI (Client Admin — Orchestrator)

`Modules/ClientPortal/resources/views/approvals/show.blade.php`

**Gap:** `POST portal/approvals/{id}/sign` exists with full controller logic but no UI surface.

**Fix:** In the Action Section, when `$approval->request_type === 'quote_approval'` and
`$approval->canBeActioned()`, show a **third action panel**:

```
[✍ Sign & Approve]  ← primary success-tier button

Expand →
  "Type your full legal name to sign this document"
  [__________________ full name text input]

  Signature method: ◉ Digital  ○ DocuSign  ○ Manual Upload

  [ Confirm Signature & Approve ]   [ Cancel ]
```

- Input is required, minimum 2 characters.
- On submit, posts to `portal.approvals.sign` with `signature` and `method`.
- Show a loading spinner while processing.
- On success, the page reloads with the signed status banner.

---

## 3.2 Finance — Month-over-Month Context on Invoice Index

`Modules/ClientPortal/resources/views/invoices/index.blade.php`

**Gap:** Finance user has no trend context from the list view.

**Fix:** Above the Invoice History table, add a **6-month trend strip**:
- Six compact card tiles (e.g., Oct–Mar), each showing month name + total amount
- Current month highlighted in primary colour
- If current month total > previous month, show a diff chip:
  `▲ $150 more than last month → see why`
  that links to the latest invoice detail on its `summary` tab

Data supplied by `InvoiceController::index()` — add a `$monthlyTotals` array that
aggregates total_amount per calendar month for the last 6 months.

---

## 3.3 Finance — Per-Line "Query this charge" Action

`Modules/ClientPortal/resources/views/invoices/show.blade.php`

**Gap:** Dispute is whole-invoice only. Finance needs line-item-level challenge.

**Fix:** In the **Line Items** tab, add a `⋯` overflow button on each line item row.
Clicking expands an inline form:

```
"What's your question about this charge?"
[______________________________________________]
[ Send Query ]   [ Cancel ]
```

Posts to `portal.invoices.dispute` with an additional `line_item_id` and `reason` parameter.
If `line_item_id` is set, the backend generates a targeted dispute ticket with the line item
description pre-populated in the ticket subject.

---

## 3.4 End-User — Intent-First Support Intake

`Modules/ClientPortal/resources/views/support/index.blade.php`

Replace the single modal form with a two-step intent-first flow:

**Step 1 — What do you need?**

Three large cards (full-width on mobile, 1/3 grid on desktop):
```
[ Something is broken ]    [ I need something new ]    [ I have a question ]
```
Clicking a card selects the intent and advances to Step 2.

**Step 2 — Describe it**

The form adapts based on intent:
- "Something is broken" → `Subject` (pre-labelled "What's broken?"), description, optional screenshot upload
- "I need something new" → Show pre-approved **Software/Hardware Catalog** if SoftwareSubscriptions module active, otherwise free-text
- "I have a question" → Just subject + description (priority set to `low` automatically)

Intent maps to a hidden `category` field sent to the backend.
`SupportController::store()` maps `intent` → `priority` and `category` appropriately:
- `broken` → `high` + maps to `helpdesk` category
- `new_request` → `medium` + maps to `service_request` category
- `question` → `low` + maps to `general` category

The "Priority" dropdown is removed from the client-facing form entirely.

---

## 3.5 End-User — Plain-Language Ticket Status Tracker

`Modules/ClientPortal/resources/views/support/show.blade.php`

**Gap:** Status is still `Status: Open/Pending/Closed` with no "Waiting On" context.

**Fix:** Replace raw status line with a horizontal **progress tracker** (3 nodes):

```
[●]───────────[●]───────────[○]
Received     In Progress   Resolved
```

Node labels and active node derived from:
- `status == 1 (Active)` and `waiting_reason == null` → In Progress active
- `waiting_on_user_id` set to current auth user ID → show callout:
  💬 "We need your help — check the latest reply and respond below."
- `waiting_reason == 'waiting_on_client'` → same callout above
- `waiting_reason == 'waiting_on_vendor'` → "Our vendor is working on this — we'll update you soon."
- `status == 3` → Resolved active, show rating prompt

The `waiting_on_user_id` and `waiting_reason` values are sourced from the `Conversation`
model already hydrated in `SupportController::show()`.

---

## 3.6 Portal Layout — Consolidate to Single Layout File

**Gap:** Views split between `layouts.app` and `layouts.portal`; they are byte-for-byte identical.

**Fix:** Consolidate all client portal views to a single `layouts.portal`. Remove `layouts.app`
or alias it to `portal` to avoid breaking any legacy references.
Update `dashboard.blade.php` and the payments views that currently use `layouts.app`.

---

## Acceptance Criteria

- [ ] Approval detail shows "Sign & Approve" panel for pending quote_approval items
- [ ] Invoice index shows 6-month trend strip with month-over-month delta chip
- [ ] Invoice line-items tab shows per-row "Query this charge" inline form
- [ ] Support intake uses 3-card intent picker, no Priority dropdown visible to user
- [ ] Ticket show page has 3-node status tracker, surfaces "waiting on you" callout
- [ ] All portal views share a single layout file
