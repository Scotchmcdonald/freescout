#!/bin/bash

# generate_report.sh

# This script generates a summary of the test runs based on the provided arguments.

set -e

REPORT_DIR="reports"
REPORT_FILE="$REPORT_DIR/TEST_RESULTS_SUMMARY.md"
COVERAGE_DIR="coverage-report"
COVERAGE_DASHBOARD="$COVERAGE_DIR/dashboard.html"
SELECTED_TESTS=("$@")

# Create the report directory if it doesn't exist
mkdir -p $REPORT_DIR

# Start the report
echo "# Test Results Summary" > $REPORT_FILE
echo "" >> $REPORT_FILE
echo "**Date:** $(date)" >> $REPORT_FILE
echo "" >> $REPORT_FILE

# Function to check if a test was selected
is_selected() {
    local test_name="$1"
    for selected in "${SELECTED_TESTS[@]}"; do
        if [[ "$selected" == "$test_name" ]]; then
            return 0
        fi
    done
    return 1
}

# --- PHPStan Bodyscan ---
if is_selected "phpstan-bodyscan"; then
    echo "## PHPStan Bodyscan Results" >> $REPORT_FILE
    echo "" >> $REPORT_FILE
    if [ -s "$REPORT_DIR/phpstan-bodyscan.log" ]; then
        echo "\`\`\`" >> $REPORT_FILE
        cat "$REPORT_DIR/phpstan-bodyscan.log" >> $REPORT_FILE
        echo "\`\`\`" >> $REPORT_FILE
    else
        echo "✅ PHPStan Bodyscan completed without any output." >> $REPORT_FILE
    fi
    echo "" >> $REPORT_FILE
fi

# --- PHPStan Analyse ---
if is_selected "phpstan-analyse"; then
    echo "## PHPStan Analyse Results" >> $REPORT_FILE
    echo "" >> $REPORT_FILE
    if [ -s "$REPORT_DIR/phpstan-analyse.log" ]; then
        # Check for the success message, as phpstan can output text even on success
        if grep -q "No errors" "$REPORT_DIR/phpstan-analyse.log"; then
            echo "✅ PHPStan Analyse completed with no errors." >> $REPORT_FILE
            echo "\`\`\`" >> $REPORT_FILE
            grep "No errors" "$REPORT_DIR/phpstan-analyse.log" >> $REPORT_FILE
            echo "\`\`\`" >> $REPORT_FILE
        else
            echo "⚠️ PHPStan Analyse reported errors:" >> $REPORT_FILE
            echo "\`\`\`" >> $REPORT_FILE
            cat "$REPORT_DIR/phpstan-analyse.log" >> $REPORT_FILE
            echo "\`\`\`" >> $REPORT_FILE
        fi
    else
        echo "✅ PHPStan Analyse completed without any output." >> $REPORT_FILE
    fi
    echo "" >> $REPORT_FILE
fi

# --- PHP Artisan Test ---
if is_selected "artisan-test"; then
    echo "## PHP Artisan Test Results" >> $REPORT_FILE
    echo "" >> $REPORT_FILE
    if [ -s "$REPORT_DIR/artisan-test.log" ]; then
        echo "\`\`\`" >> $REPORT_FILE
        # Filter for summary lines
        grep -E "Tests:  |Time:   " "$REPORT_DIR/artisan-test.log" >> $REPORT_FILE
        echo "" >> $REPORT_FILE
        # Show failing tests if any
        if grep -q "FAIL" "$REPORT_DIR/artisan-test.log"; then
            echo "---" >> $REPORT_FILE
            echo "Failing Tests:" >> $REPORT_FILE
            grep -A 1 "FAIL" "$REPORT_DIR/artisan-test.log" >> $REPORT_FILE
        fi
        echo "\`\`\`" >> $REPORT_FILE
    else
        echo "✅ PHP Artisan Test completed without any output." >> $REPORT_FILE
    fi
    echo "" >> $REPORT_FILE

    # --- Code Coverage Summary ---
    echo "## Code Coverage Summary" >> $REPORT_FILE
    echo "" >> $REPORT_FILE
    if [ -f "$COVERAGE_DASHBOARD" ]; then
        echo "Parsing coverage data from '$COVERAGE_DASHBOARD'..." >> $REPORT_FILE
        
        # Extract metrics using grep and sed
        LINES=$(grep -A 2 'Lines' $COVERAGE_DASHBOARD | tail -n 1 | sed -e 's/<[^>]*>//g' | xargs)
        FUNCTIONS=$(grep -A 2 'Functions' $COVERAGE_DASHBOARD | tail -n 1 | sed -e 's/<[^>]*>//g' | xargs)
        CLASSES=$(grep -A 2 'Classes' $COVERAGE_DASHBOARD | tail -n 1 | sed -e 's/<[^>]*>//g' | xargs)

        # Extract total percentages from progress bars
        TOTAL_LINES_PERCENTAGE=$(grep 'Lines' $COVERAGE_DASHBOARD -A 4 | grep 'width' | sed 's/.*width: \(.*\)%.*/\1/')
        TOTAL_FUNCTIONS_PERCENTAGE=$(grep 'Functions' $COVERAGE_DASHBOARD -A 4 | grep 'width' | sed 's/.*width: \(.*\)%.*/\1/')
        TOTAL_CLASSES_PERCENTAGE=$(grep 'Classes' $COVERAGE_DASHBOARD -A 4 | grep 'width' | sed 's/.*width: \(.*\)%.*/\1/')

        echo "| Metric    | Coverage      | Covered / Total |" >> $REPORT_FILE
        echo "|-----------|---------------|-----------------|" >> $REPORT_FILE
        echo "| Lines     | **$TOTAL_LINES_PERCENTAGE%**    | $LINES          |" >> $REPORT_FILE
        echo "| Functions | **$TOTAL_FUNCTIONS_PERCENTAGE%**| $FUNCTIONS      |" >> $REPORT_FILE
        echo "| Classes   | **$TOTAL_CLASSES_PERCENTAGE%**  | $CLASSES        |" >> $REPORT_FILE
        echo "" >> $REPORT_FILE
        echo "[View Full Code Coverage Report](../$COVERAGE_DIR/index.html)" >> $REPORT_FILE
    else
        echo "⚠️ Code coverage report not found at '$COVERAGE_DASHBOARD'." >> $REPORT_FILE
    fi
    echo "" >> $REPORT_FILE
fi

echo "Report generated successfully at $REPORT_FILE"
