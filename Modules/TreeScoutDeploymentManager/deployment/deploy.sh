#!/usr/bin/env bash
# =============================================================================
# TreeScout Module Deployment Script
# =============================================================================
# Usage:
#   ./deploy.sh --code=TREE-XXXX-XXXX
#   ./deploy.sh --code=TREE-XXXX-XXXX --host=https://your-app.com
#
# This script:
#   1. Exchanges a One-Time Activation Code (OTAC) for a short-lived Git token
#   2. Configures Composer with that token
#   3. Runs `composer install` to pull private modules
#
# SECURITY NOTES:
#   - Add auth.json to your .gitignore immediately (see step 3 below)
#   - The token expires within 24 hours; do NOT store it long-term
#   - Run this script over HTTPS only
# =============================================================================

set -euo pipefail

# ── Defaults ────────────────────────────────────────────────────────────────
ACTIVATION_CODE=""
API_HOST="${TSDM_API_HOST:-}"   # Can be pre-set in environment
COMPOSER_CMD="${COMPOSER_CMD:-composer}"

# ── Colour helpers ───────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'
info()    { echo -e "${GREEN}[INFO]${NC}  $*"; }
warn()    { echo -e "${YELLOW}[WARN]${NC}  $*"; }
error()   { echo -e "${RED}[ERROR]${NC} $*" >&2; }
die()     { error "$*"; exit 1; }

# ── Argument parsing ─────────────────────────────────────────────────────────
for arg in "$@"; do
    case "$arg" in
        --code=*) ACTIVATION_CODE="${arg#--code=}" ;;
        --host=*) API_HOST="${arg#--host=}" ;;
        --help|-h)
            echo "Usage: $0 --code=TREE-XXXX-XXXX [--host=https://your-app.com]"
            exit 0
            ;;
        *) die "Unknown argument: $arg" ;;
    esac
done

# ── Validation ────────────────────────────────────────────────────────────────
[[ -z "$ACTIVATION_CODE" ]] && die "Activation code is required. Use: --code=TREE-XXXX-XXXX"
[[ -z "$API_HOST" ]] && die "API host is required. Use: --host=https://your-app.com or set TSDM_API_HOST env var."

API_HOST="${API_HOST%/}"   # strip trailing slash
API_URL="${API_HOST}/api/tsdm/activate"

# ── Dependency checks ─────────────────────────────────────────────────────────
for cmd in curl jq "$COMPOSER_CMD"; do
    command -v "$cmd" &>/dev/null || die "Required command not found: $cmd"
done

# ── Step 1: Exchange OTAC for Git token ───────────────────────────────────────
info "Contacting activation server at ${API_HOST}..."

HTTP_RESPONSE=$(curl -s -w "\n%{http_code}" \
    --max-time 30 \
    -X POST "$API_URL" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    --data-urlencode "code=${ACTIVATION_CODE}")
)

HTTP_BODY=$(echo "$HTTP_RESPONSE" | head -n -1)
HTTP_CODE=$(echo "$HTTP_RESPONSE" | tail -n 1)

if [[ "$HTTP_CODE" != "200" ]]; then
    ERROR_MESSAGE=$(echo "$HTTP_BODY" | jq -r '.message // .error // "Unknown error"' 2>/dev/null || echo "$HTTP_BODY")
    die "Activation failed (HTTP ${HTTP_CODE}): ${ERROR_MESSAGE}"
fi

GIT_TOKEN=$(echo "$HTTP_BODY" | jq -r '.token' 2>/dev/null)
GIT_HOST=$(echo  "$HTTP_BODY" | jq -r '.git_host' 2>/dev/null)
EXPIRES_AT=$(echo "$HTTP_BODY" | jq -r '.expires_at' 2>/dev/null)

[[ "$GIT_TOKEN" == "null" || -z "$GIT_TOKEN" ]] && die "Server returned no token. Check activation code validity."
[[ "$GIT_HOST"  == "null" || -z "$GIT_HOST"  ]] && die "Server returned no git_host."

info "Token received. Git host: ${GIT_HOST} | Expires: ${EXPIRES_AT}"

# ── Step 2: Configure Composer authentication ─────────────────────────────────
info "Configuring Composer authentication for ${GIT_HOST}..."

# Detect provider type from host
if echo "$GIT_HOST" | grep -qi "github"; then
    # GitHub: use github-oauth
    "$COMPOSER_CMD" config "github-oauth.${GIT_HOST}" "$GIT_TOKEN"
    info "Configured GitHub OAuth token."
else
    # GitLab (default): use gitlab-token
    "$COMPOSER_CMD" config "gitlab-token.${GIT_HOST}" "$GIT_TOKEN"
    info "Configured GitLab token."
fi

# ── Step 3: .gitignore safety check ───────────────────────────────────────────
GITIGNORE_FILE=".gitignore"
if [[ -f "$GITIGNORE_FILE" ]]; then
    if ! grep -q "auth.json" "$GITIGNORE_FILE"; then
        warn "auth.json is NOT in your .gitignore. Adding it now..."
        echo "" >> "$GITIGNORE_FILE"
        echo "# Composer auth credentials — NEVER commit this file" >> "$GITIGNORE_FILE"
        echo "auth.json" >> "$GITIGNORE_FILE"
        info "Added auth.json to .gitignore."
    fi
else
    warn "No .gitignore found. Creating one with auth.json excluded..."
    echo "auth.json" > "$GITIGNORE_FILE"
fi

# ── Step 4: Install dependencies ──────────────────────────────────────────────
info "Running composer install..."

"$COMPOSER_CMD" install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# ── Step 5: Clean up token from auth.json after install ───────────────────────
# The token is short-lived, but remove it from auth.json immediately to
# minimise the window it is at rest on disk.
AUTH_JSON="auth.json"
if [[ -f "$AUTH_JSON" ]]; then
    info "Removing token from ${AUTH_JSON} (token is single-use and short-lived)..."
    # Reset the host key to an empty object rather than deleting the file
    # so Composer doesn't prompt for re-auth on subsequent non-private packages.
    if echo "$GIT_HOST" | grep -qi "github"; then
        TMP=$(jq "del(.[\"github-oauth\"][\"${GIT_HOST}\"])" "$AUTH_JSON") && echo "$TMP" > "$AUTH_JSON"
    else
        TMP=$(jq "del(.[\"gitlab-token\"][\"${GIT_HOST}\"])" "$AUTH_JSON") && echo "$TMP" > "$AUTH_JSON"
    fi
    info "Token cleared from auth.json."
fi

# ── Done ──────────────────────────────────────────────────────────────────────
echo ""
echo -e "${GREEN}✓ Deployment complete.${NC} Private modules have been installed."
echo ""
echo "  Next steps:"
echo "  1. Run migrations:  php artisan migrate --force"
echo "  2. Clear caches:    php artisan optimize"
echo "  3. Reload workers:  sudo supervisorctl restart all"
