# Phase 2: Flaky Test Fixes — Unique Constraint + Brittle Output Assertion

**KPI affected:** Test Reliability (current 45/100 → target 55/100)
**Effort:** ~1 hour
**Risk:** Low — surgical factory and assertion changes; no business logic touched

---

## Problem Statement

Two integration tests fail intermittently when run sequentially (non-parallel), or when factory ID sequences collide across parallel workers that share a DB checkpoint:

| # | File | Line | Failure mode |
|---|------|------|-------------|
| 1 | `tests/Integration/SoftwareSubscriptions/VendorReconciliationServiceTest.php` | 31 | `UniqueConstraintViolationException` on `(subscription_id, assignable_type, assignable_id)` |
| 2 | `tests/Integration/Console/Commands/LogoutUsersCommandTest.php` | 94 | `assertOutputContains('Deleted sessions:')` — brittle against output format changes |

---

## Fix 1 — `VendorReconciliationServiceTest.php`

### Root Cause

`VendorReconciliationServiceTest` calls `SoftwareAssignment::factory()->forSubscription($subscription)->create()` at line 31. When multiple tests in the class run sequentially in a transaction-isolated context, the `forSubscription()` factory state sets `assignable_type` + `assignable_id` based on a factory-sequence integer. If the integer sequence resets between workers or re-runs, two factories produce the same `(subscription_id, assignable_type, assignable_id)` triple, violating the unique index.

### Current Code (lines 28–33)

```php
$subscription = $this->createSubscription($client->id, 'SKU-RECON-1', 'Recon Product', 5);

SoftwareAssignment::factory()->forSubscription($subscription)->create(['revoked_at' => null]);
SoftwareAssignment::factory()->forSubscription($subscription)->create(['revoked_at' => null]);
SoftwareAssignment::factory()->forSubscription($subscription)->create(['revoked_at' => now()]);
```

### Fix

Inspect the `forSubscription()` factory state and either:

**(a)** Add explicit unique `assignable_id` values in the `create()` call so the caller controls uniqueness:

```php
$subscription = $this->createSubscription($client->id, 'SKU-RECON-1', 'Recon Product', 5);

// Provide distinct assignable_ids to guarantee the unique index (subscription_id, assignable_type, assignable_id) is never violated
SoftwareAssignment::factory()->forSubscription($subscription)->create(['revoked_at' => null,  'assignable_id' => 1]);
SoftwareAssignment::factory()->forSubscription($subscription)->create(['revoked_at' => null,  'assignable_id' => 2]);
SoftwareAssignment::factory()->forSubscription($subscription)->create(['revoked_at' => now(), 'assignable_id' => 3]);
```

**(b)** Or, if `assignable_id` is a foreign key, create the assignable models explicitly and pass their IDs:

```php
$users = \App\Models\User::factory()->count(3)->create();

SoftwareAssignment::factory()->forSubscription($subscription)->create([
    'revoked_at'      => null,
    'assignable_type' => \App\Models\User::class,
    'assignable_id'   => $users[0]->id,
]);
SoftwareAssignment::factory()->forSubscription($subscription)->create([
    'revoked_at'      => null,
    'assignable_type' => \App\Models\User::class,
    'assignable_id'   => $users[1]->id,
]);
SoftwareAssignment::factory()->forSubscription($subscription)->create([
    'revoked_at'      => now(),
    'assignable_type' => \App\Models\User::class,
    'assignable_id'   => $users[2]->id,
]);
```

> **Choose the approach that matches the `forSubscription()` factory contract.** Check `Modules/SoftwareSubscriptions/Database/Factories/SoftwareAssignmentFactory.php` for the `forSubscription` state definition to understand what `assignable_type` defaults to.

### Verification

```bash
# Run the test 5 times sequentially to confirm no collision
for i in {1..5}; do
  php artisan test tests/Integration/SoftwareSubscriptions/VendorReconciliationServiceTest.php --no-parallel
done
```

---

## Fix 2 — `LogoutUsersCommandTest.php`

### Root Cause

Line 94:
```php
$this->artisan('freescout:logout-users')
    ->expectsOutputToContain('Deleted sessions:')
    ->assertSuccessful();
```

`expectsOutputToContain` performs a substring match against the command's stdout buffer. This is brittle because:
1. The text `'Deleted sessions:'` is coupled to the command's current phrasing. Any copy change (e.g. "Sessions deleted:" or adding a count prefix) silently breaks this test.
2. The test mixes output-format verification with behavioral verification — those are separate concerns.

### What the test *should* verify

The intent is: "the command successfully deletes session files." The output line is an incidental detail. The correct assertion is: **the session files no longer exist after the command runs** (already true via the `->assertSuccessful()` call plus file-deletion logic), AND if output is important, assert on the exit code + count rather than the human-readable label.

### Fix (lines 88–96)

```php
// Create multiple test session files
for ($i = 0; $i < 3; $i++) {
    $testFile = $sessionPath.'/test_session_'.uniqid().'_'.$i;
    File::put($testFile, 'test session data '.$i);
}

$this->artisan('freescout:logout-users')
    ->assertSuccessful();

// Assert the session files were actually removed — this is the real behavioral contract
$remainingFiles = File::files($sessionPath);
$this->assertEmpty($remainingFiles, 'Expected all session files to be deleted by the command');
```

If confirming some output line is a hard requirement, assert the **count token** instead of the label:

```php
$this->artisan('freescout:logout-users')
    ->expectsOutputToContain('3')   // the count of deleted files, not the label
    ->assertSuccessful();
```

### Verification

```bash
# Run the test 5 times sequentially
for i in {1..5}; do
  php artisan test tests/Integration/Console/Commands/LogoutUsersCommandTest.php --no-parallel
done
```

---

## Files Changed

| File | Change |
|------|--------|
| `tests/Integration/SoftwareSubscriptions/VendorReconciliationServiceTest.php` | Lines 29–31: explicit `assignable_id` values in `create()` calls |
| `tests/Integration/Console/Commands/LogoutUsersCommandTest.php` | Lines 92–95: replace `expectsOutputToContain` with `assertEmpty(File::files($sessionPath))` |

---

## Done When

- [ ] Both tests pass in 5 consecutive sequential runs (`--no-parallel`)
- [ ] Both tests pass in the full parallel suite (`--parallel --processes=10`)
- [ ] No new test files added — fixes are minimal and surgical
