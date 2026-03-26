import re
import os
import sys

with open("reports/coverage-final.txt") as f:
    content = f.read()

# Parse all class names from coverage report
covered_classes = set()
for match in re.finditer(r'^(\S[^\n]+)\n\s+Methods:', content, re.MULTILINE):
    covered_classes.add(match.group(1).strip())

# Also find classes with low coverage 
low_coverage = []
lines = content.split('\n')
i = 0
while i < len(lines):
    line = lines[i]
    if line and not line.startswith(' ') and line.strip():
        class_name = line.strip()
        if i + 1 < len(lines):
            next_line = lines[i+1]
            m = re.search(r'Lines:\s+([\d.]+)%\s+\(\s*(\d+)/\s*(\d+)\)', next_line)
            if m:
                pct = float(m.group(1))
                covered = int(m.group(2))
                total = int(m.group(3))
                uncovered = total - covered
                if uncovered >= 20 and pct < 50:
                    low_coverage.append((uncovered, pct, class_name))
    i += 1

low_coverage.sort(reverse=True)
print("=== CLASSES WITH 20+ UNCOVERED LINES AND <50% COVERAGE ===")
for u, p, n in low_coverage[:50]:
    print(f'{u:4d} ({p:5.1f}%): {n}')
