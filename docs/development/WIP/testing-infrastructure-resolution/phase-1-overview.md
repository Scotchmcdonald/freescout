# Phase 1 - Testing Infrastructure Resolution Plan

## Objective
Resolve the highest-risk weaknesses identified in the March 24, 2026 testing infrastructure audit while preserving the current strengths: 10-process parallel execution, framework-free unit tests by default, architecture guardrails, and zero PHPStan drift.

## Audit Snapshot
- Latest full-suite run: `php artisan test --parallel --processes=10`
- Result: `6218 passed, 1 failed, 3 skipped, 13650 assertions`
- Duration: `109.44s`
- Active failing guard: `tests/Unit/FeatureWriteAssertionDepthGuardTest.php`
- Current executive score: `72/100`
- Critical reliability gap: mutation tooling is configured but not exercised in CI or reporting.

## Scope
### In Scope
- Restore green test governance by eliminating the current shallow-write guard failure.
- Establish a repeatable mutation baseline and broaden it beyond the current 3-module scope.
- Increase assertion depth on write-oriented feature tests.
- Close high-value test coverage gaps in Actions, Policies, and request validation.
- Reduce hidden framework boot and skipped-test governance drift.
- Tighten type-safety governance in `Modules/`.

### Out of Scope
- Rewriting the entire suite for 100% coverage.
- Migrating every module to mutation testing in one pass.
- Frontend browser/E2E redesign.
- Any broad architectural refactor unrelated to test reliability.

## Success Criteria
- `php artisan test --parallel --processes=10` returns fully green with no ratchet failures.
- The 3 shallow write-endpoint tests contain meaningful side-effect assertions.
- Mutation testing produces a checked-in or archived baseline report under `reports/`.
- At least one CI entry point validates mutation health on a bounded scope.
- The highest-risk untested business-logic namespaces have explicit task owners and completed tests.
- `Modules/` strict-types coverage is measurably improved or a bounded exception list is documented.

## Execution Order
1. Complete Track A first enough to make the suite green again.
2. Run Tracks B, C, and D in parallel once the guard failure is understood.
3. Re-run the full suite, targeted mutation scope, and any lane-budget checks after each merged track.
4. Remove this WIP folder once all accepted tasks are completed and verified.

## Parallel Workstreams
- [ ] Track A: reliability and mutation baseline
  Context: [track-a-reliability-and-mutation.md](track-a-reliability-and-mutation.md)
- [x] Track B: feature boundary depth and write-assertion hardening
  Completed summary: [reports/testing-executive-audit/track-b-backlog.md](reports/testing-executive-audit/track-b-backlog.md)
- [x] Track C: architecture and type-safety ratchets
  Context: [track-c-architecture-and-type-safety.md](track-c-architecture-and-type-safety.md)
- [ ] Track D: velocity, lane governance, and isolation cleanup
  Context: [track-d-velocity-and-lane-governance.md](track-d-velocity-and-lane-governance.md)

## Shared Validation Commands
- [ ] Full suite: `php artisan test --parallel --processes=10`
- [ ] Static analysis: `bash scripts/ci/check-static-analysis.sh`
- [ ] Full CI: `bash scripts/ci.sh`
- [ ] Mutation baseline: `XDEBUG_MODE=coverage ./vendor/bin/infection --threads=8 --skip-initial-tests`

## Handoff Notes
- Use `php artisan test` as the canonical runner.
- The current failing guard is a test quality signal, not a product bug.
- Do not weaken ratchet tests to force green unless the acceptance criteria explicitly permit updating the baseline.
- Prefer adding side-effect assertions and focused coverage over increasing raw test count.
