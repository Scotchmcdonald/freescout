#!/usr/bin/env bash

set -euo pipefail

##
# test-coverage.sh
# Wrapper script for running the test suite with coverage collection.
#
# Purpose:
#   - Ensures XDEBUG_MODE=coverage is set at the orchestrator level
#   - Enables reproducible full-suite coverage reports
#   - Handles parallel worker memory configuration via phpunit.xml
#
# Usage:
#   ./scripts/test-coverage.sh                    # full suite with coverage
#   ./scripts/test-coverage.sh --min=75           # with minimum threshold
#   ./scripts/test-coverage.sh --processes=5      # override worker count
#   ./scripts/test-coverage.sh tests/Unit         # coverage for specific suite
#
# Output:
#   - reports/coverage.xml            (Clover XML format)
#   - reports/coverage-html/          (HTML report)
#   - stdout                           (text summary)
#

export XDEBUG_MODE=coverage

# Default options — override via command-line args
COVERAGE_HTML="reports/coverage-html"
COVERAGE_XML="reports/coverage.xml"
PARALLEL_PROCESSES="10"
MIN_COVERAGE=""
ADDITIONAL_ARGS=()

# Parse arguments looking for specific flags, pass remainder to pest
for arg in "$@"; do
    case "$arg" in
        --min=*)
            MIN_COVERAGE="${arg#--min=}"
            ;;
        --processes=*)
            PARALLEL_PROCESSES="${arg#--processes=}"
            ;;
        --help)
            grep '^#' "$0" | head -n 30
            exit 0
            ;;
        *)
            ADDITIONAL_ARGS+=("$arg")
            ;;
    esac
done

# Build the pest command
PHP_ARGS=(
    "php"
    "artisan"
    "test"
    "--coverage"
    "--coverage-html=${COVERAGE_HTML}"
    "--coverage-clover=${COVERAGE_XML}"
    "--parallel"
    "--processes=${PARALLEL_PROCESSES}"
)

# Add minimum coverage threshold if specified
if [[ -n "${MIN_COVERAGE}" ]]; then
    PHP_ARGS+=("--min=${MIN_COVERAGE}")
fi

# Append any additional positional arguments (e.g., specific test paths)
PHP_ARGS+=("${ADDITIONAL_ARGS[@]}")

# Execute
"${PHP_ARGS[@]}"
