# Test Implementation Plan for 90-95% Coverage

## Overview
This document details the comprehensive test plan to achieve 90-95% code coverage for 24 classes currently below this threshold.

**Target Coverage:** 90-95% line coverage and method coverage
**Total Classes:** 24
**Total Methods:** 115
**Existing Test Files:** 13 exist, 11 need creation/enhancement

## Testing Standards (from TESTING_GUIDE.md)
- ✅ Use PHP 8 attributes (NO @test annotations)
- ✅ Use `test_` prefix for test method names
- ✅ Extend appropriate base class: `FeatureTestCase`, `UnitTestCase`, or `IntegrationTestCase`
- ✅ Follow customer/email data model (emails in separate table)
- ✅ Use proper mocking for IMAP and external services
- ✅ Clean up database transactions properly

---

## Priority 1: Critical Priority (0-50% coverage) - 3 Classes

### 1. App\Console\Kernel (0% → 90-95%)
**File:** `app/Console/Kernel.php`
**Test File:** `tests/Unit/Console/KernelTest.php` (EXISTS - needs enhancement)
**Methods:** 2 (schedule, commands)

**Current State:** Basic tests exist but need expansion

**Tests Needed:**
1. ✅ Kernel instantiation and inheritance
2. ✅ Commands registration
3. ✅ Schedule method callable
4. **NEW:** Test that schedule() doesn't add any scheduled tasks (currently empty)
5. **NEW:** Test that commands() loads from __DIR__./Commands
6. **NEW:** Test that commands() requires console.php routes
7. **NEW:** Verify specific FreeScout commands are registered (module-install, module-update, module-build, etc.)
8. **NEW:** Test command loading is idempotent
9. **NEW:** Test that Laravel's default commands are also available
10. **NEW:** Edge case: Test with missing Commands directory (should handle gracefully)

**Implementation Notes:**
- Use reflection to test protected methods
- Mock filesystem for testing command loading
- Verify console routes are loaded via base_path('routes/console.php')

---

### 2. App\Http\Controllers\Api\ConversationController (0% → 90-95%)
**File:** `app/Http/Controllers/Api/ConversationController.php`
**Test File:** `tests/Unit/Http/Controllers/Api/ConversationControllerTest.php` (CREATED)
**Methods:** 1 (index)

**Current State:** New comprehensive test file created with 17 tests

**Tests Implemented:**
1. ✅ Controller instantiation
2. ✅ Inheritance from base controller
3. ✅ index() returns JsonResponse
4. ✅ index() returns empty array
5. ✅ index() returns 200 status
6. ✅ Response has JSON content type
7. ✅ Multiple calls return consistent results
8. ✅ Response is valid JSON
9. ✅ Data structure is array
10. ✅ No authentication requirement (handled by middleware)
11. ✅ No exceptions thrown
12. ✅ Method exists and is public
13. ✅ Return type check
14. ✅ Consistent response structure
15. ✅ Response can be decoded
16. ✅ Uses json response helper correctly
17. ✅ Response headers contain Content-Type

**Additional Tests Needed for 95%:**
1. **NEW:** Test with mocked Request object passed to index()
2. **NEW:** Test index() when called via route (integration test)
3. **NEW:** Test that index() doesn't access database
4. **NEW:** Test response serialization
5. **NEW:** Edge case: Test memory usage for multiple calls

**Status:** ✅ Should achieve 92-95% coverage with existing tests

---

### 3. App\Console\Commands\ModuleUpdate (33.87% → 90-95%)
**File:** `app/Console/Commands/ModuleUpdate.php`
**Test File:** `tests/Unit/Console/Commands/ModuleUpdateTest.php` (EXISTS - mostly placeholders)
**Methods:** 1 (handle) - complex method with many branches

**Current State:** Test file exists with 20 placeholder tests (expectNotToPerformAssertions)

**Tests to Implement (replacing placeholders):**

**Basic Functionality:**
1. ✅ Command signature and description
2. ✅ Optional module_alias argument
3. **ENHANCE:** Test handle() clears cache first (mock Artisan::call)
4. **ENHANCE:** Test handle() with no modules found
5. **ENHANCE:** Test handle() updates single module
6. **ENHANCE:** Test handle() updates all modules
7. **ENHANCE:** Test handle() when module not found (error message)
8. **ENHANCE:** Test handle() when no updates available

**Version Checking:**
9. **NEW:** Test version_compare logic for official modules
10. **NEW:** Test version_compare logic for custom modules
11. **NEW:** Test skipping modules without version info
12. **NEW:** Test handling of malformed version numbers

**Official Modules:**
13. **NEW:** Mock WpApi::getModules() response
14. **NEW:** Test handling of WpApi::$lastError
15. **NEW:** Test Module::updateModule() is called for outdated modules
16. **NEW:** Test success message display
17. **NEW:** Test error message display
18. **NEW:** Test output display from update

**Custom Modules:**
19. **NEW:** Mock Guzzle HTTP client
20. **NEW:** Test latestVersionUrl fetching
21. **NEW:** Test skipping official modules in custom loop
22. **NEW:** Test exception handling for network errors
23. **NEW:** Test empty latestVersionUrl handling
24. **NEW:** Test invalid JSON response handling

**Post-Update:**
25. **NEW:** Test freescout:clear-cache is called
26. **NEW:** Test counter increments correctly
27. **NEW:** Test "All modules are up-to-date" message
28. **NEW:** Test found flag for single module updates

**Edge Cases:**
29. **NEW:** Test with empty installed_modules
30. **NEW:** Test with empty modules_directory
31. **NEW:** Test with mix of official and custom modules
32. **NEW:** Test concurrent version checks
33. **NEW:** Test module update result statuses (success/failure)

**Mocking Strategy:**
```php
// Mock Module facade
Module::shouldReceive('all')->andReturn($mockModules);
Module::shouldReceive('updateModule')->andReturn(['status' => 'success', ...]);

// Mock WpApi
App\Misc\WpApi::shouldReceive('getModules')->andReturn($mockModulesDirectory);

// Mock Guzzle
$mockClient = Mockery::mock(\GuzzleHttp\Client::class);
$mockClient->shouldReceive('request')->andReturn($mockResponse);
```

**Status:** Needs significant enhancement - target 25-30 real tests

---

## Priority 2: High Priority (50-70% coverage) - 5 Classes

### 4. App\Console\Commands\ModuleBuild (50% → 90-95%)
**File:** `app/Console/Commands/ModuleBuild.php`
**Test File:** `tests/Unit/Console/Commands/ModuleBuildTest.php` (NEEDS CREATION)
**Methods:** 3 (handle, buildModule, buildVars)

**Tests Needed:**

**handle() method:**
1. Test with no modules found (error case)
2. Test building all modules (no module_alias)
3. Test building single module (with module_alias)
4. Test module not found by alias (error case)
5. Test success message display
6. Test return codes (0 for success, 1 for error)

**buildModule() method:**
7. Test module name display
8. Test public symlink existence check
9. Test error when symlink missing
10. Test buildVars() is called

**buildVars() method:**
11. Test with existing view
12. Test with missing view (skip generation)
13. Test directory creation
14. Test file writing
15. Test locales parameter passed to view
16. Test exception handling
17. Test error message display

**Edge Cases:**
18. Test with invalid module object
19. Test with missing public directory
20. Test with unwritable filesystem
21. Test view rendering errors
22. Test empty compiled view content

**Status:** Needs complete test file creation - target 20-22 tests

---

### 5. App\Listeners\RememberUserLocale (50% → 90-95%)
**File:** `app/Listeners/RememberUserLocale.php`
**Test File:** `tests/Unit/RememberUserLocaleListenerTest.php` (EXISTS - basic tests only)
**Methods:** 1 (handle)

**Current Tests:**
1. ✅ Listener has handle method
2. ✅ Handles login event without error

**Tests to Add:**
3. **NEW:** Test with user that has getLocale() method
4. **NEW:** Test locale is saved to session
5. **NEW:** Test with user without getLocale() method (should not error)
6. **NEW:** Test with null user
7. **NEW:** Test with different locale values ('en', 'fr', 'es', etc.)
8. **NEW:** Test session key is 'user_locale'
9. **NEW:** Test existing session value is overwritten
10. **NEW:** Test with Login event from different guard
11. **NEW:** Test with remember me token
12. **NEW:** Test locale persists across requests

**Edge Cases:**
13. **NEW:** Test with empty locale
14. **NEW:** Test with invalid locale format
15. **NEW:** Test when session driver unavailable

**Status:** Needs 13 additional tests - target 15 total tests

---

### 6. App\Listeners\UpdateMailboxCounters (50% → 90-95%)
**File:** `app/Listeners/UpdateMailboxCounters.php`
**Test File:** `tests/Unit/Listeners/UpdateMailboxCountersTest.php` (NEEDS CREATION)
**Methods:** 1 (handle)

**Tests Needed:**

**Basic Functionality:**
1. Test listener can be instantiated
2. Test handle() with ConversationStatusChanged event
3. Test handle() with ConversationUserChanged event
4. Test updateFoldersCounters() is called on mailbox
5. Test with mailbox that has updateFoldersCounters() method
6. Test with mailbox that doesn't have updateFoldersCounters() method (should not error)

**Event Handling:**
7. Test with real conversation and mailbox objects
8. Test method_exists check works correctly
9. Test event->conversation->mailbox chain is valid
10. Test with lazy-loaded relationships

**Edge Cases:**
11. Test with null mailbox
12. Test with conversation without mailbox
13. Test when updateFoldersCounters() throws exception
14. Test event handling is non-blocking
15. Test multiple events processed sequentially

**Mocking Strategy:**
```php
$event = Mockery::mock(ConversationStatusChanged::class);
$conversation = Mockery::mock(Conversation::class);
$mailbox = Mockery::mock(Mailbox::class);
$mailbox->shouldReceive('updateFoldersCounters')->once();
```

**Status:** Needs complete test file creation - target 15 tests

---

### 7. App\Listeners\SendReplyToCustomer (61.11% → 90-95%)
**File:** `app/Listeners/SendReplyToCustomer.php`
**Test File:** `tests/Unit/Listeners/SendReplyToCustomerTest.php` (EXISTS)
**Methods:** 1 (handle)

**Current Coverage:** 61.11% - needs additional tests

**Additional Tests Needed:**
1. Test email is sent to customer
2. Test correct email template is used
3. Test with different thread types
4. Test with attachments
5. Test email fails gracefully
6. Test queue job dispatching
7. Test email headers
8. Test with multiple customer emails
9. Test CC/BCC handling
10. Edge case: Test with deleted customer
11. Edge case: Test with invalid email address
12. Edge case: Test email service unavailable

**Status:** Needs 8-10 additional tests

---

### 8. App\Http\Controllers\SystemController (69.33% → 90-95%)
**File:** `app/Http/Controllers/SystemController.php`
**Test File:** `tests/Unit/Controllers/SystemControllerTest.php` (EXISTS)
**Methods:** 6 methods

**Current Coverage:** 69.33% - needs additional edge cases and error paths

**Additional Tests Needed (per method):**
1. **index():** Test with admin vs non-admin
2. **status():** Test system status checks
3. **phpInfo():** Test PHP info display
4. **logs():** Test log file reading, pagination, filtering
5. **clearCache():** Test cache clearing, success message
6. **tools():** Test various tools, permissions

**Edge Cases:**
- Large log files
- Missing log files
- Permission denied scenarios
- Invalid cache keys
- Concurrent cache clearing

**Status:** Needs 15-20 additional tests

---

## Priority 3: Medium Priority (70-85% coverage) - 9 Classes

### 9. App\Services\SmtpService (71.30% → 90-95%)
**Methods:** 5 (send, configure, test, validate, getErrors)

**Tests Needed:**
- Connection testing with valid/invalid credentials
- Email sending success/failure cases
- Configuration validation
- Error handling and reporting
- TLS/SSL configuration
- Timeout handling
- **Target:** 20-25 tests

### 10. App\Console\Commands\ModuleInstall (71.43% → 90-95%)
**Methods:** 3 (handle, installModule, createSymlink)

**Tests Needed:**
- Install single module
- Install all modules
- Symlink creation
- Error handling
- Module validation
- **Target:** 18-20 tests

### 11. App\Jobs\SendAlert (72.50% → 90-95%)
**Methods:** 1 (handle) - complex

**Tests Needed:**
- Alert types
- Recipient resolution
- Email/Slack/webhook delivery
- Queue handling
- Retry logic
- **Target:** 15-18 tests

### 12. App\Models\Thread (73.81% → 90-95%)
**Methods:** 17 methods
**Test File:** EXISTS with 24 tests

**Additional Tests Needed:**
1. **isAutoResponder():** Test with various header patterns
2. **isBounce():** Test bounce detection logic
3. **getCreatedBy():** Test user resolution
4. **getStatusName():** Test all status types
5. **getActionText():** Test action text generation with parameters
6. **getAssigneeName():** Test with/without assigned user
7. **sendLogs():** Test relationship
8. **editedByUser():** Test relationship with editor
9. **Edge cases:** Test with null values, edge timestamps
10. **isCustomerMessage(), isUserMessage(), isNote():** Boundary tests

**Target:** Add 12-15 tests (total 36-39 tests)

### 13-17. Additional Medium Priority Classes
- App\Console\Commands\Update (80%)
- App\Mail\AutoReply (80%)
- App\Policies\UserPolicy (81.82%)
- App\Console\Commands\LogoutUsers (83.33%)
- App\Jobs\SendNotificationToUsers (83.87%)

**Each needs:** 8-12 additional tests focusing on uncovered branches

---

## Priority 4: Lower Priority (85-90% coverage) - 7 Classes

### 18. App\Http\Controllers\ProfileController (85.19% → 90-95%)
**Methods:** 4 (edit, update, destroy, updatePassword)

**Missing Coverage:**
- Password validation edge cases
- Profile update conflicts
- Session handling
- Redirect logic edge cases
**Target:** 8-10 additional tests

### 19. App\Console\Commands\UpdateFolderCounters (85.71% → 90-95%)
### 20. App\Console\Commands\CheckRequirements (87.88% → 90-95%)
### 21. App\Listeners\SendAutoReply (88.57% → 90-95%)

**Each needs:** 5-8 tests for edge cases

### 22. App\Models\User (88.89% → 90-95%)
**Methods:** 17 methods
**Test File:** EXISTS

**Missing Coverage:**
- Permission checking edge cases
- Role-specific methods
- Relationship edge cases
- Locale handling
**Target:** 10-12 additional tests

### 23. App\Http\Controllers\SettingsController (89.32% → 90-95%)
**Methods:** 16 methods

**Missing Coverage:**
- Setting validation
- Permission checks for each setting type
- Edge cases in form handling
**Target:** 12-15 additional tests

### 24. App\Models\Conversation (89.55% → 90-95%)
**Methods:** 17 methods

**Missing Coverage:**
- Status transitions
- Assignment logic
- Folder movement
- Search functionality edge cases
**Target:** 10-12 additional tests

---

## Implementation Strategy

### Phase 1: Critical Tests (Classes 1-3)
**Estimated Tests:** 70-75 tests
**Priority:** IMMEDIATE
**Impact:** Brings 3 classes from 0-34% to 90-95%

### Phase 2: High Priority (Classes 4-8)
**Estimated Tests:** 85-95 tests
**Priority:** HIGH
**Impact:** Brings 5 classes from 50-69% to 90-95%

### Phase 3: Medium Priority (Classes 9-17)
**Estimated Tests:** 140-160 tests
**Priority:** MEDIUM
**Impact:** Brings 9 classes from 70-85% to 90-95%

### Phase 4: Lower Priority (Classes 18-24)
**Estimated Tests:** 65-75 tests
**Priority:** LOW
**Impact:** Brings 7 classes from 85-90% to 90-95%

### Total Estimated Tests to Implement
**Total:** 360-405 new/enhanced tests across all phases

---

## Test Writing Checklist

For each new test:
- [ ] Uses `test_` prefix (no @test annotation)
- [ ] Extends correct base class (UnitTestCase/FeatureTestCase/IntegrationTestCase)
- [ ] Has descriptive name that explains what is being tested
- [ ] Includes PHPDoc describing purpose if complex
- [ ] Mocks external dependencies appropriately
- [ ] Uses proper assertions (not just assertTrue)
- [ ] Tests one specific behavior
- [ ] Includes edge cases and error conditions
- [ ] Follows customer/email data model for user tests
- [ ] Properly cleans up resources in tearDown if needed

---

## Common Patterns

### Testing Console Commands
```php
public function test_command_with_argument(): void
{
    $this->artisan('command:name', ['arg' => 'value'])
        ->expectsOutput('Expected output')
        ->assertExitCode(0);
}
```

### Testing Listeners
```php
public function test_listener_handles_event(): void
{
    $event = new SomeEvent($data);
    $listener = new SomeListener();
    
    $listener->handle($event);
    
    $this->assertDatabaseHas('table', ['expected' => 'data']);
}
```

### Testing Controllers
```php
public function test_controller_method_returns_view(): void
{
    $user = User::factory()->create();
    $this->actingAs($user);
    
    $response = $this->get('/route');
    
    $response->assertOk();
    $response->assertViewIs('view.name');
}
```

### Testing Models
```php
public function test_model_method_returns_expected_value(): void
{
    $model = Model::factory()->create(['field' => 'value']);
    
    $result = $model->someMethod();
    
    $this->assertEquals('expected', $result);
}
```

### Testing Jobs
```php
public function test_job_processes_correctly(): void
{
    $job = new SomeJob($data);
    
    $job->handle();
    
    $this->assertDatabaseHas('table', ['processed' => true]);
}
```

---

## Notes

- **Don't run tests during implementation** - as specified in requirements
- Focus on comprehensive coverage of all code paths
- Include edge cases and error conditions
- Mock external dependencies (IMAP, HTTP clients, file system)
- Follow existing test patterns in the codebase
- Use factories for model creation
- Clean up test database properly

---

## Completion Criteria

✅ All 24 classes have 90-95% line coverage
✅ All 24 classes have 90-95% method coverage
✅ All tests follow TESTING_GUIDE.md standards
✅ All tests use PHP 8 attributes (no @test annotations)
✅ All tests have descriptive names
✅ Edge cases and error conditions are tested
✅ No placeholder tests (expectNotToPerformAssertions)
