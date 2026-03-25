#!/usr/bin/env python3
"""
parse-coverage-index.py
-----------------------
Parse storage/infection/coverage/index.xml and print a clean
namespace-level coverage summary table.

The XML has a nested <directory> tree. File elements carry an href
attribute with the relative path (e.g. "Modules/Crm/Http/CrmController.php.xml").
We read per-file totals from the individual XML files stored next to
index.xml (referenced via href), but since those require extra I/O we
instead accumulate from the <totals> child inside each per-directory
element when available, or from the index itself.

Usage:
    python3 scripts/testing/parse-coverage-index.py [options]

Options:
    --all            Include view/blade files in totals (default: excluded)
    --sort-name      Sort alphabetically (default: sort by gap, largest first)
    --min-exe <N>    Only show namespaces with >= N executable lines (default: 50)
    --depth <N>      Group at this many path segments (default: 2, e.g. Modules/Crm)
"""

import xml.etree.ElementTree as ET
import sys
from pathlib import Path

# ─────────────────────────────────────────────────────────
# Config
# ─────────────────────────────────────────────────────────
COVERAGE_XML = Path(__file__).parent.parent.parent / "storage/infection/coverage/index.xml"
XML_DIR = COVERAGE_XML.parent  # individual file XML reports live here

BLADE_KEYWORDS = ("resources/views", ".blade.php")

filter_views = "--all" not in sys.argv
sort_by = "name" if "--sort-name" in sys.argv else "gap"

_min_exe_idx = sys.argv.index("--min-exe") if "--min-exe" in sys.argv else -1
min_exe = int(sys.argv[_min_exe_idx + 1]) if _min_exe_idx >= 0 else 50

_depth_idx = sys.argv.index("--depth") if "--depth" in sys.argv else -1
GROUP_DEPTH = int(sys.argv[_depth_idx + 1]) if _depth_idx >= 0 else 2


def is_blade(path: str) -> bool:
    return any(kw in path for kw in BLADE_KEYWORDS)


def namespace_key(href: str) -> str:
    """
    href is a relative path like "Modules/Crm/Http/CrmController.php.xml".
    Strip .xml suffix, then take first GROUP_DEPTH segments.
    """
    p = href.removesuffix(".xml").replace("\\", "/")
    parts = p.split("/")
    return "/".join(parts[:GROUP_DEPTH]) if len(parts) >= GROUP_DEPTH else parts[0]


# ─────────────────────────────────────────────────────────
# Parse XML — walk directory tree, read per-file XML reports
# ─────────────────────────────────────────────────────────
if not COVERAGE_XML.exists():
    print(f"ERROR: coverage XML not found at {COVERAGE_XML}")
    print("Run: XDEBUG_MODE=coverage php -d memory_limit=3G vendor/bin/pest --coverage-xml storage/infection/coverage")
    sys.exit(1)

tree = ET.parse(COVERAGE_XML)
root = tree.getroot()

# Strip XML namespace prefix for easier querying
# The document uses xmlns="https://schema.phpunit.de/coverage/1.0"
XMLNS = "{https://schema.phpunit.de/coverage/1.0}"

namespaces: dict[str, dict] = {}


def read_file_totals(href: str) -> tuple[int, int]:
    """Read executable/executed line counts from the per-file XML report."""
    xml_path = XML_DIR / href
    if not xml_path.exists():
        return 0, 0
    try:
        ft = ET.parse(xml_path)
        fr = ft.getroot()
        # Look for <totals><lines executable="..." executed="..."/>
        for lines_elem in fr.iter(f"{XMLNS}lines"):
            exe = int(lines_elem.get("executable", 0))
            cov = int(lines_elem.get("executed", 0))
            if exe > 0:
                return exe, cov
        # Fallback: bare element without namespace
        for lines_elem in fr.iter("lines"):
            exe = int(lines_elem.get("executable", 0))
            cov = int(lines_elem.get("executed", 0))
            if exe > 0:
                return exe, cov
    except ET.ParseError:
        pass
    return 0, 0


def walk_directory(dir_elem):
    """Recursively walk <directory> elements and accumulate per-file stats."""
    for child in dir_elem:
        tag = child.tag.replace(XMLNS, "")
        if tag == "directory":
            walk_directory(child)
        elif tag == "file":
            href = child.get("href", "")
            if not href:
                continue
            if filter_views and is_blade(href):
                continue
            ns = namespace_key(href)
            exe, cov = read_file_totals(href)
            if exe == 0:
                continue
            if ns not in namespaces:
                namespaces[ns] = {"exe": 0, "cov": 0}
            namespaces[ns]["exe"] += exe
            namespaces[ns]["cov"] += cov


# Find the root <project> or <directory name="/"> element
project = root.find(f"{XMLNS}project") or root.find("project")
if project is None:
    project = root  # fallback: walk from root

for child in project:
    tag = child.tag.replace(XMLNS, "")
    if tag == "directory":
        walk_directory(child)

# ─────────────────────────────────────────────────────────
# Build summary
# ─────────────────────────────────────────────────────────
rows = []
total_exe = 0
total_cov = 0

for ns_key, data in namespaces.items():
    exe = data["exe"]
    cov = data["cov"]
    if exe < min_exe:
        continue
    pct = (cov / exe * 100) if exe > 0 else 0.0
    gap = exe - cov
    rows.append((ns_key, exe, cov, pct, gap))
    total_exe += exe
    total_cov += cov

if sort_by == "gap":
    rows.sort(key=lambda r: r[4], reverse=True)  # largest gap first
else:
    rows.sort(key=lambda r: r[0])

# ─────────────────────────────────────────────────────────
# Print
# ─────────────────────────────────────────────────────────
HEADER = f"{'Namespace':<45} {'Exec':>7} {'Covd':>7} {'%':>7} {'Gap':>7}"
SEP = "-" * len(HEADER)

print()
print("Coverage Summary — PHP Source")
print(f"XML: {COVERAGE_XML}")
if filter_views:
    print("(views/blade files excluded)")
print()
print(HEADER)
print(SEP)

for ns_key, exe, cov, pct, gap in rows:
    bar = "🔴" if pct < 30 else ("🟡" if pct < 65 else "🟢")
    print(f"{ns_key:<45} {exe:>7,} {cov:>7,} {pct:>6.1f}% {gap:>7,}  {bar}")

print(SEP)
total_pct = (total_cov / total_exe * 100) if total_exe > 0 else 0.0
total_gap = total_exe - total_cov
print(f"{'TOTAL':<45} {total_exe:>7,} {total_cov:>7,} {total_pct:>6.1f}% {total_gap:>7,}")
print()

# Target progress
TARGET = 80.0
target_lines = int(total_exe * TARGET / 100)
needed = max(0, target_lines - total_cov)
print(f"Target: {TARGET}%  →  need {needed:,} more lines covered (currently {total_pct:.1f}%)")
if needed == 0:
    print("✅ TARGET REACHED")
else:
    phases_est = needed / 3500  # rough estimate of 3,500 lines per phase
    print(f"   ≈ {phases_est:.1f} 'phases' of ~3,500 lines each")
print()
