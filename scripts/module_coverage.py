import re
import sys

with open("reports/coverage-final.txt") as f:
    content = f.read()
    lines = content.split('\n')

# Parse all class entries
module_stats = {}
i = 0
while i < len(lines):
    line = lines[i].rstrip()
    if line and not line.startswith(' ') and line.strip():
        class_name = line.strip()
        if i + 1 < len(lines):
            next_line = lines[i+1]
            m = re.search(r'Lines:\s+([\d.]+)%\s+\(\s*(\d+)/\s*(\d+)\)', next_line)
            if m:
                pct = float(m.group(1))
                covered = int(m.group(2))
                total = int(m.group(3))
                # Determine module
                if class_name.startswith('Modules\\'):
                    parts = class_name.split('\\')
                    module = parts[1] if len(parts) > 1 else 'Unknown'
                elif class_name.startswith('App\\'):
                    module = 'App'
                else:
                    module = 'Other'
                
                if module not in module_stats:
                    module_stats[module] = {'covered': 0, 'total': 0}
                module_stats[module]['covered'] += covered
                module_stats[module]['total'] += total
    i += 1

# Print summary sorted by uncovered lines
results = []
for module, stats in module_stats.items():
    total = stats['total']
    covered = stats['covered']
    uncovered = total - covered
    pct = covered / total * 100 if total > 0 else 0
    results.append((uncovered, pct, covered, total, module))

results.sort(reverse=True)
print(f"{'Module':<35} {'Coverage':>10} {'Covered':>8} {'Total':>8} {'Uncovered':>10}")
print("-" * 75)
for uncovered, pct, covered, total, module in results[:25]:
    print(f"{module:<35} {pct:9.1f}% {covered:8d} {total:8d} {uncovered:10d}")

# Summary
total_covered = sum(s['covered'] for s in module_stats.values())
total_lines = sum(s['total'] for s in module_stats.values())
print(f"\nTotal covered: {total_covered}/{total_lines} = {total_covered/total_lines*100:.2f}%")
print(f"Need for 70%: {int(total_lines * 0.70) - total_covered} more lines")
