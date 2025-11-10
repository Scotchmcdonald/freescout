#!/bin/bash

# ==============================================================================
# Comprehensive Test Runner Script
#
# Runs a suite of tests in parallel, captures their exit codes, and
# generates a single, consolidated summary report with full failure logs.
#
# New options:
#   ./run-tests.sh fast   -> Runs all tests except 'phpstan-bodyscan'
# ==============================================================================

# --- Configuration ---

# We use set -u to exit on unbound variables, but not -e (exit on error)
# because we want to manually capture all failures from parallel jobs.
set -u
set -o pipefail

# Define all tests your project can run
AVAILABLE_TESTS=(
    "artisan-test"
    "dusk"
    "phpstan-analyse"
    "phpstan-bodyscan"
    "lint-style"
    "security-audit"
    "frontend-test"
    "frontend-lint"
)

# Define the exact shell commands for each test
declare -A TEST_COMMANDS=(
    ["artisan-test"]="php artisan test --parallel --coverage-html reports/coverage-report"
    ["dusk"]="php artisan dusk"
    ["phpstan-analyse"]="vendor/bin/phpstan analyse --memory-limit=2G"
    ["phpstan-bodyscan"]="vendor/bin/phpstan-bodyscan" # 'analyse' arg is not needed
    ["lint-style"]="vendor/bin/pint --test"
    ["security-audit"]="composer audit"
    ["frontend-test"]="npm run test -- --watch=false" # '--watch=false' is common for Jest/Vitest
    ["frontend-lint"]="npm run lint"
)

# Define friendly, human-readable names for each test
declare -A TEST_NAMES=(
    ["artisan-test"]="Artisan Test (w/ Coverage)"
    ["dusk"]="Dusk (Browser Tests)"
    ["phpstan-analyse"]="PHPStan Analyse (Fast)"
    ["phpstan-bodyscan"]="PHPStan Bodyscan (Full Report)"
    ["lint-style"]="Pint (Code Style Linting)"
    ["security-audit"]="Composer (Security Audit)"
    ["frontend-test"]="NPM/Yarn (Frontend Tests)"
    ["frontend-lint"]="NPM/Yarn (Frontend Linting)"
)

# --- Script Variables ---
LOG_DIR="reports"
COVERAGE_DIR="$LOG_DIR/coverage-report"
REPORT_FILE="$LOG_DIR/summary-report.txt" # The new consolidated report
SELECTED_TESTS=()

# --- Functions ---

# Print usage information
usage() {
    echo "Usage: $0 [fast | test1 test2 ...]"
    echo ""
    echo "Run tests in parallel and generate a consolidated report."
    echo ""
    echo "Special arguments:"
    echo "  fast                 -> Run all tests except the slow 'phpstan-bodyscan'"
    echo ""
    echo "If no arguments are provided, an interactive menu will be shown."
    echo ""
    echo "Available tests:"
    for test_key in "${AVAILABLE_TESTS[@]}"; do
        printf "  %-20s %s\n" "$test_key" "${TEST_NAMES[$test_key]}"
    done
    echo ""
    echo "Example:"
    echo "  $0 artisan-test lint-style"
}

# Interactive multi-select menu
interactive_menu() {
    echo "Select tests to run (use space to toggle, enter to confirm):"

    # Fallback to simple text menu if 'dialog' isn't installed
    if ! command -v dialog &> /dev/null; then
        echo "---"
        for i in "${!AVAILABLE_TESTS[@]}"; do
            echo "$((i+1))) ${TEST_NAMES[${AVAILABLE_TESTS[$i]}]}"
        done
        echo "---"
        read -p "Enter numbers of tests to run (e.g., '1 3'): " selection
        for i in $selection; do
            if [[ "$i" -gt 0 && "$i" -le "${#AVAILABLE_TESTS[@]}" ]]; then
                SELECTED_TESTS+=("${AVAILABLE_TESTS[$((i-1))]}")
            fi
        done
        return
    fi

    # Use 'dialog' for a better TUI
    local options=()
    for i in "${!AVAILABLE_TESTS[@]}"; do
        options+=("$i" "${TEST_NAMES[${AVAILABLE_TESTS[$i]}]}" "OFF")
    done

    cmd=(dialog --separate-output --checklist "Select tests to run:" 20 70 12)
    choices=$("${cmd[@]}" "${options[@]}" 2>&1 >/dev/tty)
    
    for choice in $choices; do
        SELECTED_TESTS+=("${AVAILABLE_TESTS[$choice]}")
    done
}

# --- Main Execution ---

# 1. Parse Arguments or Show Menu
if [ "$#" -gt 0 ]; then
    # --- NEW: Handle 'fast' argument ---
    if [ "$1" == "fast" ]; then
        echo "🚀 Running all fast tests..."
        for test in "${AVAILABLE_TESTS[@]}"; do
            if [ "$test" != "phpstan-bodyscan" ]; then
                SELECTED_TESTS+=("$test")
            fi
        done
    else
        # --- Existing argument parsing ---
        for arg in "$@"; do
            # Check if the arg is a valid test key
            if [[ " ${AVAILABLE_TESTS[*]} " =~ " ${arg} " ]]; then
                SELECTED_TESTS+=("$arg")
            else
                echo "Error: Invalid test '$arg'"
                usage
                exit 1
            fi
        done
    fi
else
    interactive_menu
fi

if [ ${#SELECTED_TESTS[@]} -eq 0 ]; then
    echo "No tests selected. Exiting."
    exit 0
fi

# 2. Prepare for Test Run
echo "Preparing to run the following tests:"
for test in "${SELECTED_TESTS[@]}"; do
    echo "- ${TEST_NAMES[$test]}"
done
echo ""

# Clean up old reports
echo "Cleaning up old reports..."
rm -rf "$LOG_DIR"
mkdir -p "$LOG_DIR"
echo "Log directory '$LOG_DIR' created."
echo ""

# 3. Run Tests in Parallel
pids=()
echo "🚀 Starting all tests in parallel..."
echo "---"

for test in "${SELECTED_TESTS[@]}"; do
    echo "   Starting: ${TEST_NAMES[$test]}"
    (
        bash -c "${TEST_COMMANDS[$test]}" > "$LOG_DIR/$test.log" 2>&1
        exit_code=$?
        echo $exit_code > "$LOG_DIR/$test.exit"
        
        if [ $exit_code -ne 0 ]; then
            echo "   Finished: ${TEST_NAMES[$test]} (Failed with code $exit_code)"
        else
            echo "   Finished: ${TEST_NAMES[$test]} (Passed)"
        fi
    ) &
    pids+=($!)
done

# 4. Wait for All Jobs
echo "---"
echo "Waiting for all tests to complete..."
wait "${pids[@]}" || true
echo "All tests have finished."
echo ""

# 5. Generate Summary Report
# 'tee' writes to both the console (stdout) and the report file
echo "========================================" | tee "$REPORT_FILE"
echo "           Test Run Summary"             | tee -a "$REPORT_FILE"
echo "========================================" | tee -a "$REPORT_FILE"
echo "" | tee -a "$REPORT_FILE"

final_exit_code=0
declare -A test_statuses

for test in "${SELECTED_TESTS[@]}"; do
    if [ -f "$LOG_DIR/$test.exit" ]; then
        code=$(cat "$LOG_DIR/$test.exit")
        if [ "$code" -eq 0 ]; then
            echo "✅ ${TEST_NAMES[$test]} (Passed)" | tee -a "$REPORT_FILE"
            test_statuses[$test]="Passed"
        else
            echo "❌ ${TEST_NAMES[$test]} (Failed with code $code)" | tee -a "$REPORT_FILE"
            test_statuses[$test]="Failed ($code)"
            final_exit_code=1 # Mark the whole script as failed
        fi
    else
        echo "❓ ${TEST_NAMES[$test]} (Status unknown - .exit file missing)" | tee -a "$REPORT_FILE"
        test_statuses[$test]="Unknown"
        final_exit_code=1 # This is a script error
    fi
done
echo "" | tee -a "$REPORT_FILE"

# 6. Collate Failure Details into Report
if [ $final_exit_code -ne 0 ]; then
    echo "---" | tee -a "$REPORT_FILE"
    echo "One or more tests failed. Collating full logs into report..."
    echo "---"
    
    for test in "${SELECTED_TESTS[@]}"; do
        if [[ "${test_statuses[$test]}" == "Failed"* ]]; then
            # --- THIS IS THE KEY CHANGE ---
            # Append the full log for the failed test into the summary report
            echo "" >> "$REPORT_FILE"
            echo "========================================" >> "$REPORT_FILE"
            echo "Logs for ❌ ${TEST_NAMES[$test]}"      >> "$REPORT_FILE"
            echo "========================================" >> "$REPORT_FILE"
            echo "" >> "$REPORT_FILE"
            
            cat "$LOG_DIR/$test.log" >> "$REPORT_FILE"
        fi
    done
    echo "All failure logs have been added to $REPORT_FILE"
else
    echo "🎉 All tests passed successfully! 🎉" | tee -a "$REPORT_FILE"
fi

# 7. Final Info and Exit
echo "" | tee -a "$REPORT_FILE"
echo "Coverage report (if generated): file://$(pwd)/$COVERAGE_DIR/index.html" | tee -a "$REPORT_FILE"
echo "========================================" | tee -a "$REPORT_FILE"

echo ""
echo "Consolidated test report is now available at: $REPORT_FILE"
echo "Script complete."
exit $final_exit_code