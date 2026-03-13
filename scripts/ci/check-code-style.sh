#!/usr/bin/env bash
# ==============================================================================
# LARAVEL PINT CODE STYLE AUDITOR
# Fails the build if code does not match the Laravel formatting standard.
# ==============================================================================

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
echo "--> Running Laravel Pint format check..."

if [ -f "$ROOT_DIR/vendor/bin/pint" ]; then
    # Run in test mode so it exits with code 1 on failure
    "$ROOT_DIR/vendor/bin/pint" --test

    if [ $? -ne 0 ]; then
        echo "❌ AUDIT FAILED: Code style violations found."
        echo "Run './vendor/bin/pint' locally to automatically fix these issues."
        exit 1
    fi
else
    echo "⚠️  WARNING: Laravel Pint binary not found in vendor/. Skipping."
    exit 0
fi

echo "✅ AUDIT PASSED: Code style is perfectly formatted."
exit 0
