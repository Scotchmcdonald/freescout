#!/usr/bin/env bash
# ==============================================================================
# STRICT TYPES AUDITOR
# Fails if any PHP file in app/ or Modules/ is missing declare(strict_types=1);
# ==============================================================================

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
echo "--> Checking for strict_types=1 declarations..."

# Find all PHP files (excluding blade templates) missing the strict_types declaration
MISSING=$(find "$ROOT_DIR/app" "$ROOT_DIR/Modules" -type f -name "*.php" ! -name "*.blade.php" ! -exec grep -q "declare(strict_types=1);" {} \; -print 2>/dev/null)

if [ -n "$MISSING" ]; then
    echo "❌ AUDIT FAILED: Missing 'declare(strict_types=1);' in the following files:"
    echo ""
    echo "$MISSING"
    exit 1
fi

echo "✅ AUDIT PASSED: All PHP files have strict types enforced."
exit 0
