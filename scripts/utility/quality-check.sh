#!/bin/bash
# Run all code quality checks
# Usage: ./quality-check.sh

echo "🔍 Running Code Quality Checks..."
echo ""

echo "1️⃣  Laravel Pint (Code Formatting)..."
./vendor/bin/pint --test
PINT_EXIT=$?
echo ""

echo "2️⃣  PHPStan (Static Analysis)..."
./vendor/bin/phpstan analyse --memory-limit=1G
PHPSTAN_EXIT=$?
echo ""

echo "3️⃣  PHPUnit (Tests)..."
php artisan test
PHPUNIT_EXIT=$?
echo ""

echo "================================"
if [ $PINT_EXIT -eq 0 ] && [ $PHPSTAN_EXIT -eq 0 ] && [ $PHPUNIT_EXIT -eq 0 ]; then
    echo "✅ All checks passed!"
    exit 0
else
    echo "❌ Some checks failed:"
    [ $PINT_EXIT -ne 0 ] && echo "  - Pint: FAILED"
    [ $PHPSTAN_EXIT -ne 0 ] && echo "  - PHPStan: FAILED"
    [ $PHPUNIT_EXIT -ne 0 ] && echo "  - PHPUnit: FAILED"
    exit 1
fi
