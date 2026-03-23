# Phase 2 - Technical Monitoring Implementation for Scaling Triggers

## Goal
Turn SCALING_PLAYBOOK.md trigger thresholds into concrete, machine-evaluated signals with dashboards and alert rules.

## Baseline from Current Repo
- Docker deployment already runs Redis and dedicated queue workers (`queue`, `queue-billing`) via compose/deploy scripts.
- App has `MetricsService` for structured event/performance logging, but no first-class Prometheus endpoint in code yet.
- Sentry is installed; Horizon is not currently in `composer.json` and must be added to get Redis queue internals.

## Architecture Decision: Separate Module for AppHealth/Metrics

Decision: Yes, create a dedicated module (`Modules/AppHealth`) for health, metrics, and scaling-trigger observability.

Why this should be a module in this codebase:
- The platform already uses infrastructure modules (`Alerts`, `WidgetRegistry`) to isolate cross-cutting concerns.
- App health and metrics are cross-domain capabilities with independent lifecycle and ownership.
- A module allows strict contracts and avoids scattering observability logic across `app/` and business modules.
- It creates a clean extraction path if observability components later move to separate services.

Scope of `Modules/AppHealth`:
- Health endpoints and dependency checks.
- Metrics instrumentation helpers and route-group metric labeling.
- Trigger evaluator jobs (daily/weekly stage-gate snapshots).
- Dashboard metadata and scorecard generation helpers.
- Optional Prometheus exposition endpoint (internal-only).

Out of scope for `Modules/AppHealth`:
- Business-domain events and billing logic.
- Runtime-specific infra provisioning (Prometheus/Grafana installation scripts can stay in deployment docs/scripts).

Module interfaces to define:
- `HealthCheckContract` for dependency probes.
- `MetricRecorderContract` for counters/histograms/timers.
- `TriggerEvaluatorContract` for Stage A/B/C/D threshold evaluation.

Initial endpoints owned by module:
- `GET /internal/health`
- `GET /internal/health/detailed`
- `GET /internal/metrics` (auth/internal network only)
- `GET /internal/scaling/scorecard` (auth only)

## Technical Architecture (Data Path)
1. Application and queue runtime
- Laravel app containers emit HTTP and app logs.
- Queue containers emit worker logs.

2. Metrics collection
- Prometheus scrapes:
  - `node_exporter` for host CPU/memory/disk.
  - `cadvisor` for per-container CPU/memory.
  - `mysqld_exporter` (or managed DB metrics) for DB CPU/connections/slow queries.
  - `redis_exporter` for Redis health and queue-side pressure.
  - `horizon_exporter` (custom or sidecar) for queue wait/throughput/failures.
  - optional app `/internal/metrics` endpoint for business counters/histograms.

3. Visualization and alerting
- Grafana dashboards backed by Prometheus.
- Alertmanager routes alerts to Slack and PagerDuty.
- Sentry handles transaction traces and exception alerts.

## Implementation Workstreams

### Workstream 0 - Module Bootstrap (AppHealth)
1. Create `Modules/AppHealth` with providers, routes, contracts, and services.
2. Move/adapter-wrap existing `app/Services/MetricsService.php` into module service layer.
3. Add feature flags:
  - `APPHEALTH_ENABLED=true`
  - `APPHEALTH_METRICS_ENABLED=true`
  - `APPHEALTH_TRIGGER_EVALUATION_ENABLED=true`
4. Add module tests for health checks, metrics endpoint auth, and trigger evaluation output.

Definition of done for Workstream 0:
- App health and metrics concerns no longer require direct edits in unrelated business modules.
- Existing metrics logging behavior remains backward-compatible.

### Workstream A - Queue Observability (Redis + Horizon)
1. Install and configure Horizon.
2. Ensure queue workers run via Redis connection in prod Docker profile.
3. Export Horizon queue metrics to Prometheus.

Suggested commands:
```bash
composer require laravel/horizon
php artisan horizon:install
php artisan migrate
```

Metrics required:
- `queue_wait_seconds{queue}`
- `queue_jobs_processed_total{queue,status}`
- `queue_jobs_failed_total{queue}`
- `queue_jobs_pending{queue}`
- `queue_oldest_job_age_seconds{queue}`

### Workstream B - API Latency and Error Budgets
1. Enable request duration histogram metrics (by route group).
2. Use Sentry transaction performance for p95/p99 validation.
3. Define endpoint groups (`ticket`, `billing`, `sync`, `portal`) with stable labels.

Metrics required:
- `http_request_duration_seconds_bucket{route_group,method,status}`
- `http_requests_total{route_group,status}`
- `app_errors_total{route_group,error_class}`

### Workstream C - Infra Saturation
1. Host-level and container-level CPU/memory telemetry.
2. DB CPU/connections/slow query telemetry.
3. Redis memory and command latency telemetry.

Metrics required:
- `container_cpu_usage_seconds_total{name}`
- `container_memory_working_set_bytes{name}`
- `mysql_global_status_threads_connected`
- `mysql_global_status_slow_queries`
- `redis_memory_used_bytes`
- `redis_commands_duration_seconds_total`

### Workstream D - Stage B/C/D Governance Signals
1. Maintain event family consumer registry (YAML/Markdown in repo).
2. Track backfills/replays via incident tags.
3. Track engineering flow metrics (lead time, deploy frequency, incident isolation).

Artifacts:
- `docs/development/architecture-scorecard.md` (weekly)
- `docs/development/event-family-registry.md` (monthly)

## Exact Trigger Queries (Examples)

### Stage A Trigger 1: p95 API latency > 2.0s
PromQL:
```promql
histogram_quantile(
  0.95,
  sum(rate(http_request_duration_seconds_bucket{route_group="ticket"}[5m])) by (le)
) > 2
```

### Stage A Trigger 2: Queue wait p95 > 30s (business hours)
PromQL:
```promql
histogram_quantile(
  0.95,
  sum(rate(queue_wait_seconds_bucket{queue="default"}[5m])) by (le)
) > 30
```

### Stage A Trigger 3: Failed jobs > 0.1%
PromQL:
```promql
(
  sum(rate(queue_jobs_failed_total[1h]))
  /
  clamp_min(sum(rate(queue_jobs_processed_total[1h])), 1)
) > 0.001
```

### Stage A Trigger 4: Worker CPU > 70% for >4h/day
PromQL (container example):
```promql
avg by (name) (
  rate(container_cpu_usage_seconds_total{name=~"queue|queue-billing|app"}[5m])
) > 0.7
```

Daily cumulative breach check:
- Use recording rule to sum `1m` breach windows and alert if cumulative > 240 minutes/day.

### Stage A Trigger 5: DB CPU > 70% for >4h/day
PromQL:
```promql
avg_over_time(db_cpu_utilization_percent[15m]) > 70
```

If using managed DB metric names, map to provider-specific CPU utilization metric.

## SQL/CLI Fallbacks (If Metrics Pipeline Is Not Ready)

### Failed job ratio (last 24h)
```sql
SELECT
  (SUM(CASE WHEN failed_at >= NOW() - INTERVAL 24 HOUR THEN 1 ELSE 0 END) /
   GREATEST(COUNT(*),1)) AS failed_ratio_24h
FROM failed_jobs;
```

### Queue backlog (database queue mode only)
```sql
SELECT queue, COUNT(*) AS pending
FROM jobs
GROUP BY queue;
```

### Oldest pending job age (database queue mode only)
```sql
SELECT queue, TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(MIN(available_at)), NOW()) AS oldest_age_s
FROM jobs
GROUP BY queue;
```

### Redis queue depth fallback
```bash
redis-cli LLEN queues:default
redis-cli LLEN queues:billing
redis-cli LLEN queues:long-running
```

## Alert Rules (Production Defaults)

### Warning level
- API ticket p95 > 2.0s for 15m.
- Queue wait p95 > 30s for 30m.
- Failed jobs > 0.05% for 30m.

### Critical level
- API ticket p95 > 3.0s for 15m.
- Queue wait p95 > 60s for 15m.
- Failed jobs > 0.1% for 15m.
- DB CPU > 80% for 30m.

### Routing
- Warning -> Slack `#platform-alerts`.
- Critical -> Slack `#incidents` + PagerDuty on-call.

## Dashboard Build Specification

### Dashboard 1: Executive Scaling (weekly)
Panels:
1. ticket endpoint p95/p99 latency
2. 5xx and app error rates
3. queue wait p95 by queue
4. failed job ratio (1h, 24h)
5. worker CPU/memory by service
6. DB CPU and slow queries

### Dashboard 2: Queue Operations (daily)
Panels:
1. processed jobs/sec by queue
2. pending jobs by queue
3. oldest job age by queue
4. retry count and failure rate by job class
5. worker restarts and crash loops

### Dashboard 3: Stage Gate Signals (monthly)
Panels:
1. event families with consumer count
2. replay/backfill count by quarter
3. lead time and deploy frequency trend
4. incidents by domain and blast radius

## Verification and Runbook Drills
1. Synthetic load drill:
- Generate controlled ticket traffic and queue load.
- Validate alerts fire at expected thresholds.

2. Queue degradation drill:
- Pause one queue worker tier.
- Confirm wait-time and oldest-age alerts fire.

3. Recovery drill:
- Restore workers and confirm alert auto-resolution.
- Record MTTR and update thresholds if too noisy.

## 30-Day Delivery Plan
Week 1:
- Bootstrap `Modules/AppHealth` and wire baseline health endpoints.
- Install Horizon and wire queue metrics export.
- Deploy Prometheus exporters (node, cadvisor, redis, DB).

Week 2:
- Implement API latency histograms and route-group labels in `Modules/AppHealth` middleware/helpers.
- Build dashboards 1 and 2.

Week 3:
- Configure alert rules and routing.
- Add scorecard artifacts for Stage B/C/D governance via `TriggerEvaluator` job.

Week 4:
- Execute synthetic drill and one incident simulation.
- Tune thresholds and finalize runbooks.

## Definition of Done
- [ ] Every playbook trigger is backed by an automatic metric query.
- [ ] Every metric has warning and critical alert thresholds.
- [ ] Dashboards 1 to 3 are live and reviewed on cadence.
- [ ] One full drill completed with documented learnings.
- [ ] Stage-gate decision can be made from observed data without manual log scraping.
- [ ] `Modules/AppHealth` owns health/metrics/trigger-evaluation contracts and endpoints.
