# Architecture Documentation Index

**Last Updated:** March 24, 2026

This directory contains the comprehensive architecture documentation for the MSP Management Platform. Documents have been recently reviewed and consolidated to remove redundancy and outdated content.

---

## 📚 Core Documentation (Start Here)

### 1. [APP_OVERVIEW.md](APP_OVERVIEW.md)
**Purpose:** Canonical component inventory — what lives where and why
**Audience:** All developers (first stop when asking "where does X live?")
**When to Read:** Before touching any module boundary or looking for a class
**Last Updated:** March 2, 2026
**Length:** ~400 lines

Covers:
- Tenancy model and RBAC roles
- Per-module component tables (models, services, events, contracts)
- Architectural conventions: Core Blindness, `resolveRelationUsing`, cross-module patterns

### 2. [ARCHITECTURE_OVERVIEW.md](ARCHITECTURE_OVERVIEW.md)
**Purpose:** Concise overview of current implemented architecture
**Audience:** New developers, technical leadership
**When to Read:** Second document for onboarding (after APP_OVERVIEW.md)
**Last Updated:** March 2, 2026
**Length:** ~1000 lines

Quick introduction to:
- Core architectural principles (Core Blindness, Event-Driven)
- Module structure and responsibilities
- Data flow patterns
- Controller organization

### 3. [SYSTEM_ARCHITECTURE.md](SYSTEM_ARCHITECTURE.md)
**Purpose:** Authoritative design specification and reference
**Audience:** All developers, architects
**When to Read:** Deep dives, planning new features
**Last Updated:** March 3, 2026 (v4.9)
**Length:** ~12,000 lines

### 4. [MODULAR_SYSTEM_QA.md](MODULAR_SYSTEM_QA.md)
**Purpose:** Q&A reference for onboarding architects
**Audience:** Incoming architects, senior engineers
**When to Read:** Alongside ARCHITECTURE_OVERVIEW.md for onboarding
**Last Updated:** March 2, 2026
**Length:** ~350 lines

Answers:
- How modularity is enforced (nwidart, PSR-4, `module.json`)
- Inter-module communication patterns (events, `class_exists` guards)
- Testing, scaffolding, seeding, deployment

---

## 🎯 Technical Specifications

### 5. [IDENTITY_AND_ACCESS_MODEL.md](IDENTITY_AND_ACCESS_MODEL.md)
**Purpose:** Identity terminology, RBAC model, and access-boundary conventions
**Status:** Active reference

---

## 📐 Compliance & Operations

### 6. [EVENT_LISTENER_RESILIENCE.md](EVENT_LISTENER_RESILIENCE.md)
**Purpose:** App-wide queued listener resilience and failure-handling strategy
**Status:** ✅ Implemented

### 7. [DEVELOPER_GETTING_STARTED.md](DEVELOPER_GETTING_STARTED.md)
**Purpose:** Practical day-one setup guide for new contributors
**Audience:** New developers
**When to Read:** Before writing your first line of code

### 8. [MODULE_REPO_MANAGEMENT.md](MODULE_REPO_MANAGEMENT.md)
**Purpose:** Git workflow for managing module repos independently
**Audience:** Engineers who own or maintain module packages

---

## 📦 Archived Documents

Completed assessments and action plans have been moved to `docs/ARCHIVE/`:

- **QUICK_WINS_ACTION_PLAN_COMPLETED_2026-02-09.md** - Most items completed as of Feb 9, 2026
- **ARCHITECTURAL_BEST_PRACTICES_REVIEW_2026-02-08.md** - Point-in-time assessment (recommendations implemented)

---

## 🗺️ Document Navigation Guide

**I'm new to the project:**
1. Start with [APP_OVERVIEW.md](APP_OVERVIEW.md) (10 min — what lives where)
2. Read [ARCHITECTURE_OVERVIEW.md](ARCHITECTURE_OVERVIEW.md) (15-20 min read)
3. Review [MODULAR_SYSTEM_QA.md](MODULAR_SYSTEM_QA.md) for compliance and boundary Q&A
4. Refer to [SYSTEM_ARCHITECTURE.md](SYSTEM_ARCHITECTURE.md) for specific details

**I'm implementing a new feature:**
1. Check [SYSTEM_ARCHITECTURE.md](SYSTEM_ARCHITECTURE.md) for patterns and constraints
2. Follow [MODULE_DEVELOPMENT_GUIDE.md](../development/MODULE_DEVELOPMENT_GUIDE.md) for implementation patterns

**I'm building an API:**
1. Review [APP_OVERVIEW.md](APP_OVERVIEW.md) for API surface inventory and module boundaries
2. Check [SYSTEM_ARCHITECTURE.md](SYSTEM_ARCHITECTURE.md) Section 18 for authentication

**I'm debugging production issues:**
1. Review [SYSTEM_ARCHITECTURE.md](SYSTEM_ARCHITECTURE.md) Section 17 for disaster recovery
2. Check application logs via `storage/logs/laravel.log`

---

## 📊 Documentation Maintenance

**Maintenance Schedule:**
- **Quarterly Review:** Update implementation statuses, archive completed work
- **After Major Features:** Update SYSTEM_ARCHITECTURE.md version, update ARCHITECTURE_OVERVIEW.md
- **After Audits:** Archive assessment documents, update implementation guides

**Documentation Standards:**
- Use status indicators: ✅ (Implemented), ⏳ (Planned), ⚠️ (Partial), 🐛 (Bug/Gap)
- Update "Last Updated" dates when making changes
- Cross-reference related documents
- Keep ARCHITECTURE_OVERVIEW.md concise (under 1500 lines)
- Archive point-in-time assessments after recommendations are addressed

**Document Owners:**
- SYSTEM_ARCHITECTURE.md: Architecture Team
- ARCHITECTURE_OVERVIEW.md: Architecture Team
- Feature Specs: Product + Engineering
- Technical Specs: Engineering Team

---

## 🔗 Related Documentation

**Product Documentation:** `docs/product/`
**Development Guides:** `docs/development/`
**Testing Documentation:** `docs/testing/`
**Deployment Guides:** `deployment/`

---

**Questions?** Contact the Architecture Team or open a discussion in the team channel.
