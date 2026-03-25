#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
WIP_DIR="${ROOT_DIR}/docs/development/WIP/reliability-type-safety-uplift-2026-03-25"
RUN_ID="${1:-$(date -u +%Y%m%dT%H%M%SZ)}"
BASELINE_DIR="${WIP_DIR}/baseline-artifacts/${RUN_ID}"

mkdir -p "${BASELINE_DIR}" "${ROOT_DIR}/reports" "${ROOT_DIR}/storage/infection/coverage"

echo "[phase1] run_id=${RUN_ID}"
echo "[phase1] baseline_dir=${BASELINE_DIR}"

echo "[phase1] 1/6 Parallel health run"
"${ROOT_DIR}/vendor/bin/pest" --parallel --processes=10 > "${ROOT_DIR}/reports/phase1-parallel-${RUN_ID}.log"

echo "[phase1] 2/6 Coverage XML (sequential)"
XDEBUG_MODE=coverage php -d memory_limit=3G "${ROOT_DIR}/vendor/bin/pest" \
  --coverage-xml="${ROOT_DIR}/storage/infection/coverage" \
  --log-junit="${ROOT_DIR}/storage/infection/junit.xml" \
  > "${ROOT_DIR}/reports/phase1-coverage-${RUN_ID}.log"

echo "[phase1] 3/6 Tier 1 mutation"
env XDEBUG_MODE=off php -d memory_limit=4G "${ROOT_DIR}/vendor/bin/infection" \
  --configuration="${ROOT_DIR}/infection.json5" \
  --coverage="${ROOT_DIR}/storage/infection/coverage" \
  --skip-initial-tests \
  --logger-text="${ROOT_DIR}/reports/infection.log" \
  --logger-summary-json="${ROOT_DIR}/reports/infection-summary.json" \
  > "${ROOT_DIR}/reports/phase1-tier1-mutation-${RUN_ID}.log" 2>&1

echo "[phase1] 4/6 Tier 2 mutation"
bash "${ROOT_DIR}/scripts/ci/check-mutation-tier2.sh" > "${ROOT_DIR}/reports/phase1-tier2-mutation-${RUN_ID}.log" 2>&1

echo "[phase1] 5/6 Freeze canonical artifacts"
cp "${ROOT_DIR}/reports/infection-summary.json" "${BASELINE_DIR}/infection-summary.json"
cp "${ROOT_DIR}/reports/infection-extended-summary.json" "${BASELINE_DIR}/infection-extended-summary.json"
cp "${ROOT_DIR}/storage/infection/coverage/index.xml" "${BASELINE_DIR}/coverage-index.xml"

echo "[phase1] 6/6 Write metadata + checksums"
{
  echo "run_id=${RUN_ID}"
  echo "created_utc=$(date -u +%Y-%m-%dT%H:%M:%SZ)"
  echo "git_commit=$(git -C "${ROOT_DIR}" rev-parse HEAD)"
} > "${BASELINE_DIR}/metadata.env"

sha256sum \
  "${BASELINE_DIR}/infection-summary.json" \
  "${BASELINE_DIR}/infection-extended-summary.json" \
  "${BASELINE_DIR}/coverage-index.xml" \
  > "${BASELINE_DIR}/checksums.sha256"

echo "[phase1] baseline complete"
echo "[phase1] artifacts:"
echo "  - ${BASELINE_DIR}/infection-summary.json"
echo "  - ${BASELINE_DIR}/infection-extended-summary.json"
echo "  - ${BASELINE_DIR}/coverage-index.xml"
echo "  - ${BASELINE_DIR}/metadata.env"
echo "  - ${BASELINE_DIR}/checksums.sha256"
