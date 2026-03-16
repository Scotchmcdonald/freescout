# Case Manager Module — Engineering Critique (March 2026)

> **Date:** 2026-03-15
> **Scope:** Second-pass review of the module following the initial critique sprint. Supersedes `CASE_MANAGER_CRITIQUE.md`.
> **PHPStan:** Level 9 — 0 code errors

---

## What Was Fixed

18 issues across four severity tiers were identified and remediated in this pass. The most significant:

| Finding | Fix |
|---|---|
| **C-1** Gemini API key in URL query string (logged in plain text by every reverse proxy) | Key moved to `x-goog-api-key` request header across all call sites in `GeminiClient` and `CheckGeminiModelsCommand`. Production key rotated after deployment to all environments that had made Gemini API calls. |
| **C-2** `FernSettingsController::update()` validated inputs but discarded them silently | `update()` now persists all three fields via `Option::updateOrCreate()`. |
| **C-3** `$name` undefined variable in `ProvideKbArticleStrategy` and `ImmediateRemediationStrategy` on the happy path | `$name` resolved unconditionally at top of `execute()`, before any conditional blocks. |
| **C-4** `{uptime_threshold}` placeholder sent verbatim to Gemini in Fern triage prompt | Placeholder replaced with value read from Options table → config fallback. |
| **H-1** Thought signatures built on a non-existent API response field; always produced synthetic timestamps | Redesigned to capture actual `thought: true` part text from the Gemini response. |
| **H-2** `ReopenAndLinkStrategy` linked all customer history, not just recurrent tickets | Now uses `related_conversation_ids` from `HistoryService::detectRecurrence()` output. |
| **H-4** `FernBudgetService` cache key had no month/year component; stale at every month rollover | Cache key now includes `Y-m` suffix; `recordCost()` clears the same keyed entry. |
| **H-5** `AudienceTargetingService` bypassed the Options table entirely | Reads each flag from Options first, falls back to config. |
| **H-6** `HandleConversationClosed` posted duplicate error notes on each retry | Error note and alert dispatch moved exclusively to `failed()`; never fires on intermediate retries. |
| **H-7** Ticket split executed nothing; customer received a confirmation but one ticket remained | `performTicketSplit()` implemented — creates child Conversations, Threads, and CaseRecords in a DB transaction; transitions parent to `split_completed`. |
| **M-1/M-2** `DecisionEngine` singleton held stale `CaseManagerAiService` reference across queue jobs | `DecisionEngine` changed from `singleton` to `scoped`; strategies injected via constructor. |
| **M-3** `CaseManagerAiService` used `app()` service locator for `HistoryService` at call time | `HistoryService` injected via constructor; two inline `app()` calls removed. |
| **M-4** `FernCaseRecord::transitionTo()` accepted any arbitrary state string | `ALLOWED_STATE_TRANSITIONS` guard added; throws `LogicException` on invalid transitions. |
| **M-5** `DashboardController` built raw SQL from config-derived column names | Checklist fields filtered against `VALID_CHECKLIST_COLUMNS` allowlist before `selectRaw()`. |
| **M-7** `SettingsController` exposed the full plain-text Gemini API key in the settings view | View receives only the last 4 characters; `storeApiKeys()` skips write when the field is empty. |
| **M-8** `DecisionContext::fromConversation()` issued `exists()` + `first()` double query | `exists()` call removed; single `first()` with null-check used. |
| **M-9** `HandleConversationClosed` entered `pending_kb_review` before confirming KB module availability | `isEnabled()` guard added; case goes directly to `closed` when KB module is offline. |
| **L-1/L-2/L-3/L-4** Index duplication, string date comparison, empty `down()`, `FernDiagnostic` parity | Redundant index dropped via migration; Carbon comparison used; `down()` implemented; `FernDiagnostic` brought to parity with `Diagnostic`. |
| **R-2** PHPStan level 9 failures across `Action1ManageService`, `MspScriptService`, `ResilienceController`, and `Action1ScriptCallbackController` | `config()->string()` typed accessors used throughout; `/** @var */` annotations added for `cache()->get()` returns; `is_string()` narrowing guards replace bare casts. PHPStan now returns clean at level 9. |
| **R-3** `HandleFernConversationCreated` passed hardcoded `assetTelemetry: []` to `runFernTriage()` | `RmmBridgeService::getFernAssetTelemetry()` resolves the sender's endpoint from the Action1 device cache and returns cached health data; telemetry is now forwarded into `runFernTriage()` when available, empty array when no device match. |
| **R-4** No end-to-end integration test for the Action1 diagnostic callback flow | Added `DiagnosticFlowIntegrationTest` with a full `Http::fake()` harness: Action1 OAuth → `runScript` → webhook callback → `ProcessDiagnosticResultJob` → `ProcessCompletedDiagnosticsJob` → `ready_for_tech` state assertion. Multi-diagnostic wait logic covered in a second test. |
| **R-5** `CaseManagerAiService` exposed four `@deprecated` methods (`triage`, `research`, `resolution`, `assessForKnowledgeBase`) still called in production | Methods renamed to `analyzeTicketTriage`, `buildResearchSummary`, `draftResolution`, `evaluateKnowledgeBaseContribution`; all callers in `HandleCustomerReplied` and `KnowledgeEngine` updated; unit tests updated to match. |
| **R-6** Split child `CaseRecord`s linked to parent only via activity log payload | Nullable `parent_case_id` FK migration added; `CaseRecord` `$fillable`, `parentCase()` BelongsTo, and `childCases()` HasMany added; `performTicketSplit()` sets `parent_case_id` on each child record at creation. |

Full per-task implementation detail is preserved in git history.
