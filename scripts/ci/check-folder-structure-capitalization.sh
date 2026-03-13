#!/usr/bin/env bash

# ==============================================================================
# LARAVEL FOLDER NAMING AUDITOR (CASE-STRICT)
# Bypasses macOS/Windows case-insensitive mounts by reading directory arrays.
# REPORTING ONLY - Fails with exit code 1 if violations are found.
# ==============================================================================

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ISSUES=()

echo "========================================"
echo " Running Strict Laravel Folder Audit"
echo " Workspace: $ROOT_DIR"
echo "========================================"
echo ""

# Helper function to strictly check if an exact case-sensitive folder exists
# Usage: exact_match_exists "directory_to_scan" "Folder_To_Find"
exact_match_exists() {
    local parent_dir="$1"
    local target="$2"
    # ls -A1 lists all files/folders. grep -qx ensures exact, case-sensitive line match.
    [ -d "$parent_dir" ] && ls -A1 "$parent_dir" 2>/dev/null | grep -qx "$target"
}

# --- 1. Audit Standard Laravel Root Folders (Strict Lowercase) ---
STANDARD_DIRS=("app" "bootstrap" "config" "database" "public" "resources" "routes" "storage" "tests")

for dir in "${STANDARD_DIRS[@]}"; do
    UPPER_DIR="$(tr '[:lower:]' '[:upper:]' <<< ${dir:0:1})${dir:1}"
    if exact_match_exists "$ROOT_DIR" "$UPPER_DIR"; then
        ISSUES+=("[CORE] Root directory '$UPPER_DIR' is capitalized. Laravel expects lowercase '$dir'.")
    fi
done

# --- 1.5 Audit App Directory (PSR-4 PascalCase) ---
# Laravel PSR-4 autoloading requires these to be strictly PascalCase on Linux
APP_SUBDIRS=("Http" "Models" "Providers" "Console" "Exceptions")

for subdir in "${APP_SUBDIRS[@]}"; do
    # Convert the first letter to lowercase to check for mistakes
    LOWER_SUBDIR="$(tr '[:upper:]' '[:lower:]' <<< ${subdir:0:1})${subdir:1}"

    if exact_match_exists "$ROOT_DIR/app" "$LOWER_SUBDIR"; then
        ISSUES+=("[PSR-4] Directory 'app/$LOWER_SUBDIR' is lowercase. PSR-4 requires PascalCase 'app/$subdir'.")
    fi
done

# --- 2. Audit 'tests' Directory Specifics ---
if exact_match_exists "$ROOT_DIR/tests" "javascript"; then
    ISSUES+=("[TESTS] Directory 'tests/javascript' is lowercase. Convention requires PascalCase 'tests/JavaScript'.")
fi

# --- 3. Audit Docs Collision ---
has_docs=$(exact_match_exists "$ROOT_DIR" "docs" && echo true || echo false)
has_Docs=$(exact_match_exists "$ROOT_DIR" "Docs" && echo true || echo false)

if $has_docs && $has_Docs; then
    ISSUES+=("[COLLISION] Both 'docs' and 'Docs' directories exist at the root level.")
elif $has_Docs; then
    ISSUES+=("[NAMING] Directory 'Docs' is capitalized. Consider using lowercase 'docs'.")
fi

# --- 4. Audit 'Modules' Architecture ---
has_modules=$(exact_match_exists "$ROOT_DIR" "modules" && echo true || echo false)
has_Modules=$(exact_match_exists "$ROOT_DIR" "Modules" && echo true || echo false)

if $has_modules && ! $has_Modules; then
    ISSUES+=("[MODULES] Root modules directory is lowercase 'modules'. It should be PascalCase 'Modules'.")
fi

if $has_Modules; then
    for module_path in "$ROOT_DIR/Modules"/*/; do
        [ -d "$module_path" ] || continue
        module=$(basename "$module_path")

        # Check Resources vs resources
        has_Resources=$(exact_match_exists "$module_path" "Resources" && echo true || echo false)
        has_resources=$(exact_match_exists "$module_path" "resources" && echo true || echo false)

        if $has_Resources && $has_resources; then
            ISSUES+=("[COLLISION] Module '$module' has both 'Resources' and 'resources' directories.")
        elif $has_Resources; then
            ISSUES+=("[MODULES] Module '$module' uses 'Resources'. Standard Laravel requires lowercase 'resources'.")
        fi

        # Check Database vs database
        # Convention: PascalCase 'Database/' is correct for nwidart modules.
        # Lowercase 'database/' is the anomaly.
        has_Database=$(exact_match_exists "$module_path" "Database" && echo true || echo false)
        has_database=$(exact_match_exists "$module_path" "database" && echo true || echo false)

        if $has_Database && $has_database; then
            ISSUES+=("[COLLISION] Module '$module' has both 'Database' and 'database' directories. This causes Composer autoload duplicates.")
        fi

        # Check Tests vs tests
        # Convention: PascalCase 'Tests/' is correct; phpunit.xml only scans 'Tests/'.
        # A lowercase 'tests/' alongside 'Tests/' causes PHP 'Cannot redeclare' fatal errors.
        has_Tests=$(exact_match_exists "$module_path" "Tests" && echo true || echo false)
        has_tests=$(exact_match_exists "$module_path" "tests" && echo true || echo false)

        if $has_Tests && $has_tests; then
            ISSUES+=("[COLLISION] Module '$module' has both 'Tests' and 'tests' directories. This causes PHP fatal 'Cannot redeclare' errors.")
        elif $has_tests && ! $has_Tests; then
            ISSUES+=("[MODULES] Module '$module' uses lowercase 'tests'. Convention requires PascalCase 'Tests'.")
        fi
    done
fi

# --- 5. Final Report & CI Exit Codes ---
if [ ${#ISSUES[@]} -eq 0 ]; then
    echo "✅ AUDIT PASSED: No directory naming violations found."
    echo "========================================"
    exit 0
else
    echo "❌ AUDIT FAILED: Found ${#ISSUES[@]} naming violation(s):"
    echo ""
    for issue in "${ISSUES[@]}"; do
        echo "  - $issue"
    done
    echo ""
    echo "Please fix the casing issues using 'git mv <old> <new>' and commit the changes."
    echo "========================================"
    exit 1
fi
