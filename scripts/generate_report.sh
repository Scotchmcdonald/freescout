#!/bin/bash

# generate_report.sh

# This script generates a summary of the test runs based on the provided arguments.

set -e

REPORT_DIR="reports"
REPORT_FILE="$REPORT_DIR/TEST_RESULTS_SUMMARY.md"
COVERAGE_DIR="$REPORT_DIR/coverage-report"
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
    
    # Extract error counts summary
    if [ -s "$REPORT_DIR/phpstan-bodyscan.log" ]; then
        echo "### Error Count Summary" >> $REPORT_FILE
        echo "" >> $REPORT_FILE
        grep -A 11 "Level.*Error count.*Increment" "$REPORT_DIR/phpstan-bodyscan.log" | tail -n 11 >> $REPORT_FILE
        echo "" >> $REPORT_FILE
        
        # Extract detailed errors from bodyscan (runs bare PHPStan without ignores)
        echo "### Detailed Errors by Level" >> $REPORT_FILE
        echo "" >> $REPORT_FILE
        
        # Always run bodyscan at level 9 (maximum strictness)
        BODYSCAN_LEVEL=9
        
        # Extract error count at level 9 from bodyscan summary
        LEVEL_9_ERROR_COUNT=0
        while IFS='|' read -r _ level errors _; do
            level=$(echo "$level" | xargs)
            errors=$(echo "$errors" | xargs)
            if [[ "$level" == "9" ]] && [[ "$errors" =~ ^[0-9]+$ ]]; then
                LEVEL_9_ERROR_COUNT=$errors
                break
            fi
        done < <(grep "|" "$REPORT_DIR/phpstan-bodyscan.log" | tail -10)
        
        if [ "$LEVEL_9_ERROR_COUNT" -gt 0 ] || [ "$LEVEL_9_ERROR_COUNT" -eq 0 ]; then
            echo "Running bare PHPStan analysis at level $BODYSCAN_LEVEL (maximum strictness - $LEVEL_9_ERROR_COUNT errors detected)..." >&2
            echo "" >> $REPORT_FILE
            echo "**Note:** Bodyscan runs PHPStan at level 9 in bare mode (no config ignores) to show all potential issues." >> $REPORT_FILE
            echo "" >> $REPORT_FILE
            
            # Create a minimal bare config without ignores at level 9
            cat > /tmp/phpstan-bare.neon << 'BAREEOF'
parameters:
    level: 9
    paths:
        - /var/www/html/app
BAREEOF
            
            # Run PHPStan with bare config at level 9
            ERROR_OUTPUT=$(vendor/bin/phpstan analyse -c /tmp/phpstan-bare.neon --memory-limit=2G --error-format=table --level=9 --no-progress --no-ansi 2>&1 || true)
            
            if echo "$ERROR_OUTPUT" | grep -q "Line"; then
                echo "#### Level 9 - Detailed Errors (Bare Analysis)" >> $REPORT_FILE
                echo "" >> $REPORT_FILE
                echo "\`\`\`" >> $REPORT_FILE
                # Show first 150 lines of errors (approximately 30-40 errors)
                echo "$ERROR_OUTPUT" | head -150 >> $REPORT_FILE
                echo "\`\`\`" >> $REPORT_FILE
                echo "" >> $REPORT_FILE
                
                if [ "$LEVEL_9_ERROR_COUNT" -gt 40 ]; then
                    echo "*(Showing first ~40 errors of $LEVEL_9_ERROR_COUNT total - run full bodyscan for complete list)*" >> $REPORT_FILE
                    echo "" >> $REPORT_FILE
                fi
            else
                echo "⚠️ Could not extract detailed errors. Run \`vendor/bin/phpstan-bodyscan analyse\` manually." >> $REPORT_FILE
                echo "" >> $REPORT_FILE
            fi
        else
            echo "✅ No errors found at any level." >> $REPORT_FILE
            echo "" >> $REPORT_FILE
        fi
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
        # Extract summary
        echo "### Test Summary" >> $REPORT_FILE
        echo "" >> $REPORT_FILE
        echo "\`\`\`" >> $REPORT_FILE
        grep -E "Tests:  |Time:   " "$REPORT_DIR/artisan-test.log" >> $REPORT_FILE
        echo "\`\`\`" >> $REPORT_FILE
        echo "" >> $REPORT_FILE
        
        # Show failing tests with details if any
        if grep -q "FAILED" "$REPORT_DIR/artisan-test.log"; then
            echo "### ❌ Failed Tests Details" >> $REPORT_FILE
            echo "" >> $REPORT_FILE
            
            # Count total failures
            FAIL_COUNT=$(grep -c "FAILED" "$REPORT_DIR/artisan-test.log" || echo "0")
            echo "**Total Failures: $FAIL_COUNT**" >> $REPORT_FILE
            echo "" >> $REPORT_FILE
            
            # Extract each failed test with FULL error message (not truncated)
            echo "\`\`\`" >> $REPORT_FILE
            grep "FAILED" "$REPORT_DIR/artisan-test.log" | sed 's/^[[:space:]]*//' | head -50 >> $REPORT_FILE
            if [ "$FAIL_COUNT" -gt 50 ]; then
                echo "" >> $REPORT_FILE
                echo "... and $((FAIL_COUNT - 50)) more failures (see full log)" >> $REPORT_FILE
            fi
            echo "\`\`\`" >> $REPORT_FILE
            echo "" >> $REPORT_FILE
            
            # Group errors by type
            echo "### Error Analysis" >> $REPORT_FILE
            echo "" >> $REPORT_FILE
            
            # Extract actual exception types and count them
            ERROR_TYPES=$(grep "FAILED" "$REPORT_DIR/artisan-test.log" | grep -oE '[A-Z][a-zA-Z]*Exception|[A-Z][a-zA-Z]*Error' | sort | uniq -c | sort -rn)
            
            if [ -n "$ERROR_TYPES" ]; then
                echo "**Error Types:**" >> $REPORT_FILE
                echo "\`\`\`" >> $REPORT_FILE
                echo "$ERROR_TYPES" >> $REPORT_FILE
                echo "\`\`\`" >> $REPORT_FILE
                echo "" >> $REPORT_FILE
            fi
            
            # Extract the actual error messages from the log (look for the detailed error output)
            echo "### Sample Error Details" >> $REPORT_FILE
            echo "" >> $REPORT_FILE
            echo "First error detail from log:" >> $REPORT_FILE
            echo "\`\`\`" >> $REPORT_FILE
            # Find first detailed error (starts after FAILED line)
            sed -n '/FAILED.*QueryException/,/^[[:space:]]*$/p' "$REPORT_DIR/artisan-test.log" | head -20 >> $REPORT_FILE
            echo "\`\`\`" >> $REPORT_FILE
        fi
        echo "" >> $REPORT_FILE
    else
        echo "✅ PHP Artisan Test completed without any output." >> $REPORT_FILE
    fi
    echo "" >> $REPORT_FILE

    # --- Code Coverage Summary ---
    echo "## Code Coverage Summary" >> $REPORT_FILE
    echo "" >> $REPORT_FILE
    
    # Check if coverage was generated (only happens when tests pass)
    COVERAGE_INDEX="$COVERAGE_DIR/index.html"
    if [ -f "$COVERAGE_INDEX" ] && [ -s "$COVERAGE_INDEX" ]; then
        # Extract metrics from index.html Total row
        TOTAL_ROW=$(grep -A 10 'class="warning">Total' $COVERAGE_INDEX 2>/dev/null | head -15)
        
        # Extract percentages from the Total row using aria-valuenow
        TOTAL_LINES_PERCENTAGE=$(echo "$TOTAL_ROW" | grep -oP 'aria-valuenow="\K[0-9.]+' | head -1)
        TOTAL_FUNCTIONS_PERCENTAGE=$(echo "$TOTAL_ROW" | grep -oP 'aria-valuenow="\K[0-9.]+' | sed -n '2p')
        TOTAL_CLASSES_PERCENTAGE=$(echo "$TOTAL_ROW" | grep -oP 'aria-valuenow="\K[0-9.]+' | sed -n '3p')
        
        # Extract covered/total numbers (handle &nbsp; entities)
        LINES=$(echo "$TOTAL_ROW" | grep -oP '<div align="right">\K[^<]+' | sed 's/&nbsp;//g' | grep '/' | head -1 | xargs)
        FUNCTIONS=$(echo "$TOTAL_ROW" | grep -oP '<div align="right">\K[^<]+' | sed 's/&nbsp;//g' | grep '/' | sed -n '2p' | xargs)
        CLASSES=$(echo "$TOTAL_ROW" | grep -oP '<div align="right">\K[^<]+' | sed 's/&nbsp;//g' | grep '/' | sed -n '3p' | xargs)

        # Show coverage if we got valid data
        if [ -n "$TOTAL_LINES_PERCENTAGE" ]; then
            echo "### Overall Metrics" >> $REPORT_FILE
            echo "" >> $REPORT_FILE
            echo "| Metric    | Coverage      | Covered / Total |" >> $REPORT_FILE
            echo "|-----------|---------------|-----------------|" >> $REPORT_FILE
            echo "| Lines     | **$TOTAL_LINES_PERCENTAGE%**    | $LINES          |" >> $REPORT_FILE
            echo "| Functions | **$TOTAL_FUNCTIONS_PERCENTAGE%**| $FUNCTIONS      |" >> $REPORT_FILE
            echo "| Classes   | **$TOTAL_CLASSES_PERCENTAGE%**  | $CLASSES        |" >> $REPORT_FILE
            echo "" >> $REPORT_FILE
            echo "[View Full Code Coverage Report](coverage-report/index.html)" >> $REPORT_FILE
            echo "" >> $REPORT_FILE
        
            # Create Python parser for HTML coverage report
            cat > /tmp/parse_coverage.py << 'PYEOF'
#!/usr/bin/env python3
import re
import sys
import os

# Try both locations for coverage report
coverage_path = '/var/www/html/reports/coverage-report/dashboard.html'
if not os.path.exists(coverage_path):
    coverage_path = '/var/www/html/coverage-report/dashboard.html'
    
if not os.path.exists(coverage_path):
    print("ERROR: Coverage report not found", file=sys.stderr)
    sys.exit(1)

with open(coverage_path, 'r') as f:
    content = f.read()

# Extract class insufficient coverage
print("CLASSES_LOW_COVERAGE")
match = re.search(r'<h3>Insufficient Coverage</h3>.*?<tbody>(.*?)</tbody>', content, re.DOTALL)
if match:
    rows = re.findall(r'<tr><td><a[^>]*>([^<]+)</a></td><td class="text-right">([^<]+)</td></tr>', match.group(1))
    for name, coverage in rows[:15]:
        clean_name = name.replace('App\\', '').replace('\\', '.')
        print(f"| {clean_name} | {coverage} |")

print("CLASSES_HIGH_CRAP")
match = re.search(r'<h3>Project Risks</h3>.*?<tbody>(.*?)</tbody>', content, re.DOTALL)
if match:
    rows = re.findall(r'<tr><td><a[^>]*>([^<]+)</a></td><td class="text-right">([^<]+)</td></tr>', match.group(1))
    for name, crap in rows[:15]:
        clean_name = name.replace('App\\', '').replace('\\', '.')
        print(f"| {clean_name} | {crap} |")

print("METHODS_LOW_COVERAGE")
methods_section = re.search(r'<h2>Methods</h2>.*?<h3>Insufficient Coverage</h3>.*?<tbody>(.*?)</tbody>', content, re.DOTALL)
if methods_section:
    rows = re.findall(r'<tr><td><a[^>]*><abbr title="([^"]+)">[^<]*</abbr></a></td><td class="text-right">([^<]+)</td></tr>', methods_section.group(1))
    for name, coverage in rows[:20]:
        clean_name = name.replace('App\\', '').replace('\\', '.')
        print(f"| {clean_name} | {coverage} |")

print("METHODS_HIGH_CRAP")
methods_risks = re.search(r'<h2>Methods</h2>.*?<h3>Project Risks</h3>.*?<tbody>(.*?)</tbody>', content, re.DOTALL)
if methods_risks:
    rows = re.findall(r'<tr><td><a[^>]*><abbr title="([^"]+)">[^<]*</abbr></a></td><td class="text-right">([^<]+)</td></tr>', methods_risks.group(1))
    for name, crap in rows[:20]:
        clean_name = name.replace('App\\', '').replace('\\', '.')
        print(f"| {clean_name} | {crap} |")
PYEOF

            # Run parser and extract sections
            COVERAGE_DATA=$(python3 /tmp/parse_coverage.py 2>/dev/null)
            
            # Extract top classes with insufficient coverage
            echo "### 🚨 Top Classes at Risk (Low Coverage)" >> $REPORT_FILE
            echo "" >> $REPORT_FILE
            echo "| Class | Coverage |" >> $REPORT_FILE
            echo "|-------|----------|" >> $REPORT_FILE
            echo "$COVERAGE_DATA" | sed -n '/CLASSES_LOW_COVERAGE/,/CLASSES_HIGH_CRAP/p' | grep "^|" >> $REPORT_FILE
            echo "" >> $REPORT_FILE
            
            # Extract top classes with high CRAP scores
            echo "### ⚠️ Top Classes at Risk (High CRAP Scores)" >> $REPORT_FILE
            echo "" >> $REPORT_FILE
            echo "| Class | CRAP Score |" >> $REPORT_FILE
            echo "|-------|------------|" >> $REPORT_FILE
            echo "$COVERAGE_DATA" | sed -n '/CLASSES_HIGH_CRAP/,/METHODS_LOW_COVERAGE/p' | grep "^|" >> $REPORT_FILE
            echo "" >> $REPORT_FILE
            
            # Extract top methods with insufficient coverage
            echo "### 🔍 Top Methods at Risk (Low Coverage)" >> $REPORT_FILE
            echo "" >> $REPORT_FILE
            echo "| Method | Coverage |" >> $REPORT_FILE
            echo "|--------|----------|" >> $REPORT_FILE
            echo "$COVERAGE_DATA" | sed -n '/METHODS_LOW_COVERAGE/,/METHODS_HIGH_CRAP/p' | grep "^|" >> $REPORT_FILE
            echo "" >> $REPORT_FILE
            
            # Extract top methods with high CRAP scores
            echo "### ⚡ Top Methods at Risk (High CRAP Scores)" >> $REPORT_FILE
            echo "" >> $REPORT_FILE
            echo "| Method | CRAP Score |" >> $REPORT_FILE
            echo "|--------|------------|" >> $REPORT_FILE
            echo "$COVERAGE_DATA" | sed -n '/METHODS_HIGH_CRAP/,$p' | grep "^|" >> $REPORT_FILE
            echo "" >> $REPORT_FILE
            
            # Test Priority Recommendations (dynamic based on actual data)
            echo "## 📋 Test Priority Recommendations" >> $REPORT_FILE
            echo "" >> $REPORT_FILE
            echo "Based on coverage analysis, prioritize tests for:" >> $REPORT_FILE
            echo "" >> $REPORT_FILE
            echo "### High Priority (Critical & Untested)" >> $REPORT_FILE
            echo "" >> $REPORT_FILE
            
            # Get top 3 classes by CRAP score
            TOP_CRAP_CLASSES=$(echo "$COVERAGE_DATA" | sed -n '/CLASSES_HIGH_CRAP/,/METHODS_LOW_COVERAGE/p' | grep "^|" | head -3)
            echo "$TOP_CRAP_CLASSES" | while IFS='|' read -r _ class crap _; do
                class=$(echo "$class" | xargs)
                crap=$(echo "$crap" | xargs)
                if [ -n "$class" ] && [ -n "$crap" ]; then
                    # Get coverage for this class
                    coverage=$(echo "$COVERAGE_DATA" | sed -n '/CLASSES_LOW_COVERAGE/,/CLASSES_HIGH_CRAP/p' | grep "$class" | awk -F'|' '{print $3}' | xargs)
                    echo "- **$class** - Coverage: ${coverage:-unknown}, CRAP: $crap" >> $REPORT_FILE
                fi
            done
            echo "" >> $REPORT_FILE
            
            echo "### Medium Priority (Partially Tested)" >> $REPORT_FILE
            echo "" >> $REPORT_FILE
            
            # Get classes with 30-60% coverage
            MED_COVERAGE=$(echo "$COVERAGE_DATA" | sed -n '/CLASSES_LOW_COVERAGE/,/CLASSES_HIGH_CRAP/p' | grep "^|" | awk -F'|' '$3 ~ /%/ {cov=$3; gsub(/%/,"",cov); if(cov>=30 && cov<=60) print $0}' | head -5)
            echo "$MED_COVERAGE" | while IFS='|' read -r _ class coverage _; do
                class=$(echo "$class" | xargs)
                coverage=$(echo "$coverage" | xargs)
                if [ -n "$class" ] && [ -n "$coverage" ]; then
                    echo "- **$class** - $coverage coverage" >> $REPORT_FILE
                fi
            done
            echo "" >> $REPORT_FILE
            
            echo "### Top Methods Needing Tests" >> $REPORT_FILE
            echo "" >> $REPORT_FILE
            
            # Get top 5 methods by CRAP
            echo "$COVERAGE_DATA" | sed -n '/METHODS_HIGH_CRAP/,$p' | grep "^|" | head -5 | while IFS='|' read -r _ method crap _; do
                method=$(echo "$method" | xargs)
                crap=$(echo "$crap" | xargs)
                if [ -n "$method" ] && [ -n "$crap" ]; then
                    echo "- \`$method\` - CRAP: $crap" >> $REPORT_FILE
                fi
            done
            echo "" >> $REPORT_FILE
        else
            echo "⚠️ Coverage data could not be parsed from the report." >> $REPORT_FILE
            echo "" >> $REPORT_FILE
        fi
    else
        echo "⚠️ **Coverage report not available.**" >> $REPORT_FILE
        echo "" >> $REPORT_FILE
        echo "Code coverage is only generated when all tests pass successfully." >> $REPORT_FILE
        echo "Fix the failing tests above to enable coverage analysis." >> $REPORT_FILE
    fi
    echo "" >> $REPORT_FILE
fi

echo "Report generated successfully at $REPORT_FILE"
