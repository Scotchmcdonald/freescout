#!/usr/bin/env bash
# ==============================================================================
# Type Coverage Check
#
# Delegates to check-type-coverage.php which uses PHPStan's cached data and
# direct token inspection to measure what percentage of public method signatures
# in app/ and Modules/ carry explicit return-type and parameter-type declarations.
#
# Outputs:
#   reports/type-coverage-latest.txt  — full per-namespace breakdown
#   reports/type-coverage-summary.json — machine-readable percentage
#
# Thresholds (configurable via env):
#   TYPE_COVERAGE_MIN  (default: 80, percent 0-100)
#
# Usage:
#   bash scripts/ci/check-type-coverage.sh
# ==============================================================================

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
CI_DIR="$(dirname "$0")"

echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║ Type Coverage Check (PHPStan level 9 + declaration audit)      ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Step 1: confirm PHPStan level 9 is passing (type correctness gate)
if [ -f "$ROOT_DIR/vendor/bin/phpstan" ]; then
    echo "→ Step 1: PHPStan type-correctness (level 9)..."
    "$ROOT_DIR/vendor/bin/phpstan" analyse --memory-limit=2G --configuration="$ROOT_DIR/phpstan.neon" || {
        echo "❌ FAILED: PHPStan found type errors — fix these before measuring coverage."
        exit 1
    }
    echo "   ✅ PHPStan passed."
else
    echo "   ⚠️  PHPStan not found — skipping correctness step."
fi

echo ""
echo "→ Step 2: Type declaration density audit..."
php "$CI_DIR/check-type-coverage.php"
