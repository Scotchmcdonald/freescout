#!/usr/bin/env bash
# ==============================================================================
# Line Coverage Collection
#
# Runs the full test suite with Xdebug coverage in a single sequential process
# to avoid the OOM issue that occurs when merging per-worker coverage XML from
# 10 parallel processes (documented in phpunit.xml Phase 5 Audit note).
#
# Produces:
#   reports/coverage-final.txt    — human-readable summary consumed by quality gate
#   storage/infection/coverage/   — Clover XML used by Infection for mutation testing
#
# Time budget: ~8-10 min (documented in phpunit.xml)
# Memory:      3G (single process, no parallel merge overhead)
#
# Usage (CI — after parallel test pass):
#   bash scripts/ci/check-line-coverage.sh
#
# Usage (local — only when full coverage needed):
#   bash scripts/ci/check-line-coverage.sh
# ==============================================================================

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
REPORTS_DIR="$ROOT_DIR/reports"
COVERAGE_XML_DIR="$ROOT_DIR/storage/infection/coverage"

mkdir -p "$REPORTS_DIR" "$COVERAGE_XML_DIR"

COVERAGE_TXT="$REPORTS_DIR/coverage-final.txt"
MIN_COVERAGE="${TEST_MIN_COVERAGE:-70}"

echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║ Line Coverage Collection (sequential, 3G, Xdebug)             ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
echo "  Min required : ${MIN_COVERAGE}%"
echo "  Output text  : $COVERAGE_TXT"
echo "  Output XML   : $COVERAGE_XML_DIR"
echo ""

START=$(date +%s)

XDEBUG_MODE=coverage php \
    -d memory_limit=3G \
    "$ROOT_DIR/vendor/bin/pest" \
    --coverage-text="$COVERAGE_TXT" \
    --coverage-xml="$COVERAGE_XML_DIR" \
    --no-progress

END=$(date +%s)
DURATION=$((END - START))

echo ""
echo "✅ Coverage collected in ${DURATION}s"
echo ""

if [ ! -f "$COVERAGE_TXT" ]; then
    echo "❌ ERROR: coverage-final.txt was not written."
    exit 1
fi

# Extract the overall line coverage percentage from the text report
# Format: "  Lines:     72.45% (1234 / 1703)"
LINE_COVERAGE=$(grep -E "^\s+Lines:" "$COVERAGE_TXT" | head -1 | grep -oE '[0-9]+\.[0-9]+' | head -1)

if [ -z "$LINE_COVERAGE" ]; then
    echo "⚠️  WARNING: Could not parse line coverage from $COVERAGE_TXT"
    echo "   Quality gate will use the artifact directly."
    exit 0
fi

echo "📊 Line coverage: ${LINE_COVERAGE}%  (minimum: ${MIN_COVERAGE}%)"

# Write a machine-readable summary for the quality gate
cat > "$REPORTS_DIR/coverage-summary.json" << JSON
{"line_coverage": $LINE_COVERAGE, "generated_at": "$(date -u +%Y-%m-%dT%H:%M:%SZ)", "source": "coverage-final.txt"}
JSON

# Enforce the minimum threshold
if (( $(echo "$LINE_COVERAGE < $MIN_COVERAGE" | bc -l) )); then
    echo ""
    echo "❌ FAILED: Line coverage ${LINE_COVERAGE}% is below the ${MIN_COVERAGE}% minimum."
    exit 1
fi

echo "✅ PASSED: Line coverage ${LINE_COVERAGE}% meets the ${MIN_COVERAGE}% minimum."
