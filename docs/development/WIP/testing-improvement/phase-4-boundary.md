# Phase 4 - Boundary Coverage (Validation, Authorization, Throttling)

## Goal
Increase depth of edge-case verification for high-risk behavior boundaries.

## Baseline (2026-03-25)
- Large test suite exists, but boundary concentration is not yet explicitly measured in one place.

## Plan
1. Add a lightweight boundary inventory script that counts tests tagged or named for validation/auth/throttle concerns.
2. Add a minimum floor and trend output in the consolidated quality report.
3. Prioritize missing boundary tests in hotspot namespaces surfaced by failures.

## Deliverables
- Boundary subsection in quality report with counts and trend note.
- Follow-up list of candidate namespaces for boundary expansion.

## Success Criteria
- Boundary coverage becomes measurable release-over-release.
- CI report includes explicit boundary health snapshot.

---

## Wave 2 Assessment (2026-03-25)

### Findings from live data (boundary report + gate)
- **389 boundary hits across 510 test files** — overall density is healthy.
- **54 of 101 namespaces have zero boundary hits** — mostly `Browser/*` (acceptable) but critically also:
  - `Feature/Auth` (6 files, 0 hits) — login/logout/session expiry edge cases are untested at boundary.
  - `Integration/Events` (8 files, 0 hits) — event-driven flows have no auth/validation assertions.
  - `Integration/CrossModule` (4 files, 0 hits) — cross-module workflows don't assert rejection paths.
  - `Feature/ClientPortal` (1 file, 0 hits) — portal access control is unverified.
- `check-boundary-namespace-report.php` now generates `reports/boundary-coverage-latest.md` with full per-namespace table and zero-hit file lists.

### Wave 2 Actions (Priority Order)
1. **`Feature/Auth`** — Add boundary tests for: failed login (422), locked account (403), expired session (401), and rate-limited login (429). This is the highest-risk gap.
2. **`Integration/CrossModule`** — Assert that cross-module workflows correctly reject malformed or unauthorized input before propagating events.
3. **`Feature/ClientPortal`** — Add tests asserting portal routes reject unauthenticated and unauthorised (403) guests.
4. Set `--min-density=0.5` in CI once the `Feature/Auth` and `Feature/ClientPortal` gaps are closed to enforce future density standards.
