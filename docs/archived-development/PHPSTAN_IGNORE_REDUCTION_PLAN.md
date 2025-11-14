# PHPStan Ignore Reduction & Level 9 Bodyscan Plan

**Date:** November 9, 2025  
**Goal:** Increase bodyscan to level 9 and reduce/eliminate all ignores from phpstan.neon  
**Current State:** Level 9 analyse (0 errors), Level 6 bodyscan (93 errors)

---

## Current Ignore Analysis

### Category 1: QUICK FIXES (Low Effort, High Impact)

#### 1.1 View String Type Hints (5 ignores - RESOLVED in code)
**Files:** Auth controllers  
**Issue:** PHPDoc `@var view-string` unresolvable type  
**Current Status:** Already using proper type hints in code, ignores may be obsolete  
**Fix:** Remove ignores and verify PHPStan passes  
**Effort:** 5 minutes  
**Affected ignores:**
- Auth/AuthenticatedSessionController.php
- Auth/ConfirmablePasswordController.php
- Auth/EmailVerificationPromptController.php
- Auth/NewPasswordController.php
- Auth/PasswordResetLinkController.php

#### 1.2 Missing Type Hints (2 identifier ignores)
**Issue:** Generic iterables and missing type documentation  
**Fix:** Add proper type hints to method signatures and properties  
**Effort:** 1-2 hours  
**Affected ignores:**
```php
- identifier: missingType.iterableValue
- identifier: missingType.generics
```

#### 1.3 UserFactory Type Issues (2 ignores)
**Issue:** PHPDoc tag issues with UserFactory  
**Current Status:** May already be resolved by PR #25/#26  
**Fix:** Remove ignores and test  
**Effort:** 5 minutes  
**Affected ignores:**
```php
- '#PHPDoc tag @use has invalid type Database\\Factories\\UserFactory#'
- '#Type Database\\Factories\\UserFactory .* is not subtype#'
```

### Category 2: MODERATE FIXES (Refactoring Required)

#### 2.1 Eloquent Model Property Access (6 ignores)
**Files:** Services/ImapService.php, Controllers/ConversationController.php, Policies/MailboxPolicy.php  
**Issue:** Accessing properties on generic `Model` instead of specific model types  
**Root Cause:** Methods return `Model` instead of specific model class  
**Fix:** Add proper return type hints using PHPDoc or union types  
**Effort:** 2-3 hours  
**Affected ignores:**
```php
- '#Access to an undefined property Illuminate\\Database\\Eloquent\\Model::\$id#'
- '#Access to an undefined property Illuminate\\Database\\Eloquent\\Model::\$pivot#'
- '#Property App\\Models\\Conversation::\$.* does not accept#'
```

**Example Fix:**
```php
// Before
public function findMailbox($id) {
    return Mailbox::find($id); // Returns Model
}

// After
/** @return \App\Models\Mailbox|null */
public function findMailbox($id): ?Mailbox {
    return Mailbox::find($id);
}
```

#### 2.2 Parameter Type Mismatches (4 ignores)
**Files:** Services/ImapService.php, Services/SmtpService.php  
**Issue:** Encryption parameter type confusion (int|null vs string|null)  
**Root Cause:** Config values stored as strings, methods expect int  
**Fix:** Add type casting or update method signatures  
**Effort:** 1-2 hours  
**Affected ignores:**
```php
- '#Parameter .* of method App\\Services\\(Imap|Smtp)Service::getEncryption\(\) expects int\|null, string\|null given#'
```

#### 2.3 VerifyEmailController Type Issue (1 ignore)
**File:** Auth/VerifyEmailController.php  
**Issue:** Parameter type mismatch for Verified event  
**Fix:** Add proper type hint or cast  
**Effort:** 30 minutes  

### Category 3: COMPLEX FIXES (Architectural/Library Issues)

#### 3.1 Module Facade Dynamic Methods (1 ignore)
**File:** ModulesController.php  
**Issue:** Nwidart\Modules\Facades\Module::findByAlias() not recognized  
**Root Cause:** Third-party package facade without PHPStan stubs  
**Fix Options:**
1. Create PHPStan extension/stub for package (permanent)
2. Add PHPDoc hints to usage (temporary)
3. Contact package maintainer for official stubs  
**Effort:** 2-4 hours (depends on approach)  
**Recommendation:** Keep this ignore OR add local PHPDoc

#### 3.2 Laravel Mail::failures() (1 ignore)
**File:** Jobs/SendAutoReply.php  
**Issue:** Mail::failures() is legacy method not in modern Laravel  
**Root Cause:** Deprecated Laravel API still in use  
**Fix:** Refactor to use modern exception handling  
**Effort:** 1-2 hours + testing  
**Affected ignore:**
```php
- '#Call to an undefined static method Illuminate\\Support\\Facades\\Mail::failures\(\)#'
```

#### 3.3 ImapService Complex Type Issues (14 ignores)
**File:** Services/ImapService.php  
**Issue:** Multiple type issues with IMAP library (Webklex\PHPIMAP)  
**Root Cause:** Third-party library has weak typing, complex conditionals  
**Problems:**
- `explode()` receiving array instead of string
- `json_decode()` receiving array instead of string
- Always-true conditions from type narrowing
- Undefined properties on IMAP library objects
- Nullsafe operator on non-nullable types

**Fix Options:**
1. Create wrapper layer with strict types (recommended)
2. Add comprehensive PHPDoc throughout
3. Submit upstream fixes to PHPIMAP library

**Effort:** 8-16 hours (major refactor)  
**Recommendation:** Phase 2 work, requires careful testing

**Affected ignores (14):**
```php
- explode/json_decode parameter issues
- is_object()/method_exists() always true
- Offset checks always true
- Boolean expression issues
- Nullsafe operator issues
- Property access on IMAP objects
```

#### 3.4 Broad Dynamic Method Ignore (1 DANGEROUS ignore)
**Issue:** Catches ALL undefined method calls  
**Current:** `- '#Call to an undefined method [a-zA-Z0-9\\_]+::[a-zA-Z0-9\\_]+\(\)#'`  
**Risk:** Hides real bugs  
**Fix:** Remove and address specific issues  
**Effort:** Depends on how many errors surface  
**Priority:** HIGH (this defeats the purpose of static analysis)

### Category 4: SIMPLE MODEL FIXES

#### 4.1 Option Model Logical Expression (1 ignore)
**File:** Models/Option.php  
**Issue:** Right side of || always true  
**Fix:** Review logic, likely can simplify or remove dead code  
**Effort:** 15 minutes  

#### 4.2 Customer Model Negated Expression (1 ignore)
**File:** Models/Customer.php  
**Issue:** Negated boolean always false  
**Fix:** Review logic, likely unnecessary check  
**Effort:** 15 minutes  

#### 4.3 SendAutoReply empty() Check (1 ignore)
**File:** Jobs/SendAutoReply.php  
**Issue:** Checking offset that doesn't exist  
**Fix:** Add existence check before empty()  
**Effort:** 15 minutes  

#### 4.4 EventServiceProvider Observers (1 ignore)
**File:** Providers/EventServiceProvider.php  
**Issue:** Property type issue with observers array  
**Fix:** Add proper type hint to property  
**Effort:** 10 minutes  

---

## Recommended Implementation Plan

### Phase 1: Quick Wins & Validation (2-3 hours)
**Goal:** Remove ~15 ignores with minimal risk

1. **Update bodyscan to level 9** (30 min)
   - Modify `scripts/generate_report.sh` to hardcode level 9
   - Update `/tmp/phpstan-bare.neon` generation
   - Run tests to establish baseline

2. **Remove obsolete ignores** (30 min)
   - Remove view-string ignores (likely already fixed)
   - Remove UserFactory ignores (likely fixed by PRs)
   - Test after each removal

3. **Fix simple model issues** (1 hour)
   - Option.php logical expression
   - Customer.php negated expression
   - SendAutoReply empty() check
   - EventServiceProvider observers type

4. **Add missing type hints** (1 hour)
   - Add generic/iterable type hints
   - Remove identifier ignores

**Expected Result:** 15-18 fewer ignores, bodyscan at level 9

### Phase 2: Model & Parameter Types (4-6 hours)
**Goal:** Fix structural typing issues

1. **Eloquent model property access** (3 hours)
   - Add return type hints throughout
   - Fix Controllers/Services to use specific model types
   - Remove 6 model property ignores

2. **Parameter type mismatches** (2 hours)
   - Fix encryption parameter types
   - Add type casting where needed
   - Remove 5 parameter ignores

3. **VerifyEmailController fix** (30 min)

**Expected Result:** 11 fewer ignores, better type safety

### Phase 3: Complex Refactoring (8-20 hours)
**Goal:** Address architectural issues

1. **Remove dangerous broad ignore** (variable time)
   - Remove catch-all dynamic method ignore
   - Address specific errors that surface
   - HIGH PRIORITY due to bug-hiding risk

2. **Mail::failures() refactor** (2 hours)
   - Replace with modern error handling
   - Update tests

3. **ImapService refactoring** (8-16 hours)
   - Create typed wrapper layer OR
   - Add comprehensive PHPDoc OR
   - Contribute upstream to PHPIMAP
   - This is the largest technical debt

4. **Module facade** (2-4 hours)
   - Create PHPStan stub OR
   - Keep ignore (lowest priority)

**Expected Result:** Clean codebase, all ignores removed or justified

---

## Level 9 Bodyscan Changes Required

### Current Code (generate_report.sh lines 52-80):
```bash
# Dynamically finds highest failing level
HIGHEST_LEVEL=0
# ... logic to determine level ...
vendor/bin/phpstan analyse -c /tmp/phpstan-bare.neon --level=$HIGHEST_LEVEL
```

### Proposed Change:
```bash
# Always run at level 9 for bodyscan
BODYSCAN_LEVEL=9
echo "Running bare PHPStan analysis at level $BODYSCAN_LEVEL (maximum strictness)..." >&2

cat > /tmp/phpstan-bare.neon << 'BAREEOF'
parameters:
    level: 9
    paths:
        - /var/www/html/app
BAREEOF

vendor/bin/phpstan analyse -c /tmp/phpstan-bare.neon --memory-limit=2G --error-format=table --level=9
```

---

## Risk Assessment

### Low Risk (Phase 1)
- Most changes are additive (type hints)
- Simple logic fixes are localized
- Can remove ignores incrementally with testing

### Medium Risk (Phase 2)
- Type changes may expose edge cases
- Requires comprehensive testing
- May reveal previously hidden bugs (this is good!)

### High Risk (Phase 3)
- ImapService is critical path (email processing)
- Requires extensive integration testing
- Should be done in separate PR with thorough QA

---

## Success Metrics

1. **Bodyscan runs at level 9** ✓ (simple config change)
2. **Ignore count reduced from 40+ to <5** (target: Module facade, maybe 1-2 IMAP issues)
3. **All tests still pass** (must maintain functionality)
4. **No new bugs introduced** (through comprehensive testing)
5. **Code is more maintainable** (explicit types = better IDE support)

---

## Next Steps

**Immediate:**
1. Update bodyscan to level 9 and run baseline report
2. Review baseline errors to validate categorization
3. Begin Phase 1 quick wins

**Decision Points:**
- Should we keep Module facade ignore? (third-party limitation)
- How aggressive should we be with ImapService refactor?
- Create separate PRs or one large refactor?

**Recommendation:** Start with Phase 1, create PR, then evaluate effort for Phase 2/3 based on results.
