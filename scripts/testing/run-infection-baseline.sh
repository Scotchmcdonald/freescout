#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COVERAGE_DIR="${ROOT_DIR}/storage/infection/coverage"
JUNIT_FILE="${COVERAGE_DIR}/junit.xml"
TEXT_LOG="${ROOT_DIR}/reports/infection.log"
SUMMARY_JSON="${ROOT_DIR}/reports/infection-summary.json"

THREADS="${INFECTION_THREADS:-6}"
MEMORY_LIMIT="${INFECTION_MEMORY_LIMIT:-2G}"
PREPARE_MEMORY_LIMIT="${INFECTION_PREPARE_MEMORY_LIMIT:-3G}"
PREPARE_TIMEOUT_MINUTES="${INFECTION_PREPARE_TIMEOUT_MINUTES:-30}"
TIMEOUT_MINUTES="${INFECTION_TIMEOUT_MINUTES:-45}"

mkdir -p "${COVERAGE_DIR}" "${ROOT_DIR}/reports"

echo "[infection-baseline] 1/3 Generate coverage + JUnit via Pest"
timeout $((PREPARE_TIMEOUT_MINUTES * 60)) \
env XDEBUG_MODE=coverage php -d memory_limit="${PREPARE_MEMORY_LIMIT}" "${ROOT_DIR}/vendor/bin/pest" \
  --coverage-xml="${COVERAGE_DIR}" \
  --log-junit="${JUNIT_FILE}" \
  > "${ROOT_DIR}/reports/infection-baseline-coverage.log" 2>&1

echo "[infection-baseline] 2/3 Normalize JUnit metadata for Infection"
php "${ROOT_DIR}/scripts/testing/normalize-pest-junit-for-infection.php" "${JUNIT_FILE}"

echo "[infection-baseline] 3/3 Run Infection"
timeout $((TIMEOUT_MINUTES * 60)) \
env XDEBUG_MODE=off php -d memory_limit="${MEMORY_LIMIT}" "${ROOT_DIR}/vendor/bin/infection" \
  --threads="${THREADS}" \
  --skip-initial-tests \
  --coverage="${COVERAGE_DIR}" \
  --no-progress \
  --logger-text="${TEXT_LOG}" \
  --logger-summary-json="${SUMMARY_JSON}" \
  2>&1 | tee "${ROOT_DIR}/reports/infection-baseline-run.log"

echo "[infection-baseline] Complete"
echo "  - ${TEXT_LOG}"
echo "  - ${SUMMARY_JSON}"
