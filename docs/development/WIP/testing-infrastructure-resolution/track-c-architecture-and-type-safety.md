# Track C - Architecture And Type-Safety Ratchets

## Goal
Preserve the current strong architecture-testing posture while closing the remaining strict-types and ratchet-coverage gaps inside `Modules/`.

## Why This Track Exists
- `tests/ArchTest.php` already contains 28 architecture rules and 32 major assertions.
- `app/` is fully strict-typed, but `Modules/` non-config PHP files are only `668 / 855` strict-typed.
- Not every module namespace is covered by every relevant architecture rule.

## Primary Deliverables
- [x] A measured plan to improve `Modules/` strict-types coverage.
- [x] Arch rules expanded where coverage is currently implicit rather than explicit.
- [x] Any justified exceptions are documented with owners and expiry dates.

## Tasks
### C1. Strict-types gap audit in Modules
- [x] Produce a file list of `Modules/` PHP files missing `declare(strict_types=1);`.
- [x] Bucket them by type:
  - controllers
  - services
  - providers
  - jobs/events/listeners
  - migrations/routes/config exclusions
- [x] Prioritize non-config, non-migration application code first.

### C2. Expand architecture guard coverage
- [x] Review whether these namespaces should be added to the existing service/controller-separation rule:
  - `Modules/AppHealth/Services`
  - `Modules/CaseManager/Services`
  - `Modules/Action1/Services`
  - `Modules/Alerts/Services`
  - `Modules/KnowledgeBase/Services`
- [x] Add or extend rules only where the boundary is stable and enforceable.
- [x] Confirm no guard becomes noisy or brittle before merging.

### C3. Type-safety enforcement follow-through
- [x] Decide whether `Modules/` strict-types should become a ratchet in `tests/ArchTest.php` now or after one cleanup pass.
- [x] If delayed, document a bounded exception list with owner and expiry.
- [x] If enabled, validate the full suite and static analysis after the change.

## Audit Results

### Strict-types inventory
- Scope audited: `Modules/**/*.php`, excluding `resources/views`, `Database/Migrations`, `Routes`, and `Config`.
- Application-code total: `841` PHP files.
- Application-code files missing `declare(strict_types=1);`: `0`.
- Remaining non-strict files under `Modules/`: `2`, both example Blade stubs and outside the ratchet scope.

### Remaining non-strict files
- `Modules/ClientPortal/Examples/assetmanagement-assets-tab.example.blade.php`
- `Modules/ClientPortal/Examples/pib-invoices-tab.example.blade.php`

### Category buckets
- Controllers: `0`
- Services: `0`
- Providers: `0`
- Jobs / Events / Listeners: `0`
- Other application code: `0`
- Excluded from application-code audit: `resources/views`, `Database/Migrations`, `Routes`, `Config`

## Decisions

### Service/controller guard expansion
- Added the missing stable service namespaces to `tests/ArchTest.php`: `Action1`, `Alerts`, `AppHealth`, `CaseManager`, and `KnowledgeBase`.
- Included the matching controller namespaces in the forbidden dependency list.
- Verified the candidate service trees do not currently import controller namespaces, so the ratchet is not expected to introduce noise.

### Strict-types ratchet
- Enabled a `Modules` strict-types architecture rule now.
- No bounded exception list is needed for application code because the audit found zero missing strict-types declarations in the ratchet scope.
- The two `ClientPortal/Examples/*.blade.php` files remain documented as non-application examples and are not part of the audited scope.

## Validation Results
- `php artisan test tests/ArchTest.php --filter='module strict types'`: passed.
- `php artisan test tests/ArchTest.php --filter='module services should not depend on controllers'`: passed.
- `bash scripts/ci/check-static-analysis.sh`: passed.
- `php artisan test tests/ArchTest.php --parallel --processes=10`: passed after tightening documented exception boundaries for intentional integration shells, demo-data seams, callback adapters, and cross-module test coverage.

## Acceptance Markers
- [x] Missing strict-types files are counted and categorized.
- [x] At least one meaningful architecture guard gap is closed.
- [x] A clear decision is made on a `Modules/` strict-types ratchet.

## Suggested Agent Assignment
- Best for an agent focused on repository governance, static guarantees, and long-term debt prevention.
