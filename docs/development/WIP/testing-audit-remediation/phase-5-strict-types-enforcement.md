# Phase 5: Strict-Types Enforcement — Raise Test File Coverage from 61% to 90%+

**KPI affected:** Type Safety Breadth (current 61/100 → target 81/100)
**Effort:** ~2 hours (mostly mechanical; can be scripted)
**Risk:** Very low — `declare(strict_types=1)` is compile-time only; no runtime behavior changes

---

## Problem Statement

**Current state:** 300 of 491 test PHP files have `declare(strict_types=1)` — **61.1%**.

The missing 191 files mean PHP silently coerces scalars during test execution, which can mask real type errors in production code. For example, a service method that returns `int` but is being passed a test value of `'1'` (string) will pass the test via coercion but may fail in strict production code paths.

The `tests/Architecture/BillingPaymentTypeCoverageGuardTest.php` already enforces 100% strict types on `Modules/PIB/Services` and `Modules/Payment/Services`. The test namespace itself should be held to the same standard.

---

## Gap Analysis

```bash
# Find all test PHP files missing declare(strict_types=1)
find tests -name '*.php' -type f \
  | xargs grep -rL 'declare(strict_types=1)' \
  | sort > reports/test-files-missing-strict-types.txt

wc -l reports/test-files-missing-strict-types.txt
# Expected: ~191
```

Then break down by directory:

```bash
awk -F/ '{print $2}' reports/test-files-missing-strict-types.txt | sort | uniq -c | sort -rn
# Shows which suites have the worst coverage
```

---

## Fix Steps

### Step 1: Generate the full gap list

```bash
find tests -name '*.php' -type f \
  | xargs grep -rL 'declare(strict_types=1)' \
  | sort > reports/test-files-missing-strict-types.txt

echo "Missing strict_types in $(wc -l < reports/test-files-missing-strict-types.txt) test files"
```

### Step 2: Script the mechanical fix

The fix is purely mechanical: add `declare(strict_types=1);` after the opening `<?php` tag. A `sed` script handles the bulk:

```bash
# Dry-run first — print what would change
while IFS= read -r file; do
  head -n 3 "$file"
  echo "---"
done < reports/test-files-missing-strict-types.txt | head -n 60
```

```bash
# Apply — insert declare after <?php on line 1, only if not already present
while IFS= read -r file; do
  # Skip if already has declare (double-check)
  if grep -q 'declare(strict_types=1)' "$file"; then
    continue
  fi
  # Insert declare(strict_types=1); after the opening <?php tag
  sed -i '1s|^<?php$|<?php\n\ndeclare(strict_types=1);|' "$file"
done < reports/test-files-missing-strict-types.txt
```

> **Caution:** Some files may have `<?php` followed immediately by a docblock or namespace. Review 10–15 files manually after running the script to confirm formatting is correct.

```bash
# Spot-check 5 random files post-fix
shuf reports/test-files-missing-strict-types.txt | head -n 5 | xargs head -n 5
```

### Step 3: Run the full test suite to confirm no regressions

```bash
php artisan test --parallel --processes=10
```

`declare(strict_types=1)` should produce zero failures in well-typed test code. If any test breaks, it means the test was relying on implicit coercion — which is the behavior we want to catch. Fix those tests by using the correct literal types (e.g. `1` instead of `'1'` for int parameters).

### Step 4: Add an arch rule to prevent regression

Add to `tests/ArchTest.php`:

```php
arch('all test files declare strict types')
    ->expect('Tests')
    ->toUseStrictTypes();
```

> **If this rule catches existing exceptions you cannot immediately fix**, use a ratchet via a baseline file or the `.pest.php` `arch()->ignoring()` mechanism, similar to the existing `CriticalNamespaceBoundaryGuardTest.php` pattern.

**Ratchet approach (if `toUseStrictTypes()` is not available in Pest's arch API):**

```php
it('strict_types coverage in test files meets threshold', function (): void {
    $allTestFiles   = collect(File::allFiles(base_path('tests')))->filter(fn ($f) => $f->getExtension() === 'php');
    $strictFiles    = $allTestFiles->filter(fn ($f) => str_contains(File::get($f->getPathname()), 'declare(strict_types=1)'));
    $ratio          = $strictFiles->count() / $allTestFiles->count();

    expect($ratio)->toBeGreaterThanOrEqual(0.90, sprintf(
        'Expected ≥90%% of test files to declare strict_types, got %.1f%% (%d/%d)',
        $ratio * 100,
        $strictFiles->count(),
        $allTestFiles->count()
    ));
})->group('architecture');
```

This test will fail if the ratio drops back below 90%, acting as a ratchet.

---

## Verification

```bash
# Confirm count after fix
grep -rl 'declare(strict_types=1)' tests --include='*.php' | wc -l
# Target: ≥ 441 (90% of 491)

# Confirm full suite still green
php artisan test --parallel --processes=10
```

---

## Files Changed

| File | Change |
|------|--------|
| Up to 191 test files | Add `declare(strict_types=1);` after `<?php` |
| `tests/ArchTest.php` | New arch rule: all test files declare strict types (threshold 90%) |
| `reports/test-files-missing-strict-types.txt` | **New** (generated, temporary) — gap list; delete after fix |

---

## Done When

- [ ] `grep -rl 'declare(strict_types=1)' tests --include='*.php' | wc -l` returns ≥ 441
- [ ] `php artisan test --parallel --processes=10` is green (all tests pass)
- [ ] New arch rule in `tests/ArchTest.php` enforces ≥ 90% threshold
- [ ] `reports/test-files-missing-strict-types.txt` deleted (it was a working artifact)
- [ ] Type Safety Breadth KPI re-score estimated at 81+/100
