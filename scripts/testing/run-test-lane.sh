#!/usr/bin/env bash

set -euo pipefail

if [[ $# -lt 1 ]]; then
    echo "Usage: $0 <guards|unit|feature|integration|architecture> [extra artisan test args...]" >&2
    exit 2
fi

lane="$1"
shift || true

mode="single"

case "$lane" in
    guards)
        mode="guards"
        history="reports/lane-runtime-history-guards.jsonl"
        ;;
    unit)
        command=(php artisan test tests/Unit --parallel --processes=10)
        history="reports/lane-runtime-history-unit.jsonl"
        ;;
    feature)
        command=(php artisan test tests/Feature --parallel --processes=10)
        history="reports/lane-runtime-history-feature.jsonl"
        ;;
    integration)
        command=(php artisan test tests/Integration --parallel --processes=10)
        history="reports/lane-runtime-history-integration.jsonl"
        ;;
    architecture)
        command=(bash scripts/ci/check-architecture-compliance.sh)
        history="reports/lane-runtime-history-architecture.jsonl"
        ;;
    *)
        echo "Unknown lane: $lane" >&2
        exit 2
        ;;
esac

if [[ $# -gt 0 ]]; then
    command+=("$@")
fi

mkdir -p reports

start_ts=$(date +%s)
set +e
if [[ "$mode" == "guards" ]]; then
    php artisan test tests/Unit/ModuleUnitIsolationGuardTest.php --parallel --processes=10 -q
    guard_exit_1=$?
    php artisan test tests/Unit/RefreshDatabaseUsageGuardTest.php --parallel --processes=10 -q
    guard_exit_2=$?
    php artisan test tests/Unit/UnitFrameworkBootingGuardTest.php --parallel --processes=10 -q
    guard_exit_3=$?
    php artisan test tests/Unit/FeatureWriteAssertionDepthGuardTest.php --parallel --processes=10 -q
    guard_exit_4=$?

    test_exit=0
    for exit_code in "$guard_exit_1" "$guard_exit_2" "$guard_exit_3" "$guard_exit_4"; do
        if [[ "$exit_code" -ne 0 ]]; then
            test_exit="$exit_code"
            break
        fi
    done
else
    "${command[@]}"
    test_exit=$?
fi
set -e
end_ts=$(date +%s)
duration=$((end_ts-start_ts))

php scripts/ci/check-test-lane-runtime-budgets.php \
    --lane="$lane" \
    --duration="$duration" \
    --history="$history"
budget_exit=$?

if [[ $test_exit -ne 0 || $budget_exit -ne 0 ]]; then
    exit 1
fi
