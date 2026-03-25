#!/usr/bin/env bash
# ==============================================================================
# MUTATION TESTING: TIER 2 (App Services + Actions)
# Runs post-PR to gate MSI degradation in critical business logic.
# ==============================================================================

set -e

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
CONFIG_FILE="$ROOT_DIR/infection-extended.json5"
MIN_MSI=95
MIN_COVERED_MSI=95
THREADS=6
TIMEOUT_MINUTES=45

echo "🧬 Mutation Testing: Tier 2 (app/Services + app/Actions)"
echo "========================================================"
echo "Configuration: $CONFIG_FILE"
echo "Thresholds: MSI ≥ $MIN_MSI, Covered MSI ≥ $MIN_COVERED_MSI (baseline: 100/100)"
echo "Threads: $THREADS"
echo "Timeout: ${TIMEOUT_MINUTES} minutes"
echo ""

# Verify coverage XML exists (mutation testing requires prior coverage collection)
if [ ! -d "$ROOT_DIR/storage/infection/coverage" ]; then
    echo "⚠️  Coverage XML not found. Collecting coverage first..."
    XDEBUG_MODE=coverage php -d memory_limit=3G "$ROOT_DIR/vendor/bin/pest" \
        --coverage-xml="$ROOT_DIR/storage/infection/coverage" \
        > /dev/null 2>&1 || true
fi

if [ ! -d "$ROOT_DIR/storage/infection/coverage" ]; then
    echo "❌ Failed to collect coverage. Aborting mutation testing."
    exit 1
fi

# Run Infection using pre-collected coverage XML (avoids 25-min redundant test re-run)
# --coverage: path to Xdebug coverage XML dir (collected in the step above)
# --skip-initial-tests: don't re-run the full suite; use the coverage we already have
# XDEBUG_MODE=off: prevent xdebug from attaching during mutant execution (slows each run)
timeout $((TIMEOUT_MINUTES * 60)) \
    env XDEBUG_MODE=off php -d memory_limit=4G "$ROOT_DIR/vendor/bin/infection" \
    --configuration="$CONFIG_FILE" \
    --coverage="$ROOT_DIR/storage/infection/coverage" \
    --skip-initial-tests \
    --threads="$THREADS" \
    --min-msi="$MIN_MSI" \
    --min-covered-msi="$MIN_COVERED_MSI" \
    --logger-text="$ROOT_DIR/reports/infection-extended.log" \
    --logger-summary-json="$ROOT_DIR/reports/infection-extended-summary.json" \
    2>&1 | tee "$ROOT_DIR/reports/infection-run.log"

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
