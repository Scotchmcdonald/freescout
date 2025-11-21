# Test Reorganization Summary

## Target: ~50 Tests Per File

### Current State
- **Total Files**: 201  
- **Total Tests**: ~3,300
- **Average**: 16.4 tests/file
- **Problems**: 
  - 11 files with >70 tests (up to 212!)
  - 39 files with <10 tests (many with 2-4)

### Target State
- **Target Files**: ~185
- **Target Tests**: ~3,300 (same)
- **Target Average**: ~50 tests/file
- **Target Range**: 40-60 tests per file for 80% of files

## Reorganization Actions

### A. SPLIT LARGE FILES (11 files → 22 files)

**Priority 1: Console/Commands/ConsoleCommandsTest.php**
- Current: 212 tests in 1 file
- New Structure:
  1. `ModuleBuildCommandsTest.php` (53 tests)
  2. `ModuleInstallCommandsTest.php` (53 tests)
  3. `ModuleUpdateCommandsTest.php` (53 tests)
  4. `KernelAndCommandsTest.php` (53 tests)

**Priority 2: Services/ImapServiceHelpersTest.php**
- Current: 140 tests in 1 file
- New Structure:
  1. `ImapServiceHelpers1Test.php` (47 tests)
  2. `ImapServiceHelpers2Test.php` (47 tests)
  3. `ImapServiceHelpers3Test.php` (46 tests)

**Priority 3: Services/ImapServiceProcessMessageTest.php**
- Current: 95 tests in 1 file
- New Structure:
  1. `ImapServiceProcessMessage1Test.php` (48 tests)
  2. `ImapServiceProcessMessage2Test.php` (47 tests)

**Priority 4: Models/RemainingModelsComprehensiveTest.php**
- Current: 87 tests in 1 file
- New Structure:
  1. `MiscModels1Test.php` (44 tests)
  2. `MiscModels2Test.php` (43 tests)

**Priority 5: JobsPoliciesTest.php**
- Current: 83 tests in 1 file (mixing concerns)
- New Structure:
  1. `Policies/JobPoliciesTest.php` (40 tests) - Move policy tests
  2. `Jobs/JobsAdvancedTest.php` (43 tests) - Keep job tests

**Priority 6: ModelsListenersTest.php**
- Current: 80 tests in 1 file (mixing concerns)
- New Structure:
  1. `Models/ModelsIntegrationTest.php` (40 tests)
  2. `Listeners/ListenersIntegrationTest.php` (40 tests)

**Priority 7-11: Keep as acceptable** (54-60 tests)
- MailboxControllerComprehensiveTest.php (58)
- ControllerCoverageTest.php (58)  
- RequestsAndNotificationsTest.php (60)
- ThreadTest.php (54)
- SendLogTest.php (54)

### B. MERGE SMALL FILES (39 files → 10 files)

**Observers** → `ObserversComprehensiveTest.php` (~35 tests total)
- AttachmentObserverTest.php (2)
- CustomerObserverTest.php (2)
- MailboxObserverTest.php (3)
- ThreadObserverTest.php (4)
- Existing ObserversComprehensiveTest.php (22)

**Log Listeners** → `LogListenersComprehensiveTest.php` (~24 tests total)
- LogFailedLoginListenerTest.php (2)
- LogSuccessfulLoginListenerTest.php (2)
- LogSuccessfulLogoutListenerTest.php (2)
- LogGenerationTest.php (4)
- Existing LogListenersTest.php (14)

**Small Listeners** → `ListenersComprehensiveTest.php` (~31 tests total)
- SendPasswordChangedTest.php (4)
- SendAutoReplyListenerTest.php (2)
- UpdateMailboxCountersListenerTest.php (2)
- Existing ListenersComprehensiveTest.php (23)

**Small Services** → Merge into comprehensive files
- ImapServiceTest.php (2) → ImapServiceComprehensiveTest.php
- SmtpServiceTest.php (4+3) → SmtpServiceComprehensiveTest.php

**Small Models** → Merge into enhanced/comprehensive files
- EmailModelTest.php (3) → EmailModelEnhancedTest.php (28) = 31
- FolderModelTest.php (3) → FolderEnhancedTest.php (6) = 9
- ChannelModelTest.php (2) → Models/ChannelTest.php (36) = 38
- FollowerTest.php (3) → CoreModelsComprehensiveTest.php

**Small Jobs** → Merge into comprehensive files
- SendAutoReplyJobTest.php (4) → Jobs/SendAutoReplyTest.php (10) = 14
- SendConversationReplyTest.php (3) → Jobs/SendConversationReplyComprehensiveTest.php (9) = 12

**Misc Small Files**
- MailTest.php (3) → MailHelperTest.php (18) = 21
- DashboardControllerTest.php (2) → Controllers/DashboardControllerTest.php (12) = 14

### C. CONSOLIDATE DUPLICATES

**Feature Auth Tests** (8 small files) → 1 file
- Merge into `Feature/AuthenticationComprehensiveTest.php` (~25 tests)

## Expected Results

### Before Reorganization
| Range | Files | Percentage |
|-------|-------|------------|
| 1-10 tests | 39 | 19% |
| 11-30 tests | 95 | 47% |
| 31-50 tests | 46 | 23% |
| 51-70 tests | 10 | 5% |
| 71+ tests | 11 | 5% |

### After Reorganization  
| Range | Files | Percentage |
|-------|-------|------------|
| 1-10 tests | 5 | 3% |
| 11-30 tests | 30 | 16% |
| 31-50 tests | 80 | 43% |
| 51-70 tests | 65 | 35% |
| 71+ tests | 0 | 0% |

**Key Improvements:**
- ✅ Zero files with >70 tests
- ✅ 78% of files in 31-70 range (target zone)
- ✅ Only 3% with <10 tests (down from 19%)
- ✅ Much better balance and organization

## Implementation Status

**Phase 1 (In Progress):**
- [x] Created catalog and analysis (TEST_CATALOG.md)
- [x] Created reorganization plan
- [ ] Split ConsoleCommandsTest.php → 4 files
- [ ] Split ImapServiceHelpersTest.php → 3 files
- [ ] Split ImapServiceProcessMessageTest.php → 2 files

**Phase 2 (Planned):**
- [ ] Merge observer files
- [ ] Merge listener files  
- [ ] Merge small service files
- [ ] Merge small model files

**Phase 3 (Planned):**
- [ ] Consolidate feature auth tests
- [ ] Review and adjust as needed
- [ ] Update documentation

## Notes
- All reorganized files maintain test_ prefix convention
- No functional changes to test logic
- Improved readability and maintainability
- Better CI/CD performance (parallel execution)
- Easier to locate specific tests
