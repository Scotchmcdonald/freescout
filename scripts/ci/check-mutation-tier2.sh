#!/usr/bin/env bash
# ==============================================================================
# MUTATION TESTING: TIER 2 (App Services + Actions)
# Runs post-PR to gate MSI degradation in critical business logic.
# ==============================================================================

set -e

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
CONFIG_FILE="$ROOT_DIR/infection-extended.json5"
MIN_MSI=70
MIN_COVERED_MSI=75
THREADS=6
TIMEOUT_MINUTES=45

echo "🧬 Mutation Testing: Tier 2 (app/Services + app/Actions)"
echo "========================================================"
echo "Configuration: $CONFIG_FILE"
echo "Thresholds: MSI ≥ $MIN_MSI, Covered MSI ≥ $MIN_COVERED_MSI"
echo "Threads: $THREADS"
echo "Timeout: ${TIMEOUT_MINUTES} minutes"
echo ""

# Verify coverage XML exists (mutation testing requires prior coverage collection)
if [ ! -d "$ROOT_DIR/storage/infection/coverage" ]; then
    echo "⚠️  Coverage XML not found. Collection coverage first..."
    XDEBUG_MODE=coverage php -d memory_limit=3G "$ROOT_DIR/vendor/bin/pest" \
        --coverage-xml="$ROOT_DIR/storage/infection/coverage" \
        --no-coverage-ignore-unmocked-methods \
        > /dev/null 2>&1 || true
fi

if [ ! -d "$ROOT_DIR/storage/infection/coverage" ]; then
    echo "❌ Failed to collect coverage. Aborting mutation testing."
    exit 1
fi

# Run Infection
timeout $((TIMEOUT_MINUTES * 60)) \
    php "$ROOT_DIR/vendor/bin/infection" \
    --configuration="$CONFIG_FILE" \
    --threads="$THREADS" \
    --min-msi="$MIN_MSI" \
    --min-covered-msi="$MIN_COVERED_MSI" \
    --log-junit="$ROOT_DIR/reports/infection-extended-junit.xml" \
    --ansi \
    --no-interaction \
    2>&1 | tee "$ROOT_DIR/reports/infection-extended.log"

EXIT_CODE=${PIPESTATUS[0]}

# Parse results
if [ -f "$ROOT_DIR/reports/infection-extended-summary.json" ]; then
    echo ""
    echo "📊 Mutation Summary (Tier 2):"
    php -r "
        \$data = json_decode(file_get_contents('$ROOT_DIR/reports/infection-extended-summary.json'), true);
        \$stats = \$data['stats'] ?? [];
        printf(\"  MSI: %d / 100\n\", \$stats['msi'] ?? 0);
        printf(\"  Covered MSI: %d / 100\n\", \$stats['coveredCodeMsi'] ?? 0);
        printf(\"  Killed: %d / %d\n\", \$stats['killedCount'] ?? 0, \$stats['totalMutantsCount'] ?? 0);
        printf(\"  Escaped: %d\n\", \$stats['escapedCount'] ?? 0);
        printf(\"  Errors: %d\n\", \$stats['errorCount'] ?? 0);
    " || true
fi

if [ $EXIT_CODE -eq 0 ]; then
    echo ""
    echo "✅ Tier 2 mutation testing PASSED."
    exit 0
else
    echo ""
    echo "❌ Tier 2 mutation testing FAILED."
    echo "   Review escaped mutants in: $ROOT_DIR/reports/infection-extended.log"
    exit 1
fi
