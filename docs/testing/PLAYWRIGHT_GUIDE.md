# End-to-End Testing (Playwright)

We use **Playwright** for end-to-end (E2E) testing. This replaces the previous Laravel Dusk setup.

## Quick Start

1. Install dependencies:
```bash
npm install
```

2. Run tests:
```bash
npx playwright test
```

## Test Structure

Tests are located in `tests/e2e/`.

```typescript
import { test, expect } from '@playwright/test';

test('basic test', async ({ page }) => {
  await page.goto('https://example.com');
  await expect(page).toHaveTitle(/Example/);
});
```

## Running Specifically

Run a single file:
```bash
npx playwright test tests/e2e/import.spec.ts
```

Run in UI mode (interactive):
```bash
npx playwright test --ui
```

## Configuration

Configuration is handled in `playwright.config.ts` in the project root.
