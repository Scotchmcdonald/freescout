# Documentation Index

Welcome to the FreeScout Modernized documentation. This index helps you find the right documentation for your needs.

## 📖 Core Documentation

### [README.md](README.md)
Main project overview including:
- Project status and features
- Installation and setup instructions
- Docker deployment guide
- Development environment setup
- Requirements and dependencies

### [UX_STYLE_GUIDE.md](docs/product/UX_STYLE_GUIDE.md)
Interface design standards and patterns:
- "Pilot's Cockpit" philosophy
- Wizard patterns for multi-step processes
- Dashboard and control tower layouts
- Error handling and resilient design
- Tab navigation patterns
- Component guidelines

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
Concise overview of the current system architecture:
- Core principles (Core Blindness, Data Ownership)
- Module structure and responsibilities
- Data flow patterns
- Controller organization
- Cross-module integration patterns
- Key architectural decisions
- **Recommended first read for new developers (15 min)**

### [System Architecture](docs/architecture/SYSTEM_ARCHITECTURE.md)
Comprehensive design specification (7500+ lines):
- Complete architectural patterns
- Implementation details
- Event catalog
- Database schemas
- Case studies with code examples
- **Reference document for detailed design**

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

### [Module Communication Audit](docs/architecture/MODULE_COMMUNICATION_AUDIT.md)
Auto-generated audit of all verified module interactions:
- Connectivity Matrix (Events, Listeners, Relations)
- Data flow sequences for key workflows
- Architecture graph (Mermaid)
- **Verified against codebase Jan 2026**

### [UI Specification](docs/product/UI_SPECIFICATION.md)
Original master UI specification (archived for reference):
- Complete feature specifications for UI components
- Design philosophy and patterns
- **Reference only**

### [Modernization Guide](docs/development/MODULE_MODERNIZATION_GUIDE.md)
Updating legacy modules for Laravel 11:
- Namespace updates (App\Model → App\Models\Model)
- Route modernization to tuple syntax
- Migration updates for Laravel 11 conventions
- Controller inheritance
- Service provider cleanup

### [Development Scripts](scripts/README.md)
Utility scripts for development:
- PHPStan static analysis runner
- Test runner with filtering
- Code statistics generator

## 🧪 Testing

### [Test Runner Requirements](scripts/TEST_RUNNER_REQUIREMENTS.md)
Configuration and requirements for the test environment

### [Test Isolation System](docs/TEST_ISOLATION_SYSTEM.md)
Automatic detection of tests that hang or fail in parallel/batch runs:
- Custom PHPUnit attributes (#[NonParallel], #[NonBatched], #[Flaky])
- Runtime hang detection with timeouts
- ParaTest integration for parallel execution
- Three-phase test execution strategy

### [Quick Start: Multi-User Testing](docs/testing/QUICK_START_MULTI_USER_TESTING.md)
Guide for running and extending the End-to-End test suite:
- Running Quote Lifecycle, Sales-to-Cash, and Payment workflows
- Testing Admin ↔ Client Portal interactions
- Using the `MultiUserTestCase` base class
- Understanding test coverage and debug screenshots

## 🚧 Work in Progress & Roadmaps

### [UI Implemented](docs/WIP/UI_IMPLEMENTED.md)
Catalog of all implemented UI components:
- Infrastructure monitoring and Asset management
- Billing operations and CRM interfaces
- Client Portal and Premium features
- **Current State Snapshot**

### [UI Roadmap](docs/WIP/UI_ROADMAP.md)
Plan for remaining UI implementation:
- Premium features (Quote Architect)
- Scoped out features

### [Implementation Roadmap](docs/WIP/IMPLEMENTATION_ROADMAP.md)
Phase-by-phase execution plan:
- Current implementation status
- Module priorities and dependencies

## 📋 Templates

### [Pull Request Template](.github/PULL_REQUEST_TEMPLATE.md)
Standard template for code contributions

## Quick Navigation by Task

### "I'm new to the project"
1. **Start with [Architecture Overview](docs/architecture/ARCHITECTURE_OVERVIEW.md)** - Understand current system (15 min)
2. Review [README.md](README.md) for setup instructions
3. Check [Module Development Guide](docs/development/MODULE_DEVELOPMENT_GUIDE.md) for patterns

### "I want to understand the architecture"
1. **Read [Architecture Overview](docs/architecture/ARCHITECTURE_OVERVIEW.md)** for current state
2. Dive into [System Architecture](docs/architecture/SYSTEM_ARCHITECTURE.md) for complete design
3. Check [Implementation Roadmap](docs/WIP/IMPLEMENTATION_ROADMAP.md) for what's next

### "I want to build a new module"
1. Read [Architecture Overview](docs/architecture/ARCHITECTURE_OVERVIEW.md) for principles
2. Follow [Module Development Guide](docs/development/MODULE_DEVELOPMENT_GUIDE.md) patterns
3. Review [UX Style Guide](UX_STYLE_GUIDE.md) for UI patterns
4. Check [Module Installer README](MODULE_INSTALLER_README.md) for installation features

### "I need to update a legacy module"
1. Follow [Modernization Guide](scripts/MODERNIZATION_GUIDE.md)
2. Verify against [Module Development Guide](docs/development/MODULE_DEVELOPMENT_GUIDE.md)
3. Run tests using [Development Scripts](scripts/README.md)

### "I'm setting up a development environment"
1. Start with [README.md](README.md) setup instructions
2. Review [Development Scripts](scripts/README.md)
3. Check [Test Runner Requirements](scripts/TEST_RUNNER_REQUIREMENTS.md)

### "I need to extend a core model"
1. Review [ExtensibleModel Pattern](docs/development/MODULE_DEVELOPMENT_GUIDE.md#3-the-extensiblemodel-pattern)
2. Check [Dynamic Relationships](docs/development/MODULE_DEVELOPMENT_GUIDE.md#4-dynamic-relationships)
3. Follow [Migration Best Practices](docs/development/MODULE_DEVELOPMENT_GUIDE.md#5-migration-best-practices)

### "I'm designing a new interface"
1. Check [UI Implemented](docs/WIP/UI_IMPLEMENTED.md) for existing components
2. Review [UI Roadmap](docs/WIP/UI_ROADMAP.md) for planned features
3. Read [UX Style Guide](UX_STYLE_GUIDE.md) for design patterns
4. Apply the "Pilot's Cockpit" philosophy
5. Use established patterns (Wizards, Dashboards, Tabs)

## Documentation Structure

```
/var/www/html/
├── README.md                          # Main project overview
├── MODULE_INSTALLER_README.md         # Module installation system
├── UX_STYLE_GUIDE.md                  # Design standards
├── DOCUMENTATION_INDEX.md             # This file
├── docs/
│   ├── architecture/
│   │   ├── ARCHITECTURE_OVERVIEW.md       # Start here for new devs
│   │   ├── SYSTEM_ARCHITECTURE.md         # Complete design reference
│   │   └── MODULE_COMMUNICATION_AUDIT.md  # Verified Data Flow
│   ├── development/
│   │   └── MODULE_DEVELOPMENT_GUIDE.md    # Module patterns
│   ├── product/
│   │   ├── MSP_PRODUCT_DEFINITIONS.md
│   │   └── UI_SPECIFICATION.md
│   ├── testing/
│   │   ├── TEST_ISOLATION_SYSTEM.md       # Test categorization
│   │   └── QUICK_START_MULTI_USER_TESTING.md
│   └── WIP/                           # Roadmaps and implementation plans
│       ├── UI_IMPLEMENTED.md
│       ├── UI_ROADMAP.md
│       └── IMPLEMENTATION_ROADMAP.md
├── scripts/
│   ├── README.md                      # Development scripts
│   ├── MODERNIZATION_GUIDE.md         # Laravel 11 migration
│   └── TEST_RUNNER_REQUIREMENTS.md    # Test environment
└── Modules/*/README.md                # Per-module documentation
```

## Contributing

When contributing to this project:
1. Follow the patterns in [Module Development Guide](docs/MODULE_DEVELOPMENT_GUIDE.md)
2. Adhere to UI standards in [UX Style Guide](UX_STYLE_GUIDE.md)
3. Use the [Pull Request Template](.github/PULL_REQUEST_TEMPLATE.md)
4. Run tests before submitting (see [Development Scripts](scripts/README.md))

## Getting Help

- **Module Development**: See [Module Development Guide](docs/MODULE_DEVELOPMENT_GUIDE.md)
- **UI/UX Questions**: Check [UX Style Guide](UX_STYLE_GUIDE.md)
- **Installation Issues**: Review [Module Installer README](MODULE_INSTALLER_README.md)
- **Setup Problems**: Start with main [README.md](README.md)

---

**Last Updated:** January 17, 2026  
**Documentation Version:** 2.3
