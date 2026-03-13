#!/usr/bin/env bash
# ==============================================================================
# MIGRATION ROLLBACK AUDITOR
# Fails if a migration has an up() method but is missing a down() method.
# ==============================================================================

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
echo "--> Checking migrations for valid down() methods..."

BAD_FILES=()

while IFS= read -r file; do
    if grep -q "function up()" "$file"; then
        if ! grep -q "function down()" "$file"; then
            BAD_FILES+=("$file")
        fi
    fi
done < <(find "$ROOT_DIR/database/migrations" "$ROOT_DIR/Modules" -type f -name "*.php" | grep "migrations" 2>/dev/null)

if [ ${#BAD_FILES[@]} -gt 0 ]; then
    echo "❌ AUDIT FAILED: The following migrations cannot be rolled back (missing down method):"
    echo ""
    printf '  - %s\n' "${BAD_FILES[@]}"
    exit 1
fi

echo "✅ AUDIT PASSED: All migrations support rollbacks."
exit 0
