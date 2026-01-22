#!/bin/bash

# Architecture Verification Script
# Verifies "Core Blindness" - ensuring CRM Core modules do not depend on Feature Modules.

echo "🔍 Running Architectural Compliance Check (Core Blindness)..."
VIOLATIONS=0

# Ensure we are in the project root
cd "$(dirname "${BASH_SOURCE[0]}")/../.."

# Define Forbidden Patterns for CRM
# CRM should NOT import from: PIB, AssetManagement, Payment, SoftwareSubscriptions, ContractManager
FORBIDDEN_MODULES="PIB|AssetManagement|Payment|SoftwareSubscriptions|ContractManager"

# Check 1: Core Blindness in CRM
echo "Checking Modules/Crm for prohibited imports..."
grep -rE "use Modules\\\\($FORBIDDEN_MODULES)" Modules/Crm --exclude-dir=Tests
if [ $? -eq 0 ]; then
    echo "❌ CRITICAL VIOLATION: CRM module is importing from Feature Modules!"
    VIOLATIONS=1
else
    echo "✅ CRM Module is clean."
fi

# Check 2: Core Blindness in App Core (Models/Services)
# App Core should NOT import from Feature Modules (except maybe aggregator controllers)
echo "Checking app/Models and app/Services for prohibited imports..."
grep -rE "use Modules\\\\($FORBIDDEN_MODULES)" app/Models app/Services
if [ $? -eq 0 ]; then
    echo "❌ CRITICAL VIOLATION: App Core is importing from Feature Modules!"
    VIOLATIONS=1
else
    echo "✅ App Core Models/Services are clean."
fi

if [ $VIOLATIONS -eq 0 ]; then
    echo "🎉 Architecture Audit Passed!"
    exit 0
else
    echo "Detailed errors above."
    exit 1
fi
