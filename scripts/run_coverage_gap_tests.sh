#!/bin/bash
# Browser Test Execution Script
# Run new coverage gap tests

set -e

echo "================================================"
echo "Browser Test Coverage Gap Execution"
echo "Date: $(date)"
echo "================================================"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to run test file
run_test_file() {
    local test_file=$1
    local test_name=$2
    
    echo -e "\n${YELLOW}Running: $test_name${NC}"
    echo "File: $test_file"
    
    if php artisan dusk "$test_file"; then
        echo -e "${GREEN}✓ PASSED: $test_name${NC}"
        return 0
    else
        echo -e "${RED}✗ FAILED: $test_name${NC}"
        return 1
    fi
}

# Track results
total_tests=0
passed_tests=0
failed_tests=0

echo -e "\n${YELLOW}=== Priority 1: Revenue Assurance ===${NC}"

# Plan Overrides
((total_tests++))
if run_test_file "tests/Browser/Billing/PlanOverridesTest.php" "Plan Overrides"; then
    ((passed_tests++))
else
    ((failed_tests++))
fi

# Ticket Billing
((total_tests++))
if run_test_file "tests/Browser/Billing/TicketBillingTest.php" "Ticket Billing"; then
    ((passed_tests++))
else
    ((failed_tests++))
fi

# Hardware Procurement
((total_tests++))
if run_test_file "tests/Browser/Commerce/HardwareProcurementTest.php" "Hardware Procurement"; then
    ((passed_tests++))
else
    ((failed_tests++))
fi

# Project Milestones
((total_tests++))
if run_test_file "tests/Browser/Billing/ProjectMilestonesTest.php" "Project Milestones"; then
    ((passed_tests++))
else
    ((failed_tests++))
fi

# Rent to Own
((total_tests++))
if run_test_file "tests/Browser/Billing/RentToOwnTest.php" "Rent to Own"; then
    ((passed_tests++))
else
    ((failed_tests++))
fi

echo -e "\n${YELLOW}=== Priority 2: Service Delivery ===${NC}"

# Entitlement Enforcement
((total_tests++))
if run_test_file "tests/Browser/Service/EntitlementEnforcementTest.php" "Entitlement Enforcement"; then
    ((passed_tests++))
else
    ((failed_tests++))
fi

# Software Assignment (Enhanced)
((total_tests++))
if run_test_file "tests/Browser/Service/SoftwareAssignmentTest.php" "Software Assignment (Atomic Counter)"; then
    ((passed_tests++))
else
    ((failed_tests++))
fi

# Asset Credit Ledger
((total_tests++))
if run_test_file "tests/Browser/Billing/AssetCreditLedgerTest.php" "Asset Credit Ledger"; then
    ((passed_tests++))
else
    ((failed_tests++))
fi

echo -e "\n${YELLOW}=== Priority 3: Client Experience ===${NC}"

# Email Migration Wizard
((total_tests++))
if run_test_file "tests/Browser/EmailMigration/MigrationWizardTest.php" "Email Migration Wizard"; then
    ((passed_tests++))
else
    ((failed_tests++))
fi

# Helpdesk Client Interaction
((total_tests++))
if run_test_file "tests/Browser/Helpdesk/ClientTicketInteractionTest.php" "Helpdesk Client Interaction"; then
    ((passed_tests++))
else
    ((failed_tests++))
fi

# Summary
echo -e "\n================================================"
echo -e "${YELLOW}Test Execution Summary${NC}"
echo "================================================"
echo "Total Test Files: $total_tests"
echo -e "${GREEN}Passed: $passed_tests${NC}"
echo -e "${RED}Failed: $failed_tests${NC}"

if [ $failed_tests -eq 0 ]; then
    echo -e "\n${GREEN}🎉 All tests passed!${NC}"
    exit 0
else
    pass_rate=$(awk "BEGIN {printf \"%.1f\", ($passed_tests/$total_tests)*100}")
    echo -e "\n${YELLOW}Pass Rate: $pass_rate%${NC}"
    echo -e "${RED}⚠ Some tests failed. Review output above.${NC}"
    exit 1
fi
