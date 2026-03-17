#!/usr/bin/env bash
# ==============================================================================
# UI/UX STANDARDS AUDITOR
# Enforces theme-agnostic UI rules across UI files by default.
# Use --changed for a local, incremental scan.
# ==============================================================================

set -u

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

SCAN_ALL=true
if [[ "${1:-}" == "--changed" ]]; then
    SCAN_ALL=false
fi

echo "--> Running UI/UX standards check..."

TAILWIND_HARDCODED_COLORS='\b(bg|text|border|ring|from|to|via|stroke|fill)-(slate|gray|zinc|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose)-(50|100|200|300|400|500|600|700|800|900|950)\b'
INLINE_HARDCODED_COLORS='style\s*=\s*["'"'"'][^"'"'"']*(color|background|background-color|border-color)\s*:\s*(#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})|rgb\(|rgba\(|hsl\(|hsla\()'

# Optional inline escape hatch for justified exceptions:
# <!-- uiux-ignore --> or // uiux-ignore
IGNORE_MARKER='uiux-ignore'

collect_targets() {
    if [[ "$SCAN_ALL" == true ]]; then
        find resources Modules themes -type f \( \
            -name '*.blade.php' -o \
            -name '*.vue' -o \
            -name '*.jsx' -o \
            -name '*.tsx' -o \
            -name '*.js' -o \
            -name '*.ts' \
        \) 2>/dev/null | sort -u
        return
    fi

    local base_ref="${BASE_REF:-}"
    local changed=""

    if [[ -n "$base_ref" ]]; then
        changed="$(git diff --name-only "$base_ref...HEAD" 2>/dev/null || true)"
    fi

    if [[ -z "$changed" ]]; then
        changed="$(
            {
                git diff --name-only --cached 2>/dev/null
                git diff --name-only 2>/dev/null
            } | sort -u
        )"
    fi

    printf '%s\n' "$changed" | grep -E '^(resources|Modules|themes)/.*\.(blade\.php|vue|jsx|tsx|js|ts)$' | while read -r f; do
        [[ -f "$f" ]] && echo "$f"
    done | sort -u
}

TARGET_FILES="$(collect_targets)"

if [[ -z "$TARGET_FILES" ]]; then
    echo "✅ AUDIT PASSED: No UI files to scan."
    exit 0
fi

echo "Scanning $(echo "$TARGET_FILES" | wc -l | tr -d ' ') file(s)..."

FAIL=0

echo ""
echo "Rule 1: No hardcoded Tailwind palette colors (use semantic theme classes)."
R1_MATCHES="$(echo "$TARGET_FILES" | xargs -r grep -nE "$TAILWIND_HARDCODED_COLORS" 2>/dev/null | grep -v "$IGNORE_MARKER" || true)"
if [[ -n "$R1_MATCHES" ]]; then
    echo "❌ FAIL: Found hardcoded Tailwind palette classes:"
    echo "$R1_MATCHES"
    FAIL=1
else
    echo "✅ PASS"
fi

echo ""
echo "Rule 2: No inline hardcoded color values in style attributes (use CSS variables/theme tokens)."
R2_MATCHES="$(echo "$TARGET_FILES" | xargs -r grep -nE "$INLINE_HARDCODED_COLORS" 2>/dev/null | grep -v "$IGNORE_MARKER" || true)"
if [[ -n "$R2_MATCHES" ]]; then
    echo "❌ FAIL: Found inline hardcoded color values:"
    echo "$R2_MATCHES"
    FAIL=1
else
    echo "✅ PASS"
fi

echo ""
if [[ $FAIL -ne 0 ]]; then
    echo "❌ AUDIT FAILED: UI/UX standards violations found."
    echo "Guidance: docs/development/UX_STYLE_GUIDE.md"
    echo "Tip: add '$IGNORE_MARKER' only for justified exceptions."
    exit 1
fi

echo "✅ AUDIT PASSED: UI/UX standards check passed."
exit 0
