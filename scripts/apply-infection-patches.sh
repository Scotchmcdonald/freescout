#!/usr/bin/env bash
# Apply vendor patches for Infection mutation testing compatibility.
# Run this after `composer install` or `composer update`.
#
# Patches:
#   1. infection-pest-p-prefix-fix.patch
#      Fixes Pest 4.x "P\" namespace prefix mismatch between coverage-xml
#      and junit.xml when running Infection mutation testing.
#      See: https://github.com/infection/infection/issues (upstream compat issue)

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
PATCHES_DIR="$PROJECT_ROOT/patches"
TARGET_FILE="$PROJECT_ROOT/vendor/infection/infection/src/TestFramework/Coverage/JUnit/JUnitTestFileDataProvider.php"

echo "Applying infection vendor patches..."

# Check if patch is already applied
if grep -q "P\\\\" "$TARGET_FILE" 2>/dev/null; then
    echo "  ✓ infection-pest-p-prefix-fix.patch already applied"
else
    PATCH_FILE="$PATCHES_DIR/infection-pest-p-prefix-fix.patch"
    if [ -f "$PATCH_FILE" ]; then
        patch -p1 --directory="$PROJECT_ROOT" < "$PATCH_FILE" && \
            echo "  ✓ infection-pest-p-prefix-fix.patch applied successfully" || \
            echo "  ✗ Failed to apply infection-pest-p-prefix-fix.patch (may need manual review)"
    else
        echo "  ✗ Patch file not found: $PATCH_FILE"
        exit 1
    fi
fi

echo "Infection patches complete."
