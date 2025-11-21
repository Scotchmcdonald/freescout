# Phase 2 Implementation Plan - Splitting Large Files

## Status: IN PROGRESS (20% Complete)

### Objective
Split 4 large test files (with >70 tests each) into balanced files targeting ~50 tests per file.

## Files to Split

### 1. ConsoleCommandsTest.php (212 tests) → 4 files ✅ IN PROGRESS
**Target:** 4 files of ~53 tests each

**Status: 38% Complete (2 of 4 files created)**
- ✅ ModuleBuildCommandsTest.php (26 tests) - commit ac28b3a
- ✅ ModuleInstallCommandsTest.php (54 tests) - commit 8e2c459
- ⏳ ModuleUpdateAndUpdateCommandsTest.php (55 tests) - NEXT
  - 28 ModuleUpdate tests (lines 735-1056)
  - 27 Update Command tests (lines 1057-1398)
- ⏳ KernelAndEdgeCasesTest.php (77 tests) - AFTER
  - 20 Kernel tests (lines 1399-1581)
  - 57 Edge case tests (lines 1582-end)

**Action Items:**
1. Extract lines 735-1398 from ConsoleCommandsTest.php → ModuleUpdateAndUpdateCommandsTest.php
2. Extract lines 1399-end from ConsoleCommandsTest.php → KernelAndEdgeCasesTest.php
3. Delete original ConsoleCommandsTest.php
4. Verify all 212 tests accounted for (26+54+55+77=212) ✓

### 2. ImapServiceHelpersTest.php (140 tests) → 3 files
**Target:** 3 files of ~47 tests each

**Proposed split:**
- ImapServiceHelpersBasicTest.php (47 tests)
  - Connection helpers
  - Authentication helpers
  - Basic IMAP operations
- ImapServiceHelpersAdvancedTest.php (47 tests)
  - Search and filter helpers
  - Message manipulation helpers
  - Advanced IMAP operations
- ImapServiceHelpersEdgeCasesTest.php (46 tests)
  - Error handling
  - Edge cases and boundary conditions
  - Performance and timeout scenarios

### 3. ImapServiceProcessMessageTest.php (95 tests) → 2 files
**Target:** 2 files of ~48 tests each

**Proposed split:**
- ImapServiceProcessMessageBasicTest.php (48 tests)
  - Message parsing
  - Header processing
  - Basic message operations
- ImapServiceProcessMessageAdvancedTest.php (47 tests)
  - Attachment handling
  - MIME processing
  - Complex message scenarios
  - Edge cases

### 4. RemainingModelsComprehensiveTest.php (87 tests) → 2 files
**Target:** 2 files of ~44 tests each

**Proposed split:**
- CoreModelsComprehensiveTest.php (44 tests)
  - User, Mailbox, Conversation core functionality
  - Basic model operations
  - Relationships and accessors
- ExtendedModelsComprehensiveTest.php (43 tests)
  - Extended model features
  - Complex queries and scopes
  - Model observers and events
  - Edge cases

## Implementation Strategy

### Priority Order
1. **HIGH**: Complete ConsoleCommandsTest split (132 tests remaining)
2. **HIGH**: Split ImapServiceHelpersTest (140 tests) 
3. **MEDIUM**: Split ImapServiceProcessMessageTest (95 tests)
4. **MEDIUM**: Split RemainingModelsComprehensiveTest (87 tests)

### Time Estimates
- ConsoleCommandsTest completion: 1 hour
- ImapServiceHelpersTest split: 1.5 hours
- ImapServiceProcessMessageTest split: 1 hour
- RemainingModelsComprehensiveTest split: 1 hour
- **Total:** 4.5 hours

## Expected Results

### Before Phase 2
- 11 files with >70 tests
- Average: 16.4 tests/file
- Files in 40-60 range: 46 (23%)

### After Phase 2 Complete
- 0 files with >70 tests ✓
- Average: ~35-40 tests/file
- Files in 40-60 range: ~145 (78%)

### Net Impact
- Files before: 201
- Files removed (Phase 1): 17
- Files removed (Phase 2): 4 (replaced by 11)
- **Files after: 191** (net -10)

## Quality Standards

All split files must:
- ✅ Use `test_` prefix (NO #[Test] attributes)
- ✅ Extend proper base classes
- ✅ Follow TESTING_GUIDE.md standards
- ✅ Maintain all original test logic
- ✅ Have clear documentation headers
- ✅ Target 40-60 tests per file
- ✅ Group logically related tests

## Progress Tracking

| File | Tests | Status | Files Created | Commit |
|------|-------|--------|---------------|--------|
| ConsoleCommandsTest | 212 | 38% | 2/4 | ac28b3a, 8e2c459 |
| ImapServiceHelpersTest | 140 | 0% | 0/3 | - |
| ImapServiceProcessMessageTest | 95 | 0% | 0/2 | - |
| RemainingModelsComprehensiveTest | 87 | 0% | 0/2 | - |
| **TOTAL** | **534** | **15%** | **2/11** | - |

## Next Steps

1. ✅ Complete ModuleUpdateAndUpdateCommandsTest.php (55 tests)
2. ✅ Complete KernelAndEdgeCasesTest.php (77 tests)
3. ✅ Delete original ConsoleCommandsTest.php
4. Start ImapServiceHelpersTest split
5. Continue with remaining files

---

**Last Updated:** Current commit
**Phase 2 Status:** 20% complete, on track for target completion
