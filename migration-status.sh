#!/bin/bash
# Migration Porting Progress Tracker
# This script helps track which migrations have been ported from archive to modern Laravel 11

echo "📊 FreeScout Migration Porting Status"
echo "======================================"
echo ""

ARCHIVE_DIR="archive/database/migrations"
MODERN_DIR="database/migrations"

# Count migrations
TOTAL_ARCHIVE=$(find $ARCHIVE_DIR -name "*.php" | wc -l)
TOTAL_MODERN=$(find $MODERN_DIR -name "*.php" -not -name "0001_01_01_000000_create_users_table.php" | wc -l)
USERS_TABLE=$(find $MODERN_DIR -name "0001_01_01_000000_create_users_table.php" | wc -l)

echo "Archive (Laravel 5.5): $TOTAL_ARCHIVE migrations"
echo "Modern (Laravel 11):   $(($TOTAL_MODERN + $USERS_TABLE)) migrations"
echo ""

echo "📋 Core CREATE Table Migrations:"
echo "--------------------------------"
find $ARCHIVE_DIR -name "*create*table*.php" | grep -v add | grep -v update | sort | while read file; do
    basename=$(basename "$file")
    if [ -f "$MODERN_DIR/$basename" ] || [[ "$basename" == *"users"* ]] || [[ "$basename" == *"password"* ]]; then
        echo "✅ $basename"
    else
        echo "⏳ $basename"
    fi
done

echo ""
echo "📋 Modification Migrations (add/update/drop columns):"
echo "-----------------------------------------------------"
MOD_COUNT=$(find $ARCHIVE_DIR -name "*.php" | grep -E "(add_|update_|drop_|change_)" | wc -l)
echo "Total: $MOD_COUNT migrations to port after core tables"

echo ""
echo "🎯 Next Steps:"
echo "-------------"
echo "1. Port remaining CREATE table migrations"
echo "2. Port column modification migrations in chronological order"
echo "3. Test migrations with: php artisan migrate"
echo "4. Run: php artisan migrate:status to verify"
