#!/usr/bin/env bash
# ==============================================================================
# Fast Parallel Test Gate
#
# Runs the full test suite in parallel WITHOUT coverage to verify all tests pass.
# This is a fast (~2 min) fail-fast gate that runs BEFORE the slow sequential
# coverage collection step (check-line-coverage.sh, ~8-10 min).
#
# Why not combine parallel + coverage?
#   ParaTest's CoverageMerger OOMs: 10 workers × ~1MB coverage XML each exceeds
#   the 2GB heap during merge (documented in phpunit.xml Phase 5 Audit).
#
# Artifact: reports/all-tests-passed.json
#   Used by check-line-coverage.sh to confirm pre-verification.
#
# Usage:
#   bash scripts/ci/check-all-tests.sh
# ==============================================================================

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
REPORTS_DIR="$ROOT_DIR/reports"
TEST_PROCESSES="${TEST_PROCESSES:-10}"

mkdir -p "$REPORTS_DIR"

echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║ Fast Parallel Test Gate (${TEST_PROCESSES} workers, no coverage)           ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

START=$(date +%s)

php "$ROOT_DIR/artisan" test \
    --parallel \
    --processes="$TEST_PROCESSES" \
    --no-progress

END=$(date +%s)
DURATION=$((END - START))

echo ""
echo "✅ All tests passed in ${DURATION}s (${TEST_PROCESSES} parallel workers)"

# Write a machine-readable artifact for downstream steps
cat > "$REPORTS_DIR/all-tests-passed.json" << JSON
{"passed": true, "duration_seconds": $DURATION, "processes": $TEST_PROCESSES, "ts": "$(date -u +%Y-%m-%dT%H:%M:%SZ)"}
JSON
