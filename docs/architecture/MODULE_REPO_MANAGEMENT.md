# Module Repository Management
**Audience:** Architects, DevOps, senior engineers  
**Last Updated:** February 28, 2026

---

## Current State (What Is Actually Happening)

### Repository Structure

The application is spread across **15 separate git repositories**:

| Repo | Location | Remote |
|---|---|---|
| Core application | `/var/www/html` | `github.com/Scotchmcdonald/freescout` |
| Action1 | `Modules/Action1` | `github.com/BorealTek/Action1-Module` |
| AssetManagement | `Modules/AssetManagement` | `github.com/BorealTek/AssetManagement-Module` |
| ClientPortal | `Modules/ClientPortal` | `github.com/BorealTek/ClientPortal-Module` |
| ContractManager | `Modules/ContractManager` | `github.com/BorealTek/ContractManager-Module` |
| Crm | `Modules/Crm` | `github.com/BorealTek/Crm-Module` |
| DevFeedback | `Modules/DevFeedback` | `github.com/BorealTek/DevFeedback-Module` |
| EmailMigration | `Modules/EmailMigration` | `github.com/BorealTek/EmailMigration-Module` |
| GoogleAdmin | `Modules/GoogleAdmin` | `github.com/BorealTek/GoogleAdmin-Module` |
| KnowledgeBase | `Modules/KnowledgeBase` | `github.com/BorealTek/KnowledgeBase-Module` |
| PIB | `Modules/PIB` | `github.com/BorealTek/PIB-Module` |
| Payment | `Modules/Payment` | `github.com/BorealTek/Payment-Module` |
| **Alerts** | `Modules/Alerts` | **⚠ No git repo — local files only** |
| **SoftwareSubscriptions** | `Modules/SoftwareSubscriptions` | **⚠ No git repo — local files only** |
| **WidgetRegistry** | `Modules/WidgetRegistry` | **⚠ No git repo — local files only** |

### How the `Modules/` Directory is Tracked (Or Not)

The entire `Modules/` directory is in the root `.gitignore`:

```gitignore
# Modules (if dynamically managed)
/Modules
```

This means:
- **The main repo does not track any module code.** Cloning `Scotchmcdonald/freescout` gives you a shell with no modules.
- The 11 modules that have `.git` directories are **nested git repos** — not submodules. They are independent, with no registration in `.gitmodules`. The parent repo is completely unaware of them.
- The 3 modules without `.git` (**Alerts, SoftwareSubscriptions, WidgetRegistry**) exist only on this server. They have never been pushed anywhere. If this machine is lost, those modules are gone.

### How Modules Are Installed on Deployment

`deployment/docker_deploy.sh` contains a hardcoded `MODULES_TO_INSTALL` array and an `install_modules()` function that `git clone`s each entry into `$INSTALL_DIR/src/Modules/`:

```bash
MODULES_TO_INSTALL=(
    "Action1|https://github.com/BorealTek/Action1-Module.git|REPO_TOKEN|main"
    "Alerts|https://github.com/BorealTek/Alerts-Module.git|REPO_TOKEN|main"
    # ... 12 more
)
```

On re-deploy it does a `git fetch` + `git pull` instead of a fresh clone. Authentication is handled by injecting a GitHub token from `$REPO_TOKEN` into the HTTPS clone URL.

The `deploy.conf.example` shows the intended operator-facing override: copy the file to `deploy.conf`, uncomment and edit the `MODULES_TO_INSTALL` array, and populate it with the subset of modules needed.

### Immediate Risks

1. **Three modules have no remote backup** — `Alerts`, `SoftwareSubscriptions`, `WidgetRegistry` will be lost if this server is replaced.
2. ~~**Fresh dev setup is undocumented**~~ ✅ **RESOLVED** — `scripts/setup-modules.sh` now exists and is documented in [`DEVELOPER_GETTING_STARTED.md`](DEVELOPER_GETTING_STARTED.md).
3. **No enforceable consistency** — a developer can be on `main` in `Crm` while another is on a feature branch. There is no mechanism to tie a specific core version to specific module versions.
4. **`modules_statuses.json` tracks enable/disable but not version** — it only records `true/false`, not which commit of each module is deployed.

---

## Options for Managing Module Repositories

There are three standard patterns. Here is an honest comparison given where the project is today.

### Option A — Git Submodules (Formal Registration)

Register each module repo as a git submodule in the core repo. The `.gitmodules` file pins each module to a commit. `git clone --recurse-submodules` bootstraps everything in one step.

```bash
git submodule add https://github.com/BorealTek/Crm-Module.git Modules/Crm
# repeat for each module, then commit .gitmodules
```

**Pros:**
- Single `git clone --recurse-submodules <core-repo>` sets up the full workspace
- Core repo commits record the exact module commit hash — reproducible builds
- `git submodule update --remote` updates all modules to their latest branch tip
- Standard git tooling; no extra scripts

**Cons:**
- Submodule UX is notoriously awkward (detached HEAD, forgetting `--recurse-submodules`, etc.)
- Every module update requires a commit to the core repo to advance the pointer — adds process friction
- Does not natively support "profiles" (different module sets per client)
- Requires removing `/Modules` from `.gitignore` for the registered paths

**Verdict:** Best option for strict version pinning. Worthwhile if deploy-time reproducibility is the priority.

---

### Option B — Composer Packages (Private Registry)

Publish each module as a Composer package. Host them on a private Packagist instance (e.g., [Private Packagist](https://packagist.com) or self-hosted [Satis](https://github.com/composer/satis)). Declare per-client configurations as separate `composer.json` files or environment-driven variant files.

```json
// composer.json (full install)
"require": {
    "borealtek/crm-module": "^1.0",
    "borealtek/pib-module": "^1.0",
    "borealtek/payment-module": "^1.0"
}
```

**Pros:**
- Standard PHP ecosystem tooling; `composer install` handles everything
- Semantic versioning, changelogs, and dependency resolution per-module
- Per-client profiles are just different `composer.json` files
- nwidart supports Composer-installed modules natively

**Cons:**
- Requires setting up and maintaining a private Packagist/Satis server
- Each module needs a `composer.json` with proper versioning (currently only has a bare-bones one)
- Higher upfront migration effort
- Adds a publishing step to each module's release workflow

**Verdict:** The most scalable long-term option — especially for the multi-client future. High setup cost now; pays off at 3+ client configurations.

---

### Option C — Deploy-Time Manifest Clone (Current Approach, Improved)

Keep the current `git clone` approach but make the manifest the single source of truth for **all** tooling: the deploy script, the developer setup script, and CI. Add named profiles to support different client configurations.

This is the path of least structural disruption and is the recommended next step even if Option B is the long-term target.

---

## Recommended Approach: Module Manifest + Profiles

A single file, `deployment/modules.manifest.json`, declares all known modules and all named deployment profiles. The deploy script, a new `scripts/setup-modules.sh` developer bootstrap, and CI all read from this file.

### Step 1 — Create the manifest

```json
// deployment/modules.manifest.json
{
  "_comment": "Single source of truth for all module repos and deployment profiles.",
  "modules": {
    "Crm":                  { "repo": "https://github.com/BorealTek/Crm-Module.git",                  "branch": "main" },
    "PIB":                  { "repo": "https://github.com/BorealTek/PIB-Module.git",                  "branch": "main" },
    "Payment":              { "repo": "https://github.com/BorealTek/Payment-Module.git",              "branch": "main" },
    "ContractManager":      { "repo": "https://github.com/BorealTek/ContractManager-Module.git",      "branch": "main" },
    "AssetManagement":      { "repo": "https://github.com/BorealTek/AssetManagement-Module.git",      "branch": "main" },
    "SoftwareSubscriptions":{ "repo": "https://github.com/BorealTek/SoftwareSubscriptions-Module.git","branch": "main" },
    "ClientPortal":         { "repo": "https://github.com/BorealTek/ClientPortal-Module.git",         "branch": "main" },
    "KnowledgeBase":        { "repo": "https://github.com/BorealTek/KnowledgeBase-Module.git",        "branch": "main" },
    "GoogleAdmin":          { "repo": "https://github.com/BorealTek/GoogleAdmin-Module.git",          "branch": "main" },
    "Alerts":               { "repo": "https://github.com/BorealTek/Alerts-Module.git",               "branch": "main" },
    "Action1":              { "repo": "https://github.com/BorealTek/Action1-Module.git",              "branch": "main" },
    "WidgetRegistry":       { "repo": "https://github.com/BorealTek/WidgetRegistry-Module.git",       "branch": "main" },
    "DevFeedback":          { "repo": "https://github.com/BorealTek/DevFeedback-Module.git",          "branch": "main" },
    "EmailMigration":       { "repo": "https://github.com/BorealTek/EmailMigration-Module.git",       "branch": "main" }
  },
  "profiles": {
    "full": {
      "_comment": "All modules — used for internal BorealTek deployment",
      "modules": ["Crm", "PIB", "Payment", "ContractManager", "AssetManagement",
                  "SoftwareSubscriptions", "ClientPortal", "KnowledgeBase",
                  "GoogleAdmin", "Alerts", "Action1", "WidgetRegistry",
                  "DevFeedback", "EmailMigration"]
    },
    "core-msp": {
      "_comment": "Standard MSP client — helpdesk + CRM + billing + assets",
      "modules": ["Crm", "PIB", "Payment", "ContractManager",
                  "AssetManagement", "SoftwareSubscriptions",
                  "ClientPortal", "Alerts", "WidgetRegistry"]
    },
    "helpdesk-only": {
      "_comment": "Minimal install — ticketing and CRM only, no billing",
      "modules": ["Crm", "KnowledgeBase", "ClientPortal", "WidgetRegistry"]
    },
    "google-workspace-msp": {
      "_comment": "MSP with Google Workspace management",
      "modules": ["Crm", "PIB", "Payment", "ContractManager",
                  "AssetManagement", "SoftwareSubscriptions",
                  "ClientPortal", "GoogleAdmin", "Alerts", "WidgetRegistry"]
    }
  }
}
```

### Step 2 — Create a developer bootstrap script

`scripts/setup-modules.sh` — run this once after cloning the core repo.

```bash
#!/usr/bin/env bash
# Usage: ./scripts/setup-modules.sh [profile]
# Example: ./scripts/setup-modules.sh core-msp
# Default profile: full

set -euo pipefail

PROFILE="${1:-full}"
MANIFEST="deployment/modules.manifest.json"
TOKEN="${REPO_TOKEN:-}"

if ! command -v jq &>/dev/null; then
    echo "Error: jq is required. Install it with: sudo apt install jq / brew install jq"
    exit 1
fi

echo "Installing modules for profile: $PROFILE"
MODULES=$(jq -r ".profiles[\"$PROFILE\"].modules[]" "$MANIFEST")

for name in $MODULES; do
    repo=$(jq -r ".modules[\"$name\"].repo" "$MANIFEST")
    branch=$(jq -r ".modules[\"$name\"].branch" "$MANIFEST")
    target="Modules/$name"

    if [ -d "$target/.git" ]; then
        echo "  [$name] exists — pulling $branch"
        git -C "$target" fetch origin
        git -C "$target" checkout "$branch"
        git -C "$target" pull origin "$branch"
    else
        echo "  [$name] cloning from $repo @ $branch"
        if [ -n "$TOKEN" ]; then
            clean="${repo#https://}"
            repo="https://oauth2:${TOKEN}@${clean}"
        fi
        git clone -b "$branch" "$repo" "$target"
    fi
done

echo ""
echo "Done. Run: php artisan module:list  to verify."
```

### Step 3 — Update the deploy script to read the manifest

Replace the hardcoded `MODULES_TO_INSTALL` array in `deployment/docker_deploy.sh` with a call that reads from the manifest and accepts a `--profile` flag. This keeps the array as a fallback but makes the manifest authoritative.

### Step 4 — `modules_statuses.json` follows the profile

After cloning, the bootstrap sets `modules_statuses.json` to only enable the modules in the selected profile. Modules not in the profile are not cloned at all — they simply don't exist on disk.

```bash
# At the end of setup-modules.sh
php artisan module:list --format=json | ... # build statuses dynamically
```

---

## Immediate Action Items

These three items address real risk right now, before any structural change:

### 1. Push the three untracked modules to GitHub

`Alerts`, `SoftwareSubscriptions`, and `WidgetRegistry` exist only on this server:

```bash
# For each untracked module:
cd Modules/Alerts
git init
git add .
git commit -m "Initial commit — extracted from dev server"
git remote add origin https://github.com/BorealTek/Alerts-Module.git
git push -u origin main

# Repeat for SoftwareSubscriptions and WidgetRegistry
```

Then add them to the `MODULES_TO_INSTALL` array in `deployment/docker_deploy.sh` (or the manifest).

### 2. ✅ `modules.manifest.json` — DONE

`deployment/modules.manifest.json` exists and defines all module repos and named profiles (`full`, `core-msp`, `helpdesk-only`, `google-workspace-msp`). The Step 1 template above was used as the basis for this file.

### 3. ✅ Module bootstrap documented — DONE

`scripts/setup-modules.sh` exists and is executable. [`DEVELOPER_GETTING_STARTED.md`](DEVELOPER_GETTING_STARTED.md) documents it as step 2 of both the Docker and non-Docker setup flows.

---

## Designing Client Profiles

When deploying for a specific client, the profile determines which modules are cloned and enabled. Key design decisions:

### What makes a good profile boundary?

Profiles should be defined by **business capability**, not by technical size:

| Question | If Yes, Add |
|---|---|
| Does the client need invoicing & billing? | `PIB`, `Payment`, `ContractManager` |
| Does the client track hardware? | `AssetManagement` |
| Does the client manage software licenses? | `SoftwareSubscriptions` |
| Does the client want a self-service portal? | `ClientPortal` |
| Does the client use Google Workspace? | `GoogleAdmin` |
| Does the client use Action1 (RMM)? | `Action1` |

`Crm` and `WidgetRegistry` are effectively always required — `Crm` is depended on by PIB (`module.json` `"requires": ["Crm"]`), and `WidgetRegistry` underpins the UI composition system.

### Per-client configuration vs per-client code

A deployment profile should be **configuration**, not a code fork. The distinction:

| Mechanism | Use for |
|---|---|
| Profile (module list) | Which capabilities exist |
| `modules_statuses.json` | Runtime enable/disable of installed modules |
| `.env` / `deploy.conf` | Credentials, domain, database, feature flags |
| Database seeders | Client-specific reference data (products, users, roles) |

Do **not** fork the module repos per client. If a client needs a behavioral difference, that belongs in a configurable feature flag within the module, not a branch.

### Module version pinning per client

Once on Composer packages (Option B above), per-client version pinning is trivial via `composer.json`. Until then, pin via branch or tag in the manifest:

```json
// deployment/modules.manifest.json — client-pinned example
"Crm": { "repo": "...", "branch": "v1.2-stable" }
```

For production client deployments, prefer a tag over a branch name so that `git pull` cannot inadvertently advance a running deployment.

---

## Summary

| Topic | Current State | Recommended Next Step |
|---|---|---|
| Module repos | 11 nested gits + 3 local-only | Push 3 untracked modules to GitHub |
| Dev workspace setup | ✅ `scripts/setup-modules.sh` + [`DEVELOPER_GETTING_STARTED.md`](DEVELOPER_GETTING_STARTED.md) | Update deploy script to read manifest |
| Deploy module list | Hardcoded in `deployment/docker_deploy.sh` | ✅ `modules.manifest.json` exists — wire deploy script to read it |
| Client profiles | ✅ Defined in `modules.manifest.json` (`full`, `core-msp`, `helpdesk-only`, `google-workspace-msp`) | Use `--profile` flag in deploy script |
| Version pinning | Branch name only | Tags for client prod deploys |
| Long-term packaging | Nested git repos | Composer private packages (Option B) |
