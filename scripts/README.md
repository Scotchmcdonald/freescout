# Development Scripts

This directory contains utility scripts for maintaining the FreeScout development environment.

## Available Scripts

### `phpstan_runner.php`

Unified runner for PHPStan static analysis.

**Usage:**
```bash
# Run analysis (default level from phpstan.neon)
php scripts/phpstan_runner.php

# Run analysis with specific level
php scripts/phpstan_runner.php analyse --level=5

# Run "bodyscan" (check error counts for levels 0-9)
php scripts/phpstan_runner.php bodyscan

# Generate new baseline
php scripts/phpstan_runner.php baseline
```

### `test_runner.php`

Helper script for running tests.

**Usage:**
```bash
php scripts/test_runner.php
```

## Notes

- These scripts are designed to be run from the project root.
- They wrap standard tools (PHPStan, PHPUnit) to provide consistent configuration and convenience commands.
