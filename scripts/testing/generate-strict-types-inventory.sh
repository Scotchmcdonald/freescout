#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
OUT_FILE="${1:-${ROOT_DIR}/docs/development/WIP/reliability-type-safety-uplift-2026-03-25/strict-types-inventory.csv}"

mkdir -p "$(dirname "${OUT_FILE}")"

echo "path,category,strict_types" > "${OUT_FILE}"

find "${ROOT_DIR}/app" "${ROOT_DIR}/Modules" "${ROOT_DIR}/tests" -type f -name '*.php' ! -path '*/resources/views/*' ! -name '*.blade.php' | sort | while IFS= read -r file; do
  rel_path="${file#${ROOT_DIR}/}"

  category="module"
  case "${rel_path}" in
    app/*) category="app" ;;
    tests/*) category="tests" ;;
    Modules/*/Tests/*) category="tests" ;;
    Modules/*) category="module" ;;
  esac

  if grep -q "declare(strict_types=1);" "${file}"; then
    strict="yes"
  else
    strict="no"
  fi

  echo "${rel_path},${category},${strict}" >> "${OUT_FILE}"
done

echo "Wrote ${OUT_FILE}"
