# Phase 3: Unit Base Class Cleanup — Bind `Unit/` to `PureUnitTestCase`

**KPI affected:** Architecture Fitness (current 78/100 → target 86/100), Velocity (current 72/100 → target 80/100)
**Effort:** ~2 hours
**Risk:** Medium — requires auditing 4 legacy Unit files that currently rely on `UnitTestCase` (and therefore `RefreshDatabase`)

---

## Problem Statement

`tests/Pest.php` line 51:
```php
pest()->extend(Tests\UnitTestCase::class)->in('Unit');
```

`Tests\UnitTestCase` extends `Tests\TestCase` which boots the full Laravel application and uses `Illuminate\Foundation\Testing\RefreshDatabase`. This means **every file in `tests/Unit/`** — including the 85 files that already inherit `PureUnitTestCase` directly — pays the framework boot cost for the Pest registration phase.

Additionally, the `UnitFrameworkBootingGuardTest` ratchet (`tests/Unit/UnitFrameworkBootingGuardTest.php`) allows exactly 4 legacy framework-booting Unit files (`max_count=4`). Those 4 files are the only legitimate users of `UnitTestCase` in the Unit suite. The other 85 do not need it.

**Impact:**
- Every Unit test file starts with an unnecessary Laravel app boot registration
- The ratchet guard expires `2026-04-30` with no migration plan attached
- `UnitTestCase` is documented as "temporary exception pending WS-C migration" — but no migration exists yet

---

## Current State

```
tests/Unit/  (89 files)
├── 85 files  →  extend PureUnitTestCase  ✅ correct
└──  4 files  →  extend UnitTestCase      ⚠️  legacy (framework boot required)
```

Global binding in `tests/Pest.php`:
```php
pest()->extend(Tests\UnitTestCase::class)->in('Unit');   // ← all 89 files
```

Ratchet guard: `tests/Unit/UnitFrameworkBootingGuardTest.php`
```php
->max(max_count: 4, expires: '2026-04-30')
```

---

## Fix Steps

### Step 1: Identify the 4 legacy Unit files that need `UnitTestCase`

```bash
# These are the files the ratchet guard is protecting
grep -rl 'UnitTestCase\|RefreshDatabase' tests/Unit --include='*.php' | grep -v 'UnitFrameworkBootingGuardTest'
```

Confirm there are exactly 4. Note their paths — they will receive an explicit `pest()->extend()` override.

### Step 2: Change the global Unit binding to `PureUnitTestCase`

**File:** `tests/Pest.php` line 51

Change:
```php
pest()->extend(Tests\UnitTestCase::class)->in('Unit');
```
To:
```php
pest()->extend(Tests\PureUnitTestCase::class)->in('Unit');
```

### Step 3: Add explicit `pest()->extend()` overrides for the 4 legacy files

In `tests/Pest.php`, immediately after the line changed in Step 2, add one `pest()->extend()` call per legacy file. Use `->in()` with the specific directory path for each, or use file-path include syntax:

```php
// Legacy Unit files that still require framework boot (UnitTestCase + RefreshDatabase)
// TODO: migrate these to PureUnitTestCase as part of the WS-C sprint
// Ratchet guard: tests/Unit/UnitFrameworkBootingGuardTest.php — expires 2026-04-30
pest()->extend(Tests\UnitTestCase::class)->in(
    'Unit/path/to/legacy-dir-1',
    'Unit/path/to/legacy-dir-2',
    // ... (fill in the exact paths from Step 1)
);
```

> **If the 4 files are scattered across different directories**, use individual `->in()` calls per path, not a single broad one:
> ```php
> pest()->extend(Tests\UnitTestCase::class)->in('Unit/SomeSpecificSubdir');
> pest()->extend(Tests\UnitTestCase::class)->in('Unit/AnotherSubdir');
> ```

### Step 4: Run the Unit suite and confirm no regressions

```bash
php artisan test --testsuite=Unit --no-parallel
```

All 89 tests must pass. If any of the 4 legacy files fail with "class not found" or "database not available" errors, they need to be listed more precisely in the `->in()` overrides from Step 3.

### Step 5: Lower the ratchet guard threshold to 0 (optional — after WS-C migration)

Once the 4 legacy files are migrated to `PureUnitTestCase`, update `tests/Unit/UnitFrameworkBootingGuardTest.php`:

```php
// After migration:
->max(max_count: 0, expires: '2026-04-30')
```

And delete the explicit `pest()->extend(Tests\UnitTestCase::class)->in(...)` override from `tests/Pest.php`.

---

## Why This Matters for Architecture Score

The Pest architecture rule in `tests/ArchTest.php` enforces that Unit tests are framework-free. The current global `UnitTestCase` binding means that rule's intent is violated at the registration level, even though individual test classes override it. Correct base class binding at the Pest level makes the architecture self-documenting and removes the ambiguity.

It also directly improves cold-start suite velocity: framework bootstrap is ~80ms per worker process. With 10 workers and the Unit suite split across processes, the current binding adds ~800ms of unnecessary application initialization to every parallel Unit run.

---

## Files Changed

| File | Change |
|------|--------|
| `tests/Pest.php` | Line 51: `UnitTestCase` → `PureUnitTestCase`; add legacy 4-file explicit override |
| `tests/Unit/UnitFrameworkBootingGuardTest.php` | *(optional, post WS-C)* Lower `max_count` from 4 to 0 |

---

## Done When

- [ ] `tests/Pest.php` global Unit binding points to `PureUnitTestCase`
- [ ] 4 legacy files have explicit `pest()->extend(Tests\UnitTestCase::class)` override in `tests/Pest.php`
- [ ] `php artisan test --testsuite=Unit` passes with 0 failures
- [ ] `php artisan test tests/Unit/UnitFrameworkBootingGuardTest.php` still passes (ratchet count unchanged at 4)
