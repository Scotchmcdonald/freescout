#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="${1:-.}"
REPORT_DIR="${ROOT_DIR}/reports"
mkdir -p "${REPORT_DIR}"

TIMESTAMP="$(date +%Y%m%d-%H%M%S)"
REPORT_FILE="${REPORT_DIR}/markdown-link-check-${TIMESTAMP}.log"
LATEST_LINK="${REPORT_DIR}/markdown-link-check-latest.log"

errors=0
checked=0

is_ignored_path() {
    case "$1" in
        */.git/*|*/node_modules/*|*/vendor/*|*/storage/*|*/public/build/*)
            return 0
            ;;
        *)
            return 1
            ;;
    esac
}

extract_links() {
    # Extract Markdown links of the form [label](target).
    # This intentionally ignores autolinks and HTML links.
    grep -oE '\[[^][]+\]\(([^)]+)\)' "$1" | sed -E 's/.*\(([^)]+)\)/\1/' || true
}

resolve_target() {
    local source_file="$1"
    local target="$2"
    local source_dir
    source_dir="$(dirname "$source_file")"

    if [[ "$target" == /* ]]; then
        realpath -m "${ROOT_DIR}${target}"
    else
        realpath -m "${source_dir}/${target}"
    fi
}

{
    echo "Markdown link check report"
    echo "Generated: $(date -Iseconds)"
    echo "Root: ${ROOT_DIR}"
    echo

    while IFS= read -r file; do
        if is_ignored_path "$file"; then
            continue
        fi

        while IFS= read -r raw_link; do
            checked=$((checked + 1))

            case "$raw_link" in
                http://*|https://*|mailto:*|tel:*|\#*)
                    continue
                    ;;
            esac

            link_no_query="${raw_link%%\?*}"
            link_path="${link_no_query%%#*}"

            if [[ -z "$link_path" ]]; then
                continue
            fi

            resolved="$(resolve_target "$file" "$link_path")"

            if [[ ! -e "$resolved" ]]; then
                errors=$((errors + 1))
                printf 'BROKEN | %s | %s\n' "$file" "$raw_link"
            fi
        done < <(extract_links "$file")
    done < <(find "$ROOT_DIR" -type f -name '*.md' | sort)

    echo
    echo "Links checked: ${checked}"
    echo "Broken links: ${errors}"
} > "$REPORT_FILE"

cat "$REPORT_FILE"

ln -sfn "$(basename "$REPORT_FILE")" "$LATEST_LINK"

if [[ "$errors" -gt 0 ]]; then
    echo "Markdown link check failed. See ${REPORT_FILE}" >&2
    exit 1
fi

echo "Markdown link check passed. Report: ${REPORT_FILE}"
