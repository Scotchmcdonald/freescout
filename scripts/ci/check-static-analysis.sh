#!/usr/bin/env bash
# ==============================================================================
# PHPSTAN STATIC ANALYSIS AUDITOR
# Fails the build if static analysis detects potential fatal errors.
# ==============================================================================

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
echo "--> Running PHPStan static analysis..."

if [ -f "$ROOT_DIR/vendor/bin/phpstan" ]; then
    # Run analysis with sufficient memory for a large modular app
    "$ROOT_DIR/vendor/bin/phpstan" analyse --memory-limit=2G

    if [ $? -ne 0 ]; then
        echo "❌ AUDIT FAILED: Static analysis detected errors."
        exit 1
    fi
else
    echo "⚠️  WARNING: PHPStan binary not found in vendor/. Skipping."
    exit 0
fi

echo "✅ AUDIT PASSED: Static analysis found no issues."
exit 0
