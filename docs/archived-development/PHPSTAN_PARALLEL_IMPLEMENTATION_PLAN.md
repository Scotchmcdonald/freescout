# PHPStan Level 9 & Ignore Reduction - Parallel Implementation Plan

**Date:** November 9, 2025  
**Strategy:** Batch work into independent groups that can be executed by separate agents in parallel  
**Goal:** Minimize overall wall-clock time while maintaining code quality

---

## Parallelization Strategy

### Key Principles
1. **File Independence:** Each batch works on different files with no overlap
2. **No Merge Conflicts:** Agents work in isolated areas of codebase
3. **Independent Testing:** Each batch can be tested separately
4. **Sequential Gates:** Some work must complete before next phase begins

---

## PHASE 0: Foundation (Sequential - 1 agent)
**Prerequisites for all other work**

### Agent: Setup Agent
**Duration:** 30 minutes  
**Deliverable:** Baseline report with level 9 bodyscan

**Tasks:**
1. Update `scripts/generate_report.sh` to use level 9 bodyscan
2. Run full test suite to establish baseline
3. Document all current level 9 errors
4. Commit changes to reporting infrastructure

**Files Modified:**
- `scripts/generate_report.sh`

**Output Required:**
- `reports/TEST_RESULTS_SUMMARY.md` with level 9 bodyscan results
- List of all errors at level 9 (will be more than current 93)

**Blockers:** None  
**Blocks:** All Phase 1 batches (need baseline to validate fixes)

---

## PHASE 1: Quick Wins - 5 Parallel Batches
**Can start after Phase 0 completes**  
**Total Duration:** 1-2 hours (wall-clock time if parallel)

### Batch 1A: Auth Controllers & View Strings
**Agent:** Auth Agent  
**Duration:** 30 minutes  
**Risk:** Very Low (removing likely obsolete ignores)

**Tasks:**
1. Remove 5 view-string type ignores from phpstan.neon
2. Verify Auth controllers still pass PHPStan
3. If errors appear, add proper `view-string` type hints

**Files to Modify:**
- `phpstan.neon` (remove ignores)
- Possibly: Auth controller files if type hints needed

**Ignores to Remove:**
```yaml
# All unresolvable view-string errors for Auth controllers
```

**Test Command:**
```bash
vendor/bin/phpstan analyse app/Http/Controllers/Auth/ --level=9
```

**Success Criteria:** No errors in Auth controllers, 5 fewer ignores

---

### Batch 1B: Simple Model Logic Fixes
**Agent:** Models Agent  
**Duration:** 45 minutes  
**Risk:** Low (simple logic fixes)

**Tasks:**
1. Fix Option.php - right side of || always true
2. Fix Customer.php - negated boolean always false  
3. Remove corresponding ignores from phpstan.neon

**Files to Modify:**
- `app/Models/Option.php`
- `app/Models/Customer.php`
- `phpstan.neon` (remove 2 ignores)

**Ignores to Remove:**
```yaml
- message: '#Right side of \|\| is always true#'
  path: app/Models/Option.php
- message: '#Negated boolean expression is always false#'
  path: app/Models/Customer.php
```

**Test Command:**
```bash
vendor/bin/phpstan analyse app/Models/Option.php app/Models/Customer.php --level=9
php artisan test --filter="Option|Customer"
```

**Success Criteria:** Logic fixed, tests pass, 2 fewer ignores

---

### Batch 1C: Jobs & Events Fixes
**Agent:** Jobs Agent  
**Duration:** 45 minutes  
**Risk:** Low (adding null checks)

**Tasks:**
1. Fix SendAutoReply.php - empty() on non-existent offset
2. Fix EventServiceProvider.php - observers property type
3. Remove corresponding ignores

**Files to Modify:**
- `app/Jobs/SendAutoReply.php`
- `app/Providers/EventServiceProvider.php`
- `phpstan.neon` (remove 2 ignores)

**Ignores to Remove:**
```yaml
- message: '#Offset .* on .* in empty\(\) does not exist#'
  path: app/Jobs/SendAutoReply.php
- message: '#Property App\\Providers\\EventServiceProvider::\$observers .* does not accept default value#'
  path: app/Providers/EventServiceProvider.php
```

**Test Command:**
```bash
vendor/bin/phpstan analyse app/Jobs/SendAutoReply.php app/Providers/ --level=9
php artisan test --filter="SendAutoReply|Event"
```

**Success Criteria:** Fixes applied, tests pass, 2 fewer ignores

---

### Batch 1D: Factory & Generic Type Hints
**Agent:** Types Agent  
**Duration:** 1 hour  
**Risk:** Low (additive changes)

**Tasks:**
1. Remove UserFactory ignores (likely obsolete from PR #25/#26)
2. Add missing generic/iterable type hints throughout codebase
3. Remove identifier-based ignores

**Files to Modify:**
- `phpstan.neon` (remove 4 ignores)
- Various files needing type hints (will discover during PHPStan run)

**Ignores to Remove:**
```yaml
- '#PHPDoc tag @use has invalid type Database\\Factories\\UserFactory#'
- '#Type Database\\Factories\\UserFactory .* is not subtype#'
- identifier: missingType.iterableValue
- identifier: missingType.generics
```

**Test Command:**
```bash
vendor/bin/phpstan analyse database/factories/ --level=9
vendor/bin/phpstan analyse --level=9 --error-format=raw | grep "missingType"
```

**Success Criteria:** All generic types documented, 4 fewer ignores

---

### Batch 1E: VerifyEmailController Fix
**Agent:** Verification Agent  
**Duration:** 30 minutes  
**Risk:** Low (isolated change)

**Tasks:**
1. Fix parameter type for Verified event constructor
2. Remove ignore from phpstan.neon

**Files to Modify:**
- `app/Http/Controllers/Auth/VerifyEmailController.php`
- `phpstan.neon` (remove 1 ignore)

**Ignore to Remove:**
```yaml
- message: '#Parameter \#1 \$user of class Illuminate\\Auth\\Events\\Verified constructor#'
  path: app/Http/Controllers/Auth/VerifyEmailController.php
```

**Test Command:**
```bash
vendor/bin/phpstan analyse app/Http/Controllers/Auth/VerifyEmailController.php --level=9
php artisan test --filter="EmailVerification"
```

**Success Criteria:** Type issue resolved, tests pass, 1 fewer ignore

---

### Phase 1 Integration Point
**Agent:** Integration Agent  
**Duration:** 30 minutes  
**Tasks:**
1. Merge all 5 batches (1A-1E)
2. Resolve any merge conflicts in phpstan.neon
3. Run full test suite
4. Verify 14-15 ignores removed

**Expected Results:**
- ~15 fewer ignores
- All tests still passing
- No new PHPStan errors with level 9

---

## PHASE 2: Structural Improvements - 3 Parallel Batches
**Can start after Phase 1 completes and merges**  
**Total Duration:** 3-4 hours (wall-clock time if parallel)

### Batch 2A: Controller Model Types
**Agent:** Controllers Agent  
**Duration:** 2 hours  
**Risk:** Medium (requires careful type hints)

**Tasks:**
1. Add return type hints to all controller methods that return models
2. Fix ConversationController property access
3. Remove model property ignores for controllers

**Files to Modify:**
- `app/Http/Controllers/ConversationController.php`
- `app/Http/Controllers/CustomerController.php`
- `app/Http/Controllers/UserController.php`
- `app/Http/Controllers/MailboxController.php`
- `phpstan.neon` (remove controller-related model ignores)

**Ignores to Remove:**
```yaml
- message: '#Access to an undefined property Illuminate\\Database\\Eloquent\\Model::\$id#'
  paths:
    - app/Http/Controllers/ConversationController.php
```

**Test Command:**
```bash
vendor/bin/phpstan analyse app/Http/Controllers/ --level=9
php artisan test tests/Feature/Http/Controllers/
```

**Success Criteria:** All controller methods properly typed, tests pass

---

### Batch 2B: Service Layer Model Types
**Agent:** Services Agent  
**Duration:** 2-3 hours  
**Risk:** Medium (SmtpService is critical)

**Tasks:**
1. Add return type hints for SmtpService methods
2. Fix encryption parameter types (int|null vs string|null)
3. Remove service-related ignores (NOT ImapService - that's Phase 3)

**Files to Modify:**
- `app/Services/SmtpService.php`
- `phpstan.neon` (remove SmtpService ignores only)

**Ignores to Remove:**
```yaml
- message: '#Parameter .* of method App\\Services\\SmtpService::getEncryption\(\) expects int\|null, string\|null given#'
  path: app/Services/SmtpService.php
```

**Test Command:**
```bash
vendor/bin/phpstan analyse app/Services/SmtpService.php --level=9
php artisan test --filter="Smtp"
```

**Success Criteria:** SmtpService fully typed, encryption handling correct

---

### Batch 2C: Policy Model Types
**Agent:** Policies Agent  
**Duration:** 1 hour  
**Risk:** Low (isolated to policies)

**Tasks:**
1. Fix MailboxPolicy pivot property access
2. Add proper relationship type hints
3. Remove policy-related ignores

**Files to Modify:**
- `app/Policies/MailboxPolicy.php`
- `phpstan.neon` (remove pivot ignore)

**Ignores to Remove:**
```yaml
- message: '#Access to an undefined property Illuminate\\Database\\Eloquent\\Model::\$pivot#'
  path: app/Policies/MailboxPolicy.php
```

**Test Command:**
```bash
vendor/bin/phpstan analyse app/Policies/ --level=9
php artisan test --filter="Policy"
```

**Success Criteria:** Pivot access properly typed, tests pass

---

### Phase 2 Integration Point
**Agent:** Integration Agent  
**Duration:** 1 hour  
**Tasks:**
1. Merge all 3 batches (2A-2C)
2. Run full test suite
3. Verify 10-11 ignores removed

**Expected Results:**
- ~11 fewer ignores (total ~26 removed so far)
- All tests still passing
- Better type safety across controllers/services/policies

---

## PHASE 3: Complex Refactoring - Sequential (Cannot Parallelize)
**Can start after Phase 2 completes**  
**Total Duration:** 8-20 hours (HIGH RISK, needs careful execution)

### Batch 3A: Remove Dangerous Catch-All (HIGH PRIORITY)
**Agent:** Security Agent  
**Duration:** 2-4 hours  
**Risk:** HIGH (will expose hidden errors)

**Tasks:**
1. Remove broad dynamic method ignore from phpstan.neon
2. Run PHPStan to see what breaks
3. Address each specific error that surfaces
4. Document why each method call is safe

**Ignore to Remove:**
```yaml
- '#Call to an undefined method [a-zA-Z0-9\\_]+::[a-zA-Z0-9\\_]+\(\)#'
```

**Why Not Parallelizable:**
- Unknown scope of impact
- May affect multiple files
- Needs iterative fixing

**Test Command:**
```bash
vendor/bin/phpstan analyse --level=9
php artisan test
```

**Success Criteria:** Catch-all removed, all specific issues addressed

---

### Batch 3B: Mail::failures() Refactor
**Agent:** Mail Agent  
**Duration:** 2 hours  
**Risk:** Medium (changes error handling)

**Tasks:**
1. Replace Mail::failures() with modern exception handling
2. Update SendAutoReply job
3. Remove ignore

**Files to Modify:**
- `app/Jobs/SendAutoReply.php`
- `phpstan.neon` (remove Mail::failures ignore)

**Ignore to Remove:**
```yaml
- message: '#Call to an undefined static method Illuminate\\Support\\Facades\\Mail::failures\(\)#'
  path: app/Jobs/SendAutoReply.php
```

**Test Command:**
```bash
vendor/bin/phpstan analyse app/Jobs/SendAutoReply.php --level=9
php artisan test --filter="SendAutoReply"
```

**Success Criteria:** Modern error handling, tests pass

---

### Batch 3C: ImapService Major Refactor
**Agent:** IMAP Specialist Agent  
**Duration:** 8-16 hours  
**Risk:** VERY HIGH (critical email processing path)

**Tasks:**
1. Create typed wrapper layer for PHPIMAP library
2. Fix all 14 ImapService ignores:
   - Parameter type issues
   - Always-true conditions  
   - Property access on library objects
   - Nullsafe operator issues
3. Comprehensive testing

**Files to Modify:**
- `app/Services/ImapService.php` (major refactor)
- Possibly create `app/Services/Contracts/ImapClientInterface.php`
- `phpstan.neon` (remove all 14 ImapService ignores)

**Ignores to Remove:**
```yaml
# All 14 ImapService-related ignores
```

**Why Not Parallelizable:**
- All errors in same file
- Interdependent fixes
- Requires deep understanding of IMAP flow

**Test Strategy:**
```bash
# Unit tests
php artisan test --filter="Imap"

# Integration tests with real IMAP
php artisan test tests/Feature/EmailFetchingTest.php

# Manual testing with test mailbox
php artisan fetch-emails
```

**Success Criteria:**
- All type issues resolved
- Email fetching still works
- No regressions in IMAP functionality

---

### Batch 3D: Module Facade (Optional)
**Agent:** Module Agent  
**Duration:** 2-4 hours OR keep ignore  
**Risk:** Low (third-party limitation)

**Decision Point:** 
- Option A: Create PHPStan stub for Nwidart\Modules package
- Option B: Keep ignore (justified as third-party limitation)

**Recommendation:** Keep ignore with documentation

**If implementing, tasks:**
1. Create `stubs/nwidart-modules.stub.php`
2. Update phpstan.neon to include stub
3. Remove ignore

**Files to Create/Modify:**
- `stubs/nwidart-modules.stub.php`
- `phpstan.neon`

---

## Summary: Parallelization Opportunities

### Maximum Parallelization
- **Phase 0:** 1 agent (sequential, foundation)
- **Phase 1:** 5 agents working simultaneously (Batches 1A-1E)
- **Phase 2:** 3 agents working simultaneously (Batches 2A-2C)
- **Phase 3:** 1 agent per batch, but sequential (3A → 3B → 3C, 3D optional)

### Wall-Clock Time Estimates

#### Without Parallelization (Sequential):
- Phase 0: 0.5 hours
- Phase 1: 3.5 hours (5 batches × ~45 min avg)
- Phase 2: 5 hours (3 batches × ~2 hours avg)
- Phase 3: 12-22 hours (sequential, complex)
- **Total: 21-31 hours**

#### With Parallelization:
- Phase 0: 0.5 hours (1 agent)
- Phase 1: 1 hour (5 agents parallel, longest batch = 1 hour)
- Phase 2: 3 hours (3 agents parallel, longest batch = 3 hours)
- Phase 3: 12-22 hours (sequential, complex)
- **Total: 16.5-26.5 hours**

**Time Saved: ~5 hours (24% reduction)**

---

## Implementation Workflow

### For Each Batch:

1. **Branch Creation:**
   ```bash
   git checkout -b copilot/phpstan-batch-1a-auth-controllers
   ```

2. **Work in Isolation:**
   - Make changes
   - Test locally
   - Commit with descriptive messages

3. **Create PR:**
   ```bash
   gh pr create --title "PHPStan Level 9: Batch 1A - Auth Controllers" \
                --body "Part of parallel PHPStan improvement initiative..."
   ```

4. **Integration Agent Merges:**
   - Wait for all batches in phase to complete
   - Merge all PRs
   - Resolve conflicts
   - Run full test suite
   - Push to main branch

---

## Risk Mitigation

### For Parallel Work:
1. **Clear File Ownership:** Each batch owns specific files
2. **phpstan.neon Conflicts:** Integration agent handles merge
3. **Testing Before Integration:** Each batch must pass local tests
4. **Rollback Plan:** Each batch is a separate PR, can revert individually

### For Phase 3:
1. **Extra Testing:** Manual QA for ImapService
2. **Staging Environment:** Test email fetching before production
3. **Incremental Approach:** Fix one ignore at a time if needed
4. **Feature Flags:** Consider flagging new IMAP wrapper

---

## Deliverables

### Phase 0:
- ✅ Level 9 bodyscan report
- ✅ Updated reporting infrastructure

### Phase 1:
- ✅ 14-15 ignores removed
- ✅ 5 PRs merged

### Phase 2:
- ✅ 10-11 ignores removed
- ✅ 3 PRs merged
- ✅ All models/controllers/services properly typed

### Phase 3:
- ✅ Dangerous catch-all removed
- ✅ Modern error handling
- ✅ ImapService fully typed (or documented technical debt)
- ✅ Final ignore count: <5 (ideally just Module facade)

---

## Decision Points for User

1. **Start with Phase 1 only?** (Low risk, 5 parallel agents, ~1 hour)
2. **Include Phase 2?** (Medium risk, 3 parallel agents, +3 hours)
3. **Tackle Phase 3?** (High risk, sequential, +12-22 hours)
4. **Keep Module facade ignore?** (Recommend: yes, third-party limitation)

**Recommended Starting Point:** Execute Phase 0 + Phase 1 (6 tasks total: 1 sequential + 5 parallel) to prove the approach and get quick wins.
