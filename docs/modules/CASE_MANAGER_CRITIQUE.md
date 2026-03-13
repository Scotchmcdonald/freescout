# Case Manager Module — Implementation Critique

> **Audience:** Engineering Team, Technical Leadership
> **Last Updated:** 2026-03-05
> **Scope:** Honest assessment of the current implementation — strengths, weaknesses, incomplete features, and recommended improvements.

---

## Summary

The Case Manager module demonstrates strong architectural fundamentals: immutable DTOs, a clean strategy pattern, event-driven processing with retry semantics, a well-designed resilience layer, and strict data isolation. PHPStan level 9 compliance across the entire module is a genuine achievement.

However, the implementation has significant gaps in feature completeness, test coverage, and internal consistency. Several subsystems are scaffolded but non-functional. This document catalogs every known issue with severity ratings and recommended actions.

---

## Severity Definitions

| Severity | Meaning |
|---|---|
| **Critical** | Affects correctness, data integrity, or could cause production incidents. |
| **High** | Feature is advertised or relied upon but not functional. |
| **Medium** | Technical debt that increases maintenance cost or hinders future development. |
| **Low** | Cosmetic, organizational, or nice-to-have improvements. |

---

## 1. ~~No Test Coverage~~ — ~~Critical~~ RESOLVED

**Status:** **Resolved.** Full test suite in place as of March 2026. **418 tests pass, 0 failures** (918 assertions). Covers DTOs, models, strategies, services, listeners, jobs, controllers, and traits across all pipelines.

**Phase 1 — Foundational unit tests:**

- **DTO unit tests:** `GeminiResult` (`succeeded()`/`failed()` branches), `StrategyResult` (`withMetadata()`, `withBriefing()`, `label()`, `hasClientMessage()`, `toArray()`).
- **Strategy unit tests:** All 6 strategies tested for `supports()` (positive + negative) and `execute()` (correct `strategyName`, `nextState`, `needsHumanApproval`, key metadata).
- **DecisionEngine routing test:** `resolveStrategy()` priority chain verified — multi-issue → KB match → recurrence → unclear → clear+confident → AI fallback → ultimate fallback.
- **FernBudgetService test:** `canAfford()` (monthly limit, ops priority, work-hour throttle, budget curve), `recordCost()` persistence to `FernCaseRecord`.

**Phase 2 — State machine, resilience, listeners:**

- **CaseRecordTransitionTest:** `transitionTo()` in-memory update, DB persistence, activity log creation, default/explicit actor, all known transitions, `todo()` marker for future guard enforcement.
- **CheckCaseApiErrorJobTest:** Stuck case transitions (`triaging`, `new`, `awaiting_split_confirmation` → `api_error_needs_human`), progressed/missing/non-guard-list state no-ops.
- **HandleSplitConfirmationTest:** Affirmative → `ready_for_tech`, negative → re-triage, ambiguous → decline path.
- **HandleFernConversationCreatedTest:** Fern disabled guard, audience targeting rejection, happy path with HistoryService, null customerId, triage failure handling.

**Phase 3 — RMM, diagnostics, callbacks (items #3 & #4):**

- **RmmBridgeServiceTest (13 tests):** `resolveEndpointByEmail()` exact match/no-match/empty/table-not-exists/most-recent-device. `resolveOrganizationForEndpoint()`. `fetchEndpointHealth()`. `detectScripts()` keyword matching. `allDiagnosticsComplete()`.
- **CheckDiagnosticTimeoutJobTest (5 tests):** Timeout marking, already-complete/progressed/missing-case no-ops, partial dispatch.
- **ProcessDiagnosticResultJobTest (5 tests):** Output parsing, activity logging, all-complete chaining, idempotency guard, missing-diagnostic guard.
- **ProcessCompletedDiagnosticsJobTest (5 tests):** Summary building, state transition, idempotency, no-diagnostics guard, partial results with timeouts.
- **DiagnosticCallbackControllerPestTest (7 tests):** HMAC-SHA256 validation, result storage + job dispatch, 404/200/400 edge cases.

**Phase 4 — Remaining service layer (March 2026):**

- **HandleConversationCreatedTest (~20 tests):** Full event listener coverage — listener disabled guard, audience rejection, case creation, Decision Engine dispatch, context construction, error handling with `handleApiFailure`, activity log creation, history lookup integration.
- **HandleCustomerRepliedTest (~14 tests):** Multi-turn clarity flow — split confirmation detection, re-triage via Decision Engine, `strategy_pivoted_after_reply` log, state transitions, error handling.
- **GeminiClientTest (32 tests):** `parseJsonResponse()` (clean JSON, markdown fence stripping, invalid input), config resolvers (`apiKey`, `endpoint`, `maxTokens`, `temperature`, `modelForStage`, `thoughtSignaturesEnabled`), `generate()` (API key guard, HTTP success/failure, thought signature extraction, synthetic signature fallback), prompt logging success/failure, context caching (cache hit/miss), payload structure (systemInstruction, thinkingConfig, legacy mode, thought signature context), circuit breaker and rate limiter error propagation, per-stage model selection.
- **CaseManagerAiServiceTest (~20 tests):** `extractKeywords`, `rankKbResults`, `insightfulIntake` (asset data, KB results, endpoint health, thought signature passthrough), `technicianBriefing` (RMM/general prompts, ticket thread), legacy pipeline stages (`triage`, `research`, `resolution`, `kb_assessment`), `callGeminiForStage` bridge, `runFernTriage` (budget gate, context caching, cost recording, log context attribution), AI name customization.
- **PromptLogQuickWinTest (16 tests):** Model relationships, `record()` static factory, `CaseRecord` scopes, activity log helpers.
- **GenerateOptionsTest (8 tests):** DTO defaults, field assignments, immutability.
- **TicketSidebarControllerTest (17 tests):** `show()` (no-case guard, full data, diagnostics, quick wins, decision engine fields, history summary), `approveQuickWin()` (state guard, activity log, 404 guard), `rejectQuickWin()` (approved override, activity log), authentication enforcement.
- **DashboardControllerTest (13 tests):** `index()` (view response, metrics, AI name, empty dashboard, auth redirect), `eventLog()` (activity entries, prompt log entries, chronological merge, 404, state transition humanisation, known action humanisation, auth guard, `conversation_id` in response).
- **AudienceTargetingServiceTest (13 tests):** All 4 audience paths — unknown (allow/deny), internal admin/finance (allow/deny), client with active contract (allow/deny), prospect with no/expired contract (allow/deny).
- **AiPipelineFailureHandlerTest (11 tests):** `handleApiFailure()` state transitions (new/triaging/awaiting_split_confirmation → `api_error_needs_human`, terminally-progressed states ignored), activity log entry, `dispatchDelayedErrorCheck()` job dispatch, `getErrorCheckDelayMinutes()` config fallback/Options override/minimum enforcement.
- **DecisionEngineProcessTest (full pipeline, ~16 tests):** History gathering (enabled/disabled feature flag, null customerId skip), KB concierge (enabled/disabled), endpoint health enrichment (success + graceful failure), intake defaults (`triage_and_clarify` on empty/null result), technician briefing (generated for non-awaiting states, skipped for `awaiting_clarity`), `StrategyResult` structure validation.

**Bug discovered and fixed during Phase 4:**
- `DashboardController::eventLog()` used `Eloquent\Collection::merge()` to combine activity log entries with prompt log entries after both had been mapped to plain arrays. Eloquent's `merge()` calls `getKey()` on each item, which fails on arrays, causing a 500 error whenever prompt log entries were present. Fixed by using `->toBase()->merge()` to convert to a base `Collection` before merging.

**Remaining gap:**
- End-to-end integration test for the full diagnostic flow (launch → Action1 API → callback → processing → state transition). Currently covered unit-by-unit; a true integration harness with `Http::fake()` mocking the Action1 webhook cycle is still missing.

---

## 2. ~~Fern Budget Tracking Is Non-Functional~~ — ~~High~~ RESOLVED

**Status:** **Resolved.** `FernBudgetService::recordCost()` now accepts an optional `?int $fernCaseId` parameter. When provided, it increments `estimated_cost_usd` and `total_tokens_used` on the corresponding `FernCaseRecord`. `CaseManagerAiService::runFernTriage()` passes `$this->fernCaseId` to `recordCost()`, so `getCurrentMonthUsage()` (which sums `estimated_cost_usd`) now reports accurate values.

**Previously:** `recordCost()` calculated costs but never persisted them. `getCurrentMonthUsage()` always returned 0, rendering all budget controls (monthly cap, work-hour throttling, budget curve) non-functional.

---

## 3. ~~Endpoint Health (Stage 3) Not Implemented~~ — ~~High~~ RESOLVED

**Status:** **Resolved.** Stage 3 of the `DecisionEngine` pipeline now resolves the customer's managed endpoint and fetches device health telemetry.

**Implementation (March 2026):**
- `RmmBridgeService::resolveEndpointByEmail()` queries the `action1_device_cache` table, matching the customer email against the `last_user` column (ordered by `last_seen_at` desc to pick the most recently active device).
- `RmmBridgeService::fetchEndpointHealth()` retrieves OS type/version, online status, hostname, and last-seen timestamp for the resolved endpoint.
- `DecisionEngine` Stage 3 calls `resolveEndpointByEmail()` → `fetchEndpointHealth()` → `$context->withEndpointHealth()`, enriching the `DecisionContext` for downstream strategies and AI prompts.
- `HandleConversationCreated` also resolves the endpoint for diagnostic dispatch, with a fallback to `resolveEndpointByEmail()` when Stage 3 health data is unavailable.
- All resolution is wrapped in try/catch — endpoint resolution failure is never fatal to the pipeline.

**Test coverage:** 13 unit tests in `RmmBridgeServiceTest` covering exact match, no-match, empty email, table-not-exists graceful fallback, multiple-device recency ordering, organization resolution, health fetch, and diagnostic detection.

**Previously:** Stage 3 was a commented-out TODO block. No endpoint data was ever gathered, and the AI made decisions without device context.

---

## 4. ~~RMM Script Execution Is a Placeholder~~ — ~~High~~ RESOLVED

**Status:** **Resolved.** `RmmBridgeService::runMatchingDiagnostics()` now executes real diagnostic scripts on managed endpoints via the Action1 API, with a webhook phone-home pattern for result collection and timeout handling.

**Implementation (March 2026):**

**Script execution:**
- `RmmBridgeService::launchDiagnostic()` resolves the endpoint's `organization_id` via `resolveOrganizationForEndpoint()`, builds a signed callback URL, and calls `Action1Service::runScript()` with parameters (`DiagnosticID`, `CallbackURL`, `AuthToken`) that the CMD script uses to phone home results.
- `Action1Service::runScript()` extended with optional `?array $parameters` argument for passing script parameters to the Action1 API payload.
- The `action1_job_id` from the Action1 API response is stored on the `Diagnostic` model for traceability.

**Webhook phone-home (result collection):**
- `DiagnosticCallbackController` (Action1 module) receives raw CMD output via `POST /api/action1/diagnostic-callback/{diagnosticId}`.
- `VerifyDiagnosticCallbackSignature` middleware validates HMAC-SHA256 signature (`X-Diagnostic-Auth` header) using a shared secret.
- On valid callback: stores `raw_output`, sets status to `completed`, dispatches `ProcessDiagnosticResultJob`.

**Result processing pipeline:**
- `ProcessDiagnosticResultJob` parses `=== SECTION NAME ===` delimited CMD output into structured `parsed_result` JSON. Logs activity. When all diagnostics for a case complete, dispatches `ProcessCompletedDiagnosticsJob`.
- `ProcessCompletedDiagnosticsJob` collects all results, builds a human-readable diagnostic summary (with emoji status indicators), appends to `decision_reasoning`, and transitions the case from `diagnostics_running` → `ready_for_tech`.

**Timeout handling:**
- `CheckDiagnosticTimeoutJob` is dispatched with a configurable delay (default 120s) when diagnostics launch. Marks pending/running diagnostics as `timed_out` if they haven't phoned home. Dispatches `ProcessCompletedDiagnosticsJob` for partial results.
- All jobs are idempotent with state guards.

**Configuration:**
- `action1.diagnostic_callback_url` — base URL for phone-home callbacks.
- `action1.diagnostic_webhook_secret` — HMAC signing secret.

**Test coverage:**
- `ProcessDiagnosticResultJobTest` (5 tests) — parsing, activity logging, completion chaining, already-complete guard, missing diagnostic guard.
- `ProcessCompletedDiagnosticsJobTest` (5 tests) — summary building, state transition, idempotency, no-diagnostics guard, partial results with timeouts.
- `CheckDiagnosticTimeoutJobTest` (5 tests) — timeout marking, already-complete no-op, progressed-case no-op, missing-case no-op, partial completion dispatch.
- `DiagnosticCallbackControllerPestTest` (7 tests) — HMAC validation (valid/invalid/missing), result storage + job dispatch, 404 for unknown ID, 200 for already-complete, 400 for invalid ID.

**Previously:** No API calls were made to Action1. No polling or callback mechanism existed. Diagnostic records were created with status `running` but never completed, leaving technicians with perpetually "running" diagnostics.

---

## 5. Ticket Splitting Not Implemented — High

**Status:** `ProposeTicketSplitStrategy` detects multiple distinct issues and drafts a confirmation message. `HandleCustomerReplied` detects split confirmations. But:

- The actual ticket splitting (creating new conversations from the original) is not implemented.
- The `awaiting_split_confirmation` state exists but there is no handler that processes the confirmation and creates the split tickets.

**Impact:** Users are asked to confirm a ticket split, but confirming does nothing. The ticket remains in `awaiting_split_confirmation` until a technician manually intervenes.

**Recommendation:** Implement a `performTicketSplit()` method that:
1. Creates new `Conversation` records for each distinct issue.
2. Creates corresponding `CaseRecord` entries.
3. Dispatches each through the Decision Engine independently.
4. Transitions the original case to a `split_completed` state with metadata linking to child tickets.

---

## 14. ~~No Draft Reply Approval Endpoint~~ — ~~High~~ RESOLVED

**Status:** **Resolved.** `TicketSidebarController::approveDraftReply()` now provides an API endpoint for technicians to approve and send AI-generated draft replies.

**Implementation (March 2026):**
- `POST /case-manager/cases/{caseId}/approve-draft` reads the most recent `draft_reply_generated` activity log entry.
- Sends the message as a customer-facing email via `ReplyToConversationAction`.
- Records a `draft_reply_approved` activity log entry with the approving technician's identity.
- Permission-gated via `manage_case_manager`.
- Test coverage: 7 tests (happy path, no-draft 404, empty message 422, missing conversation 404, multiple drafts picks latest, non-existent case 404, auth guard).

**Previously:** When `auto_respond_clarity = false` (default), draft replies were stored in the activity log but had no mechanism for approval or sending. Technicians had to manually compose and send replies, losing the AI-generated content.

---

## 15. ~~`auto_respond_split` Missing From Config~~ — ~~Low~~ RESOLVED

**Status:** **Resolved.** `casemanager.features.auto_respond_split` added to `Config/config.php` with env var `CASEMANAGER_AUTO_RESPOND_SPLIT` and default `false`.

**Previously:** `ProposeTicketSplitStrategy` referenced this config key, but it was not declared in the config file. The fallback default (`false`) meant it worked correctly, but the omission was inconsistent with how all other feature flags were declared.

---

## 6. Code Duplication Between AI Services — ~~Medium~~ RESOLVED

**Status:** **Resolved.** `GeminiClient` now provides shared Gemini HTTP transport, payload construction, response parsing (with markdown fence stripping), rate limiting, circuit breaking, context caching, and prompt logging. `FernAiService` has been deleted. `CaseManagerAiService` is the unified AI service for both pipelines, delegating all API calls to `GeminiClient`. Fern triage is handled via `CaseManagerAiService::runFernTriage()`. Audience targeting has been extracted to `AudienceTargetingService`.

**Bugs fixed during resolution:**
1. Fern missing JSON fence-stripping — `GeminiClient::parseJsonResponse()` handles fences consistently.
2. Fern missing rate limiter — `GeminiClient` applies both rate limiter (1500/hour) and circuit breaker.
3. Fern ignoring Options table for model selection — `GeminiClient::modelForStage()` checks Options first.

---

## 7. ~~Fern Pipeline Has Hardcoded Stubs~~ — ~~Medium~~ RESOLVED

**Status:** **Resolved.** The three unused Fern stage methods (`runDiagnostics`, `runHandoff`, `runKbAssessment`) and their hardcoded stubs (e.g. `available_scripts`) have been deleted along with `FernAiService`. The `historySummary` stub has been replaced with a real `HistoryService::getHistorySummary()` call in `HandleFernConversationCreated`. The `assetTelemetry` empty-array stub remains as a documented follow-up pending RMM bridge integration.

**Previously:** `historySummary` and `assetTelemetry` were both hardcoded as empty arrays. History is now wired; asset telemetry remains a follow-up per items #4 and #7 (partial).

---

## 8. ~~No Circuit Breaker or Rate Limiter~~ — ~~Medium~~ RESOLVED

**Status:** **Resolved.** `GeminiClient` wraps every Gemini API call with both `CircuitBreakerService` (5-failure threshold, 60s recovery) and `RateLimiterService` (1500 calls/hour). Both pipelines (main and Fern) now share the same resilience infrastructure.

**Previously:** The main pipeline used both services directly; the Fern pipeline only used circuit breaker and skipped rate limiting. Now unified in `GeminiClient`.

---

## 9. ~~`model_lifecycle_check` Feature Flag Is Ignored~~ — ~~Low~~ RESOLVED

**Status:** **Resolved.** `CheckGeminiModelsCommand::handle()` now checks the `casemanager_features.model_lifecycle_check` flag (via Options table with config fallback) at the top of `handle()`. Returns `SUCCESS` early when disabled.

**Previously:** The command always ran when invoked by the scheduler, ignoring the feature flag.

---

## 10. ~~Legacy Pipeline Methods Remain~~ — ~~Low~~ RESOLVED

**Status:** **Resolved.** `CaseManagerAiService` methods `triage()`, `research()`, `resolution()`, and `assessForKnowledgeBase()` now carry `@deprecated` annotations directing callers to use the Decision Engine pipeline instead. The methods are retained for backward compatibility with `HandleConversationCreated::processWithLegacyPipeline()` and `HandleCustomerReplied::processWithLegacyPipeline()`. Scheduled for removal once the legacy pipeline path is confirmed unused in production.

**Previously:** The methods existed without deprecation markers, increasing the risk of new code depending on the legacy path.

---

## 11. ~~Documentation Index Does Not Reference CaseManager~~ — ~~Low~~ RESOLVED

**Status:** **Resolved.** `DOCUMENTATION_INDEX.md` now includes a dedicated **Case Manager (AI Decision Engine)** section under **📦 Module Documentation** with entries for all four documents:
- **[Module Overview](docs/modules/CASE_MANAGER.md)** — Feature summary, pipeline stages, strategy descriptions, configuration reference
- **[Executive Technical Overview](docs/modules/CASE_MANAGER_EXECUTIVE.md)** — Safety design, cost controls, decision flows, benefits to Clients and Technicians
- **[Architecture Reference](docs/modules/CASE_MANAGER_ARCHITECTURE.md)** — Service architecture, strategy pattern, DTOs, event flow, resilience layer, state machine, database schema
- **[Implementation Critique](docs/modules/CASE_MANAGER_CRITIQUE.md)** — Honest assessment of gaps, incomplete features, and prioritized recommendations

---

## 12. ~~Existing Module Documentation Is Outdated~~ — ~~Low~~ RESOLVED

**Status:** **Resolved.** `docs/modules/CASE_MANAGER.md` has been updated to include:

- All 6 strategies documented (`propose_ticket_split` and `route_to_technician` added).
- State machine section with all states including `awaiting_split_confirmation` and `api_error_needs_human`.
- Fern Pipeline section covering `HandleFernConversationCreated`, `FernCaseRecord` lifecycle, `AudienceTargetingService`, `FernBudgetService`, and `HistoryService` wiring.
- Feature flags table with `casemanager_features.auto_respond_split`.
- Commands section documenting `CheckGeminiModelsCommand` and its `model_lifecycle_check` feature flag.
- Resilience Layer section covering `AiPipelineFailureHandler` trait and `CheckCaseApiErrorJob` pattern.

**Previously:** Only 4 of 6 strategies were documented. No coverage of the resilience layer, Fern pipeline, `api_error_needs_human`/`awaiting_split_confirmation` states, budget controls, `CheckGeminiModelsCommand`, or alert integration.

---

## 13. ~~`StrategyResult` Reconstruction Pattern~~ — ~~Low~~ RESOLVED

**Status:** **Resolved.** `StrategyResult` now has `withMetadata(array $merge): self` and `withBriefing(array $overrides): self` methods following the same clone-and-extend pattern as `DecisionContext`. `DecisionEngine::process()` has been refactored to use `$result->withBriefing([...])` and `$result->withMetadata([...])` instead of manually reconstructing all properties.

**Previously:** Both reconstruction sites in `DecisionEngine::process()` manually copied all fields from the previous `StrategyResult` instance, risking field-mapping errors when new properties were added.

---

## Strengths Worth Preserving

Not everything is a problem. The following architectural choices should be maintained:

1. **Immutable DTOs** — `DecisionContext` and `StrategyResult` are `final readonly class` with clone-and-extend. This prevents accidental mutation across pipeline stages.

2. **Strategy Pattern** — Clean separation of routing logic from execution logic. Adding a new strategy requires one class implementing `StrategyInterface` and one registration line in `DecisionEngine`.

3. **Core Blindness** — Complete data isolation from FreeScout core tables. The module can be uninstalled cleanly.

4. **PHPStan Level 9** — The entire module passes with 0 errors. This is unusually strict for a Laravel module and catches type errors, null safety issues, and dead code.

5. **Resilience Layer** — The `AiPipelineFailureHandler` trait + `CheckCaseApiErrorJob` pattern is well-designed: idempotent, multi-channel notification, and self-healing when retries succeed. Additionally, all CaseManager listeners are covered by the app-wide `ResilientListener` trait’s fallback `failed()` mechanism and the `listener.failed` alert type (see [Event Robustness WIP](../development/WIP/event_robustness.md)).

6. **Activity Logging** — Every state transition, strategy decision, and error is recorded with structured payloads. Full audit trail.

7. **Per-stage Model Selection** — Fine-grained cost control without sacrificing quality where it matters.

8. **Thought Signatures** — Genuine innovation for multi-turn AI interactions in a helpdesk context. Preserves reasoning continuity without replaying the full conversation.

---

## Priority Recommendations

| Priority | Item | Effort | Impact |
|---|---|---|---|
| ~~1~~ | ~~Write unit + integration tests (#1)~~ | ~~Large~~ | **RESOLVED** — 418 tests, 0 failures; all service/controller/listener/trait layers covered |
| ~~2~~ | ~~Implement Fern budget tracking (#2)~~ | ~~Small~~ | **RESOLVED** — `recordCost()` now writes to `FernCaseRecord` |
| ~~3~~ | ~~Implement circuit breaker (#8)~~ | ~~Medium~~ | **RESOLVED** — unified in `GeminiClient` |
| ~~4~~ | ~~Extract shared GeminiClient (#6)~~ | ~~Medium~~ | **RESOLVED** — `GeminiClient` + `FernAiService` merged |
| 5 | Complete ticket splitting (#5) | Medium | High — feature is user-facing but broken |
| ~~6~~ | ~~Wire RMM script execution (#4)~~ | ~~Large~~ | **RESOLVED** — webhook phone-home + timeout pipeline |
| ~~7~~ | ~~Wire Fern enrichment stages (#7)~~ | ~~Small~~ | **RESOLVED** — `HistoryService::getHistorySummary()` wired; `assetTelemetry` stub documented as follow-up pending RMM bridge |
| ~~8~~ | ~~Add `StrategyResult::with*()` methods (#13)~~ | ~~Small~~ | **RESOLVED** — `withMetadata()` + `withBriefing()` added |
| ~~9~~ | ~~Fix feature flag check (#9)~~ | ~~Trivial~~ | **RESOLVED** — guard added to `CheckGeminiModelsCommand` |
| ~~10~~ | ~~Update existing documentation (#11, #12)~~ | ~~Small~~ | **RESOLVED** — both `DOCUMENTATION_INDEX.md` entries and `CASE_MANAGER.md` content updated |
| ~~11~~ | ~~Add draft reply approval endpoint (#14)~~ | ~~Small~~ | **RESOLVED** — `approveDraftReply()` endpoint + route + tests |
| ~~12~~ | ~~Add `auto_respond_split` to config (#15)~~ | ~~Trivial~~ | **RESOLVED** — added to features config array |
