#!/usr/bin/env bash
# ==============================================================================
# Architecture Suite Runner
#
# Runs the two architecture test targets in sequence and writes a JSON artifact
# that check-testing-quality-gate.php reads as its 5th check.
#
# Artifact: reports/arch-suite-latest.json
#   { "passed": bool, "arch_dir_exit": int, "arch_file_exit": int, "ts": string }
#
# Usage:
#   bash scripts/ci/check-arch-suite.sh
# ==============================================================================

set -uo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
REPORTS_DIR="$ROOT_DIR/reports"

mkdir -p "$REPORTS_DIR"

echo ""
echo "📋 Architecture Suite: running arch rules + arch file suites"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

ARCH_DIR_EXIT=0
ARCH_FILE_EXIT=0

# Use `|| ARCH_DIR_EXIT=$?` so that the exit code from the failing command is
# captured directly.  The previous `if ! cmd; then EXIT=$?` pattern set $? to
# the negated exit of the condition expression (always 0), masking failures.
echo "→ Running tests/Architecture/ ..."
php "$ROOT_DIR/artisan" test tests/Architecture || ARCH_DIR_EXIT=$?

echo ""
echo "→ Running tests/ArchTest.php ..."
php "$ROOT_DIR/artisan" test tests/ArchTest.php || ARCH_FILE_EXIT=$?

OVERALL=0
if [ "$ARCH_DIR_EXIT" -ne 0 ] || [ "$ARCH_FILE_EXIT" -ne 0 ]; then
    OVERALL=1
fi

PASSED_JSON=$([ "$OVERALL" -eq 0 ] && echo "true" || echo "false")

cat > "$REPORTS_DIR/arch-suite-latest.json" <<JSON
{
  "passed": $PASSED_JSON,
  "arch_dir_exit": $ARCH_DIR_EXIT,
  "arch_file_exit": $ARCH_FILE_EXIT,
  "ts": "$(date -u +%Y-%m-%dT%H:%M:%SZ)"
}
JSON

echo ""
echo "Architecture suite complete: passed=$PASSED_JSON"
echo "Artifact saved to: reports/arch-suite-latest.json"

exit $OVERALL
