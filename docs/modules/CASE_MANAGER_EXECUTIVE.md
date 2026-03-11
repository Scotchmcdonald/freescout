# Case Manager Module — Executive Technical Overview

> **Audience:** Technical Executives, Engineering Leadership, MSP Stakeholders  
> **Last Updated:** 2025-07-15  
> **Module Status:** Production — Active Development  

---

## What It Does

The Case Manager module is an **AI-powered helpdesk decision engine** built on top of FreeScout. When a customer submits a support ticket, the module automatically analyzes the request, gathers contextual data, and routes the ticket to the optimal resolution path — all before a technician sees it.

The system acts as an intelligent first-responder that enriches every ticket with structured context, proposes solutions, and prepares technician briefings. **It never sends a message to a client without human approval unless explicitly configured to do so.**

---

## Safety-First Design

Every architectural decision in the Case Manager prioritizes safety over speed. The system is built around three foundational safety principles:

### 1. Human-in-the-Loop by Default

All six resolution strategies set `needsHumanApproval = true` by default. This means:

- AI-generated client-facing messages are stored as **draft replies**, not sent automatically.
- Technicians review, edit, and approve every outbound message.
- The `auto_respond_clarity` feature flag defaults to `false` — automatic replies require explicit opt-in.

### 2. Core Blindness

The module **never writes to FreeScout's core database tables** (conversations, threads, users). All AI metadata — triage results, confidence scores, thought signatures, model usage — is stored exclusively in the module's own `case_manager_*` tables. This guarantees:

- **Zero risk of data corruption** in the core helpdesk.
- Clean module uninstall with no orphaned data.
- Core FreeScout upgrades cannot break AI metadata.

### 3. Graceful Degradation on API Failure

When the Gemini API is unavailable or returns errors:

| Layer | Behavior |
|---|---|
| **Listener Retries** | Each event listener retries 3 times with 30-second backoff before failing. |
| **Delayed Error Check** | A background job fires after 5 minutes (configurable). If the case is still stuck, it transitions to `api_error_needs_human`. |
| **Internal Note** | An automatic internal note appears in the FreeScout conversation UI: *"⚠️ AI Triage failed due to API Error. Manual routing required."* |
| **Admin Alert** | A structured alert is dispatched to the Alerts module with a direct link to the affected case. |
| **No Silent Failures** | The case **never** silently disappears. It is surfaced to humans through at least two independent channels. |

If the entire AI pipeline collapses, **tickets still arrive in FreeScout normally** — the Case Manager simply cannot enrich them. Technicians work exactly as they would without the module.

---

## Cost Controls

AI API costs are managed through multiple layers of controls:

### Per-Stage Model Selection

Not every AI task requires the most powerful (and expensive) model. The system routes each stage to the appropriate tier:

| Stage | Default Model | Relative Cost | Rationale |
|---|---|---|---|
| Keyword Extraction | Gemini 2.5 Flash | $ | Simple extraction task |
| KB Article Ranking | Gemini 2.5 Flash | $ | Structured comparison |
| Insightful Intake (Triage) | Gemini 2.5 Flash | $ | Fast initial analysis |
| Research & Diagnostics | Gemini 2.5 Flash | $ | Focused investigation |
| Technician Briefing | Gemini 2.5 Pro | $$$ | Complex synthesis — quality matters |
| Resolution Drafting | Gemini 2.5 Pro | $$$ | Client-facing — accuracy critical |

All model assignments are configurable from the admin UI per-stage.

### Gemini API Parameters

- **Max tokens:** 4,096 per request (prevents runaway responses).
- **Temperature:** 0.3 (low creativity — deterministic, factual responses).

### Fern Pipeline Budget Cap

The secondary Fern pipeline (proactive communication) enforces a **$25/month hard budget limit** with intelligent throttling:

- **Work hours (7 AM – 5 PM, Mon–Fri):** Background tasks (KB assessment, analytics) are throttled to 0% — all budget is reserved for operational tasks (triage, diagnostics, handoff).
- **Off hours:** Background tasks run only when spending is under a linear budget curve projection.
- **Operational tasks** (triage, handoff, diagnostics) are always permitted until the hard monthly limit is reached.

### Audience Targeting

AI processing is restricted by customer type. By default:

| Customer Type | AI Triage Enabled |
|---|---|
| Unknown (not in database) | No |
| Prospects (no active contract) | No |
| Clients (active contract) | Yes |
| Internal staff | Yes |

This prevents budget waste on spam, unsolicited emails, or prospects.

---

## Decision Flow

Every incoming ticket follows a 7-stage pipeline:

```
┌──────────────────────────────────────────────────────────────────┐
│ Stage 1: History Lookup                                         │
│   Retrieves up to 10 prior tickets, detects recurring issues    │
├──────────────────────────────────────────────────────────────────┤
│ Stage 2: KB Concierge                                           │
│   Extracts keywords → Searches Knowledge Base → AI-ranks results│
├──────────────────────────────────────────────────────────────────┤
│ Stage 3: Endpoint Health (future RMM integration)               │
│   Will pull real-time device health from Action1 / RMM          │
├──────────────────────────────────────────────────────────────────┤
│ Stage 4: Insightful Intake                                      │
│   AI analyzes ticket + all gathered context → structured output │
├──────────────────────────────────────────────────────────────────┤
│ Stage 5: Strategy Routing                                       │
│   AI recommendation + rule overrides → select optimal strategy  │
├──────────────────────────────────────────────────────────────────┤
│ Stage 6: Strategy Execution                                     │
│   Chosen strategy generates messages, notes, and metadata       │
├──────────────────────────────────────────────────────────────────┤
│ Stage 7: Technician Briefing                                    │
│   Comprehensive markdown brief for the assigned technician      │
└──────────────────────────────────────────────────────────────────┘
```

### The Six Resolution Strategies

| Strategy | Trigger | What Happens |
|---|---|---|
| **Provide KB Article** | KB match confidence ≥ 85% | Drafts a reply linking the client to the matching documentation. |
| **Reopen and Link** | Highly similar recent ticket found in customer history | Warns the technician about a recurring issue and links to prior context. |
| **Triage and Clarify** | Vague or incomplete request | Asks the client up to 3 clarifying questions (Rule of 3), then escalates. |
| **Immediate Remediation** | Clear, actionable problem (e.g., "reset my password") | Scaffolds RMM diagnostic scripts and proposes quick-win fixes. |
| **Propose Ticket Split** | Multiple distinct issues detected in one ticket | Identifies each issue and proposes splitting into separate tickets. |
| **Route to Technician** | Complex issue or no automated path is appropriate | Passes to a human with a full briefing — the safe default. |

**Route to Technician** is the ultimate fallback. When in doubt, the system always hands off to a human rather than attempting an automated response.

---

## Benefits to Clients

| Benefit | How |
|---|---|
| **Faster initial acknowledgment** | AI greets users and begins analysis within seconds of ticket creation. |
| **Intelligent self-service** | High-confidence KB article matches are surfaced immediately, enabling self-resolution. |
| **No "please describe your issue" loops** | The AI asks targeted, specific clarifying questions — at most 3 — instead of generic follow-ups. |
| **Multi-issue detection** | If a client submits a ticket containing multiple unrelated problems, the system proposes splitting them for parallel resolution. |
| **Consistent experience** | Every ticket gets the same structured analysis regardless of which technician picks it up. |

## Benefits to Technicians

| Benefit | How |
|---|---|
| **Structured briefings** | Every ticket arrives with a comprehensive markdown brief: problem summary, customer history, KB matches, escalation recommendations, and complexity estimate. |
| **9-point Sparkle checklist** | A pre-built checklist tracks what the AI has already done (greeted, problem statement, ownership, diagnostics, KB search, etc.) so the tech knows exactly where to pick up. |
| **Customer history at a glance** | Up to 10 prior tickets with recurrence detection — no manual searching. |
| **Quick-win suggestions** | AI proposes specific, actionable fixes with expected outcomes. |
| **Escalation recommendations** | The briefing explicitly states whether escalation is warranted and why. |
| **Thought continuity** | Thought Signatures preserve AI reasoning across multi-turn conversations — follow-up replies inherit full context. |
| **Reduced noise** | Audience targeting, spam filtering, and unknown-sender blocking keep non-actionable tickets out of the AI pipeline. |

---

## Proactive Model Lifecycle Management

The system includes a daily scheduled command (`casemanager:check-gemini-models`) that queries the Gemini API to detect:

- **Sunset models** — a configured model is no longer listed in the API.
- **Deprecated models** — Google has announced a deprecation timeline.
- **Newer versions** — a major upgrade is available (e.g., 3.0 when we use 2.5).

When issues are detected, the system dispatches structured admin alerts through the Alerts module with specific recommended actions. This prevents production outages caused by Google deprecating models without warning.

---

## Key Metrics at a Glance

| Metric | Value |
|---|---|
| Resolution strategies | 6 |
| Pipeline stages | 7 |
| Checklist items per case | 9 |
| Default KB confidence threshold | 85% |
| Max clarifying questions | 3 |
| History lookup depth | 10 tickets |
| Recurrence detection window | 90 days |
| Listener retry attempts | 3 (30s backoff) |
| Delayed error check | 5 minutes |
| Fern monthly budget cap | $25.00 |
| PHPStan compliance | Level 9 — 0 errors |

---

## Risk Summary

| Risk | Mitigation |
|---|---|
| AI sends incorrect information to client | Human approval required by default; auto-respond is opt-in. |
| Gemini API outage | 3 retries → delayed job → error state → admin alert → manual fallback. |
| Cost overrun | Per-stage model selection, $25/month Fern cap, audience targeting, work-hour throttling. |
| Model deprecation | Daily lifecycle check with proactive admin alerts. |
| Data integrity | Core Blindness — module tables are completely isolated from FreeScout core. |
| Module failure | FreeScout continues operating normally; tickets arrive but lack AI enrichment. |
