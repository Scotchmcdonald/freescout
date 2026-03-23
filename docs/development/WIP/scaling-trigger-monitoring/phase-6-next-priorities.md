# Phase 6 - Operational Quality (Execution)

Date: 2026-03-20
Owner: Platform/Architecture
Status: In Progress

## Goal
Turn AppHealth from a working skeleton into production-grade instrumentation:
1. Replace summary-based duration storage with true Prometheus histogram buckets.
2. Add a real ingestion adapter for runtime inputs (Horizon stats + queue DB fallback).
3. Surface 7d/30d trend deltas on the operator scorecard page.
4. Document alert noise tuning runbook.

## Track A — Prometheus Histogram Buckets

**Problem:** `MetricRecorderService::observe()` stores only `{count, sum, last}` which cannot
produce accurate quantiles in Grafana/PromQL. PromQL's `histogram_quantile` requires cumulative
`_bucket` samples with `le` labels.

**Plan:**
- Add `HISTOGRAM_PREFIX = 'apphealth:histogram:'` and `histogram_bounds` config key with
  default buckets `[0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10, +Inf]`.
- New public method `recordHistogram(string $name, float $value, array $labels)` on the service
  and contract.
- On each observation: walk each boundary, increment the `<=boundary` bucket counters.
- `renderPrometheus()` emits:
  ```
  # TYPE <metric>_bucket histogram
  <metric>_bucket{le="0.005",...} N
  ...
  <metric>_bucket{le="+Inf",...} N
  <metric>_count N
  <metric>_sum S
  ```
- `RecordHttpRouteMetrics` calls `recordHistogram` instead of (or in addition to) `observe`
  for `http_request_duration_seconds`.

## Track B — Horizon / Queue Depth Ingestion Adapter

**Problem:** `TriggerEvaluationService::resolveInputsFromConfigAndRuntime()` reads entirely from
env config. Without a real adapter, `api_p95_seconds` and `queue_wait_p95_seconds` are always
`0.0` unless operators set env vars manually.

**Plan:**
- New contract `MetricIngestionContract` with `fetchInputs(): array<string, float>`.
- New service `RuntimeMetricIngestionService` that:
  - Reads `queue_wait_p95_seconds` from `failed_job_ratio` fallback + DB queue tables.
  - Reads `api_p95_seconds` from the in-memory histogram if available (from RecordHttpRouteMetrics).
  - Reads `failed_job_ratio` from `failed_jobs`/`jobs` tables (already exists in TriggerEvaluationService).
  - Falls back to config/env values so existing tests are unaffected.
- Bind the contract in the provider; wire it into `TriggerEvaluationService`.
- Config flag `apphealth.ingestion.enabled` (default true).

## Track C — Trend Deltas on Operator Scorecard

**Problem:** Single-day snapshot gives no directional signal. Operators need to see whether breach
count is improving or worsening week-over-week to make the 2-consecutive-week gate call.

**Plan:**
- Add `TrendDeltaService` with `weeklyDelta(int $currentBreachCount): array` that queries the
  last 7 and 14 snapshots to produce `{delta_7d, trend_direction, consecutive_breach_weeks}`.
- Expose `trend` variable to the Blade view.
- Add a fourth summary card "Weekly Trend" to the operator scorecard page showing delta and
  a directional badge (IMPROVING / STABLE / WORSENING).
- Add `consecutive_breach_weeks_required` config key (default 2) from SCALING_PLAYBOOK.

## Track D — Alert Runbook

**Plan:**
- Create `docs/development/apphealth/alert-runbook.md` with:
  - Alert definitions (what fires, why, severity).
  - Noise tuning guide (alert-rules.example.yml comments explained).
  - Weekly review checklist.
  - Stage-gate ownership table.

## Done in this phase
- ✅ Track A: Added `recordHistogram()` to `MetricRecorderContract` and `MetricRecorderService`.
- ✅ Track A: `renderCachedHistograms()` emits proper `_bucket` + `# TYPE histogram` lines.
- ✅ Track A: `RecordHttpRouteMetrics` now calls `recordHistogram` for `http_request_duration_seconds`.
- ✅ Track A: `histogram_buckets` config key with defaults matching Prometheus canonical HTTP buckets.
- ✅ Track B: `MetricIngestionContract` + `RuntimeMetricIngestionService` (p95 estimate from histogram, queue wait from oldest job age, failed_job_ratio from DB).
- ✅ Track B: `TriggerEvaluationService` now injects and uses `MetricIngestionContract` as secondary source when env config is absent.
- ✅ Track B: `MetricIngestionContract` bound in provider.
- ✅ Track C: `TrendDeltaService` with `weeklyDelta()` returning delta_7d, delta_14d, direction, consecutive_breach_weeks, gate_condition_met.
- ✅ Track C: `OperatorScorecardPageController` injects `TrendDeltaService`, passes `trend` to view.
- ✅ Track C: Operator scorecard Blade updated to 4-column grid + gate condition banner.
- ✅ Track D: `docs/development/apphealth/alert-runbook.md` with alert definitions, noise tuning, weekly checklist, ownership table, ingestion gaps.
- ✅ Tests: 25/25 passing (75 assertions): 4 new histogram unit tests, 3 ingestion/evaluator unit tests, 5 trend/gate feature tests.

## Testing Strategy
- Unit: `HistogramRecordingTest` — assert bucket counts increment correctly.
- Unit: `RuntimeIngestionServiceTest` — assert fallback values when queue tables absent.
- Feature: `OperatorScorecardTrendTest` — assert trend card renders with known snapshot history.
- All existing 13 tests must remain green.
