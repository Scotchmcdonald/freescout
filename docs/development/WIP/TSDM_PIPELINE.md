# TSDM Deployment Pipeline — Remaining Work

> **Last Updated:** 2026-03-11  
> **Context:** `Modules/TreeScoutDeploymentManager` core is built. DB migrations
> (4 tables), web CRUD UI under Tools, and the `/api/tsdm/activate` gateway are
> all live. What remains is the CI/CD integration, multi-tenant isolation,
> security hardening, and operational improvements.

---

## Phase 1 — Validate Current TSDM Core

- [ ] Stand up a GitLab instance; store token as `TSDM_GITLAB_ADMIN_TOKEN` in environment.
- [ ] Run `deploy.sh` end-to-end to verify OTAC code generation, redemption, and module delivery.
- [ ] Document any gaps found against the `ActivationService` and `GitProviderService` implementations.

---

## Phase 2 — GitHub → GitLab CI Pipeline

- [ ] Create `.github/workflows/publish-module.yml`: on tag push (`v*.*.*`), package and push module release to the GitLab registry.
- [ ] Validate the pipeline triggers correctly and the packaged artifact is consumable by `deploy.sh`.

---

## Phase 3 — Per-Client Token Isolation

- [ ] Store `provider_token_id` per deployment record so tokens can be individually revoked.
- [ ] Implement multi-tenant isolation: each client gets a scoped deployment token; admin tokens are never issued to clients.
- [ ] Handle API token revocation flow from GitHub/GitLab provider side (webhook or polling).

---

## Phase 4 — Security Hardening

- [ ] Generate and publish SHA-256 checksums for `deploy.sh`; verify checksum on client-side before execution.
- [ ] Enforce OTAC single-use limits with rate conditioning: an OTAC code that has been attempted (even on failure) must be invalidated and a new one issued.
- [ ] Audit `ActivationBrokerController` API surface for injection / replay attack vectors.

---

## Phase 5 — Operational Improvements

- [ ] Enforce licensing module limits: prevent deployments that would exceed a client's licensed module count.
- [ ] Deployment notifications: Slack/Email webhook on successful deploy, failed deploy, and token expiry.
