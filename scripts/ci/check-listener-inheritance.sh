#!/bin/bash

echo "👂 Checking listener class inheritance..."

cd "$(dirname "${BASH_SOURCE[0]}")/../.."

# Find all listener classes
LISTENER_FILES=$(find app/Listeners Modules/ -type f -path "*/Listeners/*.php" 2>/dev/null | \
    grep -v "IdempotentListener.php" || true)

if [ -z "$LISTENER_FILES" ]; then
    echo "ℹ️  No listener files found yet"
    exit 0
fi

VIOLATIONS=""
for file in $LISTENER_FILES; do
    # Check if file contains a class definition
    if grep -q "class.*Listener" "$file"; then
        # Check if it extends IdempotentListener
        if ! grep -q "extends IdempotentListener" "$file"; then
            # Allow event subscribers that implement idempotency manually
            # These are marked with "implements idempotency manually" in their docblock
            if grep -q "implements idempotency manually" "$file"; then
                continue
            fi
            VIOLATIONS="${VIOLATIONS}${file}\n"
        fi
    fi
done

if [ -n "$VIOLATIONS" ]; then
    echo "❌ FAIL: Listener classes must extend IdempotentListener"
    echo ""
    echo -e "$VIOLATIONS"
    echo ""
    echo "All listeners MUST extend App\Listeners\IdempotentListener for:"
    echo "  - Automatic event deduplication"
    echo "  - Safe event replay after failures"
    echo "  - Guaranteed exactly-once processing"
    echo ""
    echo "Exception: Event subscribers can implement idempotency manually"
    echo "           Add 'implements idempotency manually' to the class docblock"
    exit 1
fi

echo "✅ All listeners extend IdempotentListener"
exit 0
