# Agent Primer Prompt - FreeScout Test Expansion

## 🎯 YOUR MISSION

You are assigned to implement comprehensive test coverage for the FreeScout Laravel 11 helpdesk application. Your work is part of a larger effort to achieve 70-80% test coverage across the codebase.

---

## 📋 CRITICAL CONTEXT

### Project Background
- **Application**: FreeScout - Open-source helpdesk system
- **Framework**: Laravel 11
- **Current Status**: 674 tests passing, 49.78% line coverage
- **Your Goal**: Implement your assigned phase from TEST_EXPANSION_PROPOSAL.md

### Database Compatibility Requirement ⚠️
**CRITICAL**: This modernized Laravel 11 app shares a database with a legacy Laravel 5 application located in `/var/www/html/archive/`. 

**You MUST**:
- ✅ Check `/var/www/html/archive/database/migrations/` before modifying any models
- ✅ Ensure all tests work with the existing database schema
- ❌ DO NOT create migrations that break backward compatibility
- ❌ DO NOT add new columns or tables without explicit approval

### Repository Information
- **Owner**: Scotchmcdonald
- **Repo**: freescout
- **Branch**: laravel-11-foundation
- **Root**: /var/www/html

---

## 📖 READING YOUR ASSIGNMENT

1. **Read the Test Plan**:
   ```bash
   cat /var/www/html/docs/TEST_EXPANSION_PROPOSAL.md
   ```

2. **Find Your Phase**: Look for your agent assignment (Agent-1-Services, Agent-2-Commands, etc.)

3. **Understand Your Scope**:
   - Components to test
   - Current coverage percentages
   - Target coverage goals
   - Number of tests to add
   - Estimated duration

4. **Review Coverage Report**:
   ```bash
   # View in browser or check HTML files
   ls -la /var/www/html/coverage-report/
   # Key file: dashboard.html shows CRAP scores and coverage %
   ```

---

## 🔍 BEFORE YOU START

### 1. Verify Current State
```bash
cd /var/www/html

# Run all existing tests - should be 674 passing
php artisan test

# Generate fresh coverage report
php artisan test --coverage-html coverage-report
```

### 2. Review Existing Code
For each component you're testing, read the actual implementation:

```bash
# Example: If testing ImapService
cat app/Services/ImapService.php

# Example: If testing a Job
cat app/Jobs/SendAutoReplyJob.php

# Example: If testing a Model
cat app/Models/Conversation.php
```

### 3. Check Existing Tests
See what's already tested to avoid duplication:

```bash
# List existing test files
ls -la tests/Unit/
ls -la tests/Feature/

# Read existing tests for your component
# Example:
cat tests/Unit/Services/ImapServiceTest.php
```

### 4. Verify Database Schema Compatibility
```bash
# Check archive migrations for your models
ls -la /var/www/html/archive/database/migrations/

# Example: If working with Subscription model
grep -r "subscriptions" /var/www/html/archive/database/migrations/
```

---

## ✍️ WRITING TESTS

### Test File Structure

**For Unit Tests** (app component testing):
```php
<?php

namespace Tests\Unit;

use App\Models\YourModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YourComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_descriptive_name_of_what_is_tested(): void
    {
        // Arrange: Set up test data
        $model = YourModel::factory()->create(['attribute' => 'value']);
        
        // Act: Execute the behavior
        $result = $model->someMethod();
        
        // Assert: Verify the outcome
        $this->assertTrue($result);
        $this->assertDatabaseHas('your_models', ['id' => $model->id]);
    }
}
```

**For Feature Tests** (HTTP/integration testing):
```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YourFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_perform_action(): void
    {
        // Arrange
        $user = User::factory()->create();
        
        // Act
        $response = $this->actingAs($user)->get('/your-route');
        
        // Assert
        $response->assertOk();
        $response->assertSee('Expected Content');
        $this->assertDatabaseHas('table', ['field' => 'value']);
    }
}
```

### Quality Requirements ✅

Every test MUST:
- [ ] Use `RefreshDatabase` trait
- [ ] Use factories for test data (never hardcode)
- [ ] Test actual behavior (not just HTTP 200)
- [ ] Include database assertions for mutations
- [ ] Have descriptive names (`test_user_can_create_conversation`)
- [ ] Follow AAA pattern (Arrange, Act, Assert)
- [ ] Be independent (can run in any order)
- [ ] Execute quickly (< 1 second)
- [ ] Handle both success and failure cases

### Common Patterns

```php
// ✅ GOOD - Tests actual behavior
public function test_conversation_creation_saves_to_database(): void
{
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    
    $response = $this->actingAs($user)->post('/conversations', [
        'mailbox_id' => $mailbox->id,
        'subject' => 'Test Subject',
        'body' => 'Test Body',
    ]);
    
    $response->assertRedirect();
    $this->assertDatabaseHas('conversations', [
        'mailbox_id' => $mailbox->id,
        'subject' => 'Test Subject',
    ]);
}

// ❌ BAD - Only tests HTTP status
public function test_conversation_creation(): void
{
    $response = $this->post('/conversations', [...]);
    $response->assertOk();
}
```

---

## 🔄 INCREMENTAL WORKFLOW

### Step-by-Step Process

1. **Create/Expand ONE test file at a time**
2. **Write 3-5 tests in that file**
3. **Run tests immediately**:
   ```bash
   php artisan test tests/Unit/YourNewTest.php
   ```
4. **Fix any failures before continuing**
5. **Commit working tests**:
   ```bash
   git add tests/Unit/YourNewTest.php
   git commit -m "Add tests for ComponentName - 5 tests added"
   ```
6. **Repeat until phase complete**

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific file
php artisan test tests/Unit/Services/ImapServiceTest.php

# Run specific test method
php artisan test --filter=test_service_handles_connection_failure

# Run with coverage for your files only
php artisan test --coverage --path=app/Services/

# Parallel execution (faster)
php artisan test --parallel
```

---

## 🚨 HANDLING FAILURES

### If Tests Fail

1. **Read the error message carefully**
2. **Check the actual vs expected**
3. **Verify your test data setup**
4. **Ensure database state is clean** (RefreshDatabase)
5. **Check for missing relationships** (foreign keys)
6. **Verify model fillable attributes**
7. **Look for validation rules** in controllers/requests

### Common Issues

**Issue**: `Column not found: conversation_id`
**Solution**: Check if column exists in migration, verify fillable array

**Issue**: `Call to undefined method`
**Solution**: Check model/class has the method you're testing

**Issue**: `Integrity constraint violation`
**Solution**: Create required parent records first (e.g., mailbox before conversation)

**Issue**: `Route [...] not defined`
**Solution**: Check routes/web.php or routes/api.php for correct route name

---

## 📊 MONITORING PROGRESS

### Check Your Coverage Impact

```bash
# Before starting
php artisan test --coverage-html coverage-report

# After adding tests
php artisan test --coverage-html coverage-report

# Compare the numbers in coverage-report/dashboard.html
```

### Track Your Test Count

```bash
# Count tests in your files
grep -r "public function test_" tests/Unit/Services/ | wc -l

# See your test output
php artisan test | grep "Tests:"
```

---

## ✅ PHASE COMPLETION CHECKLIST

Before marking your phase complete:

- [ ] All new tests are passing
- [ ] Coverage targets met (check coverage-report/dashboard.html)
- [ ] No database schema changes made
- [ ] All tests use factories (no hardcoded data)
- [ ] All tests have meaningful assertions (not just status codes)
- [ ] Tests are documented (clear names, comments if complex)
- [ ] Code follows existing patterns in codebase
- [ ] Ran full test suite: `php artisan test` - all passing
- [ ] Generated fresh coverage report
- [ ] Committed all changes with clear messages

---

## 📝 REPORTING COMPLETION

When your phase is complete, provide:

1. **Test Count**:
   ```
   Phase X Complete:
   - Tests Added: 25
   - Total Tests: 699 (was 674)
   - All Passing: ✅
   ```

2. **Coverage Impact**:
   ```
   Coverage Changes:
   - ImapService: 6% → 58% (+52%)
   - SmtpService: 40% → 72% (+32%)
   - Overall Lines: 49.78% → 55.32% (+5.54%)
   ```

3. **Files Modified/Created**:
   ```
   New Files:
   - tests/Unit/Services/ImapServiceAdvancedTest.php (15 tests)
   - tests/Unit/Jobs/SendConversationReplyJobTest.php (10 tests)
   
   Expanded Files:
   - tests/Unit/Services/SmtpServiceTest.php (+12 tests)
   ```

4. **Any Blockers or Issues**:
   ```
   Issues Encountered:
   - None, all tests passing ✅
   
   OR
   
   - ImapService requires mock IMAP server for full testing
   - Documented workaround in test comments
   ```

---

## 🆘 GETTING HELP

### Key Files to Reference

- **Test Plan**: `/var/www/html/docs/TEST_EXPANSION_PROPOSAL.md`
- **Existing Tests**: `/var/www/html/tests/`
- **Application Code**: `/var/www/html/app/`
- **Archive (DB Schema)**: `/var/www/html/archive/database/migrations/`
- **Coverage Report**: `/var/www/html/coverage-report/dashboard.html`

### Useful Commands

```bash
# Find a specific test
grep -r "test_conversation" tests/

# Find usage of a class
grep -r "ImapService" app/ tests/

# See all routes
php artisan route:list

# See all migrations
ls -la database/migrations/

# Clear cache if needed
php artisan config:clear && php artisan cache:clear
```

---

## 🎯 YOUR SPECIFIC ASSIGNMENT

**[Replace this section with agent-specific details]**

**Phase**: [X]
**Agent ID**: [Agent-X-Name]
**Components**: [List components]
**Target Tests**: [+XX tests]
**Duration Estimate**: [X-Y hours]
**Coverage Goal**: [Component: XX% → YY%]

**Focus Areas**:
1. [Specific component/feature]
2. [Specific component/feature]
3. [Specific component/feature]

**Success Criteria**:
- [Specific metric]
- [Specific metric]
- [Specific metric]

---

## 🚀 START HERE

1. ✅ Read this entire document
2. ✅ Run existing tests: `php artisan test`
3. ✅ Read your phase in TEST_EXPANSION_PROPOSAL.md
4. ✅ Review coverage report: check coverage-report/dashboard.html
5. ✅ Review implementation code in app/ for your components
6. ✅ Check archive database migrations for schema
7. ✅ Create your first test file
8. ✅ Write 3-5 tests
9. ✅ Run and verify they pass
10. ✅ Commit and continue

**Ready to begin? Start with step 1 above!**

---

**Document Version**: 1.0  
**Created**: November 7, 2025  
**Purpose**: Agent initialization and guidance  
**Related**: TEST_EXPANSION_PROPOSAL.md
