# Test Implementation Roadmap - 50% → 80% Coverage

**Current State:** 2.28% line coverage, 0% class coverage  
**Phase 1 Goal:** 50% minimum all classes (40% lines, 50% methods)  
**Phase 2 Goal:** 80% minimum all classes (70% lines, 80% methods)

---

## Executive Summary

### The Problem
- 115 classes with 0% coverage
- Critical business logic (ImapService, ConversationController) completely untested
- CRAP scores up to 34,410 indicating high risk
- Production deployment risky without test safety net

### The Solution
- **Phase 1 (8 weeks):** Add ~1,200 tests to reach 50% coverage on all classes
- **Phase 2 (8 weeks):** Add ~1,200 tests to reach 80% coverage on all classes
- **Phase 3 (4 weeks):** Add ~500 tests to reach 95% coverage

### The Commitment
> **Every class will have at least 50% coverage after Phase 1**  
> **Every class will have at least 80% coverage after Phase 2**

---

## Phase 1: 8-Week Sprint to 50% Coverage

### Week-by-Week Breakdown

#### Week 1: Critical Foundation (Target: 8% coverage)
**Tests to Add: 150**

**Monday-Tuesday: Setup & Infrastructure**
- [ ] Create enhanced factory states (15 states)
- [ ] Create email fixture files (8 files)
- [ ] Create EmailFixtures helper class
- [ ] Create base test case enhancements
- [ ] Run baseline coverage report

**Wednesday-Friday: Start Critical Tests**
- [ ] ImapService::parseAddresses() - 15 tests
- [ ] ImapService::getAddressesWithNames() - 12 tests
- [ ] MailHelper::replaceMailVars() - 20 tests
- [ ] MailHelper::isAutoResponder() - 10 tests
- [ ] ConversationController index/show - 15 tests

**Milestone:** 72 tests added, critical utility methods tested

---

#### Week 2: Critical Services (Target: 15% coverage)
**Tests to Add: 180**

**Focus:** Complete ImapService and ConversationController basics

**Monday-Wednesday: ImapService**
- [ ] ImapService::fetchEmails() - 15 tests
- [ ] ImapService::testConnection() - 10 tests
- [ ] ImapService::createClient() - 8 tests
- [ ] ImapService::getMessageHeaders() - 12 tests

**Thursday-Friday: ConversationController**
- [ ] store() method - 15 tests
- [ ] reply() method - 15 tests
- [ ] update() method - 10 tests
- [ ] destroy() method - 8 tests

**Weekend Goal:** 
- [ ] Run coverage: ImapService should be ~35%, ConversationController ~30%

---

#### Week 3: Models Foundation (Target: 22% coverage)
**Tests to Add: 200**

**Focus:** Get all models above 50%

**Monday-Tuesday: Customer & User Models**
- [ ] Customer::create() - 15 tests
- [ ] Customer::setData() - 12 tests
- [ ] Customer relationships - 10 tests
- [ ] User permissions - 12 tests
- [ ] User relationships - 10 tests

**Wednesday-Thursday: Conversation & Thread**
- [ ] Conversation::updateFolder() - 10 tests
- [ ] Conversation status transitions - 15 tests
- [ ] Conversation relationships - 12 tests
- [ ] Thread type system - 15 tests
- [ ] Thread relationships - 10 tests

**Friday: Remaining Models**
- [ ] Mailbox - 15 tests
- [ ] Folder - 12 tests
- [ ] Email - 10 tests
- [ ] Attachment - 12 tests

**Weekend Goal:**
- [ ] All major models at 60%+ coverage

---

#### Week 4: Controllers Baseline (Target: 28% coverage)
**Tests to Add: 190**

**Focus:** Every controller has basic CRUD tests

**Monday-Tuesday: High-Priority Controllers**
- [ ] MailboxController - 25 tests
- [ ] SettingsController - 25 tests
- [ ] SystemController - 20 tests
- [ ] UserController - 15 tests

**Wednesday-Thursday: Medium-Priority Controllers**
- [ ] CustomerController - 12 tests
- [ ] ModulesController - 8 tests
- [ ] DashboardController - 5 tests
- [ ] 10 other controllers - 5 tests each (50 tests)

**Friday: Controller Polish**
- [ ] Add authorization tests - 30 tests

**Weekend Goal:**
- [ ] All 22 controllers at 50%+ coverage

---

#### Week 5: Events, Listeners, Observers (Target: 34% coverage)
**Tests to Add: 180**

**Focus:** Complete event system coverage

**Monday: Events**
- [ ] All 11 event classes - 4 tests each (44 tests)

**Tuesday-Wednesday: Listeners**
- [ ] SendNotificationToUsers - 10 tests (enhance existing)
- [ ] SendAutoReply - 8 tests (enhance existing)
- [ ] SendReplyToCustomer - 8 tests
- [ ] 11 other listeners - 4 tests each (44 tests)

**Thursday-Friday: Observers**
- [ ] ConversationObserver - 8 tests
- [ ] ThreadObserver - 8 tests
- [ ] UserObserver - 6 tests (enhance existing)
- [ ] CustomerObserver - 6 tests
- [ ] MailboxObserver - 6 tests
- [ ] AttachmentObserver - 6 tests (enhance existing)

**Weekend Goal:**
- [ ] All events/listeners/observers at 55%+ coverage

---

#### Week 6: Jobs, Mail, Policies (Target: 39% coverage)
**Tests to Add: 170**

**Monday-Tuesday: Jobs**
- [ ] SendNotificationToUsers - 8 additional tests (total 20)
- [ ] SendAutoReply - 6 additional tests (total 12)
- [ ] SendConversationReply - 8 tests
- [ ] SendReplyToCustomer - 6 tests
- [ ] RecalculateFolderCounters - 5 tests

**Wednesday: Mail Classes**
- [ ] All 8 mail classes - 5 tests each (40 tests)

**Thursday-Friday: Policies**
- [ ] MailboxPolicy - 12 tests
- [ ] ConversationPolicy - 10 tests
- [ ] UserPolicy - 8 tests
- [ ] CustomerPolicy - 6 tests
- [ ] SystemPolicy - 6 tests

**Weekend Goal:**
- [ ] Jobs at 60%+, Mail at 60%+, Policies at 55%+

---

#### Week 7: Console Commands & Services (Target: 44% coverage)
**Tests to Add: 160**

**Monday-Wednesday: Console Commands**
- [ ] ModuleUpdate - 7 tests
- [ ] CheckRequirements - 8 tests
- [ ] ModuleInstall - 8 tests
- [ ] CreateUser - 7 tests
- [ ] FetchEmails - 6 tests
- [ ] UpdateFolderCounters - 5 tests
- [ ] 9 other commands - 4 tests each (36 tests)

**Thursday-Friday: Services & Misc**
- [ ] SmtpService - 15 tests
- [ ] ImapService additional tests - 30 tests (reach 60%)
- [ ] Providers - 15 tests
- [ ] Helper utilities - 10 tests

**Weekend Goal:**
- [ ] All commands at 50%+
- [ ] ImapService at 60%+

---

#### Week 8: Polish & Edge Cases (Target: 50% coverage)
**Tests to Add: 170**

**Monday-Tuesday: Fill Gaps**
- Review coverage report
- Add tests to any class below 50%
- Focus on edge cases in critical classes

**Wednesday-Thursday: Integration Tests**
- [ ] Email fetch → Conversation creation flow - 10 tests
- [ ] Reply → Notification → Email send flow - 10 tests
- [ ] Conversation merge workflow - 8 tests
- [ ] User creation → Permission setup flow - 8 tests

**Friday: Validation & Cleanup**
- [ ] Run full test suite: ensure all passing
- [ ] Generate final Phase 1 coverage report
- [ ] Document remaining gaps
- [ ] Celebrate hitting 50% milestone! 🎉

**Weekend Goal:**
- [ ] ✅ 50%+ coverage on all 115 classes
- [ ] ✅ 40%+ line coverage overall
- [ ] ✅ 50%+ method coverage overall
- [ ] ✅ All tests passing in CI/CD

---

## Phase 1 Success Metrics

### Coverage Achieved
- **Class Coverage:** 50-60% (was 0%)
- **Line Coverage:** 40-45% (was 2.28%)
- **Method Coverage:** 50-55% (was 2.93%)
- **Classes at 0%:** 0 (was 115)

### CRAP Score Reduction
| Method | Before | After | Reduction |
|--------|--------|-------|-----------|
| processMessage | 6,162 | ~2,500 | 60% |
| ModuleUpdate::handle | 600 | ~240 | 60% |
| replaceMailVars | 506 | ~200 | 60% |
| Customer::create | 342 | ~140 | 60% |

### Test Suite Growth
- **Before:** ~100 tests
- **After:** ~1,300 tests
- **New:** ~1,200 tests added

---

## Phase 2: 8-Week Sprint to 80% Coverage

### High-Level Strategy

**Weeks 9-12: Deep Testing (50% → 65%)**
- Add 5-10 additional tests per class
- Focus on edge cases from refined plan
- Add error path coverage
- Add validation failure tests
- Target: All classes to 65%+

**Weeks 13-16: Complete Coverage (65% → 80%)**
- Add integration tests
- Add performance tests
- Add security tests
- Add rare edge cases
- Target: All classes to 80%+

### Week-by-Week (High Level)

**Week 9-10:** ImapService to 85%, ConversationController to 85% (+180 tests)
**Week 11-12:** All models to 85% (+300 tests)
**Week 13-14:** All controllers to 80% (+200 tests)
**Week 15-16:** All other classes to 80%+, integration tests (+520 tests)

**Total Phase 2:** ~1,200 additional tests

---

## Phase 2 Success Metrics

### Coverage Achieved
- **Class Coverage:** 80-85% (was 50-60%)
- **Line Coverage:** 70-75% (was 40-45%)
- **Method Coverage:** 80-85% (was 50-55%)
- **Classes below 80%:** 0 (was 115)

### CRAP Score Reduction
- All methods with CRAP > 500: Reduced to < 200
- All methods with CRAP > 200: Reduced to < 100
- Average CRAP score: < 50

### Test Suite Growth
- **Before Phase 2:** ~1,300 tests
- **After Phase 2:** ~2,500 tests
- **New:** ~1,200 tests added

---

## Daily Workflow

### Developer Day (Phase 1)

**Morning (3 hours):**
1. Pick next class from [COVERAGE_TARGETS_BY_CLASS.md](./COVERAGE_TARGETS_BY_CLASS.md)
2. Write 10-15 tests focusing on:
   - Happy path (2-3 tests)
   - Edge cases (5-8 tests)
   - Error cases (2-4 tests)
3. Run tests: `php artisan test --filter=YourNewTests`
4. Commit: `git commit -m "Add tests for ClassName (0% → 50%)"`

**Afternoon (2 hours):**
1. Pick next class or continue previous
2. Write 5-10 more tests
3. Run full suite: `php artisan test`
4. Generate coverage: `php artisan test --coverage`
5. Update tracking sheet

**End of Day:**
- Target: 15-20 tests added
- Coverage increase: 1-2%
- Time: 5 hours

### Pair Programming Days (Recommended)

**Tuesday & Thursday:**
- Pair on complex classes (ImapService, ConversationController)
- Review each other's tests
- Discuss edge cases
- Share knowledge

---

## Tools & Commands

### Generate Coverage Report
```bash
# HTML report
php artisan test --coverage-html reports/coverage-$(date +%Y%m%d)

# Terminal summary
php artisan test --coverage

# Specific file
php artisan test --coverage tests/Unit/Services/ImapServiceTest.php
```

### Check Class Coverage
```bash
# Find classes still at 0%
grep -r "0%" reports/coverage-latest/dashboard.html | grep -o "class=\".*\"" | wc -l

# Check specific class
grep "ImapService" reports/coverage-latest/dashboard.html
```

### Run Tests Efficiently
```bash
# Parallel execution (faster)
php artisan test --parallel

# Specific test class
php artisan test tests/Unit/Services/ImapServiceTest.php

# Specific method
php artisan test --filter=test_parseAddresses_with_valid_email

# Watch mode (run on file change)
php artisan test --watch
```

### CI/CD Integration
```yaml
# .github/workflows/tests.yml
name: Tests
on: [push, pull_request]
jobs:
  tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Run tests
        run: php artisan test --coverage
      - name: Check coverage thresholds
        run: |
          # Fail if below phase targets
          php artisan test:coverage --min-line=40 --min-method=50
```

---

## Tracking & Reporting

### Weekly Report Template

**Week X Report (Phase 1)**

**Tests Added:** XXX  
**Coverage Progress:**
- Line: X.X% (target: Y%)
- Method: X.X% (target: Y%)
- Class: X.X% (target: Y%)

**Classes Completed (0% → 50%+):**
- ClassName1
- ClassName2
- ClassName3

**Blockers/Issues:**
- Issue 1: Description and plan
- Issue 2: Description and plan

**Next Week Focus:**
- Area 1
- Area 2

### Monthly Milestone Review

**End of Month 1:**
- [ ] Week 1-4 completed
- [ ] 600+ tests added
- [ ] 20%+ line coverage
- [ ] 40+ classes at 50%+
- [ ] Team sync: address blockers

**End of Month 2:**
- [ ] Week 5-8 completed
- [ ] 1,200+ tests added
- [ ] 40%+ line coverage
- [ ] 115 classes at 50%+
- [ ] Phase 1 complete! 🎉

---

## Risk Management

### Common Issues & Solutions

**Issue:** Tests are slow (> 5 min for full suite)
**Solution:**
- Use `--parallel` flag
- Mock external services (IMAP, SMTP)
- Use in-memory database for faster tests
- Split into unit/feature/integration suites

**Issue:** Can't reach 50% on complex class
**Solution:**
- Focus on public methods first
- Test happy paths before edge cases
- Use mocking for dependencies
- Document what's NOT tested

**Issue:** Tests are brittle (failing randomly)
**Solution:**
- Use factories instead of hardcoded data
- Avoid time-dependent tests (use Carbon::setTestNow)
- Clear cache between tests
- Use database transactions

**Issue:** Not enough time
**Solution:**
- Reduce target: Some classes to 45% if very complex
- Pair program on hard classes
- Focus on critical paths first
- Request more resources

---

## Resources

### Documentation
- [Main Test Plan](./COVERAGE_ANALYSIS_AND_TEST_PLAN.md)
- [Class-by-Class Targets](./COVERAGE_TARGETS_BY_CLASS.md)
- [PHPUnit Documentation](https://phpunit.de/)
- [Laravel Testing](https://laravel.com/docs/testing)

### Team Communication
- Daily standup: Share progress, blockers
- Weekly sync: Review coverage reports
- Slack channel: #testing
- Code reviews: Require tests for all new code

---

## Celebration Milestones

- 🎯 **10% coverage:** First milestone - momentum building
- 🎯 **25% coverage:** Quarter way there
- 🎯 **40% coverage:** Nearly at Phase 1 target
- 🎉 **50% coverage - Phase 1 Complete:** Major celebration!
- 🎯 **65% coverage:** Phase 2 halfway
- 🎉 **80% coverage - Phase 2 Complete:** Production ready!
- 🏆 **95% coverage - Phase 3 Complete:** World-class testing!

---

**Let's build a robust, well-tested codebase! 🚀**
