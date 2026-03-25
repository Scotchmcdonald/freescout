# Phase 2 - Agent Dispatch Map

## Purpose
Use this file as a quick routing sheet when distributing the remediation work across multiple agents.

## Dispatch Slots

### Agent 1 - Reliability Lead
- File: [track-a-reliability-and-mutation.md](track-a-reliability-and-mutation.md)
- Mission: restore the failing guard, add side-effect assertions to the 3 flagged tests, and generate the first mutation baseline.
- Validation target:
  - `php artisan test --parallel --processes=10`
  - `./vendor/bin/infection --threads=8 --skip-initial-tests`

### Agent 2 - Boundary Lead
- File: [track-b-boundary-and-assertion-depth.md](track-b-boundary-and-assertion-depth.md)
- Mission: close gaps in Actions, Policies, and FormRequest validation while upgrading write-endpoint assertions.
- Validation target:
  - targeted `php artisan test` paths for changed suites
  - final full-suite rerun

### Agent 3 - Governance Lead
- File: [track-c-architecture-and-type-safety.md](track-c-architecture-and-type-safety.md)
- Mission: increase `Modules/` strict-types adoption and extend stable architecture ratchets.
- Validation target:
  - `bash scripts/ci/check-static-analysis.sh`
  - `php artisan test tests/ArchTest.php --parallel --processes=10`

### Agent 4 - Velocity Lead
- File: [track-d-velocity-and-lane-governance.md](track-d-velocity-and-lane-governance.md)
- Mission: keep Unit tests framework-free, classify facade usage, and make skipped-test governance operational.
- Validation target:
  - targeted Unit lane
  - lane budget checks

## Merge Order
1. Agent 1 first if the goal is to restore a fully green suite immediately.
2. Agents 2, 3, and 4 can proceed in parallel after Agent 1 has reproduced the current guard failure.
3. Reconcile overlapping edits in `tests/Pest.php`, `tests/ArchTest.php`, and any shared support helpers before the final CI run.

## Final Exit Gate
- [ ] Full suite green.
- [ ] Static analysis green.
- [ ] CI green.
- [ ] Mutation baseline captured.
- [ ] WIP folder removed after completion and verification.
