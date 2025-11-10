#!/bin/bash
# Documentation Cleanup Script
# Date: November 10, 2025
# Repository: Scotchmcdonald/freescout
# Branch: laravel-11-foundation
#
# REVIEW THIS SCRIPT CAREFULLY BEFORE RUNNING!
# Run from repository root: bash cleanup-docs.sh

set -e  # Exit on error

echo "=== FreeScout Documentation Cleanup ==="
echo "Starting cleanup process..."
echo ""

# Verify we're in the right directory
if [ ! -f "composer.json" ] || [ ! -d "docs" ] || [ ! -d "reports" ]; then
    echo "ERROR: This script must be run from the repository root!"
    echo "Current directory: $(pwd)"
    exit 1
fi

# Create new directory structure
echo "Creating directory structure..."
mkdir -p docs/guides
mkdir -p docs/plans/phpstan
mkdir -p docs/archive/phase-2
mkdir -p docs/archive/phase-3
mkdir -p docs/archive/test-planning
mkdir -p docs/archive/completions

# Reports cleanup - remove redundant .exit files
echo "Cleaning up reports directory..."
rm -f reports/artisan-test.exit
rm -f reports/dusk.exit
rm -f reports/frontend-test.exit
rm -f reports/frontend-lint.exit
rm -f reports/lint-style.exit
rm -f reports/phpstan-analyse.exit
rm -f reports/security-audit.exit

# Move guides to guides/ directory
echo "Organizing guides..."
mv docs/FRONTEND_QUICK_REFERENCE.md docs/guides/FRONTEND_REFERENCE.md
mv docs/DEPLOYMENT.md docs/guides/DEPLOYMENT.md
mv docs/STARTUP_SERVICES_SETUP.md docs/guides/SERVICES_SETUP.md
mv docs/DATABASE_PARITY_MAINTENANCE.md docs/guides/DATABASE_MAINTENANCE.md
mv docs/TEST_SUITE_DOCUMENTATION.md docs/guides/TESTING.md
mv docs/TEST_COVERAGE_ANALYSIS_SUMMARY.md docs/guides/TEST_COVERAGE_SUMMARY.md

# Consolidate Quick Reference guides
echo "Consolidating quick reference guides..."
cat > docs/guides/DEVELOPMENT_REFERENCE.md << 'EOF'
# FreeScout Development Reference

This guide consolidates development commands and implementation patterns for FreeScout.

## Quick Links
- [Module Management](#module-management)
- [Frontend Development](#frontend-development)
- [Database Management](#database-management)
- [Testing](#testing)
- [Feature Implementation](#feature-implementation)

---

EOF

# Append QUICK_REFERENCE content
cat docs/QUICK_REFERENCE.md >> docs/guides/DEVELOPMENT_REFERENCE.md

echo -e "\n---\n\n## Feature Implementation Patterns\n" >> docs/guides/DEVELOPMENT_REFERENCE.md

# Append IMPLEMENTATION_QUICK_REFERENCE content (skip first 2 lines - title and date)
tail -n +3 docs/IMPLEMENTATION_QUICK_REFERENCE.md >> docs/guides/DEVELOPMENT_REFERENCE.md

# Remove original files after consolidation
rm docs/QUICK_REFERENCE.md
rm docs/IMPLEMENTATION_QUICK_REFERENCE.md

# Move plans to plans/ directory
echo "Organizing plans..."
mv docs/FRONTEND_MODERNIZATION.md docs/plans/FRONTEND_MODERNIZATION.md
mv docs/FEATURE_PARITY_ANALYSIS.md docs/plans/FEATURE_PARITY.md
mv docs/PHPSTAN_IMPROVEMENT_PLAN.md docs/plans/phpstan/IMPROVEMENT_PLAN.md
mv docs/PHPSTAN_MAX_LEVEL_ROADMAP.md docs/plans/phpstan/ROADMAP.md
mv docs/PHPSTAN_PARALLEL_IMPLEMENTATION_PLAN.md docs/plans/phpstan/PARALLEL_IMPLEMENTATION.md
mv docs/PHPSTAN_IGNORE_REDUCTION_PLAN.md docs/plans/phpstan/IGNORE_REDUCTION.md

# Move PHPStan report to phpstan-reports/
echo "Organizing PHPStan reports..."
mv docs/PHPSTAN_BODYSCAN_REPORT.txt docs/phpstan-reports/BODYSCAN_REPORT.txt

# Archive Phase 2 documents
echo "Archiving Phase 2 documents..."
mv docs/PHASE2_SMOKE_TESTS_ANALYSIS.md docs/archive/phase-2/SMOKE_TESTS_ANALYSIS.md

# Archive Phase 3 documents
echo "Archiving Phase 3 documents..."
mv docs/PHASE_3_QUICK_START.md docs/archive/phase-3/QUICK_START.md
mv docs/PHASE_3_TEST_ENHANCEMENT_RECOMMENDATIONS.md docs/archive/phase-3/RECOMMENDATIONS.md
mv docs/PHASE_3_ADDENDUM_ADDITIONAL_TESTS.md docs/archive/phase-3/ADDENDUM.md
mv docs/PHASE_3_ADDENDUM_2_THIRD_REVIEW.md docs/archive/phase-3/ADDENDUM_2.md

# Archive test planning documents
echo "Archiving test planning documents..."
mv docs/TEST_COVERAGE_ANALYSIS.md docs/archive/test-planning/COVERAGE_ANALYSIS_DETAILED.md
mv docs/TEST_EXPANSION_PROPOSAL.md docs/archive/test-planning/EXPANSION_PROPOSAL.md
mv docs/TEST_IMPORT_SUMMARY.md docs/archive/test-planning/IMPORT_SUMMARY.md

# Archive completion reports
echo "Archiving completion reports..."
mv docs/PHPSTAN_LEVEL_7_COMPLETED.md docs/archive/completions/PHPSTAN_LEVEL_7_COMPLETED.md
mv docs/PR_19-24_TEST_VERIFICATION.md docs/archive/completions/PR_19-24_VERIFICATION.md

# Delete obsolete files
echo "Removing obsolete files..."
rm -f docs/TEST_FAILURES_REPORT.md
rm -f docs/TEST_FIXES_QUICK_START.md
rm -f docs/SESSION_SUMMARY.md
rm -f docs/AGENT_INIT_PHASE_TEMPLATE.txt
rm -rf docs/IMPLEMENTATION_NOTES

echo ""
echo "=== Cleanup Complete ==="
echo ""
echo "Summary:"
echo "  ✓ Removed 7 redundant .exit files from reports/"
echo "  ✓ Created guides/, plans/, and archive/ subdirectories"
echo "  ✓ Consolidated 2 quick reference guides into 1"
echo "  ✓ Moved 6 guides to docs/guides/"
echo "  ✓ Moved 6 plans to docs/plans/"
echo "  ✓ Archived 10 historical documents"
echo "  ✓ Deleted 5 obsolete files"
echo ""
echo "Remaining in docs/ root:"
echo "  - PROGRESS.md (main tracker)"
echo "  - SUCCESS_METRICS_STATUS.md (current status)"
echo "  - AGENT_PRIMER_PROMPT.md (onboarding)"
echo "  - TEST_EXPANSION_COMPLETED.md (completion summary)"
echo ""
echo "New structure:"
echo "  - docs/guides/ (6 operational guides)"
echo "  - docs/plans/ (6 strategic plans)"
echo "  - docs/archive/ (33 historical documents in subdirectories)"
echo "  - docs/phpstan-reports/ (8 analysis reports)"
echo ""
echo "Next steps:"
echo "  1. Review changes: git status"
echo "  2. Test that all references still work"
echo "  3. Update any links in README.md if needed"
echo "  4. Commit changes: git add . && git commit -m 'Clean up and organize documentation'"
echo ""
