# Case Manager Module

The Case Manager module is an AI-powered IT Support orchestration engine. It transforms linear ticket processing into a dynamic pipeline using the **Decision Engine**.

> **See also:**
> - [Executive Technical Overview](CASE_MANAGER_EXECUTIVE.md) — Safety, cost controls, decision flows, benefits to Clients and Technicians
> - [Architecture Reference](CASE_MANAGER_ARCHITECTURE.md) — Service architecture, strategy pattern, DTOs, event flow, resilience layer, state machine, database schema
> - [Implementation Critique](CASE_MANAGER_CRITIQUE_2026_03.md) — Implementation analysis and verification record

## Architecture: Decision Engine

The Decision Engine analyzes incoming support tickets (conversations) to intelligently determine the best course of action. It leverages multiple stages of processing and routes the ticket to specific strategies based on historical context, knowledge base matches, and AI-driven intake.

### Processing Pipeline

1.  **History Lookup (`HistoryService`)**: Automatically retrieves the customer's past tickets (up to a configurable limit) and detects recurring issues based on keyword overlap (default $\ge$ 30%).
2.  **KB Concierge (`KnowledgeEngine`)**:
    - Extracts distinct keywords from the ticket subject and body.
    - Queries the KnowledgeBase module using a fast semantic search.
    - Evaluates and ranks the results using a Gemini AI model to find high-confidence article matches.
3.  **Endpoint Health**: Resolves the customer's managed endpoint via `action1_device_cache` and fetches device health telemetry via `RmmBridgeService`.
4.  **Insightful Intake (`CaseManagerAiService`)**: Enriches the basic ticket info with the gathered history and KB results, performing an initial AI analysis to determine the user's intent.
5.  **Strategy Routing**: Analyzes the intake output to determine the ticket's optimal path.
6.  **Strategy Execution**: The matched strategy applies its logic, generating customer-facing messages or tech briefings.
7.  **Technician Briefing**: For tickets requiring human intervention, creates a comprehensive markdown brief summarizing the problem, context, and AI recommendations.

### Available Strategies

*   **Provide KB Article (`provide_kb_article`)**: Triggered when a KB article match exceeds the confidence threshold (e.g., 0.85). Proposes an automatic reply linking the client to the relevant documentation.
*   **Reopen and Link (`reopen_and_link`)**: Triggered when a highly similar recent ticket is found in the customer's history. Warns technicians about repeated problems and links to the past context.
*   **Immediate Remediation (`immediate_remediation`)**: Triggered when the issue is clear and actionable (e.g., "Reset my password", "Printer offline"). Scaffolds actionable remote troubleshooting scripts via RMM integration.
*   **Triage and Clarify (`triage_and_clarify`)**: A fallback or deliberate strategy when the user's request is vague. Engages the user with clarifying questions up to a strict limit (Rule of 3) before escalating.
*   **Propose Ticket Split (`propose_ticket_split`)**: Triggered when the AI detects two or more distinct issues in a single ticket (`issues` array with ≥ 2 entries). Proposes splitting the ticket into separate conversations so each issue can be triaged independently.
    *   **Output state:** `awaiting_split_confirmation`
    *   **`needsHumanApproval`:** Defaults to `true` unless `casemanager_features.auto_respond_split` is enabled. When approval is required, the split proposal is held as a draft until a technician reviews it.
*   **Route to Technician (`route_to_technician`)**: Triggered when the problem is clear (`has_clear_problem` is true) and the AI explicitly recommends technician routing (`suggested_strategy === 'route_to_technician'`). Immediately escalates to a human technician with a pre-built briefing.
    *   **Output state:** `ready_for_tech`
    *   **`needsHumanApproval`:** Always `true` — every technician-routed ticket requires human approval before the client-facing acknowledgement is sent.

### State Machine

The `CaseRecord` progresses through the following states:

| State | Description |
|---|---|
| `new` | Record created, no processing has started. |
| `triaging` | AI pipeline is actively analyzing the ticket. |
| `awaiting_clarity` | AI has sent clarifying questions; waiting for the customer to reply. `HandleCustomerReplied` monitors for responses and re-triggers the Decision Engine. |
| `awaiting_split_confirmation` | Customer has been asked to confirm a proposed ticket split. `HandleCustomerReplied` monitors for affirmative/negative replies; affirmative → `split_completed`, negative → re-triage via Decision Engine. |
| `ready_for_tech` | Triage complete; a technician can now work the case using the AI-generated briefing. |
| `split_completed` | Original ticket successfully split into child tickets. Terminal state. Set by `HandleCustomerReplied::performTicketSplit()`. |
| `api_error_needs_human` | Gemini API failed after retries; a technician must manually triage. Set by `CheckCaseApiErrorJob` via the `AiPipelineFailureHandler` trait. An internal note and alert are generated automatically. |

### Key Technical Concepts

*   **Multi-Model Configuration**: By default, distinct AI tasks map to different model tiers. Keyword extraction, KB ranking, and intake default to fast models (`gemini-2.5-flash`), whereas detailed resolution and tech briefings use complex models (`gemini-2.5-pro`).
*   **Thought Signatures**: Captures the AI's internal reasoning (`thinkingConfig: { includeThoughts: true }`) in a JSON column natively, ensuring reasoning continuity across follow-up replies from the customer.
*   **Immutable Context DTOs**: `DecisionContext` and `StrategyResult` enforce immutability, ensuring each stage leaves an exact trace.

## Requirements

*   PHP 8.2+
*   Laravel 12
*   **KnowledgeBase Module** (Optional but highly recommended for the KB Concierge feature)
*   **Action1 / RMM Module** (Optional for diagnostics features)
*   Gemini API Key configured in the Settings UI

## Feature Flags

The module's behavior can be toggled via the Case Manager Settings -> Decision Engine page:

*   **Decision Engine Enabled**: Toggles between the new dynamic decision engine and the legacy linear pipeline.
*   **KB Concierge Enabled**: Toggles advanced pre-search against the Knowledge Base.
*   **History Lookup Enabled**: Toggles automatic retrieval of a user's previous support tickets.
*   **Thought Signatures Enabled**: Toggles capturing the AI's intermediate reasoning string.
*   **Auto Respond Clarity** (`casemanager_features.auto_respond_clarity`): Default `false`. When disabled, clarifying questions produced by `triage_and_clarify` are held as draft replies awaiting technician approval. When enabled, clarifying questions are sent to the customer automatically.
*   **Auto Respond Split** (`casemanager_features.auto_respond_split`): Default `false`. When disabled, ticket split proposals produced by `propose_ticket_split` require human approval before the message is sent to the customer. When enabled, split proposals are sent automatically.

## Decision Engine Scope

### What It Can Do

The Decision Engine is an **assessment and routing system**. On every incoming email it:

1. **Assesses** the ticket — extracts structured data (category, confidence, business impact, affected users, asset mapping) from unstructured email text.
2. **Enriches** with context — gathers customer history, Knowledge Base matches, and endpoint health telemetry.
3. **Routes** to a strategy — selects the optimal resolution path from 6 strategies.
4. **Drafts** a client-facing response — when the strategy calls for customer communication (clarifying questions, split proposals, KB article links).
5. **Briefs** the technician — generates a structured markdown briefing summarizing everything the AI has gathered.

### What It Decides

The AI produces a structured intake result containing:

| Field | Maps To | Description |
|---|---|---|
| `has_clear_problem` | Is the problem clear? | Whether the issue can be understood without further questions. |
| `issues[].includes_requester` | Is the issue on behalf of someone else? | Whether the requester is the person affected, or asking for another user/group. |
| `issues[].category` | Hardware, software, or something else? | Categorized as `OS`, `App`, `Hardware`, `Network`, `Account`, or other. |
| `issues[].impact_radius` | Who is affected? (blast radius) | `one` (individual), `several` (team), or `all` (organization-wide). |
| `issues[].business_impact` | Is there a work stoppage? | `work_halted`, `work_degraded`, or `informational`. |
| `category` + AI reasoning | Is this a security event? | If the AI detects security indicators, it sets category accordingly and routes to `route_to_technician` for immediate human review. |
| `confidence` | How certain is the AI? | 0.0–1.0 score driving strategy selection thresholds. |
| `suggested_strategy` | What should happen next? | The AI's recommendation, which may be overridden by rule-based priority logic. |

These fields are the structured equivalent of the original triage intake matrix — the AI extracts them from free-text emails automatically rather than requiring a form.

### How It Responds

The Decision Engine has **two response modes** controlled by feature flags:

#### Mode 1: Draft & Approve (Default — Human-in-the-Loop)

When `auto_respond_clarity` and `auto_respond_split` are `false` (default):

1. The strategy generates a `clientFacingMessage` (e.g., clarifying questions).
2. The message is stored as a `draft_reply_generated` entry in the activity log.
3. **No email is sent** to the customer.
4. A technician reviews the draft in the sidebar and approves it via the `POST /case-manager/cases/{caseId}/approve-draft` endpoint.
5. On approval, the message is sent as a real email reply via `ReplyToConversationAction`.

#### Mode 2: Auto-Respond (Opt-In)

When `auto_respond_clarity` is `true`:

1. The strategy generates the message with `needsHumanApproval = false`.
2. The message is sent immediately as an email reply.
3. Activity log records `ai_reply_sent`.

| Strategy | Auto-Respond Flag | Default | What Gets Sent |
|---|---|---|---|
| `triage_and_clarify` | `auto_respond_clarity` | `false` | Up to 3 targeted clarifying questions |
| `propose_ticket_split` | `auto_respond_split` | `false` | Split confirmation request listing detected issues |
| `provide_kb_article` | — | Always draft | KB article link + explanation |
| `immediate_remediation` | — | Always draft | Remediation acknowledgement |
| `reopen_and_link` | — | Always draft | Recurrence notification |
| `route_to_technician` | — | Always draft | Routing acknowledgement |

#### Draft Approval API

**Endpoint:** `POST /case-manager/cases/{caseId}/approve-draft`
**Permission:** `manage_case_manager`
**Behavior:** Reads the most recent `draft_reply_generated` activity log entry for the case, sends it as a customer-facing email via `ReplyToConversationAction`, and records a `draft_reply_approved` activity log entry with the approving user's identity.

## Commands

### `CheckGeminiModelsCommand`

Proactive Gemini model lifecycle management. Runs daily via the Laravel scheduler. Checks the Gemini API `/models` endpoint to detect:

- **Sunset models** — a configured model is no longer listed in the API.
- **Deprecated models** — a `deprecationTime` attribute has been set.
- **Newer major versions** — e.g., we use 2.5 but 3.0 is available.

Dispatches a `casemanager_model_deprecation` alert via the Alerts module for any findings.

**Feature flag:** `casemanager_features.model_lifecycle_check` (Options table with config fallback, default `true`). When disabled, the command returns `SUCCESS` early without making any API calls.

## Fern Pipeline

The **Fern Pipeline** is a parallel AI triage path designed for lightweight, fast initial processing of incoming conversations.

### Entry Point

`HandleFernConversationCreated` listens for `CustomerCreatedConversation` events. It is a queued listener (`ShouldQueue`) with 3 retries and 30-second backoff.

### FernCaseRecord

Fern uses its own model (`FernCaseRecord`, table `fern_case_records`) — separate from the main `CaseRecord`. State lifecycle:

| State | Description |
|---|---|
| `pending` | Record created, no processing done yet. |
| `triage` | Active triage in progress. |
| `diagnostics` | Awaiting further diagnostic data. |
| `ready_for_tech` | Ready for a human technician to act. |
| `resolved` | Resolved by Fern without human intervention. |
| `ignored` | Sender is not in scope per audience-targeting rules. No AI processing was performed. |

### Processing Pipeline

1. **Feature Flag Guard** — `casemanager_fern_enabled` (Options table + config fallback). If disabled, the listener returns immediately.
2. **Audience Targeting** (`AudienceTargetingService`) — Pre-flight check against allowlist/blocklist rules. Rejected senders are transitioned to `ignored`.
3. **Customer History** (`HistoryService`) — Retrieves the customer's recent ticket history via `getHistorySummary()` for AI context enrichment.
4. **Triage Router** (`CaseManagerAiService::runFernTriage()`) — Runs the AI triage with ticket context, history summary, and asset telemetry sourced from the Action1 device cache when an endpoint match is available.
5. **Budget Controls** (`FernBudgetService`) — Enforces monthly cost cap, work-hour throttling for non-critical actions, and a daily budget curve to prevent front-loading spend.

### Error Handling

On failure, the listener dispatches a delayed error check via `AiPipelineFailureHandler` and re-throws for queue retry. The `failed()` method adds an internal error note and fires a `casemanager_api_error` alert.

## Resilience Layer

The module incorporates a multi-layered resilience strategy to handle Gemini API failures gracefully.

### AiPipelineFailureHandler Trait

Used by `HandleConversationCreated`, `HandleCustomerReplied`, and `HandleFernConversationCreated`. Provides:

- **`handleApiFailure()`** — Transitions the `CaseRecord` to `api_error_needs_human` (only if still in an early/waiting stage), adds an internal FreeScout thread note for technician visibility, dispatches a `casemanager_api_error` alert, and records an `api_error_transition` activity log entry.
- **`dispatchDelayedErrorCheck()`** — Dispatches `CheckCaseApiErrorJob` with a configurable delay (default 5 minutes, configurable via `casemanager_decision_engine.api_error_check_delay_minutes`).

### CheckCaseApiErrorJob

A delayed safety-net job that fires after the configured timeout. Behavior:

- If the case has progressed beyond an early stage (left the `new`, `triaging`, or `awaiting_split_confirmation` states), the job is a no-op — retries resolved the issue.
- If the case is still stuck, it calls `handleApiFailure()` to transition to `api_error_needs_human` and notify relevant parties.
- The job is intentionally idempotent — multiple dispatches for the same case are harmless.

## UI

The integrated Case Manager Sidebar (Vue/Alpine) updates dynamically to show:
- The AI's Suggested Path with animated badging.
- Expandable AI reasoning.
- Customer history overview.
- Top KB Article suggestions with confidence bounds.
