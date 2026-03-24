# Testing Remediation Plan (Phased)

Date: 2026-03-23
Owner: QA + Platform + Module Maintainers
Status: In Progress (Phases 1-2 complete; Phase 3 waves 1-3 implemented, closeout pending)

## Objective

Completely eliminate the weaknesses identified in the suite audit:

1. Unit tests are not truly unit-isolated (Laravel app + DB boot in Unit scope).
2. Feature tests over-index on HTTP 200 checks without enough side-effect assertions.
3. Modern architecture constraints and type-boundary coverage are incomplete.
4. Test suite speed and reliability are below desired long-term targets.

## Phase Files

1. [phase-1-baseline-and-guardrails.md](phase-1-baseline-and-guardrails.md)
2. [phase-2-unit-purification.md](phase-2-unit-purification.md)
3. [phase-3-feature-meaningfulness.md](phase-3-feature-meaningfulness.md)
4. [phase-4-architecture-and-type-coverage.md](phase-4-architecture-and-type-coverage.md)
5. [phase-5-ci-speed-and-reliability.md](phase-5-ci-speed-and-reliability.md)
6. [phase-6-hardening-and-closeout.md](phase-6-hardening-and-closeout.md)

## Success Definition

The remediation is complete only when all conditions are true:

- Unit layer is mostly pure (no framework boot in default Unit scope).
- Unit/Feature/Integration/Browser distribution follows target pyramid bands.
- Feature tests enforce meaningful assertions for write operations and critical flows.
- Architecture guards include strict dependency boundaries for critical namespaces.
- CI default lanes are fast, stable, and have explicit regression budgets.
- Documentation and contributor checks prevent relapse.

## Global KPI Targets

- Unit isolation:
  - >= 70 percent of tests in tests/Unit run on PureUnitTestCase.
  - 0 new Unit tests extending framework booting base classes unless approved.
- Feature assertion quality:
  - >= 85 percent of write-endpoint Feature tests include side-effect assertions.
  - Status-only write tests reduced to <= 10 percent.
- Pyramid balance (file-level as practical proxy):
  - Unit: 55 to 65 percent
  - Feature: 20 to 30 percent
  - Integration: 10 to 20 percent
  - Browser: <= 10 percent
- CI runtime (default PR lanes):
  - Unit <= 30s
  - Feature <= 90s
  - Integration <= 90s
  - Guard lanes <= 30s
- Reliability:
  - 10 consecutive green runs on PR lanes.
  - Flake rate < 1 percent over trailing 14 days.

## Governance

- Each phase requires: scope, implementation PR(s), metric snapshot, and sign-off.
- Do not move to the next phase without satisfying previous phase exit criteria.
- If a phase blocks delivery work, use temporary allowlists with expiry dates and owner.
