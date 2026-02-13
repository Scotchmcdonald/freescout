# Migration Path: Native Playwright (TypeScript)

**Status:** Proposed / Experimental
**Current State:** Not Adopted (Using Pest/Dusk)

## Executive Summary

Moving to Native Playwright (TypeScript) offers significant performance and debugging benefits but introduces a "Black Box" data problem. We currently use Pest (PHP) because it allows direct usage of Laravel Factories.

This document outlines the architectural changes required if the team decides to migrate to Native Playwright in the future.

## Pros & Cons

| Feature | Current Stack (Pest/Dusk) | Native Playwright (TS) |
| :--- | :--- | :--- |
| **Speed** | 🐢 Slower (Selenium/WebDriver) | ⚡ Fast (WebSocket Protocol) |
| **Stability** | ⚠️ Can be flaky with JS updates | ✅ Auto-wait mechanism |
| **Debugging** | 📸 Screenshots only | 🎥 Trace Viewer (Time travel) |
| **Data Seeding** | ✅ **Native** (`User::factory()`) | ❌ **API Only** (Black Box) |
| **Language** | PHP | TypeScript / JavaScript |

## The Data Seeding Challenge

In Pest, we do this:
```php
// Instant, synchronous DB insertion
$company = Company::factory()->create();
$user = User::factory()->for($company)->create();
```

In Native Playwright, the test runs in Node.js and **cannot** touch the PHP database classes.
To migrate, we would need to implement **Test Data APIs**:

```typescript
// Playwright (TS)
const response = await request.post('/api/test-support/seed/company');
const company = await response.json();
```

## Migration Roadmap

If we decide to upgrade, follow these steps:

### Phase 1: Infrastructure Preparation
1.  **Create "Test Support" Routes:**
    *   Create a set of API endpoints only enabled when `APP_ENV=testing`.
    *   Endpoints: `POST /_testing/login`, `POST /_testing/seed/user`, `POST /_testing/reset-db`.
2.  **Install Playwright Properly:**
    *   `npm install @playwright/test`
    *   Configure `playwright.config.ts`.

### Phase 2: Pilot Migration
1.  Select **one** critical flow (e.g., "Create Ticket").
2.  Write the spec in TS using the new API endpoints for setup.
3.  Compare execution time and reliability vs the Pest version.

### Phase 3: Transition
1.  Freeze new Pest browser tests.
2.  Port existing suites module by module.
3.  Set up CI pipeline to run `npx playwright test`.

## Decision Criteria

**Do NOT migrate if:**
*   The team is primarily PHP-focused and uncomfortable with TypeScript.
*   The application relies heavily on complex server-side state setup that is hard to expose via API.

**DO migrate if:**
*   Test flakiness becomes unmanageable.
*   Test suite runtime exceeds acceptable CI limits (>20 mins).
*   Frontend complexity (SPAs, heavy React/Vue) outpaces Dusk's ability to interact with it.
