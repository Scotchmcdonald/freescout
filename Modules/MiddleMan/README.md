# MiddleMan

MiddleMan is an operator control plane for Laravel events: observe, intercept, mutate, replay, trace, and safely operate event-driven workflows in production-like environments.

## Access and Permissions
- Base path: `/middleman`
- Global middleware on all routes:
  - `auth`
  - `verified`
  - `can:view_middleman`
- Mutating actions additionally require:
  - `can:manage_middleman`

## Production Safety Controls
- Circuit breaker auto-trips on repeated failures, event storms, or queue backpressure.
- Infrastructure failures (cache/queue path) are logged at `emergency` level and force `OPEN` state.
- Breaker state has a file fallback at `storage/framework/middleman_breaker.flag` so `OPEN` survives cache outages.
- Intercept hydration armor marks failed replay/hydration attempts as `corrupted` and stores operator notes in `resolution_notes`.
- Marshal async model search is allowlisted: model must implement `MiddleManSearchable` or be listed in `middleman.searchable_models`.
- Daily lifecycle pruning is scheduled at `03:30` with independent retention windows for logs/intercepts/audit.

## Tabs and URLs
- Dashboard: `GET /middleman`
- Logging: `GET /middleman/logging`
- Intercept: `GET /middleman/intercept`
- Marshal: `GET /middleman/marshal`
- Topology: `GET /middleman/topology`
- Schema Drift: `GET /middleman/schema`
- Tracing: `GET /middleman/tracing`
- Replay Workspace: `GET /middleman/replay`
- Muting: `GET /middleman/muting`

## Tab-by-Tab Capabilities

### 1) Dashboard (`/middleman`)
Purpose: module health and operational posture.

Features:
- Module enabled/disabled state.
- High-level metrics (recent logs, total logs, pending intercepts, unique event types).
- Circuit breaker status with reset action.
- Logging and intercept status cards with active rule counts.
- Rules overview and recent audit trail.

Manage actions:
- Reset circuit breaker: `POST /middleman/circuit-breaker/reset`

Typical workflow:
- Check if event control plane is healthy.
- If breaker is `OPEN` or `HALF_OPEN`, reset if operationally appropriate.
- Pivot into Logging/Intercept based on queue and rule state.

### 2) Logging (`/middleman/logging`)
Purpose: capture and inspect matched event traffic.

Features:
- Start/stop logging toggle.
- Add/remove log match rules (supports wildcard patterns).
- Discoverable event list for rule authoring.
- Paginated event log table with detail panel.
- Selection-based replay submission to sequence replay API.

Read/filter APIs:
- Filter logs: `GET /middleman/logging/filter`
- Event detail: `GET /middleman/logging/{id}`

Manage actions:
- Toggle logging: `POST /middleman/logging/toggle`
- Add rule: `POST /middleman/logging/rules`
- Remove rule: `DELETE /middleman/logging/rules`
- Clear logs: `DELETE /middleman/logging/clear`
- Replay selected sequence: `POST /middleman/replay/sequence` with `{ source: "logs", ids: [...] }`

Typical workflow:
- Enable logging, define rules, generate traffic, inspect payloads.
- Select a subset of captured events and replay in original time order.

### 3) Intercept (`/middleman/intercept`)
Purpose: halt matched events before listeners run, then release/discard deliberately.

Features:
- Start/stop interception toggle.
- Add/remove intercept rules.
- Pending queue with drag-drop ordering.
- Per-item actions: fire, discard, edit payload.
- Bulk actions: fire selected, replay selected sequence, fire all.
- Hydration armor during fire paths: failed items are marked `corrupted` instead of hard-failing the request.
- Recent resolved history.

Read APIs:
- Intercept detail: `GET /middleman/intercept/{id}`

Manage actions:
- Toggle interception: `POST /middleman/intercept/toggle`
- Add rule: `POST /middleman/intercept/rules`
- Remove rule: `DELETE /middleman/intercept/rules`
- Update payload: `PUT /middleman/intercept/{id}/payload`
- Fire one: `POST /middleman/intercept/{id}/fire`
- Discard one: `POST /middleman/intercept/{id}/discard`
- Fire selected: `POST /middleman/intercept/fire-selected`
- Fire all: `POST /middleman/intercept/fire-all`
- Reorder queue: `POST /middleman/intercept/reorder`
- Replay selected sequence: `POST /middleman/replay/sequence` with `{ source: "intercepts", ids: [...] }`

Typical workflow:
- Intercept a risky flow, inspect queued events, reorder for dependency correctness, edit payloads if needed, then release selected events.

Response notes:
- `POST /middleman/intercept/{id}/fire` returns `422` when event hydration/dispatch fails, and the intercept is marked `corrupted`.
- `POST /middleman/intercept/fire-selected` and `POST /middleman/intercept/fire-all` return counters for both `fired` and `corrupted`.

### 4) Marshal (`/middleman/marshal`)
Purpose: manually construct and dispatch events for testing and operations.

Features:
- Event class search and module filtering.
- Constructor reflection to render dynamic parameter forms.
- Type-aware inputs:
  - enum dropdowns
  - async model search fields (allowlisted only)
  - scalar coercion
- Single dispatch and batch JSON dispatch.
- Optional hold in intercept queue.
- Saved presets per event class (load/save/delete).

Read APIs:
- Parameter discovery: `GET /middleman/marshal/parameters?event_class=...`
- Async model search: `GET /middleman/marshal/search-model?model_class=...&query=...`

Security notes:
- Async model search rejects non-allowlisted models with `403`.
- Allowlist options:
  - implement `Modules\\MiddleMan\\Contracts\\MiddleManSearchable`
  - add FQCN to `MIDDLEMAN_SEARCHABLE_MODELS` / `middleman.searchable_models`

Manage actions:
- Fire/hold single: `POST /middleman/marshal/fire`
- Fire/hold batch: `POST /middleman/marshal/batch`
- Save preset: `POST /middleman/marshal/presets`
- Delete preset: `DELETE /middleman/marshal/presets/{id}`

Typical workflow:
- Pick event class, fill typed parameters, optionally save as preset, then fire immediately or hold for controlled release in Intercept.

### 5) Topology (`/middleman/topology`)
Purpose: discover event-to-listener graph shape.

Features:
- Snapshot counts: total events, listeners, edges.
- Graphviz DOT source output.
- JSON graph payload output.
- Optional SVG rendering through Kroki.

Read APIs:
- Topology page: `GET /middleman/topology`
- Rendered SVG: `GET /middleman/topology/diagram.svg`

Notes:
- SVG endpoint requires `middleman.kroki.enabled=true`.
- Configure Kroki via module config/environment (`enabled`, `base_url`, `timeout_seconds`).

Typical workflow:
- Inspect graph for coupling/hotspots, export DOT/JSON, and verify render path through local Kroki sidecar.

### 6) Schema Drift (`/middleman/schema`)
Purpose: compare observed payloads against known schema baselines.

Features:
- Paginated baseline schemas by event class/version.
- Drift counter and recent drifted log list.
- Inline drift metadata inspection from log metadata.

Read API:
- Schema page: `GET /middleman/schema`

Typical workflow:
- Review drift spikes, inspect affected event classes, and use details to coordinate contract/version updates.

### 7) Tracing (`/middleman/tracing`)
Purpose: correlation/causation timeline analysis.

Features:
- Correlation group list with event counts and recency.
- Filter trace stream by `correlation_id`.
- Event table with correlation and causation identifiers.

Read API:
- Tracing page (optional filter): `GET /middleman/tracing?correlation_id=...`

Typical workflow:
- Select a correlation ID, walk event chronology, and identify fan-out or causation gaps.

### 8) Replay Workspace (`/middleman/replay`)
Purpose: replay previously captured logs one-by-one from a dedicated workspace.

Features:
- Paginated recent logs eligible for replay.
- Per-row replay trigger.
- Replay counter for historical replay activity.

Manage actions:
- Replay single log: `POST /middleman/replay/{logId}`

Typical workflow:
- Find a candidate event and re-run it quickly without leaving the workspace.

### 9) Muting (`/middleman/muting`)
Purpose: surgically suppress specific listeners while preserving event flow.

Features:
- Add muted listener class from discovered candidates.
- Remove individual mute.
- Clear all mutes.
- Live list of current muted listeners.

Read APIs:
- Muting page: `GET /middleman/muting`
- Muting data: `GET /middleman/muting/data`

Manage actions:
- Add mute: `POST /middleman/muting/add`
- Remove mute: `DELETE /middleman/muting/remove`
- Clear all: `DELETE /middleman/muting/clear`

Typical workflow:
- Mute noisy or side-effect-heavy listeners during diagnostics, validate behavior, then unmute/clear.

## Core Cross-Tab Workflows

### Controlled Event Surgery
1. Enable Intercept and define narrow rules.
2. Generate or marshal events.
3. Inspect and optionally edit pending payloads.
4. Reorder and fire selected events.
5. Validate side effects in logs/audit.

### Deterministic Replay from Captures
1. Select captured items in Logging or Intercept.
2. Submit sequence replay.
3. Review sequence outcome (`processed`, `succeeded`, `failed`, `errors`).
4. Investigate failures by event class and payload history.

Replay response notes:
- `POST /middleman/replay/sequence` returns `200` when all selected items succeed.
- Returns `207` when processing is mixed (some success, some failed).
- Request validation: `source` must be `logs` or `intercepts`; `ids` must contain 1-200 integers.

### Contract/Dependency Diagnostics
1. Use Topology to inspect listener graph and edges.
2. Use Schema Drift to find payload contract changes.
3. Use Tracing to analyze correlation chains.
4. Apply Muting for focused isolation tests.

## Operational Notes
- Sequence replay accepts max 200 IDs per request.
- Marshal batch accepts max 100 payload items per request.
- Intercept actions preserve queue order semantics; use reorder carefully when events depend on prior state.
- All control actions are audit logged via `MiddleManAuditEntry`.

### Data Lifecycle / Pruning
- Scheduled task: `middleman:prune` runs daily at `03:30` (see `routes/console.php`).
- Retention windows (config):
  - `middleman.prune.logs_days` (default `7`)
  - `middleman.prune.intercepts_days` (default `14`)
  - `middleman.prune.audit_days` (default `90`)
- Command options:
  - `--logs-days=`, `--intercepts-days=`, `--audit-days=`
  - `--include-audit` (audit pruning is opt-in)
  - `--dry-run` and `--batch=` for safe operations
