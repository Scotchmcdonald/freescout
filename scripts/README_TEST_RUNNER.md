# Test Runner Documentation

## Overview

The Freescout Test Runner (`test_runner.php`) supports both parallel and sequential test execution to balance speed with test reliability.

## How It Works

### Test Classification

Tests are automatically classified into two categories:

1. **Parallel Tests** (Default): Tests that can run safely in parallel batches
2. **Sequential Tests**: Tests marked with the `@sequential` annotation that need to run one batch at a time

### Marking Tests as Sequential

To mark a test class for sequential execution, add the `@sequential` annotation to the class docblock:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Tests that require sequential execution.
 * 
 * @sequential
 */
class DatabaseMigrationTest extends TestCase
{
    public function test_migration_up(): void
    {
        // Test code that has race conditions
    }
}
```

### Execution Flow

1. **Discovery Phase**: All test files are scanned and classified
2. **Parallel Phase**: Non-sequential tests run in parallel batches (no batch info shown)
3. **Sequential Phase**: Sequential tests run in batches with batch information displayed
4. **Summary**: Combined results from both phases

## Usage

```bash
# Run all tests (parallel + sequential)
php scripts/test_runner.php

# Run specific suite
php scripts/test_runner.php Feature

# Run with coverage
php scripts/test_runner.php --coverage

# Run with filter
php scripts/test_runner.php --filter=UserController
```

## When to Use Sequential Tests

Mark tests as sequential when they:

- Have race conditions when run in parallel
- Require exclusive access to shared resources (files, database tables, etc.)
- Modify global state that affects other tests
- Perform operations that cannot be safely isolated

## Performance Considerations

- Sequential tests are slower since they run one batch at a time
- Only mark tests as sequential if they truly need it
- Most tests should be able to run in parallel

## Progress Bar

The progress bar shows:
- **Green**: Passing tests
- **Orange**: Failing tests
- **Red**: Errors
- **Blue**: Skipped tests
- **Yellow**: Incomplete tests

During parallel execution, batch information is hidden for cleaner output.
During sequential execution, batch information is shown to help identify specific issues.
