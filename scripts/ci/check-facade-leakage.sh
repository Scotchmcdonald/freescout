#!/usr/bin/env bash
# ==============================================================================
# FACADE LEAKAGE CHECKER
# Fails if Illuminate\Support\Facades are used inside strict Modules.
# ==============================================================================

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
echo "--> Checking for Facade leakage in Modules..."

# Search for Facade usage in PRODUCTION code only.
# The following are intentionally exempt:
#   - Database/Migrations/: Schema:: is the standard Laravel migration API
#   - Providers/: Route::, Gate::, Event::, Blade:: are standard bootstrap patterns
#   - Routes/ and routes/: Route:: is the standard route file API
#   - Tests/: Event::fake(), Http::fake(), Queue::fake() are first-party test helpers
#   - *.md files: documentation only
LEAKS=$(grep -rnw "$ROOT_DIR/Modules" --include="*.php" \
    -e "use Illuminate\\\\Support\\\\Facades" 2>/dev/null \
    | grep -v "/Database/Migrations/" \
    | grep -v "/database/migrations/" \
    | grep -v "/Providers/" \
    | grep -v "/providers/" \
    | grep -v "/Routes/" \
    | grep -v "/routes/" \
    | grep -v "/Tests/" \
    | grep -v "/tests/")

if [ -n "$LEAKS" ]; then
    echo "❌ AUDIT FAILED: Facade leakage detected! Use Dependency Injection instead."
    echo ""
    echo "$LEAKS"
    exit 1
fi

echo "✅ AUDIT PASSED: No Facade leakage detected."
exit 0
