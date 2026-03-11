# Case Manager Module — Architecture Reference

> **Audience:** Software Architects, Senior Engineers  
> **Last Updated:** 2026-03-04  
> **PHPStan Level:** 9 — 0 errors  
> **Runtime:** PHP 8.2+ / Laravel 12 / FreeScout  

---

## Table of Contents

1. [Module Structure](#module-structure)
2. [Service Architecture](#service-architecture)
3. [Decision Engine Pipeline](#decision-engine-pipeline)
4. [Strategy Pattern](#strategy-pattern)
5. [Data Transfer Objects](#data-transfer-objects)
6. [Event-Driven Architecture](#event-driven-architecture)
7. [Resilience Layer](#resilience-layer)
8. [State Machine](#state-machine)
9. [Dual Pipeline: Main vs Fern](#dual-pipeline-main-vs-fern)
10. [Per-Stage Model Selection](#per-stage-model-selection)
11. [Prompt Architecture](#prompt-architecture)
12. [Knowledge Engine Integration](#knowledge-engine-integration)
13. [Core Blindness Principle](#core-blindness-principle)
14. [Configuration & Feature Flags](#configuration--feature-flags)
15. [Database Schema](#database-schema)
16. [Alerts Module Integration](#alerts-module-integration)
17. [Scheduling & Background Jobs](#scheduling--background-jobs)

---

## Module Structure

```
Modules/CaseManager/
├── Config/config.php              # All defaults; overridable via Options table
├── Console/
│   └── CheckGeminiModelsCommand.php   # Daily model lifecycle check
├── DataTransferObjects/
│   ├── AssetContext.php            # Device/software context DTO
│   ├── DecisionContext.php         # Immutable pipeline context
│   ├── InsightfulIntakeSchema.php  # Stage 4 JSON schema definition
│   └── StrategyResult.php         # Immutable strategy output
├── Enums/
│   ├── AssetEnvironment.php       # Desktop / Laptop / Server / Mobile / VM
│   ├── BusinessImpact.php         # Critical / High / Medium / Low / None
│   ├── ImpactRadius.php           # Individual / Team / Department / Organization
│   ├── IssueCategory.php          # Hardware / Software / Network / Security / ...
│   ├── IssueType.php              # Incident / ServiceRequest / ChangeRequest / ...
│   └── SuggestedStrategy.php      # Typed enum of strategy machine names
├── Events/                        # (Currently empty — events come from core)
├── Http/                          # Controllers, routes, middleware
├── Jobs/
│   └── CheckCaseApiErrorJob.php   # Delayed error assessment (idempotent)
├── Listeners/
│   ├── HandleConversationCreated.php    # Main pipeline entry point
│   ├── HandleCustomerReplied.php        # Multi-turn: clarify → re-intake
│   ├── HandleConversationClosed.php     # KB assessment loop on resolution
│   └── HandleFernConversationCreated.php # Fern pipeline entry point
├── Models/
│   ├── CaseRecord.php             # Primary AI metadata model
│   ├── FernCaseRecord.php         # Fern pipeline metadata
│   ├── Diagnostic.php             # RMM diagnostic results
│   ├── QuickWin.php               # AI-suggested quick fixes
│   ├── ActivityLog.php            # State transition audit trail
│   ├── FernDiagnostic.php         # Fern diagnostic results
│   └── PromptLog.php              # Full prompt/response logging
├── Services/
│   ├── GeminiClient.php            # Shared Gemini HTTP transport, parsing, caching (scoped)
│   ├── CaseManagerAiService.php   # Unified AI service — main + Fern pipelines (scoped)
│   ├── AudienceTargetingService.php # Fern audience pre-flight gate (scoped)
│   ├── FernBudgetService.php      # Monthly budget + throttling
│   ├── DecisionEngine.php         # Strategy pattern orchestrator (singleton)
│   ├── HistoryService.php         # Customer history + recurrence (singleton)
│   ├── KnowledgeEngine.php        # KB Concierge integration
│   ├── RmmBridgeService.php       # Action1 / RMM bridge
│   ├── Action1Service.php         # Direct Action1 API client
│   └── Strategies/
│       ├── StrategyInterface.php
│       ├── ProvideKbArticleStrategy.php
│       ├── ReopenAndLinkStrategy.php
│       ├── TriageAndClarifyStrategy.php
│       ├── ImmediateRemediationStrategy.php
│       ├── ProposeTicketSplitStrategy.php
│       └── RouteToTechnicianStrategy.php
├── Traits/
│   └── AiPipelineFailureHandler.php   # Shared error handling trait
├── Policies/                      # Authorization policies
├── Providers/                     # Service provider + event bindings
└── Database/Migrations/           # 7 migration files
```

---

## Service Architecture

### Service Container Bindings

| Service | Scope | Rationale |
|---|---|---|
| `GeminiClient` | **Scoped** (per-request) | Shared HTTP transport, rate limiting, circuit breaking, context caching. Scoped for consistent state per request. |
| `CaseManagerAiService` | **Scoped** (per-request) | Holds mutable `conversationId`, `caseId`, and `fernCaseId` for prompt logging. Unified service for both main and Fern pipelines. |
| `AudienceTargetingService` | **Scoped** | Pre-flight audience gate for Fern pipeline. Pure business logic (User/Contract queries). |
| `DecisionEngine` | **Singleton** | Stateless orchestrator. Strategies are resolved via `app()` at construction. |
| `HistoryService` | **Singleton** | Stateless service — all state comes from method arguments. |
| `KnowledgeEngine` | Resolved fresh | Depends on KnowledgeBase module availability. |
| `FernBudgetService` | Resolved fresh | Reads config on construction. |

### Dependency Graph

```
HandleConversationCreated
  ├── CaseManagerAiService  (injected)
  ├── RmmBridgeService      (injected)
  ├── DecisionEngine        (injected)
  │     ├── CaseManagerAiService
  │     ├── HistoryService
  │     ├── KnowledgeEngine
  │     │     └── CaseManagerAiService (KB keyword extraction + ranking)
  │     └── RmmBridgeService
  └── ReplyToConversationAction (injected, core FreeScout)
```

---

## Decision Engine Pipeline

The `DecisionEngine::process()` method is the central orchestrator. It accepts an immutable `DecisionContext` and returns a `StrategyResult`.

### Stage Flow

```
                    ┌───────────────────┐
                    │  DecisionContext  │  (immutable seed)
                    └────────┬──────────┘
                             │
              ┌──────────────▼──────────────┐
Stage 1       │     HistoryService          │  → context.withHistory()
              │  getCustomerHistory()       │
              │  detectRecurrence()         │
              └──────────────┬──────────────┘
                             │
              ┌──────────────▼──────────────┐
Stage 2       │     KnowledgeEngine         │  → context.withKbResults()
              │  searchForTicket()          │
              │    → keyword extraction     │  (Gemini Flash)
              │    → KB semantic search     │  (KnowledgeBase module)
              │    → AI-ranked results      │  (Gemini Flash)
              └──────────────┬──────────────┘
                             │
              ┌──────────────▼──────────────┐
Stage 3       │     Endpoint Health         │  → context.withEndpointHealth()
              │  (Future — RMM Integration) │
              └──────────────┬──────────────┘
                             │
              ┌──────────────▼──────────────┐
Stage 4       │  CaseManagerAiService       │  → intakeResult{}
              │  insightfulIntake()         │  (Gemini Flash)
              │                             │
              │  Returns structured JSON:   │
              │  - has_clear_problem (bool) │
              │  - category (IssueCategory) │
              │  - confidence (0.0-1.0)     │
              │  - summary (string)         │
              │  - suggested_strategy       │
              │  - clarifying_questions     │
              │  - client_facing_draft      │
              │  - issue_type (IssueType)   │
              │  - business_impact          │
              │  - distinct_issues[]        │
              └──────────────┬──────────────┘
                             │
              ┌──────────────▼──────────────┐
Stage 5       │  resolveStrategy()          │  → strategyName
              │  Priority-ordered routing:  │
              │   1. provide_kb_article     │  (KB confidence ≥ 0.85)
              │   2. reopen_and_link        │  (recurring issue detected)
              │   3. propose_ticket_split   │  (multiple distinct issues)
              │   4. immediate_remediation  │  (clear + actionable)
              │   5. triage_and_clarify     │  (vague request)
              │   6. route_to_technician    │  (ultimate fallback)
              └──────────────┬──────────────┘
                             │
              ┌──────────────▼──────────────┐
Stage 6       │  Strategy::execute()        │  → StrategyResult
              │  Each strategy implements   │
              │  StrategyInterface          │
              └──────────────┬──────────────┘
                             │
              ┌──────────────▼──────────────┐
Stage 7       │  technicianBriefing()       │  (Gemini Pro)
              │  Skipped for states:        │
              │    - awaiting_clarity       │
              │    - awaiting_split_confirm │
              │  Appends:                   │
              │    - briefing_text          │
              │    - client_facing_message  │
              │    - escalation_recommend   │
              │    - estimated_complexity   │
              └──────────────┬──────────────┘
                             │
                    ┌────────▼─────────┐
                    │  StrategyResult  │  (immutable output)
                    └──────────────────┘
```

### Strategy Resolution Logic

`resolveStrategy()` evaluates strategies in a **fixed priority order** using rule-based overrides on top of the AI's `suggested_strategy`:

1. **KB Article Match** — If `kbResults.confidence >= kb_confidence_threshold` (default 0.85).
2. **Reopen and Link** — If `history` contains a recurring match within the recurrence window.
3. **Propose Ticket Split** — If `intakeResult.distinct_issues` contains 2+ issues.
4. **Immediate Remediation** — If `has_clear_problem == true` and issue is actionable.
5. **Triage and Clarify** — If `has_clear_problem == false` or confidence is low.
6. **Route to Technician** — Catch-all fallback when no automated path is appropriate.

Rule-based conditions take precedence over the AI's suggestion. If the AI recommends `immediate_remediation` but KB confidence is 0.92, the system routes to `provide_kb_article`.

---

## Strategy Pattern

### Interface Contract

```php
interface StrategyInterface
{
    public function execute(DecisionContext $context, array $intakeResult): StrategyResult;
    public function supports(DecisionContext $context, array $intakeResult): bool;
}
```

- `execute()` performs the strategy logic and returns an immutable `StrategyResult`.
- `supports()` is a predicate used by the engine for candidate evaluation (the engine may still override).

### Strategy Implementations

| Strategy | `nextState` | `needsHumanApproval` | Key Behavior |
|---|---|---|---|
| `ProvideKbArticleStrategy` | `ready_for_tech` | `true` | Builds response linking to KB article. Includes confidence score and article metadata. |
| `ReopenAndLinkStrategy` | `ready_for_tech` | `true` | Creates internal note linking to prior ticket. Warns tech about recurrence pattern. |
| `TriageAndClarifyStrategy` | `awaiting_clarity` | `true` | Uses `client_facing_draft` from Stage 4 intake (no second API call). Enforces Rule of 3 max questions. |
| `ImmediateRemediationStrategy` | `ready_for_tech` | `true` | Triggers RMM diagnostics if keyword match found. Proposes quick-win solutions. |
| `ProposeTicketSplitStrategy` | `awaiting_split_confirmation` | `true` | Enumerates each distinct issue from intake. Drafts split confirmation message for client. |
| `RouteToTechnicianStrategy` | `ready_for_tech` | `true` | Generates routing note + brief reasoning. Safe default — no automated client contact. |

All strategies default `needsHumanApproval = true`. Auto-sending requires the `auto_respond_clarity` feature flag to be enabled AND the strategy to explicitly set `needsHumanApproval = false`.

---

## Data Transfer Objects

### `DecisionContext` (immutable, `final readonly class`)

Accumulates enrichment data as it flows through the pipeline using a **clone-and-extend** pattern:

```php
$context = DecisionContext::fromConversation($conversation, $case);
$context = $context->withHistory($history);      // returns new instance
$context = $context->withKbResults($kbResults);   // returns new instance
$context = $context->withEndpointHealth($health); // returns new instance
$context = $context->withThoughtSignature($sig);  // returns new instance
$context = $context->withAssetContext($assets);    // returns new instance
```

Properties: `case`, `conversation`, `ticketSubject`, `ticketBody`, `customerEmail`, `customerId`, `history`, `kbResults`, `endpointHealth`, `thoughtSignature`, `assetContext`.

### `StrategyResult` (immutable, `final readonly class`)

Returned by every strategy. Carries:

- `strategyName` — Machine name for persistence.
- `nextState` — Target state for `CaseRecord::transitionTo()`.
- `aiResult` — Raw structured AI response.
- `thoughtSignature` — Gemini thought chain for multi-turn continuity.
- `clientFacingMessage` — Draft reply (null if none generated).
- `internalNote` — Internal note for technicians.
- `needsHumanApproval` — Gate for auto-send.
- `metadata` — Strategy-specific data (models used, KB article IDs, related ticket IDs, etc.).

### `InsightfulIntakeSchema`

Defines the JSON schema for Stage 4 output validation. Uses PHP backed enums (`IssueCategory`, `IssueType`, `BusinessImpact`, `ImpactRadius`, `AssetEnvironment`, `SuggestedStrategy`) to constrain the AI's response to valid values.

### `AssetContext`

Structured representation of a customer's device: hostname, OS, last seen, uptime, assigned software, environment type.

---

## Event-Driven Architecture

### Event → Listener Mapping

| FreeScout Core Event | Listener | Queue Config | Purpose |
|---|---|---|---|
| `CustomerCreatedConversation` | `HandleConversationCreated` | tries: 3, backoff: 30s | Main pipeline entry |
| `CustomerRepliedToConversation` | `HandleCustomerReplied` | tries: 3, backoff: 30s | Multi-turn: re-intake on customer reply |
| `ConversationStatusChanged` (closed) | `HandleConversationClosed` | tries: 2, backoff: 60s | KB assessment loop |
| `CustomerCreatedConversation` | `HandleFernConversationCreated` | tries: 2, backoff: 30s | Fern pipeline entry |

All listeners implement `ShouldQueue`. Events are dispatched by the core FreeScout application; the module registers listeners via its service provider.

### Multi-Turn Flow (HandleCustomerReplied)

When a customer replies to a ticket in `awaiting_clarity` state:

1. Retrieve the existing `CaseRecord` and its `thought_signature`.
2. Rebuild `DecisionContext` with the new reply body + prior thought signature.
3. Re-run `insightfulIntake()` — Gemini receives the accumulated context.
4. Re-route through strategy selection (may now resolve to a different strategy).
5. Update `CaseRecord` with new results.

Thought signatures enable **reasoning continuity** across turns — Gemini sees its prior chain-of-thought.

### KB Assessment Loop (HandleConversationClosed)

When a ticket is closed (resolved), the system optionally:

1. Runs a KB assessment AI prompt (Gemini Flash).
2. Determines if a new KB article should be created, an existing one updated, or no action needed.
3. Stores the assessment on the `CaseRecord`.
4. Feature-flagged via `kb_loop_enabled`.

---

## Resilience Layer

### Architecture

```
┌──────────────────────────────────────────────────────────────────┐
│ Listener (e.g., HandleConversationCreated)                      │
│   uses AiPipelineFailureHandler                                │
│                                                                  │
│   try {                                                          │
│       runDecisionEnginePipeline()                                │
│   } catch (Throwable $e) {                                       │
│       dispatchDelayedErrorCheck(caseId, conversationId)          │
│       throw $e  ← triggers queue retry (up to 3x)              │
│   }                                                              │
│                                                                  │
│   failed() method → handleApiFailure()                          │
│       → CaseRecord → api_error_needs_human                      │
│       → Internal FreeScout note                                 │
│       → Alert dispatch                                          │
│       → Activity log entry                                      │
└──────────────────────────────────────────────────────────────────┘
          │
          │ (after 5 minutes)
          ▼
┌──────────────────────────────────────────────────────────────────┐
│ CheckCaseApiErrorJob (idempotent)                               │
│                                                                  │
│   if (case.state NOT IN [new, triaging,                         │
│       awaiting_split_confirmation]) → no-op (retries won)       │
│   else → handleApiFailure() (case is stuck)                     │
└──────────────────────────────────────────────────────────────────┘
```

### `AiPipelineFailureHandler` Trait

Shared across all 4 listeners. Provides:

- `handleApiFailure()` — Transitions case to `api_error_needs_human`, adds internal note, dispatches alert, records activity log. Guards on states: `new`, `triaging`, `awaiting_split_confirmation`.
- `addInternalErrorNote()` — Creates a FreeScout internal note (Thread type `NOTE`) on the conversation.
- `dispatchApiErrorAlert()` — Fires a structured alert via `AlertService` with type `casemanager_api_error`.
- `dispatchDelayedErrorCheck()` — Dispatches `CheckCaseApiErrorJob` with configurable delay.
- `getErrorCheckDelayMinutes()` — Reads from Options table → config fallback → default 5.

### `CheckCaseApiErrorJob`

- **Intentionally idempotent** — safe to dispatch multiple times for the same case.
- **tries: 1** — the job itself must not retry, as it's a safety net for the listener's retries.
- If the case has progressed beyond `new`/`triaging`/`awaiting_split_confirmation`, the job exits as a no-op (retries succeeded).
- If the case is still stuck, it calls `handleApiFailure()` with a descriptive timeout error.

---

## State Machine

`CaseRecord.state` is a string column with the following known values:

| State | Meaning | Set By |
|---|---|---|
| `new` | Case just created, not yet processed. | `CaseRecord::create()` |
| `triaging` | Decision Engine is processing. | Listener on pipeline entry |
| `awaiting_clarity` | Waiting for customer to answer clarifying questions. | `TriageAndClarifyStrategy` |
| `awaiting_split_confirmation` | Waiting for customer to confirm ticket split. | `ProposeTicketSplitStrategy` |
| `ready_for_tech` | Pipeline complete — technician can pick up. | Most strategies |
| `in_progress` | Technician is actively working. | Manual / UI |
| `resolved` | Case resolved. | Manual / UI |
| `api_error_needs_human` | AI pipeline failed — manual routing required. | `AiPipelineFailureHandler` |

Transitions are recorded in `ActivityLog` with format: `state_transition:{old}->{new}`.

### Transition Guards

- `HandleConversationCreated` skips if `state` is not `new` or `triaging` (prevents duplicate processing on retry).
- `CheckCaseApiErrorJob` only acts on `new`, `triaging`, or `awaiting_split_confirmation` states.
- `handleApiFailure()` only transitions from `new`, `triaging`, or `awaiting_split_confirmation`.
- `HandleCustomerReplied::failed()` only triggers error handling on `new`, `triaging`, or `awaiting_split_confirmation` states.

---

## Dual Pipeline: Main vs Fern

The module runs two independent AI pipelines:

| Aspect | Main Pipeline | Fern Pipeline |
|---|---|---|
| **Entry Point** | `HandleConversationCreated` | `HandleFernConversationCreated` |
| **AI Service** | `CaseManagerAiService` | `CaseManagerAiService` (via `runFernTriage()`) |
| **Model** | `CaseRecord` | `FernCaseRecord` |
| **Budget** | No explicit cap | $25/month via `FernBudgetService` |
| **Purpose** | Full decision engine — triage, route, brief | Proactive greeting + lightweight triage |
| **Audience** | Configurable per customer type | Same audience config |
| **Feature Flag** | `casemanager.enabled` | `casemanager.fern.enabled` |

### Fern Pipeline Isolation

The Fern pipeline is intentionally isolated:

- **Never disrupts email flow** — operates on its own model (`fern_case_records`).
- Fern errors do not affect the main pipeline.
- Budget exhaustion stops only Fern; the main pipeline continues unaffected.

### Code Duplication Note

Both pipelines now share a unified `GeminiClient` for all Gemini API interactions. `FernAiService` has been merged into `CaseManagerAiService` (the `runFernTriage()` method handles Fern-specific triage with budget controls). Audience targeting has been extracted into a dedicated `AudienceTargetingService`.

---

## Per-Stage Model Selection

Each AI stage is independently configurable to use a different Gemini model:

```php
'models' => [
    'keyword_extraction'  => 'gemini-2.5-flash',    // Fast, cheap
    'insightful_intake'   => 'gemini-2.5-flash',    // Fast triage
    'kb_ranking'          => 'gemini-2.5-flash',    // Structured comparison
    'technician_briefing' => 'gemini-2.5-pro',      // Quality matters
    'kb_assessment'       => 'gemini-2.5-flash',    // Background task
    'research'            => 'gemini-2.5-flash',    // Focused
    'resolution'          => 'gemini-2.5-pro',      // Client-facing accuracy
    'triage'              => 'gemini-2.5-flash',    // Legacy stage
]
```

Model names are resolved at runtime via:
1. `Options` table (admin UI override).
2. Config file default.
3. Ultimate fallback: `gemini-2.5-flash`.

The `ai_model_used` JSON column on `CaseRecord` tracks exactly which model was used for each stage of a specific case, enabling cost auditing.

---

## Prompt Architecture

### System Prompt Strategy

| Stage | Prompt Location | Method |
|---|---|---|
| Keyword Extraction | `config.php` inline | Simple — no dynamic context |
| KB Ranking | `config.php` inline | Simple — article list injected as user message |
| Insightful Intake | `CaseManagerAiService::insightfulIntakeSystemPrompt()` | Complex — inline in code, references `InsightfulIntakeSchema` |
| Technician Briefing | `CaseManagerAiService::technicianBriefing*SystemPrompt()` | Two variants: RMM-enriched and general |
| KB Assessment | `config.php` inline | Post-resolution analysis |
| Triage / Research / Resolution | `config.php` inline | Legacy pipeline prompts |

### Thought Signatures

When enabled (`thought_signatures_enabled`), every Gemini call includes `thinkingConfig: { includeThoughts: true }`. The AI's intermediate reasoning is captured and stored as a JSON column on `CaseRecord`. On follow-up turns, the accumulated thought signature is injected into the next prompt, enabling **reasoning continuity without reprocessing the full history**.

### Schema Enforcement

Stage 4 (Insightful Intake) uses `InsightfulIntakeSchema` to generate a JSON schema injected into the Gemini system prompt. This constrains the AI's output to:

- Validated enum values (via PHP backed enums).
- Required fields with explicit types.
- Bounded arrays (e.g., `distinct_issues` max items).

---

## Knowledge Engine Integration

`KnowledgeEngine` bridges the CaseManager to the optional KnowledgeBase module:

1. **Keyword Extraction** — Gemini Flash extracts 3–5 technical search terms from the ticket.
2. **Semantic Search** — Calls `KnowledgeBase` module's search API with extracted keywords.
3. **AI Ranking** — Gemini Flash ranks returned articles by relevance, assigning confidence scores.
4. **Threshold Filter** — Only articles with confidence ≥ `kb_confidence_threshold` (default 0.85) are considered matches.

If the KnowledgeBase module is not installed, KB Concierge is silently disabled.

---

## Core Blindness Principle

The module maintains strict separation from FreeScout's core data model:

### Module-Owned Tables (7 migrations)
- `case_manager_cases` — Primary case record + AI metadata.
- `case_manager_diagnostics` — RMM diagnostic results.
- `case_manager_quick_wins` — AI-suggested quick fixes.
- `case_manager_activity_log` — Full state transition audit trail.
- `case_manager_prompt_logs` — Complete prompt + response audit.
- `fern_case_records` — Fern pipeline metadata.
- `fern_diagnostics` — Fern diagnostic results.

### Core Table Access

The module **reads** core tables (`conversations`, `threads`, `users`, `customers`) but **never writes** to them, with one controlled exception:

- `ReplyToConversationAction` is used to post internal notes and (when auto-respond is enabled) customer-facing replies. This is FreeScout's own action class — the module never manipulates threads directly.

---

## Configuration & Feature Flags

All configuration lives in `Config/config.php` with a consistent override pattern:

```
Config default → Options table (admin UI) → ENV variable
```

### Feature Flags

| Flag | Default | Purpose |
|---|---|---|
| `decision_engine_enabled` | `true` | Toggle between Decision Engine and legacy linear pipeline. |
| `kb_concierge_enabled` | `true` | Enable/disable KB search during pipeline. |
| `history_lookup_enabled` | `true` | Enable/disable customer history gathering. |
| `thought_signatures_enabled` | `true` | Enable/disable Gemini thought capture. |
| `auto_respond_clarity` | `false` | Allow auto-send of clarifying questions (safety-critical). |
| `auto_run_diagnostics` | `true` | Auto-launch RMM diagnostics when matched. |
| `kb_loop_enabled` | `true` | Post-resolution KB assessment. |
| `quick_wins_enabled` | `true` | AI quick-win suggestion generation. |
| `model_lifecycle_check` | `true` | Daily Gemini model deprecation monitoring. |

### Decision Engine Tuning

| Parameter | Default | Purpose |
|---|---|---|
| `kb_confidence_threshold` | 0.85 | Minimum KB match confidence to trigger `provide_kb_article`. |
| `max_clarifying_questions` | 3 | Maximum questions before escalation (Rule of 3). |
| `history_limit` | 10 | Max prior tickets to retrieve. |
| `recurrence_window_days` | 90 | Days to look back for recurring patterns. |
| `api_error_check_delay_minutes` | 5 | Delay before checking if a case is stuck. |

---

## Database Schema

### `case_manager_cases`

| Column | Type | Purpose |
|---|---|---|
| `id` | bigint PK | |
| `conversation_id` | bigint FK | Links to FreeScout conversation |
| `assigned_user_id` | bigint FK nullable | Assigned technician |
| `state` | string | Current state machine value |
| **9-point checklist** | | |
| `greeted` | boolean | |
| `clear_problem_statement` | boolean | |
| `clear_ownership` | boolean | |
| `asked_clarifying_questions` | boolean | |
| `ran_diagnostics` | boolean | |
| `ran_problem_resolution_prompt` | boolean | |
| `assessed_for_quick_wins` | boolean | |
| `assessed_for_related_kb_articles` | boolean | |
| `assessed_article_relevance` | boolean | |
| **AI results** | | |
| `triage_result` | JSON | Structured intake output |
| `research_result` | JSON | Research stage output |
| `resolution_result` | JSON | Resolution draft output |
| `kb_assessment_result` | JSON | Post-resolution KB assessment |
| `detected_category` | string nullable | AI-detected issue category |
| `ai_confidence` | float nullable | Confidence score (0.0–1.0) |
| `needs_escalation` | boolean | Escalation flag |
| **Decision Engine** | | |
| `decision_path` | string nullable | Strategy machine name |
| `decision_reasoning` | text nullable | Briefing text / reasoning |
| `thought_signature` | JSON nullable | Gemini thought chain |
| `historical_context` | JSON nullable | Customer history snapshot |
| `kb_search_result` | JSON nullable | KB Concierge results |
| `endpoint_health` | JSON nullable | RMM endpoint data |
| `ai_model_used` | JSON nullable | Per-stage model tracking |
| **Timestamps** | | |
| `created_at`, `updated_at` | timestamp | |
| `deleted_at` | timestamp nullable | Soft delete |

### Key Relationships

```
CaseRecord 1──* Diagnostic
CaseRecord 1──* QuickWin
CaseRecord 1──* ActivityLog
CaseRecord 1──* PromptLog
CaseRecord *──1 Conversation  (read-only FK)
CaseRecord *──1 User          (assigned technician)
```

---

## Alerts Module Integration

The CaseManager integrates with the Alerts module via two module-specific alert types plus the app-wide `listener.failed` type:

| Alert Type Code | Category | Trigger |
|---|---|---|
| `casemanager_api_error` | `ai` | AI pipeline failure after all retries exhausted. |
| `casemanager_model_deprecation` | `ai` | `CheckGeminiModelsCommand` detects sunset/deprecated/newer model. |
| `listener.failed` | `system` | Any `ShouldQueue` listener using `ResilientListener` fails permanently. See [Event Robustness WIP](../development/WIP/event_robustness.md). |

Alerts are dispatched via `AlertService::dispatch(AlertPayload)` and include actionable metadata: case ID, conversation ID, error details, and direct action URLs.

---

## Scheduling & Background Jobs

### Scheduled Commands

| Command | Schedule | Purpose |
|---|---|---|
| `casemanager:check-gemini-models` | Daily | Queries Gemini API `/models` endpoint. Detects sunsets, deprecations, and available upgrades. Dispatches alerts. |

### Queued Jobs

| Job | Dispatch Trigger | Delay | Retries | Purpose |
|---|---|---|---|---|
| `CheckCaseApiErrorJob` | Listener catch block | 5 min (configurable) | 1 | Safety net for stuck cases after API failures. |

### Listener Queue Config

| Listener | Tries | Backoff |
|---|---|---|
| `HandleConversationCreated` | 3 | 30s |
| `HandleCustomerReplied` | 3 | 30s |
| `HandleConversationClosed` | 2 | 60s |
| `HandleFernConversationCreated` | 2 | 30s |
