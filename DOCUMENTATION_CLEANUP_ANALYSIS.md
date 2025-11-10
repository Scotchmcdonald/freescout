# Documentation Cleanup Analysis
**Date**: November 10, 2025  
**Repository**: Scotchmcdonald/freescout  
**Branch**: laravel-11-foundation  
**Analyst**: Technical Documentation Manager

---

## INVENTORY SUMMARY

### Reports Directory
- **Total files**: 130 files
- **Total size**: ~15MB
- **Main content**: Coverage reports (HTML), test logs, exit codes
- **Breakdown by category**:
  - **KEEP**: 6 files (summary report, action plan, current logs)
  - **ARCHIVE**: 0 files (all current)
  - **DELETE**: 6 files (exit code files - redundant)
  - **CONSOLIDATE**: 0 files

### Docs Directory
- **Total files**: 64 files
- **Total size**: ~1MB
- **Main content**: Guides, plans, session summaries, test analysis, PHPStan reports
- **Breakdown by category**:
  - **KEEP**: 18 files (current guides, active plans)
  - **ARCHIVE**: 29 files (already in archive/)
  - **DELETE**: 8 files (superseded, redundant)
  - **CONSOLIDATE**: 9 files (multiple overlapping guides)

---

## FILE-BY-FILE ANALYSIS

### REPORTS DIRECTORY

#### Current Test Reports (KEEP)

**File:** reports/summary-report.txt  
**Category:** KEEP  
**Size/Date:** 22K, Nov 10 04:23  
**Reasoning:** Current test run summary showing 4 failing test categories. Essential for tracking test status.  
**Action:** Keep as-is

**File:** reports/action-plan.md  
**Category:** KEEP  
**Size/Date:** 5.8K, Nov 10 04:23  
**Reasoning:** Action plan generated from current test failures. Active document for fixing issues.  
**Action:** Keep as-is

**File:** reports/artisan-test.log  
**Category:** KEEP  
**Size/Date:** 2.1K, Nov 10 04:23  
**Reasoning:** Current test run log for PHPUnit tests.  
**Action:** Keep as-is

**File:** reports/dusk.log  
**Category:** KEEP  
**Size/Date:** 1.5K, Nov 10 04:23  
**Reasoning:** Current browser test failure log.  
**Action:** Keep as-is

**File:** reports/lint-style.log  
**Category:** KEEP  
**Size/Date:** 14K, Nov 10 04:23  
**Reasoning:** Current Pint style violations (156 issues).  
**Action:** Keep as-is

**File:** reports/security-audit.log  
**Category:** KEEP  
**Size/Date:** 44 bytes, Nov 10 04:23  
**Reasoning:** Current security audit status.  
**Action:** Keep as-is

**File:** reports/frontend-test.log  
**Category:** KEEP  
**Size/Date:** 5.3K, Nov 10 04:23  
**Reasoning:** Current frontend test failures (4 tests).  
**Action:** Keep as-is

**File:** reports/frontend-lint.log  
**Category:** KEEP  
**Size/Date:** 303 bytes, Nov 10 04:23  
**Reasoning:** Current frontend lint status.  
**Action:** Keep as-is

**File:** reports/phpstan-analyse.log  
**Category:** KEEP  
**Size/Date:** 352 bytes, Nov 10 04:23  
**Reasoning:** Current PHPStan analysis log.  
**Action:** Keep as-is

#### Coverage Report (KEEP)

**File:** reports/coverage-report/  
**Category:** KEEP  
**Size/Date:** ~15MB, Nov 10 04:23  
**Reasoning:** Current code coverage HTML report. Essential for understanding test coverage.  
**Action:** Keep as-is

#### Exit Code Files (DELETE)

**File:** reports/artisan-test.exit  
**Category:** DELETE  
**Size/Date:** 2 bytes, Nov 10 04:23  
**Reasoning:** Redundant - exit codes already in summary-report.txt  
**Action:** Delete

**File:** reports/dusk.exit  
**Category:** DELETE  
**Size/Date:** 2 bytes, Nov 10 04:23  
**Reasoning:** Redundant - exit codes already in summary-report.txt  
**Action:** Delete

**File:** reports/frontend-test.exit  
**Category:** DELETE  
**Size/Date:** 2 bytes, Nov 10 04:23  
**Reasoning:** Redundant - exit codes already in summary-report.txt  
**Action:** Delete

**File:** reports/frontend-lint.exit  
**Category:** DELETE  
**Size/Date:** 2 bytes, Nov 10 04:23  
**Reasoning:** Redundant - exit codes already in summary-report.txt  
**Action:** Delete

**File:** reports/lint-style.exit  
**Category:** DELETE  
**Size/Date:** 2 bytes, Nov 10 04:23  
**Reasoning:** Redundant - exit codes already in summary-report.txt  
**Action:** Delete

**File:** reports/phpstan-analyse.exit  
**Category:** DELETE  
**Size/Date:** 2 bytes, Nov 10 04:23  
**Reasoning:** Redundant - exit codes already in summary-report.txt  
**Action:** Delete

**File:** reports/security-audit.exit  
**Category:** DELETE  
**Size/Date:** 2 bytes, Nov 10 04:23  
**Reasoning:** Redundant - exit codes already in summary-report.txt  
**Action:** Delete

---

### DOCS DIRECTORY

#### Current Guides & References (KEEP)

**File:** docs/PROGRESS.md  
**Category:** KEEP  
**Size/Date:** 42K, Nov 10 04:23  
**Reasoning:** Master progress document. Shows 97% completion. Actively maintained.  
**Action:** Keep as-is

**File:** docs/QUICK_REFERENCE.md  
**Category:** CONSOLIDATE  
**Size/Date:** 5.4K, Nov 10 04:23  
**Reasoning:** Development commands reference. Overlaps with IMPLEMENTATION_QUICK_REFERENCE.md  
**Action:** Merge with IMPLEMENTATION_QUICK_REFERENCE.md

**File:** docs/IMPLEMENTATION_QUICK_REFERENCE.md  
**Category:** CONSOLIDATE  
**Size/Date:** 5.9K, Nov 10 04:23  
**Reasoning:** Feature implementation reference. Overlaps with QUICK_REFERENCE.md  
**Action:** Merge with QUICK_REFERENCE.md into single DEVELOPMENT_REFERENCE.md

**File:** docs/FRONTEND_QUICK_REFERENCE.md  
**Category:** KEEP  
**Size/Date:** 12K, Nov 10 04:23  
**Reasoning:** Frontend-specific development guide. Distinct from backend guides.  
**Action:** Move to docs/guides/FRONTEND_REFERENCE.md

**File:** docs/DEPLOYMENT.md  
**Category:** KEEP  
**Size/Date:** 11K, Nov 10 04:23  
**Reasoning:** Deployment instructions. Essential operational document.  
**Action:** Move to docs/guides/DEPLOYMENT.md

**File:** docs/STARTUP_SERVICES_SETUP.md  
**Category:** KEEP  
**Size/Date:** 14K, Nov 10 04:23  
**Reasoning:** Service setup guide. Essential for development environment.  
**Action:** Move to docs/guides/SERVICES_SETUP.md

**File:** docs/DATABASE_PARITY_MAINTENANCE.md  
**Category:** KEEP  
**Size/Date:** 14K, Nov 10 04:23  
**Reasoning:** Database maintenance procedures. Active reference.  
**Action:** Move to docs/guides/DATABASE_MAINTENANCE.md

#### Active Plans & Roadmaps (KEEP)

**File:** docs/FRONTEND_MODERNIZATION.md  
**Category:** KEEP  
**Size/Date:** 15K, Nov 10 04:23  
**Reasoning:** Active frontend modernization plan.  
**Action:** Move to docs/plans/FRONTEND_MODERNIZATION.md

**File:** docs/FEATURE_PARITY_ANALYSIS.md  
**Category:** KEEP  
**Size/Date:** 14K, Nov 10 04:23  
**Reasoning:** Feature comparison analysis. Active reference.  
**Action:** Move to docs/plans/FEATURE_PARITY.md

**File:** docs/PHPSTAN_IMPROVEMENT_PLAN.md  
**Category:** KEEP  
**Size/Date:** 24K, Nov 10 04:23  
**Reasoning:** PHPStan improvement strategy. Active plan.  
**Action:** Move to docs/plans/PHPSTAN_IMPROVEMENT.md

**File:** docs/PHPSTAN_MAX_LEVEL_ROADMAP.md  
**Category:** KEEP  
**Size/Date:** 12K, Nov 10 04:23  
**Reasoning:** PHPStan level progression plan.  
**Action:** Move to docs/plans/PHPSTAN_ROADMAP.md

**File:** docs/PHPSTAN_PARALLEL_IMPLEMENTATION_PLAN.md  
**Category:** KEEP  
**Size/Date:** 15K, Nov 10 04:23  
**Reasoning:** PHPStan parallel processing implementation.  
**Action:** Move to docs/plans/PHPSTAN_PARALLEL.md

**File:** docs/PHPSTAN_IGNORE_REDUCTION_PLAN.md  
**Category:** KEEP  
**Size/Date:** 11K, Nov 10 04:23  
**Reasoning:** Strategy for reducing PHPStan ignores.  
**Action:** Move to docs/plans/PHPSTAN_IGNORE_REDUCTION.md

#### Test Documentation (KEEP/CONSOLIDATE)

**File:** docs/TEST_SUITE_DOCUMENTATION.md  
**Category:** KEEP  
**Size/Date:** 21K, Nov 10 04:23  
**Reasoning:** Comprehensive test suite documentation.  
**Action:** Move to docs/guides/TESTING.md

**File:** docs/TEST_COVERAGE_ANALYSIS.md  
**Category:** ARCHIVE  
**Size/Date:** 41K, Nov 10 04:23  
**Reasoning:** Detailed coverage analysis. Historical - superseded by TEST_COVERAGE_ANALYSIS_SUMMARY.md  
**Action:** Move to docs/archive/TEST_COVERAGE_ANALYSIS.md

**File:** docs/TEST_COVERAGE_ANALYSIS_SUMMARY.md  
**Category:** KEEP  
**Size/Date:** 14K, Nov 10 04:23  
**Reasoning:** Executive summary of coverage analysis. Current reference.  
**Action:** Keep as-is or move to docs/guides/TEST_COVERAGE_SUMMARY.md

**File:** docs/TEST_EXPANSION_PROPOSAL.md  
**Category:** ARCHIVE  
**Size/Date:** 29K, Nov 10 04:23  
**Reasoning:** Proposal document - work has been completed per TEST_EXPANSION_COMPLETED.md  
**Action:** Move to docs/archive/TEST_EXPANSION_PROPOSAL.md

**File:** docs/TEST_EXPANSION_COMPLETED.md  
**Category:** KEEP  
**Size/Date:** 11K, Nov 10 04:23  
**Reasoning:** Completion summary of test expansion work.  
**Action:** Keep as-is

**File:** docs/TEST_FAILURES_REPORT.md  
**Category:** DELETE  
**Size/Date:** 8.1K, Nov 10 04:23  
**Reasoning:** Superseded by reports/summary-report.txt and reports/action-plan.md  
**Action:** Delete

**File:** docs/TEST_FIXES_QUICK_START.md  
**Category:** DELETE  
**Size/Date:** 2.7K, Nov 10 04:23  
**Reasoning:** Superseded by reports/action-plan.md  
**Action:** Delete

**File:** docs/TEST_IMPORT_SUMMARY.md  
**Category:** ARCHIVE  
**Size/Date:** 5.8K, Nov 10 04:23  
**Reasoning:** Historical summary of test import process.  
**Action:** Move to docs/archive/TEST_IMPORT_SUMMARY.md

#### Phase 3 Test Documentation (CONSOLIDATE)

**File:** docs/PHASE_3_QUICK_START.md  
**Category:** CONSOLIDATE  
**Size/Date:** 4.5K, Nov 10 04:23  
**Reasoning:** Phase 3 quick start. Should be merged with related Phase 3 docs.  
**Action:** Consider archiving entire Phase 3 set since work is complete

**File:** docs/PHASE_3_TEST_ENHANCEMENT_RECOMMENDATIONS.md  
**Category:** ARCHIVE  
**Size/Date:** 29K, Nov 10 04:23  
**Reasoning:** Phase 3 enhancement recommendations. Historical - work completed.  
**Action:** Move to docs/archive/PHASE_3_RECOMMENDATIONS.md

**File:** docs/PHASE_3_ADDENDUM_ADDITIONAL_TESTS.md  
**Category:** ARCHIVE  
**Size/Date:** 22K, Nov 10 04:23  
**Reasoning:** Phase 3 addendum. Historical - work completed.  
**Action:** Move to docs/archive/PHASE_3_ADDENDUM.md

**File:** docs/PHASE_3_ADDENDUM_2_THIRD_REVIEW.md  
**Category:** ARCHIVE  
**Size/Date:** 26K, Nov 10 04:23  
**Reasoning:** Phase 3 second addendum. Historical - work completed.  
**Action:** Move to docs/archive/PHASE_3_ADDENDUM_2.md

**File:** docs/PHASE2_SMOKE_TESTS_ANALYSIS.md  
**Category:** ARCHIVE  
**Size/Date:** 32K, Nov 10 04:23  
**Reasoning:** Phase 2 analysis. Historical - work completed.  
**Action:** Move to docs/archive/PHASE2_ANALYSIS.md

#### Status & Completion Reports (ARCHIVE)

**File:** docs/SUCCESS_METRICS_STATUS.md  
**Category:** KEEP  
**Size/Date:** 5.5K, Nov 10 04:23  
**Reasoning:** Current success metrics and status. Actively tracking issues.  
**Action:** Keep as-is

**File:** docs/PHPSTAN_LEVEL_7_COMPLETED.md  
**Category:** ARCHIVE  
**Size/Date:** 6.5K, Nov 10 04:23  
**Reasoning:** Completion report. Historical reference.  
**Action:** Move to docs/archive/PHPSTAN_LEVEL_7_COMPLETED.md

**File:** docs/PR_19-24_TEST_VERIFICATION.md  
**Category:** ARCHIVE  
**Size/Date:** 11K, Nov 10 04:23  
**Reasoning:** Specific PR verification document. Historical.  
**Action:** Move to docs/archive/PR_19-24_VERIFICATION.md

#### Session & Agent Documentation (DELETE/ARCHIVE)

**File:** docs/SESSION_SUMMARY.md  
**Category:** DELETE  
**Size/Date:** 10K, Nov 10 04:23  
**Reasoning:** Temporary session summary. Information captured in PROGRESS.md  
**Action:** Delete

**File:** docs/AGENT_PRIMER_PROMPT.md  
**Category:** KEEP  
**Size/Date:** 11K, Nov 10 04:23  
**Reasoning:** Agent onboarding documentation. Useful for future contributors.  
**Action:** Keep as-is

**File:** docs/AGENT_INIT_PHASE_TEMPLATE.txt  
**Category:** DELETE  
**Size/Date:** 1.7K, Nov 10 04:23  
**Reasoning:** Template file. Not actively used.  
**Action:** Delete

**File:** docs/IMPLEMENTATION_NOTES/session-2025-11-05.md  
**Category:** DELETE  
**Size/Date:** 5.6K, Nov 10 04:23  
**Reasoning:** Single session note. Information captured in main docs.  
**Action:** Delete entire IMPLEMENTATION_NOTES directory

#### PHPStan Reports (KEEP)

**File:** docs/phpstan-reports/*.json  
**Category:** KEEP  
**Size/Date:** Various, Nov 10 04:23  
**Reasoning:** Historical PHPStan error reports. Useful for tracking progress.  
**Action:** Keep as-is

**File:** docs/PHPSTAN_BODYSCAN_REPORT.txt  
**Category:** KEEP  
**Size/Date:** 600 bytes, Nov 10 04:23  
**Reasoning:** Current bodyscan report.  
**Action:** Move to phpstan-reports/ directory

#### Archive Directory (EXISTS)

**File:** docs/archive/*  
**Category:** KEEP  
**Size/Date:** 260K total, 23 files  
**Reasoning:** Already properly archived historical documents.  
**Action:** Keep as-is. Good organization already exists.

---

## CONSOLIDATION OPPORTUNITIES

### 1. Quick Reference Guides → Single Development Guide
**Files to Merge:**
- docs/QUICK_REFERENCE.md (5.4K)
- docs/IMPLEMENTATION_QUICK_REFERENCE.md (5.9K)

**Reason:** Both documents provide development commands and feature implementation guidance. They overlap significantly in purpose and audience.

**Proposed Action:** Create `docs/guides/DEVELOPMENT_REFERENCE.md` combining:
- Module management commands
- Frontend development commands
- Feature implementation patterns
- Database commands
- Testing commands

**Estimated Size:** ~8K (deduplicated)

### 2. Phase 3 Test Documents → Archive as Set
**Files to Archive:**
- docs/PHASE_3_QUICK_START.md
- docs/PHASE_3_TEST_ENHANCEMENT_RECOMMENDATIONS.md
- docs/PHASE_3_ADDENDUM_ADDITIONAL_TESTS.md
- docs/PHASE_3_ADDENDUM_2_THIRD_REVIEW.md

**Reason:** Phase 3 work is complete (per TEST_EXPANSION_COMPLETED.md). These are historical planning documents.

**Proposed Action:** Move entire set to docs/archive/phase-3/ subdirectory

### 3. Test Analysis Documents → Consolidate/Archive
**Files:**
- docs/TEST_COVERAGE_ANALYSIS.md (41K) → Archive
- docs/TEST_COVERAGE_ANALYSIS_SUMMARY.md (14K) → Keep
- docs/TEST_EXPANSION_PROPOSAL.md (29K) → Archive
- docs/TEST_EXPANSION_COMPLETED.md (11K) → Keep

**Reason:** Proposals and detailed analyses are historical. Summaries and completion reports remain useful.

### 4. PHPStan Plans → Single Directory
**Files:**
- docs/PHPSTAN_IMPROVEMENT_PLAN.md
- docs/PHPSTAN_MAX_LEVEL_ROADMAP.md
- docs/PHPSTAN_PARALLEL_IMPLEMENTATION_PLAN.md
- docs/PHPSTAN_IGNORE_REDUCTION_PLAN.md

**Proposed Action:** Move all to docs/plans/phpstan/ subdirectory for better organization

---

## PROPOSED DIRECTORY STRUCTURE

```
docs/
├── PROGRESS.md                          (Main progress tracker)
├── SUCCESS_METRICS_STATUS.md            (Current metrics)
├── AGENT_PRIMER_PROMPT.md               (Onboarding)
├── TEST_EXPANSION_COMPLETED.md          (Completion summary)
│
├── guides/                              (Current operational guides)
│   ├── DEVELOPMENT_REFERENCE.md         (Consolidated from 2 files)
│   ├── FRONTEND_REFERENCE.md            (Renamed from FRONTEND_QUICK_REFERENCE.md)
│   ├── TESTING.md                       (Renamed from TEST_SUITE_DOCUMENTATION.md)
│   ├── DEPLOYMENT.md                    (Moved from root)
│   ├── SERVICES_SETUP.md                (Renamed from STARTUP_SERVICES_SETUP.md)
│   ├── DATABASE_MAINTENANCE.md          (Renamed from DATABASE_PARITY_MAINTENANCE.md)
│   └── TEST_COVERAGE_SUMMARY.md         (Moved from root)
│
├── plans/                               (Strategic plans & roadmaps)
│   ├── FRONTEND_MODERNIZATION.md        (Moved from root)
│   ├── FEATURE_PARITY.md                (Renamed from FEATURE_PARITY_ANALYSIS.md)
│   └── phpstan/                         (PHPStan-related plans)
│       ├── IMPROVEMENT_PLAN.md
│       ├── ROADMAP.md
│       ├── PARALLEL_IMPLEMENTATION.md
│       └── IGNORE_REDUCTION.md
│
├── phpstan-reports/                     (PHPStan analysis reports)
│   ├── bodyscan-log.txt
│   ├── bodyscan-results.json
│   ├── phpstan_level_*.json
│   └── BODYSCAN_REPORT.txt              (Moved from root)
│
└── archive/                             (Historical documents)
    ├── phase-2/
    │   └── SMOKE_TESTS_ANALYSIS.md
    ├── phase-3/
    │   ├── QUICK_START.md
    │   ├── RECOMMENDATIONS.md
    │   ├── ADDENDUM.md
    │   └── ADDENDUM_2.md
    ├── test-planning/
    │   ├── COVERAGE_ANALYSIS_DETAILED.md
    │   ├── EXPANSION_PROPOSAL.md
    │   └── IMPORT_SUMMARY.md
    ├── completions/
    │   ├── PHPSTAN_LEVEL_7_COMPLETED.md
    │   └── PR_19-24_VERIFICATION.md
    └── [existing 23 archived files...]

reports/
├── summary-report.txt                   (Current test run summary)
├── action-plan.md                       (Current action items)
├── artisan-test.log                     (Current test log)
├── dusk.log                             (Current browser test log)
├── frontend-test.log                    (Current frontend test log)
├── frontend-lint.log                    (Current frontend lint log)
├── lint-style.log                       (Current style lint log)
├── phpstan-analyse.log                  (Current PHPStan log)
├── security-audit.log                   (Current security log)
└── coverage-report/                     (Current HTML coverage report)
    └── [130 HTML/CSS/JS files]
```

---

## IMPLEMENTATION SCRIPT

```bash
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

# Append IMPLEMENTATION_QUICK_REFERENCE content
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
echo "Next steps:"
echo "  1. Review changes: git status"
echo "  2. Test that all references still work"
echo "  3. Update any links in README.md if needed"
echo "  4. Commit changes: git add . && git commit -m 'Clean up and organize documentation'"
echo ""
```

---

## SUMMARY OF CHANGES

### Files Affected
- **Reports**: 7 files deleted (redundant .exit files)
- **Docs**: 
  - 2 files consolidated into 1
  - 12 files moved to guides/
  - 6 files moved to plans/
  - 10 files moved to archive/
  - 5 files deleted (obsolete)

### Directory Changes
- Created: `docs/guides/`
- Created: `docs/plans/phpstan/`
- Created: `docs/archive/phase-2/`
- Created: `docs/archive/phase-3/`
- Created: `docs/archive/test-planning/`
- Created: `docs/archive/completions/`

### Net Result
- **Before**: 64 docs files scattered, 130 reports files with redundancy
- **After**: 38 active docs files organized, 123 reports files (no redundancy)
- **Improvement**: Better organization, clearer purpose, reduced clutter

---

## RATIONALE FOR DECISIONS

### Why Keep Current Test Reports?
The reports/ directory contains the most recent test run (Nov 10, 2025). All files are current and actively referenced by action-plan.md. The only redundancy is the .exit files which duplicate status information already in summary-report.txt.

### Why Consolidate Quick Reference Guides?
QUICK_REFERENCE.md and IMPLEMENTATION_QUICK_REFERENCE.md serve overlapping purposes for different audiences (developers vs implementers), but both need the same information. A single DEVELOPMENT_REFERENCE.md serves both better.

### Why Archive Phase 3 Documents?
TEST_EXPANSION_COMPLETED.md clearly indicates Phase 3 work is done (Nov 6, 2025). The planning and recommendation documents (PHASE_3_*.md) are valuable historical reference but not active working documents.

### Why Keep PROGRESS.md in Root?
PROGRESS.md is the master tracking document (42K, actively maintained, 97% completion status). It should remain highly visible in the docs/ root as the primary project status reference.

### Why Create Subdirectories?
- **guides/**: Operational "how-to" documents for daily development
- **plans/**: Strategic planning and roadmap documents  
- **archive/**: Historical documents for reference
- **phpstan-reports/**: Technical analysis reports

This structure matches common documentation patterns and makes it easier to find the right document for the task at hand.

---

## VALIDATION CHECKLIST

After running the cleanup script, verify:

- [ ] All referenced files in action-plan.md still exist
- [ ] PROGRESS.md links still work (update if needed)
- [ ] README.md documentation links updated (if any)
- [ ] No broken symlinks or references
- [ ] Git status shows expected file moves and deletions
- [ ] Directory structure matches proposal
- [ ] All active guides accessible in docs/guides/
- [ ] All strategic plans in docs/plans/
- [ ] Historical docs properly archived

---

## MAINTENANCE RECOMMENDATIONS

### Going Forward

1. **Session Notes**: Don't create permanent session summary files. Capture important information in PROGRESS.md or relevant guides.

2. **Test Reports**: Keep only the most recent test run in reports/. Archive old runs if needed for comparison.

3. **Planning Documents**: When a plan is completed, create a completion summary and archive the detailed planning docs.

4. **Quick References**: Maintain a single DEVELOPMENT_REFERENCE.md. Don't create multiple overlapping reference guides.

5. **Directory Structure**: 
   - `docs/` root: Only high-level tracking and onboarding docs
   - `docs/guides/`: Active operational documentation
   - `docs/plans/`: Active strategic planning
   - `docs/archive/`: Completed work, historical reference

6. **Naming Convention**:
   - Guides: `[TOPIC]_REFERENCE.md` or `[AREA]_GUIDE.md`
   - Plans: `[FEATURE]_PLAN.md` or `[AREA]_ROADMAP.md`
   - Status: `[TOPIC]_STATUS.md`
   - Completion: `[PHASE]_COMPLETED.md`

---

**Analysis Complete**  
**Ready for Review and Implementation**
