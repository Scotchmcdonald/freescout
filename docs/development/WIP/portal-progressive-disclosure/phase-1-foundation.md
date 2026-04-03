# Phase 1 — Foundation & Security

**Goal:** Establish the backend contracts that make Progressive Disclosure possible and close the tenant-isolation security gap.

---

## 1.1 PortalActionRegistry Service

`Modules/ClientPortal/Services/PortalActionRegistry.php`

A module-scoped registry (analogous to `PortalTabRegistry`) where every module that
contributes portal-visible actions registers those actions with:

| Field | Type | Purpose |
|---|---|---|
| `resource` | string | Machine slug, e.g. `invoices` |
| `action` | string | CRUD or special action key, e.g. `dispute`, `pay`, `approve`, `sign` |
| `label` | string | Plain-English label, e.g. `"Query a charge"` |
| `description` | string | Plain-English description shown in RBAC builder |
| `permission` | string | Gate permission required, e.g. `portal.invoices.dispute` |
| `persona` | array | Which roles should see this by default: `admin`, `finance`, `user` |
| `route` | string | Named portal route |
| `icon` | string|null | HeroIcon string |

Usage by modules:

```php
app(PortalActionRegistry::class)->registerAction(
    resource:    'invoices',
    action:      'pay',
    label:       'Pay an invoice',
    description: 'Allows this user to submit payment against outstanding invoices.',
    permission:  'portal.invoices.pay',
    persona:     ['finance', 'admin'],
    route:       'portal.invoices.pay',
    icon:        'heroicon-o-credit-card',
);
```

Dashboard `Action Center` widget calls `PortalActionRegistry::getActionsForUser()` to
build the "pending items" list, filtering by user permissions.

**Status:** Implement in this phase. Register in `ClientPortalServiceProvider::registerBuiltInTabs()`.

---

## 1.2 ContactPermission — Business-Language Descriptions

`Modules/Crm/Models/ContactPermission.php`

Add `getActionDescriptions()` returning a map of `action_key → plain-English description`
so the admin RBAC matrix legend and the Client Admin "effective permissions" block can
render human-readable toggle captions instead of snake_cased slugs.

```php
public static function getActionDescriptions(): array
{
    return [
        'view_invoices'          => 'Can see past and current invoices',
        'approve_payments'       => 'Can dispute invoices and authorise payment',
        'download_invoices'      => 'Can download PDF copies of invoices',
        'manage_payment_methods' => 'Can add or remove saved payment methods',
        'view_assets'            => 'Can see the hardware inventory assigned to your company',
        'view_tickets'           => 'Can view all support tickets for your company',
        'create_tickets'         => 'Can open new support requests',
        'manage_contacts'        => 'Can add, edit, and deactivate staff in the portal',
        'request_onboarding'     => 'Can request IT setup for a new employee',
    ];
}
```

Also add a `getPermissionDescriptions()` returning a role → plain-English summary, shown
on the Admin matrix as a quick explainer below each column header.

---

## 1.3 Tenant-Safe Bulk Permission Update

`app/Http/Controllers/Admin/PermissionMatrixController.php`

**Current gap:** `bulkUpdate()` validates only `exists:crm_contacts,id`.
A malicious or careless admin could supply a contact_id from a different tenant.

**Fix:**
1. After validation, verify each `contact->client_id` matches the authenticated admin's
   permitted client scope (via `auth()->user()->manageable_client_ids` or a Policy check).
2. Enforce "admin can only assign permissions they already possess" — if the requesting
   user's own role doesn't include `full_access`, they cannot promote someone else to
   `full_access`.
3. Log every bulk permission change to `activity_log` with `causer`, `subject`, `old` and
   `new` attributes.

**Status:** Implement in this phase.

---

## Acceptance Criteria

- [ ] `PortalActionRegistry` exists as a bound singleton, has `registerAction()` and `getActionsForUser()` methods
- [ ] `ContactPermission::getActionDescriptions()` returns human-readable strings for all existing action keys
- [ ] `PermissionMatrixController::bulkUpdate()` throws 403 when a contact_id does not belong to the requesting admin's tenant scope
- [ ] All three classes have type-safe docblocks
