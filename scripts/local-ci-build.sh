#!/usr/bin/env bash
set -euo pipefail
IFS=$'\n\t'

# Mirrors .github/workflows/build-and-push.yml locally:
# - Reads deployment/modules.manifest.json
# - Clones modules for selected profiles into an isolated temp context
# - Builds Dockerfile.prod with matching PROFILE/BUILD_DATE/VCS_REF args

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
MANIFEST_PATH="$ROOT_DIR/deployment/modules.manifest.json"

PROFILES="all"
NO_CACHE="false"
DRY_RUN="false"

usage() {
  cat <<'EOF'
Usage:
  bash scripts/local-ci-build.sh [--profiles=all|csv] [--no-cache] [--dry-run]

Examples:
  bash scripts/local-ci-build.sh --profiles=core-msp
  bash scripts/local-ci-build.sh --profiles=full,google-workspace-msp --no-cache
  bash scripts/local-ci-build.sh --dry-run

Required env:
  REPO_TOKEN   GitHub PAT with repo access to BorealTek private module repos.
EOF
}

for arg in "$@"; do
  case "$arg" in
    --profiles=*) PROFILES="${arg#--profiles=}" ;;
    --no-cache) NO_CACHE="true" ;;
    --dry-run) DRY_RUN="true" ;;
    -h|--help) usage; exit 0 ;;
    *) echo "Unknown arg: $arg"; usage; exit 1 ;;
  esac
done

require_cmd() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "Missing required command: $1"
    exit 1
  fi
}

require_cmd git
require_cmd docker
require_cmd php

if [[ ! -f "$MANIFEST_PATH" ]]; then
  echo "Manifest not found: $MANIFEST_PATH"
  exit 1
fi

if [[ -z "${REPO_TOKEN:-}" ]]; then
  echo "REPO_TOKEN is required in your shell environment."
  echo "Example: export REPO_TOKEN=ghp_xxx"
  exit 1
fi

profiles_from_manifest() {
  php -r '
    $m = json_decode(file_get_contents($argv[1]), true);
    foreach (array_keys($m["profiles"]) as $p) echo $p, PHP_EOL;
  ' "$MANIFEST_PATH"
}

resolve_profiles() {
  if [[ "$PROFILES" == "all" ]]; then
    profiles_from_manifest
    return
  fi

  IFS=',' read -r -a requested <<< "$PROFILES"
  for p in "${requested[@]}"; do
    p="${p// /}"
    if [[ -n "$p" ]]; then
      echo "$p"
    fi
  done
}

validate_profile() {
  local profile="$1"
  php -r '
    $m = json_decode(file_get_contents($argv[1]), true);
    if (!isset($m["profiles"][$argv[2]])) { fwrite(STDERR, "Invalid profile: {$argv[2]}\n"); exit(1); }
  ' "$MANIFEST_PATH" "$profile"
}

modules_for_profile() {
  local profile="$1"
  php -r '
    $m = json_decode(file_get_contents($argv[1]), true);
    foreach (($m["profiles"][$argv[2]]["modules"] ?? []) as $module) {
      $repo = $m["modules"][$module]["repo"] ?? "";
      $branch = $m["modules"][$module]["branch"] ?? "main";
      echo $module, "\t", $repo, "\t", $branch, PHP_EOL;
    }
  ' "$MANIFEST_PATH" "$profile"
}

SHA="$(git -C "$ROOT_DIR" rev-parse --short HEAD 2>/dev/null || echo local)"
BUILD_DATE="$(date -u +%Y-%m-%dT%H:%M:%SZ)"

WORK_DIR="$(mktemp -d)"
trap 'rm -rf "$WORK_DIR"' EXIT
CONTEXT_DIR="$WORK_DIR/context"

# Keep context isolated so local workspace is not mutated.
if command -v rsync >/dev/null 2>&1; then
  rsync -a --delete \
    --exclude '.git' \
    --exclude 'node_modules' \
    --exclude 'vendor' \
    --exclude 'storage/logs' \
    "$ROOT_DIR/" "$CONTEXT_DIR/"
else
  mkdir -p "$CONTEXT_DIR"
  tar \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='storage/logs' \
    -C "$ROOT_DIR" -cf - . | tar -C "$CONTEXT_DIR" -xf -
fi

echo "Local CI build context: $CONTEXT_DIR"

while IFS= read -r profile; do
  [[ -z "$profile" ]] && continue
  validate_profile "$profile"

  echo ""
  echo "=== Profile: $profile ==="

  rm -rf "$CONTEXT_DIR/Modules"
  mkdir -p "$CONTEXT_DIR/Modules"

  while IFS=$'\t' read -r module repo branch; do
    [[ -z "$module" ]] && continue
    echo "Cloning $module ($branch)"

    if [[ "$DRY_RUN" == "true" ]]; then
      echo "  DRY RUN: git clone --depth=1 --branch $branch https://oauth2:***@${repo#https://} Modules/$module"
      continue
    fi

    git clone --depth=1 --branch "$branch" \
      "https://oauth2:${REPO_TOKEN}@${repo#https://}" \
      "$CONTEXT_DIR/Modules/$module" -q

    rm -rf "$CONTEXT_DIR/Modules/$module/.git"
  done < <(modules_for_profile "$profile")

  IMAGE_TAG="treescout-local:${profile}-test"
  CACHE_ARGS=()
  if [[ "$NO_CACHE" == "true" ]]; then
    CACHE_ARGS+=(--no-cache)
  fi

  if [[ "$DRY_RUN" == "true" ]]; then
    echo "  DRY RUN: docker build -f Dockerfile.prod -t $IMAGE_TAG --build-arg PROFILE=$profile --build-arg BUILD_DATE=$BUILD_DATE --build-arg VCS_REF=$SHA ${CACHE_ARGS[*]} $CONTEXT_DIR"
    continue
  fi

  docker build \
    -f "$CONTEXT_DIR/Dockerfile.prod" \
    -t "$IMAGE_TAG" \
    --build-arg "PROFILE=$profile" \
    --build-arg "BUILD_DATE=$BUILD_DATE" \
    --build-arg "VCS_REF=$SHA" \
    "${CACHE_ARGS[@]}" \
    "$CONTEXT_DIR"

done < <(resolve_profiles)

echo ""
echo "Done. Local CI profile build(s) completed."
