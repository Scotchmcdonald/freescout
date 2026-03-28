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

### 4) Marshal (`/middleman/marshal`)
Purpose: manually construct and dispatch events for testing and operations.

Features:
- Event class search and module filtering.
- Constructor reflection to render dynamic parameter forms.
- Type-aware inputs:
  - enum dropdowns
  - async model search fields
  - scalar coercion
- Single dispatch and batch JSON dispatch.
- Optional hold in intercept queue.
- Saved presets per event class (load/save/delete).

Read APIs:
- Parameter discovery: `GET /middleman/marshal/parameters?event_class=...`
- Async model search: `GET /middleman/marshal/search-model?model_class=...&query=...`

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
