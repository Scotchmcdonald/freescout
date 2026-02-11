#!/bin/bash
# Unit Test Analysis Helper Script
# Usage: ./scripts/analyze_unit_tests.sh [category]

set -e

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
REPORT_DIR="reports/unit_test_analysis"
mkdir -p "$REPORT_DIR"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "======================================"
echo "Unit Test Failure Analysis"
echo "======================================"
echo ""

# Function to run tests and capture results
run_test_category() {
    local category=$1
    local output_file="$REPORT_DIR/${category}_${TIMESTAMP}.log"
    
    echo -e "${YELLOW}Running: $category${NC}"
    
    if vendor/bin/pest "tests/Unit/$category" 2>&1 | tee "$output_file"; then
        echo -e "${GREEN}✓ $category PASSED${NC}"
        return 0
    else
        echo -e "${RED}✗ $category FAILED${NC}"
        echo "  Log: $output_file"
        
        # Extract failure summary
        grep -E "Tests:.*failed|FAILED" "$output_file" | tail -5
        return 1
    fi
    echo ""
}

# Function to run a single test file
run_single_test() {
    local test_file=$1
    echo -e "${YELLOW}Running single test: $test_file${NC}"
    
    if vendor/bin/pest "$test_file"; then
        echo -e "${GREEN}✓ Test PASSED individually${NC}"
        return 0
    else
        echo -e "${RED}✗ Test FAILED individually${NC}"
        return 1
    fi
}

# Function to compare sequential vs parallel
compare_modes() {
    echo "======================================"
    echo "Comparing Sequential vs Parallel"
    echo "======================================"
    
    echo "Running Sequential..."
    vendor/bin/pest tests/Unit/ 2>&1 | tee "$REPORT_DIR/sequential_${TIMESTAMP}.log"
    local seq_result=$?
    
    echo ""
    echo "Running Parallel..."
    vendor/bin/pest tests/Unit/ --parallel 2>&1 | tee "$REPORT_DIR/parallel_${TIMESTAMP}.log"
    local par_result=$?
    
    echo ""
    echo "Generating comparison report..."
    
    cat > "$REPORT_DIR/comparison_${TIMESTAMP}.md" << EOF
# Test Execution Comparison

## Sequential Results
\`\`\`
$(tail -20 "$REPORT_DIR/sequential_${TIMESTAMP}.log")
\`\`\`

## Parallel Results
\`\`\`
$(tail -20 "$REPORT_DIR/parallel_${TIMESTAMP}.log")
\`\`\`

## Analysis
- Sequential exit code: $seq_result
- Parallel exit code: $par_result
- Comparison: $([ $seq_result -eq $par_result ] && echo "Same" || echo "Different - potential race conditions")
EOF
    
    echo "Report saved to: $REPORT_DIR/comparison_${TIMESTAMP}.md"
}

# Function to find flaky tests
find_flaky_tests() {
    echo "======================================"
    echo "Searching for Flaky Tests"
    echo "======================================"
    
    local test_file=$1
    local iterations=${2:-5}
    local failures=0
    local passes=0
    
    echo "Running $test_file $iterations times..."
    
    for i in $(seq 1 $iterations); do
        echo -n "  Run $i/$iterations: "
        if vendor/bin/pest "$test_file" > /dev/null 2>&1; then
            echo -e "${GREEN}PASS${NC}"
            ((passes++))
        else
            echo -e "${RED}FAIL${NC}"
            ((failures++))
        fi
    done
    
    echo ""
    echo "Results: $passes passes, $failures failures out of $iterations runs"
    
    if [ $failures -gt 0 ] && [ $passes -gt 0 ]; then
        echo -e "${RED}⚠ FLAKY TEST DETECTED${NC}"
        return 1
    elif [ $failures -eq $iterations ]; then
        echo "Consistently failing (not flaky)"
        return 2
    else
        echo -e "${GREEN}Stable test${NC}"
        return 0
    fi
}

# Main execution based on argument
case "${1:-all}" in
    "all")
        echo "Running ALL unit tests..."
        run_test_category ""
        ;;
    
    "categories")
        echo "Running tests by category..."
        run_test_category "Jobs/"
        run_test_category "Listeners/"
        run_test_category "Mail/"
        run_test_category "Middleware/"
        run_test_category "Models/"
        run_test_category "Services/"
        ;;
    
    "jobs")
        run_test_category "Jobs/"
        ;;
    
    "listeners")
        run_test_category "Listeners/"
        ;;
    
    "mail")
        run_test_category "Mail/"
        ;;
    
    "compare")
        compare_modes
        ;;
    
    "flaky")
        if [ -z "$2" ]; then
            echo "Usage: $0 flaky <test-file> [iterations]"
            exit 1
        fi
        find_flaky_tests "$2" "${3:-5}"
        ;;
    
    "single")
        if [ -z "$2" ]; then
            echo "Usage: $0 single <test-file>"
            exit 1
        fi
        run_single_test "$2"
        ;;
    
    "summary")
        echo "======================================"
        echo "Generating Failure Summary"
        echo "======================================"
        
        # Run tests and extract summary
        vendor/bin/pest tests/Unit/ 2>&1 | tee "$REPORT_DIR/full_run_${TIMESTAMP}.log"
        
        # Parse failures
        echo ""
        echo "Top 10 Most Common Failures:"
        grep -oP "FAILED.*\K(Tests\\\\Unit\\\\[^ ]+)" "$REPORT_DIR/full_run_${TIMESTAMP}.log" | \
            sort | uniq -c | sort -rn | head -10
        
        echo ""
        echo "Failure Types:"
        grep "Failed asserting" "$REPORT_DIR/full_run_${TIMESTAMP}.log" | \
            sed 's/Failed asserting.*//' | sort | uniq -c | sort -rn | head -5
        ;;
    
    "missing-classes")
        echo "Searching for missing class errors..."
        vendor/bin/pest tests/Unit/ 2>&1 | grep -A 2 "Class.*not found" | tee "$REPORT_DIR/missing_classes_${TIMESTAMP}.log"
        ;;
    
    *)
        echo "Usage: $0 {all|categories|jobs|listeners|mail|compare|flaky|single|summary|missing-classes}"
        echo ""
        echo "Examples:"
        echo "  $0 all                                    # Run all unit tests"
        echo "  $0 categories                             # Run by category"
        echo "  $0 jobs                                   # Run only job tests"
        echo "  $0 compare                                # Compare sequential vs parallel"
        echo "  $0 flaky tests/Unit/Listeners/Test.php    # Check if test is flaky"
        echo "  $0 single tests/Unit/Jobs/Test.php        # Run single test file"
        echo "  $0 summary                                # Get failure summary with stats"
        echo "  $0 missing-classes                        # Find missing class errors"
        exit 1
        ;;
esac

echo ""
echo "======================================"
echo "Analysis Complete"
echo "Reports saved to: $REPORT_DIR"
echo "======================================"
