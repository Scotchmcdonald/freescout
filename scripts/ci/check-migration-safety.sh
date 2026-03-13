#!/usr/bin/env bash
# ==============================================================================
# DESTRUCTIVE MIGRATION CHECKER
# Fails if dropColumn, dropTable, or truncate are used without an override.
# ==============================================================================

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
echo "--> Checking for destructive database migrations..."

BAD_FILES=()

# Find all migration files in core and modules
while IFS= read -r file; do
    # Check for destructive schema methods
    if grep -qE "dropColumn|dropTable|truncate" "$file"; then
        # Check if the developer explicitly authorized it with a comment
        if ! grep -q "CI-ALLOW-DESTRUCTIVE" "$file"; then
            BAD_FILES+=("$file")
        fi
    fi
done < <(find "$ROOT_DIR/database/migrations" "$ROOT_DIR/Modules" -type f -name "*.php" | grep "migrations" 2>/dev/null)

if [ ${#BAD_FILES[@]} -gt 0 ]; then
    echo "❌ AUDIT FAILED: Destructive operations found without override comment:"
    echo ""
    printf '  - %s\n' "${BAD_FILES[@]}"
    echo ""
    echo "If this data loss is intentional, add '// CI-ALLOW-DESTRUCTIVE' to the file."
    exit 1
fi

echo "✅ AUDIT PASSED: No unsafe destructive migrations found."
exit 0
