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
        
        # Now extract detailed errors for each level
        echo "### Detailed Errors by Level" >> $REPORT_FILE
        echo "" >> $REPORT_FILE
        
        for level in {0..9}; do
            echo "Running PHPStan level $level analysis for detailed errors..." >&2
            if vendor/bin/phpstan analyse --memory-limit=2G --error-format=table --level=$level --no-progress 2>&1 | grep -q "File"; then
                ERROR_OUTPUT=$(vendor/bin/phpstan analyse --memory-limit=2G --error-format=table --level=$level --no-progress 2>&1)
                ERROR_COUNT=$(echo "$ERROR_OUTPUT" | grep -oP '\d+(?= error)' | head -1 || echo "0")
                
                if [ "$ERROR_COUNT" != "0" ]; then
                    echo "#### Level $level - $ERROR_COUNT errors" >> $REPORT_FILE
                    echo "" >> $REPORT_FILE
                    echo "\`\`\`" >> $REPORT_FILE
                    echo "$ERROR_OUTPUT" | grep -A 1000 "File" | grep -v "^\[" >> $REPORT_FILE
                    echo "\`\`\`" >> $REPORT_FILE
                    echo "" >> $REPORT_FILE
                fi
            fi
        done
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
            
            # Extract each failed test with its error message
            echo "\`\`\`" >> $REPORT_FILE
            grep -B 1 "FAILED" "$REPORT_DIR/artisan-test.log" | grep -v "^--$" >> $REPORT_FILE
            echo "\`\`\`" >> $REPORT_FILE
            echo "" >> $REPORT_FILE
            
            # Group errors by type
            echo "### Error Summary by Type" >> $REPORT_FILE
            echo "" >> $REPORT_FILE
            
            ERROR_TYPES=$(grep "FAILED" "$REPORT_DIR/artisan-test.log" | awk '{for(i=1;i<=NF;i++) if($i ~ /Error|Exception/) print $i}' | sort | uniq -c | sort -rn)
            
            if [ -n "$ERROR_TYPES" ]; then
                echo "\`\`\`" >> $REPORT_FILE
                echo "$ERROR_TYPES" >> $REPORT_FILE
                echo "\`\`\`" >> $REPORT_FILE
            fi
            
            # Extract unique error messages
            echo "" >> $REPORT_FILE
            echo "### Unique Error Messages" >> $REPORT_FILE
            echo "" >> $REPORT_FILE
            echo "\`\`\`" >> $REPORT_FILE
            grep "FAILED" "$REPORT_DIR/artisan-test.log" | sed 's/.*FAILED[^>]*> //' | sort -u >> $REPORT_FILE
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
    if [ -f "$COVERAGE_DASHBOARD" ] && [ -s "$COVERAGE_DASHBOARD" ]; then
        # Extract metrics using grep and sed
        LINES=$(grep -A 2 'Lines' $COVERAGE_DASHBOARD 2>/dev/null | tail -n 1 | sed -e 's/<[^>]*>//g' | xargs)
        FUNCTIONS=$(grep -A 2 'Functions' $COVERAGE_DASHBOARD 2>/dev/null | tail -n 1 | sed -e 's/<[^>]*>//g' | xargs)
        CLASSES=$(grep -A 2 'Classes' $COVERAGE_DASHBOARD 2>/dev/null | tail -n 1 | sed -e 's/<[^>]*>//g' | xargs)

        # Extract total percentages from progress bars
        TOTAL_LINES_PERCENTAGE=$(grep 'Lines' $COVERAGE_DASHBOARD -A 4 2>/dev/null | grep 'width' | sed 's/.*width: \(.*\)%.*/\1/' | head -1)
        TOTAL_FUNCTIONS_PERCENTAGE=$(grep 'Functions' $COVERAGE_DASHBOARD -A 4 2>/dev/null | grep 'width' | sed 's/.*width: \(.*\)%.*/\1/' | head -1)
        TOTAL_CLASSES_PERCENTAGE=$(grep 'Classes' $COVERAGE_DASHBOARD -A 4 2>/dev/null | grep 'width' | sed 's/.*width: \(.*\)%.*/\1/' | head -1)

        # Only show coverage if we got valid data
        if [ -n "$TOTAL_LINES_PERCENTAGE" ] && [ -n "$LINES" ]; then
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
