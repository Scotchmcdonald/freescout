# Google Multi-Tenant Resilience and Audit Implementation Plan

Date: 2026-03-16
Owner: Platform / Integrations
Status: Draft for execution

## 1) Problem Statement
We now support Google Workspace credentials per enrolled client domain. We need an implementation plan that makes this production-safe and operator-friendly, including:

- Reliable per-tenant connectivity checks (home domain quick test + enrolled-domain sweep)
- Strong security and lifecycle management for tenant credentials
- Better observability in the resilience dashboard
- Sustainable monthly audit archival and data-retention behavior

## 2) Current State (Context for Next Agent)
Recent work already introduced:

- Home-domain probe and tenant sweep probe paths for Google Workspace in Admin Resilience
- UI actions for both probe modes in the resilience dashboard
- Feature tests covering probe behaviors and tenant-sweep failure scenarios

Relevant touched areas include:

- app/Http/Controllers/Admin/ResilienceController.php
- resources/views/admin/resilience/index.blade.php
- tests/Feature/Admin/ResilienceAdminPestTest.php

Google tenant data source is the GoogleAdmin config table/model (per-client domain + admin + encrypted credentials).

## 3) Scope and Outcomes
### In scope
- Harden multi-tenant credential handling and probe behavior
- Add dashboard ranking for rate-limit and breaker listings by most used / highest risk first
- Define and implement monthly audit generation, delivery, archival, and retention

### Out of scope (for this plan)
- Re-architecting all resilience modules
- Replacing existing Google sync job workflows

## 4) Design Decisions
1. Tenant isolation is mandatory:
   - Separate credentials, auth context, breaker state, and limiter keys by tenant.

2. Dashboard ordering must prioritize actionability:
   - Sort by highest usage and highest risk first.
   - Most-used integrations appear at the top.

3. Monthly audits should be preserved externally and pruned locally:
   - Generate signed audit artifacts monthly.
   - Deliver via email and/or Google Drive destination.
   - Apply retention policy to local records and artifacts to control storage.

4. Deletion must be policy-based, not immediate blind purge:
   - Keep hot data locally for short troubleshooting window.
   - Archive immutable snapshot externally before local purge.

## 5) Task Breakdown

## Phase A: Security and Credential Lifecycle
### Task A1: Credential Storage Hardening
- Validate encryption at rest for tenant credential payloads.
- Ensure raw credentials are never logged or exposed in error payloads.
- Add masking helpers for all admin UI displays.

Deliverable:
- Security guardrails in model/controller/service layers.

Acceptance:
- No plaintext credentials in logs, responses, or activity feeds.

### Task A2: Upload Validation and Activation Gate
- Enforce required fields for service account JSON.
- Validate admin delegation identity and domain consistency.
- Require non-destructive read probe before config becomes active.

Deliverable:
- Config status field: draft, verified, active, invalid.

Acceptance:
- Invalid credentials cannot become active.

### Task A3: Rotation Workflow
- Add rotation metadata: version, last_rotated_at, rotated_by.
- Implement staged rotate flow with rollback to prior version if probe fails.

Deliverable:
- Rotation runbook + UI/API endpoint.

Acceptance:
- Rotation can complete without tenant outage.

## Phase B: Resilience Dashboard and Probe UX
### Task B1: Sort Rate-Limit Rows by Most Used
- Update rate-limit aggregation ordering to sort descending by:
  1) used_percent (when limit exists)
  2) used calls (fallback)
- Tie-break by API name.

Deliverable:
- Rate-limit listing sorted with most-used at the top.

Acceptance:
- Dashboard consistently shows high-use rows first.

### Task B2: Sort Circuit Breaker Rows by Operational Risk
- Add ranking strategy:
  1) state severity: open > half_open > closed
  2) failure_count desc
  3) recency of failure/open desc
  4) usage desc fallback

Deliverable:
- Breaker list places highest-risk/highest-use entries first.

Acceptance:
- Open/high-failure services remain near top until healthy.

### Task B3: Tenant Sweep Result Usability
- Show summary counts: checked, passed, failed.
- Provide per-tenant row with client, domain, status, latency, error class.
- Add CSV export for tenant sweep results.

Deliverable:
- Operator-friendly tenant diagnostics.

Acceptance:
- On-call can identify exact failing tenant in < 30 seconds.

## Phase C: Monthly Audits, Delivery, Archival, Retention
### Task C1: Monthly Audit Job
- Build scheduled command/job that runs monthly.
- Capture:
  - credential health posture
  - probe pass/fail history
  - breaker/rate-limit trends
  - tenant config drift findings

Deliverable:
- Monthly JSON + human-readable PDF/HTML report artifact.

Acceptance:
- One report set generated every month with deterministic naming.

### Task C2: Delivery Channels (Email and Drive)
- Email report to configured recipients/distribution list.
- Upload report bundle to Google Drive archive folder when configured.
- Record delivery status and checksum.

Deliverable:
- Dual-channel delivery with retry and failure alerts.

Acceptance:
- At least one delivery path succeeds, failures are visible.

### Task C3: Retention and Space Management
- Retention policy proposal:
  - Local detailed records: 90 days
  - Local monthly summaries: 12 months
  - External archive (Drive/object storage): 24+ months per compliance needs
- Purge policy runs only after archive success is confirmed.
- Keep immutable audit index in DB (minimal metadata) after purge.

Deliverable:
- Automated purge command with dry-run mode and audit log.

Acceptance:
- Storage growth remains bounded while preserving compliance artifacts.

## Phase D: Alerting, Governance, and Operations
### Task D1: Alerts and Thresholds
- Alert when tenant sweep failure rate exceeds threshold.
- Alert on missing monthly report generation/delivery.

Deliverable:
- Notification rules and escalation mapping.

Acceptance:
- Missed audits and major tenant regressions generate actionable alerts.

### Task D2: Access Control and Auditability
- Restrict who can view, trigger, export, and purge audit data.
- Log all admin actions around credentials and retention jobs.

Deliverable:
- RBAC matrix + activity logging coverage.

Acceptance:
- Sensitive actions are permission-gated and traceable.

## 6) Suggested Implementation Order
1. B1 + B2 (dashboard sorting improvements)
2. C1 (monthly audit generation)
3. C2 (email + drive delivery)
4. C3 (retention/purge with safety checks)
5. A1-A3 (credential hardening + rotation), if not already fully complete
6. D1-D2 (alerts + governance hardening)

Rationale:
- Improves operator visibility immediately, then secures compliance and storage, then deepens lifecycle controls.

## 7) Data Model and Config Additions
Potential additions:

- google_configs:
  - status
  - credential_version
  - last_verified_at
  - last_rotation_at
- resilience_audit_reports:
  - period_month
  - generated_at
  - artifact_path
  - checksum
  - email_delivery_status
  - drive_delivery_status
- resilience_audit_report_items (optional detail table)

Config keys:

- resilience.audit.enabled
- resilience.audit.recipients
- resilience.audit.drive_folder_id
- resilience.audit.local_retention_days
- resilience.audit.summary_retention_months
- resilience.audit.purge_requires_archive_confirmation

## 8) Testing Plan
### Unit tests
- Sort ranking logic for rate-limit and breaker lists
- Retention policy date-window and purge eligibility

### Feature tests
- Monthly audit generation creates expected artifacts
- Delivery job records success/failure for email and Drive
- Purge does not run when archive confirmation is missing

### Smoke tests
- Resilience dashboard ordering on seeded high/low usage data
- End-to-end monthly audit run in non-production environment

## 9) Rollout Plan
1. Release dashboard ordering changes behind a small feature flag if needed.
2. Deploy audit generation and delivery in observe-only mode for one cycle.
3. Enable purge in dry-run mode and review output.
4. Enable active purge after sign-off from security/compliance.

## 10) Open Questions
1. Is email required in all environments, or only production?
2. Is Google Drive mandatory, optional fallback, or one of multiple archive targets?
3. What compliance retention period is required per customer contract (12, 24, 36+ months)?
4. Should archived reports be encrypted with tenant-specific keys before upload?

## 11) Recommended Policy Answer (for the asked question)
Yes, monthly audits should be emailed and/or archived to Google Drive, but local record deletion should only occur after successful archive confirmation and under an explicit retention policy.

Recommended default:
- Email + Drive upload both enabled in production
- Keep 90 days of local detailed records
- Keep 12 months of local summary/index metadata
- Purge only after archive checksum and delivery status are recorded
