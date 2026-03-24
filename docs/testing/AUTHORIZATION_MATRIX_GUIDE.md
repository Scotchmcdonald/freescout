# Authorization Matrix Testing Guide

This project now has a shared pattern for authorization coverage across modules that mix route middleware, model scopes, and policies.

## Shared Test Actors

Use [tests/Traits/MakesRoleActors.php](../../tests/Traits/MakesRoleActors.php) for repeatable user setup.

- `makeAdminUser()` creates a legacy/RBAC admin-equivalent user.
- `makeFinanceUser($company)` creates an internal finance user attached to a company.
- `makeTechnicianUser($company)` creates an internal staff user with approved company access.
- `makeTechnicianWithoutAccess()` creates an internal user with no company attachment.
- `makeClientUser($company, $client)` creates an active client user attached to the company and client.

The trait intentionally writes the legacy `role` column because most authorization helpers (`isAdmin()`, `isFinance()`, `TechnicianScope`) still read that field during the RBAC transition.

## What To Test

Split authorization coverage by enforcement layer.

### Policy-level tests

Use direct policy assertions when the route is already protected by broad middleware and the policy contains the real business rules.

Examples:

- PIB invoice permissions: view, pay, dispute, download.
- Asset CRUD permissions when middleware only distinguishes admin-panel access.

### HTTP-level tests

Use route tests when controller methods call `authorize()` or rely on route model binding plus tenant scopes.

Examples:

- ContractManager contract show and generate-invoice routes.
- AssetManagement asset detail pages after controller-level `authorize()` checks.

## Scope vs Policy

Several models use [app/Scopes/TechnicianScope.php](../../app/Scopes/TechnicianScope.php).

- Scope filtering runs before controller code for route model binding.
- Policy checks run after the model is resolved.
- Tests should expect `404` when a scoped model is invisible before authorization.
- Tests should expect `403` when the model resolves but the policy denies access.

ContractManager contracts currently use route-model binding with `TechnicianScope`, so cross-company technician access should resolve as `404`.

## Billing-specific Notes

PIB admin billing routes are guarded by `can:manage_billing`, but invoice ownership rules live in [Modules/PIB/Policies/InvoicePolicy.php](../../Modules/PIB/Policies/InvoicePolicy.php). Keep invoice matrix coverage at the policy layer unless the route itself starts calling `authorize()`.

## Test Isolation Notes

Avoid global helper function collisions between Pest files. The recurring invoice tests currently define `setupCountersJob()` in more than one file, which causes fatal redeclaration errors when both files are loaded in the same process. Prefer one of these patterns instead:

- move shared helpers into a trait or support file
- wrap helpers in `if (! function_exists(...))`
- replace file-level functions with closures inside `beforeEach()` or `describe()` blocks
