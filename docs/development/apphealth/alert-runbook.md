# AppHealth Alert Runbook

**Module:** `Modules/AppHealth`
**Audience:** On-call engineers, platform team, capacity decision-makers
**Last Updated:** 2026-03-20

---

## 1. Alert Definitions

| Alert Name | Fires When | Severity |
|---|---|---|
| `AppHealthStageABreach` | `apphealth_stage_a_breach_count >= 2` | warning |
| `AppHealthStageAGate` | `apphealth_stage_a_schedule_recommended == 1` for 2 consecutive evaluation windows | critical |
| `AppHealthEndpointDown` | `apphealth_up == 0` for 5 min | critical |
| `AppHealthApiLatencyHigh` | `histogram_quantile(0.95, http_request_duration_seconds_bucket) > 2` | warning |
| `AppHealthQueueDepthHigh` | queue pending jobs > 500 AND age > 30s | warning |
| `AppHealthFailedJobRatioHigh` | `failed_job_ratio > 0.001` for 15 min | warning |

Full YAML alert definitions are in `Modules/AppHealth/docs/alert-rules.example.yml`.

---

## 2. Alert Logic Explained

### `AppHealthStageABreach`
The Stage A trigger evaluator runs every 15 minutes (configurable via `APPHEALTH_EVALUATION_CRON`).
It checks five signals against their thresholds (see `config/apphealth.php` → `thresholds`).
When **≥2 signals breach**, `breach_count` rises to ≥2.

This alert is **informational/warning** — a single breach window does not require action.

### `AppHealthStageAGate`
Fires after **2 consecutive breach weeks** as defined in `SCALING_PLAYBOOK.md`.
At this point `apphealth_stage_a_schedule_recommended == 1` persists across multiple evaluation windows.

**This alert requires action.** See §4 (Stage-gate ownership table).

### `AppHealthApiLatencyHigh`
Fired from Prometheus histogram buckets emitted at `/internal/metrics`.
`RecordHttpRouteMetrics` middleware accumulates `http_request_duration_seconds_bucket{le=<bound>}`
counters per route group.

**Noise tuning:** If this fires due to a one-off deployment spike, confirm that the percentile
returns to baseline within 1h elapsed. If it persists, correlate with DB query duration.

---

## 3. Noise Tuning Guide

### When alerts fire too often

1. **Adjust thresholds first** via env vars before touching alert YAML:
   ```
   APPHEALTH_STAGE_A_API_P95_SECONDS=3.0        # relax from 2.0
   APPHEALTH_STAGE_A_QUEUE_WAIT_P95_SECONDS=60  # relax from 30
   ```

2. **Increase `for:` duration** in alert rule to require the signal to persist longer before firing.

3. **Reduce evaluation frequency** via `APPHEALTH_EVALUATION_CRON='*/30 * * * *'` (every 30m).

4. **Silence during maintenance:** Use Alertmanager silence with matcher `alertname="AppHealthStageABreach"` for the maintenance window.

### When alerts fire too rarely (missed capacity events)

1. **Check that ingestion is enabled:** `APPHEALTH_INGESTION_ENABLED=true`
2. **Check the Prometheus scrape target** can reach `GET /internal/metrics` with the configured `X-AppHealth-Token`.
3. **Verify scorecard is being persisted** — check `app_health_scaling_scorecard_snapshots` table for recent rows.

---

## 4. Weekly Review Checklist

Every Monday (or after each `EvaluateScalingTriggersJob` execution that results in `recommendation = schedule_stage_a_work`):

- [ ] Open `/app-health/scorecard` (requires admin login)
- [ ] Review the **Weekly Trend** card — note direction and consecutive breach weeks
- [ ] Review the **Stage A Trigger Checks** table — which specific signals are breaching?
- [ ] If `gate_condition_met = true`: escalate to §4 ownership table
- [ ] Log review outcome in the capacity tracking sheet (link in team wiki)

### UI Workflow Guidance
- Primary operator cockpit: `/app-health/scorecard` (internal admin UI)
- Adjacent internal diagnostics: `/resilience`
- Grafana/Prometheus are still direct-access tools for deep time-series analysis.
- Use the scorecard's "Observability Console" links when `APPHEALTH_GRAFANA_URL` /
    `APPHEALTH_PROMETHEUS_URL` are configured.

---

## 5. Stage-Gate Ownership Table

| Role | Responsibility |
|---|---|
| **Platform Engineer (on-call)** | Acknowledges `AppHealthStageAGate` alert; verifies data integrity |
| **Engineering Manager** | Go/no-go decision for Stage A capacity work; communicates timeline to Product |
| **Lead Engineer** | Defines scope of Stage A work; assigns to sprint |
| **CTO / VP Eng** | Signs off on infrastructure spend if Stage A requires new capacity commits |

**Stage A Work:** Defined in `SCALING_PLAYBOOK.md` §3 — includes query optimisation, cache layer review, horizontal scaling preparation.

---

## 6. Metric Ingestion Gaps

The current `RuntimeMetricIngestionService` provides:
- ✅ `failed_job_ratio` — from DB `failed_jobs`/`jobs` tables
- ✅ `queue_wait_p95_seconds` — approximated from oldest pending job age
- ✅ `api_p95_seconds` — estimated from histogram buckets when `RecordHttpRouteMetrics` is active
- ⚠️ `worker_cpu_breach_minutes_24h` — **not yet implemented** (requires GCP Monitoring API or Cloud Ops agent)
- ⚠️ `db_cpu_breach_minutes_24h` — **not yet implemented** (requires Cloud SQL metrics API)

Until these are wired, the CPU inputs remain `0.0` — they never contribute to breach count.
Set them via env vars to simulate production thresholds in staging:
```
APPHEALTH_INPUT_WORKER_CPU_BREACH_MINUTES_24H=300
APPHEALTH_INPUT_DB_CPU_BREACH_MINUTES_24H=300
```

---

## 7. Escalation Path

```
Alert fires → On-call acknowledges (PagerDuty/Opsgenie)
    → Checks /app-health/scorecard
    → Checks /internal/health/detailed (with X-AppHealth-Token)
    → If breach_count < 2: monitor for trend
    → If breach_count >= 2 AND consecutive_weeks >= 2: pages Engineering Manager
        → Stage A work scheduled within next sprint
```
