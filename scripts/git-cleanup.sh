#!/bin/bash
# =============================================================================
# git-cleanup.sh — Repository Audit Cleanup Script
# Generated: 2026-03-10
#
# PURPOSE: Untrack git-ignored ghost files, remove local junk/backup files,
#          and report on nested repo status (Modules + deployment).
#
# USAGE:
#   bash scripts/git-cleanup.sh [--dry-run] [--apply]
#
#   --dry-run  Print all actions without executing (default if no flag given).
#   --apply    Execute the cleanup for real.
#
# SAFETY: This script NEVER deletes tracked source files. It only calls
#   `git rm --cached` (remove from index, keep local file) or `rm` on
#   explicitly listed local-only junk files.
# =============================================================================

set -euo pipefail

DRY_RUN=true
if [[ "${1:-}" == "--apply" ]]; then
  DRY_RUN=false
elif [[ "${1:-}" != "--dry-run" && "${1:-}" != "" ]]; then
  echo "Usage: $0 [--dry-run|--apply]"
  exit 1
fi

RED='\033[0;31m'
YELLOW='\033[1;33m'
GREEN='\033[0;32m'
CYAN='\033[0;36m'
RESET='\033[0m'

GIT_RM="git rm --cached"
RM_CMD="rm"

log_action() {
  local color="$1"; shift
  echo -e "${color}[$(date +%H:%M:%S)] $*${RESET}"
}

run() {
  if $DRY_RUN; then
    log_action "$YELLOW" "[DRY-RUN] $*"
  else
    log_action "$GREEN" "[EXEC] $*"
    eval "$@"
  fi
}

header() {
  echo ""
  echo -e "${CYAN}════════════════════════════════════════════════════════${RESET}"
  echo -e "${CYAN}  $1${RESET}"
  echo -e "${CYAN}════════════════════════════════════════════════════════${RESET}"
}

if $DRY_RUN; then
  echo ""
  log_action "$YELLOW" "DRY-RUN MODE — no changes will be made."
  log_action "$YELLOW" "Run with --apply to execute."
else
  echo ""
  log_action "$RED" "APPLY MODE — changes will be written to the git index."
  log_action "$RED" "Ensure you have a clean state or stash WIP before proceeding."
  read -rp "Continue? [y/N] " confirm
  [[ "$confirm" =~ ^[Yy]$ ]] || { echo "Aborted."; exit 0; }
fi

# =============================================================================
# SECTION 1: Untrack files that should never be in git (secrets, binaries)
# =============================================================================
header "SECTION 1: Untracking ghost files (tracked but now .gitignored)"

GHOST_FILES=(
  # ── CRITICAL: Environment files containing real secrets ──────────────────
  ".env.docker"
  ".env.dusk.local"

  # ── TLS private key and certificate (NEVER in git) ───────────────────────
  "nginx/ssl/key.pem"
  "nginx/ssl/cert.pem"

  # ── SQLite test database auxiliary files (binary, runtime artefacts) ─────
  "database/testing.sqlite-shm"
  "database/testing.sqlite-wal"

  # ── Legacy root-level noise ───────────────────────────────────────────────
  "index.nginx-debian.html"   # Default Debian nginx placeholder page
)

for f in "${GHOST_FILES[@]}"; do
  if git ls-files --error-unmatch "$f" &>/dev/null; then
    run "$GIT_RM $f"
  else
    log_action "$CYAN" "[SKIP] $f is not currently tracked — nothing to do."
  fi
done

# =============================================================================
# SECTION 2: Untrack Module files still in the git index from prior commits
#
# Context: Modules/Action1, AssetManagement, ContractManager, Crm, PIB, and
# Payment had source files committed directly into the main repo before the
# team migrated to independent repos. The .gitignore now has /Modules, but
# committed files are still in the index. This removes them from tracking
# without touching the local files.
# =============================================================================
header "SECTION 2: Purge residual Module source files from main-repo index"

MODULES_IN_INDEX=(
  "Modules/Action1"
  "Modules/AssetManagement"
  "Modules/ContractManager"
  "Modules/Crm"
  "Modules/PIB"
  "Modules/Payment"
)

for m in "${MODULES_IN_INDEX[@]}"; do
  # If HEAD already records this path as a 160000 gitlink, it's a registered
  # submodule. git ls-files prefix-matches the gitlink entry itself, so we
  # must check the mode explicitly rather than relying on ls-files output.
  local_mode=$(git ls-tree HEAD "$m" 2>/dev/null | awk '{print $1}')
  if [[ "$local_mode" == "160000" ]]; then
    log_action "$CYAN" "[SKIP] $m is already a registered submodule (160000 gitlink) — nothing to do."
  elif git ls-files --stage -- "${m}/" | grep -qE "^(100644|100755)"; then
    run "$GIT_RM -r --quiet ${m}/"
  else
    log_action "$CYAN" "[SKIP] No tracked source files found under $m/ — already clean."
  fi
done

# =============================================================================
# SECTION 3: Remove local junk/backup files from disk
#
# These files are NOT tracked (they won't appear in git status), but they
# are noise on disk. Review each before running --apply.
# =============================================================================
header "SECTION 3: Remove local-only junk/backup files from disk"

LOCAL_JUNK=(
  "app/Models/Thread.php.orig"                          # Leftover merge artefact
  "app/Models/User.php.orig"                            # Leftover merge artefact
  "Modules/CaseManager/Services/Action1Service.php.bak" # Old backup of service
)

for f in "${LOCAL_JUNK[@]}"; do
  if [[ -f "$f" ]]; then
    run "$RM_CMD $f"
  else
    log_action "$CYAN" "[SKIP] $f does not exist — nothing to do."
  fi
done

# =============================================================================
# SECTION 4: Remove the freescout empty directory
# =============================================================================
header "SECTION 4: Remove empty /freescout directory"

if [[ -d "freescout" && -z "$(ls -A freescout/)" ]]; then
  run "rmdir freescout"
else
  log_action "$CYAN" "[SKIP] freescout/ is non-empty or does not exist."
fi

# =============================================================================
# SECTION 5: Nested Repository Status Report (informational only)
# =============================================================================
header "SECTION 5: Nested repository report (INFO — no changes made)"

# Dynamically find all nested .git dirs (Modules/* + deployment) and cross-check
# against .gitmodules. Only report paths that are NOT yet registered.
UNREGISTERED=()
for candidate in deployment $(find Modules -maxdepth 1 -mindepth 1 -type d | sort); do
  if [[ -d "$candidate/.git" || -f "$candidate/.git" ]]; then
    if ! grep -q "path = $candidate" .gitmodules 2>/dev/null; then
      UNREGISTERED+=("$candidate")
    fi
  fi
done

if [[ ${#UNREGISTERED[@]} -eq 0 ]]; then
  log_action "$GREEN" "All nested repos are registered as submodules. Nothing to do here."
else
  echo "The following nested repos have a .git dir but are NOT yet in .gitmodules:"
  echo ""
  echo "To register each as a submodule, run:"
  echo ""
  for path in "${UNREGISTERED[@]}"; do
    remote=$(git -C "$path" remote get-url origin 2>/dev/null || echo "<no-remote-set>")
    echo "  git submodule add $remote $path"
  done
fi

echo ""

# =============================================================================
# SUMMARY
# =============================================================================
header "SUMMARY"

if $DRY_RUN; then
  echo ""
  echo -e "${YELLOW}Dry run complete. Review the actions above, then run:${RESET}"
  echo -e "${GREEN}  bash scripts/git-cleanup.sh --apply${RESET}"
else
  echo ""
  log_action "$GREEN" "Cleanup applied. Recommended next steps:"
  echo ""
  echo "  1. Review staged changes:  git diff --cached --stat"
  echo "  2. Verify nothing critical was removed"
  echo "  3. ROTATE the following credentials immediately — they were exposed:"
  echo "       - APP_KEY in .env.docker"
  echo "       - APP_KEY in .env.dusk.local"
  echo "       - DB_PASSWORD / DB_ROOT_PASSWORD in .env.docker"
  echo "  4. Commit:  git commit -m 'chore: untrack secrets, certs, and legacy noise'"
  echo "  5. If the TLS key was pushed to a remote, consider the cert compromised"
  echo "     and regenerate via your CA / mkcert / Let's Encrypt."
  echo "  6. Decide on Submodule vs. Co-located strategy (Section 5 above)."
fi

echo ""
