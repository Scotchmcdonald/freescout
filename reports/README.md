# Test Coverage Reports & Implementation Plans

**Mission:** Achieve 50% minimum coverage on all 115 classes (Phase 1), then 80% (Phase 2)  
**Current:** 2.28% line coverage, 0% class coverage (115 classes untested)  
**Timeline:** 16 weeks total (8 weeks per phase)

---

## 📚 Documentation Index

### ⭐ Start Here

1. **[IMPLEMENTATION_ROADMAP.md](./IMPLEMENTATION_ROADMAP.md)** - Day-by-day action plan
   - Week 1-8 detailed breakdown
   - Daily test targets (15-25 tests/day)
   - Test templates (copy-paste ready)
   - Success metrics

2. **[COVERAGE_TARGETS_BY_CLASS.md](./COVERAGE_TARGETS_BY_CLASS.md)** - Test count per class
   - All 115 classes with targets
   - Phase 1: ~1,303 tests needed
   - Phase 2: ~1,190 additional tests
   - Progress tracking template

3. **[COVERAGE_ANALYSIS_AND_TEST_PLAN.md](./COVERAGE_ANALYSIS_AND_TEST_PLAN.md)** - Strategy & edge cases
   - 150+ edge cases documented
   - 7 concrete test examples
   - Test data strategy
   - 10-phase breakdown

4. **[PHASE_3_ENHANCEMENT_ANALYSIS.md](./PHASE_3_ENHANCEMENT_ANALYSIS.md)** - Is Phase 3 worthwhile?
   - ROI analysis (750-1,700% return)
   - Coverage gap analysis
   - Production incident prevention
   - Security hardening details
   - **Recommendation: YES - Highly worthwhile**

---

## 🎯 Quick Reference

### Coverage Targets

| Phase | Minimum per Class | Line Coverage | Method Coverage | Duration |
|-------|------------------|---------------|-----------------|----------|
| **Phase 1** | 50% | 40%+ | 50%+ | 8 weeks |
| **Phase 2** | 80% | 70%+ | 80%+ | 8 weeks |
| **Phase 3** | 95% | 85%+ | 90%+ | 4 weeks |

### Phase 1 Test Distribution

| Category | Classes | Tests Needed | Target Coverage |
|----------|---------|-------------|-----------------|
| Critical (CRAP > 1000) | 9 | 310 | 55-60% |
| Console Commands | 15 | 81 | 50-60% |
| Events | 11 | 44 | 55-60% |
| Controllers | 22 | 240 | 50-55% |
| Jobs | 5 | 51 | 55-70% |
| Listeners | 14 | 59 | 55-65% |
| Mail | 8 | 40 | 60% |
| Models | 18 | 236 | 60-80% |
| Observers | 6 | 40 | 55-70% |
| Policies | 5 | 42 | 50-60% |
| Services | 2 | 95 | 60% |
| Misc/Providers | 5 | 65 | 50-60% |
| **TOTAL** | **120** | **~1,303** | **50%+** |

---

## 🚀 Week 1 Quick Start

### Day 1-2: Setup
```bash
# Enhance factories
# tests/database/factories/* - Add state methods

# Create fixtures
mkdir -p tests/Fixtures/emails
# Add: valid_email.eml, auto_responder_email.eml, etc.

# Create helper
# tests/Support/EmailFixtures.php

# Baseline coverage
php artisan test --coverage-html reports/baseline
```

### Day 3-5: Start Testing
```bash
# Priority 1: ImapService parseAddresses (CRAP 420)
php artisan make:test Unit/Services/ImapServiceTest --unit
# Add 10 tests with DataProvider

# Priority 2: MailHelper replaceMailVars (CRAP 506)
php artisan make:test Unit/Misc/MailHelperTest --unit
# Add 15 tests

# Priority 3: ConversationController CRUD
php artisan make:test Feature/Controllers/ConversationControllerTest
# Add 10 tests
```

**Week 1 Target:** 150 tests, 8% coverage

---

## 📊 Coverage Reports

### Current Reports
- [test-coverage/index.html](./test-coverage/index.html) - HTML coverage dashboard
- [test-coverage/dashboard.html](./test-coverage/dashboard.html) - CRAP scores
- [coverage_summary.txt](../coverage_summary.txt) - Text summary

### Generate New Report
```bash
php artisan test --coverage-html reports/test-coverage
```

---

## 🎓 Test Examples

### Unit Test Example
```php
/** @test */
public function parseAddresses_with_valid_email_returns_array(): void
{
    $service = new ImapService();
    $result = $service->parseAddresses('John Doe <john@example.com>');
    
    $this->assertIsArray($result);
    $this->assertEquals('john@example.com', $result[0]['email']);
}
```

### Feature Test Example
```php
/** @test */
public function user_can_view_conversation_in_assigned_mailbox(): void
{
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user->id);
    
    $conversation = Conversation::factory()
        ->for($mailbox)
        ->create();
    
    $response = $this->actingAs($user)
        ->get(route('conversations.show', $conversation));
    
    $response->assertOk();
}
```

---

## 📈 Progress Tracking

### Weekly Checklist
- [ ] Tests added: XXX (target: 150-200/week)
- [ ] Coverage increased: X% (target: 5%/week)
- [ ] Classes at 0%: XX (target: decrease by 10-15/week)
- [ ] All tests passing
- [ ] CI/CD green

### Milestones
- 🎯 **Week 2:** ImapService to 35%, ConversationController to 30%
- 🎯 **Week 4:** All models to 60%+, all controllers at 25%+
- 🎯 **Week 6:** Jobs/Listeners/Observers complete
- 🎉 **Week 8:** Phase 1 Complete - All classes 50%+!

---

## 🛠️ Tools & Commands

### Run Tests
```bash
# All tests
php artisan test

# With coverage
php artisan test --coverage

# Specific test
php artisan test --filter=ImapServiceTest

# Parallel (faster)
php artisan test --parallel
```

### Check Coverage
```bash
# Find classes at 0%
grep -r "0%" reports/test-coverage/dashboard.html | wc -l

# Generate report
php artisan test --coverage-html reports/week-$(date +%U)
```

---

## 📞 Support

**Questions?** Check the main documents:
- Implementation: [IMPLEMENTATION_ROADMAP.md](./IMPLEMENTATION_ROADMAP.md)
- Targets: [COVERAGE_TARGETS_BY_CLASS.md](./COVERAGE_TARGETS_BY_CLASS.md)
- Strategy: [COVERAGE_ANALYSIS_AND_TEST_PLAN.md](./COVERAGE_ANALYSIS_AND_TEST_PLAN.md)

**Blockers?** Document in weekly report template (see IMPLEMENTATION_ROADMAP.md)

---

## 🏆 Success Criteria

### Phase 1 Complete When:
- ✅ All 115 classes have test files
- ✅ Zero classes at 0% coverage
- ✅ All classes at 50%+ coverage
- ✅ 40%+ line coverage overall
- ✅ 50%+ method coverage overall
- ✅ Top 10 CRAP methods reduced by 60%
- ✅ All tests passing in CI/CD

---

**Let's build comprehensive test coverage! 🚀**
