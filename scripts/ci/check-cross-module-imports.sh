#!/bin/bash

echo "📦 Checking for cross-module import violations..."

cd "$(dirname "${BASH_SOURCE[0]}")/../.."

# Check #1: No imports from feature modules in core
if grep -r "use Modules\\\\" app/Models/ app/Services/ app/Events/ app/Listeners/ 2>/dev/null | grep -v "test"; then
    echo "❌ FAIL: Core code imports from feature modules (violates Core Blindness principle)"
    echo "   Core modules (app/) should not depend on feature modules (Modules/)"
    exit 1
fi

# Check #2: No direct cross-module model imports in listeners (excluding test files)
VIOLATIONS=$(find Modules/ -type f -path "*/Listeners/*.php" ! -path "*/Tests/*" -exec grep -l "use Modules\\\\" {} \; 2>/dev/null | while read file; do
    # Extract module name from file path
    MODULE=$(echo "$file" | sed -n 's|Modules/\([^/]*\)/.*|\1|p')
    
    # Check if importing from different module (ALLOW importing Events)
    if grep "use Modules\\\\" "$file" | grep -v "use Modules\\\\${MODULE}\\\\" | grep -v "\\\\Events\\\\"; then
        echo "$file"
    fi
done)

if [ -n "$VIOLATIONS" ]; then
    echo "❌ FAIL: Listeners import models from other modules (use events instead)"
    echo "$VIOLATIONS"
    exit 1
fi

echo "✅ No cross-module import violations detected"
exit 0
