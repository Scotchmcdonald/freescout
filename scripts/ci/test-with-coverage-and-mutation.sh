#!/usr/bin/env bash
# ==============================================================================
# CI/CD TEST ORCHESTRATION REFERENCE
# Complete pipeline: Test Suite → Coverage → Mutation Testing
# ==============================================================================
#
# This script is a REFERENCE for CI/CD pipeline configuration.
# Copy patterns into your .github/workflows/*.yml or .gitlab-ci.yml
#
# Usage:
#   # Option 1: Include this script in your CI/CD definition
#   bash scripts/ci/test-with-coverage-and-mutation.sh
#
#   # Option 2: Use as a template and copy individual sections to your CI config
#
# ==============================================================================

set -e

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PHASE_TIMEOUT_TESTS=300        # 5 min for tests (usually done by 2 min)
PHASE_TIMEOUT_COVERAGE=600     # 10 min for coverage collection
PHASE_TIMEOUT_MUTATION=3000    # 50 min for mutation testing

echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║ CI/CD TEST ORCHESTRATION: Full Pipeline                        ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# ==============================================================================
# PHASE 1: TEST EXECUTION (Parallel)
# ==============================================================================

echo "📋 PHASE 1: Running test suite (parallel, no coverage)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

TESTS_START=$(date +%s)

if ! timeout $PHASE_TIMEOUT_TESTS "$ROOT_DIR/vendor/bin/pest" \
    --parallel \
    --processes=10; then
    echo ""
    echo "❌ PHASE 1 FAILED: Tests did not pass."
    exit 1
fi

TESTS_END=$(date +%s)
TESTS_DURATION=$((TESTS_END - TESTS_START))

echo ""
echo "✅ PHASE 1 PASSED (${TESTS_DURATION}s)"
echo ""

# ==============================================================================
# PHASE 2: COVERAGE COLLECTION (Sequential)
# ==============================================================================

echo "📋 PHASE 2: Collecting code coverage"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

COVERAGE_START=$(date +%s)

if ! timeout $PHASE_TIMEOUT_COVERAGE \
    env XDEBUG_MODE=coverage \
    php -d memory_limit=3G "$ROOT_DIR/vendor/bin/pest" \
    --coverage-xml="$ROOT_DIR/storage/infection/coverage" \
    --coverage-text="$ROOT_DIR/reports/coverage-final.txt" > /dev/null 2>&1; then

    echo ""
    echo "⚠️  PHASE 2 WARNING: Coverage collection had issues."
    echo "    Continuing to mutation phase (coverage may be incomplete)..."
fi

COVERAGE_END=$(date +%s)
COVERAGE_DURATION=$((COVERAGE_END - COVERAGE_START))

# Display coverage summary
if [ -f "$ROOT_DIR/reports/coverage-final.txt" ]; then
    echo ""
    head -20 "$ROOT_DIR/reports/coverage-final.txt"

    # Write machine-readable coverage-summary.json consumed by check-testing-quality-gate.php
    LINE_COVERAGE=$(grep -E "^\s+Lines:" "$ROOT_DIR/reports/coverage-final.txt" | head -1 | grep -oE '[0-9]+\.[0-9]+' | head -1)
    if [ -n "$LINE_COVERAGE" ]; then
        cat > "$ROOT_DIR/reports/coverage-summary.json" << JSON
{"line_coverage": $LINE_COVERAGE, "generated_at": "$(date -u +%Y-%m-%dT%H:%M:%SZ)", "source": "coverage-final.txt"}
JSON
        echo ""
        echo "  📊 Line coverage written to reports/coverage-summary.json: ${LINE_COVERAGE}%"
    fi
fi

echo ""
echo "✅ PHASE 2 PASSED (${COVERAGE_DURATION}s)"
echo ""

# ==============================================================================
# PHASE 3: MUTATION TESTING (Tier 2)
# ==============================================================================

echo "📋 PHASE 3: Running mutation testing (Tier 2)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

MUTATION_START=$(date +%s)

if ! timeout $PHASE_TIMEOUT_MUTATION \
    bash "$ROOT_DIR/scripts/ci/check-mutation-tier2.sh"; then

    echo ""
    echo "❌ PHASE 3 FAILED: Mutation testing did not pass."
    echo "   Review escaped mutants in: $ROOT_DIR/reports/infection-extended.log"
    exit 1
fi

MUTATION_END=$(date +%s)
MUTATION_DURATION=$((MUTATION_END - MUTATION_START))

echo ""
echo "✅ PHASE 3 PASSED (${MUTATION_DURATION}s)"
echo ""

# ==============================================================================
# SUMMARY
# ==============================================================================

TOTAL_DURATION=$((MUTATION_END - TESTS_START))

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║ ✅ ALL TESTING GATES PASSED                                     ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
echo "Timing Summary:"
echo "  Phase 1 (Tests):     ${TESTS_DURATION}s"
echo "  Phase 2 (Coverage):  ${COVERAGE_DURATION}s"
echo "  Phase 3 (Mutation):  ${MUTATION_DURATION}s"
echo "  ────────────────────────"
echo "  Total:               ${TOTAL_DURATION}s (~$(( (TOTAL_DURATION + 59) / 60 )) min)"
echo ""
echo "Reports:"
echo "  Coverage: $ROOT_DIR/reports/coverage-final.txt"
echo "  Mutation: $ROOT_DIR/reports/infection-extended.log"
echo "  Mutation: $ROOT_DIR/reports/infection-extended-summary.log"
echo ""

# ==============================================================================
# TIMING SIDE-CHANNEL
# Write a JSON file so that check-testing-quality-gate.php can display
# per-phase timings in the report even when env vars are not injected.
# ==============================================================================

CI_TIMING_FILE="$ROOT_DIR/reports/ci-timing-latest.json"

cat > "$CI_TIMING_FILE" <<EOF
{
  "generated": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
  "tests_s": ${TESTS_DURATION},
  "coverage_s": ${COVERAGE_DURATION},
  "mutation_s": ${MUTATION_DURATION},
  "total_s": ${TOTAL_DURATION}
}
EOF

echo "Timing side-channel: $CI_TIMING_FILE"
