# Track C - Architecture And Type-Safety Ratchets

## Goal
Preserve the current strong architecture-testing posture while closing the remaining strict-types and ratchet-coverage gaps inside `Modules/`.

## Why This Track Exists
- `tests/ArchTest.php` already contains 28 architecture rules and 32 major assertions.
- `app/` is fully strict-typed, but `Modules/` non-config PHP files are only `668 / 855` strict-typed.
- Not every module namespace is covered by every relevant architecture rule.

## Primary Deliverables
- [ ] A measured plan to improve `Modules/` strict-types coverage.
- [ ] Arch rules expanded where coverage is currently implicit rather than explicit.
- [ ] Any justified exceptions are documented with owners and expiry dates.

## Tasks
### C1. Strict-types gap audit in Modules
- [ ] Produce a file list of `Modules/` PHP files missing `declare(strict_types=1);`.
- [ ] Bucket them by type:
  - controllers
  - services
  - providers
  - jobs/events/listeners
  - migrations/routes/config exclusions
- [ ] Prioritize non-config, non-migration application code first.

### C2. Expand architecture guard coverage
- [ ] Review whether these namespaces should be added to the existing service/controller-separation rule:
  - `Modules/AppHealth/Services`
  - `Modules/CaseManager/Services`
  - `Modules/Action1/Services`
  - `Modules/Alerts/Services`
  - `Modules/KnowledgeBase/Services`
- [ ] Add or extend rules only where the boundary is stable and enforceable.
- [ ] Confirm no guard becomes noisy or brittle before merging.

### C3. Type-safety enforcement follow-through
- [ ] Decide whether `Modules/` strict-types should become a ratchet in `tests/ArchTest.php` now or after one cleanup pass.
- [ ] If delayed, document a bounded exception list with owner and expiry.
- [ ] If enabled, validate the full suite and static analysis after the change.

## Acceptance Markers
- [ ] Missing strict-types files are counted and categorized.
- [ ] At least one meaningful architecture guard gap is closed.
- [ ] A clear decision is made on a `Modules/` strict-types ratchet.

## Suggested Agent Assignment
- Best for an agent focused on repository governance, static guarantees, and long-term debt prevention.
