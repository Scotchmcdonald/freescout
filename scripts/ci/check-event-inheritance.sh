#!/bin/bash

echo "📡 Checking event class inheritance..."

cd "$(dirname "${BASH_SOURCE[0]}")/../.."

# Find all event classes
EVENT_FILES=$(find app/Events Modules/ -type f -path "*/Events/*.php" 2>/dev/null | \
    grep -v "VersionedEvent.php" || true)

if [ -z "$EVENT_FILES" ]; then
    echo "ℹ️  No event files found yet"
    exit 0
fi

VIOLATIONS=""
for file in $EVENT_FILES; do
    # Check if file contains a class definition
    if grep -q "class.*Event" "$file"; then
        # Check if it extends VersionedEvent
        if ! grep -q "extends VersionedEvent" "$file"; then
            VIOLATIONS="${VIOLATIONS}${file}\n"
        fi
    fi
done

if [ -n "$VIOLATIONS" ]; then
    echo "❌ FAIL: Event classes must extend VersionedEvent"
    echo ""
    echo -e "$VIOLATIONS"
    echo ""
    echo "All events MUST extend App\Events\VersionedEvent for:"
    echo "  - Unique event IDs (idempotency tracking)"
    echo "  - Schema versioning (backward compatibility)"
    echo "  - Automatic event migration"
    exit 1
fi

echo "✅ All events extend VersionedEvent"
exit 0
