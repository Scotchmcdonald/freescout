# Documentation Index

**Last Updated:** February 13, 2026  
**Documentation Status:** ✅ Synchronized with implementation (v4.7)

Welcome to the FreeScout Modernized documentation. This index helps you find the right documentation for your needs.

---

## 📊 Documentation Health & Status Tracking

**Recent Architecture Documentation Update (Feb 13, 2026):**
- ✅ **System Architecture v4.7**: Updated infrastructure (PHP 8.2+), unified deployment flows, and standardized migration commands.
- ✅ **Module Modernization**: Updated guide for Laravel 12 compatibility and strict typing standards.
- ✅ **Module Development**: Version bumped to 2.2 to reflect Laravel 12 / PHP 8.2+ environments.

**Recent Architecture Documentation Update (Feb 8, 2026):**
- ✅ Module statuses synchronized with actual codebase
- ✅ Service naming corrected (GoogleWorkspaceService, Action1Service, InvoiceGenerator)
- ✅ Implementation status indicators added (✅/⏳/⚠️/🐛)
- ✅ Planned features clearly distinguished from implemented features
- ✅ 26/26 ArchTest guards confirmed passing

**Documentation Best Practices:**
1. **Status Indicators**: Use ✅ (Implemented), ⏳ (Planned), ⚠️ (Partial), 🐛 (Bug/Gap)
2. **Regular Updates**: Review architecture docs quarterly or after major releases
3. **Accuracy First**: Document reality, not just aspirations
4. **Separate Plans**: Keep future plans clearly marked as ⏳ Planned
5. **Version Tracking**: Update version numbers and dates when making changes

---

## 📖 Core Documentation

### [README.md](README.md)
Main project overview including:
- Project status and features
- Installation and setup instructions
- Docker deployment guide
- Development environment setup
- Requirements and dependencies

### [UX_STYLE_GUIDE.md](docs/development/UX_STYLE_GUIDE.md)
Interface design standards and patterns:
- "Pilot's Cockpit" philosophy
- Wizard patterns for multi-step processes
- Dashboard and control tower layouts
- Error handling and resilient design
- Tab navigation patterns
- Component guidelines

### [Administrator Guide](docs/product/ADMIN_GUIDE.md)
Comprehensive guide for system administrators:
- Deployment options (Docker vs Manual)
- Initial application setup (Mail, Branding)
- Module management and installation
- User roles and management
- Basic troubleshooting

### [Initial Setup Guide](docs/product/INITIAL_SETUP.md)
Step-by-step setup instructions:
- Docker vs Manual steps
- Key configuration files
- Database and Mail setup
- **Contains Import Templates**

### [Module Installer System](docs/development/MODULE_INSTALLER_SYSTEM.md)
Comprehensive module installation system documentation:
- Security and authentication features
- Real-time progress tracking with SSE
- Module preview and dependency analysis
- Installation workflow and architecture
- Error handling and recovery
- API reference

## 🏗️ Architecture & Development Guides

### [Architecture Overview](docs/architecture/ARCHITECTURE_OVERVIEW.md) ⭐ **START HERE**
**Updated:** February 10, 2026 | **Status:** ✅ Synchronized with codebase

Concise overview of the current system architecture:
- Core principles (Core Blindness, Data Ownership, Queue Isolation)
- Module structure and responsibilities (✅ All 14 modules documented)
- Data flow patterns
- Controller organization
- Cross-module integration patterns
- Key architectural decisions
- **Implementation status tracking** (✅ Implemented vs ⏳ Planned)
- **Recommended first read for new developers (15 min)**

### [System Architecture](docs/architecture/SYSTEM_ARCHITECTURE.md)
**Version:** 4.7 | **Updated:** February 13, 2026 | **Status:** Production-Ready Design Document

Comprehensive design specification (12,000+ lines):
- **NEW (v4.5):** Performance & Scalability Architecture (Section 14)
- **NEW (v4.5):** Observability & Monitoring (Section 15)
- **NEW (v4.5):** Transaction Management Guidelines (Section 16)
- **NEW (v4.5):** Disaster Recovery & Business Continuity (Section 17)
- **NEW (v4.5):** API Standards & Versioning (Section 18)
- Implementation status tracking (✅/⏳/⚠️/🐛 indicators)
- Complete architectural patterns with production-ready code
- Event catalog (62 events, 28 listeners documented)
- Database schemas and optimization patterns
- Case studies with implementation examples
- **Complete technical reference with executable patterns**

### [Architectural Audit Report](docs/architecture/ARCHITECTURAL_AUDIT_REPORT.md)
**Date:** February 13, 2026 | **Status:** ⚠️ Non-Compliant (1 Critical Violation)

Current architectural compliance audit:
- Cross-module dependency violations with specific file locations
- `GlobalSearchController` pattern recommendations
- Remediation steps with verification commands
- **See also (archived):** `docs/ARCHIVE/ARCHITECTURAL_BEST_PRACTICES_REVIEW_2026-02-08.md`

### [API Versioning Strategy](docs/architecture/API_VERSIONING.md) 📋 **NEW**
**Created:** February 8, 2026 | **Status:** Documented

Comprehensive API versioning approach:
- **Header-Based Versioning:** Recommended approach with Accept headers
- **URL-Based Alternative:** For public APIs with many integrators
- **Version Compatibility Policy:** 12-month support window
- **Breaking Change Guidelines:** Clear definitions and examples
- **Implementation Strategy:** 5-week phased rollout plan
- **Resource Transformers:** Version-specific response formatting
- **Testing Strategy:** Full test coverage for all versions
- **OpenAPI Documentation:** Separate specs per version
- **Monitoring:** Version usage tracking and migration reports
- **Complete with:**
  - Step-by-step implementation procedures
  - Testing and verification protocols
  - Deployment procedures and rollback plans
  - Troubleshooting guides
  - Success metrics and monitoring
  - 4-week timeline with task assignments
- **Executable implementation guide - copy-paste ready**

### [MSP Product Definitions](docs/product/MSP_PRODUCT_DEFINITIONS.md)
Domain definitions for Managed Service Plans:
- Service plan tiers (Silver, Gold, Platinum)
- Billing logic and rate calculations
- Entitlement definitions per plan
- **Domain logic reference**

### [Module Development Guide](docs/development/MODULE_DEVELOPMENT_GUIDE.md)
Complete guide for building modules:
- Module structure and `module.json` configuration
- Cross-module interactions and dependencies
- Controller organization patterns
- ExtensibleModel pattern for extending core models
- Dynamic relationships
- Migration best practices
- Testing and compliance

### [Development Scripts](scripts/README.md)
Utility scripts for development:
- PHPStan static analysis runner
- Test runner with filtering
- Code statistics generator

## 📦 Module Documentation

### Case Manager (AI Decision Engine)

- **[Module Overview](docs/modules/CASE_MANAGER.md)** — Feature summary, pipeline stages, strategy descriptions, configuration reference
- **[Executive Technical Overview](docs/modules/CASE_MANAGER_EXECUTIVE.md)** — Safety design, cost controls, decision flows, benefits to Clients and Technicians
- **[Architecture Reference](docs/modules/CASE_MANAGER_ARCHITECTURE.md)** — Service architecture, strategy pattern, DTOs, event flow, resilience layer, state machine, database schema
- **[Implementation Critique](docs/modules/CASE_MANAGER_CRITIQUE.md)** — Honest assessment of gaps, incomplete features, and prioritized recommendations

### Knowledge Base

- **[Module Overview](docs/modules/KNOWLEDGE_BASE.md)** — KB article management, semantic search, AI integration

## 🧪 Testing

### [Browser Testing Guide](docs/testing/BROWSER_TESTING_GUIDE.md)
**Stack:** Pest + `pest-plugin-browser` (Playwright-backed)

Browser test setup and patterns:
- Running browser tests via Pest
- Writing tests with Playwright automation via the PHP bridge
- Test location: `tests/Browser/`

### [Migration to Native Playwright](docs/testing/MIGRATION_TO_NATIVE_PLAYWRIGHT.md)
**Status:** Decision Document — For Future Reference

Trade-off analysis for adopting a fully native TypeScript Playwright setup:
- Current vs. proposed stack comparison
- Data seeding challenge with native approach
- Migration decision criteria

### [Manual Testing Plan v1](docs/testing/manual/MANUAL_TESTING_PLAN_v1.md)
**Date:** January 19, 2026

Manual entry pathway test plan (GoogleAdmin/Action1 integrations disabled):
- Core system functionality coverage
- Estimated time: 2–3 hours

## 🚧 Work in Progress

### [CRM User Import / Google Sync](docs/development/WIP/CRM%20user%20import.md)
**Status:** ✅ Implemented & Verified | **Date:** February 15, 2026

Google Admin user sync with conflict resolution:
- Bi-directional sync between Google Workspace and CRM contacts
- Staging record workflow for conflict resolution
- All tests passing

### [Asset Lifecycle](docs/development/WIP/asset_lifecycle.md)
**Status:** 🚧 In Progress

Unified workflow for Asset Management lifecycle events:
- All asset changes flow through `AssetStagingRecord`
- Duplicate prevention and billing accuracy goals
- Integration with GoogleAdmin and Action1 sync

### [Guided Tour Feature v2.0](docs/development/WIP/guided_tour_feature.md)
**Status:** Finalized Draft | **Date:** February 14, 2026

Onboarding guided tour using Driver.js + Alpine.js:
- Step-by-step user onboarding flow
- Framework: Laravel 12.x + Alpine.js + Driver.js

### [Staging Resolution Plan](docs/development/WIP/staging_resolution_plan.md)
**Status:** Planning

Consolidation of conflict resolution UIs for Assets and Users:
- Dedicated "Staging Inbox" operational views
- Replaces embedded conflict lists in integration settings pages

### [Event & Listener Robustness](docs/development/WIP/event_robustness.md)
**Status:** ✅ Implemented | **Date:** March 4, 2026

App-wide strategy for making queued event listeners resilient:
- `ResilientListener` trait: retry config + `failed()` + alert dispatch for all queued listeners
- `AiPipelineFailureHandler`: CaseManager-specific error recovery (state transitions, internal notes, delayed checks)
- Full listener inventory with resilience coverage matrix
- Bugs fixed: Fern exception swallowing, missing state guards, silent listener failures

### [User & Company Lifecycle](docs/development/WIP/user_company_lifecycle.md)
**Status:** 🚧 In Progress

Workflow standards for managing Users and Companies:
- Internal vs. external client user paths
- Domain deduplication on company ingestion
- ClientUser creation lifecycle

## 🤖 AI Development Prompts

Standardized prompts for common development tasks (copy into your LLM):

- **[Module Generation](docs/development/Prompts/PROMPT_MODULE_GENERATION.md)** — Generate new system modules to architectural standards
- **[Architecture Compliance Fixer](docs/development/Prompts/PROMPT_ARCHITECTURE_FIX.md)** — Identify and fix cross-module violations
- **[PHPStan Fixer](docs/development/Prompts/PROMPT_PHPSTAN_FIX.md)** — Resolve strict-mode type errors
- **[Migration Refactor](docs/development/Prompts/PROMPT_MIGRATION_REFACTOR.md)** — Consolidate/standardize migrations
- **[UI/UX Review](docs/development/Prompts/PROMPTS_UI_UX_REVIEW.md)** — Review or generate UI to design-system standards
- **[Pre-Deployment Readiness](docs/development/Prompts/PROMPT_PRE_DEPLOYMENT_READINESS.md)** — Release verification checklist
- **[Test Runner](docs/development/Prompts/PROMPT_TEST_RUNNER.md)** — Run and triage Pest test failures

## 📋 Templates

### [Pull Request Template](.github/PULL_REQUEST_TEMPLATE.md)
Standard template for code contributions.

## Quick Navigation by Task

### "I'm new to the project"
1. **Start with [Architecture Overview](docs/architecture/ARCHITECTURE_OVERVIEW.md)** — Understand current system (15 min)
2. Review [README.md](README.md) for setup instructions
3. Check [Developer Getting Started](docs/architecture/DEVELOPER_GETTING_STARTED.md) for environment setup
4. Check [Module Development Guide](docs/development/MODULE_DEVELOPMENT_GUIDE.md) for patterns

### "I want to understand the architecture"
1. **Read [Architecture Overview](docs/architecture/ARCHITECTURE_OVERVIEW.md)** for current state
2. Dive into [System Architecture](docs/architecture/SYSTEM_ARCHITECTURE.md) for complete design
3. See [Architecture README](docs/architecture/README.md) for document navigation

### "I want to build a new module"
1. Read [Architecture Overview](docs/architecture/ARCHITECTURE_OVERVIEW.md) for principles
2. Follow [Module Development Guide](docs/development/MODULE_DEVELOPMENT_GUIDE.md) patterns
3. Review [UX Style Guide](docs/development/UX_STYLE_GUIDE.md) for UI patterns
4. See [Module Installer System](docs/development/MODULE_INSTALLER_SYSTEM.md) for installation features

### "I need to update a legacy module"
1. Reference [Module Development Guide](docs/development/MODULE_DEVELOPMENT_GUIDE.md) Appendix A for Laravel 12 migration patterns
2. Run `php artisan module:migrate --all --force` for migration updates

### "I'm setting up a development environment"
1. Start with [README.md](README.md) setup instructions
2. Follow [Developer Getting Started](docs/architecture/DEVELOPER_GETTING_STARTED.md)
3. Review [Development Scripts](scripts/README.md)

### "I need to extend a core model"
1. Review [ExtensibleModel Pattern](docs/development/MODULE_DEVELOPMENT_GUIDE.md#3-the-extensiblemodel-pattern)
2. Check [Dynamic Relationships](docs/development/MODULE_DEVELOPMENT_GUIDE.md#4-dynamic-relationships)
3. Follow [Migration Best Practices](docs/development/MODULE_DEVELOPMENT_GUIDE.md#5-migration-best-practices)

### "I'm designing a new interface"
1. Read [UX Style Guide](docs/development/UX_STYLE_GUIDE.md) for design patterns
2. Apply the "Pilot's Cockpit" philosophy
3. Use established patterns (Wizards, Dashboards, Tabs)

## Documentation Structure

```
/var/www/html/
├── README.md                              # Main project overview
├── GETTING_STARTED.md                     # Quick start walkthrough
├── DOCUMENTATION_INDEX.md                 # This file
└── docs/
    ├── architecture/
    │   ├── README.md                      # Architecture doc navigation
    │   ├── ARCHITECTURE_OVERVIEW.md       # ⭐ Start here for new devs
    │   ├── SYSTEM_ARCHITECTURE.md         # Complete design reference (v4.7)
    │   ├── ARCHITECTURAL_AUDIT_REPORT.md  # Compliance audit (Feb 2026)
    │   ├── API_VERSIONING.md              # API versioning strategy
    │   ├── APP_OVERVIEW.md                # Application overview for architects
    │   ├── DEVELOPER_GETTING_STARTED.md   # Onboarding guide for new engineers
    │   ├── MODULAR_SYSTEM_QA.md           # Modular architecture Q&A
    │   └── MODULE_REPO_MANAGEMENT.md      # Repository management guide
    ├── development/
    │   ├── MODULE_DEVELOPMENT_GUIDE.md    # Module patterns & best practices
    │   ├── MODULE_INSTALLER_SYSTEM.md     # Module installation system
    │   ├── UX_STYLE_GUIDE.md              # Design standards
    │   ├── WEBHOOK_SETUP.md               # Webhook configuration
    │   ├── integrations-settings-feature.md # Integration settings pattern
    │   ├── Prompts/                       # AI development prompts (7 files)
    │   └── WIP/                           # In-progress design documents
    ├── product/
    │   ├── ADMIN_GUIDE.md                 # Administrator guide
    │   ├── INITIAL_SETUP.md               # Initial setup walkthrough
    │   └── MSP_PRODUCT_DEFINITIONS.md     # Billing products and plans
    ├── testing/
    │   ├── BROWSER_TESTING_GUIDE.md       # Pest + Playwright testing
    │   ├── MIGRATION_TO_NATIVE_PLAYWRIGHT.md # Migration decision guide
    │   └── manual/MANUAL_TESTING_PLAN_v1.md  # Manual test plan
    ├── modules/
    │   └── KNOWLEDGE_BASE.md              # Knowledge base spec (⚠️ pre-alpha)
    ├── reports/                           # Point-in-time implementation reports
    └── ARCHIVE/                           # Completed assessments & old plans
├── scripts/
│   └── README.md                          # Development scripts
└── Modules/*/README.md                    # Per-module documentation
```

## Contributing

When contributing to this project:
1. Follow the patterns in [Module Development Guide](docs/development/MODULE_DEVELOPMENT_GUIDE.md)
2. Adhere to UI standards in [UX Style Guide](docs/development/UX_STYLE_GUIDE.md)
3. Use the [Pull Request Template](.github/PULL_REQUEST_TEMPLATE.md)
4. Run tests before submitting: `php artisan test`

## Getting Help

- **Module Development**: See [Module Development Guide](docs/development/MODULE_DEVELOPMENT_GUIDE.md)
- **UI/UX Questions**: Check [UX Style Guide](docs/development/UX_STYLE_GUIDE.md)
- **Installation Issues**: Review [Module Installer System](docs/development/MODULE_INSTALLER_SYSTEM.md)
- **Setup Problems**: Start with main [README.md](README.md)

---

**Last Updated:** March 4, 2026  
**Documentation Version:** 2.6
