# Phase 5 - Next Priorities (Execution)

Date: 2026-03-20
Owner: Platform/Architecture
Status: In Progress

## Goal
Move AppHealth from baseline implementation to operational usefulness with better observability signal quality and operator workflows.

## Priority Order
1. HTTP route-group instrumentation hardening
- Add request counters and duration observations by route group, method, and status class.
- Ensure metrics rendering works in non-Redis cache stores used in tests/local.
- Tests: verify metrics endpoint contains recorded HTTP metrics after internal endpoint calls.

2. Operator scorecard web surface
- Add authenticated admin operator page to review latest Stage A checks quickly.
- Keep endpoint-grade security for JSON APIs; keep UI under staff/admin auth.
- Tests: guest redirect, non-admin forbidden, admin success.

3. Governance and runbook updates
- Document new UI route and instrumentation behavior.
- Track follow-up backlog for Prometheus-native histograms and Horizon/infra metric ingestion.

## Done in this phase
- Added `RecordHttpRouteMetrics` middleware.
- Wired AppHealth API routes through instrumentation middleware.
- Added cache-store-agnostic metric key indexing in `MetricRecorderService`.
- Added `/app-health/scorecard` operator page + Blade view.
- Added feature tests for operator page auth boundaries.

## Follow-up Backlog
- Replace summary-based duration metric with true histogram buckets for Prometheus quantiles.
- Add ingestion adapters for Horizon/Prometheus exporters as primary trigger inputs.
- Add trend delta calculations (7d/30d) on operator scorecard.
- Add alert/noise tuning runbook and ownership handoff checklist.

## Residual Work (Post-Phase 6)
1. Operator observability navigation
- Clarify whether operators should rely only on Grafana or use internal UI first.
- Implement scorecard-side "Observability Console" with links to Resilience + optional Grafana/Prometheus.

2. Documentation alignment
- Update README and runbook to reflect "scorecard first, Grafana deep dive" workflow.

## Residual Work Done
- Added Observability Console to `/app-health/scorecard`.
- Added config-driven external links: `APPHEALTH_GRAFANA_URL`, `APPHEALTH_PROMETHEUS_URL`.
- Kept Resilience Dashboard as first-class adjacent UI from scorecard.
- Updated AppHealth README + alert runbook with access pattern guidance.
