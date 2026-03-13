#!/usr/bin/env bash
# ==============================================================================
# ENVIRONMENT PARITY AUDITOR
# Fails if required keys in .env.example are missing from the testing environment.
# ==============================================================================

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
echo "--> Checking .env.example parity..."

ENV_EXAMPLE="$ROOT_DIR/.env.example"
# Change this to .env if you want to audit your local dev file instead
ENV_TARGET="$ROOT_DIR/.env.testing"

if [ ! -f "$ENV_EXAMPLE" ] || [ ! -f "$ENV_TARGET" ]; then
    echo "⚠️  WARNING: Missing $ENV_EXAMPLE or $ENV_TARGET. Skipping check."
    exit 0
fi

# Extract keys, stripping comments and empty lines
EXAMPLE_KEYS=$(grep -v '^#' "$ENV_EXAMPLE" | grep -v '^\s*$' | cut -d= -f1 | sort)
TARGET_KEYS=$(grep -v '^#' "$ENV_TARGET" | grep -v '^\s*$' | cut -d= -f1 | sort)

# Compare the two lists and find what exists in EXAMPLE but not in TARGET
MISSING_KEYS=$(comm -23 <(echo "$EXAMPLE_KEYS") <(echo "$TARGET_KEYS"))

if [ -n "$MISSING_KEYS" ]; then
    echo "❌ AUDIT FAILED: The following keys are documented in .env.example but missing from $(basename "$ENV_TARGET"):"
    echo ""
    echo "$MISSING_KEYS"
    exit 1
fi

echo "✅ AUDIT PASSED: Environment variables are in parity."
exit 0
