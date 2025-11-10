# Documentation Guide

**Last Updated**: November 10, 2025  
**Repository**: Scotchmcdonald/freescout  
**Branch**: laravel-11-foundation

This directory contains all project documentation organized for easy navigation and maintenance.

---

## 📂 Directory Structure

### Root Level Documents (Start Here)

- **[PROGRESS.md](PROGRESS.md)** - Master progress tracker showing 97% completion
- **[SUCCESS_METRICS_STATUS.md](SUCCESS_METRICS_STATUS.md)** - Current metrics and KPIs
- **[AGENT_PRIMER_PROMPT.md](AGENT_PRIMER_PROMPT.md)** - Onboarding guide for new contributors
- **[TEST_EXPANSION_COMPLETED.md](TEST_EXPANSION_COMPLETED.md)** - Test expansion completion summary

### 📚 [guides/](guides/) - Operational Documentation

Daily development and operational guides:

- **[DEVELOPMENT_REFERENCE.md](guides/DEVELOPMENT_REFERENCE.md)** - Consolidated development commands (modules, testing, frontend)
- **[FRONTEND_REFERENCE.md](guides/FRONTEND_REFERENCE.md)** - Frontend-specific development guide
- **[TESTING.md](guides/TESTING.md)** - Comprehensive test suite documentation
- **[DEPLOYMENT.md](guides/DEPLOYMENT.md)** - Deployment procedures and instructions
- **[SERVICES_SETUP.md](guides/SERVICES_SETUP.md)** - Development environment service setup
- **[DATABASE_MAINTENANCE.md](guides/DATABASE_MAINTENANCE.md)** - Database maintenance procedures
- **[TEST_COVERAGE_SUMMARY.md](guides/TEST_COVERAGE_SUMMARY.md)** - Test coverage analysis executive summary

### 🗺️ [plans/](plans/) - Strategic Planning

Active strategic plans and roadmaps:

- **[FRONTEND_MODERNIZATION.md](plans/FRONTEND_MODERNIZATION.md)** - Frontend modernization strategy
- **[FEATURE_PARITY.md](plans/FEATURE_PARITY.md)** - Feature comparison analysis

#### [plans/phpstan/](plans/phpstan/) - PHPStan Plans

- **[IMPROVEMENT_PLAN.md](plans/phpstan/IMPROVEMENT_PLAN.md)** - PHPStan improvement strategy (24K)
- **[ROADMAP.md](plans/phpstan/ROADMAP.md)** - PHPStan level progression plan
- **[PARALLEL_IMPLEMENTATION.md](plans/phpstan/PARALLEL_IMPLEMENTATION.md)** - Parallel processing implementation
- **[IGNORE_REDUCTION.md](plans/phpstan/IGNORE_REDUCTION.md)** - Strategy for reducing PHPStan ignores

### 📊 [phpstan-reports/](phpstan-reports/) - Analysis Reports

PHPStan analysis results and reports:

- **BODYSCAN_REPORT.txt** - Latest bodyscan report
- **bodyscan-log.txt** - Detailed bodyscan log
- **bodyscan-results.json** - Machine-readable bodyscan results
- **phpstan_level_*.json** - Historical error reports by level

### 📦 [archive/](archive/) - Historical Documents

Completed work and historical reference documents organized by category:

#### [archive/phase-2/](archive/phase-2/)
- **SMOKE_TESTS_ANALYSIS.md** - Phase 2 smoke tests analysis (32K)

#### [archive/phase-3/](archive/phase-3/)
- **QUICK_START.md** - Phase 3 quick start guide
- **RECOMMENDATIONS.md** - Test enhancement recommendations (29K)
- **ADDENDUM.md** - Additional tests addendum (22K)
- **ADDENDUM_2.md** - Third review addendum (26K)

#### [archive/test-planning/](archive/test-planning/)
- **COVERAGE_ANALYSIS_DETAILED.md** - Detailed coverage analysis (41K)
- **EXPANSION_PROPOSAL.md** - Test expansion proposal (29K)
- **IMPORT_SUMMARY.md** - Test import summary

#### [archive/completions/](archive/completions/)
- **PHPSTAN_LEVEL_7_COMPLETED.md** - PHPStan level 7 completion report
- **PR_19-24_VERIFICATION.md** - Pull request verification document

#### [archive/](archive/) (Root)
23 additional historical documents including:
- Architecture documentation
- Migration guides
- Implementation roadmaps
- Email system documentation
- Session summaries

---

## 🎯 Quick Start Guide

### For Developers

1. **Getting Started**: Read [AGENT_PRIMER_PROMPT.md](AGENT_PRIMER_PROMPT.md)
2. **Development Commands**: See [guides/DEVELOPMENT_REFERENCE.md](guides/DEVELOPMENT_REFERENCE.md)
3. **Testing**: Check [guides/TESTING.md](guides/TESTING.md)
4. **Frontend Work**: Refer to [guides/FRONTEND_REFERENCE.md](guides/FRONTEND_REFERENCE.md)

### For Project Managers

1. **Progress Status**: Review [PROGRESS.md](PROGRESS.md)
2. **Success Metrics**: Check [SUCCESS_METRICS_STATUS.md](SUCCESS_METRICS_STATUS.md)
3. **Strategic Plans**: Browse [plans/](plans/)

### For DevOps

1. **Deployment**: Follow [guides/DEPLOYMENT.md](guides/DEPLOYMENT.md)
2. **Services Setup**: See [guides/SERVICES_SETUP.md](guides/SERVICES_SETUP.md)
3. **Database**: Refer to [guides/DATABASE_MAINTENANCE.md](guides/DATABASE_MAINTENANCE.md)

---

## 🔍 Finding Documentation

### By Topic

- **Module Development**: [guides/DEVELOPMENT_REFERENCE.md](guides/DEVELOPMENT_REFERENCE.md#module-management)
- **Frontend Development**: [guides/FRONTEND_REFERENCE.md](guides/FRONTEND_REFERENCE.md)
- **Testing**: [guides/TESTING.md](guides/TESTING.md)
- **PHPStan**: [plans/phpstan/](plans/phpstan/)
- **Deployment**: [guides/DEPLOYMENT.md](guides/DEPLOYMENT.md)
- **Historical Work**: [archive/](archive/)

### By Phase

- **Phase 2**: [archive/phase-2/](archive/phase-2/)
- **Phase 3**: [archive/phase-3/](archive/phase-3/)
- **Completed Work**: [archive/completions/](archive/completions/)

---

## 📝 Documentation Standards

### File Naming

- **Guides**: `[TOPIC]_REFERENCE.md` or `[AREA]_GUIDE.md`
- **Plans**: `[FEATURE]_PLAN.md` or `[AREA]_ROADMAP.md`
- **Status**: `[TOPIC]_STATUS.md`
- **Completion**: `[PHASE]_COMPLETED.md`

### Where to Put New Documentation

| Type | Location | Example |
|------|----------|---------|
| Daily operational guide | `docs/guides/` | Testing procedures |
| Strategic plan | `docs/plans/` | Modernization roadmap |
| Completion summary | Keep in root, then archive | PHASE_X_COMPLETED.md |
| Session notes | Don't create - use PROGRESS.md | N/A |
| Historical reference | `docs/archive/[category]/` | Completed phase docs |
| Analysis reports | `docs/phpstan-reports/` | PHPStan results |

### Maintenance Guidelines

1. **Don't Create Session Summaries**: Capture important information in PROGRESS.md or relevant guides
2. **Archive Completed Work**: When a plan is done, create a completion summary and move planning docs to archive
3. **Consolidate Similar Docs**: Avoid multiple overlapping guides on the same topic
4. **Keep Root Clean**: Only high-level tracking and onboarding docs in root
5. **Update PROGRESS.md**: This is the single source of truth for project status

---

## 📊 Statistics

- **Total Documentation**: ~1MB across 44 active files
- **Guides**: 7 operational guides (98K)
- **Plans**: 6 strategic plans (36K + phpstan/)
- **Archives**: 33 historical documents (276K)
- **Reports**: 8 analysis reports (12K)

---

## 🔄 Recent Changes

### November 10, 2025 - Major Reorganization

**Consolidated**:
- QUICK_REFERENCE.md + IMPLEMENTATION_QUICK_REFERENCE.md → guides/DEVELOPMENT_REFERENCE.md

**Moved to guides/**:
- 6 operational guides organized for daily use

**Moved to plans/**:
- 6 strategic plans including phpstan/ subdirectory

**Archived**:
- 10 completed phase documents
- Organized into phase-2/, phase-3/, test-planning/, and completions/ subdirectories

**Deleted**:
- 5 obsolete/superseded documents
- 7 redundant .exit files from reports/

**Result**: Cleaner structure, better organization, easier navigation

See [DOCUMENTATION_CLEANUP_ANALYSIS.md](../DOCUMENTATION_CLEANUP_ANALYSIS.md) for complete details.

---

## 🤝 Contributing

When adding new documentation:

1. **Choose the right location** based on the table above
2. **Follow naming conventions** for consistency
3. **Update this README** if adding new categories
4. **Link from PROGRESS.md** if it's important status info
5. **Archive completed work** instead of deleting it

---

## 📞 Questions?

- **Can't find something?** Check the [archive/](archive/) directory
- **Need help?** Read [AGENT_PRIMER_PROMPT.md](AGENT_PRIMER_PROMPT.md)
- **Want to see progress?** Check [PROGRESS.md](PROGRESS.md)

---

**Last cleanup**: November 10, 2025  
**Next review**: When adding significant new documentation
