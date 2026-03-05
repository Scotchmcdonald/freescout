#!/usr/bin/env bash
# =============================================================================
# sync-module-repos.sh
# Ensures every module has a GitHub repo under BorealTek, a git remote pointing
# at it, and local commits pushed to main.
#
# Prerequisites:
#   - gh CLI authenticated as a user with BorealTek org access
#   - git installed
#   - Run from the repo root: bash scripts/sync-module-repos.sh
# =============================================================================

set -e

MODULES_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)/Modules"
ORG="BorealTek"

# Map of module folder → GitHub repo name under BorealTek
declare -A REPOS=(
    ["Action1"]="Action1-Module"
    ["Alerts"]="Alerts-Module"
    ["AssetManagement"]="AssetManagement-Module"
    ["CaseManager"]="CaseManager-Module"
    ["ClientPortal"]="ClientPortal-Module"
    ["ContractManager"]="ContractManager-Module"
    ["Crm"]="Crm-Module"
    ["DevFeedback"]="DevFeedback-Module"
    ["EmailMigration"]="EmailMigration-Module"
    ["GoogleAdmin"]="GoogleAdmin-Module"
    ["KnowledgeBase"]="KnowledgeBase-Module"
    ["Payment"]="Payment-Module"
    ["PIB"]="PIB-Module"
    ["SoftwareSubscriptions"]="SoftwareSubscriptions-Module"
    ["WidgetRegistry"]="WidgetRegistry-Module"
    # TreeScoutDeploymentManager deliberately omitted (deferred)
)

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

log()    { echo -e "${GREEN}[✓]${NC} $*"; }
warn()   { echo -e "${YELLOW}[!]${NC} $*"; }
err()    { echo -e "${RED}[✗]${NC} $*"; }
header() { echo -e "\n${YELLOW}=== $* ===${NC}"; }

# Check prerequisites
if ! command -v gh &>/dev/null; then
    err "gh CLI not found. Install from https://cli.github.com/"
    exit 1
fi

if ! gh auth status &>/dev/null; then
    err "gh CLI not authenticated. Run: gh auth login"
    exit 1
fi

if ! command -v git &>/dev/null; then
    err "git not found."
    exit 1
fi

ERRORS=()

for MODULE in "${!REPOS[@]}"; do
    REPO_NAME="${REPOS[$MODULE]}"
    MODULE_DIR="$MODULES_DIR/$MODULE"
    FULL_REPO="$ORG/$REPO_NAME"
    REMOTE_URL="https://github.com/$FULL_REPO.git"

    header "$MODULE → $FULL_REPO"

    # ── 1. Module directory must exist ───────────────────────────────────────
    if [[ ! -d "$MODULE_DIR" ]]; then
        err "Module directory not found: $MODULE_DIR — skipping"
        ERRORS+=("$MODULE: directory missing")
        continue
    fi

    # ── 2. Ensure GitHub repo exists (create if not) ─────────────────────────
    if gh repo view "$FULL_REPO" &>/dev/null; then
        log "Repo exists: $FULL_REPO"
    else
        warn "Repo not found — creating private repo $FULL_REPO"
        if gh repo create "$FULL_REPO" --private --description "FreeScout $MODULE module" 2>&1; then
            log "Created: $FULL_REPO"
        else
            err "Failed to create repo $FULL_REPO"
            ERRORS+=("$MODULE: failed to create repo")
            continue
        fi
    fi

    cd "$MODULE_DIR"

    # ── 3. Initialize git if needed ──────────────────────────────────────────
    if [[ ! -d ".git" ]]; then
        warn "No .git found — initializing"
        git init -b main
        log "Initialized git in $MODULE_DIR"
    fi

    # ── 4. Ensure remote 'origin' points to the correct URL ─────────────────
    if git remote get-url origin &>/dev/null; then
        CURRENT_REMOTE=$(git remote get-url origin)
        if [[ "$CURRENT_REMOTE" != "$REMOTE_URL" ]]; then
            warn "Remote mismatch (was: $CURRENT_REMOTE) — updating to $REMOTE_URL"
            git remote set-url origin "$REMOTE_URL"
        else
            log "Remote origin already correct"
        fi
    else
        warn "No remote 'origin' — adding $REMOTE_URL"
        git remote add origin "$REMOTE_URL"
    fi

    # ── 5. Stage and commit any uncommitted changes ──────────────────────────
    git add -A

    if git diff --cached --quiet; then
        log "Nothing new to commit"
    else
        COMMIT_MSG="chore: sync local module code"
        if ! git log --oneline -1 &>/dev/null 2>&1; then
            COMMIT_MSG="feat: initial commit"
        fi
        git commit -m "$COMMIT_MSG"
        log "Committed changes"
    fi

    # ── 6. Ensure we are on 'main' ───────────────────────────────────────────
    CURRENT_BRANCH=$(git branch --show-current 2>/dev/null || echo "")
    if [[ "$CURRENT_BRANCH" != "main" ]]; then
        warn "Current branch is '$CURRENT_BRANCH' — renaming to main"
        git branch -M main
    fi

    # ── 7. Push to origin/main ───────────────────────────────────────────────
    if git push -u origin main 2>&1; then
        log "Pushed to $FULL_REPO main"
    else
        # If push was rejected because remote has commits we don't have
        # (e.g. gh repo create added a README), try pulling first
        warn "Push rejected — attempting pull --rebase then re-push"
        if git pull --rebase origin main 2>&1 && git push -u origin main 2>&1; then
            log "Pushed after rebase"
        else
            err "Could not push $MODULE — manual intervention needed"
            ERRORS+=("$MODULE: push failed")
        fi
    fi

    cd - > /dev/null
done

# ── Summary ──────────────────────────────────────────────────────────────────
echo ""
if [[ ${#ERRORS[@]} -eq 0 ]]; then
    log "All modules synced successfully."
else
    err "Completed with ${#ERRORS[@]} error(s):"
    for E in "${ERRORS[@]}"; do
        echo "    - $E"
    done
    exit 1
fi
