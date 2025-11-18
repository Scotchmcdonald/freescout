# Testing Guide for FreeScout Laravel 11

## Overview

This guide documents critical lessons learned from fixing 813+ failing tests during the Laravel 11 migration. Following these guidelines will help you write tests that pass on the first try.

## ⚠️ CRITICAL: Use PHP 8 Attributes, NOT Doc-Comments

**PHPUnit 12 Requirement:** All test metadata MUST use PHP 8 attributes instead of doc-comment annotations.

❌ **WRONG (Deprecated):**
```php
/**
 * @test
 * @dataProvider myDataProvider
 * @depends testSomething
 */
public function it_does_something()
{
}
```

✅ **CORRECT (Required):**
```php
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;

#[Test]
#[DataProvider('myDataProvider')]
#[Depends('testSomething')]
public function it_does_something(): void
{
}
```

**OR simply prefix method names with `test_`:**
```php
public function test_it_does_something(): void
{
    // No attributes needed when method starts with "test_"
}
```

## Test Architecture

### Base Test Classes

We use a three-tier test architecture to prevent transaction pollution and ensure proper database isolation:

```php
// Feature Tests - Full application tests with HTTP requests
class MyFeatureTest extends FeatureTestCase
{
    // Automatically includes RefreshDatabase and transaction cleanup
}

// Unit Tests - Isolated component tests
class MyUnitTest extends UnitTestCase
{
    // Automatically includes RefreshDatabase and transaction cleanup
}

// Integration Tests - Controller tests with full authorization
class MyIntegrationTest extends IntegrationTestCase
{
    // Automatically includes RefreshDatabase and transaction cleanup
}
```

**CRITICAL:** Never extend `TestCase` directly if your test needs database access. Always use one of the above base classes.

### Why This Matters

**Problem:** Mixing tests that use `RefreshDatabase` directly with those that inherit it causes:
```
PDOException: There is already an active transaction
```

**Solution:** Each test type has its own base class with proper transaction cleanup in `setUp()` and `tearDown()`.

## Customer and Email Data Model

### Critical Understanding

The `customers` table does **NOT** have an `email` column. Emails are stored in a separate `emails` table via a `hasMany` relationship.

```php
// customers table columns:
// id, first_name, last_name, created_at, updated_at

// emails table columns:
// id, customer_id, email, type, created_at, updated_at
```

### Common Mistakes

❌ **WRONG:**
```php
// This will fail - no email column exists!
$this->assertDatabaseHas('customers', ['email' => 'test@example.com']);

// This will fail - Customer::where() doesn't have email column
$customer = Customer::where('email', 'test@example.com')->first();

// This will fail - can't create customer with email attribute
Customer::factory()->create(['email' => 'test@example.com']);
```

✅ **CORRECT:**
```php
// Check emails table instead
$this->assertDatabaseHas('emails', ['email' => 'test@example.com']);

// Query via relationship
$customer = Customer::whereHas('emails', fn($q) => $q->where('email', 'test@example.com'))->first();

// Use CustomerFactory's create() override which handles email
Customer::factory()->create(['email' => 'test@example.com']); // Now works!

// Or create separately
$customer = Customer::factory()->create();
$customer->emails()->create(['email' => 'test@example.com']);
```

### CustomerFactory Behavior

The `CustomerFactory` has been enhanced with a `create()` override that handles the `email` attribute:

```php
// This now works correctly:
$customer = Customer::factory()->create([
    'first_name' => 'John',
    'email' => 'john@example.com', // Automatically creates email record
]);

// Behind the scenes:
// 1. Extracts 'email' from attributes
// 2. Creates customer without email
// 3. Creates email record in emails table
// 4. Associates email with customer
```

## Unit vs Feature Tests: Database Usage

### When to Use Pure Unit Tests (No Database)

**Rule of Thumb:** If you can test the logic without touching the database, you should.

#### Pure Unit Test Pattern

```php
class ImapServiceFetchEmailsTest extends TestCase  // Note: Plain TestCase, not UnitTestCase!
{
    // No RefreshDatabase trait!
    // No database migrations!
    
    public function test_successful_fetch_with_mocked_dependencies(): void
    {
        // Mock the Mailbox model instead of creating it
        $mailbox = Mockery::mock(Mailbox::class)->makePartial();
        $mailbox->id = 1;
        $mailbox->in_server = 'imap.example.com';
        $mailbox->in_port = 993;
        $mailbox->imap_folder_path = 'INBOX';
        
        // Mock IMAP client
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('connect')->once();
        // ... more mocks
        
        // Test the service logic without database
        $service = Mockery::mock(ImapService::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('createClient')->andReturn($mockClient);
        
        $result = $service->fetchEmails($mailbox);
        
        $this->assertIsArray($result);
    }
    
    protected function tearDown(): void
    {
        Mockery::close();  // Always close Mockery in unit tests
        parent::tearDown();
    }
}
```

#### Key Points:

1. **Extend `TestCase`** (NOT `UnitTestCase`) - no database needed
2. **Mock model objects** instead of `factory()->create()`:
   ```php
   // ❌ WRONG - Requires database
   $mailbox = Mailbox::factory()->create();
   
   // ✅ RIGHT - Pure mocking
   $mailbox = Mockery::mock(Mailbox::class)->makePartial();
   $mailbox->id = 1;
   $mailbox->in_server = 'imap.example.com';
   ```
3. **Use `makePartial()`** to allow property assignment without triggering `setAttribute()`
4. **Always `Mockery::close()`** in `tearDown()` to verify expectations

#### Protected Method Mocking

When testing a class that calls its own protected methods:

```php
// Enable protected method mocking
$service = Mockery::mock(ImapService::class)
    ->makePartial()
    ->shouldAllowMockingProtectedMethods();

// Now you can mock protected createClient()
$service->shouldReceive('createClient')
    ->with($mailbox)
    ->andReturn($mockClient);
```

#### Common Pitfall: Eloquent Model Properties

```php
// ❌ WRONG - Triggers setAttribute() which needs expectations
$mailbox = Mockery::mock(Mailbox::class);
$mailbox->in_server = 'test';  // BadMethodCallException!

// ✅ RIGHT - makePartial() allows property setting
$mailbox = Mockery::mock(Mailbox::class)->makePartial();
$mailbox->in_server = 'test';  // Works!
```

### When to Use Feature Tests (With Database)

Use `FeatureTestCase` when:
- Testing end-to-end user workflows
- Verifying actual database persistence
- Testing model relationships
- Integration between multiple components

```php
class ConversationCreationTest extends FeatureTestCase
{
    use RefreshDatabase;  // Already in FeatureTestCase, shown for clarity
    
    public function test_creates_conversation_from_email(): void
    {
        $mailbox = Mailbox::factory()->create();  // Real database record
        
        // ... test actual database interactions
        
        $this->assertDatabaseHas('conversations', [
            'mailbox_id' => $mailbox->id,
        ]);
    }
}
```

## IMAP Testing Patterns

### Mock Object Requirements

When mocking IMAP objects, be aware of type requirements:

#### 1. Query Must Return WhereQuery

The IMAP library uses `WhereQuery` for query building, not the base `Query` class:

❌ **WRONG:**
```php
use Webklex\PHPIMAP\Query\Query;

$mockQuery = Mockery::mock(Query::class);
$mockFolder->shouldReceive('query')->andReturn($mockQuery);
// TypeError: Return value must be of type Webklex\PHPIMAP\Query\WhereQuery
```

✅ **CORRECT:**
```php
use Webklex\PHPIMAP\Query\WhereQuery;

$mockQuery = Mockery::mock(WhereQuery::class);
$mockFolder->shouldReceive('query')->andReturn($mockQuery);
$mockQuery->shouldReceive('since')->andReturnSelf();
$mockQuery->shouldReceive('unseen')->andReturnSelf();
$mockQuery->shouldReceive('leaveUnread')->andReturnSelf();
$mockQuery->shouldReceive('get')->andReturn(new MessageCollection());
```

#### 2. Address Objects Must Be Stringable

❌ **WRONG:**
```php
$message->shouldReceive('getFrom')
    ->andReturn([(object)['mail' => 'from@example.com']]);
```

The service tries to cast addresses to strings, causing:
```
Error: Object of class stdClass could not be converted to string
```

✅ **CORRECT:**
```php
use Tests\Unit\Services\MockImapAddress;

$message->shouldReceive('getFrom')
    ->andReturn([new MockImapAddress('from@example.com', 'From Name')]);
```

Or add `method_exists` checks in production code:
```php
if (method_exists($addr, '__toString')) {
    $email = (string) $addr;
}
```

#### 2. Header::get() Return Type

❌ **WRONG:**
```php
$header->shouldReceive('get')
    ->with('subject')
    ->andReturn(null); // TypeError!
```

The `Header::get()` method has a return type of `Attribute`, not `?Attribute`.

✅ **CORRECT:**
```php
use Webklex\PHPIMAP\Attribute;

// For empty values, return empty Attribute
$header->shouldReceive('get')
    ->with('subject')
    ->andReturn(new Attribute('subject', []));

// For values, ensure it can be called with first()
$subjectAttr = Mockery::mock(Attribute::class);
$subjectAttr->shouldReceive('first')->andReturn('Test Subject');
$header->shouldReceive('get')->with('subject')->andReturn($subjectAttr);
```

#### 3. AttachmentCollection Type

❌ **WRONG:**
```php
$service->processMessage($mailbox, [
    'attachments' => collect([]), // Wrong type!
]);
```

The service expects `AttachmentCollection`, not generic `Collection`.

✅ **CORRECT:**
```php
use Webklex\PHPIMAP\Support\AttachmentCollection;

$service->processMessage($mailbox, [
    'attachments' => new AttachmentCollection(), // Correct!
]);
```

### Complete IMAP Mock Example

```php
use Mockery;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Header;
use Webklex\PHPIMAP\Attribute;
use Webklex\PHPIMAP\Support\AttachmentCollection;
use Tests\Unit\Services\MockImapAddress;

$message = Mockery::mock(Message::class);

// Header mock with proper return types
$header = Mockery::mock(Header::class);
$subjectAttr = Mockery::mock(Attribute::class);
$subjectAttr->shouldReceive('first')->andReturn('Test Subject');
$header->shouldReceive('get')->with('subject')->andReturn($subjectAttr);

// For empty attributes
$emptyAttr = Mockery::mock(Attribute::class);
$emptyAttr->shouldReceive('first')->andReturn(null);
$header->shouldReceive('get')->with('in_reply_to')->andReturn($emptyAttr);

$message->shouldReceive('getHeader')->andReturn($header);

// Address mocks with __toString()
$message->shouldReceive('getFrom')
    ->andReturn([new MockImapAddress('from@example.com', 'From Name')]);

$message->shouldReceive('getTo')
    ->andReturn([new MockImapAddress('to@example.com', 'To Name')]);

// Other mocks
$message->shouldReceive('getTextBody')->andReturn('Email body');
$message->shouldReceive('getAttachments')->andReturn(new AttachmentCollection());
$message->shouldReceive('getMessageId')->andReturn('<unique-id@example.com>');
```

## Database Transaction Issues

### Symptom: Tests Pollute Each Other

```
PDOException: There is already an active transaction
SQLSTATE[HY000]: General error: 1 database is locked
```

### Root Cause

Tests that don't properly clean up transactions can leave the database in an inconsistent state for subsequent tests.

### Solution

Our base test classes include aggressive transaction cleanup:

```php
protected function setUp(): void
{
    parent::setUp();
    
    // Clean up any lingering transactions
    while (DB::transactionLevel() > 0) {
        DB::rollBack();
    }
}

protected function tearDown(): void
{
    // Ensure clean state after test
    while (DB::transactionLevel() > 0) {
        DB::rollBack();
    }
    
    parent::tearDown();
}
```

**Action Required:** If you create a new test file, ensure it extends the appropriate base class (`FeatureTestCase`, `UnitTestCase`, or `IntegrationTestCase`).

## Test Execution Order

Tests are executed in a specific order to minimize issues:

```xml
<!-- phpunit.xml -->
<testsuites>
    <testsuite name="Feature">
        <directory>tests/Feature</directory>
    </testsuite>
    <testsuite name="Integration">
        <directory>tests/Integration</directory>
    </testsuite>
    <testsuite name="Unit">
        <directory>tests/Unit</directory>
    </testsuite>
</testsuites>
```

**Why:** Feature tests set up the full application context, Integration tests verify controller behavior, and Unit tests run isolated component tests last.

## Quick Checklist for New Tests

Before submitting a test, verify:

- [ ] ⚠️ **CRITICAL:** Does NOT use `/** @test */` annotations (use `test_` prefix instead)
- [ ] ⚠️ **CRITICAL:** Does NOT use any doc-comment annotations (`@dataProvider`, `@depends`, `@group`)
- [ ] Extends correct base class (`FeatureTestCase`, `UnitTestCase`, or `IntegrationTestCase`)
- [ ] Does NOT extend `TestCase` directly if using database
- [ ] Does NOT use `RefreshDatabase` trait directly (inherited from base class)
- [ ] Customer email checks use `emails` table or `whereHas('emails', ...)`
- [ ] IMAP mocks use `MockImapAddress` for address objects
- [ ] IMAP mocks return `Attribute` objects from `Header::get()`, not null
- [ ] IMAP mocks use `AttachmentCollection`, not `collect([])`
- [ ] No hardcoded assumptions about database schema (e.g., customers.email column)

## Common Patterns

### Testing Customer Creation from Email

```php
public function test_creates_customer_from_email(): void
{
    $service = app(ImapService::class);
    $mailbox = Mailbox::factory()->create();
    
    $message = $this->createMockMessage([
        'from' => [new MockImapAddress('customer@example.com', 'John Doe')],
    ]);
    
    $service->processMessage($mailbox, $message);
    
    // Check email was created
    $this->assertDatabaseHas('emails', ['email' => 'customer@example.com']);
    
    // Check customer exists with that email
    $customer = Customer::whereHas('emails', fn($q) => 
        $q->where('email', 'customer@example.com')
    )->first();
    
    $this->assertNotNull($customer);
    $this->assertEquals('John', $customer->first_name);
}
```

### Testing Thread Creation

```php
public function test_creates_thread_for_conversation(): void
{
    $mailbox = Mailbox::factory()->create();
    $conversation = Conversation::factory()
        ->for($mailbox)
        ->create();
    
    $message = $this->createMockMessage([
        'body' => 'Test reply body',
    ]);
    
    $service = app(ImapService::class);
    $service->processMessage($mailbox, $message);
    
    $this->assertDatabaseHas('threads', [
        'conversation_id' => $conversation->id,
        'type' => Thread::TYPE_MESSAGE,
    ]);
}
```

### Testing Policy Authorization

```php
class ThreadPolicyTest extends UnitTestCase // Not TestCase!
{
    public function test_user_can_view_thread_in_authorized_mailbox(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        
        $conversation = Conversation::factory()
            ->for($mailbox)
            ->create();
            
        $thread = Thread::factory()
            ->for($conversation)
            ->create();
        
        $policy = new ThreadPolicy();
        $this->assertTrue($policy->view($user, $thread));
    }
}
```

## Debugging Test Failures

### ⚠️ CRITICAL: Check for @test Annotations FIRST

**If your test suite hangs or times out, this is the #1 cause:**

```bash
# Search for problematic @test annotations
grep -r "/** @test \*/" tests/

# Count how many files have the issue
grep -r "/** @test \*/" tests/ --include="*Test.php" | cut -d: -f1 | sort -u | wc -l
```

**Quick Fix for All Files:**
```bash
# Remove all @test annotations
find tests/ -name "*Test.php" -exec sed -i '/^[[:space:]]*\/\*\* @test \*\/$/d' {} \;

# Add test_ prefix to methods starting with "it_"
find tests/ -name "*Test.php" -exec sed -i 's/public function it_/public function test_it_/g' {} \;

# Verify all annotations are gone
grep -r "/** @test \*/" tests/  # Should return nothing
```

**Why This Causes Hangs:**
- PHPUnit 11 generates 1 deprecation warning per `@test` annotation
- 200+ annotations = 200+ warnings flooding the output buffer
- Output buffer overflow causes test process to hang/timeout
- Tests pass individually but hang when run together

**Real Example from Phase 4K:**
- 21 test files with `@test` annotations
- Full test suite hung after 60 seconds
- After removing annotations: 2744 tests pass in 140 seconds

### Enable Detailed Errors

```bash
# Run single test with full output
php artisan test --filter=test_name_here

# Run with stop-on-failure
php artisan test --stop-on-failure

# Save output for analysis
php artisan test > test_results.txt 2>&1
```

### Common Error Messages

| Error | Likely Cause | Fix |
|-------|-------------|-----|
| **Test suite hangs/times out** | `/** @test */` annotations causing 200+ deprecation warnings that flood output | Remove all `@test` annotations, use `test_` prefix |
| **Deprecation warnings in test output** | Using doc-comment annotations instead of attributes | Replace `@test` with `test_` prefix or `#[Test]` attribute |
| `PDOException: There is already an active transaction` | Wrong base class or missing transaction cleanup | Extend `FeatureTestCase`, `UnitTestCase`, or `IntegrationTestCase` |
| `SQLSTATE[HY000]: General error: 1 no such column: email` | Querying customers.email column | Query `emails` table or use `whereHas('emails', ...)` |
| `Object of class stdClass could not be converted to string` | IMAP mock without __toString() | Use `MockImapAddress` class |
| `Return value must be of type Webklex\PHPIMAP\Attribute, null returned` | Header::get() returning null | Return empty `Attribute` object instead |
| `Argument must be of type AttachmentCollection, Collection given` | Wrong collection type | Use `new AttachmentCollection()` |

## Performance Tips

### Use Factories Efficiently

```php
// Good - create in bulk
$customers = Customer::factory()->count(100)->create();

// Better - create without events if not needed
$customers = Customer::factory()
    ->count(100)
    ->createQuietly();
```

### Avoid N+1 in Tests

```php
// Bad - causes N+1 queries
foreach ($conversations as $conversation) {
    $conversation->customer->first_name; // Query each time!
}

// Good - eager load
$conversations = Conversation::with('customer')->get();
foreach ($conversations as $conversation) {
    $conversation->customer->first_name; // No query!
}
```

## Summary of Lessons Learned

1. **Transaction Pollution** - The root cause of 864+ initial failures was `RefreshDatabase` being applied twice (base class + child class)
2. **Schema Assumptions** - 60+ failures from assuming `customers` table had `email` column
3. **Type Safety** - 95+ failures from IMAP mocks not respecting type hints
4. **Relationship Queries** - 22+ failures from querying wrong table for customer emails

Following this guide, these 813+ failures could have been prevented before the tests were ever written.

## Getting Help

If your test fails:

1. Check this guide first
2. Verify you're using the correct base test class
3. Review the schema - don't assume column names
4. Check mock object types match what's expected
5. Look at similar passing tests for patterns

## Version History

- **2025-11-14**: Initial version documenting Laravel 11 migration test fixes
  - 813 tests fixed from transaction pollution, schema assumptions, and type safety issues
  - Added base test class architecture
  - Documented customer/email relationship pattern
  - Added IMAP testing patterns

## PHPUnit 12 Compatibility - MANDATORY

### ⚠️ ALL Metadata Must Use Attributes

**PHPUnit 12 will REMOVE support for doc-comment annotations.** All test metadata must use PHP 8 attributes.

### Quick Reference - Annotation to Attribute Conversion

| Deprecated Annotation | Required Attribute | Import |
|----------------------|-------------------|--------|
| `/** @test */` | Use `test_` prefix OR `#[Test]` | `use PHPUnit\Framework\Attributes\Test;` |
| `/** @dataProvider myProvider */` | `#[DataProvider('myProvider')]` | `use PHPUnit\Framework\Attributes\DataProvider;` |
| `/** @depends testSomething */` | `#[Depends('testSomething')]` | `use PHPUnit\Framework\Attributes\Depends;` |
| `/** @group slow */` | `#[Group('slow')]` | `use PHPUnit\Framework\Attributes\Group;` |
| `/** @covers \App\Service */` | `#[CoversClass(Service::class)]` | `use PHPUnit\Framework\Attributes\CoversClass;` |

### Best Practice: Use `test_` Prefix (Simplest)

The easiest way to avoid deprecation warnings is to prefix all test method names with `test_`:

```php
class MyTest extends UnitTestCase
{
    // ✅ No attributes needed - method name starts with "test_"
    public function test_it_does_something(): void
    {
        $this->assertTrue(true);
    }
    
    // ✅ Also works
    public function test_another_behavior(): void
    {
        $this->assertTrue(true);
    }
}
```

### When You MUST Use Attributes

Only use attributes when you need specific PHPUnit features:

```php
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

class MyTest extends UnitTestCase
{
    #[DataProvider('provideTestData')]
    public function test_with_data_provider(string $input, string $expected): void
    {
        $this->assertEquals($expected, $input);
    }
    
    public static function provideTestData(): array
    {
        return [
            ['input1', 'expected1'],
            ['input2', 'expected2'],
        ];
    }
    
    #[Group('slow')]
    public function test_slow_operation(): void
    {
        // Test code
    }
}
