# Remaining Test Failures - Work Distribution Guide

**Date:** November 14, 2025  
**Status:** 51 failures, 2311 passing (5161 assertions)  
**Context:** After fixing 813 tests (94% improvement), these remaining failures are test implementation issues, not infrastructure problems.

---

## Prerequisites & Environment Setup

### ⚠️ CRITICAL: Dependency Installation Issue

**The `vendor/` directory is NOT tracked in git** (it's in `.gitignore`). This creates a chicken-and-egg problem:
- You need dependencies to run tests
- But composer requires GitHub authentication which may not be available in all environments

### Three Scenarios:

#### Scenario A: vendor/ Already Exists (IDEAL)

If your environment already has a populated `vendor/` directory:

```bash
cd /var/www/html

# Check if dependencies exist
ls vendor/laravel/framework/src/Illuminate/Foundation/Application.php

# If that file exists, you're golden! Skip to "Getting Started" section.
php artisan test
```

**If this works, you can skip all the composer stuff below and go directly to fixing tests!**

---

#### Scenario B: Composer Works in Your Environment (LUCKY)

Try these in order:

```bash
cd /var/www/html

# Try 1: Prefer dist (downloads zips, avoids git clone)
composer install --prefer-dist --no-interaction

# Try 2: Skip dev dependencies
composer install --prefer-dist --no-dev --no-interaction --ignore-platform-reqs

# Try 3: With your GitHub personal access token
# Get token from: https://github.com/settings/tokens
composer config -g github-oauth.github.com "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"
composer install --prefer-dist --no-interaction
```

If any of these succeed, proceed to "Getting Started" section.

---

#### Scenario C: Composer Blocked by GitHub Auth (BLOCKED)

**This is a known issue.** Even with `--prefer-dist`, composer falls back to source cloning when dist downloads fail (rate limits/timeouts), which requires GitHub authentication.

**WORKAROUND OPTIONS:**

1. **Request Pre-Populated Environment:**
   - Ask the repository owner to provide an environment with `vendor/` already installed
   - Or run tests in a container/VM where dependencies are pre-installed

2. **Use Packagist Mirror:**
   ```bash
   # Try using a Packagist mirror (may bypass GitHub)
   composer config -g repos.packagist composer https://packagist.org
   composer install --prefer-dist --no-interaction
   ```

3. **Manual Dependency Copy:**
   - If you have access to another environment where composer works
   - Run composer there and copy the `vendor/` directory
   - Transfer it to your blocked environment

4. **Docker/Container Approach:**
   ```bash
   # If Docker is available
   docker run --rm -v $(pwd):/app composer:latest install --prefer-dist --no-interaction
   ```

5. **Skip This Work:**
   - If none of the above work, you're legitimately blocked
   - Report back to the repository owner with the error details
   - Let them know you need either:
     - GitHub token access, OR
     - Pre-populated vendor directory, OR  
     - Different environment with network access

**DO NOT spend hours fighting composer.** If the first few attempts fail, you're blocked and need help from the repo owner.

---

### After Dependencies Are Installed:

Once `vendor/` exists (by any method), you're ready:

1. **Edit test files** in `tests/Unit/` or `tests/Integration/`
2. **Run tests:** `php artisan test`
3. **Commit your fixes** to the test files only (NOT vendor/)
4. **Verify:** `php artisan test` again

#### Environment Requirements:

- PHP 8.2+ (already configured)
- SQLite extension (already configured)
- Write access to `storage/` and `bootstrap/cache/` (should already have)
- No composer, no npm, no external dependencies needed

#### Quick Test:

```bash
# This should work immediately without any setup
php artisan test --filter=LogoutUsersTest

# If this passes, you're good to start fixing tests!
```

**Bottom Line:** You're working on TEST FILES, not infrastructure. Just edit the test files in `tests/Unit/` and `tests/Integration/`, run `php artisan test`, and commit your fixes. No composer or GitHub authentication needed!

---

## If You're Blocked: What to Report

If you cannot get `vendor/` installed due to composer/GitHub authentication issues, **STOP** and report back with:

```bash
# Run these commands and share the output:

# 1. Environment info
php -v
composer --version
pwd
whoami

# 2. Check if vendor exists
ls -la vendor/ 2>&1 | head -20

# 3. Try basic composer command
composer install --prefer-dist --no-interaction 2>&1 | tail -50

# 4. Check composer config
composer config -g --list | grep github

# 5. Network test
curl -I https://api.github.com 2>&1
```

**Include this info when reporting you're blocked.** The repository owner can then either:
- Provide a GitHub token
- Set up the environment with pre-installed dependencies
- Adjust the repository to include vendor/ (not recommended but possible)
- Provide alternative access method

**Don't waste time beyond 30 minutes on dependency installation.** That's an environment/access issue, not a testing issue.

---

## Getting Started (5-Minute Setup - Assuming vendor/ Works)

### Step 1: Verify Environment
```bash
cd /var/www/html

# Verify PHP version
php -v  # Should be 8.2+

# Verify vendor directory exists
ls vendor/laravel/framework  # Should show framework files

# Run a simple test to confirm everything works
php artisan test --filter=LogoutUsersTest
```

**Expected Output:** Test should pass. If it does, you're ready!

### Step 2: See Current Failures
```bash
# Run all tests and save output
php artisan test 2>&1 | tee test_output.txt

# Count failures
grep "FAILED" test_output.txt | wc -l  # Should show 51
```

### Step 3: Pick Your Work
Choose from the Agent Work Distribution section below based on:
- Your available time (30 min to 3 hours)
- Your comfort level (Quick Wins = easiest)
- Your interest area (IMAP, Controllers, Jobs, etc.)

### Step 4: Make Fixes & Test
```bash
# Edit test file
vim tests/Unit/JobsPoliciesTest.php  # or your editor of choice

# Run just that test
php artisan test --filter=JobsPoliciesTest

# When it passes, commit
git add tests/Unit/JobsPoliciesTest.php
git commit -m "fix: Customer email assertions in JobsPoliciesTest"
```

### Step 5: Verify No Regressions
```bash
# Run full test suite
php artisan test

# Should show fewer failures than before, same passing count
```

---

## Summary by Category

| Category | Count | Type | Priority |
|----------|-------|------|----------|
| ImapServiceProcessMessageTest | 18 | Assertions, Events, Logic | Medium |
| ImapServiceHelpersTest | 18 | Assertions, Type Issues | Medium |
| ControllerCoverageTest | 10 | Authorization, Mocks | Low |
| JobsPoliciesTest | 3 | Assertions, Email | Low |
| Misc | 2 | Various | Low |

---

## 1. ImapServiceProcessMessageTest (18 failures)

**File:** `tests/Unit/Services/ImapServiceProcessMessageTest.php`

### Issue Categories:

#### A. Customer Attribute Assertions (5 failures)
**Lines:** 160-162, 237-239, 512-514, plus 2 more

**Problem:** Tests asserting on `customers` table with customer attributes that don't exist or checking wrong table.

**Example:**
```php
// Line 160
$this->assertDatabaseHas('customers', [
    'first_name' => 'Jane',
    'last_name' => 'Customer',
]);
```

**Root Cause:** Some assertions still check `customers` table incorrectly or expect data that isn't created by the test.

**Fix Needed:**
- Verify customer is created correctly
- Check if assertions match what the service actually creates
- May need to query via customer ID rather than attributes

---

#### B. Event Dispatch Failures (2 failures)
**Lines:** ~1772, ~1795

**Problem:** Events not being dispatched when expected.

**Errors:**
- `The expected [App\Events\CustomerCreatedConversation] event was not dispatched.`
- `The expected [App\Events\CustomerReplied] event was not dispatched.`

**Root Cause:** Either:
1. Events not actually firing in the code path
2. Test setup incorrect (Event::fake() timing)
3. Service logic changed

**Fix Needed:**
- Verify ImapService actually dispatches these events
- Check event conditions in service
- May need to adjust test expectations

---

#### C. Conversation Creation Check (1 failure)
**Line:** ~1908

**Problem:** Test expects 0 conversations but 1 exists.

**Error:** `Failed asserting that 1 matches expected 0.`

**Root Cause:** Test assumes message shouldn't create conversation but it does.

**Fix Needed:**
- Review test scenario - should it create conversation?
- May need to adjust test data or expectations

---

#### D. Exception Handling (2 failures)
**Lines:** ~389, plus 1 more

**Problem:** Test expects exceptions but they aren't thrown.

**Error:** `Failed asserting that exception of type "Exception" is thrown.`

**Root Cause:** Service no longer throws exceptions in these scenarios (maybe caught/handled differently).

**Fix Needed:**
- Check if service behavior changed
- Update test expectations or fix service

---

#### E. Type/Mock Issues (4 failures)

**TypeError (Line 402):**
```
Mockery_13_Webklex_PHPIMAP_Header::get(): Return value must be of type 
Webklex\PHPIMAP\Attribute, null returned
```
**Fix:** Header mock returning null instead of Attribute object

**Exception (Lines 262, 299):**
Service exceptions during processing - need to check specific test scenarios.

**UniqueConstraintViolationException:**
Trying to insert duplicate data - check test data setup.

---

#### F. Name Trimming Logic (1 failure)
**Line:** ~2868

**Problem:** Name not being trimmed as expected.

**Error:** `Failed asserting that '  John   Doe  ' is not equal to '  John   Doe  '.`

**Root Cause:** Service doesn't trim whitespace from names, or test assertion is backwards.

**Fix Needed:**
- Check if service should trim names
- Or reverse test logic

---

#### G. Null/Not Null Assertions (3 failures)
**Lines:** ~347, plus 2 more

**Problem:** Objects expected but null returned.

**Error:** `Failed asserting that null is not null.`

**Root Cause:** Service not returning expected objects (customer, conversation, thread).

**Fix Needed:**
- Verify test data setup
- Check service logic matches test expectations

---

## 2. ImapServiceHelpersTest (18 failures)

**File:** `tests/Unit/Services/ImapServiceHelpersTest.php`

### Issue Categories:

#### A. Address Parsing (6 failures)
**Lines:** 94, 269, 313, 425, 498, 514

**Problem:** Tests for `getAddresses*()` and `parseAddresses()` helper methods failing.

**Common Error:** `Failed asserting that null is not null.`

**Root Cause:** 
- Helper methods returning different structure than expected
- May need MockImapAddress objects in test data
- Attribute object handling issues

**Fix Needed:**
- Review helper method implementation in ImapService
- Update test mocks to match expected input format
- Check return value structure

---

#### B. Parse Address Type Errors (2 failures)
**Lines:** ~1070 (called from tests at line 42)

**Problem:** Error in parseAddresses when processing certain input types.

**Error Type:** `Error` (not Exception)

**Root Cause:** Code trying to access property/method on wrong type.

**Fix Needed:**
- Add type checks in parseAddresses
- Handle edge cases (null, empty arrays, wrong types)

---

#### C. Reply Separation (3 failures)
**Lines:** 750, 760, 899

**Problem:** Tests for `separateReply()` method failing.

**Likely Issue:** Body text not being split correctly between reply and original message.

**Fix Needed:**
- Check regex patterns in separateReply
- Verify test input matches expected format

---

#### D. Original Sender Extraction (4 failures)
**Lines:** 1050, 1060, 1140, 1152

**Problem:** Tests for `getOriginalSenderFromFwd()` failing.

**Method Purpose:** Extract original sender from forwarded message body.

**Common Issues:**
- Regex not matching test patterns
- Not handling all email formats (with/without semicolons, angle brackets)
- Returning null when should return email

**Fix Needed:**
- Review forwarded message patterns
- Update regex to handle all test cases
- May need multiple regex patterns for different formats

---

#### E. Customer Creation (3 failures)
**Lines:** 1355-1357, 1394-1396, 1414-1416

**Problem:** `createCustomersFromMessage()` not creating customers with expected attributes.

**Root Cause:** Similar to ProcessMessageTest - customer creation/assertion mismatch.

**Fix Needed:**
- Verify what attributes createCustomersFromMessage actually sets
- May need to check emails table instead of customers table for email

---

## 3. ControllerCoverageTest (10 failures)

**File:** `tests/Integration/ControllerCoverageTest.php`

### Issue Categories:

#### A. Authorization Exceptions (4 failures)
**Lines:** 69, 294, 883, 906

**Problem:** Tests expect success but hit authorization failures.

**Controllers Affected:**
- ConversationController (lines 69, 294)
- UserController (lines 883, 906)

**Root Cause:** Test users don't have required permissions.

**Fix Needed:**
- Give test user admin role: `$user->role = User::ROLE_ADMIN;`
- Or give specific permissions to mailboxes
- Or expect authorization failure and test that instead

---

#### B. Module Operation Mocking (4 failures)
**Lines:** 1154, ~1158, and 2 more

**Problem:** Mock expectations not met for module enable/disable/delete operations.

**Errors:**
- `Expectation failed for method name is "getName" when invoked 1 time. Method was expected to be called 1 time, actually called 0 times.`

**Root Cause:** Module mock setup incorrect or module API changed.

**Fix Needed:**
- Review module system implementation
- Update mocks to match actual module behavior
- May need to use real modules instead of mocks

---

#### C. Destroy Operations (2 failures)
**Lines:** 248, 622

**Problem:** Conversation destroy tests failing.

**Likely Issue:** Soft delete not working as expected or authorization required.

**Fix Needed:**
- Check ConversationController@destroy implementation
- Verify soft delete setup
- Add proper authorization

---

## 4. JobsPoliciesTest (3 failures)

**File:** `tests/Unit/JobsPoliciesTest.php`

### Issues:

#### A. SendLog Assertion (1 failure)
**Line:** 494-496

**Problem:** `$customer->email` doesn't exist (customers have emails relationship).

**Error:** Testing send_logs table with `'email' => $customer->email`

**Fix:**
```php
// Wrong
'email' => $customer->email,

// Right
'email' => $customer->emails->first()->email,
// Or
'email' => $customer->primary_email,
```

---

#### B. InvalidCountException (1 failure)
**Line:** 539

**Problem:** Collection count different than expected.

**Root Cause:** Query returning wrong number of results or test expectation incorrect.

**Fix Needed:**
- Check what's being counted
- Verify test data setup

---

#### C. Job Properties (1 failure)
**Line:** 1455

**Problem:** Job property assertion failing.

**Fix Needed:**
- Check job structure matches test expectations
- May need to adjust queue configuration

---

## 5. Miscellaneous (2 failures)

### A. QueryException
**File:** ImapServiceProcessMessageTest  
**Line:** 3068

**Problem:** `SQLSTATE[HY000]: General error: 1 no such column: email`

**Root Cause:** Still one place querying customers.email directly.

**Fix:**
```php
// Find and replace
Customer::where('email', ...) 
// with
Customer::whereHas('emails', fn($q) => $q->where('email', ...))
```

---

## Priority Order for Fixes

### High Priority (Infrastructure-adjacent)
1. **QueryException** - Database query using wrong column (1 failure)
2. **Type Errors** - Mock return types incorrect (3 failures)

### Medium Priority (Test Logic)
3. **Event Dispatch** - May indicate service bugs (2 failures)
4. **Customer Assertions** - Wrong table/attributes (11 failures)
5. **Address Parsing** - Helper method issues (8 failures)

### Low Priority (Test Implementation)
6. **Authorization** - Just need proper test setup (4 failures)
7. **Module Mocks** - API may have changed (4 failures)
8. **Reply/Forward Parsing** - Regex patterns (7 failures)
9. **Job Properties** - Test expectations (3 failures)
10. **Misc Assertions** - Various (8 failures)

---

## Quick Fixes Available

### Immediate Wins (Can fix in <30 minutes):

1. **Customer email attribute** (4 failures)
   - Replace `$customer->email` with `$customer->emails->first()->email`
   - Files: JobsPoliciesTest.php

2. **QueryException** (1 failure)
   - Find `Customer::where('email'` and replace with `whereHas('emails'`
   - File: ImapServiceProcessMessageTest.php line ~3068

3. **Authorization failures** (4 failures)
   - Add `$user->role = User::ROLE_ADMIN; $user->save();` before action
   - File: ControllerCoverageTest.php

4. **Header::get() returning null** (1 failure)
   - Fix mock to return empty Attribute object instead of null
   - File: ImapServiceProcessMessageTest.php line ~402 test setup

**Total Quick Fixes: 10 failures → reduces to 41 failures**

---

## Test Investigation Required

These need actual debugging to understand root cause:

1. **Event dispatch failures** - Why aren't events firing?
2. **Name trimming** - Should service trim or not?
3. **Reply separation** - What format is the test data?
4. **Original sender extraction** - What regex patterns are needed?
5. **Module mocks** - Has the module system API changed?

---

## Files to Modify

| File | Failures | Effort |
|------|----------|--------|
| `tests/Unit/Services/ImapServiceProcessMessageTest.php` | 18 | High |
| `tests/Unit/Services/ImapServiceHelpersTest.php` | 18 | High |
| `tests/Integration/ControllerCoverageTest.php` | 10 | Medium |
| `tests/Unit/JobsPoliciesTest.php` | 3 | Low |
| `app/Services/ImapService.php` | 0 (may need fixes) | N/A |

---

## Agent Work Distribution

### Agent 1: Quick Wins (Low effort, high impact)
**Estimated Time:** 30-60 minutes  
**Failures Fixed:** ~10
- Fix customer email attribute usage
- Fix QueryException (whereHas)
- Add authorization to controller tests
- Fix Header::get() mock

### Agent 2: IMAP Service Test Logic
**Estimated Time:** 2-3 hours  
**Failures Fixed:** ~20
- Debug event dispatch issues
- Fix customer assertion patterns
- Review helper method return values
- Fix address parsing tests

### Agent 3: IMAP Helper Methods
**Estimated Time:** 2-3 hours  
**Failures Fixed:** ~12
- Fix reply separation regex
- Fix original sender extraction
- Fix address parsing edge cases
- Update type handling

### Agent 4: Integration Tests
**Estimated Time:** 1-2 hours  
**Failures Fixed:** ~6
- Fix remaining authorization issues
- Fix or remove module operation mocks
- Fix destroy operation tests

### Agent 5: Job/Policy Tests
**Estimated Time:** 30-60 minutes  
**Failures Fixed:** ~3
- Fix SendLog assertions
- Fix collection count expectations
- Fix job property tests

---

## Success Metrics

- **Current:** 51 failures
- **After Quick Wins:** 41 failures (10 fixed)
- **After Agent 1-2:** ~21 failures (30 fixed)
- **After All Agents:** 0-5 failures (target: <5)

---

## Notes for Agents

1. **Base Test Classes:** All tests extend proper base classes (FeatureTestCase, UnitTestCase, IntegrationTestCase). Don't change this.

2. **Customer/Email Model:** Remember customers.email column doesn't exist - emails are in separate table. See `docs/TESTING_GUIDE.md`.

3. **IMAP Mocks:** Use `MockImapAddress` class and return `Attribute` objects from `Header::get()`. See examples in TESTING_GUIDE.md.

4. **Don't Break Passing Tests:** Run full test suite after changes to ensure you didn't regress.

5. **Commit Often:** One commit per logical fix (e.g., "Fix customer email assertions in JobsPoliciesTest").

---

## Reference Documentation

- **Testing Patterns:** `docs/TESTING_GUIDE.md`
- **Fix History:** `docs/TEST_FIX_SUMMARY.md`
- **Customer Model:** `app/Models/Customer.php` (see emails() relationship)
- **IMAP Service:** `app/Services/ImapService.php` (review helper methods)

---

**Last Updated:** November 14, 2025  
**Test Run Duration:** ~100 seconds  
**Passing Tests:** 2,311  
**Total Assertions:** 5,161

---

## Troubleshooting Guide

### Problem: "Could not authenticate against github.com"

**Root Cause:** Composer is trying to clone packages from GitHub source instead of downloading pre-built distributions. Even with `--prefer-dist`, composer falls back to source cloning when dist downloads fail.

**Reality Check:** This is an environment/access issue, not something you can easily fix.

**Solution Options (in order of likelihood):**

```bash
# 1. Check if vendor already exists (best case)
ls vendor/laravel/framework && php artisan test  # ✅ You're good to go!

# 2. Try with prefer-dist (may work if rate limits aren't hit)
composer install --prefer-dist --no-interaction

# 3. Try Packagist mirror
composer config repos.packagist composer https://packagist.org
composer install --prefer-dist --no-interaction

# 4. Get a GitHub personal access token
# Visit: https://github.com/settings/tokens/new
# Select: repo (full control) - or at minimum: read:packages
composer config -g github-oauth.github.com "ghp_YourTokenHere"
composer install --prefer-dist --no-interaction

# 5. Use Docker if available
docker run --rm -v $(pwd):/app composer:latest install --prefer-dist
```

**If all fail:** You're legitimately blocked. See "If You're Blocked: What to Report" section above. Don't spend more than 30 minutes on this - it's an environment issue the repo owner needs to resolve.

**Important:** Once `vendor/` is populated (by any method), you don't need to run composer again. Focus on test files only.

---

### Problem: "vendor/autoload.php not found"

**Root Cause:** You're in the wrong directory or vendor is actually missing.

**Solution:**
```bash
# Check current directory
pwd  # Should be /var/www/html

# Check if vendor exists
ls -la vendor/autoload.php  # Should exist

# If not, you're in wrong directory
cd /var/www/html

# Try again
php artisan test
```

---

### Problem: "Class 'Tests\UnitTestCase' not found"

**Root Cause:** Test base classes not autoloaded.

**Solution:**
```bash
# Regenerate autoload files (safe, doesn't hit GitHub)
composer dump-autoload

# Should work now
php artisan test
```

---

### Problem: Tests timing out or hanging

**Root Cause:** Database locking or infinite loops.

**Solution:**
```bash
# Kill any hanging processes
pkill -f "php artisan test"

# Clear test database
rm -f database/database.sqlite
touch database/database.sqlite

# Run migrations
php artisan migrate --env=testing

# Try again
php artisan test
```

---

### Problem: "Cannot modify header information - headers already sent"

**Root Cause:** Output buffering issue in tests.

**Solution:**
```bash
# Run tests without output buffering
php -d output_buffering=0 artisan test

# Or use this command
php artisan test --no-output
```

---

### Problem: Different number of failures than expected (not 51)

**Possible Reasons:**
1. ✅ **Good!** You've fixed some tests - celebrate and commit!
2. ⚠️ **Investigate:** You may have broken passing tests - check the diff
3. 🔍 **Stale results:** Clear caches and re-run

**Check:**
```bash
# See what changed
git status
git diff

# Clear everything and retest
php artisan cache:clear
php artisan config:clear
php artisan test
```

---

### Problem: "Permission denied" on storage or cache directories

**Root Cause:** File permissions issue.

**Solution:**
```bash
# Fix permissions (run as root or with sudo if needed)
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Or if that doesn't work
chmod -R 777 storage bootstrap/cache  # Less secure but works
```

---

## Quick Command Reference

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test --filter=JobsPoliciesTest

# Run specific test method
php artisan test --filter=test_send_auto_reply_creates_send_log

# Run and save output
php artisan test 2>&1 | tee test_results.txt

# Count failures
php artisan test 2>&1 | grep "Tests:" | tail -1

# Run tests in parallel (faster but harder to debug)
php artisan test --parallel

# Stop on first failure (helpful for debugging)
php artisan test --stop-on-failure

# Verbose output
php artisan test -v

# Very verbose (shows all queries, events, etc.)
php artisan test -vvv
```

---

## Common Test Patterns

### Pattern 1: Fix Customer Email Attribute
```php
// ❌ Wrong - customers table has no email column
$customer = Customer::factory()->create();
$this->assertDatabaseHas('customers', ['email' => 'test@example.com']);

// ✅ Right - check emails table
$customer = Customer::factory()->create(['email' => 'test@example.com']);
$this->assertDatabaseHas('emails', ['email' => 'test@example.com']);

// ✅ Also right - query via relationship
$customer = Customer::whereHas('emails', fn($q) => 
    $q->where('email', 'test@example.com')
)->first();
```

### Pattern 2: Fix Authorization
```php
// ❌ Wrong - regular user can't access admin routes
$user = User::factory()->create();

// ✅ Right - make user admin
$user = User::factory()->create(['role' => User::ROLE_ADMIN]);
// OR
$user = User::factory()->create();
$user->role = User::ROLE_ADMIN;
$user->save();
```

### Pattern 3: Fix IMAP Mocks
```php
// ❌ Wrong - returns null
$header->shouldReceive('get')->with('subject')->andReturn(null);

// ✅ Right - return Attribute object
use Webklex\PHPIMAP\Attribute;
$attr = Mockery::mock(Attribute::class);
$attr->shouldReceive('first')->andReturn('Subject Text');
$header->shouldReceive('get')->with('subject')->andReturn($attr);

// ✅ Also right - use empty Attribute for null values
$emptyAttr = new Attribute('subject', []);
$header->shouldReceive('get')->with('subject')->andReturn($emptyAttr);
```

---

## Need More Help?

1. **Check the main guide:** `docs/TESTING_GUIDE.md` - comprehensive patterns
2. **Check the fix history:** `docs/TEST_FIX_SUMMARY.md` - what was already fixed
3. **Look at passing tests:** Find similar tests that work and copy the pattern
4. **Check the model:** `app/Models/Customer.php` - see how relationships work
5. **Check the service:** `app/Services/ImapService.php` - understand what it does

---

## Success Checklist

Before submitting your fixes, verify:

- [ ] Tests you fixed now pass
- [ ] No passing tests were broken (regression check)
- [ ] Code follows existing patterns (see TESTING_GUIDE.md)
- [ ] Commits have clear messages ("fix: what you fixed")
- [ ] Changes are minimal (only fix what's broken)
- [ ] You can explain why the test was failing
- [ ] You tested locally with `php artisan test`

---

## Contact & Support

If you're still stuck after trying everything above:

1. **Document what you tried:** Share the commands you ran and errors you got
2. **Share your environment:** Output of `php -v`, `pwd`, `ls vendor/`
3. **Show test output:** `php artisan test --filter=YourTest 2>&1`
4. **Check similar issues:** See if another agent encountered the same problem

Remember: **You're editing test files, not deploying infrastructure.** This should be straightforward test logic fixes. If it's getting complicated, step back and review the patterns in `docs/TESTING_GUIDE.md`.

Good luck! 🚀

---

## For Repository Owner: Fixing the Blocked Agent Issue

If agents report being blocked by composer/GitHub authentication, you have several options:

### Option 1: Provide GitHub Token (Easiest)
```bash
# Create a token at: https://github.com/settings/tokens/new
# Minimum scopes: read:packages
# Share with agents via secure channel (not in commits!)
```

### Option 2: Pre-Install Dependencies in Environment
```bash
# In an environment where composer works:
cd /var/www/html
composer install --prefer-dist --no-interaction

# Then ensure vendor/ persists in agent environments
# (container volumes, shared filesystem, etc.)
```

### Option 3: Use Composer from Docker
```bash
# Add to repository documentation:
docker run --rm -v $(pwd):/app composer:latest install --prefer-dist
```

### Option 4: Commit vendor/ (Not Recommended)
```bash
# Remove from .gitignore
sed -i '/^\/vendor$/d' .gitignore

# Commit vendor directory
git add vendor/
git commit -m "Add vendor directory for environments without composer access"

# WARNING: This makes repository much larger and updates are manual
```

### Option 5: Use GitHub Actions to Build
```yaml
# .github/workflows/build-vendor.yml
name: Build Dependencies
on: [push]
jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: php-actions/composer@v6
        with:
          args: --prefer-dist --no-interaction
      - run: tar -czf vendor.tar.gz vendor/
      - uses: actions/upload-artifact@v3
        with:
          name: vendor-dependencies
          path: vendor.tar.gz
```

### Recommended Solution:

**For CI/CD environments:** Provide a GitHub token via secrets/environment variables

**For development:** Ensure developers can run composer in their own environments, vendor/ stays gitignored

**For blocked agents:** Provide pre-built vendor/ directory or run tests in a container with dependencies pre-installed

The test fixes themselves are straightforward - it's just the environment setup that's blocking progress.
