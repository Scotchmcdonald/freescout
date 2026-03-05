# TSDM Deployment Pipeline: CI/CD & Authorization TODO

## Completed Work
✅ **Module Core Framework**: Scaffold created, DB migrations alive for 4 tables.
✅ **Web UI (CRUD)**: Basic Deployments records accessible under Tools.
✅ **API Gateway**: `/api/tsdm/activate` handles request correctly.

## Outstanding Work
🔲 **Phase 1: Validate Current TSDM Core**: 
   - 🔲 Setup GitLab Instance, store `TSDM_GITLAB_ADMIN_TOKEN`.
   - 🔲 Verify `deploy.sh` end-to-end OTAC code redemption.
🔲 **Phase 2: GitHub → GitLab CI Pipeline**:
   - 🔲 Setup GitHub Actions Workflow for automated publish on tag triggers.
🔲 **Phase 3: Per-Client Token Isolation**:
   - 🔲 Multi-tenant isolation for deployed modules.
   - 🔲 Store `provider_token_id` and handle API token revocation from Git provider.
🔲 **Phase 4: Security Hardening**:
   - 🔲 SHA-256 Checksums for `deploy.sh`.
   - 🔲 OTAC single-use race conditioning UI/API limits.
🔲 **Phase 5: Operational Improvements**:
   - 🔲 Licensing module limits + deployment notifications for Slack/Email webhooks.
