#!/bin/bash

# Comprehensive Test Runner Script
#
# This script provides a command-line interface to run various tests,
# either all at once or by selecting specific ones. It runs the selected
# tests in parallel and generates a consolidated summary report.

# --- Configuration ---
AVAILABLE_TESTS=("phpstan-bodyscan" "phpstan-analyse" "artisan-test")
declare -A TEST_COMMANDS=(
    ["phpstan-bodyscan"]="vendor/bin/phpstan-bodyscan analyse"
    ["phpstan-analyse"]="vendor/bin/phpstan analyse --memory-limit=2G"
    ["artisan-test"]="php artisan test --coverage-html coverage-report"
)
declare -A TEST_NAMES=(
    ["phpstan-bodyscan"]="PHPStan Bodyscan"
    ["phpstan-analyse"]="PHPStan Analyse"
    ["artisan-test"]="PHP Artisan Test (with Coverage)"
)

LOG_DIR="reports"
SELECTED_TESTS=()

# --- Functions ---

# Print usage information
usage() {
    echo "Usage: $0 [test1 test2 ...]"
    echo ""
    echo "Run tests and generate a consolidated report."
    echo ""
    echo "If no arguments are provided, an interactive menu will be shown."
    echo ""
    echo "Available tests:"
    for test_key in "${AVAILABLE_TESTS[@]}"; do
        printf "  %-20s %s\n" "$test_key" "${TEST_NAMES[$test_key]}"
    done
    echo ""
    echo "Example:"
    echo "  $0 artisan-test phpstan-analyse"
}

# Interactive multi-select menu
interactive_menu() {
    echo "Select tests to run (use space to toggle, enter to confirm):"
    
    # This is a simple menu implementation. For a better experience,
    # tools like 'gum' or 'whiptail' could be used if available.
    
    local options=()
    for i in "${!AVAILABLE_TESTS[@]}"; do
        options+=("$i" "${TEST_NAMES[${AVAILABLE_TESTS[$i]}]}" "OFF")
    done

    # Simple text-based selection if dialog is not available
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

    cmd=(dialog --separate-output --checklist "Select tests to run:" 15 60 6)
    choices=$("${cmd[@]}" "${options[@]}" 2>&1 >/dev/tty)
    
    for choice in $choices; do
        SELECTED_TESTS+=("${AVAILABLE_TESTS[$choice]}")
    done
}

# --- Main Execution ---

# Handle command-line arguments or show interactive menu
if [ "$#" -gt 0 ]; then
    for arg in "$@"; do
        if [[ " ${AVAILABLE_TESTS[*]} " =~ " ${arg} " ]]; then
            SELECTED_TESTS+=("$arg")
        else
            echo "Error: Invalid test '$arg'"
            usage
            exit 1
        fi
    done
else
    interactive_menu
fi

if [ ${#SELECTED_TESTS[@]} -eq 0 ]; then
    echo "No tests selected. Exiting."
    exit 0
fi

# Prepare for test run
echo "Preparing to run the following tests:"
for test in "${SELECTED_TESTS[@]}"; do
    echo "- ${TEST_NAMES[$test]}"
done
echo ""

rm -rf $LOG_DIR
mkdir -p $LOG_DIR

# Run tests in parallel
pids=()
for test in "${SELECTED_TESTS[@]}"; do
    echo "Starting: ${TEST_NAMES[$test]}"
    (
        # Execute command and handle exit codes gracefully
        eval "${TEST_COMMANDS[$test]}" > "$LOG_DIR/$test.log" 2>&1
        exit_code=$?
        if [ $exit_code -ne 0 ]; then
            echo "Warning: '${TEST_NAMES[$test]}' finished with exit code $exit_code."
        fi
    ) &
    pids+=($!)
done

# Wait for all background jobs to finish
echo "Waiting for tests to complete..."
wait "${pids[@]}"
echo "All tests have finished."
echo ""

# Generate the final report
echo "Generating summary report..."
scripts/generate_report.sh "${SELECTED_TESTS[@]}"

echo "Done."
