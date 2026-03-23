# AppHealth Module

## Purpose
`Modules/AppHealth` is the canonical owner for:
- internal health endpoints
- metrics instrumentation and exposition
- scaling trigger evaluation (Stage A)
- operator scorecard snapshots

## Endpoints
- `GET /internal/health`
- `GET /internal/health/detailed`
- `GET /internal/metrics`
- `GET /internal/scaling/scorecard`

## Operator UI
- `GET /app-health/scorecard` (authenticated admin/internal operators)
- Provides a quick Stage A trigger review surface with current recommendation and per-check status.

### Observability Console (in scorecard UI)
- The scorecard now includes an "Observability Console" block for operator pivots:
   - Resilience Dashboard (internal)
   - Grafana Dashboards (external, optional)
   - Prometheus (external, optional)
- Configure external links via:
   - `APPHEALTH_GRAFANA_URL=https://grafana.example.com/d/...`
   - `APPHEALTH_PROMETHEUS_URL=https://prometheus.example.com`
- If external URLs are not configured, operators can still use the internal scorecard + resilience UI.

## Security
- All endpoints require a valid internal token via `X-AppHealth-Token` (or Bearer token).
- `/internal/scaling/scorecard` additionally requires authenticated admin/internal operator access.
- If `APPHEALTH_INTERNAL_TOKEN` is not set, endpoints deny by default.

## Environment Flags
- `APPHEALTH_ENABLED=true`
- `APPHEALTH_METRICS_ENABLED=true`
- `APPHEALTH_TRIGGER_EVALUATION_ENABLED=true`
- `APPHEALTH_INTERNAL_TOKEN=<required secret>`
- `APPHEALTH_EVALUATION_CRON=*/15 * * * *`

## Migration Notes (Metrics Compatibility)
- Existing `App\Services\MetricsService` remains unchanged.
- `Modules/AppHealth/Services/LegacyMetricsCompatibilityAdapter` forwards AppHealth metric writes into the existing metrics logging stream to preserve compatibility during migration.
- New metric instrumentation should target `MetricRecorderContract`.

## HTTP Instrumentation
- AppHealth records request counters and durations for API routes via route-group labels.
- Metrics emitted:
   - `http_requests_total`
   - `http_request_duration_seconds`
- Labels included in recorded keys: `route_group`, `method`, `status`, `status_class`.

## Run and Verify
1. Run module tests:
   - `php artisan test Modules/AppHealth/Tests --parallel --processes=10`
2. Validate route registration:
   - `php artisan route:list | grep internal/health`
3. Manual endpoint check with token:
   - `curl -H "X-AppHealth-Token: <token>" http://localhost/internal/health`
4. Trigger job manually:
   - `php artisan tinker --execute="dispatch_sync(new \\Modules\\AppHealth\\Jobs\\EvaluateScalingTriggersJob);"`
