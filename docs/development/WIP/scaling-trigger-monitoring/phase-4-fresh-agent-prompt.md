# Fresh Agent Implementation Prompt

Use this prompt with a fresh coding agent.

---

You are implementing a new infrastructure module: `Modules/AppHealth`.

## Mission
Build `Modules/AppHealth` as the canonical owner for:
- health endpoints
- metrics instrumentation and exposition
- scaling trigger evaluation
- operator scorecard output

Do this incrementally with tests and secure defaults.

## Mandatory References (Read First)
1. `docs/development/UX_STYLE_GUIDE.md`
2. `.github/copilot-instructions.md`
3. `SCALING_PLAYBOOK.md`
4. `docs/development/WIP/scaling-trigger-monitoring/phase-2-implementation.md`
5. `docs/development/WIP/scaling-trigger-monitoring/phase-3-execution-plan.md`

## Critical Constraints
- Follow module architecture conventions already used in this repo.
- Docker deploy already uses Redis queues; do not re-plan queue backend migration for Docker.
- Keep cross-cutting observability code in `Modules/AppHealth`, not scattered in unrelated domains.
- Secure internal endpoints; do not expose raw metrics publicly.
- Any UI pages must follow `docs/development/UX_STYLE_GUIDE.md` exactly.

## Implementation Steps
1. Create `Modules/AppHealth` skeleton (providers, routes, config, contracts, services, tests).
2. Implement endpoints:
- `GET /internal/health`
- `GET /internal/health/detailed`
- `GET /internal/metrics`
- `GET /internal/scaling/scorecard`
3. Add contracts and services:
- `HealthCheckContract`
- `MetricRecorderContract`
- `TriggerEvaluatorContract`
4. Add compatibility adapter around existing `app/Services/MetricsService.php`.
5. Implement trigger evaluator job and persistence model/migration for daily scorecards.
6. Add scheduler wiring for periodic trigger evaluation.
7. Add test coverage:
- endpoint auth/security
- health responses
- trigger threshold evaluation logic
- scorecard persistence
8. Add docs/artifacts:
- metric catalog
- alert rule examples
- dashboard JSON/YAML templates

## Trigger Logic to Implement First (Stage A)
- API p95 latency threshold checks.
- Queue wait p95 threshold checks.
- Failed job ratio threshold checks.
- Worker CPU cumulative breach checks.
- DB CPU cumulative breach checks.

## Deliverables
- Working `Modules/AppHealth` module with tests passing.
- Updated docs reflecting implemented endpoints, metric names, and alert rules.
- Clear migration notes for existing metrics logging use.
- Summary of decisions, trade-offs, and follow-up backlog.

## Quality Bar
- Use strongly typed PHP code, clean boundaries, and clear naming.
- Favor small composable services over large controller logic.
- Add only necessary comments.
- Keep public interfaces stable and explicit.

## Completion Checklist
- [ ] Module compiles and routes register correctly.
- [ ] Endpoints secured and tested.
- [ ] Trigger evaluator outputs deterministic scorecards.
- [ ] UI pages (if present) comply with UX style guide.
- [ ] Docs updated with exact run/verify steps.

When complete, provide:
1. file-by-file change summary
2. test results
3. known gaps and next priorities

---

Optional first command sequence (if useful):
- inspect existing module structures under `Modules/*`
- scaffold `Modules/AppHealth`
- wire provider + routes
- add tests before full implementation
