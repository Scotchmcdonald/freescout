# Phase 3 - AppHealth Module Execution Plan

## Objective
Implement a production-ready `Modules/AppHealth` module that owns platform health checks, metrics collection/exposition, scaling trigger evaluation, and scorecard reporting.

## Success Criteria
- `Modules/AppHealth` is the canonical owner for health and monitoring contracts.
- Trigger thresholds in `SCALING_PLAYBOOK.md` are machine-evaluated and visible in dashboards.
- Alerts are configured and routed for warning/critical states.
- Module has automated tests and can run safely in Docker deployments that already use Redis queues.

## Scope
### In Scope
- Health check endpoints and dependency probes.
- Metric recording contracts and adapters.
- Internal metrics endpoint for Prometheus scraping.
- Trigger evaluator jobs and persisted scorecard snapshots.
- Basic operator UI pages for status/scorecard.
- Alert rule definitions and dashboard manifests (JSON/YAML artifacts).

### Out of Scope
- Replacing business-domain metrics in all modules in one pass.
- Full Kafka implementation.
- Full SRE platform provisioning outside app repo (provide artifacts/instructions, not infra ownership transfer).

## Required References
- UI/UX guide: `docs/development/UX_STYLE_GUIDE.md`.
- Agent/repo instructions: `.github/copilot-instructions.md`.
- Scaling strategy: `SCALING_PLAYBOOK.md`.
- Existing WIP technical monitoring plan: `docs/development/WIP/scaling-trigger-monitoring/phase-2-implementation.md`.

## Architecture
### Module Structure
- `Modules/AppHealth/Providers/AppHealthServiceProvider.php`
- `Modules/AppHealth/Routes/web.php`
- `Modules/AppHealth/Routes/api.php`
- `Modules/AppHealth/Http/Controllers/HealthController.php`
- `Modules/AppHealth/Http/Controllers/MetricsController.php`
- `Modules/AppHealth/Http/Controllers/ScalingScorecardController.php`
- `Modules/AppHealth/Contracts/HealthCheckContract.php`
- `Modules/AppHealth/Contracts/MetricRecorderContract.php`
- `Modules/AppHealth/Contracts/TriggerEvaluatorContract.php`
- `Modules/AppHealth/Services/HealthCheckService.php`
- `Modules/AppHealth/Services/MetricRecorderService.php`
- `Modules/AppHealth/Services/TriggerEvaluationService.php`
- `Modules/AppHealth/Jobs/EvaluateScalingTriggersJob.php`
- `Modules/AppHealth/Models/ScalingScorecardSnapshot.php`
- `Modules/AppHealth/Database/Migrations/*create_scaling_scorecard_snapshots_table.php`
- `Modules/AppHealth/Tests/Feature/*`
- `Modules/AppHealth/Tests/Unit/*`

### Endpoint Contract
- `GET /internal/health`
- `GET /internal/health/detailed`
- `GET /internal/metrics`
- `GET /internal/scaling/scorecard`

Access rules:
- `/internal/metrics`: internal network and/or auth token middleware.
- `/internal/scaling/scorecard`: authenticated admin/operator permissions.

## Implementation Work Breakdown

### Track A - Bootstrap and wiring
1. Create module skeleton and provider registration.
2. Register routes and middleware guards.
3. Add config file: `Modules/AppHealth/config/config.php`.
4. Add feature flags:
- `APPHEALTH_ENABLED`
- `APPHEALTH_METRICS_ENABLED`
- `APPHEALTH_TRIGGER_EVALUATION_ENABLED`

### Track B - Health checks
1. Implement basic check (`status=ok` if DB reachable).
2. Implement detailed checks:
- DB latency
- Redis latency
- Queue backlog signal
- Storage utilization
- Optional external API status (non-blocking)
3. Return normalized schema with status, duration, and details.

### Track C - Metrics and exposition
1. Implement `MetricRecorderContract` API for counters/histograms/timers.
2. Build adapter over existing `app/Services/MetricsService.php` for compatibility.
3. Add metrics endpoint with safe exposure policy.
4. Add route-group HTTP latency instrumentation.

### Track D - Trigger evaluation and scorecards
1. Implement evaluator for Stage A trigger logic:
- API p95 latency
- Queue wait p95
- Failed job ratio
- Worker CPU cumulative breach
- DB CPU cumulative breach
2. Persist daily snapshot model for trend analysis.
3. Add weekly scorecard endpoint with current state and recommendation.
4. Schedule evaluation job via scheduler.

### Track E - Operator UI
1. Build internal status pages in module views.
2. Follow `docs/development/UX_STYLE_GUIDE.md` exactly.
3. Include accessible tables/cards for trigger states and trend deltas.
4. Add clear warning/critical visual semantics tied to trigger status.

### Track F - Dashboards and alerts artifacts
1. Add dashboard JSON templates under module docs/artifacts.
2. Add alert-rules YAML templates (Prometheus/Alertmanager format).
3. Document metric names, labels, and ownership.

### Track G - Testing and hardening
1. Feature tests for endpoints and auth boundaries.
2. Unit tests for trigger calculations and threshold edges.
3. Integration tests for evaluator job persistence.
4. Ensure no regressions in existing metrics logging behavior.

## Suggested Milestones
### Milestone 1 (Week 1)
- Module bootstrap complete.
- Health endpoints implemented and tested.

### Milestone 2 (Week 2)
- Metrics contract/adapter complete.
- Internal metrics endpoint and route instrumentation in place.

### Milestone 3 (Week 3)
- Trigger evaluator and persistence complete.
- Scorecard endpoint and scheduled job running.

### Milestone 4 (Week 4)
- Operator UI pages complete and UX-guide compliant.
- Alert/dashboard artifacts committed.
- Full tests green.

## Definition of Done
- Module owns health/metrics/trigger logic via clear contracts.
- Endpoints are secured and documented.
- Trigger status can be computed without manual log inspection.
- WIP docs updated with what was implemented vs deferred.
- If all implementation is complete and verified, remove this WIP folder per cleanup protocol.

## Risks and Mitigations
- Risk: metrics cardinality explosion.
  - Mitigation: enforce label allow-list and bounded route-group labels.
- Risk: internal metrics endpoint exposure.
  - Mitigation: strict middleware and network allow-list.
- Risk: noisy alerts.
  - Mitigation: include burn-rate windows and warning/critical thresholds with tuning period.

## Handoff Notes
- Keep business modules decoupled; expose module contracts and adapters instead of direct imports.
- Prefer incremental integration through interfaces and event hooks.
- Preserve backward compatibility for current logging channels (`business`, `performance`, `security`, `queue`).
