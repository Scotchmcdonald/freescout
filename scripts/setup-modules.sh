#!/usr/bin/env bash
#===============================================================================
# Module Bootstrap — Developer Setup & CI
#
# Clones (or updates) the module repositories required for a given profile.
# Run this once after cloning the core application repo, which is PUBLIC and
# contains no module code (Modules/ is gitignored in the core repo).
#
# All module repos at github.com/BorealTek/* are PRIVATE.
# REPO_TOKEN must be set to a GitHub personal access token with repo scope.
#
# Usage:
#   ./scripts/setup-modules.sh                # uses 'full' profile
#   ./scripts/setup-modules.sh core-msp       # named profile
#   ./scripts/setup-modules.sh --list         # show available profiles
#
# Authentication:
#   export REPO_TOKEN="ghp_..."   # GitHub PAT with repo scope
#
# Requirements:
#   git, jq  (sudo apt install jq  /  brew install jq)
#===============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
MANIFEST="$ROOT_DIR/deployment/modules.manifest.json"
TOKEN="${REPO_TOKEN:-}"
PROFILE="${1:-full}"

#-------------------------------------------------------------------------------
# Colors
#-------------------------------------------------------------------------------
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
CYAN='\033[0;36m'
RED='\033[0;31m'
BOLD='\033[1m'
NC='\033[0m'

log_info()    { echo -e "${CYAN}  ▸${NC} $*"; }
log_ok()      { echo -e "${GREEN}  ✔${NC} $*"; }
log_warn()    { echo -e "${YELLOW}  ⚠${NC} $*"; }
log_error()   { echo -e "${RED}  ✖${NC} $*" >&2; }
log_heading() { echo -e "\n${BOLD}$*${NC}"; }

#-------------------------------------------------------------------------------
# Preflight checks
#-------------------------------------------------------------------------------
check_deps() {
    local missing=()
    command -v git &>/dev/null || missing+=("git")
    command -v jq  &>/dev/null || missing+=("jq")
    if [ ${#missing[@]} -gt 0 ]; then
        log_error "Missing required tools: ${missing[*]}"
        echo "  Install with: sudo apt install ${missing[*]}   (or: brew install ${missing[*]})"
        exit 1
    fi
    if [ ! -f "$MANIFEST" ]; then
        log_error "Manifest not found: $MANIFEST"
        exit 1
    fi
}

#-------------------------------------------------------------------------------
# Fail fast before touching any repos if REPO_TOKEN is absent and the profile
# contains private modules.
#-------------------------------------------------------------------------------
check_auth() {
    local -a modules=("$@")
    local missing_private=()

    for name in "${modules[@]}"; do
        local is_private
        is_private=$(jq -r ".modules[\"$name\"].private // false" "$MANIFEST")
        if [ "$is_private" = "true" ] && [ -z "$TOKEN" ]; then
            missing_private+=("$name")
        fi
    done

    if [ ${#missing_private[@]} -gt 0 ]; then
        echo ""
        log_error "REPO_TOKEN is not set, but the following modules are PRIVATE:"
        for m in "${missing_private[@]}"; do
            local repo
            repo=$(jq -r ".modules[\"$m\"].repo" "$MANIFEST")
            echo "      ${m}  →  ${repo}"
        done
        echo ""
        echo "  The core app repo (Scotchmcdonald/freescout) is PUBLIC."
        echo "  All BorealTek/* module repos are PRIVATE and require a token."
        echo ""
        echo "  Create a GitHub PAT with repo scope, then retry:"
        echo "    export REPO_TOKEN=\"ghp_...\""
        echo "    ./scripts/setup-modules.sh $PROFILE"
        exit 1
    fi

    if [ -n "$TOKEN" ]; then
        log_ok "REPO_TOKEN is set — private repos accessible"
    fi
}

#-------------------------------------------------------------------------------
# List available profiles
#-------------------------------------------------------------------------------
list_profiles() {
    log_heading "Available deployment profiles:"
    echo ""
    jq -r '
      .profiles | to_entries[] |
      "  \(.key)\n    \(.value.description)\n    Modules (\(.value.modules | length)): \(.value.modules | join(", "))\n"
    ' "$MANIFEST"
    echo "  Core app (always required, PUBLIC):"
    echo "    $(jq -r '.core.repo' "$MANIFEST")  @  $(jq -r '.core.branch' "$MANIFEST")"
    echo ""
}

#-------------------------------------------------------------------------------
# Clone or update a single module
#-------------------------------------------------------------------------------
clone_or_update_module() {
    local name="$1"
    local target="$ROOT_DIR/Modules/$name"

    local repo branch is_private
    repo=$(jq -r ".modules[\"$name\"].repo // empty" "$MANIFEST")
    branch=$(jq -r ".modules[\"$name\"].branch // \"main\"" "$MANIFEST")
    is_private=$(jq -r ".modules[\"$name\"].private // false" "$MANIFEST")

    if [ -z "$repo" ]; then
        log_warn "$name — not found in manifest, skipping"
        return
    fi

    # Inject token only for private repos
    local clone_url="$repo"
    if [ "$is_private" = "true" ] && [ -n "$TOKEN" ]; then
        clone_url="https://oauth2:${TOKEN}@${repo#https://}"
    fi

    if [ -d "$target/.git" ]; then
        log_info "$name — updating  (branch: $branch)"
        git -C "$target" fetch origin --quiet
        git -C "$target" checkout "$branch" --quiet 2>/dev/null || \
            git -C "$target" checkout -b "$branch" "origin/$branch" --quiet
        git -C "$target" pull origin "$branch" --quiet
        log_ok "$name — up to date"
    else
        if [ -d "$target" ] && [ "$(ls -A "$target" 2>/dev/null)" ]; then
            log_warn "$name — directory exists but has no .git (untracked local files)."
            log_warn "         Push to $repo first, then remove $target and retry."
            return
        fi
        local visibility_label
        [ "$is_private" = "true" ] && visibility_label="private" || visibility_label="public"
        log_info "$name — cloning [$visibility_label]  @  $branch"
        git clone -b "$branch" "$clone_url" "$target" --quiet
        log_ok "$name — cloned"
    fi
}

#-------------------------------------------------------------------------------
# Write modules_statuses.json so only the selected profile's modules are
# enabled. Modules outside the profile are explicitly set to false so that
# any leftover Modules/ directories from a previous wider profile don't
# accidentally activate on boot.
#-------------------------------------------------------------------------------
update_statuses() {
    local -a profile_modules=("$@")
    local statuses_file="$ROOT_DIR/modules_statuses.json"

    local all_modules
    all_modules=$(jq -r '.modules | keys[]' "$MANIFEST")

    local json="{"
    local first=true
    for mod in $all_modules; do
        [ "$first" = true ] || json+=","
        first=false
        local enabled=false
        for pm in "${profile_modules[@]}"; do
            [ "$pm" = "$mod" ] && { enabled=true; break; }
        done
        json+="\"$mod\": $enabled"
    done
    json+="}"

    echo "$json" | jq '.' > "$statuses_file"
    log_ok "modules_statuses.json — ${#profile_modules[@]} module(s) enabled, rest disabled"
}

#-------------------------------------------------------------------------------
# Main
#-------------------------------------------------------------------------------
main() {
    check_deps

    if [ "$PROFILE" = "--list" ] || [ "$PROFILE" = "-l" ]; then
        list_profiles
        exit 0
    fi

    if ! jq -e ".profiles[\"$PROFILE\"]" "$MANIFEST" > /dev/null 2>&1; then
        log_error "Unknown profile: '$PROFILE'"
        echo ""
        list_profiles
        exit 1
    fi

    local description
    description=$(jq -r ".profiles[\"$PROFILE\"].description" "$MANIFEST")
    mapfile -t modules < <(jq -r ".profiles[\"$PROFILE\"].modules[]" "$MANIFEST")

    # Print plan
    log_heading "Module Bootstrap"
    printf "  %-14s %s\n"    "Profile:"     "$PROFILE"
    printf "  %-14s %s\n"    "Description:" "$description"
    printf "  %-14s %d module(s)\n" "Selecting:"  "${#modules[@]}"
    echo ""
    echo "  Core app  [PUBLIC]   →  $(jq -r '.core.repo' "$MANIFEST")"
    echo "  Modules   [PRIVATE]  →  github.com/BorealTek/*  (REPO_TOKEN required)"
    echo ""

    # Auth check — exits if token missing for any private module in profile
    check_auth "${modules[@]}"

    # Clone / update
    log_heading "Cloning / updating..."
    echo ""
    for name in "${modules[@]}"; do
        clone_or_update_module "$name"
    done

    # Sync statuses
    echo ""
    log_heading "Syncing modules_statuses.json..."
    update_statuses "${modules[@]}"

    # Summary
    echo ""
    log_ok "Profile '$PROFILE' ready."
    echo ""
    echo "  Enabled modules:"
    for m in "${modules[@]}"; do
        echo "    • $m"
    done
    echo ""
    echo "  Next steps:"
    echo "    php artisan module:list"
    echo "    php artisan migrate"
    echo ""
}

main
