# Comprehensive UX/UI Audit Report

**Date:** February 9, 2026  
**Auditor:** Senior Staff Product Designer & Frontend Architect  
**Source of Truth:** `docs/development/UX_STYLE_GUIDE.md`  
**Golden Template:** `Modules/EmailMigration`

---

## Executive Summary

This audit evaluates **14 modules** against the application's UX Style Guide and the EmailMigration golden template. The findings reveal a **two-tier quality system**: modules built recently (EmailMigration, GoogleAdmin, Action1) meet or exceed enterprise standards, while legacy modules (CRM clients, ContractManager, PIB) contain significant deviations that erode user confidence and workflow efficiency.

### Module Scorecard

| Module | Grade | Critical | High | Medium | Low | Primary Gap |
|--------|:-----:|:--------:|:----:|:------:|:---:|-------------|
| EmailMigration | **A+** | 0 | 0 | 0 | 0 | Golden template — no issues |
| GoogleAdmin | **A** | 0 | 0 | 1 | 1 | Native `confirm()` on destructive action |
| Action1 | **A** | 0 | 0 | 1 | 1 | Minor color tokens |
| DevFeedback | **B-** | 0 | 1 | 2 | 1 | Hardcoded button color, CDN dependency |
| AssetManagement | **B** | 0 | 1 | 2 | 1 | No CSS vars, no dark mode |
| Payment | **C+** | 1 | 3 | 4 | 3 | Component has no dark mode, hardcoded colors |
| ClientPortal | **C** | 1 | 2 | 3 | 1 | Broken layout reference, mixed color systems |
| KnowledgeBase | **C-** | 0 | 5 | 7 | 4 | Dead code, missing views, no tests |
| CRM | **D+** | 0 | 5 | 4 | 2 | Two design systems coexist, no module nav |
| PIB | **D** | 0 | 5 | 7 | 3 | Largest module, most issues, no dark mode |
| SoftwareSubscriptions | **D** | 1 | 3 | 3 | 2 | Layout chaos, placeholder pages |
| ContractManager | **D-** | 3 | 9 | 7 | 4 | Broken HTML, model queries in Blade, zero accessibility |
| Alerts | **F** | 1 | 0 | 0 | 1 | Non-functional placeholder |
| WidgetRegistry | **F** | 1 | 0 | 0 | 0 | No view files exist |

---

## The Golden Template: EmailMigration Patterns

Before diving into per-module findings, here are the **mandatory patterns** every module must replicate, extracted from the EmailMigration reference implementation:

### Pattern Checklist

| # | Category | Required Pattern |
|---|----------|-----------------|
| 1 | **Layout** | Module-level master layout (`modulename::layouts.master`) wrapping `@yield('module-content')` |
| 2 | **Module Nav** | Persistent top tab bar with SVG icons, `Route::is()` active detection, mobile `<select>` fallback |
| 3 | **Colors** | Semantic tokens only: `primary`, `success`, `warning`, `danger`, `gray`, `info`. CSS vars (`--theme-*`) for theming |
| 4 | **Alpine.js** | Function-return pattern (`function componentName() { return {...} }`), scoped to page sections |
| 5 | **Tabs** | `border-b-2` underline + icon + text, `:class` or `routeIs()` for active state with `x-transition` |
| 6 | **Cards** | `rounded-xl shadow-sm border overflow-hidden` with header/body split |
| 7 | **Stats** | Grid blocks with `border-l-4` accent, `text-2xl font-bold` value + `text-sm text-gray-600` label |
| 8 | **Progress** | Linear `h-2.5 rounded-full` bars + SVG circular gauges for scores |
| 9 | **Wizards** | Step indicator bar + `x-show="currentStep === N"` + `x-transition` animations |
| 10 | **Forms** | `rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500 py-3` |
| 11 | **Buttons** | Primary/secondary/danger variants with 3-state async (`idle → loading → success`) |
| 12 | **Alerts** | `border-l-4` + icon + title/message structure |
| 13 | **Tables** | `min-w-full divide-y` with `bg-gray-50` header, `overflow-x-auto` wrapper, mobile card fallback |
| 14 | **Modals** | Fixed full-screen overlay + centered card + backdrop + `role="dialog"` + `aria-modal="true"` |
| 15 | **Loading** | Universal SVG spinner, `disabled:opacity-50` on buttons, `animate-ping` for live indicators |
| 16 | **Empty States** | Dashed border box with centered icon + `text-gray-500` message + CTA |
| 17 | **Errors** | Flash session banners, `$errors->any()` list with `border-l-4 border-danger-600`, inline `x-show` |
| 18 | **Responsive** | Mobile `<select>` for tabs, `hidden md:block` table/card swap, `grid-cols-1 md:grid-cols-N` |
| 19 | **Real-time** | Polling + Echo WebSocket + `animate-ping` live dot where applicable |
| 20 | **Accessibility** | `role`, `aria-label`, `aria-current`, `scope="col"`, `sr-only`, visible `focus:ring` |

---

## Module-by-Module Audit

---

### 1. CRM Module

**Files Audited:** 21 Blade templates, 4 controllers  
**Test Coverage:** 11 module tests (unit/integration), 3 browser tests (`CrmFeaturePestTest`, `ClientApprovalPestTest`, `ClientTicketInteractionPestTest`), 3 cross-module integration tests

#### Current State

The CRM module has a **split personality**: newer views (`fields/*`, `customer_fields.blade.php`, `ajax_html/*`) use proper CSS custom properties and Alpine.js, while legacy views (`clients/*`, `portal/tickets.blade.php`) use hardcoded Tailwind colors and lack interactivity.

#### UX Friction Points

| # | Issue | Severity | Files Affected |
|---|-------|:--------:|----------------|
| 1 | **Two design systems coexist** — `indigo-600`/`green-100`/`red-800` alongside `var(--theme-*)` | HIGH | `clients/*`, `portal/tickets.blade.php`, `fields/edit.blade.php` |
| 2 | **No module-level tab navigation** — no way to navigate between Clients, Fields, Custom Fields | HIGH | All views |
| 3 | **Missing flash message display** on client index/show — user gets no feedback after creating a client | HIGH | `clients/index.blade.php`, `clients/show.blade.php` |
| 4 | **No empty state** on client list — empty table renders with blank `<tbody>` | HIGH | `clients/index.blade.php` |
| 5 | **Modal lacks ARIA dialog roles** — no `role="dialog"`, no `aria-modal`, no focus trap | HIGH | `clients/show.blade.php` |
| 6 | **Mixed layout approach** — `<x-app-layout>` vs `@extends('layouts.app')` within same module | MEDIUM | Cross-module |
| 7 | **Client table not scrollable** on mobile — overflow breaks below 640px | MEDIUM | `clients/index.blade.php` |
| 8 | **No loading states** on client create, contact modal submit, field edit | MEDIUM | Multiple |
| 9 | **No searchable selects** for custom field dropdowns with potentially large option sets | MEDIUM | `partials/custom_fields_renderer.blade.php` |
| 10 | **Import wizard lacks step indicators** and transition animations | LOW | `ajax_html/import.blade.php` |
| 11 | **Inconsistent delete confirmations** — styled modal vs native `confirm()` | LOW | `fields/index.blade.php` vs `ajax_html/delete_customer.blade.php` |

#### Proposed Enhancements

1. **Create `crm::layouts.master`** with persistent tab bar: Clients | Fields | Import/Export
2. **Migrate all `clients/*` views** to semantic color tokens and CSS vars
3. **Add `@forelse`/`@empty`** with dashed-border empty state to `clients/index.blade.php`
4. **Wrap client table** in `<div class="overflow-x-auto">` with mobile card fallback
5. **Add flash message blocks** to `clients/index.blade.php` and `clients/show.blade.php`
6. **Refactor contact modal** with `role="dialog"`, `aria-modal="true"`, focus trap
7. **Add 3-state submit buttons** (idle → loading → success) to all forms

#### Risk to Existing Tests

| Test Suite | Risk | Mitigation |
|-----------|------|------------|
| `CrmFeaturePestTest` (Browser) | **MEDIUM** — Tests click "Customers" nav and interact with client CRUD. Tab navigation changes may affect selectors. | Ensure tab labels match existing nav text. Run full suite after nav changes. |
| `ClientApprovalPestTest` (Browser) | **LOW** — Tests approval workflow, not layout. | Color/layout changes won't affect logic selectors. |
| `ClientServicePestTest` (Feature) | **NONE** — Backend service tests, no view coupling. | Safe. |
| `ClientTicketInteractionPestTest` (Browser) | **LOW** — Tests ticket interactions, may reference show page elements. | Verify modal selectors still match after ARIA additions. |

---

### 2. ContractManager Module

**Files Audited:** 13 Blade templates, 7 controllers (3 web + 4 API)  
**Test Coverage:** 1 module test (`BillingTemplateInvoicePestTest`), 6 browser tests (`ContractLifecyclePestTest`, `QuoteApprovalPestTest`, `QuoteCreationPestTest`, `QuoteLifecyclePestTest`, `BillingTemplateManagementPestTest`, `ContractInvoiceGenerationPestTest`)

#### Current State

The ContractManager is the **most problematic module** from a code quality perspective. It contains broken HTML, model queries embedded in Blade templates, zero accessibility attributes, and no loading states anywhere.

#### UX Friction Points

| # | Issue | Severity | Files Affected |
|---|-------|:--------:|----------------|
| 1 | **Broken HTML** — missing closing `>` on `<div>` tag | CRITICAL | `contracts/show.blade.php` L49 |
| 2 | **Broken HTML** — missing `</div>` causing form layout break | CRITICAL | `contracts/edit.blade.php` L22-23 |
| 3 | **Model queries in Blade** — `Client::all()` and `BillingTemplate::all()` called directly in template | CRITICAL | `contracts/create.blade.php` L50-55, L65-70 |
| 4 | **No module-level tab navigation** — Contracts, Quotes, Billing Templates are siloed | HIGH | All views |
| 5 | **Hardcoded `indigo-*` colors** on 14 form inputs + 1 submit button | HIGH | `contracts/create.blade.php` |
| 6 | **All `<select>` elements are plain HTML** — Client select loads ALL clients with no search | HIGH | `contracts/create.blade.php`, `quotes/create.blade.php` |
| 7 | **Zero loading/processing states** on any submit button across all views | HIGH | All form views |
| 8 | **No responsive table wrapper** — tables overflow on mobile | HIGH | All index views |
| 9 | **Missing `$errors->any()` validation display** on edit views | HIGH | `contracts/edit.blade.php`, `quotes/edit.blade.php`, `billing-templates/edit.blade.php` |
| 10 | **Zero accessibility attributes** — no `aria-label`, `role`, `sr-only` anywhere | HIGH | All views |
| 11 | **No focus trap on modals** — Tab key escapes modal container | HIGH | `contracts/show.blade.php` |
| 12 | **Hardcoded price** `value="200.00"` in price override field | HIGH | `contracts/create.blade.php` L75 |
| 13 | **Three different modal toggle mechanisms** — `classList`, `style.display`, Alpine `x-show` | MEDIUM | Mixed across views |
| 14 | **No breadcrumbs** on any page | MEDIUM | All views |
| 15 | **Duplicate success message** display in contracts/show | MEDIUM | `contracts/show.blade.php` L187-189 |
| 16 | **Alpine.js used in only 1 view** — rest uses vanilla JS `onclick` handlers | MEDIUM | Cross-module |
| 17 | **Inconsistent modal patterns** — styled modals vs native `confirm()` | LOW | Mixed |

#### Proposed Enhancements

1. **Fix critical HTML bugs immediately** in `contracts/show.blade.php` and `contracts/edit.blade.php`
2. **Move model queries to controllers** — pass `$clients` and `$billingTemplates` from `ContractController::create()`
3. **Create `contractmanager::layouts.master`** with persistent tab bar: Contracts | Quotes | Billing Templates
4. **Replace all `indigo-*` with semantic `primary-*`** across all views
5. **Add searchable select component** (Tom Select or Alpine-powered) for Client and Template selectors
6. **Wrap all tables** in `<div class="overflow-x-auto">` with mobile card fallback
7. **Add Alpine.js-powered submit buttons** with idle → loading → success states
8. **Add `$errors->any()` blocks** and per-field `@error` directives to all edit views
9. **Standardize modals** on Alpine `x-show` pattern with `role="dialog"`, `aria-modal`, focus trap

#### Risk to Existing Tests

| Test Suite | Risk | Mitigation |
|-----------|------|------------|
| `ContractLifecyclePestTest` (Browser) | **HIGH** — Tests interact with create/edit/show flows. HTML fixes and form restructuring may change selectors. | Run this suite first after every change. Map existing selectors before refactoring. |
| `QuoteCreationPestTest` (Browser) | **MEDIUM** — Tests quote creation form. Moving to searchable selects changes `<select>` interaction pattern. | Ensure searchable select still responds to Dusk `select()` or update test to use `type()` for search. |
| `QuoteApprovalPestTest` (Browser) | **LOW** — Tests approval workflow logic, not form inputs. | Safe for layout changes. |
| `BillingTemplateManagementPestTest` (Browser) | **MEDIUM** — Tests billing template CRUD. Table restructuring may affect row selectors. | Maintain `data-testid` attributes on key elements. |
| `BillingTemplateInvoicePestTest` (Feature) | **NONE** — Backend feature test. | Safe. |

---

### 3. KnowledgeBase Module

**Files Audited:** 6 Blade templates (+ 1 dead layout), 4 controllers  
**Test Coverage:** 0 module tests, 1 browser test (`KnowledgeBasePestTest`)

#### Current State

A partially-built module with orphaned code and missing view templates. The existing views are functional but deviate from the Style Guide in color usage and lack loading states.

#### UX Friction Points

| # | Issue | Severity | Files Affected |
|---|-------|:--------:|----------------|
| 1 | **5 missing view templates** referenced by controllers: `articles.index`, `articles.show`, `forks.create`, `forks.merge`, `search.results` | HIGH | `ArticleController`, `ArticleForkController`, `ArticleSearchController` |
| 2 | **Dead code** — `layouts/master.blade.php` is an orphaned standalone HTML layout never used | HIGH | `layouts/master.blade.php` |
| 3 | **No module-level tab navigation** | HIGH | All views |
| 4 | **No loading/processing states** on form submit buttons | HIGH | `articles/create.blade.php`, `articles/edit.blade.php` |
| 5 | **No delete functionality** — no route, no controller method, no UI for article deletion | HIGH | `web.php` |
| 6 | **Hardcoded `bg-green-600`** on "New Article" button | MEDIUM | `index.blade.php` L15 |
| 7 | **Hardcoded `focus:ring-indigo-500`** on Edit button | MEDIUM | `show.blade.php` L62 |
| 8 | **Hardcoded blue info alert** colors | MEDIUM | `explore.blade.php` L112-120 |
| 9 | **Plain `<select>` for categories** — needs searchable dropdown | MEDIUM | `articles/create.blade.php` L41, `articles/edit.blade.php` L43 |
| 10 | **`old()` values not wired** in create form — validation failure loses user input | MEDIUM | `articles/create.blade.php` |
| 11 | **Explore tabs are a pseudo-SPA anti-pattern** — Alpine sets visual state then immediately full-page navigates | MEDIUM | `explore.blade.php` |
| 12 | **SVGs lack `aria-hidden="true"`** | MEDIUM | All views |
| 13 | **Tabs not responsive** on mobile — no overflow or stacking | MEDIUM | `explore.blade.php` L29 |
| 14 | **No breadcrumbs** on create, edit, explore views | LOW | Multiple |

#### Proposed Enhancements

1. **Delete orphaned `layouts/master.blade.php`**
2. **Create proper `knowledgebase::layouts.master`** with tab bar: Articles | Categories | Explorer
3. **Build missing views** or remove dead controller methods
4. **Add article deletion** with confirmation modal
5. **Fix explore tabs** — either make them true SPA with Alpine content switching or remove Alpine wrapper
6. **Add searchable category select** component
7. **Wire `old()` values** through Alpine `x-init` for validation persistence

#### Risk to Existing Tests

| Test Suite | Risk | Mitigation |
|-----------|------|------------|
| `KnowledgeBasePestTest` (Browser) | **MEDIUM** — Tests basic KB workflow. Adding module nav and fixing tabs may change page structure. | Run after each view change. Ensure article CRUD selectors are preserved. |

---

### 4. Payment Module

**Files Audited:** 2 Blade templates, 2 controllers  
**Test Coverage:** 3 module tests, 2 browser tests (`PaymentProcessingE2EPestTest`, `PaymentProcessingPestTest`)

#### Current State

The audit dashboard (`audit/index.blade.php`) is well-built with semantic colors and accessibility. The `payment-history` Blade component is the weak point — hardcoded colors, no dark mode, poor accessibility.

#### UX Friction Points

| # | Issue | Severity | Files Affected |
|---|-------|:--------:|----------------|
| 1 | **No dark mode** on payment-history component — unusable in dark theme | CRITICAL | `components/payment-history.blade.php` |
| 2 | **11 hardcoded color violations** — `text-blue-600`, `bg-green-100`, `bg-red-100`, `bg-purple-100` | HIGH | `components/payment-history.blade.php` L65-135 |
| 3 | **Hardcoded "Operational" status** — doesn't reflect actual integration health | HIGH | `audit/index.blade.php` L78 |
| 4 | **Zero Alpine.js** — filter form triggers full-page refresh | HIGH | `audit/index.blade.php` L93 |
| 5 | **No loading state** on filter submit button | MEDIUM | `audit/index.blade.php` L116 |
| 6 | **No flash/validation error display** in audit view | MEDIUM | `audit/index.blade.php` |
| 7 | **7-column table not mobile-friendly** — no responsive card fallback | MEDIUM | `components/payment-history.blade.php` |
| 8 | **Poor accessibility** on payment-history — no `aria-label`, no `aria-hidden` on SVGs | MEDIUM | `components/payment-history.blade.php` |
| 9 | **Metric card SVGs missing `aria-hidden="true"`** | LOW | `audit/index.blade.php` L37-76 |

#### Proposed Enhancements

1. **Add comprehensive `dark:` classes** to `payment-history.blade.php`
2. **Replace all hardcoded colors** with semantic tokens (`success`, `warning`, `danger`, `info`)
3. **Make "Operational" status dynamic** — pull from actual health check endpoint
4. **Add Alpine.js** for live filter + auto-refresh polling on the audit dashboard
5. **Add `aria-hidden="true"`** to all decorative SVGs

#### Risk to Existing Tests

| Test Suite | Risk | Mitigation |
|-----------|------|------------|
| `PaymentProcessingE2EPestTest` (Browser) | **LOW** — Tests payment processing flow, not audit dashboard layout. | Safe for all proposed changes. |
| `PaymentProcessingPestTest` (Browser) | **LOW** — Billing-focused, not audit UI. | Safe. |
| `WebhookHandlingPestTest` (Feature) | **NONE** — Backend webhook processing. | Safe. |

---

### 5. PIB (Professional Invoicing & Billing) Module

**Files Audited:** ~15 Blade templates, multiple controllers  
**Test Coverage:** 5 module tests, 8 browser tests (`InvoiceGenerationPestTest`, `ServiceUsagePestTest`, `AssetCreditLedgerPestTest`, `BillingCyclePestTest`, `PlanOverridesPestTest`, `ProjectMilestonesPestTest`, `RentToOwnPestTest`, `TicketBillingPestTest`)

#### Current State

The largest billing module with the most views and the most issues. A few views (`templates/create.blade.php`, `service-usage/show.blade.php`) have good Alpine patterns, but the majority use hardcoded colors, vanilla JS, and lack accessibility.

#### UX Friction Points

| # | Issue | Severity | Files Affected |
|---|-------|:--------:|----------------|
| 1 | **Mixed layout patterns** — `<x-app-layout>` vs `@extends('layouts.app')` | HIGH | Cross-module |
| 2 | **No module-level tab navigation** spanning 6 sub-sections | HIGH | All views |
| 3 | **Hardcoded colors** — `bg-indigo-600`, `bg-green-600`, `text-indigo-600` throughout | HIGH | `invoices/*`, `credit-ledger/*`, `templates/*` |
| 4 | **No loading states** on 5+ form submit buttons | HIGH | `adjustments/create`, `invoices/create`, `service-usage/create`, `payments/create` |
| 5 | **Vanilla JS** `document.getElementById` instead of Alpine.js in invoice creation | HIGH | `invoices/create.blade.php` |
| 6 | **No dark mode support** in any view | MEDIUM | Entire module |
| 7 | **Destructive actions use native `confirm()`** instead of styled modals | MEDIUM | Adjustments, invoices |
| 8 | **`credit-ledger/show.blade.php` uses native `<dialog>` element** — inconsistent with all other modules | MEDIUM | `credit-ledger/show.blade.php` |
| 9 | **Missing empty states** in credit-ledger and service-usage tables | MEDIUM | `credit-ledger/show.blade.php`, `service-usage/index.blade.php` |
| 10 | **Inconsistent component usage** — `<x-data-table>` in some views, raw `<table>` in others | MEDIUM | Cross-module |
| 11 | **Forms lack `aria-describedby`** linking to error messages | MEDIUM | All form views |
| 12 | **Literal `\n` escape characters** rendered in template | MEDIUM | `service-usage/index.blade.php` |
| 13 | **Plain `<select>` for client dropdown** — potentially hundreds of items | LOW | Multiple forms |

#### Proposed Enhancements

1. **Create `pib::layouts.master`** with tab bar: Invoices | Service Usage | Credit Ledger | Adjustments | Payments | Templates
2. **Standardize all views on `<x-app-layout>`**
3. **Replace all hardcoded colors** with semantic tokens + CSS vars
4. **Rewrite `invoices/create.blade.php`** with Alpine.js reactive data
5. **Add loading states** to all submit buttons
6. **Replace native `<dialog>` and `confirm()`** with Alpine modal pattern
7. **Add comprehensive dark mode** classes

#### Risk to Existing Tests

| Test Suite | Risk | Mitigation |
|-----------|------|------------|
| `InvoiceGenerationPestTest` (Browser) | **HIGH** — Tests invoice creation flow. Alpine.js rewrite of form changes interaction patterns. | Map all existing Dusk selectors before rewrite. Maintain `name` attributes on form fields. |
| `ServiceUsagePestTest` (Browser) | **MEDIUM** — Tests service usage CRUD. Layout restructuring may shift selectors. | Preserve `data-testid` or `name` attributes. |
| `AssetCreditLedgerPestTest` (Browser) | **MEDIUM** — Tests credit ledger. Replacing `<dialog>` with Alpine modal changes interaction. | Ensure new modal has same trigger selectors. |
| `BillingCyclePestTest` (Browser) | **LOW** — Tests cycle logic. | Layout changes minimal risk. |
| All other billing browser tests | **LOW** — Test business logic flows. | Safe for cosmetic changes. |

---

### 6. SoftwareSubscriptions Module

**Files Audited:** ~10 Blade templates, multiple controllers  
**Test Coverage:** 8 module tests, 2 browser tests (`SoftwareSubscriptionPestTest`, `SoftwareAssignmentPestTest`)

#### Current State

Layout chaos: three different layout patterns coexist. The `admin/index.blade.php` is well-built (CSS vars, ARIA, dark mode) and should be the template for the rest. A placeholder "Hello World" index page exists alongside the functional admin views.

#### UX Friction Points

| # | Issue | Severity | Files Affected |
|---|-------|:--------:|----------------|
| 1 | **Placeholder "Hello World" index page** with broken master layout | CRITICAL | `index.blade.php`, `layouts/master.blade.php` |
| 2 | **Three different layout patterns** — `<x-app-layout>`, `@extends('layouts.app')`, `softwaresubscriptions::layouts.master` | HIGH | Cross-module |
| 3 | **Hardcoded `bg-indigo-600`** in catalog and client-index views | HIGH | `admin/catalog.blade.php`, `admin/client-index.blade.php` |
| 4 | **Tables lack ARIA attributes** in catalog and client-index | HIGH | `admin/catalog.blade.php`, `admin/client-index.blade.php` |
| 5 | **No module-level tab navigation** linking catalog, subscriptions, clients | MEDIUM | All views |
| 6 | **admin/edit.blade.php lacks loading state** on submit (while create has one) | MEDIUM | `admin/edit.blade.php` |
| 7 | **No dark mode** on most views (only `admin/index.blade.php` has it) | MEDIUM | All except index |
| 8 | **Potential duplicate flash messages** in show view | MEDIUM | `admin/show.blade.php` |
| 9 | **Plain `<select>` for client dropdown** | LOW | `admin/assign.blade.php` |

#### Proposed Enhancements

1. **Delete placeholder `index.blade.php` and `layouts/master.blade.php`**
2. **Standardize all views on `<x-app-layout>`**
3. **Use `admin/index.blade.php` as the internal golden template** — replicate its CSS var, ARIA, and dark mode patterns to all other views
4. **Add module-level tab bar**: Catalog | Subscriptions | Assignments

#### Risk to Existing Tests

| Test Suite | Risk | Mitigation |
|-----------|------|------------|
| `SoftwareSubscriptionPestTest` (Browser) | **MEDIUM** — Tests subscription CRUD. Layout standardization may shift selectors. | Preserve form field `name` attributes. Run suite after each view change. |
| `SoftwareAssignmentPestTest` (Browser) | **MEDIUM** — Tests assignment flow. Searchable select change alters interaction. | Update test to work with new select component. |

---

### 7. ClientPortal Module

**Files Audited:** ~15 Blade templates, 2 custom layouts  
**Test Coverage:** 2 module tests, 2 browser tests (`PortalAccessPestTest`, `ClientApprovalPestTest`)

#### Current State

The portal has its own layout system (correctly isolated from admin), but quality is split: `approvals/*` views are well-built with CSS vars, while `dashboard`, `support`, and `billing` views hardcode indigo.

#### UX Friction Points

| # | Issue | Severity | Files Affected |
|---|-------|:--------:|----------------|
| 1 | **`billing/credits.blade.php` extends `clientportal::layouts.master` which does not exist** — runtime crash | CRITICAL | `billing/credits.blade.php` |
| 2 | **Mixed color systems** — approvals use CSS vars, rest hardcodes `bg-indigo-600` | HIGH | `dashboard.blade.php`, `support/index.blade.php`, `billing/credits.blade.php` |
| 3 | **Flash messages hardcode `bg-green-50`/`bg-red-50`** in layout | HIGH | `layouts/portal.blade.php` |
| 4 | **No dark mode** in any portal view | MEDIUM | Entire module |
| 5 | **Support ticket form lacks loading state** and `@error` validation | MEDIUM | `support/index.blade.php` |
| 6 | **Portal nav lacks `aria-current="page"`** | MEDIUM | `layouts/portal.blade.php` |
| 7 | **Invoice dispute uses native `confirm()`** | LOW | `invoices/show.blade.php` |

#### Proposed Enhancements

1. **Fix broken layout reference** in `billing/credits.blade.php` → change to `clientportal::layouts.portal`
2. **Migrate all hardcoded colors** to CSS vars using the `approvals/*` pattern
3. **Add flash message semantic colors** in portal layout
4. **Add loading states** to support ticket form

#### Risk to Existing Tests

| Test Suite | Risk | Mitigation |
|-----------|------|------------|
| `PortalAccessPestTest` (Browser) | **LOW** — Tests access control, not layout. | Safe for all proposed changes. |
| `ClientApprovalPestTest` (Browser) | **LOW** — Tests approval flow on well-built views. | Safe — approvals views are not changing. |

---

### 8. GoogleAdmin Module — Grade: A

**Files Audited:** Full view set  
**Test Coverage:** 3 feature tests + 1 unit test

#### Current State

Exemplary quality. Semantic colors, dark mode, ARIA attributes, loading states with async connection testing, proper Alpine patterns. Near-perfect adherence to the Style Guide.

#### UX Friction Points

| # | Issue | Severity | Files Affected |
|---|-------|:--------:|----------------|
| 1 | **Credential deletion uses native `confirm()`** instead of styled modal | MEDIUM | `settings/edit.blade.php` |
| 2 | **Minor: a few `text-gray-*`** instead of semantic neutral classes | LOW | Minor instances |

#### Risk to Existing Tests

| Test Suite | Risk | Mitigation |
|-----------|------|------------|
| All GoogleAdmin tests | **NONE** | No significant changes proposed. |

---

### 9. Action1 Module — Grade: A

**Files Audited:** Full view set  
**Test Coverage:** 4 module tests + browser test + integration test

#### Current State

Matches GoogleAdmin quality. Semantic colors throughout, comprehensive dark mode, accessibility attributes. No changes needed beyond minor polish.

#### UX Friction Points

| # | Issue | Severity | Files Affected |
|---|-------|:--------:|----------------|
| 1 | Minor: token consistency | MEDIUM | Rare instances |
| 2 | Minor: Could add more `sr-only` labels | LOW | Icon buttons |

#### Risk to Existing Tests

| Test Suite | Risk | Mitigation |
|-----------|------|------------|
| `Action1AuditPestTest` (Browser) | **NONE** | No significant changes proposed. |

---

### 10. AssetManagement Module — Grade: B

**Files Audited:** 2 view partials  
**Test Coverage:** 1 browser test (`AssetManagementTest`), 3 backend tests

#### Current State

Both views are clean, functional partials with proper empty states and `@forelse`/`@empty` patterns. Needs CSS var migration and dark mode.

#### UX Friction Points

| # | Issue | Severity | Files Affected |
|---|-------|:--------:|----------------|
| 1 | **Hardcoded `text-indigo-600`, `bg-green-100`, `bg-blue-100`** | HIGH | `portal/index.blade.php`, `widgets/assets.blade.php` |
| 2 | **No CSS vars or dark mode** | MEDIUM | Both views |
| 3 | **No ARIA roles on asset cards** | MEDIUM | `portal/index.blade.php` |
| 4 | Minor structural polish | LOW | — |

#### Risk to Existing Tests

| Test Suite | Risk | Mitigation |
|-----------|------|------------|
| `AssetManagementTest` (Browser) | **LOW** — Tests asset CRUD. Color changes don't affect selectors. | Safe. |

---

### 11. DevFeedback Module — Grade: B-

**Files Audited:** 2 view files  
**Test Coverage:** 1 browser test (`DevFeedbackPestTest`)

#### Current State

Small, focused module. The floating feedback button component is well-built with Alpine.js state management. Settings view is clean.

#### UX Friction Points

| # | Issue | Severity | Files Affected |
|---|-------|:--------:|----------------|
| 1 | **Button uses hardcoded `bg-blue-600`** | HIGH | `button.blade.php` |
| 2 | **Submit button hardcodes `bg-gray-800`** | MEDIUM | `settings.blade.php` |
| 3 | **External CDN for Quill.js** — should be bundled via Vite | MEDIUM | `button.blade.php` |
| 4 | Minor: settings view lacks dark mode | LOW | `settings.blade.php` |

#### Risk to Existing Tests

| Test Suite | Risk | Mitigation |
|-----------|------|------------|
| `DevFeedbackPestTest` (Browser) | **LOW** — Tests feedback submission. Color changes safe. | CDN → Vite change needs build verification. |

---

### 12. Alerts Module — Grade: F

**Files Audited:** 1 placeholder  
**Test Coverage:** 1 browser test (`AlertSubscriptionPestTest`), 2 unit tests

#### Current State

Non-functional. The module consists of a "Hello World" placeholder view with a commented-out Vite master layout. All functionality is backend-only (events, listeners, jobs).

#### UX Friction Points

| # | Issue | Severity | Files Affected |
|---|-------|:--------:|----------------|
| 1 | **Entire module is a placeholder** — no functional views | CRITICAL | `index.blade.php`, `layouts/master.blade.php` |

#### Proposed Enhancement

Either build out the alert management UI (subscription settings, alert history, notification preferences) or acknowledge this module is intentionally backend-only and remove the placeholder views.

#### Risk to Existing Tests

| Test Suite | Risk | Mitigation |
|-----------|------|------------|
| `AlertSubscriptionPestTest` (Browser) | **LOW** — Likely tests alert subscription logic, not the placeholder page. | Verify test doesn't navigate to the placeholder route. |

---

### 13. WidgetRegistry Module — Grade: F

**Files Audited:** 0 view files  
**Test Coverage:** 1 browser test (`WidgetRegistryIntegrationPestTest`)

#### Current State

The module has **zero view files**. It operates entirely as a backend service for registering and serving dashboard widgets. No UI audit is possible.

#### UX Friction Points

| # | Issue | Severity | Files Affected |
|---|-------|:--------:|----------------|
| 1 | **No view files exist** — if UI is expected, it's entirely missing | CRITICAL | Module-wide |

#### Risk to Existing Tests

| Test Suite | Risk | Mitigation |
|-----------|------|------------|
| `WidgetRegistryIntegrationPestTest` (Browser) | **NONE** — Tests widget rendering in other module contexts. | Safe. |

---

## Cross-Cutting Findings

### 1. Searchable Select Compliance

**Style Guide Rule:** *"Mandatory use of searchable selects where data sets exceed 5 items."*

| Module | Select Field | Items | Searchable? | Violation? |
|--------|-------------|:-----:|:-----------:|:----------:|
| ContractManager | Client selector | N (all clients) | No | **YES** |
| ContractManager | Billing Template selector | N | No | **YES** |
| CRM | Custom field dropdown choices | Variable | No | **YES** (when >5) |
| KnowledgeBase | Category selector | Variable | No | **YES** (when >5) |
| PIB | Client selector (invoices, service-usage) | N | No | **YES** |
| SoftwareSubscriptions | Client selector (assign) | N | No | **YES** |
| Quotes | Client selector | N | No | **YES** |

**Recommendation:** Implement a shared `<x-searchable-select>` Blade component powered by Alpine.js that can replace all plain `<select>` elements where option count may exceed 5.

### 2. Layout Architecture Consistency

| Pattern | Modules Using It | Standard? |
|---------|-----------------|:---------:|
| `<x-app-layout>` | GoogleAdmin, Action1, DevFeedback, AssetManagement, some SoftwareSubscriptions, some PIB, some CRM | **Preferred** |
| `@extends('layouts.app')` | ContractManager, KnowledgeBase, some CRM, some PIB, some SoftwareSubscriptions | **Acceptable** |
| Module master layout (`module::layouts.master`) | EmailMigration (golden template) | **Ideal for multi-page modules** |
| Custom portal layout | ClientPortal | **Correct for portal** |

**Recommendation:** Modules with 3+ distinct views should adopt the EmailMigration pattern: `module::layouts.master` that extends `layouts.app` and provides module-level tab navigation.

### 3. Dark Mode Coverage

| Status | Modules |
|--------|---------|
| Full dark mode | EmailMigration, GoogleAdmin, Action1 |
| Partial dark mode | SoftwareSubscriptions (1 view only), DevFeedback (1 view) |
| No dark mode | ContractManager, CRM (clients/*), PIB, ClientPortal, AssetManagement, KnowledgeBase, Payment (component) |

### 4. Loading State Coverage

| Status | Modules |
|--------|---------|
| Proper 3-state buttons | EmailMigration, GoogleAdmin, DevFeedback |
| Partial coverage | SoftwareSubscriptions (create only), CRM (fields/create only), PIB (2 views only) |
| No loading states | ContractManager, KnowledgeBase, Payment audit |

---

## Implementation Priority Matrix

### Phase 1: Critical Fixes (Immediate — no UX changes, just bug fixes)

| # | Module | Task | Risk |
|---|--------|------|------|
| 1 | ContractManager | Fix broken HTML in `contracts/show.blade.php` L49, `contracts/edit.blade.php` L22-23 | LOW |
| 2 | ContractManager | Move `Client::all()` and `BillingTemplate::all()` from Blade to controller | LOW |
| 3 | ContractManager | Remove hardcoded `value="200.00"` from price override | LOW |
| 4 | ClientPortal | Fix `billing/credits.blade.php` layout reference → `clientportal::layouts.portal` | LOW |

### Phase 2: Color & Theme Compliance (Low risk — cosmetic only)

| # | Module | Task | Risk |
|---|--------|------|------|
| 5 | CRM | Replace `indigo-*`/`green-*`/`red-*` → semantic tokens in `clients/*`, `portal/tickets` | LOW |
| 6 | ContractManager | Replace `indigo-*` → `primary-*` in `contracts/create.blade.php` (14 inputs) | LOW |
| 7 | PIB | Replace `indigo-*`/`green-*` → semantic tokens across all views | LOW |
| 8 | SoftwareSubscriptions | Align `catalog` and `client-index` colors to `admin/index` CSS var pattern | LOW |
| 9 | Payment | Replace hardcoded colors in `payment-history.blade.php` | LOW |
| 10 | AssetManagement | Replace `indigo-*` → semantic tokens | LOW |
| 11 | KnowledgeBase | Replace `green-600`, `indigo-500`, `blue-*` → semantic tokens | LOW |
| 12 | DevFeedback | Replace `bg-blue-600` → `bg-primary-600` on button | LOW |
| 13 | ClientPortal | Align all views to `approvals/*` CSS var pattern | LOW |

### Phase 3: Structural UX Improvements (Medium risk — affects test selectors)

| # | Module | Task | Risk |
|---|--------|------|------|
| 14 | Build shared `<x-searchable-select>` component | Cross-module | MEDIUM |
| 15 | CRM | Create `crm::layouts.master` with module tab navigation | MEDIUM |
| 16 | ContractManager | Create `contractmanager::layouts.master` with module tab navigation | MEDIUM |
| 17 | PIB | Create `pib::layouts.master` with module tab navigation | MEDIUM |
| 18 | KnowledgeBase | Create `knowledgebase::layouts.master` with module tab navigation | MEDIUM |
| 19 | SoftwareSubscriptions | Create module tab navigation, delete placeholder files | MEDIUM |
| 20 | All modules | Add `overflow-x-auto` wrappers to all tables | LOW |
| 21 | All modules | Add Alpine.js 3-state submit buttons to all forms | LOW |
| 22 | All modules | Add flash message display blocks where missing | LOW |
| 23 | All modules | Add `@forelse`/`@empty` with empty state pattern where missing | LOW |

### Phase 4: Accessibility & Polish (Low risk — additive only)

| # | Module | Task | Risk |
|---|--------|------|------|
| 24 | ContractManager | Add `role="dialog"`, `aria-modal`, focus trap to all modals | LOW |
| 25 | CRM | Add ARIA attributes to client table, modal | LOW |
| 26 | PIB | Add `aria-describedby` to form error associations | LOW |
| 27 | All modules | Add `aria-hidden="true"` to decorative SVGs | LOW |
| 28 | All modules | Add `sr-only` labels to icon-only buttons | LOW |
| 29 | All modules | Add comprehensive dark mode classes where missing | LOW |

### Phase 5: Advanced Patterns (Higher risk — significant behavior changes)

| # | Module | Task | Risk |
|---|--------|------|------|
| 30 | PIB | Rewrite `invoices/create.blade.php` with Alpine.js replacing vanilla JS | HIGH |
| 31 | ContractManager | Standardize all modals on Alpine `x-show` pattern | MEDIUM |
| 32 | KnowledgeBase | Fix explore tab anti-pattern (Alpine + full reload) | MEDIUM |
| 33 | KnowledgeBase | Build missing views (articles.index, articles.show, forks/*, search.results) | HIGH |
| 34 | Payment | Add Alpine.js auto-refresh polling to audit dashboard | MEDIUM |
| 35 | CRM | Add import wizard step indicators and transitions | LOW |

---

## Test Impact Summary

### Tests That Must Be Run After Each Phase

| Phase | Critical Test Suites | Est. Count |
|-------|---------------------|:----------:|
| Phase 1 (Bugs) | `ContractLifecyclePestTest`, `PortalAccessPestTest` | 2 |
| Phase 2 (Colors) | Full browser test suite — cosmetic changes should not break, but verify | All |
| Phase 3 (Structure) | All browser tests for affected modules | ~20 |
| Phase 4 (A11y) | Spot-check 3-4 browser tests per module | ~10 |
| Phase 5 (Advanced) | Full regression of affected module suites | ~15 |

### Existing Test Suites by Module

| Module | Browser Tests | Feature Tests | Unit Tests | Integration Tests |
|--------|:------------:|:-------------:|:----------:|:-----------------:|
| EmailMigration | 1 | — | — | — |
| CRM | 3 | 2 | 6 | 3 |
| ContractManager | 6 | 1 | — | — |
| KnowledgeBase | 1 | 0 | 0 | — |
| Payment | 2 | 2 | 1 | — |
| PIB | 8 | — | 5 | — |
| SoftwareSubscriptions | 2 | 8+ | — | — |
| ClientPortal | 2 | 2 | — | — |
| GoogleAdmin | — | 3 | 1 | — |
| Action1 | 1 | 4 | — | 1 |
| AssetManagement | 1 | 3 | — | — |
| DevFeedback | 1 | — | — | — |
| Alerts | 1 | — | 2 | — |
| WidgetRegistry | 1 | — | — | — |

---

## Appendix: Shared Component Recommendations

To bring all modules into compliance efficiently, the following **shared Blade components** should be built once and reused:

| Component | Purpose | Used By |
|-----------|---------|---------|
| `<x-searchable-select>` | Alpine-powered searchable dropdown with debounced search | All modules with client/entity selects |
| `<x-module-tabs>` | Configurable module-level tab navigation with mobile select fallback | All multi-page modules |
| `<x-submit-button>` | 3-state async button (idle → loading → success) | All form views |
| `<x-confirmation-modal>` | Alpine-powered confirmation modal with `role="dialog"` | All destructive actions |
| `<x-flash-messages>` | Semantic flash message display (success/error/warning/info) | All pages |
| `<x-empty-state>` | Dashed-border empty state with icon + message + CTA | All list/table views |
| `<x-data-table>` | Responsive table with `overflow-x-auto`, mobile card fallback, ARIA | All table views |

---

*This report documents the current state only. No code modifications have been made. Awaiting approval before proceeding with implementation.*
