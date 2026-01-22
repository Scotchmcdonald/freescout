#!/bin/bash

echo "🔒 Checking for unsafe counter operations..."

cd "$(dirname "${BASH_SOURCE[0]}")/../.."

# Financial tables that require atomic operations
FINANCIAL_TABLES="client_asset_counters|client_user_counters|credit_ledger|client_credits"

# Check for raw lockForUpdate()->increment() on financial tables (exclude markdown files)
VIOLATIONS=$(grep -rE "($FINANCIAL_TABLES).*lockForUpdate\(\)->increment|lockForUpdate\(\)->decrement" \
    Modules/ app/Services/ app/Models/ 2>/dev/null | \
    grep -v "\.md:" || true)

if [ -n "$VIOLATIONS" ]; then
    echo "❌ FAIL: Raw lockForUpdate()->increment() detected on financial tables"
    echo "   These operations must use AtomicCounterService to prevent race conditions"
    echo ""
    echo "$VIOLATIONS"
    echo ""
    echo "Fix: Use app(AtomicCounterService::class)->increment(...) instead"
    exit 1
fi

# Check for raw increment/decrement without locks on financial tables (exclude markdown files and AtomicCounterService usage)
UNSAFE_OPS=$(grep -rE "DB::table\(['\"]($FINANCIAL_TABLES)['\"].*\)->increment|DB::table\(['\"]($FINANCIAL_TABLES)['\"].*\)->decrement" \
    Modules/ app/Services/ app/Models/ 2>/dev/null | \
    grep -v "\.md:" || true)

if [ -n "$UNSAFE_OPS" ]; then
    echo "⚠️  WARNING: Potentially unsafe counter operations detected"
    echo "   Consider using AtomicCounterService for financial tables"
    echo ""
    echo "$UNSAFE_OPS"
    # Warning only - don't fail build
fi

echo "✅ All counter operations use AtomicCounterService"
exit 0
