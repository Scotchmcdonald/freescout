# AppHealth Metric Catalog

## Stage A Inputs
- `api_p95_seconds`: p95 latency for core API routes.
- `queue_wait_p95_seconds`: p95 wait time for queue processing.
- `failed_job_ratio`: failed jobs / processed jobs.
- `worker_cpu_breach_minutes_24h`: cumulative minutes > 70% worker CPU.
- `db_cpu_breach_minutes_24h`: cumulative minutes > 70% DB CPU.

## Exposed Metrics (`/internal/metrics`)
- `apphealth_up`
- `apphealth_counter_total{metric="..."}`
- `apphealth_summary_count{metric="..."}`
- `apphealth_summary_sum{metric="..."}`
- `apphealth_summary_last{metric="..."}`
- `apphealth_stage_a_breach_count`
- `apphealth_stage_a_schedule_recommended`

## Ownership
- Owner: `Modules/AppHealth`
- Legacy compatibility: events mirrored through `App\Services\MetricsService` adapter.
