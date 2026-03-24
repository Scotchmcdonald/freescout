# Development Scripts

This directory contains utility scripts for maintaining code quality and running tests in the FreeScout development environment.

## Available Scripts

### `phpstan_runner.php`

Unified runner for PHPStan static analysis with convenient commands for different workflows.

**Usage:**
```bash
# Run analysis with default level from phpstan.neon
php scripts/phpstan_runner.php

# Run analysis at specific level
php scripts/phpstan_runner.php analyse --level=5

# Check error counts across all levels (0-9)
php scripts/phpstan_runner.php bodyscan

# Generate new baseline file
php scripts/phpstan_runner.php baseline
```

**What it does:**
- Wraps PHPStan with consistent configuration
- Provides quick "bodyscan" overview of code quality
- Manages baseline files for incremental improvements

### `test_runner.php`

Flexible test runner with support for different test types and filtering.

**Usage:**
```bash
# Run all tests
php scripts/test_runner.php

# Run specific test suite
php scripts/test_runner.php Feature

# Run specific test file
php scripts/test_runner.php Modules/Payment/tests/

# Additional PHPUnit options
php scripts/test_runner.php --filter=testSpecificMethod
```

**What it does:**
- Wraps PHPUnit with project-specific configuration
- Simplifies running module-specific tests
- Maintains consistent test execution environment

### `code_stats.php`

Generate statistics about codebase size and composition.

**Usage:**
```bash
php scripts/code_stats.php
```

**Outputs:**
- Lines of code by directory
- File counts by type
- Test coverage metrics

## Additional Documentation

- **[Module Development Guide](../docs/development/MODULE_DEVELOPMENT_GUIDE.md)** - Steps and standards for updating legacy modules
- **[Testing Contribution Guide](../docs/testing/TESTING_CONTRIBUTION_GUIDE.md)** - Test execution and contribution requirements

## Notes

- All scripts should be run from the project root directory
- Scripts automatically load Laravel's environment and configuration
- See individual script files for additional options and flags
