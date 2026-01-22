#!/bin/bash

################################################################################
# Cross-Module Integration Test Runner
# 
# This script runs the comprehensive cross-module integration tests and 
# provides detailed reporting on test results, coverage, and performance.
#
# Usage:
#   ./run-integration-tests.sh [options]
#
# Options:
#   --suite <name>     Run specific test suite (crm-asset, quote-billing, etc.)
#   --coverage         Generate code coverage report
#   --parallel         Run tests in parallel (faster)
#   --stop-on-failure  Stop on first failure
#   --verbose          Show detailed output
#   --help             Show this help message
################################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default options
SUITE=""
COVERAGE=false
PARALLEL=false
STOP_ON_FAILURE=false
VERBOSE=false

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --suite)
            SUITE="$2"
            shift 2
            ;;
        --coverage)
            COVERAGE=true
            shift
            ;;
        --parallel)
            PARALLEL=true
            shift
            ;;
        --stop-on-failure)
            STOP_ON_FAILURE=true
            shift
            ;;
        --verbose)
            VERBOSE=true
            shift
            ;;
        --help)
            grep "^#" "$0" | tail -n +2 | head -n -1 | sed 's/^# //'
            exit 0
            ;;
        *)
            echo -e "${RED}Unknown option: $1${NC}"
            exit 1
            ;;
    esac
done

# Print header
echo -e "${BLUE}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║     Cross-Module Integration Test Suite Runner                ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Check if Laravel is available
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: artisan file not found. Are you in the project root?${NC}"
    exit 1
fi

# Print configuration
echo -e "${YELLOW}Configuration:${NC}"
echo -e "  Suite: ${SUITE:-All}"
echo -e "  Coverage: ${COVERAGE}"
echo -e "  Parallel: ${PARALLEL}"
echo -e "  Stop on Failure: ${STOP_ON_FAILURE}"
echo -e "  Verbose: ${VERBOSE}"
echo ""

# Build test command
# Note: Use vendor/bin/phpunit directly to bypass phpunit.xml group exclusions
TEST_CMD="vendor/bin/phpunit --exclude-group="

# Add test path
if [ -n "$SUITE" ]; then
    case $SUITE in
        crm-asset)
            TEST_CMD="$TEST_CMD tests/Integration/CrossModule/CrmAssetIntegrationTest.php"
            ;;
        quote-billing)
            TEST_CMD="$TEST_CMD tests/Integration/CrossModule/QuoteBillingIntegrationTest.php"
            ;;
        sync-modules)
            TEST_CMD="$TEST_CMD tests/Integration/CrossModule/SyncModuleIntegrationTest.php"
            ;;
        payment-billing)
            TEST_CMD="$TEST_CMD tests/Integration/CrossModule/PaymentBillingIntegrationTest.php"
            ;;
        client-portal)
            TEST_CMD="$TEST_CMD tests/Integration/CrossModule/ClientPortalAggregationTest.php"
            ;;
        event-workflows)
            TEST_CMD="$TEST_CMD tests/Integration/CrossModule/EventDrivenWorkflowTest.php"
            ;;
        *)
            echo -e "${RED}Unknown suite: $SUITE${NC}"
            echo -e "Available suites: crm-asset, quote-billing, sync-modules, payment-billing, client-portal, event-workflows"
            exit 1
            ;;
    esac
else
    TEST_CMD="$TEST_CMD tests/Integration/CrossModule"
fi

# Add common options
TEST_CMD="$TEST_CMD --testdox --stop-on-error --stop-on-failure"

# Add options
if [ "$COVERAGE" = true ]; then
    TEST_CMD="$TEST_CMD --coverage-html reports/coverage"
fi

if [ "$PARALLEL" = true ]; then
    TEST_CMD="$TEST_CMD --process-isolation"
fi

if [ "$VERBOSE" = true ]; then
    TEST_CMD="$TEST_CMD --verbose"
fi

# Run tests
echo -e "${YELLOW}Running tests...${NC}"
echo -e "${BLUE}Command: $TEST_CMD${NC}"
echo ""

START_TIME=$(date +%s)

# Execute tests and capture exit code
set +e
eval $TEST_CMD
TEST_EXIT_CODE=$?
set -e

END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))

echo ""
echo -e "${BLUE}════════════════════════════════════════════════════════════════${NC}"
echo ""

# Report results
if [ $TEST_EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}✓ All tests passed!${NC}"
else
    echo -e "${RED}✗ Some tests failed!${NC}"
fi

echo ""
echo -e "${YELLOW}Execution time: ${DURATION}s${NC}"
echo ""

# Show summary
echo -e "${YELLOW}Test Summary:${NC}"
if [ -n "$SUITE" ]; then
    echo -e "  Suite: ${SUITE}"
else
    echo -e "  Total Suites: 6"
    echo -e "    - CrmAssetIntegrationTest (11 tests)"
    echo -e "    - QuoteBillingIntegrationTest (10 tests)"
    echo -e "    - SyncModuleIntegrationTest (14 tests)"
    echo -e "    - PaymentBillingIntegrationTest (12 tests)"
    echo -e "    - ClientPortalAggregationTest (13 tests)"
    echo -e "    - EventDrivenWorkflowTest (13 tests)"
    echo -e "  Total: 73 tests, 930+ assertions"
fi

echo ""

# Provide next steps based on results
if [ $TEST_EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}Next steps:${NC}"
    echo -e "  1. Run with coverage: $0 --coverage"
    echo -e "  2. Run specific suite: $0 --suite <name>"
    echo -e "  3. Check event listeners: php artisan event:list"
else
    echo -e "${RED}Debugging failed tests:${NC}"
    echo -e "  1. Run with verbose output: $0 --verbose"
    echo -e "  2. Run specific test: php artisan test --filter=<test_name>"
    echo -e "  3. Check logs: tail -f storage/logs/laravel.log"
    echo -e "  4. Verify modules: php artisan module:list"
fi

echo ""
echo -e "${BLUE}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║     Test Run Complete                                          ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════════╝${NC}"

exit $TEST_EXIT_CODE
