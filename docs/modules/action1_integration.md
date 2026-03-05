# Architecture Reference: Action1 Diagnostic Callback Integration

> **Status:** ✅ Implemented (March 2026)  
> **Last Updated:** 2026-03-05  
> This document reflects the **actual production implementation**, not the original design sketch.

---

## Overview

CaseManager triggers diagnostic CMD scripts on managed Windows endpoints via the Action1 RMM API. Instead of polling Action1 for results (rate-limit-inefficient), the endpoint uses `curl.exe` to POST its raw output directly back to a Laravel webhook ("phone-home" pattern).

---

## Security Model

### HMAC-SHA256 per-Diagnostic Token

Each diagnostic receives a unique auth token generated at launch:

```
AuthToken = hash_hmac('sha256', diagnosticId, DIAGNOSTIC_WEBHOOK_SECRET)
```

- The token is **specific to a single diagnostic ID** — it cannot be reused for a different diagnostic.
- `hash_equals()` is used server-side to prevent timing attacks.
- Missing secret or missing header → HTTP 401.
- The shared secret (`action1.diagnostic_webhook_secret`) lives in the environment and is never returned to clients.

### Body Size Cap (DoS Protection)

The controller enforces a **512 KB hard cap** on incoming payloads. Oversized payloads are truncated with a trailing notice rather than rejected, so partial results are still processed. CMD diagnostic output is typically 1–50 KB; 512 KB is a conservative upper bound.

### Known Trust-Boundary Consideration

The `AuthToken` is passed as a script parameter to Action1's automation API. Action1 automation logs may record it. This means: **anyone with Action1 admin access can see a diagnostic's auth token**. This is an accepted risk because:
- Action1 admin access is already a privileged internal role.
- Replay with a tampered body is blocked by the "already complete" idempotency guard on the controller.
- The token validates *who is calling* (the endpoint that was targeted), not *what they send*.

If stricter body integrity is required in future, the CMD script could include a second HMAC of the body:
`X-Body-Hash: hash_hmac('sha256', file_content, AuthToken)` — but this adds complexity to the CMD script and is not currently implemented.

---

## Implementation Reference

### Phase 1: Script Launch (Laravel → Action1 API)

**`RmmBridgeService::launchDiagnostic()`** — `Modules/CaseManager/Services/RmmBridgeService.php`

1. Creates a `Diagnostic` record with `status = 'pending'`.
2. Resolves the endpoint's `organization_id` from `action1_device_cache` via `resolveOrganizationForEndpoint()`.
3. Builds the callback URL: `{DIAGNOSTIC_CALLBACK_URL}/api/action1/diagnostic-callback/{diagnosticId}`.
4. Generates the auth token: `hash_hmac('sha256', $diagnosticId, $secret)`.
5. Calls `Action1Service::runScript()` with script parameters:

```php
[
    ['name' => 'DiagnosticID', 'value' => '42',           'type' => 'String'],
    ['name' => 'CallbackURL',  'value' => 'https://…/42', 'type' => 'String'],
    ['name' => 'AuthToken',    'value' => 'abc123…',      'type' => 'String'],
]
```

6. Updates the `Diagnostic` record to `status = 'running'` with the returned `action1_job_id`.
7. Dispatches `CheckDiagnosticTimeoutJob` (default 120 s delay) — handles the case where the endpoint never phones home.

**`Action1Service::runScript()`** — `Modules/Action1/Services/Action1Service.php`

Wraps the Action1 Automations API call with the MSP OAuth2 bearer token, rate limiter (60 req/hour), and circuit breaker. Returns the automation response including the Action1 job ID.

---

### Phase 2: Script Execution (Windows CMD on Managed Endpoint)

The Action1 script is stored in the Action1 Script Library with three string parameters:
`DiagnosticID`, `CallbackURL`, `AuthToken`.

**Canonical script structure:**

```cmd
@echo off
set DIAG_FILE=%TEMP%\diag_%DiagnosticID%_%RANDOM%.txt

:: ── Diagnostics ──────────────────────────────────────────────────────────
echo === IP CONFIGURATION === > %DIAG_FILE%
ipconfig /all >> %DIAG_FILE%
echo === PING TEST === >> %DIAG_FILE%
ping 8.8.8.8 -n 4 >> %DIAG_FILE%
echo === DISK USAGE === >> %DIAG_FILE%
wmic logicaldisk get caption,freespace,size /format:csv >> %DIAG_FILE%

:: ── Phone Home ───────────────────────────────────────────────────────────
curl -s -X POST ^
  -H "Content-Type: text/plain" ^
  -H "X-Diagnostic-Auth: %AuthToken%" ^
  --data-binary @%DIAG_FILE% ^
  "%CallbackURL%"

:: ── Cleanup ──────────────────────────────────────────────────────────────
del /f /q %DIAG_FILE%
exit /b 0
```

> **Note:** `curl.exe` ships by default in Windows 10 (1803+) and Server 2019+. No external dependency is needed.

---

### Phase 3: Callback Reception (Laravel)

**Route:** `POST /api/action1/diagnostic-callback/{diagnosticId}`  
**File:** `Modules/Action1/Routes/api.php`  
**Middleware:** `VerifyDiagnosticCallbackSignature`

**Middleware flow** (`Modules/Action1/Http/Middleware/VerifyDiagnosticCallbackSignature.php`):
1. Extract `{diagnosticId}` from route, `X-Diagnostic-Auth` from header.
2. Compute `hash_hmac('sha256', $diagnosticId, $secret)`.
3. `hash_equals()` comparison — reject with 401 on mismatch.

**Controller flow** (`Modules/Action1/Http/Controllers/DiagnosticCallbackController.php`):
1. Validate `$diagnosticId` is a positive integer → 400 if not.
2. Load `Diagnostic` → 404 if not found.
3. Return 200 if already in a terminal state (idempotency).
4. Read `$request->getContent()` — **truncated to 512 KB** if oversized (logged as warning).
5. Update `Diagnostic`: `raw_output`, `status = 'completed'`, `completed_at`.
6. Dispatch `ProcessDiagnosticResultJob`.
7. Return `202 Accepted`.

---

### Phase 4: Result Processing (Jobs)

| Job | Trigger | Behaviour |
|---|---|---|
| `ProcessDiagnosticResultJob` | Dispatched by callback controller | Parses `=== SECTION NAME ===` delimited CMD output into `parsed_result` JSON. Logs activity. When all diagnostics complete, dispatches `ProcessCompletedDiagnosticsJob`. |
| `ProcessCompletedDiagnosticsJob` | Dispatched when all diagnostics are done | Builds a human-readable summary (emoji status indicators), appends to `decision_reasoning`, transitions case `diagnostics_running → ready_for_tech`. |
| `CheckDiagnosticTimeoutJob` | Dispatched with delay at launch | Marks pending/running diagnostics as `timed_out` if they haven't phoned home. Dispatches `ProcessCompletedDiagnosticsJob` for partial results. |

All jobs are idempotent with state guards.

---

## Configuration

| Key | Purpose |
|---|---|
| `action1.diagnostic_callback_url` | Base URL for phone-home callbacks (e.g. `https://api.yourmsp.com`) |
| `action1.diagnostic_webhook_secret` | HMAC signing secret — strong random string, rotate if compromised |
| `casemanager.timings.rmm_timeout` | Seconds before timeout job marks diagnostics as `timed_out` (default: 120) |
| `casemanager.rmm_scripts` | JSON map of `keyword → {script_id, label}` (Options table override available) |

---

## Test Coverage

| File | Tests | Scope |
|---|---|---|
| `Modules/Action1/Tests/Feature/DiagnosticCallbackControllerPestTest.php` | 8 | HMAC middleware (valid/invalid/missing), controller 202/200/404/400, payload truncation |
| `Modules/CaseManager/Tests/Unit/Jobs/CheckDiagnosticTimeoutJobTest.php` | 5 | Timeout marking, no-ops for progressed/complete/missing cases, partial dispatch |
| `Modules/CaseManager/Tests/Unit/Jobs/ProcessDiagnosticResultJobTest.php` | 5 | Output parsing, activity logging, all-complete chaining, idempotency, missing-diagnostic guard |
| `Modules/CaseManager/Tests/Unit/Jobs/ProcessCompletedDiagnosticsJobTest.php` | 5 | Summary building, state transition, idempotency, no-diagnostics guard, partial results |
| `Modules/CaseManager/Tests/Unit/Services/RmmBridgeServiceTest.php` | 13 | Device resolution, endpoint health, org lookup, script detection |

**Remaining gap:** End-to-end integration test using `Http::fake()` to simulate the full cycle:
Action1 API trigger → callback POST → job chain → case state transition.

---

## Action1 Script Library Setup

1. In the Action1 UI, create a new **CMD** script.
2. Add three **String** parameters: `DiagnosticID`, `CallbackURL`, `AuthToken`.
3. Paste the canonical script body from Phase 2 above.
4. Note the script's ID (format: `uuid`) — add it to the `casemanager.rmm_scripts` config.
5. Set `action1.diagnostic_callback_url` and `action1.diagnostic_webhook_secret` in `.env`.
