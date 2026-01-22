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
bash "$SCRIPT_DIR/check-event-inheritance.sh" || EXIT_CODE=1
bash "$SCRIPT_DIR/check-listener-inheritance.sh" || EXIT_CODE=1

echo ""
if [ $EXIT_CODE -eq 0 ]; then
    echo "✅ All architecture compliance checks passed!"
else
    echo "❌ Architecture compliance checks failed!"
fi

exit $EXIT_CODE
