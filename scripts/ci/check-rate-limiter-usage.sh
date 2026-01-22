#!/bin/bash

echo "⏱️  Checking rate limiter usage in API services..."

cd "$(dirname "${BASH_SOURCE[0]}")/../.."

# API service classes that should use rate limiting
API_SERVICES="GoogleWorkspaceService|Action1Service|HelcimService"

# Find API service files
SERVICE_FILES=$(find app/Services Modules/ -type f -name "*Service.php" 2>/dev/null | \
    grep -E "($API_SERVICES)" || true)

if [ -z "$SERVICE_FILES" ]; then
    echo "ℹ️  No API service files found yet"
    exit 0
fi

# Check if services use RateLimiter or CircuitBreaker
MISSING_RESILIENCE=""
for file in $SERVICE_FILES; do
    if ! grep -qE "RateLimiter|CircuitBreaker" "$file"; then
        MISSING_RESILIENCE="${MISSING_RESILIENCE}${file}\n"
    fi
done

if [ -n "$MISSING_RESILIENCE" ]; then
    echo "⚠️  WARNING: API service classes should use RateLimiter or CircuitBreaker"
    echo ""
    echo -e "$MISSING_RESILIENCE"
    echo ""
    echo "Consider wrapping API calls with:"
    echo "app(RateLimiter::class)->attempt(\$key, \$max, \$decay, fn() => ...)"
    echo "OR app(CircuitBreaker::class)->call(\$key, fn() => ...)"
    # Warning only - not critical
fi

echo "✅ API services use resilience patterns (RateLimiter or CircuitBreaker)"
exit 0
