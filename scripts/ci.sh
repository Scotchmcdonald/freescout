#!/bin/bash

# 1. Define the log file and target directory
LOG_FILE="/var/www/html/reports/ci_master.log"
CI_DIR="/var/www/html/scripts/ci"

# Shared test execution defaults for all child scripts
export TEST_PROCESSES="${TEST_PROCESSES:-10}"
export INFECTION_THREADS="${INFECTION_THREADS:-10}"

mkdir -p "$(dirname "$LOG_FILE")"
: > "$LOG_FILE"

# 2. The Master Wrapper: mirror all output to terminal and log file
exec > >(tee -a "$LOG_FILE") 2>&1

echo "========================================"
echo " Starting CI Pipeline Run"
echo " Date: $(date)"
echo " Target Directory: $CI_DIR"
echo " Parallel test workers: $TEST_PROCESSES"
echo " Infection workers: $INFECTION_THREADS"
echo "========================================"

# 3. Validate that the target directory actually exists
if [ ! -d "$CI_DIR" ]; then
    echo "FATAL ERROR: The directory '$CI_DIR' does not exist."
    exit 1
fi

# 4. Loop through every .sh file in the directory
PIPELINE_FAILED=0

for script in "$CI_DIR"/*.sh; do

    # Check if the file actually exists (prevents errors if the folder is empty)
    if [ -e "$script" ]; then
        echo ""
        echo "--- Executing: $(basename "$script") at $(date) ---"

        # Run the script using 'bash' to bypass missing execute (+x) permissions
        bash "$script"

        # Capture the exit code to log success or failure
        EXIT_CODE=$?
        if [ $EXIT_CODE -ne 0 ]; then
            echo "--> WARNING: $(basename "$script") failed with exit code $EXIT_CODE"
            PIPELINE_FAILED=1
        else
            echo "--> SUCCESS: $(basename "$script") completed."
        fi
    else
        echo "No bash scripts (*.sh) found in $CI_DIR."
    fi
done

echo ""
echo "--- Executing: check-boundary-namespace-report.php at $(date) ---"
if php "$CI_DIR/check-boundary-namespace-report.php"; then
    echo "--> SUCCESS: check-boundary-namespace-report.php completed."
else
    echo "--> WARNING: check-boundary-namespace-report.php had findings."
    # Non-blocking by default; upgrade to PIPELINE_FAILED=1 when --fail-on-empty is desired
fi

echo ""
echo "--- Executing: check-testing-quality-gate.php at $(date) ---"
if php "$CI_DIR/check-testing-quality-gate.php"; then
    echo "--> SUCCESS: check-testing-quality-gate.php completed."
else
    echo "--> WARNING: check-testing-quality-gate.php failed."
    PIPELINE_FAILED=1
fi

echo ""
echo "========================================"
echo " CI Pipeline Run Complete at $(date)"
echo "========================================"

if [ $PIPELINE_FAILED -ne 0 ]; then
    echo "One or more CI checks failed."
    exit 1
fi
