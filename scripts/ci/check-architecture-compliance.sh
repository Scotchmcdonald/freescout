#!/bin/bash
set -e

echo "🔍 Running Architecture Compliance Checks..."
echo "============================================"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
EXIT_CODE=0

# Run each compliance check
bash "$SCRIPT_DIR/check-core-blindness.sh" || EXIT_CODE=1
bash "$SCRIPT_DIR/check-cross-module-imports.sh" || EXIT_CODE=1
bash "$SCRIPT_DIR/check-atomic-counters.sh" || EXIT_CODE=1
bash "$SCRIPT_DIR/check-rate-limiter-usage.sh" || EXIT_CODE=1
bash "$SCRIPT_DIR/check-ui-ux-standards.sh" || EXIT_CODE=1
bash "$SCRIPT_DIR/check-event-inheritance.sh" || EXIT_CODE=1
bash "$SCRIPT_DIR/check-listener-inheritance.sh" || EXIT_CODE=1

# Phase 4 architecture guard subset (fast, deterministic)
php artisan test tests/Architecture/CriticalNamespaceBoundaryGuardTest.php --parallel --processes=10 || EXIT_CODE=1
php artisan test tests/Architecture/BillingPaymentTypeCoverageGuardTest.php --parallel --processes=10 || EXIT_CODE=1

# Phase 5: Mutation testing gate for critical app services (Tier 2)
bash "$SCRIPT_DIR/check-mutation-tier2.sh" || EXIT_CODE=1

echo ""
if [ $EXIT_CODE -eq 0 ]; then
    echo "✅ All architecture compliance checks passed!"
else
    echo "❌ Architecture compliance checks failed!"
fi

exit $EXIT_CODE
