#!/bin/bash

# Test Runner with Code Coverage
#
# This script runs tests with code coverage enabled using PCOV.
# Note: Code coverage generation is SLOW - full test suite takes 15-30 minutes.
# Consider using --filter or --testsuite to run only specific tests.
#
# Usage:
#   ./scripts/run_tests_with_coverage.sh                    # Run all tests with coverage
#   ./scripts/run_tests_with_coverage.sh --testsuite=Unit   # Run only unit tests
#   ./scripts/run_tests_with_coverage.sh --filter=Mailbox   # Run only mailbox-related tests

set -e

COVERAGE_DIR="reports/coverage-report"

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║           FreeScout Test Suite with Code Coverage             ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Check if PCOV is available
if ! php -m | grep -q pcov; then
    echo "❌ ERROR: PCOV extension is not installed."
    echo ""
    echo "To install PCOV:"
    echo "  sudo apt-get install php8.3-pcov"
    echo "  # or"
    echo "  sudo pecl install pcov"
    exit 1
fi

echo "✓ PCOV extension detected"
echo ""

# Clean up old coverage report
if [ -d "$COVERAGE_DIR" ]; then
    echo "🧹 Cleaning up old coverage report..."
    rm -rf "$COVERAGE_DIR"
fi

mkdir -p reports

echo "📊 Running tests with code coverage..."
echo ""
echo "⚠️  WARNING: Code coverage is SLOW!"
echo "    Full test suite: ~15-30 minutes"
echo "    Unit tests only: ~5-10 minutes"
echo ""
echo "💡 TIP: Use --filter or --testsuite to run specific tests:"
echo "    --testsuite=Unit              # Unit tests only"
echo "    --testsuite=Feature           # Feature tests only"
echo "    --filter=MailboxTest          # Specific test class"
echo ""

# Enable PCOV and run tests
# Note: Cannot use --parallel with code coverage
php -d pcov.enabled=1 \
    -d pcov.directory=/var/www/html/app \
    -d memory_limit=-1 \
    artisan test \
    --coverage-html "$COVERAGE_DIR" \
    --coverage-text \
    "$@"

exit_code=$?

if [ $exit_code -eq 0 ]; then
    echo ""
    echo "✅ Tests completed successfully!"
    echo ""
    echo "📊 Coverage report generated at: $COVERAGE_DIR/index.html"
    echo ""
    echo "To view the report:"
    echo "  Open in browser: file://$(pwd)/$COVERAGE_DIR/index.html"
    echo "  Or use: xdg-open $COVERAGE_DIR/index.html"
else
    echo ""
    echo "❌ Tests failed with exit code: $exit_code"
    exit $exit_code
fi
